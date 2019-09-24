<?php

ini_set('max_execution_time', 6000);

//ini_set('memory_limit','512M');

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
    public static $units_weight = array();
    public static $units_length = array();
    public static $units_surface = array();
    public static $units_volume = array();
    private static $stockDate = array();
    private static $stockShowRoom = array();
    private static $ventes = array();
    private static $lienShowRoomEntrepot = array();

    public function __construct($module, $object_name)
    {
        self::initUnits();

        parent::__construct($module, $object_name);
    }

    public static function initUnits()
    {
        if (empty(self::$units_weight)) {
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
        }
    }

    // Droits user: 

    /*
     * Exeptionnelement les droit dans les isCre.. et isEdi... pour la creation des prod par les commerciaux
     */

    public function canCreate()
    {
        return 1;
    }

    public function canEdit()
    {
        return 1;
    }

    public function canViewStock()
    {
        global $user;
        if ($user->rights->bimpequipment->inventory->close)
            return 1;
        return 0;
    }

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
            case 'refuse':
            case 'merge':
                return $this->canValidate();
        }

        return parent::canSetAction($action);
    }

    public function canValidate()
    {
        global $user;
        if ($user->admin || $user->rights->bimpcommercial->validProd) {
            return 1;
        }
        return 0;
    }

    public function iAmAdminRedirect()
    {
        global $user;
        if ($user->rights->bimpcommercial->validProd)
            return 1;

        return parent::iAmAdminRedirect();
    }

    // Getters booléens

    public function isCreatable($force_create = false, &$errors = array())
    {
        return $this->isEditable($force_create, $errors);
    }

    public function isEditable($force_edit = false, &$errors = array())
    {
        global $user;
        if ($force_edit || $user->rights->admin or $user->rights->produit->creer)
            return 1;
    }

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
                if ((int) $this->getData('tosell') != 1) {
                    $errors[] = "Ce produit n'est pas en vente.";
                    return 0;
                }
                return 1;
            case 'merge':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }
                return 1;
            case 'refuse':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }
                if ((int) $this->getData('validate') == 1) {
                    return 0;
                }
                if ((int) $this->getData('tosell') == 0 and (int) $this->getData('tobuy') == 0) {
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

    public function isVentesInit($dateMin = null, $dateMax = null)
    {
        if (is_null($dateMin)) {
            $dateMin = '0000-00-00 00:00:00';
        }
        if (preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $dateMin)) {
            $dateMin .= ' 00:00:00';
        }

        if (is_null($dateMax)) {
            $dateMax = date('Y-m-d') . ' 23:59:59';
        }

        return (int) isset(static::$ventes[$dateMin . '-' . $dateMax]);
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

        if ($this->isActionAllowed('validate') && $this->canSetAction('validate')) {
            $buttons[] = array(
                'label'   => 'Valider',
                'icon'    => 'fas_check-circle',
                'onclick' => $this->getJsActionOnclick('validate', array(), array(
                ))
            );
        }

        if ($this->isActionAllowed('merge') && $this->canSetAction('merge')) {
            $buttons[] = array(
                'label'   => 'Fusionner',
                'icon'    => 'fas_object-group',
                'onclick' => $this->getJsActionOnclick('merge', array(), array(
                    'form_name' => 'merge'
                ))
            );
        }

//        if ($this->isActionAllowed('refuse') && $this->canSetAction('refuse')) {
//            $buttons[] = array(
//                'label'   => 'Refuser',
//                'icon'    => 'close',
//                'onclick' => $this->getJsActionOnclick('refuse', array(), array(
//                ))
//            );
//        }
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

        if (is_null($dateMin)) {
            $dateMin = '0000-00-00 00:00:00';
        }

        if (preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $dateMin)) {
            $dateMin .= ' 00:00:00';
        }

        if (is_null($dateMax)) {
            $dateMax = date('Y-m-d H:i:s');
        }

        $cache_key = $dateMin . '-' . $dateMax;

        if ((int) $id_product) {
            if (!isset(self::$ventes[$cache_key])) {
                self::initVentes($dateMin, $dateMax);
            }

            if (isset(self::$ventes[$cache_key][$id_product][$id_entrepot])) {
                return self::$ventes[$cache_key][$id_product][$id_entrepot];
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
        $propals = array();
        if ($this->isLoaded()) {
            require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';

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
        }

        return $propals;
    }

    public function getVentesCaisse()
    {
        $ventes = array();

        if ($this->isLoaded()) {
            $rows = $this->db->getRows('bc_vente_article', '`id_product` = ' . (int) $this->id, null, 'array', array('id_vente'));
            if (is_array($rows)) {
                foreach ($rows as $r) {
                    if (!in_array((int) $r['id_vente'], $ventes)) {
                        $ventes[] = (int) $r['id_vente'];
                    }
                }
            }
        }

        return $ventes;
    }

    public function getCategories($edit = 0)
    {

        global $conf;
        if ($conf->categorie->enabled) {
            require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
            if ($edit == 1) {
                $form = new Form($this->db->db);
                $cate_arbo = $form->select_all_categories(Categorie::TYPE_PRODUCT, '', 'parent', 64, 0, 1);
                return $form->multiselectarray('categories', $cate_arbo, GETPOST('categories', 'array'), '', 0, '', 0, '100%');
            } else {
                $form = new Form($this->db->db);
                return $form->showCategories($this->getData('id'), 'product', 1);
            }
        } else {
            return "L'utilisation de catégorie est inactive";
        }
    }

    public function getNbScanned()
    {
        global $cache_scann;

        $id_inventory = BimpTools::getValue('id');

        if (!isset($cache_scann[$id_inventory])) {
            $sql = 'SELECT SUM(qty) as qty, fk_product';
            $sql .= ' FROM ' . MAIN_DB_PREFIX . 'bl_inventory_det';
            $sql .= ' WHERE fk_inventory=' . $id_inventory;
            $sql .= ' GROUP BY fk_product';
            $result = $this->db->db->query($sql);
            if ($result) {
                while ($obj = $this->db->db->fetch_object($result)) {
                    $cache_scann[$id_inventory][$obj->fk_product] = $obj->qty;
                }
            }
        }
        if (isset($cache_scann[$id_inventory][$this->getData('id')]))
            return $cache_scann[$id_inventory][$this->getData('id')];
        else
            return 0;
    }

    public function getLastMouvement()
    {
        $html = '';
        // Last movement
        $sql = "SELECT MAX(datem) as datem";
        $sql .= " FROM " . MAIN_DB_PREFIX . "stock_mouvement";
        $sql .= " WHERE fk_product=" . $this->getData('id');
        $result = $this->db->db->query($sql);
        if ($result) {
            $obj = $this->db->db->fetch_object($result);
            $html .= $obj->datem;
        }

        $html .= ' (<a href="' . DOL_URL_ROOT . '/product/stock/mouvement.php?idproduct=' .
                $this->getData('id') . '">Liste complète</a>)';
        return $html;
    }

    // Getters stocks: 

    public function getStocksForEntrepot($id_entrepot, $type = 'virtuel') // $type : 'reel' / 'dispo' / 'virtuel'
    {
        $stocks = array(
            'id_stock'       => 0,
            'reel'           => 0,
            'dispo'          => 0, // Stock réel - réel réservés
            'commandes'      => 0, // qté en commande fournisseur
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

            if (in_array($type, array('dispo', 'virtuel'))) {
                BimpObject::loadClass('bimpreservation', 'BR_Reservation');

                $reserved = BR_Reservation::getProductCounts($this->id, (int) $id_entrepot);
                $stocks['total_reserves'] = $reserved['total'];
                $stocks['reel_reserves'] = $reserved['reel'];

                $stocks['dispo'] = $stocks['reel'] - $stocks['reel_reserves'];

                if (in_array($type, array('virtuel'))) {
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

                    $stocks['virtuel'] = $stocks['reel'] - $stocks['total_reserves'] + $stocks['commandes'];
                }
            }
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

    public static function getStockIconStatic($id_product, $id_entrepot = null, $serialisable = false)
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

        $html = '<span class="objectIcon displayProductStocksBtn' . ($serialisable ? ' green' : '') . '" title="Stocks" data-id_product="' . $id_product . '" data-id_entrepot="' . (int) $id_entrepot . '">';
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
            if ((float) $this->getData('cur_pa_ht')) {
                $pa_ht = (float) $this->getData('cur_pa_ht');
            } elseif ((float) $this->getData('pmp')) {
                $pa_ht = (float) $this->getData('pmp');
            } else {
                $pa_ht = (float) $this->getCurrentFournPriceAmount($id_fourn, $with_default);
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

    public function renderBestBuyPrice()
    {
        $sql = 'SELECT price FROM `' . MAIN_DB_PREFIX . 'product_fournisseur_price`';
        $sql .= ' WHERE fk_product=' . $this->getData('id');
        $sql .= ' GROUP BY fk_product';
        $sql .= ' HAVING(MIN(PRICE))';
        $rows = $this->db->executeS($sql);

        if (!empty($rows)) {
            return number_format($rows[0]->price, 2) . ' €';
        }
        return 00.00 . ' €';
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

    public function displayCategories()
    {
        // todo
        return '';
    }

    public function displayRefShort()
    {
        $ref = $this->getRef();

        if (preg_match('/^[A-Za-z0-9]{3,4}\-(.*)$/', $ref, $matches)) {
            return $matches[1];
        }

        return $ref;
    }

    public function displayStockInventory()
    {
        $id_inventory = BimpTools::getValue('id');
        $inventory = BimpCache::getBimpObjectInstance('bimplogistique', 'Inventory', $id_inventory);
        $stock = $this->getStocksForEntrepot($inventory->getData('fk_warehouse'));
        return $stock['reel'];
    }

    // Rendus HTML: 

    public function renderHeaderExtraLeft()
    {
        $html = '';
        $barcode = $this->getData('barcode');
        if (isset($barcode) and ( strlen($barcode) == 12 or strlen($barcode) == 13)) {
            $html .= '<img src="';
            $html .= DOL_URL_ROOT . '/viewimage.php?modulepart=barcode&amp;generator=phpbarcode&amp;';
            $html .= 'code=' . $barcode . '&amp;encoding=EAN13">';
        }
        return $html;
    }

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
            $html = BimpRender::renderPanel('Catégories hors module ', '', '', array(
                        'foldable'    => true,
                        'type'        => 'secondary',
                        'panel_id'    => 'annex',
                        'panel_class' => 'categ_container'
            ));

            $html .= BimpRender::renderPanel('Catégorisation', '', '', array(
                        'foldable'    => true,
                        'type'        => 'secondary',
                        'panel_id'    => 'categorization',
                        'panel_class' => 'categ_container'
            ));

            $html .= BimpRender::renderPanel('Choix', '', '', array(
                        'foldable'    => true,
                        'type'        => 'secondary',
                        'panel_id'    => 'choice',
                        'panel_class' => 'categ_container'
            ));
        }
        return $html;
    }

    public function renderHistory()
    {
        $html = '';

        if (!$this->isLoaded())
            return $html;

        $body .= '<table id="history_table" class="noborder objectlistTable">';
        // Thead
        $body .= '<thead>';
        $body .= '<th>Objets référents</th>';
        $body .= '<th>Nombre de tiers</th>';
        $body .= '<th>Nombre d\'objets référent</th>';
        $body .= '<th>Quantité totale</th>';
        $body .= '<thead/>';


        $stats_propale = $this->load_stats_propale();
        $stats_prop_supplier = $this->load_stats_proposal_supplier();
        $stats_command = $this->load_stats_commande();
        $stats_comm_fourn = $this->load_stats_commande_fournisseur();
        $stats_facture = $this->load_stats_facture();
        $stats_fact_fourn = $this->load_stats_facture_fournisseur();
        $stats_contrat = $this->load_stats_contrat();

        $stats = array($stats_propale, $stats_prop_supplier, $stats_command,
            $stats_comm_fourn, $stats_facture,
            $stats_fact_fourn, $stats_contrat);

        $body .= '<tbody>';
        foreach ($stats as $s) {
            // Tbody
            $body .= '<tr style="height: 45px;">';
            $body .= '<td id="' . $s['id'] . '" style="cursor: pointer;"';
            $body .= ' name="display_details">';
            $body .= '<a><strong>' . $s['name'] . '</strong></a></td>';
            $body .= '<td><strong>' . $s['nb_object'] . '</strong></td>';
            $body .= '<td><strong>' . $s['nb_ref'] . '</strong></td>';
            $body .= '<td><strong>' . $s['qty'] . '</strong></td>';
            $body .= '</tr>';
        }
        $body .= '</tbody>';
        $body .= '</table>';

        $html .= BimpRender::renderPanel('Historique', $body, '', array(
                    'foldable' => false,
                    'type'     => 'secondary'
        ));

        $html .= '<p>Cliquez sur un lien de la colonne Objets référents pour obtenir une vue détaillée... </p>';

        $html .= '<div id="selected_object"></div>';

        return $html;
    }

    public function renderStatusRefuse()
    {
        if (!$this->getData('tobuy') and ! $this->getData('tobuy')) {
            $color = 'danger';
            $text = 'OUI';
        } else {
            $color = 'success';
            $text = 'NON';
        }
        $html = '';
        $html .= '<div class="inputContainer validate_inputContainer " data-field_name="validate" data-initial_value="1" ';
        $html .= 'data-multiple="0" data-field_prefix="" data-required="0">';
        $html .= '<input type="hidden" name="validate" value="1"><span class="' . $color . '">' . $text . '</span></div>';
        return $html;
    }

    public function renderValidationDuration()
    {
        $date_ask_valid = new DateTime($this->getData('date_ask_valid'));
        $date_valid = new DateTime($this->getData('date_valid'));

        $diff = $date_ask_valid->diff($date_valid);

        $nb_jour = (int) $diff->format('%D');
        $html = '<strong>';
        if ($nb_jour > 1)
            $html .= $diff->format('%D jours et %H:%I');
        else
            $html .= $diff->format('%D jours et %H:%I');
        $html .= '</strong>';
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

    public function renderCountry()
    {
        // Devrait s'appeller displayCountry (display = afficher une donnée, render = générer un bloc HTML => Important pour s'y retrouver rapidement) 

        global $langs;
        $id = $this->getData('fk_country');
        if (!is_null($id) && $id) {
            return $langs->trans('Country' . $this->db->getValue('c_country', 'code', '`rowid` = ' . (int) $id));
        }
        return '';
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
        global $user;

        $errors = array();
        if ($this->getData("fk_product_type") == 0 && !(int) $this->getCurrentFournPriceId(null, true)) {
            $errors[] = "Veuillez enregistrer au moins un prix d'achat fournisseur";
        }

        if ((int) $this->getData('fk_product_type') == 1 and
                (int) $this->getData('serialisable') == 1)
            $errors[] = "Un service ne peut pas être sérialisé.";

        if ((int) $this->getData('tosell') != 1)
            $errors[] = "Ce produit n'est pas disponible à la vente";

        if (sizeof($errors) > 0)
            return $errors;

        $cur_pa_ht = $this->getCurrentPaHt(null, true);
        $datetime = new DateTime();

        $this->updateField('fk_user_valid', (int) $user->id);
        $this->updateField('date_valid', $datetime->format('Y-m-d H:i:s'));
        $this->updateField('cur_pa_ht', $cur_pa_ht);
        $this->updateField('validate', 1);


        // COMMAND
        $commandes_c = $this->getCommandes();
        foreach ($commandes_c as $commande) {
            if((int) $commande->statut != (int) Commande::STATUS_DRAFT)
                continue;
            
            $email_sent = false;
            $list_contact = $commande->liste_contact(-1, 'internal');

            // Search responsible
            foreach ($list_contact as $contact) {
                if ($contact['code'] == 'SALESREPFOLL' and ! $email_sent) {
                    $errors = array_merge($errors, $this->sendEmailCommandeValid($commande, $contact['email']));
                    $email_sent = true;
                    break;
                }
            }

            // Search signatory
            if (!$email_sent) {
                foreach ($list_contact as $contact) {
                    $errors = array_merge($errors, $this->sendEmailCommandeValid($commande, $contact['email']));
                    $email_sent = true;
                    break;
                }
            }

            // Use main commercial Franck PINERI
            if (!$email_sent) {
                require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
                $user = new User($this->db->db);
                $user->fetch((int) 62);
                $errors = array_merge($errors, $this->sendEmailCommandeValid($commande, $user->email));
                $email_sent = true;
                continue;
            }
        }

        // PROPALS
        $propals = $this->getPropals();
        foreach ($propals as $propal) {
            if((int) $propal->statut != (int) Propal::STATUS_DRAFT)
                continue;
            
            $email_sent = false;
            $list_contact = $propal->liste_contact(-1, 'internal');

            // Search responsible
            foreach ($list_contact as $contact) {
                if ($contact['code'] == 'SALESREPFOLL' and ! $email_sent) {
                    $errors = array_merge($errors, $this->sendEmailPropalValid($propal, $contact['email']));
                    $email_sent = true;
                    break;
                }
            }

            // Search signatory
            if (!$email_sent) {
                foreach ($list_contact as $contact) {
                    $errors = array_merge($errors, $this->sendEmailPropalValid($propal, $contact['email']));
                    $email_sent = true;
                    break;
                }
            }

            // Use main commercial Franck PINERI
            if (!$email_sent) {
                require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
                $user = new User($this->db->db);
                $user->fetch((int) 62);
                $errors = array_merge($errors, $this->sendEmailCommandeValid($commande, $user->email));
                $email_sent = true;
                continue;
            }
        }

        // Ventes en caisse: 
        $ventes = $this->getVentesCaisse();

        foreach ($ventes as $id_vente) {
            $vente = BimpCache::getBimpObjectInstance('bimpcaisse', 'BC_Vente', (int) $id_vente);
            if((int) $vente->getData('status') != BC_Vente::BC_VENTE_BROUILLON)
                continue;
            
            if (BimpObject::objectLoaded($vente)) {
                $user = new User($this->db->db);
                $user->fetch((int) $vente->getData('id_user_resp'));

                if (BimpObject::objectLoaded($user)) {
                    $errors = array_merge($errors, $this->sendEmailVenteCaisseValid($vente, $user->email));
                }
            }
        }

        return $errors;
    }

    public function refuseProduct()
    {
        global $user;

        $errors = array();

        // test si il y a des ventes ?
        // test un service peut-il être refusé ?

        if ($this->getData('valid') == 1)
            $errors[] = "Le produit est validé, il ne peut pas être refusé";


        if (sizeof($errors) > 0)
            return $errors;


//        $this->updateField('fk_user_valid', (int) $user->id);
//        $this->updateField('date_valid', $datetime->format('Y-m-d H:i:s'));
        $this->updateField('tosell', 0);
        $this->updateField('tobuy', 0);


        // COMMAND
        $commandes_c = $this->getCommandes();
        foreach ($commandes_c as $commande) {
            $email_sent = false;
            $list_contact = $commande->liste_contact(-1, 'internal');

            // Search responsible
            foreach ($list_contact as $contact) {
                if ($contact['code'] == 'SALESREPFOLL' and ! $email_sent) {
                    $errors = array_merge($errors, $this->sendEmailCommandeRefuse($commande, $contact['email']));
                    $email_sent = true;
                    break;
                }
            }

            // Search signatory
            if (!$email_sent) {
                foreach ($list_contact as $contact) {
                    $errors = array_merge($errors, $this->sendEmailCommandeRefuse($commande, $contact['email']));
                    $email_sent = true;
                    break;
                }
            }

            // Use main commercial Franck PINERI
            if (!$email_sent) {
                require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
                $user = new User($this->db->db);
                $user->fetch((int) 62);
                $errors = array_merge($errors, $this->sendEmailCommandeRefuse($commande, $user->email));
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
                    $errors = array_merge($errors, $this->sendEmailPropalRefuse($propal, $contact['email']));
                    $email_sent = true;
                    break;
                }
            }

            // Search signatory
            if (!$email_sent) {
                foreach ($list_contact as $contact) {
                    $errors = array_merge($errors, $this->sendEmailPropalRefuse($propal, $contact['email']));
                    $email_sent = true;
                    break;
                }
            }

            // Use main commercial Franck PINERI
            if (!$email_sent) {
                require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
                $user = new User($this->db->db);
                $user->fetch((int) 62);
                $errors = array_merge($errors, $this->sendEmailPropalRefuse($commande, $user->email));
                $email_sent = true;
                continue;
            }
        }

        // Ventes en caisse: 
        $ventes = $this->getVentesCaisse();

        foreach ($ventes as $id_vente) {
            $vente = BimpCache::getBimpObjectInstance('bimpcaisse', 'BC_Vente', (int) $id_vente);
            if (BimpObject::objectLoaded($vente)) {
                $user = new User($this->db->db);
                $user->fetch((int) $vente->getData('id_user_resp'));

                if (BimpObject::objectLoaded($user)) {
                    $errors = array_merge($errors, $this->sendEmailVenteCaisseRefuse($vente, $user->email));
                }
            }
        }

        return $errors;
    }

    private function sendEmailCommandeValid($commande, $to)
    {
        $errors = array();
        $subject = 'Produit validé pour la commande ' . $commande->ref;
        $from = 'gle@bimp.fr';
        $msg = 'Bonjour,<br/>Le produit ' . $this->getData('ref') . ' a été validé, la commande ' . $commande->getNomUrl();
        $msg .= ' est peut-être validable.';
        if (!mailSyn2($subject, $to, $from, $msg))
            $errors[] = "Envoi email vers " . $to . " pour la commande " . $commande->getNomUrl() . " impossible.";
        return $errors;
    }

    private function sendEmailPropalValid($propal, $to)
    {
        $errors = array();
        $subject = 'Produit validé pour la propale ' . $propal->ref;
        $from = 'gle@bimp.fr';
        $msg = 'Bonjour,<br/>Le produit ' . $this->getData('ref') . ' a été validé, la propale ' . $propal->getNomUrl();
        $msg .= ' est peut-être validable.';
        if (!mailSyn2($subject, $to, $from, $msg))
            $errors[] = "Envoi email vers " . $to . " pour la propale " . $propal->getNomUrl() . " impossible.";
        return $errors;
    }

    private function sendEmailCommandeRefuse($commande, $to)
    {
        $errors = array();
        $subject = 'Produit refusé pour la commande ' . $commande->ref;
        $from = 'gle@bimp.fr';
        $msg = 'Bonjour,<br/>Le produit ' . $this->getData('ref') . ' a été refusé, la commande ' . $commande->getNomUrl();
        $msg .= ' doit être modifiée.';
        if (!mailSyn2($subject, $to, $from, $msg))
            $errors[] = "Envoi email vers " . $to . " pour la commande " . $commande->getNomUrl() . " impossible.";
        return $errors;
    }

    private function sendEmailVenteCaisseValid($vente, $to)
    {
        $errors = array();
        $subject = 'Produit validé pour la vente #' . $vente->id;
        $from = 'gle@bimp.fr';
        $msg = 'Bonjour,<br/>Le produit ' . $this->getData('ref') . ' a été validé, la vente #' . $vente->id;
        $msg .= ' peut être validée.';
        if (!mailSyn2($subject, $to, $from, $msg))
            $errors[] = "Echec envoi email à " . $to . " pour la vente #" . $vente->id;
        return $errors;
    }

    private function sendEmailVenteCaisseRefuse($vente, $to)
    {
        $errors = array();
        $subject = 'Produit refusé pour la vente #' . $vente->id;
        $from = 'gle@bimp.fr';
        $msg = 'Bonjour,<br/>Le produit ' . $this->getData('ref') . ' a été refusé.';
        $msg .= ' Veuillez retirer ce produit de cette vente.';
        if (!mailSyn2($subject, $to, $from, $msg))
            $errors[] = "Echec envoi email à " . $to . " pour la vente #" . $vente->id;
        return $errors;
    }

    private function sendEmailPropalRefuse($propal, $to)
    {
        $errors = array();
        $subject = 'Produit refusé pour la propale ' . $propal->ref;
        $from = 'gle@bimp.fr';
        $msg = 'Bonjour,<br/>Le produit ' . $this->getData('ref') . ' a été refusé, la propale ' . $propal->getNomUrl();
        $msg .= ' doit être modifiée.';
        if (!mailSyn2($subject, $to, $from, $msg))
            $errors[] = "Envoi email vers " . $to . " pour la propale " . $propal->getNomUrl() . " impossible.";
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

    // Stats

    function load_stats_propale($socid = 0)
    {
        global $user;

        $sql = "SELECT COUNT(DISTINCT p.fk_soc) as nb_customers, COUNT(DISTINCT p.rowid) as nb,";
        $sql .= " COUNT(pd.rowid) as nb_rows, SUM(pd.qty) as qty";
        $sql .= " FROM " . MAIN_DB_PREFIX . "propaldet as pd";
        $sql .= ", " . MAIN_DB_PREFIX . "propal as p";
        $sql .= ", " . MAIN_DB_PREFIX . "societe as s";
        if (!$user->rights->societe->client->voir && !$socid)
            $sql .= ", " . MAIN_DB_PREFIX . "societe_commerciaux as sc";
        $sql .= " WHERE p.rowid = pd.fk_propal";
        $sql .= " AND p.fk_soc = s.rowid";
        $sql .= " AND p.entity IN (" . getEntity('propal') . ")";
        $sql .= " AND pd.fk_product = " . $this->id;
        if (!$user->rights->societe->client->voir && !$socid)
            $sql .= " AND p.fk_soc = sc.fk_soc AND sc.fk_user = " . $user->id;
        //$sql.= " AND pr.fk_statut != 0";
        if ($socid > 0)
            $sql .= " AND p.fk_soc = " . $socid;

        $result = $this->db->db->query($sql);
        if ($result) {
            $obj = $this->db->db->fetch_object($result);
            $stats = array(
                'id'        => 'Bimp_Propal',
                'name'      => 'Propositions commerciales',
                'nb_object' => $obj->nb_customers,
                'nb_ref'    => $obj->nb,
                'qty'       => $obj->qty ? $obj->qty : 0
            );
            return $stats;
        } else {
            $this->error = $this->db->db->error();
            return -1;
        }
    }

    function load_stats_proposal_supplier($socid = 0)
    {
        global $user;

        $sql = "SELECT COUNT(DISTINCT p.fk_soc) as nb_suppliers, COUNT(DISTINCT p.rowid) as nb,";
        $sql .= " COUNT(pd.rowid) as nb_rows, SUM(pd.qty) as qty";
        $sql .= " FROM " . MAIN_DB_PREFIX . "supplier_proposaldet as pd";
        $sql .= ", " . MAIN_DB_PREFIX . "supplier_proposal as p";
        $sql .= ", " . MAIN_DB_PREFIX . "societe as s";
        if (!$user->rights->societe->client->voir && !$socid)
            $sql .= ", " . MAIN_DB_PREFIX . "societe_commerciaux as sc";
        $sql .= " WHERE p.rowid = pd.fk_supplier_proposal";
        $sql .= " AND p.fk_soc = s.rowid";
        $sql .= " AND p.entity IN (" . getEntity('supplier_proposal') . ")";
        $sql .= " AND pd.fk_product = " . $this->id;
        if (!$user->rights->societe->client->voir && !$socid)
            $sql .= " AND p.fk_soc = sc.fk_soc AND sc.fk_user = " . $user->id;
        //$sql.= " AND pr.fk_statut != 0";
        if ($socid > 0)
            $sql .= " AND p.fk_soc = " . $socid;

        $result = $this->db->db->query($sql);
        if ($result) {
            $obj = $this->db->db->fetch_object($result);
            $stats = array(
                'id'        => 'Bimp_PropalFourn',
                'name'      => 'Propositions commerciales fournisseurs',
                'nb_object' => $obj->nb_suppliers,
                'nb_ref'    => $obj->nb,
                'qty'       => $obj->qty ? $obj->qty : 0
            );
            return $stats;
        } else {
            $this->error = $this->db->db->error();
            return -1;
        }
    }

    function load_stats_commande($socid = 0, $filtrestatut = '', $forVirtualStock = 0)
    {
        global $user;

        $sql = "SELECT COUNT(DISTINCT c.fk_soc) as nb_customers, COUNT(DISTINCT c.rowid) as nb,";
        $sql .= " COUNT(cd.rowid) as nb_rows, SUM(cd.qty) as qty";
        $sql .= " FROM " . MAIN_DB_PREFIX . "commandedet as cd";
        $sql .= ", " . MAIN_DB_PREFIX . "commande as c";
        $sql .= ", " . MAIN_DB_PREFIX . "societe as s";
        if (!$user->rights->societe->client->voir && !$socid && !$forVirtualStock)
            $sql .= ", " . MAIN_DB_PREFIX . "societe_commerciaux as sc";
        $sql .= " WHERE c.rowid = cd.fk_commande";
        $sql .= " AND c.fk_soc = s.rowid";
        $sql .= " AND c.entity IN (" . getEntity('commande') . ")";
        $sql .= " AND cd.fk_product = " . $this->id;
        if (!$user->rights->societe->client->voir && !$socid && !$forVirtualStock)
            $sql .= " AND c.fk_soc = sc.fk_soc AND sc.fk_user = " . $user->id;
        if ($socid > 0)
            $sql .= " AND c.fk_soc = " . $socid;
        if ($filtrestatut <> '')
            $sql .= " AND c.fk_statut in (" . $filtrestatut . ")";

        $result = $this->db->db->query($sql);
        if ($result) {
            $obj = $this->db->db->fetch_object($result);

            $stats = array(
                'id'        => 'Bimp_Commande',
                'name'      => 'Commandes clients',
                'nb_object' => $obj->nb_customers,
                'nb_ref'    => $obj->nb,
                'qty'       => $obj->qty ? $obj->qty : 0
            );

//                    // if it's a virtual product, maybe it is in order by extension
//                    if (! empty($conf->global->ORDER_ADD_ORDERS_WITH_PARENT_PROD_IF_INCDEC))
//                    {
//                            $TFather = $this->getFather();
//                            if (is_array($TFather) && !empty($TFather)) {
//                                    foreach($TFather as &$fatherData) {
//                                            $pFather = new Product($this->db->db);
//                                            $pFather->id = $fatherData['id'];
//                                            $qtyCoef = $fatherData['qty'];
//
//                                            if ($fatherData['incdec']) {
//                                                    $pFather->load_stats_commande($socid, $filtrestatut);
//
//                                                    $stats['nb_object']+=$pFather->stats_commande['nb_object'];
//                                                    $stats['nb_ref']+=$pFather->stats_commande['nb'];
//                                                    $stats['qty']+=$pFather->stats_commande['qty'] * $qtyCoef;
//
//                                            }
//                                    }
//                            }
//                    }
//
//                    // If stock decrease is on invoice validation, the theorical stock continue to
//                    // count the orders to ship in theorical stock when some are already removed b invoice validation.
//                    // If option DECREASE_ONLY_UNINVOICEDPRODUCTS is on, we make a compensation.
//                    if (! empty($conf->global->STOCK_CALCULATE_ON_BILL))
//                    {
//                            if (! empty($conf->global->DECREASE_ONLY_UNINVOICEDPRODUCTS))
//                            {
//                                    $adeduire = 0;
//                                    $sql = "SELECT sum(fd.qty) as count FROM ".MAIN_DB_PREFIX."facturedet fd ";
//                                    $sql .= " JOIN ".MAIN_DB_PREFIX."facture f ON fd.fk_facture = f.rowid ";
//                                    $sql .= " JOIN ".MAIN_DB_PREFIX."element_element el ON el.fk_target = f.rowid and el.targettype = 'facture' and sourcetype = 'commande'";
//                                    $sql .= " JOIN ".MAIN_DB_PREFIX."commande c ON el.fk_source = c.rowid ";
//                                    $sql .= " WHERE c.fk_statut IN (".$filtrestatut.") AND c.facture = 0 AND fd.fk_product = ".$this->id;
//                                    dol_syslog(__METHOD__.":: sql $sql", LOG_NOTICE);
//
//                                    $resql = $this->db->db->query($sql);
//                                    if ( $resql )
//                                    {
//                                            if ($this->db->db->num_rows($resql) > 0)
//                                            {
//                                                    $obj = $this->db->db->fetch_object($resql);
//                                                    $adeduire += $obj->count;
//                                            }
//                                    }
//
//                                    $stats['qty'] -= $adeduire;
//                            }
//                    }

            return $stats;
        } else {
            $this->error = $this->db->db->error();
            return -1;
        }
    }

    function load_stats_commande_fournisseur($socid = 0, $filtrestatut = '', $forVirtualStock = 0)
    {
        global $user;

        $sql = "SELECT COUNT(DISTINCT c.fk_soc) as nb_suppliers, COUNT(DISTINCT c.rowid) as nb,";
        $sql .= " COUNT(cd.rowid) as nb_rows, SUM(cd.qty) as qty";
        $sql .= " FROM " . MAIN_DB_PREFIX . "commande_fournisseurdet as cd";
        $sql .= ", " . MAIN_DB_PREFIX . "commande_fournisseur as c";
        $sql .= ", " . MAIN_DB_PREFIX . "societe as s";
        if (!$user->rights->societe->client->voir && !$socid && !$forVirtualStock)
            $sql .= ", " . MAIN_DB_PREFIX . "societe_commerciaux as sc";
        $sql .= " WHERE c.rowid = cd.fk_commande";
        $sql .= " AND c.fk_soc = s.rowid";
        $sql .= " AND c.entity IN (" . getEntity('supplier_order') . ")";
        $sql .= " AND cd.fk_product = " . $this->id;
        if (!$user->rights->societe->client->voir && !$socid && !$forVirtualStock)
            $sql .= " AND c.fk_soc = sc.fk_soc AND sc.fk_user = " . $user->id;
        if ($socid > 0)
            $sql .= " AND c.fk_soc = " . $socid;
        if ($filtrestatut != '')
            $sql .= " AND c.fk_statut in (" . $filtrestatut . ")"; // Peut valoir 0

        $result = $this->db->db->query($sql);
        if ($result) {
            $obj = $this->db->db->fetch_object($result);
            $stats = array(
                'id'        => 'Bimp_CommandeFourn',
                'name'      => 'Commandes fournisseurs',
                'nb_object' => $obj->nb_suppliers,
                'nb_ref'    => $obj->nb,
                'qty'       => $obj->qty ? $obj->qty : 0
            );
            return $stats;
        } else {
            $this->error = $this->db->db->error() . ' sql=' . $sql;
            return -1;
        }
    }

    function load_stats_contrat($socid = 0)
    {
        global $user;

        $sql = "SELECT COUNT(DISTINCT c.fk_soc) as nb_customers, COUNT(DISTINCT c.rowid) as nb,";
        $sql .= " COUNT(cd.rowid) as nb_rows, SUM(cd.qty) as qty";
        $sql .= " FROM " . MAIN_DB_PREFIX . "contratdet as cd";
        $sql .= ", " . MAIN_DB_PREFIX . "contrat as c";
        $sql .= ", " . MAIN_DB_PREFIX . "societe as s";
        if (!$user->rights->societe->client->voir && !$socid)
            $sql .= ", " . MAIN_DB_PREFIX . "societe_commerciaux as sc";
        $sql .= " WHERE c.rowid = cd.fk_contrat";
        $sql .= " AND c.fk_soc = s.rowid";
        $sql .= " AND c.entity IN (" . getEntity('contract') . ")";
        $sql .= " AND cd.fk_product = " . $this->id;
        if (!$user->rights->societe->client->voir && !$socid)
            $sql .= " AND c.fk_soc = sc.fk_soc AND sc.fk_user = " . $user->id;
        //$sql.= " AND c.statut != 0";
        if ($socid > 0)
            $sql .= " AND c.fk_soc = " . $socid;

        $result = $this->db->db->query($sql);
        if ($result) {
            $obj = $this->db->db->fetch_object($result);
            $stats = array(
                'id'        => 'BContract_contrat',
                'name'      => 'Contrats',
                'nb_object' => $obj->nb_customers,
                'nb_ref'    => $obj->nb,
                'qty'       => $obj->qty ? $obj->qty : 0
            );
            return $stats;
        } else {
            $this->error = $this->db->db->error() . ' sql=' . $sql;
            return -1;
        }
    }

    function load_stats_facture($socid = 0)
    {
        global $user;

        $sql = "SELECT COUNT(DISTINCT f.fk_soc) as nb_customers, COUNT(DISTINCT f.rowid) as nb,";
        $sql .= " COUNT(fd.rowid) as nb_rows, SUM(fd.qty) as qty";
        $sql .= " FROM " . MAIN_DB_PREFIX . "facturedet as fd";
        $sql .= ", " . MAIN_DB_PREFIX . "facture as f";
        $sql .= ", " . MAIN_DB_PREFIX . "societe as s";
        if (!$user->rights->societe->client->voir && !$socid)
            $sql .= ", " . MAIN_DB_PREFIX . "societe_commerciaux as sc";
        $sql .= " WHERE f.rowid = fd.fk_facture";
        $sql .= " AND f.fk_soc = s.rowid";
        $sql .= " AND f.entity IN (" . getEntity('facture') . ")";
        $sql .= " AND fd.fk_product = " . $this->id;
        if (!$user->rights->societe->client->voir && !$socid)
            $sql .= " AND f.fk_soc = sc.fk_soc AND sc.fk_user = " . $user->id;
        //$sql.= " AND f.fk_statut != 0";
        if ($socid > 0)
            $sql .= " AND f.fk_soc = " . $socid;

        $result = $this->db->db->query($sql);
        if ($result) {
            $obj = $this->db->db->fetch_object($result);
            $stats = array(
                'id'        => 'Bimp_Facture',
                'name'      => 'Factures',
                'nb_object' => $obj->nb_customers,
                'nb_ref'    => $obj->nb,
                'qty'       => $obj->qty ? $obj->qty : 0
            );
            return $stats;
        } else {
            $this->error = $this->db->db->error();
            return -1;
        }
    }

    function load_stats_facture_fournisseur($socid = 0)
    {
        global $user;

        $sql = "SELECT COUNT(DISTINCT f.fk_soc) as nb_suppliers, COUNT(DISTINCT f.rowid) as nb,";
        $sql .= " COUNT(fd.rowid) as nb_rows, SUM(fd.qty) as qty";
        $sql .= " FROM " . MAIN_DB_PREFIX . "facture_fourn_det as fd";
        $sql .= ", " . MAIN_DB_PREFIX . "facture_fourn as f";
        $sql .= ", " . MAIN_DB_PREFIX . "societe as s";
        if (!$user->rights->societe->client->voir && !$socid)
            $sql .= ", " . MAIN_DB_PREFIX . "societe_commerciaux as sc";
        $sql .= " WHERE f.rowid = fd.fk_facture_fourn";
        $sql .= " AND f.fk_soc = s.rowid";
        $sql .= " AND f.entity IN (" . getEntity('facture_fourn') . ")";
        $sql .= " AND fd.fk_product = " . $this->id;
        if (!$user->rights->societe->client->voir && !$socid)
            $sql .= " AND f.fk_soc = sc.fk_soc AND sc.fk_user = " . $user->id;
        //$sql.= " AND f.fk_statut != 0";
        if ($socid > 0)
            $sql .= " AND f.fk_soc = " . $socid;

        $result = $this->db->db->query($sql);
        if ($result) {
            $obj = $this->db->db->fetch_object($result);
            $stats = array(
                'id'        => 'Bimp_FactureFourn',
                'name'      => 'Factures fournisseurs',
                'nb_object' => $obj->nb_suppliers,
                'nb_ref'    => $obj->nb,
                'qty'       => $obj->qty ? $obj->qty : 0
            );
            return $stats;
        } else {
            $this->error = $this->db->db->error();
            return -1;
        }
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

    public function actionPrintEtiquettes($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $success_callback = '';

        $ids = $data['id_objects'];

        if (!count($ids)) {
            $errors[] = 'ID des produits absent';
        } else {
            $type = isset($data['type']) ? (string) $data['type'] : '';

            if (!$type) {
                $errors[] = 'Type d\'étiquette à générer absent';
            } else {
                $qty = isset($data['qty']) ? (int) $data['qty'] : 1;

                $url = DOL_URL_ROOT . '/bimplogistique/etiquette_produit.php?id_products=' . implode(',', $ids) . '&qty=1&type=' . $type;

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

    public function actionRefuse($data = array(), &$success = '')
    {
        $errors = $this->refuseProduct();
        return $errors;
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
//        self::$ventes = array(); // Ne pas déco ça effacerait d'autres données en cache pour d'autres dates. 

        $query = "SELECT `fk_product`, entrepot, sum(qty) as qty, sum(l.total_ht) as total_ht, sum(l.total_ttc) as total_ttc";
        $query .= " FROM `llx_facturedet` l, llx_facture f, llx_facture_extrafields e";
        $query .= " WHERE `fk_facture` = f.rowid AND e.fk_object = f.rowid AND fk_product > 0";
        $query .= " AND f.fk_statut > 0";

        if ($dateMin)
            $query .= " AND date_valid >= '" . $dateMin . "'";

        if ($dateMax)
            $query .= " AND date_valid <= '" . $dateMax . "'";

        $group_by .= " GROUP BY `fk_product`, entrepot";

        $sql = $db->query($query . " AND `subprice` >= 0" . $group_by);

        $cache_key = $dateMin . "-" . $dateMax;

        while ($ln = $db->fetch_object($sql)) {
            self::$ventes[$cache_key][$ln->fk_product][$ln->entrepot]['qty'] = $ln->qty;
            self::$ventes[$cache_key][$ln->fk_product][$ln->entrepot]['total_ht'] = $ln->total_ht;
            self::$ventes[$cache_key][$ln->fk_product][$ln->entrepot]['total_ttc'] = $ln->total_ttc;
//            self::$ventes[$cache_key][$ln->fk_product][$ln->entrepot]['total_achats'] = $ln->total_achats;

            if (!isset(self::$ventes[$cache_key][$ln->fk_product][null])) {
                self::$ventes[$cache_key][$ln->fk_product][null] = array(
                    'qty'       => 0,
                    'total_ht'  => 0,
                    'total_ttc' => 0
                );
            }

            self::$ventes[$cache_key][$ln->fk_product][null]['qty'] += $ln->qty;
            self::$ventes[$cache_key][$ln->fk_product][null]['total_ht'] += $ln->total_ht;
            self::$ventes[$cache_key][$ln->fk_product][null]['total_ttc'] += $ln->total_ttc;
//            self::$ventes[$cache_key][$ln->fk_product][null]['total_achats'] += $ln->total_achats;
        }

        $sql2 = $db->query($query . " AND `subprice` < 0" . $group_by);

        while ($ln = $db->fetch_object($sql2)) {
            if (!isset(self::$ventes[$cache_key][$ln->fk_product][$ln->entrepot])) {
                self::$ventes[$cache_key][$ln->fk_product][$ln->entrepot] = array(
                    'qty'       => 0,
                    'total_ht'  => 0,
                    'total_ttc' => 0
                );
            }

            self::$ventes[$cache_key][$ln->fk_product][$ln->entrepot]['qty'] += ($ln->qty * -1);
            self::$ventes[$cache_key][$ln->fk_product][$ln->entrepot]['total_ht'] += $ln->total_ht;
            self::$ventes[$cache_key][$ln->fk_product][$ln->entrepot]['total_ttc'] += $ln->total_ttc;
//            self::$ventes[$cache_key][$ln->fk_product][$ln->entrepot]['total_achats'] = $ln->total_achats;

            if (!isset(self::$ventes[$cache_key][$ln->fk_product][null])) {
                self::$ventes[$cache_key][$ln->fk_product][null] = array(
                    'qty'       => 0,
                    'total_ht'  => 0,
                    'total_ttc' => 0
                );
            }
            self::$ventes[$cache_key][$ln->fk_product][null]['qty'] += ($ln->qty * -1);
            self::$ventes[$cache_key][$ln->fk_product][null]['total_ht'] += $ln->total_ht;
            self::$ventes[$cache_key][$ln->fk_product][null]['total_ttc'] += $ln->total_ttc;
//            self::$ventes[$cache_key][$ln->fk_product][null]['total_achats'] += $ln->total_achats;
        }
    }
}
