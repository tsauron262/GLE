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
                $fields['secteur'] = $facture->getData('ef_type');
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

    public function updateExtraField($field_name, $value, $id_object)
    {
        return array();
    }

    // Rendus HTML: 

    public function getListHeaderButtons()
    {
        $buttons = array();

        $dt = new DateTime();
        $dow = (int) $dt->format('w');
        if ($dow > 0) {
            $dt->sub(new DateInterval('P' . $dow . 'D')); // Premier dimanche précédent. 
        }
        $date_to = $dt->format('Y-m-d');

        $dt->sub(new DateInterval('P7D'));
        $date_from = $dt->format('Y-m-d');

        $buttons[] = array(
            'classes'     => array('btn', 'btn-default'),
            'label'       => 'Générer rapport Apple',
            'icon_before' => 'fas_file-excel',
            'attr'        => array(
                'type'    => 'button',
                'onclick' => $this->getJsActionOnclick('generateAppleCSV', array(
                    'date_from' => $date_from,
                    'date_to'   => $date_to
                        ), array(
                    'form_name' => 'generate_apple_cvs'
                ))
            )
        );

        return $buttons;
    }

    // Traitements : 

    public function generateAppleCSV($dateFrom, $dateTo, &$errors = array())
    {
        set_time_limit(3600);

        BimpTools::$currencies;
        $products_list = BimpCache::getBimpObjectList('bimpcore', 'Bimp_Product', array(
                    'ref' => array(
                        'part_type' => 'beginning',
                        'part'      => 'APP-'
                    )
        ));

        $product = BimpObject::getInstance('bimpcore', 'Bimp_Product');

        $file_str = '';

        $entrepots = BimpCache::getEntrepotsArray();
        $entrepots = array(
            66 => 'test'
        );
        foreach ($products_list as $id_product) {
            $entrepots_data = $product->getAppleCsvData($dateFrom, $dateTo, $entrepots, $id_product);

            foreach ($entrepots_data as $id_entrepot => $data) {
                if ((int) $data['ventes']['qty'] || (int) $data['stock'] || (int) $data['stock_showroom']) {
                    $file_str .= implode(';', array(
                                $id_entrepot, // A remplacer par ship_to
                                preg_replace('/^APP\-(.*)$/', '$1', $product->getRef()),
                                $data['ventes']['qty'],
                                0,
                                $data['stock'],
                                $data['stock_showroom'],
                                0,
                                0,
                                0,
                                0,
                                0
                            )) . "\n";
                }
            }
        }

        $dir = DOL_DATA_ROOT . '/bimpcore/apple_csv/' . date('Y');
        $fileName = $dateFrom . '_' . $dateTo . '.csv';

        if (!file_exists(DOL_DATA_ROOT . '/bimpcore/apple_csv')) {
            mkdir(DOL_DATA_ROOT . '/bimpcore/apple_csv');
        }

        if (!file_exists($dir)) {
            mkdir($dir);
        }

        if (!file_put_contents($dir . '/' . $fileName, $file_str)) {
            $errors[] = 'Echec de la création du fichier CSV';
            return '';
        }

        return $fileName;
    }

    // Actions : 

    public function actionGenerateAppleCSV($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $success_callback = '';

        $date_from = isset($data['date_from']) ? $data['date_from'] : date('Y-m-d');
        $date_to = isset($data['date_to']) ? $data['date_to'] : '';

        if (!$date_to) {
            $dt = new DateTime($date_from);
            $dt->sub(new DateInterval('P7D'));
            $date_to = $dt->format('Y-m-d');
        }

        $file_name = $this->generateAppleCSV($date_from, $date_to, $errors);

        if ($file_name && file_exists(DOL_DATA_ROOT . '/bimpcore/apple_csv/' . date('Y') . '/' . $file_name)) {
            $url = DOL_URL_ROOT . '/document.php?modulepart=bimpcore&file=' . htmlentities('apple_csv/' . date('Y') . '/' . $file_name);

            $success_callback = 'window.open(\'' . $url . '\')';
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }
}
