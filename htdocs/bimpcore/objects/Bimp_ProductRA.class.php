<?php

class Bimp_ProductRA extends BimpObject
{

    public static $types = array(
        'crt'       => 'CRT',
        'applecare' => 'AppleCare',
        'oth'       => 'Autre'
    );
    public static $auto_add_types = array('applecare');

    // Getters données: 

    public function getInputValue($field_name)
    {
        switch ($field_name) {
            case 'nom':
                $type = BimpTools::getPostFieldValue('type', '', 'aZ09');
                if ($type !== 'other' && !$this->isLoaded() || $type !== $this->getData('type')) {
                    return static::$types[$type];
                }
                return '';
        }

        return null;
    }

    // Overrides: 

    public function create(&$warnings = [], $force_create = false)
    {
        $errors = array();

        if ($this->getData('type') !== 'other' && (int) $this->getData('id_product')) {
            $where = 'id_product = ' . (int) $this->getData('id_product') . ' AND type = \'' . $this->getData('type') . '\'';
            $id = (int) $this->db->getValue($this->getTable(), 'id', $where);

            if ($id) {
                $errors[] = 'Une remise arrière de ce type (' . $this->displayData('type', 'default', 0) . ') a déjà été ajoutée pour ce produit';
            }
        }

        if (!count($errors)) {
            $errors = parent::create($warnings, $force_create);
        }

        return $errors;
    }
}
