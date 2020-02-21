<?php

class BimpProductMouvement extends BimpObject
{

    CONST TRANS_IN = 0;
    CONST TRANS_OUT = 1;
    CONST STOCK_OUT = 2;
    CONST STOCK_IN = 3;

    public static $type_mouvement = [
        self::TRANS_IN  => ['label' => 'Entrée', 'classes' => ['success'], 'icon' => 'arrow-left'],
        self::TRANS_OUT => ['label' => 'Sortie', 'classes' => ['danger'], 'icon' => 'arrow-right'],
        self::STOCK_IN  => ['label' => 'Entrée', 'classes' => ['success'], 'icon' => 'arrow-left'],
        self::STOCK_OUT => ['label' => 'Sortie', 'classes' => ['danger'], 'icon' => 'arrow-right'],
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

                    // Inventaires: 
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
                $objet = 'Bimp_User';
                $module = 'bimpcore';
                $label = 'Immo';
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
                $label = 'Ret Vente  Caisse';
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

        return array(
            'object'     => $objet,
            'module'     => $module,
            'label'      => $label,
            'labelReverse'=> $labelReverse,
            'modal_view' => $modal_view,
            'ref_only'   => $only_ref,
            'origin'     => $origin,
            'id_origin'  => $id_origin
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
        if($this->getData('value') < 0 && $infos['labelReverse'] != '')
            $reason = $infos['labelReverse'];
        else
            $reason = $infos['label'];
        if ($reason == '') {
            $reason = 'Inconnue';

            if (stripos($this->getData("label"), "Transfert de stock") === 0 || stripos($this->getData("label"), "TR-") === 0)
                $reason = 'Transfert';
            elseif (stripos($this->getData("label"), "Correction du stock") !== false)
                $reason = 'CORRECT';
            elseif (stripos($this->getData("label"), "SAV") !== false)
                $reason = 'SAV';
            elseif (stripos($this->getData("label"), "Vol") !== false)
                $reason = 'Vol';
            elseif (stripos($this->getData("inventorycode"), 'inventory-id-') === 0)
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
}
