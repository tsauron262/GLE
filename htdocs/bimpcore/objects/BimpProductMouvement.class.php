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

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, $main_alias = 'a', &$errors = array(), $excluded = false)
    {
        switch ($field_name) {
            case 'origin_type':
                $filter = '';
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
                        $filter = $main_alias . '.origintype NOT IN (' . $in . ') AND ' . $main_alias . '.bimp_origin NOT IN (' . $in . ')';
                    }
                    if ($has_emtpy) {
                        $filter .= ($filter != '' ? ' AND ' : '') . 'NOT ((' . $main_alias . '.origintype = \'\' OR ' . $main_alias . '.origintype IS NULL) AND (' . $main_alias . '.bimp_origin = \'\' OR ' . $main_alias . '.bimp_origin IS NULL))';
                    }
                    if ($has_inc) {
                        $origintypes = self::$originetypes;
                        unset($origintypes['']);
                        unset($origintypes['inc']);
                        $origintypes = BimpTools::implodeArrayKeys($origintypes, ',', true);

                        $filter .= ($filter != '' ? ' AND ' : '') . 'NOT ((' . $main_alias . '.origintype != \'\' AND ' . $main_alias . '.origintype IS NOT NULL AND ' . $main_alias . '.origintype NOT IN(' . $origintypes . ')) OR (' . $main_alias . '.bimp_origin != \'\' AND ' . $main_alias . '.bimp_origin IS NOT NULL AND ' . $main_alias . '.bimp_origin NOT IN (' . $origintypes . ')))';
                    }
                } else {
                    if ($in) {
                        $filter = $main_alias . '.origintype IN (' . $in . ') OR ' . $main_alias . '.bimp_origin IN (' . $in . ')';
                    }
                    if ($has_emtpy) {
                        $filter .= ($filter != '' ? ' OR ' : '') . '((' . $main_alias . '.origintype = \'\' OR ' . $main_alias . '.origintype IS NULL) AND (' . $main_alias . '.bimp_origin = \'\' OR ' . $main_alias . '.bimp_origin IS NULL))';
                    }
                    if ($has_inc) {
                        $origintypes = self::$originetypes;
                        unset($origintypes['']);
                        unset($origintypes['inc']);
                        $origintypes = BimpTools::implodeArrayKeys($origintypes, ',', true);

                        $filter .= ($filter != '' ? ' AND ' : '') . '((' . $main_alias . '.origintype != \'\' AND ' . $main_alias . '.origintype IS NOT NULL AND ' . $main_alias . '.origintype NOT IN(' . $origintypes . ')) OR (' . $main_alias . '.bimp_origin != \'\' AND ' . $main_alias . '.bimp_origin IS NOT NULL AND ' . $main_alias . '.bimp_origin NOT IN (' . $origintypes . ')))';
                    }
                }

                if ($filter) {
                    $filters[$main_alias . 'origin_type'] = array(
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
                        $of_alias = $main_alias . '___origin_facture';
                        $joins[$of_alias] = array(
                            'table' => 'facture',
                            'alias' => $of_alias,
                            'on'    => '((' . $main_alias . '.origintype = \'facture\' and ' . $main_alias . '.fk_origin = ' . $of_alias . '.rowid) OR (' . $main_alias . '.bimp_origin = \'facture\' and ' . $main_alias . '.bimp_id_origin = ' . $of_alias . '.rowid))'
                        );

                        $or_origins[$of_alias . '.ref'] = array(
                            'or_field' => $or_field
                        );
                    }

                    // Commandes: 
                    if (in_array('commande', self::$current_origin_types_filters)) {
                        $oc_alias = $main_alias . '___origin_commande';
                        $joins[$oc_alias] = array(
                            'table' => 'commande',
                            'alias' => $oc_alias,
                            'on'    => '((' . $main_alias . '.origintype = \'commande\' and ' . $main_alias . '.fk_origin = ' . $oc_alias . '.rowid) OR (' . $main_alias . '.bimp_origin = \'commande\' and ' . $main_alias . '.bimp_id_origin = ' . $oc_alias . '.rowid))'
                        );

                        $or_origins[$oc_alias . '.ref'] = array(
                            'or_field' => $or_field
                        );
                    }

                    // Commandes fourn: 
                    if (in_array('order_supplier', self::$current_origin_types_filters)) {
                        $ocf_alias = $main_alias . '___origin_commande_fourn';
                        $joins[$ocf_alias] = array(
                            'table' => 'commande_fournisseur',
                            'alias' => $ocf_alias,
                            'on'    => '((' . $main_alias . '.origintype = \'order_supplier\' and ' . $main_alias . '.fk_origin = ' . $ocf_alias . '.rowid) OR (' . $main_alias . '.bimp_origin = \'order_supplier\' and ' . $main_alias . '.bimp_id_origin = ' . $ocf_alias . '.rowid))'
                        );

                        $or_origins[$ocf_alias . '.ref'] = array(
                            'or_field' => $or_field
                        );
                    }

                    // Sociétés: 
                    if (in_array('societe', self::$current_origin_types_filters)) {
                        $soc_alias = $main_alias . '___origin_societe';
                        $joins[$soc_alias] = array(
                            'table' => 'societe',
                            'alias' => $soc_alias,
                            'on'    => '((' . $main_alias . '.origintype = \'societe\' and ' . $main_alias . '.fk_origin = ' . $soc_alias . '.rowid) OR (' . $main_alias . '.bimp_origin = \'societe\' and ' . $main_alias . '.bimp_id_origin = ' . $soc_alias . '.rowid))'
                        );

                        $or_origins[$soc_alias . '.code_client'] = array(
                            'or_field' => $or_field
                        );
                        $or_origins[$soc_alias . '.code_fournisseur'] = array(
                            'or_field' => $or_field
                        );
                        $or_origins[$soc_alias . '.nom'] = array(
                            'or_field' => $or_field
                        );
                    }

                    // User: 
                    if (in_array('user', self::$current_origin_types_filters)) {
                        $user_alias = $main_alias . '___origin_user';
                        $joins[$user_alias] = array(
                            'table' => 'user',
                            'alias' => $user_alias,
                            'on'    => '((' . $main_alias . '.origintype = \'user\' and ' . $main_alias . '.fk_origin = ' . $user_alias . '.rowid) OR (' . $main_alias . '.bimp_origin = \'user\' and ' . $main_alias . '.bimp_id_origin = ' . $user_alias . '.rowid))'
                        );
                        $or_origins[$user_alias . '.lastname'] = array(
                            'or_field' => $or_field
                        );
                        $or_origins[$user_alias . '.firstname'] = array(
                            'or_field' => $or_field
                        );
                        $or_origins[$user_alias . '.login'] = array(
                            'or_field' => $or_field
                        );
                    }

                    // Ventes en caisse: 
                    if (in_array('vente_caisse', self::$current_origin_types_filters)) {
                        $ov_alias = $main_alias . '___origin_vente';
                        $joins[$ov_alias] = array(
                            'table' => 'bc_vente',
                            'alias' => $ov_alias,
                            'on'    => '(' . $main_alias . '.bimp_origin = \'vente_caisse\' and ' . $main_alias . '.bimp_id_origin = ' . $ov_alias . '.id)'
                        );
                        $or_origins[$ov_alias . '.id'] = array(
                            'in' => $values
                        );
                    }

                    // SAV: 
                    if (in_array('sav', self::$current_origin_types_filters)) {
                        $sav_alias = $main_alias . '___origin_sav';
                        $joins[$sav_alias] = array(
                            'table' => 'bs_sav',
                            'alias' => $sav_alias,
                            'on'    => '(' . $main_alias . '.bimp_origin = \'sav\' and ' . $main_alias . '.bimp_id_origin = ' . $sav_alias . '.id)'
                        );
                        $or_origins['origin_sav.ref'] = array(
                            'or_field' => $or_field
                        );
                    }

                    // Transferts: 
                    if (in_array('transfert', self::$current_origin_types_filters)) {
                        $ot_alias = $main_alias . '___origin_transfert';
                        $joins[$ot_alias] = array(
                            'table' => 'be_transfer',
                            'alias' => $ot_alias,
                            'on'    => '(' . $main_alias . '.bimp_origin = \'transfert\' and ' . $main_alias . '.bimp_id_origin = ' . $ot_alias . '.rowid)'
                        );
                        $or_origins[$ot_alias . '.rowid'] = array(
                            'in' => $values
                        );
                    }

                    // Packages: 
                    if (in_array('package', self::$current_origin_types_filters)) {
                        $op_alias = $main_alias . '___origin_package';
                        $joins[$op_alias] = array(
                            'table' => 'be_package',
                            'alias' => $op_alias,
                            'on'    => '(' . $main_alias . '.bimp_origin = \'package\' and ' . $main_alias . '.bimp_id_origin = ' . $op_alias . '.id)'
                        );
                        $or_origins[$op_alias . '.ref'] = array(
                            'or_field' => $or_field
                        );
                        $or_origins[$op_alias . '.label'] = array(
                            'or_field' => $or_field
                        );
                    }

                    // Inventaires (1): 
                    if (in_array('inventory', self::$current_origin_types_filters)) {
                        $ov_alias = $main_alias . '___origin_inventory';
                        $joins[$ov_alias] = array(
                            'table' => 'be_inventory',
                            'alias' => $ov_alias,
                            'on'    => '(' . $main_alias . '.bimp_origin = \'inventory\' and ' . $main_alias . '.bimp_id_origin = ' . $ov_alias . '.rowid)'
                        );
                        $or_origins[$ov_alias . '.rowid'] = array(
                            'in' => $values
                        );
                    }

                    // Inventaires (2): 
                    if (in_array('inventory2', self::$current_origin_types_filters)) {
                        $ov2_alias = $main_alias . '___origin_inventory2';
                        $joins[$ov2_alias] = array(
                            'table' => 'bl_inventory_2',
                            'alias' => $ov2_alias,
                            'on'    => '(' . $main_alias . '.bimp_origin = \'inventory2\' and ' . $main_alias . '.bimp_id_origin = ' . $ov2_alias . '.id)'
                        );
                        $or_origins[$ov2_alias . '.rowid'] = array(
                            'in' => $values
                        );
                    }

                    // Prêts: 
                    if (in_array('pret', self::$current_origin_types_filters)) {
                        $op_alias = $main_alias . '___origin_pret';
                        $joins[$op_alias] = array(
                            'table' => 'bs_pret',
                            'alias' => $op_alias,
                            'on'    => '(' . $main_alias . '.bimp_origin = \'pret\' and ' . $main_alias . '.bimp_id_origin = ' . $op_alias . '.id)'
                        );
                        $or_origins[$op_alias . '.ref'] = array(
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

        parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $main_alias, $errors, $excluded);
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
                if (stripos($this->getData('label'), "Emplacement") !== false) {
                    $label = 'Manuel';
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

                if (is_object($instance) && $instance->isLoaded())
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

        echo '<br/>mv ' . $this->id . '<br/>';
        $errors = array();

        BimpObject::loadClass('bimpequipment', 'BE_Package');

        // move
        if (preg_match("/^Ajout au package #(\d+) \- PKG(.+) \- Déplacement de PKG(.+) au package n°(\d+) \- Correction inventaire #(\d+)$/", $this->getData('label'), $m)) {

            $id_package_src = $this->db->getValue('be_package', 'id', 'ref = "PKG' . $m[3] . '"');

            echo 'Déplacement de ' . $id_package_src . ' à ' . $m[1] . $this->getData('fk_product') . '<br/>';

            $errors = BimpTools::merge_array($errors, BE_Package::moveElements($m[1], $id_package_src, array($this->getData('fk_product') => $this->getData('value'))
                                    , array(), $this->getData('inventorycode'), 'Revert', $this->getData('bimp_origin'), $this->getData('bimp_id_origin')));

            // enlève
        } elseif (preg_match("/^Ajout au package #(\d+)/", $this->getData('label'), $m)) {
            if ($this->getData('value') < 0) {//il est dans un package hors stock
                $package = BimpObject::getBimpObjectInstance('bimpequipment', 'BE_Package', (int) $m[1]);
                $errors = BimpTools::merge_array($errors, $package->addProduct($this->getData('fk_product'), $this->getData('value'), $this->getData('fk_entrepot'), $warnings, $this->getData('inventorycode'), 'Revert ' . $this->id . ' | ' . $this->getData('label'), $this->getData('bimp_origin'), $this->getData('bimp_id_origin')));
            } else {
                $package = BimpObject::getBimpObjectInstance('bimpequipment', 'BE_Package', (int) $m[1]);
                $errors = BimpTools::merge_array($errors, $package->addProduct($this->getData('fk_product'), -$this->getData('value'), -1, $warnings, $this->getData('inventorycode'), 'Revert ' . $this->id . ' | ' . $this->getData('label'), $this->getData('bimp_origin'), $this->getData('bimp_id_origin')));
            }

            echo 'Retrait de ' . $m[1] . ' ' . $this->getData('fk_product') . '<br/>';
        } elseif (preg_match("/^Retrait du package #(\d+)/", $this->getData('label'), $m)) {
            if ($this->getData('value') < 0) {//package en stock
                echo 'Ajout de ' . $this->getData('value') . ' dans pkg ' . $m[1] . ' ' . $this->getData('fk_product') . '<br/>';
                $package = BimpObject::getBimpObjectInstance('bimpequipment', 'BE_Package', (int) $m[1]);
                $errors = BimpTools::merge_array($errors, $package->addProduct($this->getData('fk_product'), -$this->getData('value'), -1, $warnings, $this->getData('inventorycode'), 'Revert ' . $this->id . ' | ' . $this->getData('label'), $this->getData('bimp_origin'), $this->getData('bimp_id_origin')));
            } else {//package hors stock
                echo 'Ajout de ' . $this->getData('value') . ' dans pkg ' . $m[1] . ' ' . $this->getData('fk_product') . '<br/>';
                $package = BimpObject::getBimpObjectInstance('bimpequipment', 'BE_Package', (int) $m[1]);
                $errors = BimpTools::merge_array($errors, $package->addProduct($this->getData('fk_product'), $this->getData('value'), $this->getData('fk_entrepot'), $warnings, $this->getData('inventorycode'), 'Revert ' . $this->id . ' | ' . $this->getData('label'), $this->getData('bimp_origin'), $this->getData('bimp_id_origin')));
            }
            //            print_r($m);
            //            $package = BimpObject::getBimpObjectInstance('bimpequipment', 'BE_Package', (int) $m[1]);
            //            $package->addProduct($this->getData('fk_product'), $this->getData('value'), 0, $warnings,
            //                    $this->getData('inventorycode'), 'Revert ' . $this->getData('label'), $this->getData('bimp_origin'), $this->getData('bimp_id_origin'));
//        } elseif((int) $this->getData('fk_entrepot') > 0) {
//            
//            echo ' ATTENTION ' . $this->getData('label') . '<br/>';
//            
        } else {

            echo ' ATTENTION mv id ' . $this->id . ' | ' . $this->getData('label') . ' n\'est pas géré !<br/>';

//            die('"Type inconnue');
        }

        if (!empty($errors))
            print_r($errors);
    }

    // Méthodes statiques: 

    public static function checkMouvements($date_min = '', $date_max = '', $echo = false, $id_product = 0)
    {
        $html = '';
        $errors = array();

        if (!$date_min) {
            $errors[] = 'Date min absente';
        }

        if (!$date_max) {
            $date_max = date('Y-m-d 23:59:59');
        }

        if (!count($errors)) {
            $bdb = self::getBdb();

            $sql = 'SELECT a.* FROM `llx_stock_mouvement` a WHERE
a.datem >= \'' . $date_min . ' 00:00:00\'';

            if ($id_product) {
                $sql .= ' AND a.fk_product = ' . $id_product;
            } else {
                $sql .= ' AND (
SELECT COUNT(DISTINCT b.rowid) FROM `llx_stock_mouvement` b 
WHERE b.rowid != a.rowid 
AND b.fk_product = a.fk_product 
AND b.fk_entrepot = a.fk_entrepot
AND b.`type_mouvement` = a.`type_mouvement`
AND b.`value` = a.`value`
AND b.label = a.label
AND (b.`inventorycode` LIKE \'CMDF%_LN%_RECEP%\' OR b.`inventorycode` LIKE \'CO%_EXP%\')
) > 0';
            }

            $rows = $bdb->executeS($sql, 'array');
            $done = array();
            if (is_null($rows)) {
                $errors[] = 'FAIL - ' . $bdb->err();
            } elseif (count($rows)) {
                $html .= '<h5>' . count($rows) . ' ligne(s) à traiter</h5>';
                $html .= '<table class="bimp_list_table">';
                $html .= '<tbody>';

                foreach ($rows as $r) {
                    $row_errors = array();
                    if (in_array((int) $r['rowid'], $done)) {
                        continue;
                    }

                    $where = 'rowid != ' . $r['rowid'];
                    $where .= ' AND fk_product = ' . $r['fk_product'];
                    $where .= ' AND fk_entrepot = ' . $r['fk_entrepot'];
                    $where .= ' AND value = ' . $r['value'];
                    $where .= ' AND type_mouvement = ' . $r['type_mouvement'];
                    $where .= ' AND label = \'' . $r['label'] . '\'';
                    $where .= ' AND inventorycode = \'' . $r['inventorycode'] . '\'';

                    if (!empty($done)) {
                        $where .= ' AND rowid NOT IN (' . implode(',', $done) . ')';
                    }

                    $doublons = $bdb->getRows('stock_mouvement', $where, null, 'array', null, 'datem', 'asc');

                    if (is_null($doublons)) {
                        $row_errors[] = 'FAIL DOUBLONS - ' . $bdb->err();
                    } elseif (count($doublons) || $id_product) {
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
//                                $where = 'fk_product = ' . $r['fk_product'];
//                                $where .= ' AND fk_entrepot = ' . $r['fk_entrepot'];
//                                $where .= ' AND inventorycode LIKE \'EQ%_SUPPR\'';
//                                $where .= ' AND label LIKE \'%' . $serial . '\'';
//
//                                // Recherche suppr. équipement: 
//                                $cancels = $bdb->getRows('stock_mouvement', $where, null, 'array', null);
//
//                                if (is_null($cancels)) {
//                                    $row_errors[] = 'FAIL CANCELS 1 - ' . $bdb->err();
//                                } elseif (!count($cancels) && preg_match('/^S(.+)/', $serial, $matches3)) {
//                                    $serial = $matches3[1];
//                                    $where = 'fk_product = ' . $r['fk_product'];
//                                    $where .= ' AND fk_entrepot = ' . $r['fk_entrepot'];
//                                    $where .= ' AND inventorycode LIKE \'EQ%_SUPPR\'';
//                                    $where .= ' AND label LIKE \'%' . $serial . '\'';
//
//                                    // Recherche suppr. équipement: 
//                                    $cancels = $bdb->getRows('stock_mouvement', $where, null, 'array', null);
//
//                                    if (is_null($cancels)) {
//                                        $row_errors[] = 'FAIL CANCELS 2 - ' . $bdb->err();
//                                    }
//                                }

                                $where = 'fk_product = ' . $r['fk_product'];
                                $where .= ' AND fk_entrepot = ' . $r['fk_entrepot'];
                                $where .= ' AND inventorycode = \'ANNUL_' . $r['inventorycode'] . '\'';
                                $where .= ' AND label LIKE \'%serial: ' . $serial . '\'';

                                // Recherche suppr. équipement: 
                                $cancels = $bdb->getRows('stock_mouvement', $where, null, 'array', null);
                                if (is_null($cancels)) {
                                    $row_errors[] = 'FAIL CANCELS 1 - ' . $bdb->err();
                                } elseif (!count($cancels) && preg_match('/^S(.+)/', $serial, $matches3)) {
                                    $serial = $matches3[1];
                                    $where = 'fk_product = ' . $r['fk_product'];
                                    $where .= ' AND fk_entrepot = ' . $r['fk_entrepot'];
                                    $where .= ' AND inventorycode = \'ANNUL_' . $r['inventorycode'] . '\'';
                                    $where .= ' AND label LIKE \'%serial: ' . $serial . '\'';

                                    // Recherche suppr. équipement: 
                                    $cancels = $bdb->getRows('stock_mouvement', $where, null, 'array', null);

                                    if (is_null($cancels)) {
                                        $row_errors[] = 'FAIL CANCELS 2 - ' . $bdb->err();
                                    }
                                }
                            } else {
                                $where = 'fk_product = ' . $r['fk_product'];
                                $where .= ' AND fk_entrepot = ' . $r['fk_entrepot'];
                                $where .= ' AND (inventorycode = \'ANNUL_' . $r['inventorycode'] . '\' OR inventorycode = \'ANNUL_CMDF_' . $matches[1] . '_LN_' . $matches[2] . '_RECEP_' . $matches[3] . '\')';

                                $cancels = $bdb->getRows('stock_mouvement', $where, null, 'array', null);

                                if (is_null($cancels)) {
                                    $row_errors[] = 'FAIL CANCELS 3 - ' . $bdb->err();
                                }
                            }
                        } elseif (preg_match('/^CO(\d+)_EXP(\d+)$/', $r['inventorycode'], $matches)) {
                            $serial = '';
                            if (preg_match('/^.+serial: "(.+)"$/', $r['label'], $matches2)) {
                                $serial = $matches2[1];
                            }

                            if ($serial) {
                                $where = 'fk_product = ' . $r['fk_product'];
                                $where .= ' AND fk_entrepot = ' . $r['fk_entrepot'];
                                $where .= ' AND inventorycode = \'' . $r['inventorycode'] . '_ANNUL\'';
                                $where .= ' AND label LIKE \'%serial: "' . $serial . '"\'';

                                // Recherche suppr. équipement: 
                                $cancels = $bdb->getRows('stock_mouvement', $where, null, 'array', null);
                                if (is_null($cancels)) {
                                    $row_errors[] = 'FAIL CANCELS 4 - ' . $bdb->err();
                                } elseif (!count($cancels) && preg_match('/^S(.+)/', $serial, $matches3)) {
                                    $serial = $matches3[1];
                                    $where = 'fk_product = ' . $r['fk_product'];
                                    $where .= ' AND fk_entrepot = ' . $r['fk_entrepot'];
                                    $where .= ' AND inventorycode = \'' . $r['inventorycode'] . '_ANNUL\'';
                                    $where .= ' AND label LIKE \'%serial: "' . $serial . '"\'';

                                    // Recherche suppr. équipement: 
                                    $cancels = $bdb->getRows('stock_mouvement', $where, null, 'array', null);

                                    if (is_null($cancels)) {
                                        $row_errors[] = 'FAIL CANCELS 5 - ' . $bdb->err();
                                    }
                                }
                            } else {
                                $where = 'fk_product = ' . $r['fk_product'];
                                $where .= ' AND fk_entrepot = ' . $r['fk_entrepot'];
                                $where .= ' AND inventorycode = \'' . $r['inventorycode'] . '_ANNUL\'';

                                $cancels = $bdb->getRows('stock_mouvement', $where, null, 'array', null);

                                if (is_null($cancels)) {
                                    $row_errors[] = 'FAIL CANCELS 6 - ' . $bdb->err();
                                }
                            }
                        }

                        $diff = count($doublons) - count($cancels);
                        if (($diff != 0 && $diff != -1) || $id_product) {
                            $prod = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $r['fk_product']);
                            $html .= '<tr>';
                            $html .= '<td>' . (BimpObject::objectLoaded($prod) ? $prod->getLink() : 'Prod #' . $r['fk_product']) . '<br/>' . count($doublons) . ' doublon(s) - ' . count($cancels) . ' annulations</td>';
                            $html .= '<td>';
                            $html .= '<table>';
                            $html .= '<tbody>';
                            $html .= '<tr>';
                            $html .= '<td>MVT #' . $r['rowid'] . '</td>';
                            $html .= '<td>' . $r['inventorycode'] . '</td>';
                            $html .= '<td>' . $r['label'] . '</td>';
                            $html .= '<td><span class="' . self::$type_mouvement[(int) $r['type_mouvement']]['classes'][0] . '">' . self::$type_mouvement[(int) $r['type_mouvement']]['label'] . '</span></td>';
                            $html .= '<td>' . $r['value'] . '</td>';
                            $html .= '</tr>';

                            if (count($doublons)) {
                                foreach ($doublons as $d) {
                                    $html .= '<tr>';
                                    $html .= '<td>MVT #' . $d['rowid'] . '</td>';
                                    $html .= '<td>' . $d['inventorycode'] . '</td>';
                                    $html .= '<td>' . $d['label'] . '</td>';
                                    $html .= '<td><span class="' . self::$type_mouvement[(int) $d['type_mouvement']]['classes'][0] . '">' . self::$type_mouvement[(int) $d['type_mouvement']]['label'] . '</span></td>';
                                    $html .= '<td>' . $d['value'] . '</td>';
                                    $html .= '</tr>';
                                }
                            }

                            if (count($cancels)) {
                                foreach ($cancels as $c) {
                                    $html .= '<tr>';
                                    $html .= '<td>MVT #' . $c['rowid'] . '</td>';
                                    $html .= '<td>' . $c['inventorycode'] . '</td>';
                                    $html .= '<td>' . $c['label'] . '</td>';
                                    $html .= '<td><span class="' . self::$type_mouvement[(int) $c['type_mouvement']]['classes'][0] . '">' . self::$type_mouvement[(int) $c['type_mouvement']]['label'] . '</span></td>';
                                    $html .= '<td>' . $c['value'] . '</td>';
                                    $html .= '</tr>';
                                }
                            }

                            $html .= '</tbody>';
                            $html .= '</table>';
                            $html .= '</td>';
                            $html .= '</tr>';
                        }
                    }

                    if (count($row_errors)) {
                        $html .= '<tr>';
                        $html .= '<td colspan="99">' . BimpRender::renderAlerts($row_errors) . '</td>';
                        $html .= '</tr>';
                    }
                }

                $html .= '</tbody>';
                $html .= '</table>';
            } else {
                $errors[] = 'Aucun mouvement à traiter trouvé pour ces dates';
            }
        }

        if (count($errors)) {
            $html .= BimpRender::renderAlerts($errors);
        }

        if ($echo) {
            echo $html;
        }
        return $html;
    }
}
