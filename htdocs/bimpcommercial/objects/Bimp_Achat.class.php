<?php

class Bimp_Achat extends BimpObject
{

    public static $facture_fields = array('date' => 'datef', 'id_fourn' => 'fk_soc');
    
    // Getters booléens:

    public function isCreatable($force_create = false)
    {
        return 0;
    }

    public function isEditable($force_edit = false)
    {
        return 0;
    }

    public function isDeletable($force_delete = false)
    {
        return 0;
    }

    // Overrides:

    public function fetchExtraFields()
    {
        $fields = array(
            'date'     => '',
            'id_fourn' => 0
        );

        if ($this->isLoaded()) {
            $facture = $this->getParentInstance();
            if (BimpObject::objectLoaded($facture)) {
                $fields['date'] = $facture->getData('datef');
                $fields['id_fourn'] = $facture->getData('fk_soc');
            }
        }

        return $fields;
    }

    public function getExtraFieldSavedValue($field, $id_object)
    {
        $instance = self::getBimpObjectInstance($this->module, $this->object_name, (int) $id_object);

        if (BimpObject::objectLoaded($instance)) {
            switch ($field) {
                case 'date':
                    return $this->db->getValue('facture_fourn', 'datef', '`rowid` = ' . (int) $instance->getData('fk_facture_fourn'));

                case 'id_fourn':
                    return $this->db->getValue('facture_fourn', 'fk_soc', '`rowid` = ' . (int) $instance->getData('fk_facture_fourn'));
            }
        }

        return null;
    }

    public function getExtraFieldFilterKey($field, &$joins, $main_alias = '')
    {
        $join_alias = ($main_alias ? $main_alias . '_' : '') . 'facture_fourn';
        $joins[$join_alias] = array(
            'table' => 'facture_fourn',
            'alias' => $join_alias,
            'on'    => $join_alias . '.rowid = ' . ($main_alias ? $main_alias : 'a') . '.fk_facture_fourn'
        );

        return $join_alias . '.' . self::$facture_fields[$field];
    }

    public function updateExtraField($field_name, $value, $id_object)
    {
        return array();
    }
}
