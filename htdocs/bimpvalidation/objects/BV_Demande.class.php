<?php

class BV_Demande extends BimpObject
{

    public static $status_list = array(
        -2 => array('label' => 'Abandonnée', 'icon' => 'fas_times-circle', 'classes' => array('danger')),
        -1 => array('label' => 'Refusée', 'icon' => 'fas_times', 'classes' => array('danger')),
        0  => array('label' => 'En attente', 'icon' => 'fas_hourglass-start', 'classes' => array('warning')),
        1  => array('label' => 'acceptée', 'icon' => 'fas_check', 'classes' => array('success'))
    );

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

    // Traitements: 
    public function checkUsers()
    {
        
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
