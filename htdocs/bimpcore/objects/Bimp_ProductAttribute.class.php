<?php

class Bimp_ProductAttribute extends BimpObject
{

    public $no_dol_right_check = true;

    // Droits Users

    public function canView()
    {
        global $user;
        return !empty($user->rights->variants->read);
    }

    public function canCreate()
    {
        global $user;
        return !empty($user->rights->variants->write);
    }

    public function canEdit()
    {
        global $user;
        return !empty($user->rights->variants->write);
    }

    public function canDelete()
    {
        global $user;
        return !empty($user->rights->variants->delete);
    }

    // Getters params: 

    public function getListsExtraButtons()
    {
        $buttons = array();

        if ($this->isLoaded()) {
            $val = BimpObject::getInstance('bimpcore', 'Bimp_ProductAttributeValue');

            $buttons[] = array(
                'label'   => 'Liste des valeurs',
                'icon'    => 'fas_list-ol',
                'onclick' => $val->getJsLoadModalList('default', array(
                    'id_parent' => $this->id,
                    'title'     => 'Valeurs de l\\\'attribut "' . $this->getName() . '"'
                ))
            );
        }

        return $buttons;
    }

    // Affichages : 

    public function displayNbValues()
    {
        if ($this->isLoaded()) {
            return $this->dol_object->countChildValues();
        }

        return '';
    }

    public function displayAttrValues()
    {
        return '';
    }

    // Traitements : 

    public function addValue($value)
    {
        
    }

    public function removeValue($id_value)
    {
        
    }
}
