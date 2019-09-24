<?php

class Bimp_Product_Entrepot extends BimpObject
{

    public static $product_instance = null;

    public function __construct($module, $object_name)
    {
        if (is_null(static::$product_instance)) {
            static::$product_instance = BimpObject::getInstance('bimpcore', 'Bimp_Product');
        }

        parent::__construct($module, $object_name);
    }

    // Getters: 

    function getValue1()
    {
        return 56;
    }

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, &$errors = array())
    {
        switch ($field_name) {
            case 'categ1':
            case 'categ2':
            case 'categ3':
                $alias = 'cat_prod' . $field_name;
                $joins[$alias] = array(
                    'alias' => $alias,
                    'table' => 'categorie_product',
                    'on'    => $alias . '.fk_product = a.fk_product'
                );
                $filters[$alias . '.fk_categorie'] = array(
                    'in' => $values
                );
                return;
        }

        parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $errors);
    }

    // Affichage: 

    public function displayProduct()
    {
        $product = $this->getChildObject('product');

        if (BimpObject::objectLoaded($product)) {
            $html = $product->dol_object->getNomUrl(1);
            $html .= BimpRender::renderObjectIcons($product, 1, 'default');
            $html .= '<br/>';
            $html .= $product->getData('label');
            return $html;
        }

        return (int) $this->getData('fk_product');
    }

    public function displayLastVenteDate()
    {
        if ($this->isLoaded()) {
            $sql = 'SELECT MAX(f.date_valid) as date FROM ' . MAIN_DB_PREFIX . 'facture f';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'facture_extrafields fef ON f.rowid = fef.fk_object';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'facturedet fl ON f.rowid = fl.fk_facture';
            $sql .= ' WHERE f.fk_statut > 0 AND fl.fk_product = ' . (int) $this->getData('fk_product');
            $sql .= ' AND fef.entrepot = ' . (int) $this->getData('fk_entrepot');

            $res = $this->db->executeS($sql, 'array');

            if (isset($res[0]['date'])) {
                $dt = new DateTime($res[0]['date']);
                return $dt->format('d / m / Y');
            }
        }

        return '';
    }

    public function displayLastAchatDate()
    {
        if ($this->isLoaded()) {
            $sql = 'SELECT MAX(f.datef) as date FROM ' . MAIN_DB_PREFIX . 'facture_fourn f ';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'facture_fourn_extrafields fef ON f.rowid = fef.fk_object';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'facture_fourn_det fl on f.rowid = fl.fk_facture_fourn';
            $sql .= ' WHERE f.fk_statut > 0 AND fl.fk_product = ' . (int) $this->getData('fk_product');
            $sql .= ' AND fef.entrepot = ' . (int) $this->getData('fk_entrepot');

            $res = $this->db->executeS($sql, 'array');

            if (isset($res[0]['date'])) {
                $dt = new DateTime($res[0]['date']);
                return $dt->format('d / m / Y');
            }
        }

        return '';
    }

    public function displayNbMonthVentes($nb_month, $data = 'total_ht')
    {
        if ($this->isLoaded() && (int) $nb_month) {
            $dt = new DateTime();
            $dt->sub(new DateInterval('P' . $nb_month . 'M'));
            $dateMin = $dt->format('Y-m-d') . ' 00:00:00';
            $dateMax = date('Y-m-d') . ' 23:59:59';
            $id_product = (int) $this->getData('fk_product');
            $id_entrepot = ((int) $this->getData('fk_entrepot') ? (int) $this->getData('fk_entrepot') : null);

            $ventes = static::$product_instance->getVentes($dateMin, $dateMax, $id_entrepot, $id_product);
            if (isset($ventes[$data])) {
                if (in_array($data, array('total_ht', 'total_ttc'))) {
                    return BimpTools::displayMoneyValue($ventes[$data]);
                }
                return $ventes[$data];
            }
        }

        if (in_array($data, array('total_ht', 'total_ttc'))) {
            return BimpTools::displayMoneyValue(0);
        }

        return 0;
    }

    // Actions:

    function actionPrintEtiquettes($data, &$success)
    {
        $prod = BimpObject::getInstance('bimpcore', 'Bimp_Product');

        $newIds = array();
        foreach ($data['id_objects'] as $id) {
            $tmp = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Product_Entrepot', $id);
            $newIds[] = $tmp->getData('fk_product');
        }
        $data['id_objects'] = $newIds;
        return $prod->actionPrintEtiquettes($data, $success);
    }

    // Overrides: 

    public function addConfigExtraParams()
    {
        $cols = array();

        foreach (array(1, 3, 6, 12) as $nb_month) {
            $cols['ventes_' . $nb_month . '_mois'] = array(
                'label' => 'Vente à ' . $nb_month . ' mois',
                'value' => array(
                    'callback' => array(
                        'method' => 'displayNbMonthVentes',
                        'params' => array(
                            $nb_month
                        )
                    )
                )
            );
        }

        $this->config->addParams('lists_cols', $cols);
    }

    public function fetchExtraFields()
    {
        $fields = array(
            'ventes_qty'    => 0,
            'ventes_ht'     => 0,
            'stockShowRoom' => 0,
            'cur_pa'        => 0
        );

        if ((int) $this->getData('fk_product')) {
            $tabVentes = static::$product_instance->getVentes(null, date('Y-m-d') . ' 23:59:59', (int) $this->getData('fk_entrepot'), (int) $this->getData('fk_product'));

            if ($tabVentes['qty'] > 0)
                $fields['ventes_qty'] = $tabVentes['qty'];

            if ($tabVentes['total_ht'] > 0)
                $fields['ventes_ht'] = $tabVentes['total_ht'];

            $stockShowRoom = static::$product_instance->getStockShoowRoom(date('Y-m-d H:i:s'), (int) $this->getData('fk_entrepot'), (int) $this->getData('fk_product'));

            if ($stockShowRoom > 0)
                $fields['stockShowRoom'] = $stockShowRoom;

            $prod = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $this->getData('fk_product'));
            if (BimpObject::objectLoaded($prod)) {
                $fields['cur_pa'] = $prod->getCurrentPaHt(null, true);

                $categories = BimpCache::getProductCategoriesArray((int) $this->getData('fk_product'));
                foreach ($categories as $id_category => $label) {
                    $fields['product_categories'][] = (int) $id_category;
                }

                $marques_categories = BimpCache::getMarquesList();
                foreach ($fields['product_categories'] as $id_category) {
                    if (in_array((int) $id_category, $marques_categories)) {
                        $fields['marque'] = $id_category;
                        break;
                    }
                }

                $gammes_materiel_categories = BimpCache::getGammesMaterielList();
                foreach ($fields['product_categories'] as $id_category) {
                    if (in_array((int) $id_category, $gammes_materiel_categories)) {
                        $fields['gamme'] = $id_category;
                        break;
                    }
                }
            }
        }

        return $fields;
    }
    
   public function displayLastBuyPrice()
    {
        $product = $this->getChildObject('product');
        
        $lignes = array();
        
        if (BimpObject::objectLoaded($product)) {
            $sql = 'SELECT unitprice, rowid';
            $sql .= ' FROM ' . MAIN_DB_PREFIX . 'product_fournisseur_price';
            $sql .= ' WHERE fk_product=' . $product->getData('id');

            $result = $this->db->db->query($sql);
            if ($result and mysqli_num_rows($result) > 0) {
                while ($obj = $this->db->db->fetch_object($result)) {
                    $lignes[$obj->rowid] = $obj->unitprice;
                }
            }
        }
        $good_price = 0;
        $good_id = 0;
        foreach($lignes as $rowid => $unitprice) {
            if($good_id < $rowid) {
                $good_id = $rowid;
                $good_price = $unitprice;
            }
        }

        if($good_price != 0)
            return $good_price;
        
        return "Aucun";
    }
    
    public function displayTypeMateriel()
    {
        if ($this->isLoaded()) {
            $cats = $this->getData('product_categories');
            if (!is_array($cats)) {
                $cats = array();
            }

            if (in_array((int) BimpCore::getConf('desktop_id_categorie'), $cats)) {
                return 'desktop';
            } elseif (in_array((int) BimpCore::getConf('notebook_id_categorie'), $cats)) {
                return 'notebook';
            }
        }

        return '';
    }
}
