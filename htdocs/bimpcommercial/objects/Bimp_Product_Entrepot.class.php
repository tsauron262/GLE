<?php

class Bimp_Product_Entrepot extends BimpObject
{

    public $dateBilan = null;
    public $exludeIdDifZero = array();
    public static $product_instance = null;
    public static $modeStockDate = false;
    public static $modeStockShowRoom = false;
    public static $modeVentes = false;

    public function __construct($module, $object_name)
    {
        ini_set('memory_limit','1024M');
//        $this->dateBilan = date('2019-10-01 00:00:01');
        $this->dateBilan = date('2021-02-01 00:00:01');
//        $this->dateBilan = date('2019-06-30 00:00:01');
        if (is_null(static::$product_instance)) {
            static::$product_instance = BimpObject::getInstance('bimpcore', 'Bimp_Product');
        }

        parent::__construct($module, $object_name);
    }
    
    public function getDateForStock(){
        if(isset($_REQUEST['extra_data']) && isset($_REQUEST['extra_data']['date_for_stock']))
            return date($_REQUEST['extra_data']['date_for_stock']);
        
    
        if(isset($_GET['date_for_stock']))
            return date($_GET['date_for_stock']);
        return $this->dateBilan;
    }
    

    public function beforeListFetchItems(BC_List $list)
    {
        if (array_key_exists('stockDate', $list->cols))
            static::$modeStockDate = true;
        if (array_key_exists('stockShowRoom', $list->cols))
            static::$modeStockShowRoom = true;
        if (array_key_exists('ventes_qty', $list->cols) || in_array('ventes_ht', $list->cols) || in_array('derPv', $list->cols))
            static::$modeVentes = true;

        $prod = BimpObject::getInstance("bimpcore", "Bimp_Product");
        if (static::$modeStockDate) {
            $prod::initStockDate($this->getDateForStock());
        }
        if (static::$modeStockShowRoom) {
            $prod::initStockShowRoom();
        }

        if (static::$modeStockDate || static::$modeStockShowRoom) {
            $data = $prod::insertStockDateNotZeroProductStock($this->getDateForStock());
            foreach ($data['stockDateZero'] as $tmp)
                $this->exludeIdDifZero[] = $tmp;
            $this->isInitSpecial = true;
        }
    }

    // Getters: 
    
    public function getRefFourn($idFourn = null){
        if($this->isLoaded()){
            $refFourn = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ProductFournisseurPrice');
            $filter = array('fk_product'=>$this->getData('fk_product'));
            if($idFourn)
                $filter['fk_soc'] = $idFourn;
            if($refFourn->find($filter)){
                return $refFourn->getData('ref_fourn');
            }
        }
        return '';
    }

    public function getStockByType($type) // $type : 'reel' / 'dispo' / 'virtuel'
    {
        $product = $this->getChildObject('product');
        $id_entrepot = (int) $this->getData('fk_entrepot');
        if (BimpObject::objectLoaded($product) && $id_entrepot) {
            $stocks = $product->getStocksForEntrepot($id_entrepot, $type);

            if (isset($stocks[$type])) {
                return $stocks[$type];
            }
        }

        return 0;
    }

    function getValue1()
    {
        return 56;
    }

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, &$errors = array(), $excluded = false)
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
                    ($excluded ? 'not_' : '') . 'in' => $values
                );
                return;

            case 'stockDateDifZero':
                if (count($this->exludeIdDifZero)) {
                    $filters['a.rowid'] = array(
                        'not_in' => implode(",", $this->exludeIdDifZero)
                    );
                }
                return;
                
            case 'date_for_stock ':
                die('oooooo');
                return;
        }

        parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $errors, $excluded);
    }

    public function getLastTruePaHt()
    {
        if ($this->isLoaded()) {
            $sql = 'SELECT a.pu_ht FROM ' . MAIN_DB_PREFIX . 'facture_fourn_det a';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'facture_fourn f ON f.rowid = a.fk_facture_fourn';
            $sql .= ' WHERE a.fk_product = ' . (int) $this->getData('fk_product') . ' AND f.fk_statut IN (1,2) ORDER BY f.date_valid DESC LIMIT 1';

            $rows = $this->db->executeS($sql, 'array');

            if (isset($rows[0]['pu_ht'])) {
                return (float) $rows[0]['pu_ht'];
            }

            return (float) $this->db->getValue('product', 'cur_pa_ht', 'rowid = ' . (int) $this->getData('fk_product'));
        }

        return 0;
    }

    // Affichage: 

    public function displayProduct()
    {
        $product = $this->getChildObject('product');

        if (BimpObject::objectLoaded($product)) {
            $html = $product->getLink();
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
//            $sql .= ' AND fef.entrepot = ' . (int) $this->getData('fk_entrepot');

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
//            $sql .= ' AND fef.entrepot = ' . (int) $this->getData('fk_entrepot');

            $res = $this->db->executeS($sql, 'array');

            if (isset($res[0]['date'])) {
                $dt = new DateTime($res[0]['date']);
                return $dt->format('d / m / Y');
            }
        }

        return '';
    }

    public function displayTitre()
    {
        $html = '';
        $html .= 'Produits/Entrepot';
        if ($this->getDateForStock())
            $html .= ' date de valeur  < ' . dol_print_date($this->db->db->jdate($this->getDateForStock())) . ' (Stock Date, Stock show room, Nb Ventes, Ventes a NB mois)';
        return $html;
    }

    public function displayNbMonthVentes($nb_month, $data = 'total_ht', $exlure_retour = false)
    {
        if ($this->isLoaded() && (int) $nb_month) {
            $dt = new DateTime($this->getDateForStock());
            $dt->sub(new DateInterval('P' . $nb_month . 'M'));
            $dateMin = $dt->format('Y-m-d') . ' 00:00:00';
            $id_product = (int) $this->getData('fk_product');
//            $id_entrepot = ((int) $this->getData('fk_entrepot') ? (int) $this->getData('fk_entrepot') : null);
            $id_entrepot = null; //avoir toute les ventes de tous les depot

            $ventes = static::$product_instance->getVentes($dateMin, $this->getDateForStock(), $id_entrepot, $id_product, array(), $exlure_retour);
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

    public function displayCur_pa_date()
    {
        $product = $this->getChildObject('product');
        return price($product->getCurrentPaHt(null, null, $this->getDateForStock()));
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
        foreach ($lignes as $rowid => $unitprice) {
            if ($good_id < $rowid) {
                $good_id = $rowid;
                $good_price = $unitprice;
            }
        }

        if ($good_price != 0)
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

    public function displayLastTruePaHt()
    {
        return BimpTools::displayMoneyValue($this->getLastTruePaHt());
    }

    // Rendus HTML: 

    public function renderProdList()
    {
        $html = '';

        global $user;

        if ($user->id != 1) {
            $html = BimpRender::renderAlerts('Debug en cours. Si besoin urgent me contacter: f.martinez@bimp.fr', 'warning');
        } else {
            return $this->renderList();
        }

        return $html;
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
                'label' => 'Vente à ' . $nb_month . ' mois (€)',
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

        foreach (array(1, 3, 6, 12) as $nb_month) {
            $cols['ventes_' . $nb_month . '_mois_qty'] = array(
                'label' => 'Vente à ' . $nb_month . ' mois (qté)',
                'value' => array(
                    'callback' => array(
                        'method' => 'displayNbMonthVentes',
                        'params' => array(
                            $nb_month,
                            'qty'
                        )
                    )
                )
            );
        }

        foreach (array(1, 3, 6, 12) as $nb_month) {
            $cols['ventes_' . $nb_month . '_mois_qty_with_none_retour'] = array(
                'label' => 'Vente à ' . $nb_month . ' mois (qté sans retour)',
                'value' => array(
                    'callback' => array(
                        'method' => 'displayNbMonthVentes',
                        'params' => array(
                            $nb_month,
                            'qty',
                            1
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
//            'ventes_qty'    => 0,
//            'ventes_ht'     => 0,
//            'stockShowRoom' => 0,
//            'cur_pa'        => 0
        );

        if ((int) $this->getData('fk_product')) {
            if (static::$modeVentes) {
                $tabVentes = static::$product_instance->getVentes(null, $this->getDateForStock(), (int) $this->getData('fk_entrepot'), (int) $this->getData('fk_product'));
                $derPv = static::$product_instance->getDerPv(null, $this->getDateForStock(), (int) $this->getData('fk_product'));
                $fields['derPv'] = $derPv;
                if ($tabVentes['qty'] > 0)
                    $fields['ventes_qty'] = $tabVentes['qty'];

                if ($tabVentes['total_ht'] > 0)
                    $fields['ventes_ht'] = $tabVentes['total_ht'];
            }

            if (static::$modeStockShowRoom) {
                $stockShowRoom = static::$product_instance->getStockShoowRoom($this->getDateForStock(), (int) $this->getData('fk_entrepot'), (int) $this->getData('fk_product'));
                if ($stockShowRoom > 0)
                    $fields['stockShowRoom'] = $stockShowRoom;
                else
                    $fields['stockShowRoom'] = 0;
            }


            if (static::$modeStockDate) {
                $stockDate = static::$product_instance->getStockDate($this->getDateForStock(), (int) $this->getData('fk_entrepot'), (int) $this->getData('fk_product'));
                $fields['stockDate'] = $stockDate;
                $fields['stockDepuisDate'] = $this->getData('reel') - $stockDate;
            }

            $prod = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $this->getData('fk_product'));
            if (BimpObject::objectLoaded($prod)) {
                $fields['cur_pa'] = $prod->getCurrentPaHt(null, true);

                $categories = BimpCache::getProductCategoriesArray((int) $this->getData('fk_product'));
                foreach ($categories as $id_category => $label) {
                    $fields['product_categories'][] = (int) $id_category;
                }

                $marques_categories = BimpCache::getMarquesList();
                if (isset($fields['product_categories']) && is_array($fields['product_categories'])) {
                    foreach ($fields['product_categories'] as $id_category) {
                        if (in_array((int) $id_category, $marques_categories)) {
                            $fields['marque'] = $id_category;
                            break;
                        }
                    }
                }

                $gammes_materiel_categories = BimpCache::getGammesMaterielList();

                if (isset($fields['product_categories']) && is_array($fields['product_categories'])) {
                    foreach ($fields['product_categories'] as $id_category) {
                        if (in_array((int) $id_category, $gammes_materiel_categories)) {
                            $fields['gamme'] = $id_category;
                            break;
                        }
                    }
                }
            }
        }

        return $fields;
    }

    public function displayStockDesire()
    {
        $stockAlert = $this->getStockAlert();
        if (isset($stockAlert[$this->getData("fk_product")]) && isset($stockAlert[$this->getData("fk_product")][$this->getData("fk_entrepot")]))
            return $stockAlert[$this->getData("fk_product")][$this->getData("fk_entrepot")]['desiredstock'];
        return 0;
    }

    public function displayStockAlert()
    {
        $stockAlert = $this->getStockAlert();
        if (isset($stockAlert[$this->getData("fk_product")]) && isset($stockAlert[$this->getData("fk_product")][$this->getData("fk_entrepot")]))
            return $stockAlert[$this->getData("fk_product")][$this->getData("fk_entrepot")]['seuil_stock_alerte'];
        return 0;
    }

    public function getStockAlert()
    {
        $clef = "stockAlertEntrepot";
        if (!isset(BimpCache::$cache[$clef])) {
            BimpCache::$cache[$clef] = array();
            $sql = $this->db->db->query("SELECT * FROM `" . MAIN_DB_PREFIX . "product_warehouse_properties`");
            while ($ln = $this->db->db->fetch_object($sql)) {
                BimpCache::$cache[$clef][$ln->fk_product][$ln->fk_entrepot] = array("seuil_stock_alerte" => $ln->seuil_stock_alerte, "desiredstock" => $ln->desiredstock);
            }
        }

        return BimpCache::$cache[$clef];
    }
}
