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
    public static $units_weight;
    public static $units_length;
    public static $units_surface;
    public static $units_volume;
    private static $stockDate = array();
    private static $stockShowRoom = array();
    private static $ventes = array();
    private static $lienShowRoomEntrepot = array();

    public function __construct($module, $object_name)
    {
        global $langs;
        $langs->load('other');

        self::$units_weight = array(
            3  => array('label' => $langs->transnoentitiesnoconv('WeightUnitton')),
            0  => array('label' => $langs->transnoentitiesnoconv('WeightUnitkg')),
            -3 => array('label' => $langs->transnoentitiesnoconv('WeightUnitg')),
            -6 => array('label' => $langs->transnoentitiesnoconv('WeightUnitmg')),
            98 => array('label' => $langs->transnoentitiesnoconv('WeightUnitounce')),
            99 => array('label' => $langs->transnoentitiesnoconv('WeightUnitpound'))
        );

        self::$units_length = array(
            0   => array('label' => $langs->transnoentitiesnoconv('SizeUnitm')),
            -1  => array('label' => $langs->transnoentitiesnoconv('SizeUnitdm')),
            -2  => array('label' => $langs->transnoentitiesnoconv('SizeUnitcm')),
            -3  => array('label' => $langs->transnoentitiesnoconv('SizeUnitmm')),
            -98 => array('label' => $langs->transnoentitiesnoconv('SizeUnitfoot')),
            -99 => array('label' => $langs->transnoentitiesnoconv('SizeUnitinch'))
        );

        self::$units_surface = array(
            0  => array('label' => $langs->transnoentitiesnoconv('SurfaceUnitm2')),
            -2 => array('label' => $langs->transnoentitiesnoconv('SurfaceUnitdm2')),
            -4 => array('label' => $langs->transnoentitiesnoconv('SurfaceUnitcm2')),
            -6 => array('label' => $langs->transnoentitiesnoconv('SurfaceUnitmm2')),
            98 => array('label' => $langs->transnoentitiesnoconv('SurfaceUnitfoot2')),
            99 => array('label' => $langs->transnoentitiesnoconv('SurfaceUnitinch2'))
        );

        self::$units_volume = array(
            0  => array('label' => $langs->transnoentitiesnoconv('VolumeUnitm3')),
            -3 => array('label' => $langs->transnoentitiesnoconv('VolumeUnitdm3')),
            -6 => array('label' => $langs->transnoentitiesnoconv('VolumeUnitcm3')),
            -9 => array('label' => $langs->transnoentitiesnoconv('VolumeUnitmm3')),
            88 => array('label' => $langs->transnoentitiesnoconv('VolumeUnitfoot3')),
            89 => array('label' => $langs->transnoentitiesnoconv('VolumeUnitinch3')),
            97 => array('label' => $langs->transnoentitiesnoconv('VolumeUnitounce')),
            98 => array('label' => $langs->transnoentitiesnoconv('VolumeUnitlitre')),
            99 => array('label' => $langs->transnoentitiesnoconv('VolumeUnitgallon'))
        );

        parent::__construct($module, $object_name);
    }

    // Droits user: 

    public function canEditField($field_name)
    {
        global $user;

        switch ($field_name) {
            case 'validate':
                if ((int) $user->admin != 1) {
                    return 0;
                }
                return 1;
        }

        return parent::canEditField($field_name);
    }

    public function canSetAction($action)
    {
        global $user;

        switch ($action) {
            case 'validate':
                // todo: définir droit pour valider. 
                return 1;
        }

        return parent::canSetAction($field_name);
    }

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

            case 'validate':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }
                if ((int) $this->getData('validate')) {
                    $errors[] = 'Ce produit est déjà validé';
                    return 0;
                }
                return 1;
            case 'merge':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }
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

    public function isDeletable($force_delete = false, &$errors = array())
    {
        if (!$this->isLoaded($errors)) {
            return 0;
        }

        if ($this->isTypeProduct()) {
            // Check réservations: 
            $list = BimpCache::getBimpObjectList('bimpreservation', 'BR_Reservation', array(
                        'id_product' => (int) $this->id
            ));

            if (count($list)) {
                $errors[] = 'Des réservations ont été créées pour ce produit';
            }

            // Check stock: 

            $sql = 'SELECT `rowid` FROM ' . MAIN_DB_PREFIX . 'product_stock WHERE `fk_product` = ' . $this->id . ' AND `reel` != 0';
            $list = $this->db->executeS($sql, 'array');

            if (is_array($list) && count($list)) {
                $errors[] = 'Un stock a été enregistré pour ce produit';
            }

            // Check equipements: 
            if ($this->isSerialisable()) {
                $list = BimpCache::getBimpObjectList('bimpequipment', 'Equipment', array(
                            'id_product' => (int) $this->id
                ));

                if (count($list)) {
                    $errors[] = 'Des équipements ont été créés pour ce produit';
                }
            }
        }

        // Check commandes validées: 

        $sql = 'SELECT c.rowid FROM ' . MAIN_DB_PREFIX . 'commande c';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'commandedet l ON c.rowid = l.fk_commande';
        $sql .= ' WHERE c.fk_statut > 0 AND l.fk_product = ' . (int) $this->id;

        $rows = $this->db->executeS($sql, 'array');

        if (is_array($rows) && count($rows)) {
            $errors[] = 'Ce produit est présent dans au moins une commande client validée';
        }

        // Check factures: 

        $sql = 'SELECT f.rowid FROM ' . MAIN_DB_PREFIX . 'facture f';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'facturedet l ON f.rowid = l.fk_facture';
        $sql .= ' WHERE l.fk_product = ' . (int) $this->id;

        $rows = $this->db->executeS($sql, 'array');

        if (is_array($rows) && count($rows)) {
            $errors[] = 'Ce produit est présent dans au moins une facture client';
        }

        // Check commandes fourn validées: 

        $sql = 'SELECT c.rowid FROM ' . MAIN_DB_PREFIX . 'commande_fournisseur c';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'commande_fournisseurdet l ON c.rowid = l.fk_commande';
        $sql .= ' WHERE c.fk_statut > 0 AND l.fk_product = ' . (int) $this->id;

        $rows = $this->db->executeS($sql, 'array');

        if (is_array($rows) && count($rows)) {
            $errors[] = 'Ce produit est présent dans au moins une commande fournisseur validée';
        }

        // Check factures fourn: 

        $sql = 'SELECT f.rowid FROM ' . MAIN_DB_PREFIX . 'facture_fourn f';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'facture_fourn_det l ON f.rowid = l.fk_facture_fourn';
        $sql .= ' WHERE l.fk_product = ' . (int) $this->id;

        $rows = $this->db->executeS($sql, 'array');

        if (is_array($rows) && count($rows)) {
            $errors[] = 'Ce produit est présent dans au moins une facture fournisseur';
        }

        return (count($errors) ? 0 : 1);
    }

    public function isProductMergeable($product, &$errors = array())
    {
        if ((int) $product->getData('fk_product_type') !== (int) $product->getData('fk_product_type')) {
            $errors[] = 'Les deux produits à fusionner ne sont pas du même type';
        }

        return (count($errors) ? 0 : 1);
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
    
    public function getActionsButtons()
    {
        global $user;
        $buttons = array();
        if (!$this->isLoaded() or (int) $user->admin != 1)
            return $buttons;

        if ($this->isActionAllowed('validate') && $this->canSetAction('validate')) {
            $buttons[] = array(
                'label'   => 'Valider',
                'icon'    => 'fas_check-circle',
                'onclick' => $this->getJsActionOnclick('validate', array(), array(
                        //                'success_callback' => 'function(result) {}',
                ))
            );
        }

        $buttons[] = array(
            'label'   => 'Fusionner',
            'icon'    => 'fas_object-group',
            'onclick' => $this->getJsActionOnclick('merge', array(), array(
                'form_name' => 'merge'
            ))
        );

        return $buttons;
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

    public function getCommandes()
    {
        require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
        $commandes = array();

        $sql = 'SELECT DISTINCT(c.rowid) as id';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'commande as c';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'commandedet as cd ON c.rowid=cd.fk_commande';
        $sql .= ' WHERE cd.fk_product=' . $this->getData('id');

        $result = $this->db->db->query($sql);
        if ($result and $this->db->db->num_rows($result) > 0) {
            while ($result and $obj = $this->db->db->fetch_object($result)) {
                $commande = new Commande($this->db->db);
                $commande->fetch($obj->id);
                $commandes[] = $commande;
            }
        }
        return $commandes;
    }

    public function getPropals()
    {
        // 
        require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
        $propals = array();

        $sql = 'SELECT DISTINCT(p.rowid) as id';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'propal as p';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'propaldet as pd ON p.rowid=pd.fk_propal';
        $sql .= ' WHERE pd.fk_product=' . $this->getData('id');

        $result = $this->db->db->query($sql);
        if ($result and $this->db->db->num_rows($result) > 0) {
            while ($result and $obj = $this->db->db->fetch_object($result)) {
                $propal = new Propal($this->db->db);
                $propal->fetch($obj->id);
                $propals[] = $propal;
            }
        }
        return $propals;
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

    // Gestion FournPrice: 

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
            if (!$pa_ht) {
                if ((float) $this->getData('cur_pa_ht')) {
                    $pa_ht = (float) $this->getData('cur_pa_ht');
                } elseif ((float) $this->getData('pmp')) {
                    $pa_ht = (float) $this->getData('pmp');
                } else {
                    $pa_ht = (float) $this->getCurrentFournPriceAmount($id_fourn, $with_default);
                }
            }

            return $pa_ht;
        }

        return 0;
    }

    public function getCurrentFournPriceId($id_fourn = null, $with_default = false)
    {
        if ((int) $this->getData('id_cur_fp')) {
            return (int) $this->getData('id_cur_fp');
        }

        $id_fp = 0;

        if ($this->isLoaded()) {
            $pa_ht = 0;
            if ((float) $this->getData('cur_pa_ht')) {
                $pa_ht = (float) $this->getData('cur_pa_ht');
            } elseif ((float) $this->getData('pmp')) {
                $pa_ht = (float) $this->getData('pmp');
            }

            if ($pa_ht) {
                $id_fp = (int) $this->findFournPriceIdForPaHt($pa_ht, $id_fourn);
            }

            if (!$id_fp && $with_default) {
//            $sql = 'SELECT MAX(fp.rowid) as id FROM ' . MAIN_DB_PREFIX . 'product_fournisseur_price fp WHERE fp.fk_product = ' . $this->id;
//            
                // On retourne le dernier PA fournisseur modifié ou enregistré: 
                $where1 = 'fk_product = ' . (int) $this->id;

                if (!is_null($id_fourn) && (int) $id_fourn) {
                    $where1 .= ' AND `fk_soc` = ' . (int) $id_fourn;
                }
                $where = $where1 . ' AND tms = (SELECT MAX(tms) FROM ' . MAIN_DB_PREFIX . 'product_fournisseur_price WHERE ' . $where1 . ')';

                $sql = 'SELECT rowid as id, price FROM ' . MAIN_DB_PREFIX . 'product_fournisseur_price WHERE ' . $where;

                $result = $this->db->executeS($sql);
                if (isset($result[0]->id)) {
                    $id_fp = (int) $result[0]->id;
                }
            }
        }

        return $id_fp;
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

    public function findFournPriceIdForPaHt($pa_ht, $id_fourn = null)
    {
        if ($this->isLoaded()) {
            $where1 = '`fk_product` = ' . (int) $this->id . ' AND `price` = ' . (float) $pa_ht;

            if (!is_null($id_fourn)) {
                $where1 .= ' AND `fk_soc` = ' . (int) $id_fourn;
            }

            $where = $where1 . ' AND tms = (SELECT MAX(tms) FROM ' . MAIN_DB_PREFIX . 'product_fournisseur_price WHERE ' . $where1 . ')';
            return (int) $this->db->getValue('product_fournisseur_price', 'rowid', $where);
        }

        return 0;
    }

    public function setCurrentPaHt($pa_ht, $id_fourn_price = 0, $origin = '', $id_origin = 0)
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            if ((float) $this->getData('cur_pa_ht') !== (float) $pa_ht) {
                $this->set('cur_pa_ht', (float) $pa_ht);
                $this->set('id_cur_fp', (int) $id_fourn_price);
                $this->set('cur_pa_origin', $origin);
                $this->set('cur_pa_id_origin', (int) $id_origin);
                $errors = $this->update($w, true);
            }
        }

        return $errors;
    }

    public function getBestBuyPrice()
    {

        $sql = 'SELECT price FROM `' . MAIN_DB_PREFIX . 'product_fournisseur_price`';
        $sql .= ' WHERE fk_product=' . $this->getData('id');
        $sql .= ' GROUP BY fk_product';
        $sql .= ' HAVING(MIN(PRICE))';
        $rows = $this->db->executeS($sql);

        if (!empty($rows)) {
            return $rows[0]->price;
        }
        return 00.00;
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

    public function renderCategorize()
    {
        $html = '';

        if ($this->isLoaded()) {

            $html = BimpRender::renderPanel('Catégories', $html, '', array(
                        'foldable' => false,
                        'type'     => 'secondary',
                        'panel_id' => 'test',
            ));
        }
        return $html;
    }

    public function renderMergeKeptProductInput()
    {
        $errors = array();

        if (!$this->isLoaded($errors)) {
            return BimpRender::renderAlerts($errors);
        }

        $options = array(
            $this->id => $this->getRef()
        );

        $id_merged_product = (int) BimpTools::getPostFieldValue('id_merged_product', 0);

        if ($id_merged_product) {
            $product = BimpCache::getBimpObjectInstance($this->module, $this->object_name, $id_merged_product);
            if (!BimpObject::objectLoaded($product)) {
                $errors[] = 'Le produit d\'ID ' . $id_merged_product . ' n\'existe pas';
            } else {
                $this->isProductMergeable($product, $errors);
            }

            if (count($errors)) {
                return BimpRender::renderAlerts($errors);
            }

            $options[(int) $id_merged_product] = $product->getRef();
        }

        return BimpInput::renderInput('select', 'id_kept_product', BimpTools::getPostFieldValue('id_kept_product', $this->id), array(
                    'options' => $options
        ));
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

    public function validateProduct()
    {

        $errors = array();
        if (!(int) $this->getCurrentFournPriceId(null, true)) {
            $errors[] = "Veuillez enregistrer au moins un prix d'achat fournisseur";
        }

        if ((int) $this->getData('fk_product_type') == 1 and
                (int) $this->getData('serialisable') == 1)
            $errors[] = "Un service ne peut pas être sérialisé.";

        if (sizeof($errors) > 0)
            return $errors;

        $cur_pa_ht = $this->getCurrentPaHt(null, true);

        $this->updateField('cur_pa_ht', $cur_pa_ht);
        $this->updateField('validate', 1);

        require_once DOL_DOCUMENT_ROOT . '/synopsistools/SynDiversFunction.php';

        // COMMAND
        $commandes_c = $this->getCommandes();
        foreach ($commandes_c as $commande) {
            $email_sent = false;
            $list_contact = $commande->liste_contact(-1, 'internal');

            // Search responsible
            foreach ($list_contact as $contact) {
                if ($contact['code'] == 'SALESREPFOLL' and ! $email_sent) {
                    $errors = array_merge($errors, $this->sendEmailCommande($commande, $contact['email']));
                    $email_sent = true;
                    break;
                }
            }

            // Search signatory
            if (!$email_sent) {
                foreach ($list_contact as $contact) {
                    $errors = array_merge($errors, $this->sendEmailCommande($commande, $contact['email']));
                    $email_sent = true;
                    break;
                }
            }

            // Use main commercial Franck PINERI
            if (!$email_sent) {
                require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
                $user = new User($this->db->db);
                $user->fetch((int) 62);
                $errors = array_merge($errors, $this->sendEmailCommande($commande, $user->email));
                $email_sent = true;
                continue;
            }
        }

        // PROPALS
        $propals = $this->getPropals();
        foreach ($propals as $propal) {
            $email_sent = false;
            $list_contact = $propal->liste_contact(-1, 'internal');

            // Search responsible
            foreach ($list_contact as $contact) {
                if ($contact['code'] == 'SALESREPFOLL' and ! $email_sent) {
                    $errors = array_merge($errors, $this->sendEmailPropal($propal, $contact['email']));
                    $email_sent = true;
                    break;
                }
            }

            // Search signatory
            if (!$email_sent) {
                foreach ($list_contact as $contact) {
                    $errors = array_merge($errors, $this->sendEmailPropal($propal, $contact['email']));
                    $email_sent = true;
                    break;
                }
            }

            // Use main commercial Franck PINERI
            if (!$email_sent) {
                require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
                $user = new User($this->db->db);
                $user->fetch((int) 62);
                $errors = array_merge($errors, $this->sendEmailCommande($commande, $user->email));
                $email_sent = true;
                continue;
            }
        }

        return $errors;
    }

    private function sendEmailCommande($commande, $to)
    {
        $errors = array();
        $subject = 'Produit validé pour la commande ' . $commande->ref;
        $from = 'gle@bimp.fr';
        $msg = 'Bonjour,<br/>Le produit ' . $this->getData('ref') . ' a été validé, la commande ' . $commande->getNomUrl();
        $msg .= ' est peut-être validable.';
        if (!mailSyn2($subject, $to, $from, $msg))
            $errors[] = "Envoi email vers " . $to . "impossible.";
        return $errors;
    }

    private function sendEmailPropal($propal, $to)
    {
        $errors = array();
        $subject = 'Produit validé pour la propale ' . $propal->ref;
        $from = 'gle@bimp.fr';
        $msg = 'Bonjour,<br/>Le produit ' . $this->getData('ref') . ' a été validé, la propale ' . $propal->getNomUrl();
        $msg .= ' est peut-être validable.';
        if (!mailSyn2($subject, $to, $from, $msg))
            $errors[] = "Envoi email vers " . $to . "impossible.";
        return $errors;
    }

    public function mergeProduct(Bimp_Product $merged_product, &$warnings = array())
    {
        $errors = array();

        if (!$this->isLoaded($errors)) {
            return $errors;
        }

        if (!BimpObject::objectLoaded($merged_product) || !is_a($merged_product, 'Bimp_Product')) {
            $errors[] = 'Produit à fusionner invalide';
            return $errors;
        }

        if (!$merged_product->isDeletable(false, $errors)) {
            return $errors;
        }

        if (!$this->isProductMergeable($merged_product, $errors)) {
            return $errors;
        }

        $id_merged_product = (int) $merged_product->id;

        if (count($errors)) {
            return $errors;
        }

        // Remplacement des ID: 

        $pu_ht = (float) $this->getData('price');
        $tva_tx = (float) $this->getData('tva_tx');
        $price_base = $this->getdata('price_base_type');

        if ($price_base === 'TTC') {
            $pu_ht = (float) BimpTools::calculatePriceTaxEx($pu_ht, $tva_tx);
        }

        $pa_ht = $this->getCurrentPaHt(null, true);

        // Màj des propales validées: 
        $sql = 'UPDATE ' . MAIN_DB_PREFIX . 'propaldet l';
        $sql .= ' SET l.fk_product = ' . (int) $this->id;
        $sql .= ' WHERE l.fk_product = ' . (int) $id_merged_product;
        $sql .= ' AND l.fk_propal IN (SELECT p.rowid FROM ' . MAIN_DB_PREFIX . 'propal p WHERE p.fk_statut > 0)';

        if ($this->db->execute($sql) <= 0) {
            $warnings[] = 'Erreurs lors du changement d\'ID pour les propales validées - ' . $this->db->db->lasterror();
        }

        // Pour la suite, on passe par les objets pour que les prix et les totaux soient mis à jour: 
        // Màj des propales non validées: 
        $sql = 'SELECT l.rowid as id FROM ' . MAIN_DB_PREFIX . 'propaldet l';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'propal p ON p.rowid = l.fk_propal';
        $sql .= ' WHERE l.fk_product = ' . (int) $id_merged_product . ' AND p.fk_statut = 0';

        $rows = $this->db->executeS($sql, 'array');

        if (is_array($rows) && count($rows)) {
            foreach ($rows as $r) {
                $line = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_PropalLine', array(
                            'id_line' => (int) $r['id']
                                ), true);

                $line->id_product = $this->id;
                $line->pu_ht = $pu_ht;
                $line->tva_tx = $tva_tx;
                $line->pa_ht = $pa_ht;

                $line_warnings = array();
                $line_errors = $line->update($line_warnings, true);

                if (count($line_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($line_warnings, 'Echec de la mise à jour ' . $line->getLabel('of_the'));
                }

                if (count($line_warnings)) {
                    $warnings[] = BimpTools::getMsgFromArray($line_warnings, 'Erreurs lors de la mise à jour ' . $line->getLabel('of_the'));
                }
            }
        }

        // Màj des commandes non validées:
        $sql = 'SELECT l.rowid as id FROM ' . MAIN_DB_PREFIX . 'commandedet l';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'commande c ON c.rowid = l.fk_commande';
        $sql .= ' WHERE l.fk_product = ' . (int) $id_merged_product . ' AND c.fk_statut = 0';

        $rows = $this->db->executeS($sql, 'array');

        if (is_array($rows) && count($rows)) {
            foreach ($rows as $r) {
                $line = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', array(
                            'id_line' => (int) $r['id']
                                ), true);
                if (!BimpObject::objectLoaded($line)) {
                    continue;
                }

                $line->id_product = $this->id;

                $line_warnings = array();
                $line_errors = $line->update($line_warnings, true);

                if (!count($line_errors)) {
                    $line->pu_ht = $pu_ht;
                    $line->tva_tx = $tva_tx;
                    $line->pa_ht = $pa_ht;

                    $line_errors = $line->update($line_warnings, true);
                }

                if (count($line_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($line_warnings, 'Echec de la mise à jour ' . $line->getLabel('of_the'));
                }

                if (count($line_warnings)) {
                    $warnings[] = BimpTools::getMsgFromArray($line_warnings, 'Erreurs lors de la mise à jour ' . $line->getLabel('of_the'));
                }
            }
        }

        // Màj des commandes fourn non validées: 
        $sql = 'SELECT l.rowid as id FROM ' . MAIN_DB_PREFIX . 'commande_fournisseurdet l';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'commande_fournisseur c ON c.rowid = l.fk_commande';
        $sql .= ' WHERE l.fk_product = ' . (int) $id_merged_product . ' AND c.fk_statut = 0';

        $rows = $this->db->executeS($sql, 'array');

        if (is_array($rows) && count($rows)) {
            foreach ($rows as $r) {
                $line = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFournLine', array(
                            'id_line' => (int) $r['id']
                                ), true);
                if (!BimpObject::objectLoaded($line)) {
                    continue;
                }

                $line->id_product = $this->id;

                $line_warnings = array();
                $line_errors = $line->update($line_warnings, true);

                if (count($line_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($line_warnings, 'Echec de la mise à jour ' . $line->getLabel('of_the'));
                } else {
                    $line->pu_ht = $pu_ht;
                    $line->tva_tx = $tva_tx;
                    $line->pa_ht = $pa_ht;

                    $line_errors = $line->update($line_warnings, true);
                    if (count($line_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($line_warnings, 'Echec de la mise à jour ' . $line->getLabel('of_the'));
                    }
                }

                if (count($line_warnings)) {
                    $warnings[] = BimpTools::getMsgFromArray($line_warnings, 'Erreurs lors de la mise à jour ' . $line->getLabel('of_the'));
                }
            }
        }

        // remplacements supplémentaires: 
        // Tables: contratdet?

        BimpTools::changeBimpObjectId($id_merged_product, $this->id, 'bimpcore', 'Bimp_Product');
        BimpTools::changeDolObjectId($id_merged_product, $this->id, 'product');

        // Suppression du produit: 
        $prod_ref = $merged_product->getRef();

        $del_warnings = array();
        $del_errors = $merged_product->delete($del_warnings, true);

        if (count($del_errors)) {
            $errors[] = BimpTools::getMsgFromArray($del_errors, 'Echec de la suppression du produit "' . $prod_ref . '"');
        }
        if (count($del_warnings)) {
            $errors[] = BimpTools::getMsgFromArray($del_warnings, 'Erreurs lors de la suppression du produit "' . $prod_ref . '"');
        }

        return $errors;
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

    public function actionValidate($data = array(), &$success = '')
    {
        $errors = $this->validateProduct();
        return $errors;
    }

    public function actionMerge($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Fusion effectuée avec succès';
        $success_callback = '';

        $id_merged_product = (int) (isset($data['id_merged_product']) ? $data['id_merged_product'] : 0);
        $id_kept_product = (int) (isset($data['id_kept_product']) ? $data['id_kept_product'] : 0);

        if (!$id_merged_product) {
            $errors[] = 'Aucun produit à fusionner sélectionné';
        } elseif (!$id_kept_product) {
            $errors[] = 'Information manquante: produit à conserver';
        } elseif ($id_merged_product === (int) $this->id) {
            $errors[] = 'Un produit ne peut pas être fusionné avec lui-même';
        } elseif ($id_kept_product !== (int) $this->id && $id_kept_product !== (int) $id_merged_product) {
            $errors[] = 'Erreur: produit à conserver invalide, ' . $id_kept_product . ', ' . $this->id;
        }

        if (!count($errors)) {
            if ($id_kept_product !== (int) $this->id) {
                $merged_product = $this;
                $product = BimpCache::getBimpObjectInstance($this->module, $this->object_name, (int) $id_merged_product);
                if (!BimpObject::objectLoaded($product)) {
                    $errors[] = 'Le produit d\'ID ' . $id_merged_product . ' n\'existe pas';
                }
                $success_callback = 'window.location = \'' . DOL_URL_ROOT . '/bimpcore/index.php?fc=product&id=' . $id_merged_product . '\';';
            } else {
                $merged_product = BimpCache::getBimpObjectInstance($this->module, $this->object_name, (int) $id_merged_product);
                if (!BimpObject::objectLoaded($merged_product)) {
                    $errors[] = 'Le produit d\'ID ' . $id_merged_product . ' n\'existe pas';
                }
                $product = $this;
            }

            if (!count($errors)) {
                $errors = $product->mergeProduct($merged_product, $warnings);
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

    public function insertExtraFields()
    {
        return array();
    }

    public function updateExtraFields()
    {
        return array();
    }

    public function fetchExtraFields()
    {
        $extras = array();
        $extras['best_buy_price'] = $this->getBestBuyPrice();
        $extras['product_categories'] = $this->getCategories();
//        $extras['fk_country'] = $this->getOriginCountry();
        return $extras;
    }

    public function deleteExtraFields()
    {
        return array();
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
    
//    public function setCategories() {
//        global $conf;
////        if ($conf->categorie->enabled) {
//            require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
//            $form = new Form($this->db->db);
//            $cate_arbo = $form->select_all_categories(Categorie::TYPE_PRODUCT, '', 'parent', 64, 0, 1);
//            return $form->multiselectarray('categories', $cate_arbo, GETPOST('categories', 'array'), '', 0, '', 0, '100%');
//            
////				$cate_arbo = $form->select_all_categories(Categorie::TYPE_PRODUCT, '', 'parent', 64, 0, 1);
////				$c = new Categorie($this->db->db);
////				$cats = $c->containing($this->getData('id'),Categorie::TYPE_PRODUCT);
////				$arrayselected=array();
////				foreach($cats as $cat) {
////					$arrayselected[] = $cat->id;
////				}
////				$html .= $form->multiselectarray('categories', $cate_arbo, $arrayselected, '', 0, '', 0, '100%');
//
////        } TODO set a else
//    }
    
    public function getCategories() {
        global $conf;
        if ($conf->categorie->enabled) {
            require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
            $form = new Form($this->db->db);
            return $form->showCategories($this->getData('id'),'product',1);
        } else {
            return "L'utilisation de catégorie est inactive";
        }
    }
    
    public function setOriginCountry() {
        $form = new Form($this->db->db);
        return $form->select_country($this->getData('fk_country '),'country_id');
    }
    
    public function getOriginCountry() {
        global $langs;
        echo $this->getData('fk_county');
        $return = getCountry($this->getData('fk_county'), 0, $this->db->db);
        echo 'après la fonction' . $return.'<br/>';
        die();
        return 'test'.$return.'fin';
    }
    
    public function renderHeaderExtraLeft() {
        $html = '';
        $barcode = $this->getData('barcode');
        if(isset($barcode) and (strlen($barcode) == 12 or strlen($barcode) == 13)) {
            $html .= '<img src="';
            $html .= DOL_URL_ROOT . '/viewimage.php?modulepart=barcode&amp;generator=phpbarcode&amp;';
            $html .= 'code=' . $barcode . '&amp;encoding=EAN13">';
        }
        return $html;
    }
    
    public function displayCountry() {
        global $langs;
        $id = $this->getData('fk_country');
        if (!is_null($id) && $id) {
            return $langs->trans('Country' . $this->db->getValue('c_country', 'code', '`rowid` = ' . (int) $id));
        }
        return '';
    }
    
}
