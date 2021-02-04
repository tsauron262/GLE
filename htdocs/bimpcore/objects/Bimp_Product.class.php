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
    public static $status_list = array(
        0 => array('label' => 'Non validé', 'icon' => 'fas_times', 'classes' => array('danger')),
        1 => array('label' => 'Validé', 'icon' => 'fas_check', 'classes' => array('success'))
    );
    public static $bimp_stock_origins = array('vente_caisse', 'transfert', 'sav', 'package', 'inventory', 'pret');
    public $redirectMode = 4; //5;//1 btn dans les deux cas   2// btn old vers new   3//btn new vers old   //4 auto old vers new //5 auto new vers old

    CONST STOCK_IN = 0;
    CONST STOCK_OUT = 1;
    CONST TYPE_COMPTA_NONE = 0;
    CONST TYPE_COMPTA_PRODUIT = 1;
    CONST TYPE_COMPTA_SERVICE = 2;
    CONST TYPE_COMPTA_PORT = 3;
    CONST TYPE_COMPTA_COMM = 4;

    public static $type_compta = [
        self::TYPE_COMPTA_NONE    => 'Aucun re-classement',
        self::TYPE_COMPTA_PRODUIT => 'Considéré comme produit',
        self::TYPE_COMPTA_SERVICE => "Considéré comme service",
        self::TYPE_COMPTA_PORT    => "Considéré comme frais de port",
        self::TYPE_COMPTA_COMM    => "Considéré comme commission"
    ];
    public static $units_weight = array();
    public static $units_length = array();
    public static $units_surface = array();
    public static $units_volume = array();
    private static $stockDate = array();
    private static $stockShowRoom = array();
    private static $ventes = array();
    private static $lienShowRoomEntrepot = array();
    public $fieldsWithAddNoteOnUpdate = array('serialisable');

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
            case 'cur_pa_ht':
                if ($user->admin) {
                    return 1;
                }
                return 0;
        }

        return parent::canEditField($field_name);
    }

    public function canSetAction($action)
    {
        switch ($action) {
            case 'validate':
            case 'refuse':
            case 'merge':
            case 'updatePrice':
            case 'mouvement':
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
            case 'mouvement':
                if ((int) !$this->getData('validate')) {
                    $errors[] = 'Ce produit n\'est pas validé';
                    return 0;
                }
                if ((int) $this->getData('serialisable')) {
                    $errors[] = 'Ce produit est sériliasable créer un équipment';
                    return 0;
                }
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

            case 'updatePrice':
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

    public function isVendable(&$errors, $urgent = false, $mail = true)
    {
        if (BimpCore::getConf('use_valid_product') && $this->dol_field_exists('validate')) {
            if (!(int) $this->getData('validate')) {
                $errors[] = 'Le produit "' . $this->getRef() . ' - ' . $this->getData('label') . '" n\'est pas validé';
                if ($mail) {
                    $this->db->db->rollback();
                    if ($this->mailValidation($urgent))
                        $errors[] = "Un e-mail a été envoyé pour validation du produit.";
                    $this->db->db->begin();
                }
                return 0;
            }

            //provioir pour categorie
            $null = array();
            foreach (array('categorie', 'collection', 'nature', 'famille', 'gamme') as $type) {
                if (is_null($this->getData($type)) || $this->getData($type) == "" || $this->getData($type) === 0) {
                    $null[] = $type;
                }
            }
            if (count($null) > 2) {
                mailSyn2("Prod non catagorisé", "l.gay@bimp.fr", null, "Bonjour le produit " . $this->getNomUrl(0, 1, 0, '') . " n'est pas categorisé comme il faut, il manque :  " . implode(", ", $null));
            }
        }

        return 1;
    }

    public function isAchetable(&$errors, $urgent = false, $mail = true)
    {
        return $this->isVendable($errors, $urgent, $mail);
    }

    public function hasFixePrices()
    {
        return ((int) $this->getData('no_fixe_prices') ? 0 : 1);
    }

    public function hasFixePu()
    {
        return $this->hasFixePrices();
    }

    public function hasFixePa()
    {
        return $this->hasFixePrices();
    }

    public function productBrowserIsActif()
    {
        global $conf;
        return (isset($conf->global->MAIN_MODULE_BIMPPRODUCTBROWSER) ? 1 : 0);
    }

    // Getters codes comptables: 

    public function getProductTypeCompta()
    {
        $type_compta = $this->getData('type_compta');


        if ($type_compta > 0) {
            $type = $type_compta - 1;
        } else {
            if ($frais_de_port = $this->db->getRow('categorie_product', 'fk_categorie = 9705 AND fk_product = ' . $this->id) || $this->id == 129950)
                $type = 2;
            else
                $type = $this->getData('fk_product_type');
        }
        return $type;
    }

    public function getCodeComptableAchat($zone_vente = 1, $force_type = -1, $tvaTaux = 1)
    {
        if ($force_type == -1) {
            if (!$this->isLoaded())
                return '';
            if ($this->getData('accountancy_code_buy') != '') {
                return $this->getData('accountancy_code_buy');
            }
            $type = $this->getProductTypeCompta();
        } else {
            $type = $force_type;
        }
        if ($type == 0) { // Produit
            if ($zone_vente == 1) {
                if ($tvaTaux == 0 ||
                        ($tvaTaux == 1 && $this->getData('tva_tx') == 0)) {
                    return BimpCore::getConf('BIMPTOCEGID_achat_tva_null');
                }
                return BimpCore::getConf('BIMPTOCEGID_achat_produit_fr');
            } elseif ($zone_vente == 2 || $zone_vente == 4)
                return BimpCore::getConf('BIMPTOCEGID_achat_produit_ue');
            elseif ($zone_vente == 3)
                return BimpCore::getConf('BIMPTOCEGID_achat_produit_ex');
        } elseif ($type == 1) { // Service
            if ($zone_vente == 1) {
                if ($tvaTaux == 0 ||
                        ($tvaTaux == 1 && $this->getData('tva_tx') == 0)) {
                    return BimpCore::getConf('BIMPTOCEGID_achat_tva_null_service');
                }
                return BimpCore::getConf('BIMPTOCEGID_achat_service_fr');
            } elseif ($zone_vente == 2 || $zone_vente == 4)
                return BimpCore::getConf('BIMPTOCEGID_achat_service_ue');
            elseif ($zone_vente == 3)
                return BimpCore::getConf('BIMPTOCEGID_achat_service_ex');
        } elseif ($type == 2) { // Frais de port
            if ($zone_vente == 1)
                return BimpCore::getConf('BIMPTOCEGID_frais_de_port_achat_fr');
            elseif ($zone_vente == 2 || $zone_vente == 4)
                return BimpCore::getConf('BIMPTOCEGID_frais_de_port_achat_ue');
            elseif ($zone_vente == 3)
                return BimpCore::getConf('BIMPTOCEGID_frais_de_port_achat_ex');
        }
    }

    public function getCodeComptableVente($zone_vente = 1, $force_type = -1)
    {
        // ACHAT DE D3E juste pour la france
        // ACHAT DE TVA JUSTe PAR AUTOLIQUIDATION - si on a un numéro intracom sur un pro UE

        if ($force_type == -1) {
            if (!$this->isLoaded())
                return '';
            if ($this->getData('accountancy_code_sell') != '')
                return $this->getData('accountancy_code_sell');
            $type = $this->getProductTypeCompta();
        } else
            $type = $force_type;
        if ($type == 0) {//Produit
            if ($zone_vente == 1) {
                if ($this->getData('tva_tx') == 0) {
                    return BimpCore::getConf('BIMPTOCEGID_vente_tva_null');
                }
                return BimpCore::getConf('BIMPTOCEGID_vente_produit_fr');
            } elseif ($zone_vente == 2 || $zone_vente == 4)
                return BimpCore::getConf('BIMPTOCEGID_vente_produit_ue');
            elseif ($zone_vente == 3)
                return BimpCore::getConf('BIMPTOCEGID_vente_produit_ex');
        }
        elseif ($type == 1) {//service
            if ($zone_vente == 1)
                return BimpCore::getConf('BIMPTOCEGID_vente_service_fr');
            elseif ($zone_vente == 2 || $zone_vente == 4)
                return BimpCore::getConf('BIMPTOCEGID_vente_service_ue');
            elseif ($zone_vente == 3)
                return BimpCore::getConf('BIMPTOCEGID_vente_service_ex');
        }
        elseif ($type == 2) {//Port
            if ($zone_vente == 1)
                return BimpCore::getConf('BIMPTOCEGID_frais_de_port_vente_fr');
            elseif ($zone_vente == 2 || $zone_vente == 4)
                return BimpCore::getConf('BIMPTOCEGID_frais_de_port_vente_ue');
            elseif ($zone_vente == 3)
                return BimpCore::getConf('BIMPTOCEGID_frais_de_port_vente_ex');
        }
        elseif ($type == 3) {//commission
            if ($zone_vente == 1)
                return BimpCore::getConf('BIMPTOCEGID_comissions_fr');
            elseif ($zone_vente == 2 || $zone_vente == 4)
                return BimpCore::getConf('BIMPTOCEGID_comissions_ue');
            elseif ($zone_vente == 3)
                return BimpCore::getConf('BIMPTOCEGID_comissions_ex');
        }
        return false;
    }

    public function getCodeComptableVenteTva($zone_vente = 1)
    {
        if ($zone_vente == 1)
            return BimpCore::getConf('BIMPTOCEGID_vente_tva_fr');
        elseif ($zone_vente == 2 || $zone_vente == 4)
            return BimpCore::getConf('BIMPTOCEGID_vente_tva_ue');
        return false;
    }

    public function getCodeComptableVenteDeee($zone_vente = 1)
    {
        if ($zone_vente == 1)
            return BimpCore::getConf('BIMPTOCEGID_vente_dee_fr');
//        elseif($zone_vente == 2 || $zone_vente == 4)
//            return BimpCore::getConf('BIMPTOCEGID_vente_dee_ue');
        return false;
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

    public function getFileUrl($file_name, $page = 'document')
    {
        $dir = $this->getFilesDir();
        if ($dir) {
            if (file_exists($dir . $file_name)) {
                return DOL_URL_ROOT . '/' . $page . '.php?modulepart=produit&file=' . htmlentities(dol_sanitizeFileName($this->getRef()) . '/' . $file_name);
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

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, &$errors = array(), $excluded = false)
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
                    ($excluded ? 'not_' : '') . 'in' => $values
                );
                break;
        }

        parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $errors, $excluded);
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

        if ($this->isActionAllowed('validate')) {
            $buttons[] = array(
                'label'   => 'Demande de validation',
                'icon'    => 'fas_check-circle',
                'onclick' => $this->getJsActionOnclick('mailValidate', array(), array(
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

        if ($this->isActionAllowed('generateEtiquettes') && $this->canSetAction('generateEtiquettes')) {
            $buttons[] = array(
                'label'   => 'Etiquettes',
                'icon'    => 'fas_sticky-note',
                'onclick' => $this->getJsActionOnclick('generateEtiquettes', array(
                    'qty' => 1
                        ), array(
                    'form_name' => 'etiquettes'
                ))
            );
        }

        if ($this->isActionAllowed('mouvement') && $this->canSetAction('mouvement')) {
            $buttons[] = array(
                'label'   => 'Mouvement',
                'icon'    => 'fas_random',
                'onclick' => $this->getJsActionOnclick('mouvement', array(
                    'qty' => 1
                        ), array(
                    'form_name' => 'mouvement'
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

    public function getJs()
    {
        $js = array();
        $js[] = "/bimpcore/views/js/history.js";
//        if ($this->productBrowserIsActif())
//            $js[] = "/bimpcore/views/js/categorize.js";
        return $js;
    }

    public function getNomUrlExtra()
    {
        if ($this->isLoaded()) {
            return $this->getStockIconStatic($this->id, null, $this->isSerialisable());
        }

        return '';
    }

    // Getters données: 

    public function getRemiseCrt()
    {
        if ($this->dol_field_exists('crt')) {
            return (float) $this->getData('crt');
        }

        return 0;
    }

    public function getDerPv($dateMin, $dateMax = null, $id_product = null)
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

        $cache_key = $dateMin . '-' . $dateMax . 'derPv';

        if ((int) $id_product) {
            if (!isset(self::$ventes[$cache_key])) {
                self::initDerPv($dateMin, $dateMax);
            }

            if (isset(self::$ventes[$cache_key][$id_product])) {
                return self::$ventes[$cache_key][$id_product];
            }
        }

        return 0;
    }

    public function initDerPv($dateMin, $dateMax)
    {
        global $db;
//        self::$ventes = array(); // Ne pas déco ça effacerait d'autres données en cache pour d'autres dates. 

        $query = 'SELECT MAX(l.rowid) as rowid , fk_product FROM `' . MAIN_DB_PREFIX . 'facturedet` l, `' . MAIN_DB_PREFIX . 'facture` f WHERE f.rowid = l.fk_facture AND qty > 0 ';
        if ($dateMin)
            $query .= " AND date_valid >= '" . $dateMin . "'";

        if ($dateMax)
            $query .= " AND date_valid <= '" . $dateMax . "'";
        $sql = $db->query($query . " GROUP BY fk_product");
        while ($ln = $db->fetch_object($sql)) {
            $tabT[] = $ln->rowid;
        }
        $query = 'SELECT (total_ht / qty) as derPv, fk_product FROM `' . MAIN_DB_PREFIX . 'facturedet` l WHERE rowid IN (' . implode(",", $tabT) . ')';
        $sql = $db->query($query);


        $cache_key = $dateMin . "-" . $dateMax . 'derPv';

        while ($ln = $db->fetch_object($sql)) {
            self::$ventes[$cache_key][$ln->fk_product] = $ln->derPv;
//            self::$ventes[$cache_key][$ln->fk_product][null]['total_achats'] += $ln->total_achats;
        }
    }

    public function getVentes($dateMin, $dateMax = null, $id_entrepot = null, $id_product = null, $tab_secteur = array(), $exlure_retour = false, $with_factures = false)
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

        $cache_key = $dateMin . '-' . $dateMax . "-" . implode("/", $tab_secteur) . '-' . (int) $exlure_retour;

        if ($with_factures) {
            $cache_key .= '_with_factures';
        }

        if ((int) $id_product) {
            if (!isset(self::$ventes[$cache_key])) {
                self::initVentes($dateMin, $dateMax, $tab_secteur, $exlure_retour, $with_factures);
            }

            if (isset(self::$ventes[$cache_key][$id_product][$id_entrepot])) {
                return self::$ventes[$cache_key][$id_product][$id_entrepot];
            }
        }

        return array(
            'qty'       => 0,
            'total_ht'  => 0,
            'total_ttc' => 0,
            'factures'  => array()
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
                        'stock_showroom' => 0,
                        'factures'       => array()
                    );
                }
                if ($id_entrepot == -9999) {
                    $ventes = $this->getVentes($dateMin, $dateMax, null, $id_product, array("E"), false, true);
                    $data[$ship_to]['ventes'] += $ventes['qty'];
                    $data[$ship_to]['stock'] += 0;
                    $data[$ship_to]['stock_showroom'] += 0;

                    if (isset($ventes['factures']) && !empty($ventes['factures'])) {
                        foreach ($ventes['factures'] as $id_fac => $fac_lines) {
                            foreach ($fac_lines as $id_line => $line_data) {
                                $data[$ship_to]['factures'][$id_fac][$id_line] = $line_data;
                            }
                        }
                    }
                } else {
                    $tab_secteur = array("S", "M", "CO", "BP", "C"); //tous sauf E
                    $ventes = $this->getVentes($dateMin, $dateMax, $id_entrepot, $id_product, $tab_secteur, false, true);

                    $data[$ship_to]['ventes'] += $ventes['qty'];
                    $data[$ship_to]['stock'] += $this->getStockDate($dateMax, $id_entrepot, $id_product, true);
                    $data[$ship_to]['stock_showroom'] += $this->getStockShoowRoom($dateMax, $id_entrepot, $id_product);

                    if (isset($ventes['factures']) && !empty($ventes['factures'])) {
                        foreach ($ventes['factures'] as $id_fac => $fac_lines) {
                            foreach ($fac_lines as $id_line => $line_data) {
                                $data[$ship_to]['factures'][$id_fac][$id_line] = $line_data;
                            }
                        }
                    }
                }
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

    public function getNbScanned($is_show_room = 0)
    {
        global $cache_scann;

        $id_inventory = BimpTools::getValue('id');
        $key = $id_inventory . ($is_show_room ? '_sr' : '');

        if (!isset($cache_scann[$key])) {
            $sql = 'SELECT SUM(qty) as qty, fk_product';
            $sql .= ' FROM ' . MAIN_DB_PREFIX . 'bl_inventory_' . ($is_show_room ? 'sr_det' : 'det');
            $sql .= ' WHERE fk_inventory=' . $id_inventory;
            $sql .= ' GROUP BY fk_product';
            $result = $this->db->db->query($sql);
            if ($result) {
                while ($obj = $this->db->db->fetch_object($result)) {
                    $cache_scann[$key][(int) $obj->fk_product] = $obj->qty;
                }
            }
        }
        if (isset($cache_scann[$key][(int) $this->getData('id')]))
            return $cache_scann[$key][(int) $this->getData('id')];
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

    public function getValues8sens($type, $include_empty = true)
    {
        // Utiliser ***impérativement*** le cache pour ce genre de requêtes         
        return self::getProductsTagsByTypeArray($type, $include_empty);
    }

    public function getRefFourn($idFourn = null)
    {
        if ($this->isLoaded()) {
            $refFourn = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ProductFournisseurPrice');
            $filter = array('fk_product' => $this->id);
            if ($idFourn)
                $filter['fk_soc'] = $idFourn;
            if ($refFourn->find($filter)) {
                return $refFourn->getData('ref_fourn');
            }
        }
        return '';
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

            if (!count($product->stock_warehouse))
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
                    $sql = 'SELECT line.rowid as id_line, c.rowid as id_commande, (line.qty + bline.qty_modif) as full_qty, bline.receptions FROM ' . MAIN_DB_PREFIX . 'commande_fournisseurdet line';
                    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bimp_commande_fourn_line bline ON bline.id_line = line.rowid';
                    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'commande_fournisseur c ON c.rowid = line.fk_commande';
                    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'commande_fournisseur_extrafields cef ON c.rowid = cef.fk_object';
                    $sql .= ' WHERE line.fk_product = ' . (int) $this->id;
                    $sql .= ' AND c.fk_statut > 0 AND c.fk_statut < 5';
                    $sql .= ' AND cef.entrepot = ' . (int) $id_entrepot;

                    $rows = $this->db->executeS($sql, 'array');

                    if (!is_null($rows)) {
                        foreach ($rows as $r) {
                            $received_qty = 0;

                            if ((string) $r['receptions']) {
                                $receptions = json_decode($r['receptions'], true);
                                $recep_list = array();

                                if (!empty($receptions)) {
                                    foreach ($receptions as $id_reception => $reception_data) {
                                        $recep_list[] = (int) $id_reception;
                                    }

                                    $sql = 'SELECT `id` FROM ' . MAIN_DB_PREFIX . 'bl_commande_fourn_reception ';
                                    $sql .= 'WHERE `id` IN(' . implode(',', $recep_list) . ') AND `status` = 1';

                                    $valid_receptions = $this->db->executeS($sql, 'array');

                                    if (is_array($valid_receptions)) {
                                        foreach ($valid_receptions as $reception) {
                                            $received_qty += (float) $receptions[(int) $reception['id']]['qty'];
                                        }
                                    }
                                }
                            }

                            $stocks['commandes'] += ((float) $r['full_qty'] - $received_qty);

                            // Vielle méthode trop bourrine: 
                            // Pour être sûr que les BimpLines existent: 
//                            $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', (int) $r['id_commande']);
//                            if (BimpObject::ObjectLoaded($commande)) {
//                                $commande->checkLines();
//                            }
//                            $bimp_line = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFournLine', array(
//                                        'id_line' => (int) $r['id_line']
//                                            ), true);
//
//                            if (BimpObject::ObjectLoaded($bimp_line)) {
//                                $stocks['commandes'] += ((float) $bimp_line->getFullQty() - $bimp_line->getReceivedQty(null, true));
//                            }
                        }
                    }

                    $stocks['virtuel'] = $stocks['reel'] - $stocks['total_reserves'] + $stocks['commandes'];
                }
            }
        }

        return $stocks;
    }

    public function getStockDate($date = null, $id_entrepot = null, $id_product = null, $include_shipment_diff = false)
    {
        if (is_null($date))
            return 'N/C';


        if (is_null($id_product) && $this->isLoaded()) {
            $id_product = $this->id;
        }

        if ((int) $id_product) {
            $stock = 0;
            if (!isset(self::$stockDate[$date])) {
                self::initStockDate($date, $include_shipment_diff);
            }

            if (isset(self::$stockDate[$date][$id_product][$id_entrepot]['stock'])) {
                $stock = self::$stockDate[$date][$id_product][$id_entrepot]['stock'];
            }
        }

        return $stock;
    }

    public function getStockShoowRoom($date, $id_entrepot = null, $id_product = null)
    {
        if (is_null($id_product) && $this->isLoaded()) {
            $id_product = $this->id;
        }
        $stock = 0;

        if ((int) $id_product) {
            if (!count(self::$stockShowRoom))
                self::initStockShowRoom();

            if (isset(self::$stockShowRoom[$id_product][$id_entrepot])) {
                $stock = self::$stockShowRoom[$id_product][$id_entrepot];
            }

//            if (!count(self::$lienShowRoomEntrepot))
//                self::initLienShowRoomEntrepot();
//
//
//            if (isset(self::$lienShowRoomEntrepot[$id_entrepot]))
//                $stock = $this->getStockDate($date, self::$lienShowRoomEntrepot[$id_entrepot], $id_product);
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

        $html = '<span class="objectIcon displayProductStocksBtn' . ($serialisable ? ' green' : '') . ' bs-popover" data-id_product="' . $id_product . '" data-id_entrepot="' . (int) $id_entrepot . '"';
        $html .= BimpRender::renderPopoverData('Stocks');
        $html .= '>';
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

        $sql = 'SELECT fp.rowid as id, fp.price, fp.quantity as qty, fp.tva_tx as tva, s.nom, fp.ref_fourn as ref, s.code_fournisseur as ref2';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'product_fournisseur_price fp';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'societe s ON fp.fk_soc = s.rowid';
        $sql .= BimpTools::getSqlWhere($filters);
        $sql .= ' ORDER BY fp.unitprice ASC';

        global $db;
        $bdb = new BimpDb($db);

        $rows = $bdb->executeS($sql, 'array');

        if (!is_null($rows) && count($rows)) {
            foreach ($rows as $r) {
                $label = $r['nom'] . ($r['ref'] ? ' - Réf. ' . $r['ref'] : ($r['ref2'] ? ' - Id. ' . $r['ref2'] : '')) . ' (';
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

    // Gestion Prix d'achat courant: 

    public function getCurrentPaHt($id_fourn = null, $with_default = true, $date = '')
    {
        if ((int) $this->getData('no_fixe_prices')) {
            return 0;
        }

        $pa_ht = 0;

        if ($this->isLoaded()) {
            if (!$this->hasFixePa()) {
                return 0;
            }
            if ((int) BimpCore::getConf('use_new_cur_pa_method')) {
                // Nouvelle méthode: 
                $curPa = $this->getCurrentPaObject(true, $date);
                if (BimpObject::objectLoaded($curPa)) {
                    $pa_ht = (float) $curPa->getData('amount');
                }
            } else {
                // Ancienne méthode: 
                if ((float) $this->getData('cur_pa_ht')) {
                    $pa_ht = (float) $this->getData('cur_pa_ht');
                } else {
                    $pa_ht = (float) $this->getCurrentFournPriceAmount($id_fourn, $with_default);

                    if (!$pa_ht && (float) $this->getData('pmp')) {
                        $pa_ht = (float) $this->getData('pmp');
                    }
                }
            }
        }

        return $pa_ht;
    }

    public function getCurrentPaObject($create_if_no_exists = true, $date = '')
    {
        if ((int) $this->getData('no_fixe_prices')) {
            return 0;
        }

        if (BimpCore::getConf('use_new_cur_pa_method')) {
            self::loadClass('bimpcore', 'BimpProductCurPa');
            $curPa = BimpProductCurPa::getProductCurPa($this->id, (string) $date);

            if (BimpObject::objectLoaded($curPa)) {
                return $curPa;
            }

            if ($create_if_no_exists) {
                $pfp = $this->getLastFournPrice();
                if (BimpObject::objectLoaded($pfp)) {
                    $pa_ht = (float) $pfp->getData('price');
                    if ($pa_ht) {
                        $curPa = BimpObject::getInstance('bimpcore', 'BimpProductCurPa');
                        $curPa->validateArray(array(
                            'id_product'     => (int) $this->id,
                            'date_from'      => $pfp->getData('datec'),
                            'amount'         => $pa_ht,
                            'origin'         => 'fourn_price',
                            'id_origin'      => (int) $pfp->id,
                            'id_fourn_price' => (int) $pfp->id
                        ));
                        $warnings = array();
                        $errors = $curPa->create($warnings, true);

                        if (!count($errors)) {
                            return $curPa;
                        }

                        return null;
                    }
                }

                $pa_ht = (float) $this->getData('pmp');
                if ($pa_ht) {
                    $curPa = BimpObject::getInstance('bimpcore', 'BimpProductCurPa');
                    $curPa->validateArray(array(
                        'id_product' => (int) $this->id,
                        'amount'     => $pa_ht,
                        'origin'     => 'pmp'
                    ));
                    $warnings = array();
                    $errors = $curPa->create($warnings, true);

                    if (!count($errors)) {
                        return $curPa;
                    }

                    return null;
                }
            }
        }

        return null;
    }

    //**** Nouvelles fonctions ****

    public function getLastFournPriceId($id_fourn = null)
    {
        if ($this->isLoaded()) {
            $sql = 'SELECT rowid as id FROM ' . MAIN_DB_PREFIX . 'product_fournisseur_price';
            $sql .= ' WHERE fk_product = ' . (int) $this->id;

            if (!is_null($id_fourn) && (int) $id_fourn) {
                $sql .= ' AND `fk_soc` = ' . (int) $id_fourn;
            }

            $sql .= ' ORDER BY `tms` DESC LIMIT 1';

            $result = $this->db->executeS($sql);

            if (isset($result[0]->id)) {
                return (int) $result[0]->id;
            }
        }

        return 0;
    }

    public function getLastFournPrice($id_fourn = null)
    {
        $id_pfp = (int) $this->getLastFournPriceId($id_fourn);

        if ($id_pfp) {
            return BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ProductFournisseurPrice', $id_pfp);
        }

        return null;
    }

    public function getLastFournPriceFournName()
    {
        if ($this->isLoaded()) {
            $sql = 'SELECT f.nom as nom FROM ' . MAIN_DB_PREFIX . 'product_fournisseur_price fp, ' . MAIN_DB_PREFIX . 'societe f';
            $sql .= ' WHERE f.rowid = fp.fk_soc AND  fk_product = ' . (int) $this->id;

            $sql .= ' ORDER BY fp.`tms` DESC LIMIT 1';

            $result = $this->db->executeS($sql);

            if (isset($result[0]->nom)) {
                return $result[0]->nom;
            }
        }

        return '';
    }

    //****  Anciennes fonctions ****

    public function getCurrentFournPriceId($id_fourn = null, $with_default = false)
    {
        $id_fp = 0;

        if ($this->isLoaded()) {
            if (!$this->hasFixePa()) {
                return 0;
            }

            $pa_ht = 0;

            if (BimpCore::getConf('use_new_cur_pa_method')) {
                $pa_ht = (float) $this->getCurrentPaHt();
            } else {
                if ((float) $this->getData('cur_pa_ht')) {
                    $pa_ht = (float) $this->getData('cur_pa_ht');
                }
            }

            if ($pa_ht) {
                $id_fp = (int) $this->findFournPriceIdForPaHt($pa_ht, $id_fourn);
            }

            if (!$id_fp && $with_default) {
                // On retourne le dernier PA fournisseur modifié ou enregistré:                     
                $id_fp = (int) $this->getLastFournPriceId($id_fourn);
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

    //******************************

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

    public function findRefFournForPaHtPlusProche($pa_ht, $id_fourn = null, &$difference = 0)
    {
        $return = null;
        if ($this->isLoaded()) {
            global $db;

            $query = 'SELECT * FROM `llx_product_fournisseur_price` WHERE `fk_product` = ' . (int) $this->id;
            if ($id_fourn)
                $query .= ' AND `fk_soc` = ' . (int) $id_fourn;


            $sql = $db->query($query);
            $priceMemoire = 0;
            while ($ln = $db->fetch_object($sql)) {
                if (is_null($return)) {
                    $return = $ln->ref_fourn;
                    $priceMemoire = $ln->price;
                    $difference = abs($pa_ht - $ln->price);
                } else {
                    $oldDif = abs($pa_ht - $priceMemoire);
                    $newDif = abs($pa_ht - $ln->price);

                    if ($newDif < $oldDif) {
                        $return = $ln->ref_fourn;
                        $priceMemoire = $ln->price;
                        $difference = abs($pa_ht - $ln->price);
                    }
                }
            }
        }

        return $return;
    }

    public function setCurrentPaHt($pa_ht, $id_fourn_price = 0, $origin = '', $id_origin = 0)
    {
        $errors = array();
        if ($this->isLoaded($errors)) {
            if (BimpCore::getConf('use_new_cur_pa_method')) {
                $curPa = $this->getCurrentPaObject(false);
                if (!BimpObject::objectLoaded($curPa) ||
                        ((float) $curPa->getData('amount') !== (float) $pa_ht || (int) $curPa->getData('id_fourn_price') !== (int) $id_fourn_price)) {
                    $curPa = BimpObject::getInstance('bimpcore', 'BimpProductCurPa');
                    $pa_errors = $curPa->validateArray(array(
                        'id_product'     => (int) $this->id,
                        'amount'         => (float) $pa_ht,
                        'origin'         => $origin,
                        'id_origin'      => (int) $id_origin,
                        'id_fourn_price' => (int) $id_fourn_price
                    ));

                    if (!count($pa_errors)) {
                        $w = array();
                        $pa_errors = $curPa->create($w, true);
                    }

                    if (count($pa_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($pa_errors, 'Echec de la création du nouveau prix d\'achat courant');
                    }
                }
            } else {
                if ((float) $this->getData('cur_pa_ht') !== (float) $pa_ht) {
                    $this->set('cur_pa_ht', (float) $pa_ht);
                    $this->set('id_cur_fp', (int) $id_fourn_price);
                    $this->set('cur_pa_origin', $origin);
                    $this->set('cur_pa_id_origin', (int) $id_origin);
                    $errors = $this->update($w, true);
                }
            }
        }

        return $errors;
    }

    public function updateCommandesFournPa($id_fourn, $pa_ht)
    {
        if ($this->isLoaded() && $id_fourn) {
            // Maj des lignes de commandes fourn pour les unités non réceptionnées: 
            $sql = 'SELECT bl.id as id_line, l.subprice as pa, cf.rowid as id_comm FROM ' . MAIN_DB_PREFIX . 'bimp_commande_fourn_line bl';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'commande_fournisseurdet l ON l.rowid = bl.id_line';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'commande_fournisseur cf ON cf.rowid = bl.id_obj';
            $sql .= ' WHERE l.fk_product = ' . $this->id . ' AND l.subprice != ' . $pa_ht . ' AND l.qty > 0';
            $sql .= ' AND cf.fk_soc = ' . $id_fourn;
            $sql .= ' AND (cf.fk_statut = 0 OR (cf.fk_statut < 5 AND bl.qty_to_receive > 0))';

            $rows = $this->db->executeS($sql, 'array');

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    if ((float) $r['pa'] !== (float) $pa_ht) {
                        $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFournLine', (int) $r['id_line']);
                        if (BimpObject::objectLoaded($line)) {
                            $line->pu_ht = (float) $pa_ht;

                            $receptions = $line->getData('receptions');
                            $rec_updated = array();
                            foreach ($receptions as $id_reception => $reception_data) {
                                $rec = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeFournReception', (int) $id_reception);
                                if ((int) $rec->getData('status') > 0) {
                                    continue;
                                }

                                if ($this->isSerialisable()) {
                                    foreach ($reception_data['serials'] as $idx => $serial_data) {
                                        $receptions[$id_reception]['serials'][$idx]['pu_ht'] = (float) $pa_ht;
                                    }
                                } else {
                                    foreach ($reception_data['qties'] as $idx => $qty_data) {
                                        $receptions[$id_reception]['qties'][$idx]['pu_ht'] = (float) $pa_ht;
                                    }
                                }
                                $rec_updated[] = $rec;
                            }

                            $line->set('receptions', $receptions);
                            $line_errors = $line->update($w, true);

                            if (!count($line_errors)) {
                                foreach ($rec_updated as $rec) {
                                    $rec->onLinesChange();
                                }
                            }
                        }
                    }
                }
            }
        }
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

    public function displayStock_picto()
    {
        return $this->getStockIconStatic($this->id);
    }

    public function displayStockInventorySr()
    {
        $id_inventory = BimpTools::getValue('id');
        $inventory_sr = BimpCache::getBimpObjectInstance('bimplogistique', 'InventorySR', $id_inventory);
        return $inventory_sr->getStockProduct((int) $this->getData('id'));
    }

    public function displayCurrentPaHt()
    {
        $html = '';

        if ($this->isLoaded()) {
            if (BimpCore::getConf('use_new_cur_pa_method')) {
                $curPa = $this->getCurrentPaObject();
                if (BimpObject::objectLoaded($curPa)) {
                    $html .= '<span style="font-size: 16px; font-style: bold">' . BimpTools::displayMoneyValue((float) $curPa->getData('amount')) . '</span><br/>';
                    $html .= 'Appliqué depuis le ' . $curPa->displayData('date_from') . '<br/>';
                    $html .= 'Origine: <strong>' . $curPa->displayOrigine() . '</strong>';
                } else {
                    $html .= '<span class="danger">Aucun</span>';
                }
            } else {
                $html .= '<span style="font-size: 16px; font-style: bold">';
                $html .= BimpTools::displayMoneyValue((float) $this->getData('cur_pa_ht'));
                $html .= '</span><br/>';
            }
        }

        return $html;
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


        $html .= '<div class="object_header_infos">';
        $html .= 'Créée le ' . BimpTools::printDate($this->getData('datec'), 'strong');
        $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $this->getData('fk_user_author'));
        if (BimpObject::objectLoaded($user)) {
            $html .= ' par&nbsp;&nbsp;' . $user->getLink();
        }
        $html .= '</div>';

        if ((int) $this->getData('date_valid')) {
            $html .= '<div class="object_header_infos">';
            $html .= 'Validée le ' . BimpTools::printDate($this->getData('date_valid'), 'strong');
            $html .= '</div>';
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
            if ($stocks['reel'] != 0) {//stok > 0 au debut
                $html1 .= $htmlT;
            } elseif ($stocks['dispo'] != 0) {//dispo > 0 au millieu
                $html2 .= $htmlT;
            } elseif ($stocks['virtuel'] != 0) {//virtuel > 0 au millieu
                $html3 .= $htmlT;
            } else {
                $html4 .= $htmlT;
            }
        }
        $html .= $html1 . $html2 . $html3 . $html4;

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
//        $stats_prop_supplier = $this->load_stats_proposal_supplier();
        $stats_command = $this->load_stats_commande();
        $stats_comm_fourn = $this->load_stats_commande_fournisseur();
        $stats_facture = $this->load_stats_facture();
        $stats_fact_fourn = $this->load_stats_facture_fournisseur();
        $stats_contrat = $this->load_stats_contrat();

        $stats = array($stats_propale, /* $stats_prop_supplier, */ $stats_command,
            $stats_facture, $stats_contrat,
            $stats_comm_fourn, $stats_fact_fourn);

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

    public function renderStock()
    {
        /* Ne peut être utilisé que dans l'affichage des listes à cause de $inventory->current_wt */
        $id_inventory = (int) BimpTools::getValue('id');
        $inventory = BimpCache::getBimpObjectInstance('bimplogistique', 'Inventory2', $id_inventory);
        $diff = $inventory->getDiffProduct($inventory->current_wt, $this->getData('id'));
        return $diff['stock'];
    }

    public function renderNbScanned()
    {
        /* Ne peut être utilisé que dans l'affichage des listes à cause de $inventory->current_wt */
        $id_inventory = (int) BimpTools::getValue('id');
        $inventory = BimpCache::getBimpObjectInstance('bimplogistique', 'Inventory2', $id_inventory);
        $diff = $inventory->getDiffProduct($inventory->current_wt, $this->getData('id'));
        return $diff['nb_scan'];
    }

    public function renderCardView()
    {
        $html = '';

        $tabs = array();

        // Infos: 
        $view = new BC_View($this, 'fiche');
        $view->params['panel'] = 0;
        $tabs[] = array(
            'id'      => 'infos_tab',
            'title'   => BimpRender::renderIcon('fas_info-circle', 'iconLeft') . 'Infos',
            'content' => $view->renderHtml()
        );

        if ($this->isSerialisable()) {
            // Equipements: 
            $tabs[] = array(
                'id'            => 'equipments_tab',
                'title'         => BimpRender::renderIcon('fas_desktop', 'iconLeft') . 'Equipements',
                'ajax'          => 1,
                'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectsList', '$(\'#equipments_tab .nav_tab_ajax_result\')', array('equipments'), array('button' => ''))
            );
        }

        // Evénements: 
        $tabs[] = array(
            'id'            => 'events_tab',
            'title'         => BimpRender::renderIcon('fas_calendar-check', 'iconLeft') . 'Evénements',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectsList', '$(\'#events_tab .nav_tab_ajax_result\')', array('events'), array('button' => ''))
        );

        // Rapports processus: 
        if (BimpCore::isModuleActive('bimpdatasync')) {
            $tabs[] = array(
                'id'            => 'bds_reports_tab',
                'title'         => BimpRender::renderIcon('far_file-alt', 'iconLeft') . 'Rapports processus',
                'ajax'          => 1,
                'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectsList', '$(\'#bds_reports_tab .nav_tab_ajax_result\')', array('bds_reports'), array('button' => ''))
            );
        }

        $html = BimpRender::renderNavTabs($tabs, 'stocks_view');

        return $html;
    }

    public function renderStocksView()
    {
        $html = '';

        $tabs = array();

        // Stocks par entrepôt: 
        $tabs[] = array(
            'id'            => 'stocks_by_entrepots_tab',
            'title'         => BimpRender::renderIcon('fas_box-open', 'iconLeft') . 'Stocks par entrepôts',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectsList', '$(\'#stocks_by_entrepots_tab .nav_tab_ajax_result\')', array('stocks_by_entrepots'), array('button' => ''))
        );

        // Mouvements de stock: 
        $tabs[] = array(
            'id'            => 'stocks_mvts_tab',
            'title'         => BimpRender::renderIcon('fas_exchange-alt', 'iconLeft') . 'Mouvements de stock',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectsList', '$(\'#stocks_mvts_tab .nav_tab_ajax_result\')', array('stocks_mvts'), array('button' => ''))
        );

        // Mouvements de stock: 
        $tabs[] = array(
            'id'            => 'stocks_equipment_tab',
            'title'         => BimpRender::renderIcon('fas_desktop', 'iconLeft') . 'Équipements en stock',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectsList', '$(\'#stocks_equipment_tab .nav_tab_ajax_result\')', array('stocks_equipment'), array('button' => ''))
        );


        $html = BimpRender::renderNavTabs($tabs, 'stocks_view');

        return $html;
    }

    public function renderCommercialView()
    {
        $tabs = array();

        // Propales
        $tabs[] = array(
            'id'            => 'product_propales_list_tab',
            'title'         => BimpRender::renderIcon('fas_file-invoice', 'iconLeft') . 'Propositions commerciales',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectsList', '$(\'#product_propales_list_tab .nav_tab_ajax_result\')', array('propales'), array('button' => ''))
        );

        // Commandes client
        $tabs[] = array(
            'id'            => 'product_commandes_list_tab',
            'title'         => BimpRender::renderIcon('fas_dolly', 'iconLeft') . 'Commandes',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectsList', '$(\'#product_commandes_list_tab .nav_tab_ajax_result\')', array('commandes'), array('button' => ''))
        );

        // Factures
        $tabs[] = array(
            'id'            => 'product_factures_list_tab',
            'title'         => BimpRender::renderIcon('fas_file-invoice-dollar', 'iconLeft') . 'Factures',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectsList', '$(\'#product_factures_list_tab .nav_tab_ajax_result\')', array('factures'), array('button' => ''))
        );

        // Contrats
        $tabs[] = array(
            'id'            => 'product_contrats_list_tab',
            'title'         => BimpRender::renderIcon('fas_file-signature', 'iconLeft') . 'Contrats',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectsList', '$(\'#product_contrats_list_tab .nav_tab_ajax_result\')', array('contrats'), array('button' => ''))
        );

        // Commandes fournisseurs
        $tabs[] = array(
            'id'            => 'product_commandes_fourn_list_tab',
            'title'         => BimpRender::renderIcon('fas_cart-arrow-down', 'iconLeft') . 'Commandes fournisseurs',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectsList', '$(\'#product_commandes_fourn_list_tab .nav_tab_ajax_result\')', array('commandes_fourn'), array('button' => ''))
        );

        // Factures fournisseurs
        $tabs[] = array(
            'id'            => 'product_factures_fourn_list_tab',
            'title'         => BimpRender::renderIcon('fas_file-invoice-dollar', 'iconLeft') . 'Factures fournisseurs',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectsList', '$(\'#product_factures_fourn_list_tab .nav_tab_ajax_result\')', array('factures_fourn'), array('button' => ''))
        );

        return BimpRender::renderNavTabs($tabs, 'commercial_view');
    }

    public function renderLinkedObjectsList($list_type)
    {
        $errors = array();
        if (!$this->isLoaded($errors)) {
            return BimpRender::renderAlerts($errors);
        }

        $html = '';

        $list = null;
        $product_label = $this->getRef();

        switch ($list_type) {
            case 'stocks_by_entrepots':
                $list = new BC_ListTable(BimpObject::getInstance('bimpcommercial', 'Bimp_Product_Entrepot'), 'product', 1, null, 'Stocks du produit "' . $product_label . '"', 'fas_box-open');
                $list->addFieldFilterValue('fk_product', $this->id);
                break;

            case 'stocks_mvts':
                $list = new BC_ListTable(BimpObject::getInstance('bimpcore', 'BimpProductMouvement'), 'product', 1, null, 'Mouvements stock du produit "' . $product_label . '"', 'fas_exchange-alt');
                $list->addFieldFilterValue('fk_product', $this->id);
                break;

            case 'stocks_equipment':
                if (!$this->isSerialisable()) {
                    $html .= BimpRender::renderAlerts('Ce produit n\'est pas sérialisable', 'warning');
                } else {
                    $list = new BC_ListTable(BimpObject::getInstance('bimpequipment', 'Equipment'), 'product', 1, null, 'Equipements en stock du produit "' . $product_label . '"', 'fas_desktop');
                    $list->addFieldFilterValue('id_product', $this->id);
                    $list->addFieldFilterValue('epl.position', 1);
                    $list->addFieldFilterValue('epl.type', BE_Place::BE_PLACE_ENTREPOT);
                    $list->addJoin('be_equipment_place', 'a.id = epl.id_equipment', 'epl');
                }
                break;


            case 'equipments':
                if (!$this->isSerialisable()) {
                    $html .= BimpRender::renderAlerts('Ce produit n\'est pas sérialisable', 'warning');
                } else {
                    $list = new BC_ListTable(BimpObject::getInstance('bimpequipment', 'Equipment'), 'product', 1, null, 'Equipements du produit "' . $product_label . '"', 'fas_desktop');
                    $list->addFieldFilterValue('id_product', $this->id);
                }
                break;

            case 'propales':
                $tabs = array();

                $list = new BC_ListTable(BimpObject::getInstance('bimpcommercial', 'Bimp_Propal'), 'default', 1, null, 'Propositions commerciales incluant le produit "' . $product_label . '"');

                $sql = 'a.rowid IN (SELECT DISTINCT(pdet.fk_propal) FROM ' . MAIN_DB_PREFIX . 'propaldet pdet WHERE pdet.fk_product = ' . $this->id . ')';
                $list->addFieldFilterValue('product_custom', array(
                    'custom' => $sql
                ));

                $tabs[] = array(
                    'id'      => 'product_propales',
                    'title'   => 'Liste des propales',
                    'content' => $list->renderHtml()
                );

                unset($list);
                $list = null;

                $list = new BC_ListTable(BimpObject::getInstance('bimpcommercial', 'Bimp_PropalLine'), 'product', 1, null, 'Lignes de propositions commerciales du produit "' . $product_label . '"');
                $list->addJoin('propaldet', 'pdet.rowid = a.id_line', 'pdet');
                $list->addFieldFilterValue('pdet.fk_product', (int) $this->id);

                $tabs[] = array(
                    'id'      => 'product_propales_lines',
                    'title'   => 'Lignes de propales',
                    'content' => $list->renderHtml()
                );

                unset($list);
                $list = null;

                $html = BimpRender::renderNavTabs($tabs, 'product_propales_lists');
                break;

            case 'commandes':
                $tabs = array();

                $list = new BC_ListTable(BimpObject::getInstance('bimpcommercial', 'Bimp_Commande'), 'default', 1, null, 'Commandes clients incluant le produit "' . $product_label . '"');
                $sql = 'a.rowid IN (SELECT DISTINCT(cdet.fk_commande) FROM ' . MAIN_DB_PREFIX . 'commandedet cdet WHERE cdet.fk_product = ' . $this->id . ')';
                $list->addFieldFilterValue('product_custom', array(
                    'custom' => $sql
                ));

                $tabs[] = array(
                    'id'      => 'product_commandes',
                    'title'   => 'Liste des commandes',
                    'content' => $list->renderHtml()
                );

                unset($list);
                $list = null;

                $list = new BC_ListTable(BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeLine'), 'product', 1, null, 'Lignes de commandes du produit "' . $product_label . '"');
                $list->addJoin('commandedet', 'cdet.rowid = a.id_line', 'cdet');
                $list->addFieldFilterValue('cdet.fk_product', (int) $this->id);

                $tabs[] = array(
                    'id'      => 'product_commandes_lines',
                    'title'   => 'Lignes de commandes',
                    'content' => $list->renderHtml()
                );

                unset($list);
                $list = null;

                $html = BimpRender::renderNavTabs($tabs, 'product_commandes_lists');
                break;

            case 'factures':
                $tabs = array();

                $list = new BC_ListTable(BimpObject::getInstance('bimpcommercial', 'Bimp_Facture'), 'default', 1, null, 'Factures clients incluant le produit "' . $product_label . '"');
                $sql = 'a.rowid IN (SELECT DISTINCT(fdet.fk_facture) FROM ' . MAIN_DB_PREFIX . 'facturedet fdet WHERE fdet.fk_product = ' . $this->id . ')';
                $list->addFieldFilterValue('product_custom', array(
                    'custom' => $sql
                ));

                $tabs[] = array(
                    'id'      => 'product_factures',
                    'title'   => 'Liste des factures',
                    'content' => $list->renderHtml()
                );

                unset($list);
                $list = null;

                $list = new BC_ListTable(BimpObject::getInstance('bimpcommercial', 'Bimp_FactureLine'), 'product', 1, null, 'Lignes de factures du produit "' . $product_label . '"');
                $list->addJoin('facturedet', 'fdet.rowid = a.id_line', 'fdet');
                $list->addFieldFilterValue('fdet.fk_product', (int) $this->id);

                $tabs[] = array(
                    'id'      => 'product_factures_lines',
                    'title'   => 'Lignes de factures',
                    'content' => $list->renderHtml()
                );

                unset($list);
                $list = null;

                $html = BimpRender::renderNavTabs($tabs, 'product_factures_lists');
                break;

            case 'commandes_fourn':
                $tabs = array();

                $list = new BC_ListTable(BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFourn'), 'default', 1, null, 'Commandes fournisseurs incluant le produit "' . $product_label . '"');
                $sql = 'a.rowid IN (SELECT DISTINCT(cfdet.fk_commande) FROM ' . MAIN_DB_PREFIX . 'commande_fournisseurdet cfdet WHERE cfdet.fk_product = ' . $this->id . ')';
                $list->addFieldFilterValue('product_custom', array(
                    'custom' => $sql
                ));

                $tabs[] = array(
                    'id'      => 'product_commandes_fourn',
                    'title'   => 'Liste des commandes fournisseurs',
                    'content' => $list->renderHtml()
                );

                unset($list);
                $list = null;

                $list = new BC_ListTable(BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFournLine'), 'product', 1, null, 'Lignes de commandes fournisseurs du produit "' . $product_label . '"');
                $list->addJoin('commande_fournisseurdet', 'cfdet.rowid = a.id_line', 'cfdet');
                $list->addFieldFilterValue('cfdet.fk_product', (int) $this->id);

                $tabs[] = array(
                    'id'      => 'product_commandes_fourn_lines',
                    'title'   => 'Lignes de commandes fournisseurs',
                    'content' => $list->renderHtml()
                );

                unset($list);
                $list = null;

                $html = BimpRender::renderNavTabs($tabs, 'product_commandes_fourn_lists');
                break;

            case 'factures_fourn':
                $tabs = array();

                $list = new BC_ListTable(BimpObject::getInstance('bimpcommercial', 'Bimp_FactureFourn'), 'default', 1, null, 'Factures fournisseurs incluant le produit "' . $product_label . '"');
                $sql = 'a.rowid IN (SELECT DISTINCT(ffdet.fk_facture_fourn) FROM ' . MAIN_DB_PREFIX . 'facture_fourn_det ffdet WHERE ffdet.fk_product = ' . $this->id . ')';
                $list->addFieldFilterValue('product_custom', array(
                    'custom' => $sql
                ));

                $tabs[] = array(
                    'id'      => 'product_factures_fourn',
                    'title'   => 'Liste des factures fournisseurs',
                    'content' => $list->renderHtml()
                );

                unset($list);
                $list = null;

                $list = new BC_ListTable(BimpObject::getInstance('bimpcommercial', 'Bimp_FactureFournLine'), 'product', 1, null, 'Lignes de factures fournisseurs du produit "' . $product_label . '"');
                $list->addJoin('facture_fourn_det', 'ffdet.rowid = a.id_line', 'ffdet');
                $list->addFieldFilterValue('ffdet.fk_product', (int) $this->id);

                $tabs[] = array(
                    'id'      => 'product_factures_fourn_lines',
                    'title'   => 'Lignes de factures fournisseurs',
                    'content' => $list->renderHtml()
                );

                unset($list);
                $list = null;

                $html = BimpRender::renderNavTabs($tabs, 'product_factures_fourn_lists');
                break;

            case 'contrats':
                $tabs = array();

                $list = new BC_ListTable(BimpObject::getInstance('bimpcontract', 'BContract_contrat'), 'default', 1, null, 'Contrats incluant le produit "' . $product_label . '"', 'fas_file-signature');
                $sql = 'a.rowid IN (SELECT DISTINCT(cdet.fk_contrat) FROM ' . MAIN_DB_PREFIX . 'contratdet cdet WHERE cdet.fk_product = ' . $this->id . ')';
                $list->addFieldFilterValue('product_custom', array(
                    'custom' => $sql
                ));

                $tabs[] = array(
                    'id'      => 'product_contrats',
                    'title'   => 'Liste des contrats',
                    'content' => $list->renderHtml()
                );

                unset($list);
                $list = null;

                $list = new BC_ListTable(BimpObject::getInstance('bimpcontract', 'BContract_contratLine'), 'product', 1, null, 'Lignes de contrats du produit "' . $product_label . '"');
                $list->addFieldFilterValue('fk_product', (int) $this->id);

                $tabs[] = array(
                    'id'      => 'product_contrats_lines',
                    'title'   => 'Lignes de contrats',
                    'content' => $list->renderHtml()
                );

                unset($list);
                $list = null;

                $html = BimpRender::renderNavTabs($tabs, 'product_contrats_lists');
                break;

            case 'events':
                $list = new BC_ListTable(BimpObject::getInstance('bimpcore', 'Bimp_ActionComm'), 'default', 1, null, 'Evénements', 'fas_calendar-check');
                $list->addFieldFilterValue('elementtype', 'product');
                $list->addFieldFilterValue('fk_element', $this->id);
                break;

            case 'bds_reports':
                $list = new BC_ListTable(BimpObject::getInstance('bimpdatasync', 'BDS_ReportLine'), 'product', 1, null, 'Notifications des processus', 'far_file-alt');
                $list->addFieldFilterValue('obj_module', 'bimpcore');
                $list->addFieldFilterValue('obj_name', 'Bimp_Product');
                $list->addFieldFilterValue('id_obj', $this->id);
                break;
        }

        if (is_a($list, 'BC_ListTable')) {
            $html .= $list->renderHtml();
        } elseif ($list_type && !$html) {
            $html .= BimpRender::renderAlerts('La liste de type "' . $list_type . '" n\'existe pas');
        } elseif (!$html) {
            $html .= BimpRender::renderAlerts('Type de liste non spécifié');
        }

        return $html;
    }

    public function renderFournPriceInputs()
    {
        $html = '';

        $id_fourn = (int) BimpTools::getPostFieldValue('fp_id_fourn', 0);
        $ref_fourn = BimpTools::getPostFieldValue('fp_ref_fourn', '');
        $price = (float) BimpTools::getPostFieldValue('fp_pa_ht', 0);

        $fourn_html = '';
        if ($id_fourn) {
            $fourn = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Fournisseur', $id_fourn);
            if (BimpObject::objectLoaded($fourn)) {
                $fourn_html = '<strong>Fournisseur:</strong> ' . $fourn->getLink() . '<br/>';
            } else {
                $id_fourn = 0;
                $fourn_html .= BimpRender::renderAlerts('Le fournisseur d\'ID ' . $id_fourn . ' n\'existe pas');
            }
        } else {
            $fourn_html .= BimpRender::renderAlerts('Fournisseur absent');
        }

        $html .= '<input type="hidden" name="fp_id_fourn" value="' . $id_fourn . '"/>';
        $html .= $fourn_html;

        $html .= '<input type="hidden" name="fp_ref_fourn" value="' . $ref_fourn . '"/>';

        if ($ref_fourn) {
            $html .= '<strong>Réf. fournisseur:</strong> ' . $ref_fourn . '<br/>';
        } else {
            $html .= BimpRender::renderAlerts('Référence fournisseur absente');
        }

        $html .= '<input type="hidden" name="fp_pa_ht" value="' . $price . '"/>';
        $html .= '<strong>Prix d\'achat:</strong> ' . BimpTools::displayMoneyValue($price);

        $html .= '<input type="hidden" name="g=has_fourn_price" value="' . ($id_fourn && $ref_fourn ? 1 : 0) . '"/>';
        return $html;
    }

    public function renderFournPricesReports()
    {
        $html = '';

        $fournPrices = BimpCache::getBimpObjectList('bimpcore', 'Bimp_ProductFournisseurPrice', array(
                    'fk_product' => (int) $this->id
        ));

        if (!empty($fournPrices)) {
            $reportLine = BimpObject::getInstance('bimpdatasync', 'BDS_ReportLine');
            $list = new BC_ListTable($reportLine, 'default', 1, null, 'Notifications mise à jour auto des prix fournisseurs', 'fas_comment');
            $list->addFieldFilterValue('obj_module', 'bimpcore');
            $list->addFieldFilterValue('obj_name', 'Bimp_ProductFournisseurPrice');
            $list->addFieldFilterValue('id_obj', array(
                'in' => $fournPrices
            ));
            $html .= $list->renderHtml();
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

    public function validateProduct(&$warnings = array())
    {
        global $user;

        $errors = array();

        if (!$this->isLoaded($errors)) {
            return $errors;
        }

        if ((int) $this->getInitData('validate')) {
            $errors[] = "Ce produit est déjà validé";
        }

        if ($this->getData("fk_product_type") == 0 && !(int) $this->getCurrentFournPriceId(null, true) && !$this->getData('no_fixe_prices')) {
            $errors[] = "Veuillez enregistrer au moins un prix d'achat fournisseur";
        }

        if ((int) $this->getData('fk_product_type') == 1 and
                (int) $this->getData('serialisable') == 1)
            $errors[] = "Un service ne peut pas être sérialisé.";

        if ((int) $this->getData('tosell') != 1)
            $errors[] = "Ce produit n'est pas disponible à la vente";

        if (count($errors)) {
            return $errors;
        }

        $cur_pa_ht = $this->getCurrentPaHt(null, true);
        $datetime = new DateTime();

        $this->set('fk_user_valid', (int) $user->id);
        $this->set('date_valid', $datetime->format('Y-m-d H:i:s'));
        $this->set('cur_pa_ht', $cur_pa_ht);
        $this->set('validate', 1);

        $up_errors = $this->update($warnings, true);

        if (count($up_errors)) {
            $errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec de la mise à jour du produit');
            return $errors;
        }

        // COMMAND
        $commandes_c = $this->getCommandes();
        foreach ($commandes_c as $commande) {
            if ((int) $commande->statut != (int) Commande::STATUS_DRAFT and (int) $commande->statut != (int) Commande::STATUS_CANCELED)
                continue;

            $email_sent = false;
            $list_contact = $commande->liste_contact(-1, 'internal');

            // Search responsible
            foreach ($list_contact as $contact) {
                if ($contact['code'] == 'SALESREPFOLL' and ! $email_sent) {
                    $warnings = BimpTools::merge_array($warnings, $this->sendEmailCommandeValid($commande, $contact['email']));
                    $email_sent = true;
                    break;
                }
            }

            // Search signatory
            if (!$email_sent) {
                foreach ($list_contact as $contact) {
                    $warnings = BimpTools::merge_array($warnings, $this->sendEmailCommandeValid($commande, $contact['email']));
                    $email_sent = true;
                    break;
                }
            }

            // Use main commercial Franck PINERI
            if (!$email_sent) {
                require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
                $userT = new User($this->db->db);
                $userT->fetch((int) 62);
                $warnings = BimpTools::merge_array($warnings, $this->sendEmailCommandeValid($commande, $userT->email));
                $email_sent = true;
                continue;
            }
        }

        // PROPALS
        $propals = $this->getPropals();
        foreach ($propals as $propal) {
            if ((int) $propal->statut != (int) Propal::STATUS_DRAFT and (int) $propal->statut != (int) Propal::STATUS_NOTSIGNED)
                continue;

            $email_sent = false;
            $list_contact = $propal->liste_contact(-1, 'internal');

            // Search responsible
            foreach ($list_contact as $contact) {
                if ($contact['code'] == 'SALESREPFOLL' and ! $email_sent) {
                    $warnings = BimpTools::merge_array($warnings, $this->sendEmailPropalValid($propal, $contact['email']));
                    $email_sent = true;
                    break;
                }
            }

            // Search signatory
            if (!$email_sent) {
                foreach ($list_contact as $contact) {
                    $warnings = BimpTools::merge_array($warnings, $this->sendEmailPropalValid($propal, $contact['email']));
                    $email_sent = true;
                    break;
                }
            }

            // Use main commercial Franck PINERI
            if (!$email_sent) {
                require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
                $userT = new User($this->db->db);
                $userT->fetch((int) 62);
                if (BimpObject::objectLoaded($userT)) {
                    $errors = BimpTools::merge_array($warnings, $this->sendEmailPropalValid($propal, $userT->email));
                    $email_sent = true;
                }
                continue;
            }
        }

        // Ventes en caisse: 
        $ventes = $this->getVentesCaisse();

        foreach ($ventes as $id_vente) {
            $vente = BimpCache::getBimpObjectInstance('bimpcaisse', 'BC_Vente', (int) $id_vente);
            if ((int) $vente->getData('status') != BC_Vente::BC_VENTE_BROUILLON)
                continue;

            if (BimpObject::objectLoaded($vente)) {
                $userT = new User($this->db->db);
                $userT->fetch((int) $vente->getData('id_user_resp'));

                if (BimpObject::objectLoaded($userT)) {
                    $warnings = BimpTools::merge_array($warnings, $this->sendEmailVenteCaisseValid($vente, $userT->email));
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
                    $errors = BimpTools::merge_array($errors, $this->sendEmailCommandeRefuse($commande, $contact['email']));
                    $email_sent = true;
                    break;
                }
            }

            // Search signatory
            if (!$email_sent) {
                foreach ($list_contact as $contact) {
                    $errors = BimpTools::merge_array($errors, $this->sendEmailCommandeRefuse($commande, $contact['email']));
                    $email_sent = true;
                    break;
                }
            }

            // Use main commercial Franck PINERI
            if (!$email_sent) {
                require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
                $userT = new User($this->db->db);
                $userT->fetch((int) 62);
                $errors = BimpTools::merge_array($errors, $this->sendEmailCommandeRefuse($commande, $userT->email));
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
                    $errors = BimpTools::merge_array($errors, $this->sendEmailPropalRefuse($propal, $contact['email']));
                    $email_sent = true;
                    break;
                }
            }

            // Search signatory
            if (!$email_sent) {
                foreach ($list_contact as $contact) {
                    $errors = BimpTools::merge_array($errors, $this->sendEmailPropalRefuse($propal, $contact['email']));
                    $email_sent = true;
                    break;
                }
            }

            // Use main commercial Franck PINERI
            if (!$email_sent) {
                require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
                $userT = new User($this->db->db);
                $userT->fetch((int) 62);
                $errors = BimpTools::merge_array($errors, $this->sendEmailPropalRefuse($commande, $userT->email));
                $email_sent = true;
                continue;
            }
        }

        // Ventes en caisse: 
        $ventes = $this->getVentesCaisse();

        foreach ($ventes as $id_vente) {
            $vente = BimpCache::getBimpObjectInstance('bimpcaisse', 'BC_Vente', (int) $id_vente);
            if (BimpObject::objectLoaded($vente)) {
                $userT = new User($this->db->db);
                $userT->fetch((int) $vente->getData('id_user_resp'));

                if (BimpObject::objectLoaded($userT)) {
                    $errors = BimpTools::merge_array($errors, $this->sendEmailVenteCaisseRefuse($vente, $userT->email));
                }
            }
        }

        return $errors;
    }

    private function sendEmailCommandeValid($commande, $to)
    {
        if (!$to) {
            return;
        }

        $errors = array();
        $subject = 'Produit validé pour la commande ' . $commande->ref;
        $from = 'gle@bimp.fr';
        $msg = 'Bonjour,<br/>Le produit ' . $this->getData('ref') . ' a été validé, la commande ' . $commande->getNomUrl(0);
        $msg .= ' est peut-être validable.';
        if (!mailSyn2($subject, $to, $from, $msg))
            $errors[] = "Envoi email à " . $to . " pour la commande " . $commande->getNomUrl(0) . " impossible.";
        return $errors;
    }

    private function sendEmailPropalValid($propal, $to)
    {
        if (!$to) {
            return array();
        }
        $errors = array();
        $subject = 'Produit validé pour la propale ' . $propal->ref;
        $from = 'gle@bimp.fr';

        $infoClient = "";

        if (isset($propal->socid)) {
            $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $propal->socid);
            if (is_object($client) && $client->isLoaded())
                $infoClient = " du client " . $client->getNomUrl(0, 0, 0, '');
        }

        $msg = 'Bonjour,<br/>Le produit ' . $this->getData('ref') . ' a été validé, la propale ' . $propal->getNomUrl(0) . $infoClient;
        $msg .= ' est peut-être validable.';
        if (!mailSyn2($subject, $to, $from, $msg))
            $errors[] = "Envoi email à " . $to . " pour la propale " . $propal->getNomUrl(0) . " impossible.";
        return $errors;
    }

    private function sendEmailCommandeRefuse($commande, $to)
    {
        if (!$to) {
            return array();
        }

        $errors = array();
        $subject = 'Produit refusé pour la commande ' . $commande->ref;
        $from = 'gle@bimp.fr';
        $msg = 'Bonjour,<br/>Le produit ' . $this->getData('ref') . ' a été refusé, la commande ' . $commande->getNomUrl(0);
        $msg .= ' doit être modifiée.';
        if (!mailSyn2($subject, $to, $from, $msg))
            $errors[] = "Envoi email vers " . $to . " pour la commande " . $commande->getNomUrl(0) . " impossible.";
        return $errors;
    }

    private function sendEmailVenteCaisseValid($vente, $to)
    {
        if (!$to) {
            return array();
        }

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
        if (!$to) {
            return array();
        }

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
        if (!$to) {
            return array();
        }

        $errors = array();
        $subject = 'Produit refusé pour la propale ' . $propal->ref;
        $from = 'gle@bimp.fr';
        $msg = 'Bonjour,<br/>Le produit ' . $this->getData('ref') . ' a été refusé, la propale ' . $propal->getNomUrl(0);
        $msg .= ' doit être modifiée.';
        if (!mailSyn2($subject, $to, $from, $msg))
            $errors[] = "Envoi email vers " . $to . " pour la propale " . $propal->getNomUrl(0) . " impossible.";
        return $errors;
    }

    public function mailValidation($urgent = false)
    {
        global $user;
        if ($urgent) {
            $mail = "XX_Achats@bimp.fr,dev@bimp.fr";
            $msg = 'Bonjour, ' . "\n\n";
            $msg .= 'Le produit ' . $this->getNomUrl(0) . ' a été ajouté à une vente en caisse alors qu\'il n\'est pas validé.' . "\n";
            $msg .= 'Une validation d\'urgence est nécessaire pour finaliser la vente' . "\n\n";
            $msg .= 'Cordialement.';
        } else {
            $mail = "XX_Achats@bimp.fr";
            $msg = "Bonjour " . $user->getNomUrl(0) . "souhaite que vous validiez " . $this->getNomUrl(0) . "<br/>Cordialement";
        }
        if (mailSyn2("Validation produit", $mail, null, $msg)) {
            if ($this->getData('date_ask_valid') == null or $this->getData('date_ask_valid') == '') {
                $datetime = new DateTime();
                $this->updateField('date_ask_valid', $datetime->format('Y-m-d H:i:s'));
            }
            return true;
        }
        return false;
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

        BimpObject::changeBimpObjectId($id_merged_product, $this->id, 'bimpcore', 'Bimp_Product');
//        BimpTools::changeDolObjectId($id_merged_product, $this->id, 'product');
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

    public function correctStocks($id_entrepot, $qty, $movement, $code_move, $label, $origin = '', $id_origin = null)
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            global $user;

            $isBimpOrigin = in_array($origin, self::$bimp_stock_origins);
            if ($this->dol_object->correct_stock($user, $id_entrepot, $qty, $movement, $label, 0, $code_move, (!$isBimpOrigin ? $origin : ''), (!$isBimpOrigin ? $id_origin : null)) <= 0) {
                $msg = 'Echec de la mise à jour du stock pour le produit "' . $this->getRef() . ' - ' . $this->getName() . '" (ID: ' . $this->id . ')';
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), $msg);

                BimpCore::addlog('Echec correction du stock', Bimp_Log::BIMP_LOG_ERREUR, 'stocks', $this, array(
                    'Mouvement'       => (!$movement ? 'Ajout' : 'Retrait') . ' de ' . $qty . ' unité(s)',
                    'Libellé'         => $label,
                    'Code mouvement'  => $code_move,
                    'origine'         => $origin . ($id_origin ? ' #' . $id_origin : ''),
                    'Erreurs produit' => BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object))
                ));
            } else {
                if ($origin && (int) $id_origin) {
                    $result = $this->db->executeS('SELECT rowid FROM ' . MAIN_DB_PREFIX . 'stock_mouvement WHERE inventorycode = \'' . $code_move . '\' ORDER BY rowid DESC LIMIT 1', 'array');
                    if (isset($result[0]['rowid']) && (int) $result[0]['rowid']) {
                        $this->db->update('stock_mouvement', array(
                            'bimp_origin'    => $origin,
                            'bimp_id_origin' => $id_origin
                                ), 'rowid = ' . (int) $result[0]['rowid']);
                    }
                }
            }
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
        $warnings = array();
        $success = 'Produit validé avec succès';

        $errors = $this->validateProduct($warnings);

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionMailValidate($data = array(), &$success = '')
    {
        $this->mailValidation();
        return $errors;
    }

    public function actionMouvement($data = array(), &$success = '')
    {
        global $user;
        return $this->correctStocks($data['id_entrepot'], $data['qty'], $data['sens'], 'mouvement_manuel', 'Mouvement manuel', 'user', $user->id);
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

    public function actionUpdatePrice($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Nouveau prix de vente enregistré avec succès';

        $price_base = (isset($data['price_base']) ? (float) $data['price_base'] : null);
        $tva_tx = (isset($data['tva_tx']) ? (float) $data['tva_tx'] : null);
        $price_base_type = (isset($data['price_base_type']) ? (string) $data['price_base_type'] : '');

        if (!in_array($price_base_type, array('HT', 'TTC'))) {
            $errors[] = 'Base du prix absente ou invalide';
        }
        if (is_null($price_base)) {
            $errors[] = 'Prix de base absent';
        }
        if (is_null($tva_tx)) {
            $errors[] = 'Taux de TVA absent';
        }

        if (!count($errors)) {
            global $user;
            BimpTools::resetDolObjectErrors($this->dol_object);
            if ($this->dol_object->updatePrice($price_base, $price_base_type, $user, $tva_tx) < 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la mise à jour du prix du produit');
            }
        }

        $this->hydrateFromDolObject();

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrides:

    public function validatePost()
    {
        $marque = BimpTools::getValue('marque', '');
        $ref_const = BimpTools::getValue('ref_constructeur', '');
        $mailValid = BimpTools::getValue('mailValid', 0);

        if ($marque && $ref_const) {
            $ref = strtoupper(substr($marque, 0, 3));
            $ref .= '-' . $ref_const;
            $this->set('ref', $ref);
        }
        if ($mailValid)
            $this->mailValidation();

        return parent::validatePost();
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            $id_fourn = (int) BimpTools::getPostFieldValue('fp_id_fourn', 0);
            $ref_fourn = BimpTools::getPostFieldValue('fp_ref_fourn', '');
            $pa_ht = (float) BimpTools::getPostFieldValue('fp_pa_ht', 0);

            if ($id_fourn && $ref_fourn) {
                $fp_errors = array();
                BimpObject::createBimpObject('bimpcore', 'Bimp_ProductFournisseurPrice', array(
                    'fk_product' => (int) $this->id,
                    'fk_soc'     => $id_fourn,
                    'ref_fourn'  => $ref_fourn,
                    'price'      => $pa_ht,
                    'tva_tx'     => (float) $this->getData('tva_tx')
                        ), true, $fp_errors);

                if (count($fp_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($fp_errors, 'Erreurs lors de la création du prix d\'achat fournisseur');
                }
            }
        }

        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $init_price_ht = (float) $this->getInitData('price');
        $new_price_ht = (float) $this->getData('price');
        $init_tva_tx = (float) $this->getInitData('tva_tx');
        $new_tva_tx = (float) $this->getData('tva_tx');
        $updateToSerilisable = ($this->getInitData('serialisable') == 0 && $this->getData('serialisable') == 1);

        $errors = parent::update($warnings, $force_update);

        if (!count($errors)) {
            if ($init_price_ht !== $new_price_ht || $init_tva_tx !== $new_tva_tx) {
                global $user;
                BimpTools::resetDolObjectErrors($this->dol_object);
                if ($this->dol_object->updatePrice($new_price_ht, 'HT', $user, $new_tva_tx) < 0) {
                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la mise à jour du prix du produit');
                }
            }
        }
        if (!count($errors)) {
            if ($updateToSerilisable) {
                $tabClass = array('bimpcommercial' => array("Bimp_FactureLine", "Bimp_FactureFournLine"), "bimpsupport" => array('BS_SavPropalLine'));
                foreach ($tabClass as $module => $classes)
                    foreach ($classes as $class) {
                        $obj = BimpCache::getBimpObjectInstance($module, $class);
                        $joins = array();
                        $joins['dol_line'] = array(
                            'alias' => 'dol_line',
                            'table' => $obj::$dol_line_table,
                            'on'    => 'dol_line.rowid' . ' = a.id_line'
                        );

                        $lines = BimpObject::getBimpObjectObjects($module, $class, array('dol_line.fk_product' => $this->id), null, null, $joins);
                        foreach ($lines as $line)
                            $line->createEquipmentsLines();
                    }
            }
        }

        return $errors;
    }

    public function insertExtraFields()
    {
        return array();
    }

    public function updateExtraFields()
    {
        return array();
    }

    // Overrodes Fields extra: 

    public function deleteExtraFields()
    {
        return array();
    }

    // Méthodes statiques:

    public static function initStockDate($date, $include_shipments_diff = false)
    {
        global $db;
        self::$stockDate = array();
        $sql = $db->query("SELECT `fk_product`,`fk_entrepot`,reel, rowid FROM `" . MAIN_DB_PREFIX . "product_stock`"); // WHERE `fk_product` = ".$this->id);
        while ($ln = $db->fetch_object($sql)) {
            self::$stockDate[$date][$ln->fk_product][$ln->fk_entrepot]['rowid'] = $ln->rowid;
            self::$stockDate[$date][$ln->fk_product][$ln->fk_entrepot]['now'] = $ln->reel;
            self::$stockDate[$date][$ln->fk_product][$ln->fk_entrepot]['stock'] = $ln->reel;
            self::$stockDate[$date][$ln->fk_product][null]['now'] += $ln->reel;
            self::$stockDate[$date][$ln->fk_product][null]['stock'] += $ln->reel;
        }

//        $sql = $db->query("SELECT `fk_product`, `fk_entrepot`, SUM(`value`) as nb FROM `".MAIN_DB_PREFIX."stock_mouvement` WHERE `tms` > STR_TO_DATE('" . $date . "', '%Y-%m-%d') GROUP BY `fk_product`, `fk_entrepot`");
        $sql = $db->query("SELECT `fk_product`, `fk_entrepot`, SUM(`value`) as nb FROM `" . MAIN_DB_PREFIX . "stock_mouvement` WHERE  `datem` > '" . $date . "' GROUP BY `fk_product`, `fk_entrepot`");
        while ($ln = $db->fetch_object($sql)) {
            if (!isset(self::$stockDate[$date][$ln->fk_product][$ln->fk_entrepot]['stock']))
                self::$stockDate[$date][$ln->fk_product][$ln->fk_entrepot]['stock'] = 0;
            if (!isset(self::$stockDate[$date][$ln->fk_product][null]['stock']))
                self::$stockDate[$date][$ln->fk_product][null]['stock'] = 0;

            self::$stockDate[$date][$ln->fk_product][$ln->fk_entrepot]['stock'] -= $ln->nb;
            self::$stockDate[$date][$ln->fk_product][null]['stock'] -= $ln->nb;
        }

        if ($include_shipments_diff) {

            $sql = 'SELECT SUM(bcl.qty_shipped_not_billed) as diff, cl.fk_product, ce.entrepot FROM ' . MAIN_DB_PREFIX . 'bimp_commande_line bcl';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'commandedet cl ON cl.rowid = bcl.id_line';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'commande_extrafields ce ON ce.fk_object = bcl.id_obj';
            $sql .= ' WHERE bcl.qty_shipped_not_billed != 0';
            $sql .= ' GROUP BY cl.fk_product, ce.entrepot';

            $rows = self::getBdb()->executeS($sql, 'array');

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    if (!isset(self::$stockDate[$date][$r['fk_product']][$r['entrepot']]['stock']))
                        self::$stockDate[$date][$r['fk_product']][$r['entrepot']]['stock'] = 0;

                    if (!isset(self::$stockDate[$date][$r['fk_product']][$r['entrepot']]['now']))
                        self::$stockDate[$date][$r['fk_product']][$r['entrepot']]['now'] = 0;

                    if (!isset(self::$stockDate[$date][$r['fk_product']][null]['stock']))
                        self::$stockDate[$date][$r['fk_product']][null]['stock'] = 0;

                    if (!isset(self::$stockDate[$date][$r['fk_product']][null]['now']))
                        self::$stockDate[$date][$r['fk_product']][null]['now'] = 0;

                    self::$stockDate[$date][$r['fk_product']][$r['entrepot']]['stock'] += $r['diff'];
                    self::$stockDate[$date][$r['fk_product']][$r['entrepot']]['now'] += $r['diff'];
                    self::$stockDate[$date][$r['fk_product']][null]['stock'] += $r['diff'];
                    self::$stockDate[$date][$r['fk_product']][null]['now'] += $r['diff'];
                }
            }
        }
    }

    public static function insertStockDateNotZeroProductStock($date)
    {
        global $db;
        $stockDateZero = array();
        $tabNecessaire = array();

        if (isset(self::$stockDate[$date]) && is_array(self::$stockDate[$date])) {
            foreach (self::$stockDate[$date] as $idP => $list) {
                foreach ($list as $idE => $data) {
                    if ($idE > 0) {
                        $tabNecessaire[$idP][$idE] = $data;
                    }
                }
            }
        }

        if (isset(self::$stockShowRoom) && is_array(self::$stockShowRoom)) {
            foreach (self::$stockShowRoom as $idP => $list) {
                foreach ($list as $idE => $stockShowRoom) {
                    if ($idE > 0) {
                        if (!isset($tabNecessaire[$idP][$idE]))
                            $tabNecessaire[$idP][$idE] = array('stock' => 0, 'now' => 0);

                        $tabNecessaire[$idP][$idE]['stockShowRoom'] = $stockShowRoom;
                    }
                }
            }
        }

        foreach ($tabNecessaire as $idP => $list) {
            foreach ($list as $idE => $data) {
                if ($idE > 0) {
                    $tabNecessaire[$idP][$idE] = $data;
                    $asShowRoom = (isset($data['stockShowRoom']) && $data['stockShowRoom'] > 0);
                    $asStockADate = ($data['stock'] != 0);

                    if (($asShowRoom || $asStockADate) && !isset($data['rowid'])) {//On a un stock a date et pas dentre, on ajoute
                        $db->query("INSERT INTO " . MAIN_DB_PREFIX . "product_stock (`fk_product`, `fk_entrepot`, `reel`) VALUES (" . $idP . "," . $idE . ",0)");
                    } elseif (!$asStockADate && !$asShowRoom && isset($data['rowid']) && $data['rowid'] > 0) {//On a pas de stock a date est une entre
                        if ($data['now'] == 0)//on supprime l'entré
                            $db->query("DELETE FROM " . MAIN_DB_PREFIX . "product_stock WHERE `rowid` = " . $data['rowid'] . " AND reel = 0 ");
                        $stockDateZero[] = $data['rowid'];
                    }
                }
            }
        }


        return array("stockDateZero" => $stockDateZero);
    }

    public static function initStockShowRoom()
    {
        global $db;
        self::$stockShowRoom = array();
        $sql = $db->query("SELECT `id_product`, `id_entrepot`, COUNT(*)as nb FROM `" . MAIN_DB_PREFIX . "be_equipment_place` p, " . MAIN_DB_PREFIX . "be_equipment e WHERE position = 1 AND p.id_equipment = e.id AND p.`type` = 5 GROUP BY `id_entrepot`, `id_product`");
        while ($ln = $db->fetch_object($sql)) {
            self::$stockShowRoom[$ln->id_product][$ln->id_entrepot] = $ln->nb;
            self::$stockShowRoom[$ln->id_product][null] += $ln->nb;
        }

        $sql = 'SELECT pp.id_product, pl.id_entrepot, SUM(pp.qty) as nb FROM ' . MAIN_DB_PREFIX . 'be_package_place pl, ' . MAIN_DB_PREFIX . 'be_package_product pp';
        $sql .= ' WHERE pl.position = 1 AND pl.type = 5';
        $sql .= ' AND pl.id_package = pp.id_package';
        $sql .= ' GROUP BY pl.`id_entrepot`, pp.`id_product`';

//        $rows = self::getBdb()->executeS($sql, 'array');
//        echo '<pre>';
//        print_r($rows);
//        exit;

        $sql = $db->query($sql);
        while ($ln = $db->fetch_object($sql)) {
            if (!isset(self::$stockShowRoom[$ln->id_product][$ln->id_entrepot])) {
                self::$stockShowRoom[$ln->id_product][$ln->id_entrepot] = 0;
            }
            if (!isset(self::$stockShowRoom[$ln->id_product][null])) {
                self::$stockShowRoom[$ln->id_product][null] = 0;
            }

            self::$stockShowRoom[$ln->id_product][$ln->id_entrepot] += $ln->nb;
            self::$stockShowRoom[$ln->id_product][null] += $ln->nb;
        }
    }

    private static function initLienShowRoomEntrepot()
    {
        global $db;
        self::$lienShowRoomEntrepot = array();
        $sql = $db->query("SELECT e.rowid as id1, e2.rowid as id2 FROM `" . MAIN_DB_PREFIX . "entrepot` e,`" . MAIN_DB_PREFIX . "entrepot` e2 WHERE e2.ref = CONCAT('D',e.ref)");
        while ($ln = $db->fetch_object($sql)) {
            self::$lienShowRoomEntrepot[$ln->id1] = $ln->id2;
        }
    }

    private static function initVentes($dateMin, $dateMax, $tab_secteur = array(), $exlure_retour = false, $with_factures = false)
    {
        global $db;
        $cache_key = $dateMin . '-' . $dateMax . "-" . implode("/", $tab_secteur) . '-' . (int) $exlure_retour;

        if ($with_factures) {
            $cache_key .= '_with_factures';
        }

        $query = "SELECT l.rowid as id_line, l.fk_facture, l.rang, l.subprice, f.fk_soc, l.fk_product, e.entrepot, l.qty as qty, l.total_ht as total_ht, l.total_ttc as total_ttc";
        $query .= " FROM " . MAIN_DB_PREFIX . "facturedet l, " . MAIN_DB_PREFIX . "facture f, " . MAIN_DB_PREFIX . "facture_extrafields e";
        $query .= " WHERE l.fk_facture = f.rowid AND e.fk_object = f.rowid AND l.fk_product > 0";

        if ($exlure_retour) {
            $query .= " AND l.qty > 0";
        }

        $query .= " AND f.fk_statut > 0";

        if ($dateMin) {
            $query .= " AND f.date_valid >= '" . $dateMin . "'";
        }

        if ($dateMax) {
            $query .= " AND f.date_valid <= '" . $dateMax . "'";
        }

        if (count($tab_secteur) > 0) {
            $query .= " AND e.type IN ('" . implode("','", $tab_secteur) . "')";
        }

        $sql = $db->query($query);

        // Facturés: 
        while ($ln = $db->fetch_object($sql)) {

            $qty = $ln->qty;

            if ($ln->subprice < 0) {
                $qty *= -1;
            }

            // Ventes produit / entrepôt
            if (!isset(self::$ventes[$cache_key][$ln->fk_product][$ln->entrepot])) {
                self::$ventes[$cache_key][$ln->fk_product][$ln->entrepot] = array(
                    'qty'       => 0,
                    'total_ht'  => 0,
                    'total_ttc' => 0
                );

                if ($with_factures) {
                    self::$ventes[$cache_key][$ln->fk_product][$ln->entrepot]['factures'] = array();
                }
            }

            self::$ventes[$cache_key][$ln->fk_product][$ln->entrepot]['qty'] += $ln->qty;
            self::$ventes[$cache_key][$ln->fk_product][$ln->entrepot]['total_ht'] += $ln->total_ht;
            self::$ventes[$cache_key][$ln->fk_product][$ln->entrepot]['total_ttc'] += $ln->total_ttc;

            if ($with_factures) {
                self::$ventes[$cache_key][$ln->fk_product][$ln->entrepot]['factures'][$ln->fk_facture][$ln->id_line] = array(
                    'position' => $ln->rang,
                    'qty'      => $ln->qty
                );
            }


            // Ajout au total produit: 
            if (!isset(self::$ventes[$cache_key][$ln->fk_product][null])) {
                self::$ventes[$cache_key][$ln->fk_product][null] = array(
                    'qty'       => 0,
                    'total_ht'  => 0,
                    'total_ttc' => 0
                );
                if ($with_factures) {
                    self::$ventes[$cache_key][$ln->fk_product][null]['factures'] = array();
                }
            }

            self::$ventes[$cache_key][$ln->fk_product][null]['qty'] += $ln->qty;
            self::$ventes[$cache_key][$ln->fk_product][null]['total_ht'] += $ln->total_ht;
            self::$ventes[$cache_key][$ln->fk_product][null]['total_ttc'] += $ln->total_ttc;

            if ($with_factures) {
                self::$ventes[$cache_key][$ln->fk_product][null]['factures'][$ln->fk_facture][$ln->id_line] = array(
                    'position' => $ln->rang,
                    'qty'      => $ln->qty
                );
            }
        }
    }

    public static function correctAllProductCurPa($echo = false, $echo_errors_only = true)
    {
        $bdb = self::getBdb();
        $rows = $bdb->getRows('product', 'no_fixe_prices = 0', null, 'array', array('rowid', 'cur_pa_ht'), 'rowid', 'desc');

        if (is_array($rows)) {
            BimpObject::loadClass('bimpcore', 'BimpProductCurPa');

            foreach ($rows as $r) {
                $pa_ht = BimpProductCurPa::getProductCurPaAmount((int) $r['rowid']);
                if (!is_null($pa_ht)) {
                    if ((float) $pa_ht !== (float) $r['cur_pa_ht']) {
                        if ($echo && !$echo_errors_only) {
                            echo 'Produit #' . $r['rowid'] . ' (' . $r['cur_pa_ht'] . ' => ' . $pa_ht . ') : ';
                        }

                        if ($bdb->update('product', array(
                                    'cur_pa_ht' => (float) $pa_ht
                                        ), 'rowid = ' . $r['rowid']) <= 0) {
                            if ($echo) {
                                echo '<span class="danger">';
                                if ($echo_errors_only) {
                                    echo 'Produit #' . $r['rowid'] . ' (' . $r['cur_pa_ht'] . ' => ' . $pa_ht . ') : ';
                                }
                                echo '[ECHEC] - ' . $bdb->db->lasterror();
                                echo '</span>';
                                echo '<br/>';
                            }
                        } else if ($echo && !$echo_errors_only) {
                            echo '<span class="success">OK</span><br/>';
                        }
                    }
                }
            }
        }
    }
}
