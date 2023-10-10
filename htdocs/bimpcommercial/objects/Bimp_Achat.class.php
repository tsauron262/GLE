<?php

class Bimp_Achat extends BimpObject
{

    public static $facture_fields = array('date' => 'datef', 'id_fourn' => 'fk_soc');
    public static $facture_extrafields = array('id_entrepot' => 'entrepot', 'secteur' => 'ef_type');
    public static $product_extrafields = array('categorie' => 'categorie', 'collection' => 'collection', 'nature' => 'nature', 'famille' => 'famille', 'gamme' => 'gamme');

    // Getters booléens:

    public function isCreatable($force_create = false, &$errors = array())
    {
        return 0;
    }

    public function isEditable($force_edit = false, &$errors = array())
    {
        return 0;
    }

    public function isDeletable($force_delete = false, &$errors = array())
    {
        return 0;
    }

    // Getters: 

    public function getName($withGeneric = true)
    {
        if ($this->isLoaded()) {
            $fac = $this->getChildObject('facture_fourn');

            if (BimpObject::objectLoaded($fac)) {
                return $fac->getRef() . ' - Ligne n°' . $this->getData('rang');
            }

            $html = 'Facture #' . (int) $this->getData('fk_facture_fourn') . ' - Ligne n°' . $this->getData('rang');

            return $html;
        }

        return BimpRender::renderAlerts('(789) ID ' . $this->getLabel('of_the') . ' absent');
    }

    public function getLink($params = array(), $forced_context = '')
    {
        if ($this->isLoaded()) {
            $fac = $this->getChildObject('facture_fourn');

            if (BimpObject::objectLoaded($fac)) {
                $params['label_extra'] = 'Ligne n°' . $this->getData('rang');
                return $fac->getLink($params);
            }

            $html = 'Ligne de facture #' . $this->id;

            $html .= '<span class="danger">';
            if ((int) $this->getData('fk_facture')) {
                $html .= ' (La facture fournisseur #' . $this->getData('fk_facture') . ' n\'existe plus)';
            } else {
                $html .= ' (ID de la facture fournisseur absent)';
            }
            $html .= '</span>';

            return $html;
        }

        return BimpRender::renderAlerts('(790) ID ' . $this->getLabel('of_the') . ' absent');
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
            'date'        => '',
            'id_fourn'    => 0,
            'id_entrepot' => 0,
            'secteur'     => '',
            'categorie'   => 0,
            'collection'  => 0,
            'nature'      => 0,
            'famille'     => 0,
            'gamme'       => 0,
            'ref_fourn'   => ''
        );

        if ($this->isLoaded()) {
            $facture = $this->getParentInstance();
            if (BimpObject::objectLoaded($facture)) {
                foreach (self::$facture_fields as $field_name => $fac_field) {
                    $fields[$field_name] = $facture->getData($fac_field);
                }
                foreach (self::$facture_extrafields as $field_name => $fac_field) {
                    $fields[$field_name] = $facture->getData($fac_field);
                }
            }

            $id_prod = (int) $this->getData('fk_product');
            if ($id_prod) {
                if ((int) $fields['id_fourn']) {
                    $where = 'fk_product = ' . $id_prod . ' AND fk_soc = ' . (int) $fields['id_fourn'];
                    $fields['ref_fourn'] = (string) $this->db->getValue('product_fournisseur_price', 'ref_fourn', $where, 'rowid');
                }

                $product = $this->getChildObject('product');
                if (BimpObject::objectLoaded($product)) {
                    foreach (self::$product_extrafields as $field_name => $prod_field) {
                        $fields[$field_name] = $product->getData($prod_field);
                    }
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
                return $this->db->getValue('facture_fourn', self::$facture_fields[$field], '`rowid` = ' . (int) $instance->getData('fk_facture_fourn'));
            }

            if (array_key_exists($field, self::$facture_extrafields)) {
                $fac_field = self::$facture_extrafields[$field];
                if (preg_match('/^ef_(.+)$/', $fac_field, $matches)) {
                    $fac_field = $matches[1];
                }
                return $this->db->getValue('facture_fourn_extrafields', self::$facture_extrafields[$field], '`fk_object` = ' . (int) $instance->getData('fk_facture_fourn'));
            }

            if (array_key_exists($field, self::$product_extrafields)) {
                return $this->db->getValue('product_extrafields', self::$product_extrafields[$field], '`fk_object` = ' . (int) $instance->getData('fk_product'));
            }

            switch ($field) {
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

        if (array_key_exists($field, self::$facture_extrafields)) {
            $join_alias = ($main_alias ? $main_alias . '_' : '') . 'facture_fourn_ef';
            $joins[$join_alias] = array(
                'table' => 'facture_fourn_extrafields',
                'alias' => $join_alias,
                'on'    => $join_alias . '.fk_object = ' . ($main_alias ? $main_alias : 'a') . '.fk_facture_fourn'
            );

            $fac_field = self::$facture_extrafields[$field];
            if (preg_match('/^ef_(.+)$/', $fac_field, $matches)) {
                $fac_field = $matches[1];
            }

            return $join_alias . '.' . $fac_field;
        }

        if (array_key_exists($field, self::$product_extrafields)) {
            $join_alias = ($main_alias ? $main_alias . '_' : '') . 'product_ef';
            $joins[$join_alias] = array(
                'table' => 'product_extrafields',
                'alias' => $join_alias,
                'on'    => $join_alias . '.fk_object = ' . ($main_alias ? $main_alias : 'a') . '.fk_product'
            );

            return $join_alias . '.' . self::$product_extrafields[$field];
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
