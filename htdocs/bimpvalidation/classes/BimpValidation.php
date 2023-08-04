<?php

class BimpValidation
{

    // Traitements: 

    public static function tryToValidate($object, $object_type, $val = 0, $secteur = '', $extra_data = array(), &$errors = array())
    {
        if (defined('DISABLE_BIMP_VALIDATIONS') && DISABLE_BIMP_VALIDATIONS) {
            return 1;
        }
        
        $rules = self::getValidationsRulesByTypes($object_type, $val, $secteur);

        if (empty($rules)) {
            return 1;
        }

        global $user;
        if (!BimpObject::objectLoaded($user)) {
            $errors[] = 'Aucun utilisateur connecté';
            return 0;
        }

        $global_check = 1;
        foreach ($rules as $type => $type_rules) {
            // Vérif de l'existence d'une demande pour ce type de validation: 
            $demande = BimpCache::findBimpObjectInstance('bimpvalidation', 'BV_Demande', array(
                        'type_validation' => $type,
                        'type_object'     => $object_type,
                        'id_object'       => $object->id
                            ), true);

            if (BimpObject::objectLoaded($demande)) {
                if ((int) $demande->getData('status') > 0) {
                    // La demande est déjà acceptée. 
                    continue;
                } else {
                    if (in_array($user->id, $demande->getData('validation_users'))) {
                        // Acceptation directe de la demande
                        $demande_errors = $demande->setAccepted();

                        if (count($demande_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($demande_errors, 'Echec de l\'acceptation de la demande pour la validation de type "' . $demande->displayDataDefault('type') . '"');
                            $global_check = 0;
                        }
                        continue;
                    }
                }
            }

            $rules_check = 0;
            foreach ($type_rules as $rule) {
                if (self::checkRule($rule, $object_type, $secteur, $val, $extra_data)) {
                    if ($rule->isUserAllowed()) {
                        $rules_check = 1;
                        break;
                    }
                }
            }

            if ($rules_check) {
                if (BimpObject::objectLoaded($demande)) {
                    // Acceptation directe de la demande :
                    $demande_errors = $demande->setAccepted();

                    if (count($demande_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($demande_errors, 'Echec de l\'acceptation de la demande pour la validation de type "' . $demande->displayDataDefault('type') . '"');
                        $global_check = 0;
                    }
                }
            } else {
                $global_check = 0;

                // Création d'une demande de validation : 
                $demande_errors = array();

                $infos = '';
                $users = self::getValidationUsers($type_rules, $object_type, $secteur, $val, $extra_data, $infos);

                if (!empty($users)) {
                    $demande = BimpObject::createBimpObject('bimpvalidation', 'BV_Demande', array(
                                'type_validation'  => $type,
                                'type_object'      => $object_type,
                                'id_object'        => $object->id,
                                'id_user_demande'  => $user->id,
                                'validation_users' => $users
                                    ), true, $demande_errors);
                    if ($infos) {
                        $user_affected = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $users[0]);
                        $msg = 'Demande affectée à ' . $user_affected->getName() . ' pour le(s) motif(s) suivant(s): <br/>' . $infos;
                        $demande->addObjectLog($msg);
                    }
                } else {
                    $demande_errors[] = 'Aucun utilisateur disponible';
                }

                if (count($demande_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($demande_errors, 'Echec de la création d\'une demande de validation de type "' . $demande->displayDataDefault('type') . '"');
                }
            }
        }

        return $global_check;
    }

    public static function checkRule($rule, $object_type, $secteur, $val, $extra_data)
    {
        if (!in_array($object_type, $rule->getData('objects'))) {
            return 0;
        }

        if (!in_array($secteur, $rule->getData('secteurs'))) {
            return 0;
        }

        if (in_array($rule->getData('type'), array('comm', 'fin'))) {
            $val_min = (float) $rule->getData('val_min');
            $val_max = (float) $rule->getData('val_max');

            if ($val < $val_min || $val > $val_max) {
                return 0;
            }
        }

        return 1;
    }

    // Getters: 

    public static function getValidationsRules($object_type, $val = 0, $secteur = '')
    {
        $order_by = 'id';
        $order_way = 'desc';

        $filters = array(
            'active'  => 1,
            'objects' => array(
                'part_type' => 'middle',
                'part'      => '[' . $object_type . ']'
            )
        );

        if ($secteur) {
            $filters['secteurs'] = array(
                'part_type' => 'middle',
                'part'      => '[' . $secteur . ']'
            );
        }

        if ($val) {
            $filters['val_min'] = array(
                'operator' => '<=',
                'value'    => $val
            );

            $filters['val_max'] = array(
                'operator' => '>=',
                'value'    => $val
            );

            if ($val >= 0) {
                $order_by = 'val_max';
                $order_way = 'asc';
            } else {
                $order_by = 'val_min';
                $order_way = 'desc';
            }
        }

        return BimpCache::getBimpObjectObjects('bimpvalidation', 'BV_Rule', $filters, $order_by, $order_way);
    }

    public static function getValidationsRulesByTypes($object_type, $val = 0, $secteur = '')
    {
        $return = array();
        $rules = self::getValidationsRules($object_type, $val, $secteur);

        foreach ($rules as $rule) {
            if (!isset($return[$rule->getData('type')])) {
                $return[$rule->getData('type')] = array();
            }

            $return[$rule->getData('type')][] = $rule;
        }

        return $return;
    }

    public static function getValidationTypeClassName($type, &$errors = array())
    {
        $className = 'BimpValidation' . ucfirst($type);

        if (!class_exists($className)) {
            if (file_exists(DOL_DOCUMENT_ROOT . '/bimpvalidation/classes/' . $className . 'php')) {
                require_once DOL_DOCUMENT_ROOT . '/bimpvalidation/classes/' . $className . '.php';

                if (!class_exists($className)) {
                    $errors[] = 'Classe invalide : "' . $className . '"';
                    return '';
                }
            } else {
                $errors[] = 'Classe absente pour le type de validation "' . $type . '"';
                return '';
            }
        }

        return $className;
    }

    public static function getValidationUsers($rules, $object_type, $secteur, $val, $extra_data, $infos)
    {
        BimpObject::loadClass('bimpcore', 'Bimp_User');
        $users = array();

        foreach ($rules as $rule) {
            if (self::checkRule($rule, $object_type, $secteur, $val, $extra_data)) {
                $users_allowed = $rule->getData('users');

                foreach ($users_allowed as $id_user) {
                    $unavailable_reason = '';
                    $errors = array();
                    if (Bimp_User::isUserAvailable($id_user, null, $errors, $unavailable_reason)) {
                        $users[] = $id_user;
                    } elseif (empty($users)) {
                        $bimp_user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_user);
                        $infos .= ($infos ? '<br/>' : '') . $bimp_user->getName() . ' est ' . $unavailable_reason;
                    }
                }
            }
        }

        return $users;
    }
}
