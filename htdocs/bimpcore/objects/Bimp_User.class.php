<?php

class Bimp_User extends BimpObject
{

    public static $genders = array(
        ''      => '',
        'man'   => 'Homme',
        'woman' => 'Femme'
    );
    
    public function getName($withGeneric = true) {
        return $this->getInstanceName();
    }

    // Getters: 

    public function getInstanceName()
    {
        if ($this->isLoaded()) {
            return  dolGetFirstLastname($this->getData('firstname'), $this->getData('lastname'));
        }

        return ' ';
    }

    // Affichage: 

    public function displayCountry()
    {
        $id = $this->getData('fk_country');
        if (!is_null($id) && $id) {
            return $this->db->getValue('c_country', 'label', '`rowid` = ' . (int) $id);
        }
        return '';
    }

    // Overrides

    public function update(&$warnings = array(), $force_update = false)
    {
        if ($this->isLoaded()) {
            $this->dol_object->oldcopy = clone $this->dol_object;
        }

        return parent::update($warnings, $force_update);
    }
}
