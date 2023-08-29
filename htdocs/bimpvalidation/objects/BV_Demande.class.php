<?php

class BV_Demande extends BimpObject
{

    public static $status_list = array(
        -2 => array('label' => 'Abandonnée', 'icon' => 'fas_times-circle', 'classes' => array('danger')),
        -1 => array('label' => 'Refusée', 'icon' => 'fas_times', 'classes' => array('danger')),
        0  => array('label' => 'En attente', 'icon' => 'fas_hourglass-start', 'classes' => array('warning')),
        1  => array('label' => 'acceptée', 'icon' => 'fas_check', 'classes' => array('success')),
        2  => array('label' => 'acceptée (auto)', 'icon' => 'fas_check', 'classes' => array('success')),
    );

    // Droits users: 

    public function canProcess()
    {
        global $user;
        if ($user->admin) {
            return 1;
        }

        $users = $this->getData('validation_users');

        if (!empty($users)) {
            if (in_array($user->id, $users)) {
                return 1;
            }

            // Pour chaque user on vérifie que l'utilisateur n'est pas un supérieur (quelque soit le niveau hiérarchique) 
            foreach ($users as $id_user) {
                $cur_user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_user);
                if ($cur_user->isUserSuperior($user->id, 100)) {
                    return 1;
                }
            }
        }

        return 0;
    }

    // Getters booléens: 

    public function isAccepted()
    {
        if ((int) $this->getData('status') > 0) {
            return 1;
        }

        return 0;
    }

    // Getters array: 

    public function getTypesObjectsArray()
    {
        BimpObject::loadClass('bimpvalidation', 'BV_Rule');
        return BV_Rule::$objects_list;
    }

    public function getTypesValidationsArray()
    {
        BimpObject::loadClass('bimpvalidation', 'BV_Rule');
        return BV_Rule::$types;
    }

    // Getters données: 

    public function getObjInstance()
    {
        BimpObject::loadClass('bimpvalidation', 'BV_Rule');
        $type = $this->getData('object_type');
        $id_object = (int) $this->getData('id_object');

        foreach (BV_Rule::$objects_list as $obj_type => $obj_def) {
            if ($type == $obj_type) {
                return BimpCache::getBimpObjectInstance($obj_def['module'], $obj_def['object_name'], $id_object);
            }
        }

        return null;
    }

    // Affichages: 

    public function displayObj()
    {
        $obj = $this->getObjInstance();
        if (BimpObject::objectLoaded($obj)) {
            return $obj->getLink();
        }

        return '';
    }

    // Traitements:

    public function onAccept()
    {
        $errors = array();

        // todo BV

        return $errors;
    }

    public function setAccepted()
    {
        $errors = array();

        // todo BV

        return $errors;
    }

    public function setRefused()
    {
        $errors = array();

        // todo BV

        return $errors;
    }

    public function autoAccept($reason = '')
    {
        $errors = array();

        if ((int) $this->getData('status') == 0) {
            $this->updateField('status', 2);
            $errors = $this->onAccept();

            if (!count($errors)) {
                $this->addObjectLog('Demande acceptée automatiquement' . ($reason ? '<br/><b>Motif: </b>' . $reason : ''), 'AUTO_ACCEPT');
            }
        } else {
            $errors[] = 'Cette demande de validation n\'est pas en attente d\'acceptation';
        }

        return $errors;
    }

    public function checkAffectedUser($notify_if_change = true, &$infos = '')
    {
        $errors = array();

        $id_cur_affected_user = (int) $this->getData('id_user_affected');
        $users = $this->getData('validation_users');

        if (empty($users)) {
            $errors[] = 'Aucun utilisateur ne peut traiter cette demande de validation';
        } else {
            require_once DOL_DOCUMENT_ROOT . '/bimpvalidation/BV_Lib.php';

            $id_new_user_affected = (int) self::getFirstAvailableUser($users, $infos);

            if (!$id_new_user_affected) {
                $errors[] = 'Aucun utilisateur disponible pour effectuer cette validation';
            } elseif ($id_new_user_affected !== $id_cur_affected_user) {
                $this->updateField('id_user_affected', $id_new_user_affected);

                if ($notify_if_change) {
                    $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_new_user_affected);

                    if (!BimpObject::objectLoaded($user)) {
                        $errors[] = 'L\'utilisateur #' . $id_new_user_affected . ' n\'existe plus';
                        return $errors;
                    }

                    $type = $this->getData('type_validation');
                    $types = $this->getTypesValidationsArray();
                    $obj = $this->getObjInstance();

                    $subject = 'Demande de validation ' . $types[$type]['label2'];
                    $msg = 'Bonjour, <br/><br/>';
                    $msg .= 'La validation ' . $types[$type]['label2'] . ' ' . $obj->getLabel('of_the') . ' ' . $obj->getLink();
                    $msg .= ' est en attente.';
                    $email = BimpTools::cleanEmailsStr($user->getData('email'));

                    if (!$email) {
                        $errors[] = 'Adresse e-mail absente pour l\'utilisateur ' . $user->getLink();
                    } else {
                        if ($infos) {
                            $msg .= '<br/><br/>Cette demande de validation vous a été attribuée pour les motifs suivants : <br/><br/>';
                            $msg .= $infos;
                        }

                        $this->addObjectLog('Demande attribuée à {{Utilisateur:' . $id_new_user_affected . '}}' . ($infos ? '<br/><b>Motif : </b>' . $infos : ''));
                    }

                    if (!mailSyn2($subject, $email, '', $msg)) {
                        $errors[] = 'Echec de l\'envoi de l\'e-mail de notification à ' . $user->getName();
                        $this->addObjectLog('Echec de l\'envoi de la notification à ' . $user->getName());
                    }
                }
            }
        }

        return $errors;
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

    // Overrides: 

    public function validate()
    {
        $errors = parent::validate();

        if (!count($errors)) {
            if ((float) $this->getData('val_min') > (float) $this->getData('val_max')) {
                $errors[] = 'La valeur minimale ne peut pas être supérieure à la valeur maximale';
            }
        }

        return $errors;
    }
}
