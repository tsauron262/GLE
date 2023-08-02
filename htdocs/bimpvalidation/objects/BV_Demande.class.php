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

        return $errors;
    }

    public function setAccepted()
    {
        $errors = array();

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

    public function notifyAffectedUser()
    {
        // todo BV
        $users = $this->getData('validation_users');

        if (isset($users[0]) && (int) $users[0]) {
            $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $users[0]);
            $email = '';
        }
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
