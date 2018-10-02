<?php

class Bimp_User extends BimpObject
{

    public static $genders = array(
        ''      => '',
        'man'   => 'Homme',
        'woman' => 'Femme'
    );

    // Getters: 

    public function getInstanceName()
    {
        if ($this->isLoaded()) {
            return $this->getData('lastname') . ' ' . $this->getData('firstname');
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
}
