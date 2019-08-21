<?php

class Bimp_User extends BimpObject
{

    public static $genders = array(
        ''      => '',
        'man'   => 'Homme',
        'woman' => 'Femme'
    );

    // Gestion des droits: 

    public function canView()
    {
        global $user;

        if ((int) $user->id === (int) $this->id) {
            return 1;
        }

        if ($user->admin || $user->rights->user->user->lire) {
            return 1;
        }

        return 0;
    }

    public function canCreate()
    {
        global $user;

        if ($user->admin || $user->rights->user->user->creer) {
            return 1;
        }

        return 0;
    }

    public function canEdit()
    {
        return $this->canCreate();
    }

    public function canDelete()
    {
        return $this->canCreate();
    }

    // Getters: 

    public function getName($withGeneric = true)
    {
        return $this->getInstanceName();
    }

    public function getInstanceName()
    {
        if ($this->isLoaded()) {
            return dolGetFirstLastname($this->getData('firstname'), $this->getData('lastname'));
        }

        return ' ';
    }

    public function getPageTitle()
    {
        return $this->getInstanceName();
    }

    // Getters params: 

    public function getEditFormName()
    {
        global $user;

        if ($user->admin || $user->rights->user->user->creer) {
            return 'default';
        }

        if ((int) $user->id === (int) $this->id) {
            return 'light';
        }

        return null;
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
