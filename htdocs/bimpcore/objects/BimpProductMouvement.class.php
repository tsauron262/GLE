<?php

class BimpProductMouvement extends BimpObject
{

    CONST TRANS_IN = 0;
    CONST TRANS_OUT = 1;
    CONST STOCK_OUT = 2;
    CONST STOCK_IN = 3;

    public static $type_mouvement = [
        self::TRANS_IN  => ['label' => 'Transfert entrant', 'classes' => ['success'], 'icon' => 'fas_sign-in-alt'],
        self::TRANS_OUT => ['label' => 'Transfert sortant', 'classes' => ['danger'], 'icon' => 'fas_sign-out-alt'],
        self::STOCK_IN  => ['label' => 'Entrée', 'classes' => ['success'], 'icon' => 'fas_sign-in-alt'],
        self::STOCK_OUT => ['label' => 'Sortie', 'classes' => ['danger'], 'icon' => 'fas_sign-out-alt'],
    ];
    public static $originetypes = array(
        ''               => 'Aucun',
        'facture'        => 'Facture',
        'commande'       => 'Commande',
        'order_supplier' => 'Commande fournisseur',
        'societe'        => 'Client ou fournisseur',
        'user'           => 'Utilisateur',
        'vente_caisse'   => 'Vente en caisse',
        'sav'            => 'SAV',
        'transfert'      => 'Transfert',
        'package'        => 'Package',
        'inventory'      => 'Inventaire',
        'inventory2'     => 'Inventaire',
        'pret'           => 'Pret',
        'inc'            => 'Inconnu'
    );
    public static $current_origin_types_filters = array();

    // Getters booléens: 

    public function isCreatable($force_create = false, &$errors = array())
    {
        // Création directe par cette classe bloquée
        // Toujours passer par Bimp_Product::correctStocks(). 
        return 0;
    }

    // Getters params 

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, &$errors = array(), $excluded = false)
    {
        switch ($field_name) {
            case 'origin_type':
                $in = '';
                $has_emtpy = false;
                $has_inc = false;

                foreach ($values as $value) {
                    if (!$value) {
                        $has_emtpy = true;
                        continue;
                    }
                    if ($value === 'inc') {
                        $has_inc = true;
                        continue;
                    }
                    if (!$excluded) {
                        self::$current_origin_types_filters[] = $value;
                    }

                    $in .= ($in ? ',' : '') . "'" . $value . "'";
                }

                if ($excluded) {
                    if ($in) {
                        $filter = 'a.origintype NOT IN (' . $in . ') AND a.bimp_origin NOT IN (' . $in . ')';
                    }
                    if ($has_emtpy) {
                        $filter .= ($filter ? ' AND ' : '') . 'NOT ((a.origintype = \'\' OR a.origintype IS NULL) AND (a.bimp_origin = \'\' OR a.bimp_origin IS NULL))';
                    }
                    if ($has_inc) {
                        $origintypes = self::$originetypes;
                        unset($origintypes['']);
                        unset($origintypes['inc']);
                        $origintypes = BimpTools::implodeArrayKeys($origintypes, ',', true);

                        $filter .= ($filter ? ' AND ' : '') . 'NOT ((a.origintype != \'\' AND a.origintype IS NOT NULL AND a.origintype NOT IN(' . $origintypes . ')) OR (a.bimp_origin != \'\' AND a.bimp_origin IS NOT NULL AND a.bimp_origin NOT IN (' . $origintypes . ')))';
                    }
                } else {
                    if ($in) {
                        $filter = 'a.origintype IN (' . $in . ') OR a.bimp_origin IN (' . $in . ')';
                    }
                    if ($has_emtpy) {
                        $filter .= ($filter ? ' OR ' : '') . '((a.origintype = \'\' OR a.origintype IS NULL) AND (a.bimp_origin = \'\' OR a.bimp_origin IS NULL))';
                    }
                    if ($has_inc) {
                        $origintypes = self::$originetypes;
                        unset($origintypes['']);
                        unset($origintypes['inc']);
                        $origintypes = BimpTools::implodeArrayKeys($origintypes, ',', true);

                        $filter .= ($filter ? ' AND ' : '') . '((a.origintype != \'\' AND a.origintype IS NOT NULL AND a.origintype NOT IN(' . $origintypes . ')) OR (a.bimp_origin != \'\' AND a.bimp_origin IS NOT NULL AND a.bimp_origin NOT IN (' . $origintypes . ')))';
                    }
                }

                if ($filter) {
                    $filters['origin_type'] = array(
                        'custom' => '(' . $filter . ')'
                    );
                }
                break;

            case 'origin':
                if (!empty(self::$current_origin_types_filters)) {
                    $or_field = array();
                    foreach ($values as $value) {
                        $or_field[] = BC_Filter::getValuePartSqlFilter($value, 'middle');
                    }

                    // Factures: 
                    if (in_array('facture', self::$current_origin_types_filters)) {
                        $joins['origin_facture'] = array(
                            'table' => 'facture',
                            'alias' => 'origin_facture',
                            'on'    => '((a.origintype = \'facture\' and a.fk_origin = origin_facture.rowid) OR (a.bimp_origin = \'facture\' and a.bimp_id_origin = origin_facture.rowid))'
                        );

                        $or_origins['origin_facture.facnumber'] = array(
                            'or_field' => $or_field
                        );
                    }

                    // Commandes: 
                    if (in_array('commande', self::$current_origin_types_filters)) {
                        $joins['origin_commande'] = array(
                            'table' => 'commande',
                            'alias' => 'origin_commande',
                            'on'    => '((a.origintype = \'commande\' and a.fk_origin = origin_commande.rowid) OR (a.bimp_origin = \'commande\' and a.bimp_id_origin = origin_commande.rowid))'
                        );

                        $or_origins['origin_commande.ref'] = array(
                            'or_field' => $or_field
                        );
                    }

                    // Commandes fourn: 
                    if (in_array('order_supplier', self::$current_origin_types_filters)) {
                        $joins['origin_commande_fourn'] = array(
                            'table' => 'commande_fournisseur',
                            'alias' => 'origin_commande_fourn',
                            'on'    => '((a.origintype = \'order_supplier\' and a.fk_origin = origin_commande_fourn.rowid) OR (a.bimp_origin = \'order_supplier\' and a.bimp_id_origin = origin_commande_fourn.rowid))'
                        );

                        $or_origins['origin_commande_fourn.ref'] = array(
                            'or_field' => $or_field
                        );
                    }

                    // Sociétés: 
                    if (in_array('societe', self::$current_origin_types_filters)) {
                        $joins['origin_societe'] = array(
                            'table' => 'societe',
                            'alias' => 'origin_societe',
                            'on'    => '((a.origintype = \'societe\' and a.fk_origin = origin_societe.rowid) OR (a.bimp_origin = \'societe\' and a.bimp_id_origin = origin_societe.rowid))'
                        );

                        $or_origins['origin_societe.code_client'] = array(
                            'or_field' => $or_field
                        );
                        $or_origins['origin_societe.code_fournisseur'] = array(
                            'or_field' => $or_field
                        );
                        $or_origins['origin_societe.nom'] = array(
                            'or_field' => $or_field
                        );
                    }

                    // User: 
                    if (in_array('user', self::$current_origin_types_filters)) {
                        $joins['origin_user'] = array(
                            'table' => 'user',
                            'alias' => 'origin_user',
                            'on'    => '((a.origintype = \'user\' and a.fk_origin = origin_user.rowid) OR (a.bimp_origin = \'user\' and a.bimp_id_origin = origin_user.rowid))'
                        );
                        $or_origins['origin_user.lastname'] = array(
                            'or_field' => $or_field
                        );
                        $or_origins['origin_user.firstname'] = array(
                            'or_field' => $or_field
                        );
                        $or_origins['origin_user.login'] = array(
                            'or_field' => $or_field
                        );
                    }

                    // Ventes en caisse: 
                    if (in_array('vente_caisse', self::$current_origin_types_filters)) {
                        $joins['origin_vente'] = array(
                            'table' => 'bc_vente',
                            'alias' => 'origin_vente',
                            'on'    => '(a.bimp_origin = \'vente_caisse\' and a.bimp_id_origin = origin_vente.id)'
                        );
                        $or_origins['origin_vente.id'] = array(
                            'in' => $values
                        );
                    }

                    // SAV: 
                    if (in_array('sav', self::$current_origin_types_filters)) {
                        $joins['origin_sav'] = array(
                            'table' => 'bs_sav',
                            'alias' => 'origin_sav',
                            'on'    => '(a.bimp_origin = \'sav\' and a.bimp_id_origin = origin_sav.id)'
                        );
                        $or_origins['origin_sav.ref'] = array(
                            'or_field' => $or_field
                        );
                    }

                    // Transferts: 
                    if (in_array('transfert', self::$current_origin_types_filters)) {
                        $joins['origin_transfert'] = array(
                            'table' => 'be_transfer',
                            'alias' => 'origin_transfert',
                            'on'    => '(a.bimp_origin = \'transfert\' and a.bimp_id_origin = origin_transfert.rowid)'
                        );
                        $or_origins['origin_transfert.rowid'] = array(
                            'in' => $values
                        );
                    }

                    // Packages: 
                    if (in_array('package', self::$current_origin_types_filters)) {
                        $joins['origin_package'] = array(
                            'table' => 'be_package',
                            'alias' => 'origin_package',
                            'on'    => '(a.bimp_origin = \'package\' and a.bimp_id_origin = origin_package.id)'
                        );
                        $or_origins['origin_package.ref'] = array(
                            'or_field' => $or_field
                        );
                        $or_origins['origin_package.label'] = array(
                            'or_field' => $or_field
                        );
                    }

                    // Inventaires (1): 
                    if (in_array('inventory', self::$current_origin_types_filters)) {
                        $joins['origin_inventory'] = array(
                            'table' => 'be_inventory',
                            'alias' => 'origin_inventory',
                            'on'    => '(a.bimp_origin = \'inventory\' and a.bimp_id_origin = origin_inventory.rowid)'
                        );
                        $or_origins['origin_inventory.rowid'] = array(
                            'in' => $values
                        );
                    }

                    // Inventaires (2): 
                    if (in_array('inventory2', self::$current_origin_types_filters)) {
                        $joins['origin_inventory2'] = array(
                            'table' => 'bl_inventory_2',
                            'alias' => 'origin_inventory2',
                            'on'    => '(a.bimp_origin = \'inventory2\' and a.bimp_id_origin = origin_inventory2.id)'
                        );
                        $or_origins['origin_inventory.rowid'] = array(
                            'in' => $values
                        );
                    }

                    // Prêts: 
                    if (in_array('pret', self::$current_origin_types_filters)) {
                        $joins['origin_pret'] = array(
                            'table' => 'bs_pret',
                            'alias' => 'origin_pret',
                            'on'    => '(a.bimp_origin = \'pret\' and a.bimp_id_origin = origin_pret.id)'
                        );
                        $or_origins['origin_pret.ref'] = array(
                            'or_field' => $or_field
                        );
                    }

                    if (!empty($or_origins)) {
                        $filters['or_origin'] = array(
                            'or' => $or_origins
                        );
                    }
                    break;
                }
        }

        parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $errors, $excluded);
    }

    // Getters:

    public function getOrigin()
    {
        $origin = $this->getData('bimp_origin');
        if (!$origin) {
            $origin = $this->getData('origintype');
        }

        return $origin;
    }

    public function getIdOrigin()
    {
        if ($this->getData('bimp_origin')) {
            return (int) $this->getData('bimp_id_origin');
        }

        return (int) $this->getData('fk_origin');
    }

    public function getInfosOrigine()
    {



        $objet = '';
        $module = '';
        $label = '';
        $labelReverse = '';
        $modal_view = 'default';
        $only_ref = 1;

        $id_origin = (int) $this->getIdOrigin();
        $origin = $this->getOrigin();

        switch ($origin) {
            case 'facture':
                $objet = 'Bimp_Facture';
                $module = 'bimpcommercial';
                $labelReverse = 'Vente';
                $modal_view = 'full';
                $label = 'Ret Vente';
                break;

            case 'commande':
                $objet = 'Bimp_Commande';
                $module = 'bimpcommercial';
                $labelReverse = 'Vente';
                $label = 'Ret Vente';
                $modal_view = 'full';
                break;

            case 'order_supplier':
                $objet = 'Bimp_CommandeFourn';
                $module = 'bimpcommercial';
                $label = 'Achat';
                $labelReverse = 'Ret Achat';
                $modal_view = 'full';
                break;

            case 'user':
                if (stripos($this->getData('label'), "Emplacement") !== false){
                    $label = 'Immo';
                }
                $objet = 'Bimp_User';
                $module = 'bimpcore';
                $only_ref = 0;
                break;

            case 'societe':
                $objet = 'Bimp_Societe';
                $module = 'bimpcore';
                $labelReverse = 'Vente';
                $label = 'Ret Vente';
                $only_ref = 0;
                break;

            case 'transfert':
                $objet = 'Transfer';
                $module = 'bimptransfer';
                $label = 'Transfert';
                $only_ref = 0;
                break;

            case 'vente_caisse':
                $objet = 'BC_Vente';
                $module = 'bimpcaisse';
                $labelReverse = 'Vente en caisse';
                $label = 'Ret Vente Caisse';
                $only_ref = 0;
                break;

            case 'sav':
                $objet = 'BS_SAV';
                $module = 'bimpsupport';
                $label = 'SAV';
                $only_ref = 1;
                break;

            case 'package':
                $objet = 'BE_Package';
                $module = 'bimpequipment';
                $label = 'Package';
                $only_ref = 1;
                break;

            case 'inventory':
                $objet = 'Inventory';
                $module = 'bimplogistique';
                $label = 'Inventaire';
                $only_ref = 1;
                break;

            case 'inventory2':
                $objet = 'Inventory2';
                $module = 'bimplogistique';
                $label = 'Inventaire';
                $only_ref = 1;
                break;

            case 'pret':
                $objet = 'BS_Pret';
                $module = 'bimpsupport';
                $label = 'Prêt';
                $labelReverse = 'Ret Pret';
                $only_ref = 1;
                break;

            default:
                $origin = '';
                $id_origin = 0;
                break;
        }
        if (stripos($this->getData('label'), "Import 8sens") !== false)
            $label = 'Import 8sens';

        return array(
            'object'       => $objet,
            'module'       => $module,
            'label'        => $label,
            'labelReverse' => $labelReverse,
            'modal_view'   => $modal_view,
            'ref_only'     => $only_ref,
            'origin'       => $origin,
            'id_origin'    => $id_origin
        );
    }

    // Affichage: 

    public function displayOriginMvt()
    {
        $id_origin = (int) $this->getIdOrigin();

        if ($id_origin) {
            $infos = $this->getInfosOrigine();
            if ($infos['object'] && $infos['module']) {
                $instance = BimpCache::getBimpObjectInstance($infos['module'], $infos['object'], $id_origin);

                if ($instance->isLoaded())
                    return $instance->getNomUrl(1, $infos['ref_only'], 1, $infos['modal_view']);
                else
                    return '<span class="danger">' . BimpTools::ucfirst($instance->getLabel('the')) . ' n\'existe plus</span>';
            }

            return '<span class="warning">Object inconnnu</span>';
        }

        return 'Aucun';
    }

    public function displayReasonMvt()
    {
        $infos = $this->getInfosOrigine();
        if ($this->getData('value') < 0 && $infos['labelReverse'] != '')
            $reason = $infos['labelReverse'];
        else
            $reason = $infos['label'];
        if ($reason == '') {
            $reason = 'Inconnue';

            if (stripos($this->getData("label"), "Transfert de stock") !== false || stripos($this->getData("label"), "TR-") === 0)
                $reason = 'Transfert';
            elseif (stripos($this->getData("label"), "Correction du stock") !== false || stripos($this->getData("label"), "tomm") !== false || stripos($this->getData("label"), "correction Auto Stock") !== false || stripos($this->getData("label"), "CORRECTION ") === 0 || stripos($this->getData("label"), "Suppression de l'équipement") === 0 || stripos($this->getData("label"), "Inversion ") === 0 || stripos($this->getData("label"), "Erreur ") === 0)
                $reason = 'CORRECT';
            elseif (stripos($this->getData("label"), "#correction de Facture") === 0)
                if ($this->getData('value') < 0)
                    $reason = 'VENTE';
                else
                    $reason = 'RET VENTE';
            elseif (stripos($this->getData("label"), "SAV") !== false)
                $reason = 'SAV';
            elseif (stripos($this->getData("label"), "Vol") !== false)
                $reason = 'Vol';
            elseif (stripos($this->getData("inventorycode"), 'inventory-id-') === 0 || stripos($this->getData("label"), "Inventaire-") === 0 || stripos($this->getData("label"), "Régul ") === 0 || stripos($this->getData("label"), "Preparation inventaire") === 0)
                $reason = 'Inventaire';
        }

        return $reason;
    }

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

    public function displayId()
    {
        return "<b>#" . $this->id . "</b>";
    }

    public function displayTypeMateriel()
    {
        return ''; // C'est quoi Type matériel ??
    }
    // Traitements: 

    /**
     * TODO implémenter pour équipement
     * ATTENTION cette fonction n'a pas été testé dans entièrement
     */
    public function revertMouvement(&$warnings = array())
    {

        $errors = array();

        BimpObject::loadClass('bimpequipment', 'BE_Package');

        // move
        if (preg_match("/^Ajout au package #(\d+) \- PKG(.+) \- Déplacement de PKG(.+) au package n°(\d+) \- Correction inventaire #(\d+)$/", $this->getData('label'), $m)) {

            $id_package_src = $this->db->getValue('be_package', 'id', 'ref = "PKG' . $m[3] . '"');

            echo 'Déplacement de ' . $id_package_src . ' à ' . $m[1] . $this->getData('fk_product') . '<br/>';

            $errors = BimpTools::merge_array($errors, BE_Package::moveElements($m[1], $id_package_src, array($this->getData('fk_product') => $this->getData('value'))
                                    , array(), $this->getData('inventorycode'), 'Revert', $this->getData('bimp_origin'), $this->getData('bimp_id_origin')));



            // enlève
        } elseif (preg_match("/Ajout au package #(\d+)/", $this->getData('label'), $m)) {
            echo 'Retrait de ' . $m[1] . $this->getData('fk_product') . '<br/>';
            $package = BimpObject::getBimpObjectInstance('bimpequipment', 'BE_Package', (int) $m[1]);
            $errors = BimpTools::merge_array($errors, $package->addProduct($this->getData('fk_product'), $this->getData('value'), $this->getData('fk_entrepot'), $warnings, $this->getData('inventorycode'), 'Revert ' . $this->getData('label'), $this->getData('bimp_origin'), $this->getData('bimp_id_origin')));
        } /* elseif(preg_match("/Retrait du package #(\d+)/", $this->getData('label'), $m)) {

          //            print_r($m);

          //            $package = BimpObject::getBimpObjectInstance('bimpequipment', 'BE_Package', (int) $m[1]);
          //            $package->addProduct($this->getData('fk_product'), $this->getData('value'), 0, $warnings,
          //                    $this->getData('inventorycode'), 'Revert ' . $this->getData('label'), $this->getData('bimp_origin'), $this->getData('bimp_id_origin'));
         */
//        } elseif((int) $this->getData('fk_entrepot') > 0) {
//            
//            echo ' ATTENTION ' . $this->getData('label') . '<br/>';
//            
//        } 
        else {

            echo ' ATTENTION ' . $this->getData('label') . ' n\'est pas géré !<br/>';

//            die('"Type inconnue');
        }

        if (!empty($errors))
            print_r($errors);
    }

    // Méthodes statiques: 

    public static function checkMouvements($date_min = '', $date_max = '', $echo = false, $correct = false)
    {
        $errors = array();

        if (!$date_min) {
            $errors[] = 'Date min absente';
        }

        if (!$date_max) {
            $errors[] = 'Date max absente';
        }

        if (!count($errors)) {
            $bdb = self::getBdb();

            $rows = $bdb->getRows('stock_mouvement', 'datem BETWEEN \'' . $date_min . '\' AND \'' . $date_max . '\'', null, 'array', array(
                'rowid', 'fk_product', 'fk_entrepot', 'value', 'type_mouvement', 'label', 'inventorycode'
                    ), 'datem', 'desc');

            $done = array();
            if (is_array($rows)) {
                foreach ($rows as $r) {
                    if (in_array((int) $r['rowid'], $done)) {
                        continue;
                    }

                    $where = 'fk_product = ' . $r['fk_product'];
                    $where .= ' AND fk_entrepot = ' . $r['fk_entrepot'];
                    $where .= ' AND value = ' . $r['value'];
                    $where .= ' AND type_mouvement = ' . $r['type_mouvement'];
                    $where .= ' AND label = \'' . $r['label'] . '\'';
                    $where .= ' AND inventorycode = \'' . $r['inventorycode'] . '\'';

                    if (!empty($done)) {
                        $where .= ' AND rowid NOT IN (' . implode(',', $done) . ')';
                    }

                    $doublons = $bdb->getRows('stock_mouvement', $where, null, 'array', array('rowid', 'datem', 'label', 'inventorycode'), 'datem', 'asc');

                    if (is_array($doublons) && count($doublons) > 1) {
                        $cancels = array();

                        foreach ($doublons as $mvt) {
                            $done[] = $mvt['rowid'];
                        }

                        if (preg_match('/^CMDF(\d+)_LN(\d+)_RECEP(\d+)$/', $r['inventorycode'], $matches)) {
                            $serial = '';
                            if (preg_match('/^.+serial: "(.+)"$/', $r['label'], $matches2)) {
                                $serial = $matches2[1];
                            }

                            if ($serial) {
                                $where = 'fk_product = ' . $r['fk_product'];
                                $where .= ' AND fk_entrepot = ' . $r['fk_entrepot'];
                                $where .= ' AND inventorycode LIKE \'EQ%_SUPPR\'';
                                $where .= ' AND label LIKE \'%' . $serial . '\'';

                                // Recherche suppr. équipement: 
                                $cancels = $bdb->getRows('stock_mouvement', $where, null, 'array', array('rowid'));

                                if (!count($cancels) && preg_match('/^S(.+)/', $serial, $matches3)) {
                                    $serial = $matches3[1];
                                    $where = 'fk_product = ' . $r['fk_product'];
                                    $where .= ' AND fk_entrepot = ' . $r['fk_entrepot'];
                                    $where .= ' AND inventorycode LIKE \'EQ%_SUPPR\'';
                                    $where .= ' AND label LIKE \'%' . $serial . '\'';

                                    // Recherche suppr. équipement: 
                                    $cancels = $bdb->getRows('stock_mouvement', $where, null, 'array', array('rowid'));
                                }
                            } else {
                                $where = 'fk_product = ' . $r['fk_product'];
                                $where .= ' AND fk_entrepot = ' . $r['fk_entrepot'];
                                $where .= ' AND (inventorycode = \'ANNUL_' . $r['inventorycode'] . '\' OR inventorycode = \'ANNUL_CMDF_' . $matches[1] . '_LN_' . $matches[2] . '_RECEP_' . $matches[3] . '\')';

                                $cancels = $bdb->getRows('stock_mouvement', $where, null, 'array', array('rowid'));
                            }

                            $diff = count($doublons) - count($cancels);
                            if ((int) $diff !== 1) {
                                echo 'MVT #' . $r['rowid'] . '(' . $r['inventorycode'] . '): ' . count($doublons) . ' doublons - ' . count($cancels) . ' annul. <br/>';
                            }
                        }
                    }

                    if (count($doublons) > 1) {
//                        if ($echo) {
//                            echo 'PROD #' . $r['fk_product'] . ' - Entrepôt #' . $r['fk_entrepot'] . ' - Code "' . $r['inventorycode'] . '": ' . count($doublons) . ' entrées<br/>';
//                        }
//
//                        if ($echo) {
//                            $dt = new DateTime($mvt['datem']);
//                            echo ' - ' . $mvt['rowid'] . ' ' . $dt->format('d / m / Y H:i:s') . '<br/>';
//                        }
//
//                        if ($correct && (int) $r['rowid'] !== (int) $mvt['rowid']) {
//                            // todo...
//                        }
                    }
                }
            } else {
                $errors[] = 'Aucun mouvement trouvé pour ces dates';
            }
        }

        if (count($errors)) {
            if ($echo) {
                echo BimpRender::renderAlerts($errors);
            }
        }

        return $errors;
    }
}
