<?php

class Bimp_Product extends BimpObject
{

    public $stocks = null;
    public static $sousTypes = array(
        0 => '',
        1 => 'Service inter',
        2 => 'Service contrat',
        3 => 'Déplacement inter',
        4 => 'Déplacement contrat',
        5 => 'Logiciel'
    );
    public static $product_type = array(
//        "" => '',
        0 => array('label' => 'Produit', 'icon' => 'fas_box'),
        1 => array('label' => 'Service', 'icon' => 'fas_hand-holding')
    );
    public static $price_base_types = array(
        'HT'  => 'HT',
        'TTC' => 'TTC'
    );
    private static $stockDate = array();
    private static $stockShowRoom = array();
    private static $ventes = array();
    private static $lienShowRoomEntrepot = array();

    // Getters booléens

    public function isSerialisable()
    {
        if ($this->isLoaded()) {
            if ($this->isTypeProduct()) {
                return (int) $this->getData('serialisable');
            }
        }

        return 0;
    }

    public function isNotSerialisable()
    {
        return (int) !$this->isSerialisable();
    }

    public function isActionAllowed($action, &$errors = array())
    {
        switch ($action) {
            case 'generateEtiquettes':
                return 1;
        }

        return (int) parent::isActionAllowed($action, $errors);
    }

    public function isTypeProduct()
    {
        if ((int) $this->getData('fk_product_type') === 0) {
            return 1;
        }

        return 0;
    }

    public function isTypeService()
    {
        if ((int) $this->getData('fk_product_type') === 1) {
            return 1;
        }

        return 0;
    }

    // Getters params: 

    public function getDolObjectUpdateParams()
    {
        global $user;
        if ($this->isLoaded()) {
            return array($this->id, $user);
        }
        return array(0, $user);
    }

    public function getFilesDir()
    {
        return DOL_DATA_ROOT . '/produit/' . dol_sanitizeFileName($this->getRef()) . '/';
    }

    public function getFileUrl($file_name)
    {
        $dir = $this->getFilesDir();
        if ($dir) {
            if (file_exists($dir . $file_name)) {
                return DOL_URL_ROOT . '/document.php?modulepart=produit&file=' . htmlentities(dol_sanitizeFileName($this->getRef()) . '/' . $file_name);
            }
        }

        return '';
    }

    public function getListsButtons($line_qty = 1)
    {
        $buttons = array();
        if ($line_qty >= 0 && $this->isActionAllowed('generateEtiquettes')) {
            $buttons[] = array(
                'label'   => 'Générer des étiquettes',
                'icon'    => 'fas_sticky-note',
                'onclick' => $this->getJsActionOnclick('generateEtiquettes', array(
                    'qty' => (int) $line_qty
                        ), array(
                    'form_name' => 'etiquettes'
                ))
            );
        }

        return $buttons;
    }

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, &$errors = array())
    {
        switch ($field_name) {
            case 'categ1':
            case 'categ2':
            case 'categ3':
                $alias = 'cat_prod';
                $joins[$alias] = array(
                    'alias' => $alias,
                    'table' => 'categorie_product',
                    'on'    => $alias . '.fk_product = a.rowid'
                );
                $filters['cat_prod.fk_categorie'] = array(
                    'in' => $values
                );
                break;
        }
    }

    // Getters données: 

    public function getRemiseCrt()
    {
        if ($this->dol_field_exists('crt')) {
            return (float) $this->getData('crt');
        }

        return 0;
    }

    public function getVentes($dateMin, $dateMax = null, $id_entrepot = null, $id_product = null)
    {
        if (is_null($id_product) && $this->isLoaded()) {
            $id_product = $this->id;
        }

        if ((int) $id_product) {
            if (!isset(self::$ventes[$id_product]))
                self::initVentes($dateMin, $dateMax);

            if (isset(self::$ventes[$dateMin . "-" . $dateMax][$id_product][$id_entrepot])) {
                return self::$ventes[$dateMin . "-" . $dateMax][$id_product][$id_entrepot];
            }
        }

        return array(
            'qty'       => 0,
            'total_ht'  => 0,
            'total_ttc' => 0
        );
    }

    public function getAppleCsvData($dateMin, $dateMax, $entrepot_array, $id_product = null)
    {
        $data = array();

        if (is_null($id_product) && $this->isLoaded()) {
            $id_product = $this->id;
        }

        if ((int) $id_product) {
            foreach ($entrepot_array as $id_entrepot => $ship_to) {
                if (!isset($data[$ship_to])) {
                    $data[$ship_to] = array(
                        'ventes'         => 0,
                        'stock'          => 0,
                        'stock_showroom' => 0
                    );
                }
                $ventes = $this->getVentes($dateMin, $dateMax, $id_entrepot, $id_product);
                $data[$ship_to]['ventes'] += $ventes['qty'];
                $data[$ship_to]['stock'] += $this->getStockDate($dateMax, $id_entrepot, $id_product);
                $data[$ship_to]['stock_showroom'] += $this->getStockShoowRoom($dateMax, $id_entrepot, $id_product);
            }
        }

        return $data;
    }

    // Getters stocks: 

    public function getStocksForEntrepot($id_entrepot)
    {
        $stocks = array(
            'id_stock'       => 0,
            'reel'           => 0,
            'commandes'      => 0, // qté en commande fournisseur
            'dispo'          => 0, // Stock réel - réel réservés
            'virtuel'        => 0, // reel - total_reserves + commandes
            'total_reserves' => 0, // Réservations du statut 0 à - de 300
            'reel_reserves'  => 0, // Réservations du statut 200 à - de 300 + statut 2 (A réserver)
        );

        if ($this->isLoaded()) {
            $product = $this->dol_object;

            $product->load_stock('novirtual');
            if (isset($product->stock_warehouse[(int) $id_entrepot])) {
                $stocks['id_stock'] = $product->stock_warehouse[(int) $id_entrepot]->id;
                $stocks['reel'] = $product->stock_warehouse[(int) $id_entrepot]->real;
            }


            $sql = 'SELECT line.rowid as id_line, c.rowid as id_commande FROM ' . MAIN_DB_PREFIX . 'commande_fournisseurdet line';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'commande_fournisseur c ON c.rowid = line.fk_commande';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'commande_fournisseur_extrafields cef ON c.rowid = cef.fk_object';
            $sql .= ' WHERE line.fk_product = ' . (int) $this->id;
            $sql .= ' AND c.fk_statut < 5';
            $sql .= ' AND cef.entrepot = ' . (int) $id_entrepot;

            $rows = $this->db->executeS($sql, 'array');

            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    // Pour être sûr que les BimpLines existent: 
                    $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', (int) $r['id_commande']);
                    if (BimpObject::ObjectLoaded($commande)) {
                        $commande->checkLines();
                    }

                    $bimp_line = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFournLine', array(
                                'id_line' => (int) $r['id_line']
                                    ), true);

                    if (BimpObject::ObjectLoaded($bimp_line)) {
                        $stocks['commandes'] += ((float) $bimp_line->getFullQty() - $bimp_line->getReceivedQty(null, true));
                    }
                }
            }

            BimpObject::loadClass('bimpreservation', 'BR_Reservation');

            $reserved = BR_Reservation::getProductCounts($this->id, (int) $id_entrepot);
            $stocks['total_reserves'] = $reserved['total'];
            $stocks['reel_reserves'] = $reserved['reel'];

            $stocks['dispo'] = $stocks['reel'] - $stocks['reel_reserves'];
            $stocks['virtuel'] = $stocks['reel'] - $stocks['total_reserves'] + $stocks['commandes'];
        }

        return $stocks;
    }

    public function getStockDate($date, $id_entrepot = null, $id_product = null)
    {
        if (is_null($id_product) && $this->isLoaded()) {
            $id_product = $this->id;
        }

        if ((int) $id_product) {
            if (!isset(self::$stockDate[$date]))
                self::initStockDate($date);

            if (isset(self::$stockDate[$date][$id_product][$id_entrepot]['stock'])) {
                return self::$stockDate[$date][$id_product][$id_entrepot]['stock'];
            }
        }

        return 0;
    }

    public function getStockShoowRoom($date, $id_entrepot = null, $id_product = null)
    {
        if (is_null($id_product) && $this->isLoaded()) {
            $id_product = $this->id;
        }
        $stock = 0;

        if ((int) $id_product) {
//            if (!isset(self::$stockShowRoom[$id_product]))
//                self::initStockShowRoom();
//
//            if (isset(self::$stockShowRoom[$id_product][$id_entrepot])) {
//                return self::$stockShowRoom[$id_product][$id_entrepot];
//            }

            if (!count(self::$lienShowRoomEntrepot))
                self::initLienShowRoomEntrepot();


            if (isset(self::$lienShowRoomEntrepot[$id_entrepot]))
                $stock = $this->getStockDate($date, self::$lienShowRoomEntrepot[$id_entrepot], $id_product);
        }

        return $stock;
        ;
    }

    public static function getStockIconStatic($id_product, $id_entrepot = null)
    {
        if (is_null($id_entrepot)) {
            if (BimpTools::isSubmit('id_entrepot')) {
                $id_entrepot = BimpTools::getValue('id_entrepot');
            } elseif (BimpTools::isSubmit('param_list_filters')) {
                $filters = json_decode(BimpTools::getValue('param_list_filters', array()));
                foreach ($filters as $filter) {
                    if ($filter->name === 'id_commande_client') {
                        $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $filter->filter);
                        if (BimpObject::objectLoaded($commande)) {
                            $id_entrepot = (int) $commande->dol_object->array_options['options_entrepot'];
                            break;
                        }
                    }
                }
            }
        }

        $html = '<span class="objectIcon displayProductStocksBtn" title="Stocks" data-id_product="' . $id_product . '" data-id_entrepot="' . (int) $id_entrepot . '">';
        $html .= BimpRender::renderIcon('fas_box-open');
        $html .= '</span>';
        $html .= '<div class="productStocksContainer hideOnClickOut" id="product_' . $id_product . '_stocks_popover_container"></div>';

        return $html;
    }

    // Getters Catégories: 

    public function getCategoriesList()
    {
        $categories = array();

        if ($this->isLoaded()) {
            foreach (self::getProductCategoriesArray((int) $this->id) as $id_category => $label) {
                $categories[] = (int) $id_category;
            }
        }

        return $categories;
    }

    // Getters FournPrice: 

    public static function getFournisseursPriceArray($id_product, $id_fournisseur = 0, $id_price = 0, $include_empty = true, $empty_label = '')
    {
        if (!(int) $id_product) {
            return array();
        }

        $prices = array();

        if ($include_empty) {
            $prices[0] = $empty_label;
        }

        $filters = array(
            'fp.fk_product' => (int) $id_product
        );

        if ((int) $id_fournisseur) {
            $filters['fp.fk_soc'] = (int) $id_fournisseur;
        }

        if ((int) $id_price) {
            $filters['fp.rowid'] = (int) $id_price;
        }

        $sql = 'SELECT fp.rowid as id, fp.price, fp.quantity as qty, fp.tva_tx as tva, s.nom, s.code_fournisseur as ref';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'product_fournisseur_price fp';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'societe s ON fp.fk_soc = s.rowid';
        $sql .= BimpTools::getSqlWhere($filters);
        $sql .= ' ORDER BY fp.unitprice ASC';

        global $db;
        $bdb = new BimpDb($db);

        $rows = $bdb->executeS($sql, 'array');

        if (!is_null($rows) && count($rows)) {
            foreach ($rows as $r) {
                $label = $r['nom'] . ($r['ref'] ? ' - Réf. ' . $r['ref'] : '') . ' (';
                $label .= BimpTools::displayMoneyValue((float) $r['price'], 'EUR');
                $label .= ' - TVA: ' . BimpTools::displayFloatValue((float) $r['tva']) . '%';
                $label .= ' - Qté min: ' . $r['qty'] . ')';
                $prices[(int) $r['id']] = $label;
            }
        }

        return $prices;
    }

    public static function getFournisseursArray($id_product, $include_empty = true)
    {
        $fournisseurs = array();

        $product = $this->getChildObject('product');

        if (!is_null($product) && $product->isLoaded()) {
            $list = $product->dol_object->list_suppliers();
            foreach ($list as $id_fourn) {
                if (!array_key_exists($id_fourn, $fournisseurs)) {
                    $result = $this->db->getRow('societe', '`rowid` = ' . (int) $id_fourn, array('nom', 'code_fournisseur'));
                    if (!is_null($result)) {
                        $fournisseurs[(int) $id_fourn] = $result->code_fournisseur . ' - ' . $result->nom;
                    } else {
                        echo $this->db->db->error();
                    }
                }
            }
        }

        return $fournisseurs;
    }

    public function getProductFournisseursPricesArray()
    {
        $sql = 'SELECT MAX(fp.rowid) as id FROM ' . MAIN_DB_PREFIX . 'product_fournisseur_price fp WHERE fp.fk_product = ' . (int) $this->id;
        $result = $this->db->executeS($sql);
        if (isset($result[0]->id)) {
            return (int) $result[0]->id;
        }
    }

    public function getCurrentPaHt($id_fourn = null, $with_default = false)
    {
        $pa_ht = 0;

        if ($this->isLoaded()) {
            $pa_ht = (float) $this->getCurrentFournPriceAmount($id_fourn, $with_default);

            if (!$pa_ht) {
                if ((float) $this->getData('cur_pa_ht')) {
                    $pa_ht = (float) $this->getData('cur_pa_ht');
                } elseif ((float) $this->getData('pmp')) {
                    $pa_ht = (float) $this->getData('pmp');
                }
            }

            return $pa_ht;
        }

        return 0;
    }

    public function getCurrentFournPriceId($id_fourn = null, $with_default = false)
    {
        if ($this->isLoaded()) {
            if ((int) $this->getData('id_cur_fp')) {
                return (int) $this->getData('id_cur_fp');
            }

            if ($with_default) {
//            $sql = 'SELECT MAX(fp.rowid) as id FROM ' . MAIN_DB_PREFIX . 'product_fournisseur_price fp WHERE fp.fk_product = ' . $this->id;
                // On retourne le dernier PA fournisseur modifié ou enregistré: 
                $sql = 'SELECT rowid as id, price FROM ' . MAIN_DB_PREFIX . 'product_fournisseur_price WHERE fk_product = ' . (int) $this->id;
                $sql .= ' AND tms = (SELECT MAX(tms) FROM ' . MAIN_DB_PREFIX . 'product_fournisseur_price)';

                if (!is_null($id_fourn) && (int) $id_fourn) {
                    $sql .= ' AND `fk_soc` = ' . (int) $id_fourn;
                }

                $result = $this->db->executeS($sql);
                if (isset($result[0]->id)) {
                    return (int) $result[0]->id;
                }
            }
        }

        return null;
    }

    public function getCurrentFournPriceObject($id_fourn = null, $with_default = false)
    {
        $id_pfp = (int) $this->getCurrentFournPriceId($id_fourn, $with_default);

        if ($id_pfp) {
            $pfp = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ProductFournisseurPrice', $id_pfp);
            if (BimpObject::objectLoaded($pfp)) {
                return $pfp;
            }
        }

        return null;
    }

    public function getCurrentFournPriceAmount($id_fourn = null, $with_default = false)
    {
        $pfp = $this->getCurrentFournPriceObject($id_fourn, $with_default);

        if (BimpObject::objectLoaded($pfp)) {
            return (float) $pfp->getData('price');
        }

        return 0;
    }

    // Affichages: 

    public function displayEntrepotStock($id_entrepot)
    {
        $html = '';

        if ((int) $this->isLoaded()) {
            $stocks = $this->getStocksForEntrepot($id_entrepot);

            $html .= '<div style="display: inline-block; margin-right: 8px" class="bs-popover"';
            $html .= BimpRender::renderPopoverData('Stock réel');
            $html .= '>';
            $html .= '<span class="' . ($stocks['reel'] > 0 ? 'success' : 'danger') . '">';
            $html .= BimpRender::renderIcon('fas_warehouse', 'iconLeft') . $stocks['reel'];
            $html .= '</span>';
            $html .= '</div>';

            $html .= '<div style="display: inline-block; margin-right: 8px" class="bs-popover"';
            $html .= BimpRender::renderPopoverData('Stock disponible');
            $html .= '>';
            $html .= '<span class="' . ($stocks['dispo'] > 0 ? 'success' : 'danger') . '">';
            $html .= BimpRender::renderIcon('fas_check', 'iconLeft') . $stocks['dispo'];
            $html .= '</span>';
            $html .= '</div>';

            $html .= '<div style="display: inline-block; margin-right: 8px" class="bs-popover"';
            $html .= BimpRender::renderPopoverData('Qtés commandées');
            $html .= '>';
            $html .= '<span class="' . ($stocks['commandes'] > 0 ? 'success' : 'danger') . '">';
            $html .= BimpRender::renderIcon('fas_cart-arrow-down', 'iconLeft') . $stocks['commandes'];
            $html .= '</span>';
            $html .= '</div>';

//            $html .= '<div>';
//            $html .= 'Total réservés: '.$stocks['total_reserves'].'<br/>';
//            $html .= 'Réel réservés: '.$stocks['reel_reserves'].'<br/>';
//            $html .= '</div>';
        }

        return $html;
    }

    // Traitements: 

    public function addConfigExtraParams()
    {
        $entrepots = BimpCache::getEntrepotsArray();

        $cols = array();

        foreach ($entrepots as $id_entrepot => $label) {
            $cols['stock_' . $id_entrepot] = array(
                'label' => 'Stock ' . $label,
                'value' => array(
                    'callback' => array(
                        'method' => 'displayEntrepotStock',
                        'params' => array(
                            $id_entrepot
                        )
                    )
                )
            );
        }

        $this->config->addParams('lists_cols', $cols);
    }

    public function fetchStocks()
    {
        $this->stocks = array();

        $where = '`statut` > 0';
        $rows = $this->db->getRows('entrepot', $where, null, 'array', array(
            'rowid', 'ref'
        ));


        if (!is_null($rows)) {
            foreach ($rows as $r) {
                $stocks = $this->getStocksForEntrepot((int) $r['rowid']);
                $this->stocks[(int) $r['rowid']] = array(
                    'entrepot_label' => $r['ref'],
                    'reel'           => $stocks['reel'],
                    'dispo'          => $stocks['dispo'],
                    'virtuel'        => $stocks['virtuel'],
                    'commandes'      => $stocks['commandes'],
                    'total_reserves' => $stocks['total_reserves'],
                    'reel_reserves'  => $stocks['reel_reserves']
                );
            }
        }
    }

    // Rendus HTML: 

    public function renderHeaderStatusExtra()
    {
        $html = '';

        if ($this->isLoaded()) {
            if ((int) $this->getData('validate')) {
                $html .= '<span class="success">';
                $html .= BimpRender::renderIcon('fas_check', 'iconLeft');
                $html .= 'Validé';
                $html .= '</span>';
            } else {
                $html .= '<span class="danger">';
                $html .= BimpRender::renderIcon('fas_times', 'iconLeft');
                $html .= 'Non validé';
                $html .= '</span>';
            }
        }

        return $html;
    }

    public function renderStocksByEntrepots($id_entrepot = null)
    {
        if (!$this->isLoaded()) {
            return BimpRender::renderAlerts('ID du produit absent');
        }

        if (is_null($this->stocks)) {
            $this->fetchStocks();
        }

        if (is_null($id_entrepot) || $id_entrepot == "") {
            $id_entrepot = self::getDefaultEntrepot();
        }

        $html = '';

        $html .= '<div class="productStocksContent" data-id_product="' . $this->id . '">';
        $html .= '<h3><i class="fas fa5-box-open iconLeft"></i>Stocks produit ' . $this->getData('ref') . '</h3>';
        $html .= '<div class="stockSearchContainer">';
        $html .= '<i class="fa fa-search iconLeft"></i>';
        $html .= BimpInput::renderInput('text', 'stockSearch', '');
        $html .= '</div>';
        $html .= '<table class="productStockTable bimp_list_table">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Entrepôt</th>';
        $html .= '<th>Réel</th>';
        $html .= '<th>Dispo</th>';
        $html .= '<th>Virtuel</th>';
        $html .= '</tr>';
        $html .= '</thead>';

        $html .= '<tbody>';

        if (!is_null($id_entrepot) && isset($this->stocks[(int) $id_entrepot])) {
            $html .= '<tr class="currentEntrepot">';
            $html .= '<td>' . $this->stocks[(int) $id_entrepot]['entrepot_label'] . '</td>';
            $html .= '<td>' . $this->stocks[(int) $id_entrepot]['reel'] . '</td>';
            $html .= '<td>' . $this->stocks[(int) $id_entrepot]['dispo'] . '</td>';
            $html .= '<td>' . $this->stocks[(int) $id_entrepot]['virtuel'] . '</td>';
            $html .= '</tr>';
        }

        $html1 = $html2 = "";
        foreach ($this->stocks as $id_ent => $stocks) {
            if (!is_null($id_entrepot) && ((int) $id_entrepot === (int) $id_ent)) {
                continue;
            }
            $htmlT = '<tr>';
            $htmlT .= '<td>' . $stocks['entrepot_label'] . '</td>';
            $htmlT .= '<td>' . $stocks['reel'] . '</td>';
            $htmlT .= '<td>' . $stocks['dispo'] . '</td>';
            $htmlT .= '<td>' . $stocks['virtuel'] . '</td>';
            $htmlT .= '</tr>';
            if ($stocks['reel'] <= 0) {//stok > 0 au debut
                $html2 .= $htmlT;
            } else {
                $html1 .= $htmlT;
            }
        }
        $html .= $html1 . $html2;

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    public function renderListeFournisseur()
    {
        $html = '';

        $html .= '<div class="page_content container-fluid">';
        $instance = BimpObject::getInstance('bimpcore', 'Bimp_ProductFournisseurPrice');

        $list = new BC_ListTable($instance, 'default', 1, null, '', 'plus');
        $list->addFieldFilterValue('fk_product', $this->id);

        $html .= $list->renderHtml();
        $html .= '</div>';

        return $html;
    }

    // Actions: 

    public function actionGenerateEtiquettes($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $success_callback = '';

        if (!$this->isLoaded()) {
            $errors[] = 'ID du produit absent';
        } else {
            $type = isset($data['type']) ? (string) $data['type'] : '';

            if (!$type) {
                $errors[] = 'Type d\'étiquette à générer absent';
            } else {
                $qty = isset($data['qty']) ? (int) $data['qty'] : 1;

                $url = DOL_URL_ROOT . '/bimplogistique/etiquette_produit.php?id_product=' . $this->id . '&qty=' . $qty . '&type=' . $type;

                $success_callback = 'window.open(\'' . $url . '\')';
            }
        }


        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    // Overrides:

    public function validatePost()
    {
        $marque = BimpTools::getValue('marque', '');
        $ref_const = BimpTools::getValue('ref_constructeur', '');

        if ($marque && $ref_const) {
            $ref = strtoupper(substr($marque, 0, 3));
            $ref .= '-' . $ref_const;
            $this->set('ref', $ref);
        }

        return parent::validatePost();
    }

    // Méthodes statiques : 

    private static function initStockDate($date)
    {
        global $db;
        self::$stockDate = array();
        $sql = $db->query("SELECT `fk_product`,`fk_entrepot`,reel FROM `llx_product_stock`");
        while ($ln = $db->fetch_object($sql)) {
            self::$stockDate[$date][$ln->fk_product][$ln->fk_entrepot]['now'] = $ln->reel;
            self::$stockDate[$date][$ln->fk_product][$ln->fk_entrepot]['stock'] = $ln->reel;
            self::$stockDate[$date][$ln->fk_product][null]['now'] += $ln->reel;
            self::$stockDate[$date][$ln->fk_product][null]['stock'] += $ln->reel;
        }

//        $sql = $db->query("SELECT `fk_product`, `fk_entrepot`, SUM(`value`) as nb FROM `llx_stock_mouvement` WHERE `tms` > STR_TO_DATE('" . $date . "', '%Y-%m-%d') GROUP BY `fk_product`, `fk_entrepot`");
        $sql = $db->query("SELECT `fk_product`, `fk_entrepot`, SUM(`value`) as nb FROM `llx_stock_mouvement` WHERE `tms` > '" . $date . "' GROUP BY `fk_product`, `fk_entrepot`");
        while ($ln = $db->fetch_object($sql)) {
            if (!isset(self::$stockDate[$date][$ln->fk_product][$ln->fk_entrepot]['stock']))
                self::$stockDate[$date][$ln->fk_product][$ln->fk_entrepot]['stock'] = 0;
            if (!isset(self::$stockDate[$date][$ln->fk_product][null]['stock']))
                self::$stockDate[$date][$ln->fk_product][null]['stock'] = 0;

            self::$stockDate[$date][$ln->fk_product][$ln->fk_entrepot]['stock'] -= $ln->nb;
            self::$stockDate[$date][$ln->fk_product][null]['stock'] -= $ln->nb;
        }
    }

    private static function initStockShowRoom()
    {
        global $db;
        self::$stockShowRoom = array();
//        $sql = $db->query("SELECT `id_product`, `id_entrepot`, COUNT(*)as nb FROM `llx_be_equipment_place` p, llx_be_equipment e WHERE position = 1 AND p.id_equipment = e.id AND p.`type` = 5 GROUP BY `id_entrepot`, `id_product`");
//        while ($ln = $db->fetch_object($sql)) {
//            self::$stockShowRoom[$ln->id_product][$ln->id_entrepot] = $ln->nb;
//            self::$stockShowRoom[$ln->id_product][null] += $ln->nb;
//        }
    }

    private static function initLienShowRoomEntrepot()
    {
        global $db;
        self::$lienShowRoomEntrepot = array();
        $sql = $db->query("SELECT e.rowid as id1, e2.rowid as id2 FROM `llx_entrepot` e,`llx_entrepot` e2 WHERE e2.ref = CONCAT('D',e.ref)");
        while ($ln = $db->fetch_object($sql)) {
            self::$lienShowRoomEntrepot[$ln->id1] = $ln->id2;
        }

        //
    }

    private static function initVentes($dateMin, $dateMax)
    {
        global $db;
        self::$ventes = array();
        $query = "SELECT `fk_product`, entrepot, sum(qty) as qty, sum(l.total_ht) as total_ht, sum(l.total_ttc) as total_ttc ";
        $query .= " FROM `llx_facturedet` l, llx_facture f, llx_facture_extrafields e";
        $query .= " WHERE `fk_facture` = f.rowid AND e.fk_object = f.rowid AND fk_product > 0";
        if ($dateMin)
//            $query .= " AND date_valid > STR_TO_DATE('" . $dateMin . "', '%Y-%m-%d')";
            $query .= " AND date_valid >= '" . $dateMin . "'";
        if ($dateMax)
            $query .= " AND date_valid <= '" . $dateMax . "'";
//            $query .= " AND date_valid > STR_TO_DATE('" . $dateMax . "', '%Y-%m-%d')";

        $group_by .= " GROUP BY `fk_product`, entrepot";

        $sql = $db->query($query . " AND `subprice` >= 0" . $group_by);

        while ($ln = $db->fetch_object($sql)) {
            self::$ventes[$dateMin . "-" . $dateMax][$ln->fk_product][$ln->entrepot]['qty'] = $ln->qty;
            self::$ventes[$dateMin . "-" . $dateMax][$ln->fk_product][$ln->entrepot]['total_ht'] = $ln->total_ht;
            self::$ventes[$dateMin . "-" . $dateMax][$ln->fk_product][$ln->entrepot]['total_ttc'] = $ln->total_ttc;
//            if(!isset(self::$ventes[$dateMin."-".$dateMax][$ln->fk_product][null]))
//                    self::$ventes[$dateMin."-".$dateMax][$ln->fk_product][null] = array();
            self::$ventes[$dateMin . "-" . $dateMax][$ln->fk_product][null]['qty'] += $ln->qty;
            self::$ventes[$dateMin . "-" . $dateMax][$ln->fk_product][null]['total_ht'] += $ln->total_ht;
            self::$ventes[$dateMin . "-" . $dateMax][$ln->fk_product][null]['total_ttc'] += $ln->total_ttc;
        }

        $sql2 = $db->query($query . " AND `subprice` < 0" . $group_by);

        while ($ln = $db->fetch_object($sql2)) {
            self::$ventes[$dateMin . "-" . $dateMax][$ln->fk_product][$ln->entrepot]['qty'] = ($ln->qty * -1);
            self::$ventes[$dateMin . "-" . $dateMax][$ln->fk_product][$ln->entrepot]['total_ht'] = $ln->total_ht;
            self::$ventes[$dateMin . "-" . $dateMax][$ln->fk_product][$ln->entrepot]['total_ttc'] = $ln->total_ttc;

            self::$ventes[$dateMin . "-" . $dateMax][$ln->fk_product][null]['qty'] += ($ln->qty * -1);
            self::$ventes[$dateMin . "-" . $dateMax][$ln->fk_product][null]['total_ht'] += $ln->total_ht;
            self::$ventes[$dateMin . "-" . $dateMax][$ln->fk_product][null]['total_ttc'] += $ln->total_ttc;
        }
    }
}
