<?php

class Bimp_Vente extends BimpObject
{

    public static $facture_fields = array('date' => 'datef', 'id_client' => 'fk_soc', 'id_user' => 'fk_user_author');
    public static $facture_extrafields = array('id_entrepot' => 'entrepot', 'secteur' => 'type');

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

    // Overrides : 

    public function fetchExtraFields()
    {
        $fields = array(
            'date'               => '',
            'id_client'          => 0,
            'id_entrepot'        => 0,
            'id_user'            => 0,
            'secteur'            => '',
            'product_categories' => array()
        );

        if ($this->isLoaded()) {
            $facture = $this->getParentInstance();
            if (BimpObject::objectLoaded($facture)) {
                $fields['date'] = $facture->getData('datef');
                $fields['id_client'] = $facture->getData('fk_soc');
                $fields['id_entrepot'] = $facture->getData('entrepot');
                $fields['id_user'] = $facture->getData('fk_user_author');
            }

            if ((int) $this->getData('fk_product')) {
                $categories = BimpCache::getProductCategoriesArray((int) $this->getData('fk_product'));
                foreach ($categories as $id_category => $label) {
                    $fields['product_categories'][] = (int) $id_category;
                }
            }
        }

        return $fields;
    }

    public function getExtraFieldSavedValue($field, $id_object)
    {
        $instance = self::getBimpObjectInstance($this->module, $this->object_name, (int) $id_object);

        if (BimpObject::objectLoaded($instance)) {
            if (array_key_exists($field, self::$facture_fields)) {
                if ((int) $instance->getData('fk_facture')) {
                    return $this->db->getValue('facture', self::$facture_fields[$field], '`rowid` = ' . (int) $instance->getData('fk_facture'));
                }
            } elseif (array_key_exists($field, self::$facture_extrafields)) {
                if ((int) $instance->getData('fk_facture')) {
                    return $this->db->getValue('facture_extrafields', self::$facture_fields[$field], '`fk_object` = ' . (int) $instance->getData('fk_facture'));
                }
            } elseif ($field === 'categories') {
                $id_product = (int) $instance->getData('fk_product');
                if ($id_product) {
                    if (isset(self::$cache['product_' . $id_product . '_categories_array'])) {
                        unset(self::$cache['product_' . $id_product . '_categories_array']);
                    }

                    $categories = array();
                    foreach (self::getProductCategoriesArray($id_product) as $id_category => $label) {
                        $categories[] = (int) $id_category;
                    }

                    return $categories;
                }

                return array();
            }
        }

        return null;
    }

    public function getExtraFieldFilterKey($field, &$joins, $main_alias = '')
    {
        if (array_key_exists($field, self::$facture_fields)) {
            $join_alias = ($main_alias ? $main_alias . '_' : '') . 'facture';
            $joins[$join_alias] = array(
                'table' => 'facture',
                'alias' => $join_alias,
                'on'    => $join_alias . '.rowid = ' . ($main_alias ? $main_alias : 'a') . '.fk_facture'
            );

            return $join_alias . '.' . self::$facture_fields[$field];
        } elseif (array_key_exists($field, self::$facture_extrafields)) {
            $join_alias = ($main_alias ? $main_alias . '_' : '') . 'factureef';
            $joins[$join_alias] = array(
                'table' => 'facture_extrafields',
                'alias' => $join_alias,
                'on'    => $join_alias . '.fk_object = ' . ($main_alias ? $main_alias : 'a') . '.fk_facture'
            );

            return $join_alias . '.' . self::$facture_extrafields[$field];
        } elseif ($field === 'categories') {
            // todo...
        }

        return '';
    }
}
