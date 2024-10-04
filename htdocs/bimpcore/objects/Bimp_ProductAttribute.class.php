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

    // Getters arrays: 

    public static function getAttributesArray($include_empty = true)
    {
        $cache_key = 'product_attributes_array';

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            $rows = self::getBdb()->getRows('product_attribute', '1', null, 'array', array('rowid', 'ref', 'label'), 'position', 'asc');

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    self::$cache[$cache_key][(int) $r['rowid']] = $r['ref'] . ' - ' . $r['label'];
                }
            }
        }

        return self::getCacheArray($cache_key, $include_empty);
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
