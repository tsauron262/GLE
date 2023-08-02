<?php

class BimpValidation
{

    // Traitements: 

    public static function tryToValidate($object, &$errors = array(), &$infos = array(), &$successes = array())
    {
        if (defined('DISABLE_BIMP_VALIDATIONS') && DISABLE_BIMP_VALIDATIONS) {
            return 1;
        }

        $object_params = self::getObjectParams($object, $errors);

        if (is_null($object_params)) {
            return 0;
        }

        $secteur = $object_params['secteur'];
        $object_type = $object_params['type'];

        $rules = self::getValidationsRulesByTypes($object_type, $secteur);

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
                        } else {
                            $successes[] = 'Validation de type "' . BV_Rule::$types[$type] . '" effectuée';
                        }
                        continue;
                    }
                }
            }

            $rule_errors = array();

            // Récupération des données de l'objet pour ce type de validation: 
            $object_data = self::getObjectData($type, $object, $rule_errors);

            if (count($rule_errors)) {
                $errors[] = BimpTools::getMsgFromArray($rule_errors, 'Validation de type "' . BV_Rule::$types[$type] . '"');
                $global_check = 0;
                continue;
            }

            $val = $object_data['val'];
            $extra_data = $object_data['extra_data'];

            $rules_check = 0;
            $valid_rules = array();
            foreach ($type_rules as $rule) {
                if (self::checkRule($rule, $object_type, $secteur, $val, $extra_data)) {
                    $valid_rules[] = $rule;
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
                    } else {
                        $successes[] = 'Validation de type "' . BV_Rule::$types[$type] . '" effectuée';
                    }
                }
            } else {
                $global_check = 0;

                // Création d'une demande de validation : 
                $demande_errors = array();

                $log = '';
                $users = array();

                foreach ($valid_rules as $rule) {
                    $users = $rule->getValidationUsers();

                    if (!empty($users)) {
                        break;
                    }
                }

                if (!empty($users)) {
                    $demande = BimpObject::createBimpObject('bimpvalidation', 'BV_Demande', array(
                                'type_validation'  => $type,
                                'type_object'      => $object_type,
                                'id_object'        => $object->id,
                                'id_user_demande'  => $user->id,
                                'validation_users' => $users,
                                'id_user_affected' => self::getFirstAvailableUser($users, $infos, $log)
                                    ), true, $demande_errors);

                    if (!count($demande_errors)) {
                        $user_affected = self::getFirstAvailableUser($users, $infos, $log);
                        $infos[$type] = 'Demande de validation de type "' . BV_Rule::$types[$type] . '" effectuée auprès de ' . $user_affected->getName();
                        if ($log) {
                            $msg = 'Demande affectée à ' . $user_affected->getName() . ' pour le(s) motif(s) suivant(s): <br/>' . $log;
                            $demande->addObjectLog($msg);
                        }
                    }
                } else {
                    $demande_errors[] = 'Aucun utilisateur disponible';
                }

                if (count($demande_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($demande_errors, 'Echec de la création d\'une demande de validation de type "' . $demande->displayDataDefault('type') . '"');
                }
            }
        }

        if (!$global_check && !count($errors)) {
            // Check des demandes: 
            self::checkDemandesAutoAccept($object, $object_type, $errors, $infos, $successes);

            if (!count($errors)) {
                $where = 'type_object = \'' . $object_type . '\' AND id_object = ' . $object->id;
                $where .= 'status <= 0';
                if (!(int) BimpCache::getBdb()->getCount('bv_demande', $where)) {
                    $global_check = 1;
                } else {
                    self::notifyAffectedUsers($object, $object_type);
                }
            }
        }

        return $global_check;
    }

    public static function sortValidationTypeRules($rules, $validation_type, $val, $extra_data)
    {
        // Trie des règles de validation par ordre de priorité: 

        if (in_array($validation_type, array('comm', 'fin', 'rtp'))) {
            if ($val >= 0) {

                // On trie selon les val_max croissantes
                function compareValidationRules($a, $b)
                {
                    if (is_a($a, 'BV_Rule') && is_a($b, 'BV_Rule')) {
                        $a_val = (float) $a->getData('val_max');
                        $b_val = (float) $b->getData('val_max');
                        return ($a_val > $b_val ? 1 : ($a_val < $b_val ? -1 : 0));
                    }

                    return 0;
                }
            } else {

                // On trie selon les val_min décroissantes
                function compareValidationRules($a, $b)
                {
                    if (is_a($a, 'BV_Rule') && is_a($b, 'BV_Rule')) {
                        $a_val = (float) $a->getData('val_min');
                        $b_val = (float) $b->getData('val_min');
                        return ($a_val > $b_val ? -1 : ($a_val < $b_val ? 1 : 0));
                    }

                    return 0;
                }
            }

            usort($rules, 'compareValidationRules');
        }

        return $rules;
    }

    public static function checkRule($rule, $object_type, $secteur, $val, $extra_data)
    {
        if (!in_array($object_type, $rule->getData('objects'))) {
            return 0;
        }

        if (!in_array($secteur, $rule->getData('secteurs'))) {
            return 0;
        }

        // Vérif Val
        $type = $rule->getData('type');
        if (in_array($type, array('comm', 'fin'))) {
            $val_min = (float) $rule->getData('val_min');
            $val_max = (float) $rule->getData('val_max');

            if ($val < $val_min || $val > $val_max) {
                return 0;
            }
        }

        return 1;
    }

    public static function checkDemandesAutoAccept($object, $object_type, &$errors = array(), &$infos = array(), &$successes = array())
    {
        $demandes = self::getObjectDemandes($object, 0);

        foreach ($demandes as $demande) {
            $type = $demande->getData('type_validation');
            switch ($type) {
                case 'fin':
                    if ($object->field_exists('paiement_comptant') && (int) $object->getData('paiement_comptant')) {
                        $accept_errors = $demande->autoAccept('Paiement comptant');

                        if (!count($accept_errors)) {
                            $email = BimpCore::getConf('notif_paiement_comptant_email', null, 'bimpvalidation');
                            if ($email) {
                                global $user;
                                $msg = "Bonjour,<br/><br/> " . ucfirst($object->getLabel('the')) . ' ' . $object->getLink();
                                $msg .= " a été validé" . $object->e() . " financièrement par paiement comptant ou mandat SEPA par ";
                                $msg .= ucfirst($user->firstname) . ' ' . strtoupper($user->lastname);
                                $msg .= "<br/>Merci de vérifier le paiement ultérieurement.";
                                mailSyn2('Validation par paiement comptant ou mandat SEPA - ' . $object->getLabel() . ' ' . $object->getLink(), $email, "", $msg);
                            }

                            if (isset($infos[$type])) {
                                unset($infos[$type]);
                            }

                            $successes[] = 'La demande de validation de type "' . BV_Rule::$types[$type] . '" a été acceptée automatiquement pour le motif : "Paiement comptant"';
                        } else {
                            $errors[] = BimpTools::getMsgFromArray($accept_errors, 'Echec accepation auto pour la validation de type ' . BV_Rule::$types[$type]);
                        }
                        continue;
                    } elseif (method_exists($object, 'getClientFacture')) {
                        $client_facture = $object->getClientFacture();

                        if (BimpObject::objectLoaded($client_facture)) {
                            if ($client_facture->field_exists('validation_financiere') && !(int) $client_facture->getData('validation_financiere')) {
                                $accept_errors = $demande->autoAccept('Validation financière non requise pour le client facturation ' . $object->getLabel('of_the'));
                                if (!count($accept_errors)) {
                                    if (isset($infos[$type])) {
                                        unset($infos[$type]);
                                    }

                                    $successes[] = 'La demande de validation de type "' . BV_Rule::$types[$type] . '" a été acceptée automatiquement pour le motif : "Validation financière non requise pour le client facturation ' . $object->getLabel('of_the') . '"';
                                }
                            }
                        }
                    }
                    break;
            }
        }
    }

    public static function notifyAffectedUsers($object)
    {
        $demandes = self::getObjectDemandes($object, 0);

        foreach ($demandes as $demande) {
            $demande->notifyAffectedUser();
        }
    }

    // Getters: 

    public static function getObjectParams($object, &$errors = array())
    {
        $obj_type = '';
        $secteur = '';

        BimpObject::loadClass('bimpvalidation', 'BV_Rule');

        foreach (BV_Rule::$objects_list as $type => $obj_def) {
            if (is_a($object, $obj_def['object_name'])) {
                $obj_type = $type;
                break;
            }
        }

        if (!$obj_type) {
            $errors[] = 'Objet invalide';
        }

        if (is_a($object, 'BimpComm') && $object->field_exists('ef_type')) {
            $secteur = $object->getData('ef_type');
        } elseif (is_a($object, 'BContract_contrat' && $object->field_exists('secteur'))) {
            $secteur = $object->getData('secteur');
        }

        return array(
            'type'    => $obj_type,
            'secteur' => $secteur
        );
    }

    public static function getObjectData($type_validation, $object, &$errors = array())
    {
        $val = 0;
        $extra_data = array();

        switch ($type_validation) {
            case 'comm':
                break;

            case 'fin':

                break;

            case 'rtp':
                break;
        }

        return array(
            'val'        => $val,
            'extra_data' => $extra_data
        );
    }

    public static function getValidationsRules($object_type, $secteur = '')
    {
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

        return BimpCache::getBimpObjectObjects('bimpvalidation', 'BV_Rule', $filters, 'id', 'desc');
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

    public static function getFirstAvailableUser($users, &$infos = '', $superiors_depth = 2)
    {
        $bdb = BimpCache::getBdb();
        BimpObject::loadClass('bimpcore', 'Bimp_User');

        for ($n = 0; $n <= $superiors_depth; $n++) {
            foreach ($users as $id_user) {
                if ($n > 0) {
                    // Recherche du supérieur de niveau $n
                    for ($i = 0; $i < $n; $i++) {
                        $id_user = (int) $bdb->getValue('user', 'fk_user', 'rowid = ' . $id_user);

                        if (!$id_user) {
                            break;
                        }
                    }

                    if (!$id_user) {
                        continue;
                    }
                }

                $unavailable_reason = '';
                $errors = array();
                if (Bimp_User::isUserAvailable($id_user, null, $errors, $unavailable_reason)) {
                    return $id_user;
                }

                $bimp_user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_user);
                $infos .= ($infos ? '<br/>' : '') . $bimp_user->getName() . ' est ' . $unavailable_reason;
            }
        }

        return 0;
    }

    public static function getObjectDemandes($object, $status = null, $type = null)
    {
        $obj_params = BimpValidation::getObjectParams($object);
        $obj_type = BimpTools::getArrayValueFromPath($obj_params, 'type', '');

        if ($obj_type) {
            $filters = array(
                'type_object' => $obj_type,
                'id_object'   => $object->id,
            );

            if (!is_null($status)) {
                $filters['status'] = $status;
            }

            if (!is_null($type)) {
                $filters['type'] = $type;
            }

            return BimpCache::getBimpObjectObjects('bimpvalidation', 'BV_Demande', $filters);
        }

        return array();
    }

    // Rendus HTML : 
    public static function renderObjectDemandesList($object)
    {
        if (!BimpObject::objectLoaded($object)) {
            return '';
        }

        $obj_params = BimpValidation::getObjectParams($object);
        $obj_type = BimpTools::getArrayValueFromPath($obj_params, 'type', '');

        if ($obj_type) {
            $demande = BimpObject::getInstance('bimpvalidation', 'BV_Demande');
            $title = 'Demandes de validation ' . $object->getLabel('of_the') . ' ' . $object->getRef();

            $list = new BC_ListTable($demande, 'obj', 1, null, $title);
            $list->addFieldFilterValue('type_object', $obj_type);
            $list->addFieldFilterValue('id_object', (int) $object->id);

            return $list->renderHtml();
        }

        return '';
    }
}
