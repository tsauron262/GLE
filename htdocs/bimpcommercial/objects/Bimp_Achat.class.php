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

    // Affichages: 

    public function displayRefConst()
    {
        $prod = $this->getChildObject('product');

        if (BimpObject::objectLoaded($prod)) {
            $ref = $prod->getRef();

            if (preg_match('/^[A-Z]{1,3}\-(.+)$/', $ref, $matches)) {
                $ref = $matches[1];
            }

            return $ref;
        }

        return '';
    }

    // Overrides:

    public function fetchExtraFields()
    {
        $fields = array(
            'date'      => '',
            'id_fourn'  => 0,
            'ref_fourn' => ''
        );

        if ($this->isLoaded()) {
            $facture = $this->getParentInstance();
            if (BimpObject::objectLoaded($facture)) {
                $fields['date'] = $facture->getData('datef');
                $fields['id_fourn'] = $facture->getData('fk_soc');
            }

            $id_prod = (int) $this->getData('fk_product');
            if ($id_prod && (int) $fields['id_fourn']) {
                $where = 'fk_product = ' . $id_prod . ' AND fk_soc = ' . (int) $fields['id_fourn'];
                $fields['ref_fourn'] = (string) $this->db->getValue('product_fournisseur_price', 'ref_fourn', $where, 'rowid');
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

                case 'ref_fourn':
                    $id_prod = (int) $this->getData('fk_product');
                    if ($id_prod) {
                        $id_fourn = (int) $this->getData('id_fourn');
                        if (!$id_fourn) {
                            $id_fourn = (int) $this->getExtraFieldSavedValue('id_fourn', $id_object);
                        }

                        if ($id_fourn) {
                            $where = 'fk_product = ' . $id_prod . ' AND fk_soc = ' . $id_fourn;
                            return $this->db->getValue('product_fournisseur_price', 'ref_fourn', $where, 'rowid');
                        }
                    }
            }
        }

        return null;
    }

    public function getExtraFieldFilterKey($field, &$joins, $main_alias = '', &$filters = array())
    {
        if (array_key_exists($field, self::$facture_fields)) {
            $join_alias = ($main_alias ? $main_alias . '_' : '') . 'facture_fourn';
            $joins[$join_alias] = array(
                'table' => 'facture_fourn',
                'alias' => $join_alias,
                'on'    => $join_alias . '.rowid = ' . ($main_alias ? $main_alias : 'a') . '.fk_facture_fourn'
            );

            return $join_alias . '.' . self::$facture_fields[$field];
        }

        switch ($field) {
            case 'ref_fourn':
                $fac_join_alias = ($main_alias ? $main_alias . '_' : '') . 'facture_fourn';
                if (!isset($joins[$fac_join_alias])) {
                    $joins[$fac_join_alias] = array(
                        'table' => 'facture_fourn',
                        'alias' => $fac_join_alias,
                        'on'    => $fac_join_alias . '.rowid = ' . ($main_alias ? $main_alias : 'a') . '.fk_facture_fourn'
                    );
                }

                $join_alias = ($main_alias ? $main_alias . '_' : '') . 'pfp';
                $joins[$join_alias] = array(
                    'table' => 'product_fournisseur_price',
                    'alias' => $join_alias,
                    'on'    => $join_alias . '.fk_product = ' . ($main_alias ? $main_alias : 'a') . '.fk_product'
                );
                $filters['custom_pfp'] = array(
                    'custom' => $join_alias . '.fk_soc = ' . $fac_join_alias . '.fk_soc'
                );

                return $join_alias . '.ref_fourn';
        }
    }

    public function updateExtraField($field_name, $value, $id_object)
    {
        return array();
    }
}
