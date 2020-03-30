<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';

class Bimp_Societe extends BimpDolObject
{

    public static $types_ent_list = null;
    public static $types_ent_list_code = null;
    public static $effectifs_list = null;
    public $soc_type = "";
    public static $status_list = array(
        0 => array('label' => 'Désactivé', 'icon' => 'fas_times', 'classes' => array('danger')),
        1 => array('label' => 'Actif', 'icon' => 'fas_check', 'classes' => array('success'))
    );

    public function __construct($module, $object_name)
    {
        global $langs;
        if (isset($langs)) {
            $langs->load("companies");
            $langs->load("commercial");
            $langs->load("bills");
            $langs->load("banks");
            $langs->load("users");
        }

        parent::__construct($module, $object_name);
    }

    // Droits user: 

    public function canCreate()
    {
        global $user;
        return ($user->rights->societe->creer ? 1 : 0);
    }

    public function canEdit()
    {
        return $this->canCreate();
    }

    public function canDelete()
    {
        global $user;

        return (int) $user->admin;
    }

    public function canEditField($field_name)
    {
        switch ($field_name) {
            case 'outstanding_limit':
                global $user;
                return ($user->rights->bimpcommercial->admin_financier ? 1 : 0);
        }

        return parent::canEditField($field_name);
    }

    public function getBimpObjectsLinked()
    {

//        echo '<pre>';
//        $r1 = $this->getTypeOfBimpObjectLinked('bimpcore', 'Bimp_Societe');
//        $r2 = BimpTools::getBimpObjectLinked('bimpcore', 'Bimp_Societe', $this->id);
//
////        print_r($r2);die;
//        foreach($r2 as $objTmp){
//            echo( '<br/> '.$objTmp->getLink());
//        }

        $objects = array();
        if ($this->isLoaded()) {
            if ($this->isDolObject()) {
                foreach (BimpTools::getDolObjectLinkedObjectsList($this->dol_object, $this->db) as $item) {
                    $id = $item['id_object'];
                    $class = "";
                    $label = "";
                    $module = "bimpcommercial";
                    switch ($item['type']) {
                        case 'propal':
                            $class = "Bimp_Propal";
                            break;
                        case 'facture':
                            $class = "Bimp_Facture";
                            break;
                        case 'commande':
                            $class = "Bimp_Commande";
                            break;
                        case 'order_supplier':
                            $class = "Bimp_CommandeFourn";
                            break;
                        case 'invoice_supplier':
                            $class = "Bimp_FactureFourn";
                            break;
                        default:
                            break;
                    }
                    if ($class != "") {
                        $objT = BimpCache::getBimpObjectInstance($module, $class, $id);
//                        if ($objT->isLoaded()) { // Ne jamais faire ça: BimpCache renvoie null si l'objet n'existe pas => erreur fatale. 
                        if (BimpObject::objectLoaded($objT)) {
                            $objects[] = $objT;
                        }
                    }
                }
            }

            $client = $this->getChildObject('client');

            if ($client->isLoaded()) {
                $objects[] = $client;
            }
        }


        return $objects;
    }

    public function canSetAction($action)
    {
        switch ($action) {
            case 'addCommercial':
            case 'removeCommercial':
            case 'merge':
                return $this->canEdit();

            case 'relancePaiement':
                return 1;
        }

        return (int) parent::canSetAction($action);
    }

    // Getters booléens: 

    public function isCompany()
    {
        $id_typeent = (int) $this->getData('fk_typent');
        if ($id_typeent) {
            if (!in_array($this->db->getValue('c_typent', 'code', '`id` = ' . $id_typeent), array('TE_PRIVATE', '-'))) {
                return 1;
            }
            return 0;
        }

        return 1;
    }

    public function isClient()
    {
        return (is_a($this, 'Bimp_Client') || in_array((int) $this->getData('client'), array(1, 2, 3)) ? 1 : 0);
    }

    public function isFournisseur()
    {
        return (is_a($this, 'Bimp_Fournisseur') || (int) $this->getData('fournisseur') ? 1 : 0);
    }

    public function getSocieteIsFemale()
    {
        if ($this->soc_type == "client" || (int) $this->getData('client') > 0) {
            return 0;
        }

        if ($this->soc_type == "fournisseur" || (int) $this->getData('fournisseur') > 0) {
            return 0;
        }

        return 1;
    }

    public function canBuy(&$errors = array(), $msgToError = true)
    {
        self::getTypes_entArray();
        $type_ent_sans_verif = array("TE_PRIVATE", "TE_ADMIN");
        if (!isset(self::$types_ent_list_code[$this->getData("fk_typent")]) || !in_array(self::$types_ent_list_code[$this->getData("fk_typent")], $type_ent_sans_verif)) {
            /*
             * Entreprise onf fait les verifs...
             */
            if ($this->getData('fk_pays') == 1 || $this->getData('fk_pays') < 1)
                if (strlen($this->getData("siret")) != 14 || !$this->Luhn($this->getData("siret"), 14)) {
                    $errors[] = "Siret client invalide :" . $this->getData("siret");
                }
        }
        if ($this->getData('zip') == '' || $this->getData('town') == '' || $this->getData('address') == '')
            $errors[] = "Merci de renseigner l'adresse complète du client";


        if (self::$types_ent_list_code[$this->getData("fk_typent")] != "TE_PRIVATE") {
            if ($this->getData("mode_reglement") < 1) {
                $errors[] = "Mode réglement fiche client invalide ";
            }
            if ($this->getData("cond_reglement") < 1) {
                $errors[] = "Condition réglement fiche client invalide ";
            }
        }

        if (count($errors))
            return 0;

        return 1;
    }

    public function isActionAllowed($action, &$errors = array())
    {
        if (in_array($action, array('addCommercial', 'removeCommercial', 'merge'))) {
            if (!$this->isLoaded($errors)) {
                return 0;
            }
        }

        return parent::isActionAllowed($action, $errors);
    }

    public function isSirenOk()
    {
        if ($this->isLoaded() && $this->Luhn($this->getData('siren'), 9)) {
            return 1;
        }

        return 0;
    }
    
    public function isSirenRequired()
    {        
        $code = ($this->dol_object->idprof1 != "" ? $this->dol_object->idprof1 : $this->dol_object->idprof2);
        if(strlen($code) > 5)
            return 1;
        
        if($this->dol_object->typent_code == "TE_PRIVATE" || $this->dol_object->typent_code == "TE_ADMIN")
            return 1;
        
        
        if($this->dol_object->country_id != 1)
            return 1;
        
        if($this->dol_object->parent > 1)
            return 1;
        
        
        return 0;
    }

    // Getters params: 

    public function getFilesDir()
    {
        if ($this->isLoaded()) {
            return DOL_DATA_ROOT . '/societe/' . $this->id . '/';
        }
    }

    public function getFileUrl($file_name, $page = 'document')
    {
        if (!$file_name) {
            return '';
        }

        if (!$this->isLoaded()) {
            return '';
        }

        $file = $this->id . '/' . $file_name;

        return DOL_URL_ROOT . '/' . $page . '.php?modulepart=societe&file=' . urlencode($file);
    }

    public function getActionsButtons()
    {
        $buttons = array();

        if ($this->isLoaded()) {
            if ($this->can('edit') && $this->isEditable()) {
                $buttons[] = array(
                    'label'   => 'Changer le logo',
                    'icon'    => 'fas_file-image',
                    'onclick' => $this->getJsLoadModalForm('logo', 'Changer le logo')
                );
            }

            if ($this->canSetAction('merge') && $this->isActionAllowed('merge')) {
                $buttons[] = array(
                    'label'   => 'Fusionner',
                    'icon'    => 'fas_object-group',
                    'onclick' => $this->getJsActionOnclick('merge', array(), array(
                        'form_name' => 'merge'
                    ))
                );
            }

            $buttons[] = array(
                'label'   => 'Générer document',
                'icon'    => 'fas_sync',
                'onclick' => $this->getJsActionOnclick('generatePdf', array(), array(
                    'form_name' => 'generate_pdf'
                ))
            );
        }

        return $buttons;
    }

    public function getDolObjectDeleteParams()
    {
        global $user;
        return array(
            $this->id,
            $user
        );
    }

    public function getPdfModelFileName($model)
    {
        if (!$this->isLoaded()) {
            return '';
        }

        switch ($model) {
            case 'cepa':
                return $this->id . '_sepa';
        }

        return '';
    }

    public function getCustomFilterValueLabel($field_name, $value)
    {
        switch ($field_name) {
            case 'commerciaux':
                if ((int) $value) {
                    $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $value);
                    if (BimpObject::ObjectLoaded($user)) {
                        return $user->dol_object->getFullName();
                    }
                } else {
                    return 'Aucun';
                }
                break;
        }

        return parent::getCustomFilterValueLabel($field_name, $value);
    }

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, &$errors = array(), $excluded = false)
    {
        switch ($field_name) {
            case 'commerciaux':
                $ids = array();
                $empty = false;

                foreach ($values as $idx => $value) {
                    if ($value === 'current') {
                        global $user;
                        if (BimpObject::objectLoaded($user)) {
                            $ids[] = (int) $user->id;
                        }
                    } elseif ((int) $value) {
                        $ids[] = (int) $value;
                    } else {
                        $empty = true;
                    }
                }

                $joins['soc_commercial'] = array(
                    'alias' => 'soc_commercial',
                    'table' => 'societe_commerciaux',
                    'on'    => 'a.rowid = soc_commercial.fk_soc'
                );

                $sql = '';

                $nbCommerciaux = 'SELECT COUNT(sc.rowid) FROM ' . MAIN_DB_PREFIX . 'societe_commerciaux sc WHERE sc.fk_soc = a.rowid';

                if (!empty($ids)) {
                    if (!$excluded) {
                        $sql = 'soc_commercial.fk_user IN (' . implode(',', $ids) . ')';
                    } else {
                        $sql = '(' . $nbCommerciaux . ' AND sc.fk_user IN (' . implode(',', $ids) . ')) = 0';
                    }

//                    if (!$empty && $excluded) {
//                        $sql .= ' OR (' . $nbCommerciaux . ') = 0';
//                    }
                }

                if ($empty) {
                    $sql .= ($sql ? ($excluded ? ' AND ' : ' OR ') : '');
                    $sql .= '(' . $nbCommerciaux . ') ' . ($excluded ? '>' : '=') . ' 0';
                }

                if ($sql) {
                    $filters['commerciaux_custom'] = array(
                        'custom' => '(' . $sql . ')'
                    );
                }
                break;
        }

        parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $errors, $excluded);
    }

    // Getters données: 

    public function getRef($with_generic = true)
    {
        if ($this->isClient()) {
            return $this->getData('code_client');
        } elseif ($this->isFournisseur()) {
            return $this->getData('code_fournisseur');
        }

        if ($with_generic) {
            return $this->id;
        }

        return '';
    }

    public function getSocieteLabel()
    {
        if ($this->soc_type == "client" || (int) $this->getData('client') > 0) {
            return 'client';
        }

        if ($this->soc_type == "fournisseur" || (int) $this->getData('fournisseur') > 0) {
            return 'fournisseur';
        }

        return 'société';
    }

    public function getNumSepa()
    {


        if ($this->getData('num_sepa') == "") {
            $new = BimpTools::getNextRef('societe_extrafields', 'num_sepa', 'FR02ZZZ008801-', 7);
            $this->updateField('num_sepa', $new);
            $this->update();
        }
        return $this->getData('num_sepa');
    }

    public function getCountryCode()
    {
        $fk_pays = (int) $this->getData('fk_pays');
        if ($fk_pays) {
            return $this->db->getValue('c_country', 'code', '`rowid` = ' . (int) $fk_pays);
        }
    }

    protected function getDolObjectUpdateParams()
    {
        global $user;
        return array($this->id, $user);
    }

    public function getAvailableDiscountsAmounts($is_fourn = false, $allowed = array())
    {
        if ($this->isLoaded()) {
            global $conf;

            $sql = 'SELECT SUM(r.amount_ttc) as amount';
            $sql .= ' FROM ' . MAIN_DB_PREFIX . 'societe_remise_except r';
            $sql .= ' WHERE r.entity = ' . $conf->entity;
            $sql .= ' AND r.discount_type = ' . ($is_fourn ? 1 : 0);
            $sql .= ' AND r.fk_soc = ' . (int) $this->id;

            if ($is_fourn) {
                $sql .= ' AND (r.fk_invoice_supplier IS NULL AND r.fk_invoice_supplier_line IS NULL)';
            } else {
                $sql .= ' AND (r.fk_facture IS NULL AND r.fk_facture_line IS NULL)';

                $and_where = '';
                if (isset($allowed['factures']) && !empty($allowed['factures'])) {
                    $and_where = ' AND fdet.fk_facture NOT IN (' . implode(',', $allowed['factures']) . ')';
                }

                $sql .= ' AND (SELECT COUNT(fdet.rowid) FROM ' . MAIN_DB_PREFIX . 'facturedet fdet WHERE fdet.fk_remise_except = r.rowid' . $and_where . ') = 0';

                $and_where = '';
                if (isset($allowed['commandes']) && !empty($allowed['commandes'])) {
                    $and_where = ' AND cdet.fk_commande NOT IN (' . implode(',', $allowed['commandes']) . ')';
                }

                $sql .= ' AND (SELECT COUNT(cdet.rowid) FROM ' . MAIN_DB_PREFIX . 'commandedet cdet WHERE cdet.fk_remise_except = r.rowid' . $and_where . ') = 0';

                $and_where = '';
                if (isset($allowed['propales']) && !empty($allowed['propales'])) {
                    $and_where = ' AND pdet.fk_propal NOT IN (' . implode(',', $allowed['propales']) . ')';
                }

                $sql .= ' AND (SELECT COUNT(pdet.rowid) FROM ' . MAIN_DB_PREFIX . 'propaldet pdet WHERE pdet.fk_remise_except = r.rowid' . $and_where . ') = 0';
            }

            $result = $this->db->executeS($sql, 'array');

            if (isset($result[0]['amount'])) {
                return (float) $result[0]['amount'];
            }
        }

        return 0;
    }

    public static function getDiscountUsedLabel($id_discount, $with_nom_url = false, $allowed = array())
    {
        $use_label = '';

        if (!(int) $id_discount) {
            return $use_label;
        }

        $bdb = BimpCache::getBdb();

        if (!class_exists('DiscountAbsolute')) {
            require_once DOL_DOCUMENT_ROOT . '/core/class/discount.class.php';
        }

        $discount = new DiscountAbsolute($bdb->db);
        $discount->fetch((int) $id_discount);

        if (BimpObject::objectLoaded($discount)) {
            if ((int) $discount->fk_invoice_supplier_source) {
                // Remise fournisseur
                $id_facture_fourn = 0;
                if ((isset($discount->fk_invoice_supplier) && (int) $discount->fk_invoice_supplier)) {
                    $id_facture_fourn = (int) $discount->fk_invoice_supplier;
                } elseif (isset($discount->fk_invoice_supplier_line) && (int) $discount->fk_invoice_supplier_line) {
                    $id_facture_fourn = (int) $bdb->getValue('facture_fourn_det', 'fk_facture_fourn', 'rowid = ' . (int) $discount->fk_invoice_supplier_line);
                }

                if ($id_facture_fourn) {
                    $factureFourn = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureFourn', $id_facture_fourn);
                    if (BimpObject::objectLoaded($factureFourn)) {
                        $use_label = 'Ajouté à la facture fournisseur ' . ($with_nom_url ? $factureFourn->getNomUrl(0, 1, 1, 'full') : '"' . $factureFourn->getRef() . '"');
                    } else {
                        $use_label .= 'Ajouté à la facture fournisseur #' . $id_facture_fourn;
                    }
                }
            } else {
                // Remise client
                // On ne tient pas compte de $allowed dans les deux cas suivants: 
                $id_facture = 0;
                if ((isset($discount->fk_facture) && (int) $discount->fk_facture)) {
                    $id_facture = (int) $discount->fk_facture;
                } elseif (isset($discount->fk_facture_line) && (int) $discount->fk_facture_line) {
                    $id_facture = (int) $bdb->getValue('facturedet', 'fk_facture', 'rowid = ' . (int) $discount->fk_facture_line);
                }

                if ($id_facture) {
                    $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_facture);
                    if (BimpObject::objectLoaded($facture)) {
                        $use_label = 'Ajouté à la facture ' . ($with_nom_url ? $facture->getNomUrl(0, 1, 1, 'full') : '"' . $facture->getRef() . '"');
                    } else {
                        $use_label .= 'Ajouté à la facture #' . $id_facture;
                    }
                } else {
                    $rows = $bdb->getRows('facturedet', 'fk_remise_except = ' . (int) $id_discount, null, 'array', array('fk_facture'));
                    if (is_array($rows)) {
                        foreach ($rows as $r) {
                            if (!isset($allowed['factures']) || !in_array((int) $r['fk_facture'], $allowed['factures'])) {
                                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $r['fk_facture']);
                                if (BimpObject::objectLoaded($facture)) {
                                    $use_label = 'Ajouté à la facture ' . ($with_nom_url ? $facture->getNomUrl(1, 1, 1, 'full') : '"' . $facture->getRef() . '"');
                                    break;
                                } else {
                                    $bdb->delete('facturedet', '`fk_facture` = ' . $r['fk_facture'] . ' AND `fk_remise_except` = ' . (int) $id_discount);
                                }
                            }
                        }
                    }

                    if (!$use_label) {
                        $rows = $bdb->getRows('commandedet', 'fk_remise_except = ' . (int) $id_discount, null, 'array', array('fk_commande'));
                        if (is_array($rows)) {
                            foreach ($rows as $r) {
                                if (!isset($allowed['commandes']) || !in_array((int) $r['fk_commande'], $allowed['commandes'])) {
                                    $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $r['fk_commande']);
                                    if (BimpObject::objectLoaded($commande)) {
                                        $use_label = 'Ajouté à la commande ' . ($with_nom_url ? $commande->getNomUrl(1, 1, 1, 'full') : '"' . $commande->getRef() . '"');
                                        break;
                                    } else {
                                        $bdb->delete('commandedet', '`fk_commande` = ' . $r['fk_commande'] . ' AND `fk_remise_except` = ' . (int) $id_discount);
                                    }
                                }
                            }
                        }
                    }

                    if (!$use_label) {
                        $rows = $bdb->getRows('propaldet', 'fk_remise_except = ' . (int) $id_discount, null, 'array', array('fk_propal'));
                        if (is_array($rows)) {
                            foreach ($rows as $r) {
                                if (!isset($allowed['propales']) || !in_array((int) $r['fk_propal'], $allowed['propales'])) {
                                    $propal = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', (int) $r['fk_propal']);
                                    if (BimpObject::objectLoaded($propal)) {
                                        if (!in_array($propal->getData('fk_statut'), array(4, 3))) {
                                            if (!(int) $bdb->getValue('element_element', 'rowid', '`fk_source` = ' . $r['fk_propal'] . ' AND `sourcetype` = \'propal\'  AND `targettype` = \'commande\'')) {
                                                $use_label = 'Ajouté à la propale ' . ($with_nom_url ? $propal->getNomUrl(1, 1, 1, 'full') : '"' . $propal->getRef() . '"');
                                                break;
                                            }
                                        }
                                    } else {
                                        $bdb->delete('propaldet', '`fk_propal` = ' . $r['fk_propal'] . ' AND `fk_remise_except` = ' . (int) $id_discount);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $use_label;
    }

    public function getCommercial($with_default = true)
    {
        $commerciaux = $this->getCommerciauxArray();

        foreach ($commerciaux as $id_comm => $comm_label) {
            $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $id_comm);
            if (BimpObject::objectLoaded($user)) {
                return $user;
            }
        }

        if ($with_default) {
            $default_id_commercial = (int) BimpCore::getConf('default_id_commercial', 0);
            if ($default_id_commercial) {
                $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $default_id_commercial);
                if (BimpObject::objectLoaded($user)) {
                    return $user;
                }
            }
        }

        return null;
    }

    public function getIdCommercials()
    {
        $return = array();
        if ($this->isLoaded()) {
            $sql = $this->db->db->query("SELECT fk_user FROM " . MAIN_DB_PREFIX . "societe_commerciaux WHERE fk_soc = " . $this->id);
            while ($ln = $this->db->db->fetch_object($sql)) {
                $return[] = $ln->fk_user;
            }
        }
        return $return;
    }

    // Getters array: 

    public function getContactsList($include_empty = true)
    {
        if ($this->isLoaded()) {
            return self::getSocieteContactsArray($this->id, $include_empty);
        }

        return array();
    }

    public function getAvailableDiscountsArray($is_fourn = false, $allowed = array())
    {
        $discounts = array();

        if ($this->isLoaded()) {
            global $conf;

            $sql = 'SELECT r.rowid as id, r.description, r.amount_ttc as amount';
            $sql .= ' FROM ' . MAIN_DB_PREFIX . 'societe_remise_except r';
            $sql .= ' WHERE r.entity = ' . $conf->entity;
            $sql .= ' AND r.discount_type = ' . ($is_fourn ? 1 : 0);
            $sql .= ' AND r.fk_soc = ' . (int) $this->id;

            if ($is_fourn) {
                $sql .= ' AND (r.fk_invoice_supplier IS NULL AND r.fk_invoice_supplier_line IS NULL)';
            } else {
                $sql .= ' AND (r.fk_facture IS NULL AND r.fk_facture_line IS NULL)';
            }

            $rows = $this->db->executeS($sql, 'array');

            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    $disabled_label = static::getDiscountUsedLabel((int) $r['id'], false, $allowed);

                    $discounts[(int) $r['id']] = array(
                        'label'    => BimpTools::getRemiseExceptLabel($r['description']) . ' (' . BimpTools::displayMoneyValue((float) $r['amount'], '') . ' TTC)' . ($disabled_label ? ' - ' . $disabled_label : ''),
                        'disabled' => ($disabled_label ? 1 : 0),
                        'data'     => array(
                            'amount_ttc' => (float) $r['amount']
                        )
                    );
                }
            }
        }

        return $discounts;
    }

    public function getTypes_entArray()
    {
        if (is_null(self::$types_ent_list)) {
            $sql = 'SELECT `id`, `libelle`, `code` FROM ' . MAIN_DB_PREFIX . 'c_typent WHERE `active` = 1';
            $rows = $this->db->executeS($sql, 'array');

            $types = array();
            $typesCode = array();
            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    $types[(int) $r['id']] = $r['libelle'];
                    $typesCode[(int) $r['id']] = $r['code'];
                }
            }
            self::$types_ent_list = $types;
            self::$types_ent_list_code = $typesCode;
        }

        return self::$types_ent_list;
    }

    public function getEffectifsArray()
    {
        if (is_null(self::$effectifs_list)) {
            $sql = 'SELECT `id`, `libelle` FROM ' . MAIN_DB_PREFIX . 'c_effectif WHERE `active` = 1';
            $rows = $this->db->executeS($sql, 'array');

            $effectifs = array();
            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    $effectifs[(int) $r['id']] = $r['libelle'];
                }
            }

            self::$effectifs_list = $effectifs;
        }

        return self::$effectifs_list;
    }

    public function getCommerciauxArray($include_empty = false)
    {
        if ($this->isLoaded()) {
            return self::getSocieteCommerciauxArray($this->id, $include_empty);
        }

        return array();
    }

    public function getModelsPdfArray()
    {
        if (!class_exists('ModeleThirdPartyDoc')) {
            require_once DOL_DOCUMENT_ROOT . '/core/modules/societe/modules_societe.class.php';
        }

        return ModeleThirdPartyDoc::liste_modeles($this->db->db);
    }

    // Affichages: 

    public function displayJuridicalStatus()
    {
        if ($this->isLoaded()) {
            $fk_fj = (int) $this->getData('fk_forme_juridique');
            if ($fk_fj) {
                return $this->db->getValue('c_forme_juridique', 'libelle', '`code` = ' . $fk_fj);
            }
        }

        return '';
    }

    public function displayCountry()
    {
        $id = $this->getData('fk_pays');
        if (!is_null($id) && $id) {
            return $this->db->getValue('c_country', 'label', '`rowid` = ' . (int) $id);
        }
        return '';
    }

    public function displayDepartement()
    {
        $fk_dep = (int) $this->getData('fk_departement');
        if ($fk_dep) {
            return $this->db->getValue('c_departements', 'nom', '`rowid` = ' . $fk_dep);
        }
        return '';
    }

    public function displayFullAddress($icon = false, $single_line = false)
    {
        $html = '';

        if ($this->getData('address')) {
            $html .= $this->getData('address') . ($single_line ? ' - ' : '<br/>');
        }

        if ($this->getData('zip')) {
            $html .= $this->getData('zip');

            if ($this->getData('town')) {
                $html .= ' ' . $this->getData('town');
            }
            $html .= ($single_line ? '' : '<br/>');
        } elseif ($this->getData('town')) {
            $html .= $this->getData('town') . ($single_line ? '' : '<br/>');
        }

        if (!$single_line && $this->getData('fk_departement')) {
            $html .= $this->displayDepartement();

            if ($this->getData('fk_pays')) {
                $html .= ' - ' . $this->displayCountry();
            }
        } elseif ($this->getData('fk_pays')) {
            if ($single_line) {
                $html .= ' - ';
            }
            $html .= $this->displayCountry();
        }

        if ($html && $icon) {
            $html = BimpRender::renderIcon('fas_map-marker-alt', 'iconLeft') . $html;
        }

        return $html;
    }

    public function displayFullContactInfos($icon = true, $single_line = false)
    {
        $html = '';

        if ($single_line) {
            $phone = $this->getData('phone');
            $mail = $this->getData('email');

            if ($phone) {
                $html .= ($icon ? BimpRender::renderIcon('fas_phone', 'iconLeft') : '') . $phone;
            }
            if ($mail) {
                $html .= ($html ? ' - ' : '');
                $html .= '<a href="mailto:' . $mail . '">';
                $html .= ($icon ? BimpRender::renderIcon('fas_envelope', 'iconLeft') : '') . $mail;
                $html .= '</a>';
            }
        } else {
            foreach (array(
        'phone' => 'fas_phone',
        'email' => 'fas_envelope',
        'fax'   => 'fas_fax',
        'skype' => 'fab_skype',
        'url'   => 'fas_globe',
            ) as $field => $icon_class) {
                if ($this->getData($field)) {
                    if ($field === 'email') {
                        $html .= '<a href="mailto:' . $this->getData('email') . '">';
                    } elseif ($field === 'url') {
                        $html .= '<a href="' . $this->getData('url') . '" target="_blank">';
                    }

                    $html .= ($html ? '<br/>' : '') . ($icon ? BimpRender::renderIcon($icon_class, 'iconLeft') : '') . $this->getData($field);

                    if (in_array($field, array('email', 'url'))) {
                        $html .= '</a>';
                    }
                }
            }
        }


        return $html;
    }

    public function displayDiscountAvailables()
    {
        $html = '';

        if ($this->isLoaded()) {
            $amount = (float) $this->getAvailableDiscountsAmounts($this->isFournisseur());
            $html .= BimpTools::displayMoneyValue($amount);

            $html .= '<div class="buttonsContainer align-right">';
            $url = DOL_URL_ROOT . '/comm/remx.php?id=' . $this->id;
            $html .= '<a class="btn btn-default" href="' . $url . '" target="_blank">';
            $html .= 'Liste complète des avoirs client' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight');
            $html .= '</a>';
            $html .= '</div>';
        }

        return $html;
    }

    public function displayCommerciaux($with_button = true)
    {
        $html = '';

        $users = $this->getCommerciauxArray(false);
        $default_id_commercial = (int) BimpCore::getConf('default_id_commercial');

        foreach ($users as $id_user => $label) {
            if ((int) $id_user) {
                $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $id_user);
                if (BimpObject::objectLoaded($user)) {
                    $html .= ($html ? '<br/>' : '') . $user->getLink() . ' ';

                    if ((int) $user->id !== $default_id_commercial) {
                        $onclick = $this->getJsActionOnclick('removeCommercial', array(
                            'id_commercial' => (int) $user->id
                                ), array(
                            'confirm_msg' => htmlentities('Veuillez confirmer le retrait du commercial "' . $user->getName() . '"')
                        ));
                        $html .= BimpRender::renderRowButton('Retirer', 'fas_trash-alt', $onclick);
                    } else {
                        $html .= '&nbsp;<span class="small">(commercial par défaut)</span>';
                    }
                } else {
                    $html .= '<span class="danger">L\'utilisatuer #' . $id_user . ' n\'existe plus</span>';
                }
            }
        }

        if ($with_button) {
            $html .= '<div class="buttonsContainer align-right">';
            $html .= '<span class="btn btn-default" onclick="' . $this->getJsActionOnclick('addCommercial', array(), array(
                        'form_name' => 'add_commercial'
                    )) . '">';
            $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Ajouter un commercial';
            $html .= '</span>';
            $html .= '</div>';
        }

        return $html;
    }

    public function displayCommercials()
    {
        global $modeCSV;

        $return = array();
        $ids = $this->getIdCommercials();
        if (count($ids) > 0) {
            BimpTools::loadDolClass('contact');
            foreach ($ids as $id) {
                $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id);
                if (BimpObject::objectLoaded($user)) {
                    if ($modeCSV)
                        $return[] = $user->getName();
                    else
                        $return[] = $user->getLink();
                }
            }
        }

        if ($modeCSV)
            return implode("\n", $return);
        else
            return implode("<br/>", $return);
    }

    // Rendus HTML: 

    public function renderHeaderExtraLeft()
    {
        $html = '';

        if ($this->isLoaded()) {
            $address = $this->displayFullAddress(true, true);
            $contact = $this->displayFullContactInfos(true, true);

            if ($address || $contact) {
                $html .= '<div style="margin-bottom: 8px">';
                if ($address) {
                    $html .= $address . ($contact ? '<br/>' : '');
                }
                if ($contact) {
                    $html .= $contact;
                }
                $html .= '</div>';
            }

            if ($this->dol_object->date_creation) {
                $dt = new DateTime(BimpTools::getDateFromDolDate($this->dol_object->date_creation));

                $html .= '<div class="object_header_infos">';
                $html .= 'Créé le ' . $dt->format('d / m / Y');

                if ((int) $this->dol_object->user_creation) {
                    $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $this->dol_object->user_creation);
                    if (BimpObject::objectLoaded($user)) {
                        $html .= ' par ' . $user->getLink();
                    }
                }

                $html .= '</div>';
            }

            if ($this->dol_object->date_modification) {
                $dt = new DateTime(BimpTools::getDateFromDolDate($this->dol_object->date_modification));

                $html .= '<div class="object_header_infos">';
                $html .= 'Dernière mise à jour le ' . $dt->format('d / m / Y');

                if ((int) $this->dol_object->user_modification) {
                    $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $this->dol_object->user_modification);
                    if (BimpObject::objectLoaded($user)) {
                        $html .= ' par ' . $user->getLink();
                    }
                }

                $html .= '</div>';
            }
        }

        return $html;
    }

    // Trtaitements: 

    public function checkValidity()
    {
        $errors = array();

        return $errors;
    }

    public function Luhn($numero, $longueur)
    {
        // On passe à la fonction la variable contenant le numéro à vérifier
        // et la longueur qu'il doit impérativement avoir

        if ((strlen($numero) == $longueur) && preg_match("#[0-9]{" . $longueur . "}#i", $numero)) {
            // si la longueur est bonne et que l'on n'a que des chiffres

            /* on décompose le numéro dans un tableau  */
            for ($i = 0; $i < $longueur; $i++) {
                $tableauChiffresNumero[$i] = substr($numero, $i, 1);
            }

            /* on parcours le tableau pour additionner les chiffres */
            $luhn = 0; // clef de luhn à tester
            for ($i = 0; $i < $longueur; $i++) {
                if ($i % 2 == 0) { // si le rang est pair (0,2,4 etc.)
                    if (($tableauChiffresNumero[$i] * 2) > 9) {
                        // On regarde si son double est > à 9
                        $tableauChiffresNumero[$i] = ($tableauChiffresNumero[$i] * 2) - 9;
                        //si oui on lui retire 9
                        // et on remplace la valeur
                        // par ce double corrigé
                    } else {

                        $tableauChiffresNumero[$i] = $tableauChiffresNumero[$i] * 2;
                        // si non on remplace la valeur
                        // par le double
                    }
                }
                $luhn = $luhn + $tableauChiffresNumero[$i];
                // on additionne le chiffre à la clef de luhn
            }

            /* test de la divition par 10 */
            if ($luhn % 10 == 0) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
            // la valeur fournie n'est pas conforme (caractère non numérique ou mauvaise
            // longueur)
        }
    }

    public function processLogoUpload()
    {
        if (!isset($_FILES['logo'])) {
            return array();
        }

        $errors = array();

        if ($this->isLoaded($errors)) {
            if (is_uploaded_file($_FILES['logo']['tmp_name'])) {
                global $maxwidthsmall, $maxheightsmall, $maxwidthmini, $maxheightmini, $quality;

                require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
                require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

                if (image_format_supported($_FILES['logo']['name'])) {
                    global $conf;
                    $dir = $conf->societe->multidir_output[$conf->entity] . "/" . $this->id . "/logos/";
                    dol_mkdir($dir);

                    if (is_dir($dir)) {
                        $file_name = dol_sanitizeFileName($_FILES['logo']['name']);
                        $file_path = $dir . $file_name;
                        if (dol_move_uploaded_file($_FILES['logo']['tmp_name'], $file_path, 1) > 0) {
                            $this->updateField('logo', $file_name);
                            $this->dol_object->logo = $file_name;
//                            $this->dol_object->addThumbs($file_path);

                            $file_osencoded = dol_osencode($file_path);
                            if (file_exists($file_osencoded)) {
                                vignette($file_osencoded, $maxwidthsmall, $maxheightsmall, '_small', $quality);
                                vignette($file_osencoded, $maxwidthmini, $maxheightmini, '_mini', $quality);
                            }
                        } else {
                            $errors[] = 'Echec de l\'enregistrement du fichierr';
                        }
                    } else {
                        $errors[] = 'Echec de la création du dossier de destination du logo';
                    }
                } else {
                    $errors[] = 'Format non supporté';
                }
            } else {
                switch ($_FILES['logo']['error']) {
                    case 1:
                    case 2:
                        $errors[] = "Fichier trop volumineux";
                        break;
                    case 3:
                        $errors[] = "Echec du téléchargement du fichier";
                        break;
                }
            }
        }

        return $errors;
    }

    public function onSave(&$errors = array(), &$warnings = array())
    {
        $logo_errors = $this->processLogoUpload();

        if (count($logo_errors)) {
            $warnings[] = BimpTools::getMsgFromArray($logo_errors, 'Logo');
        }

        parent::onSave($errors, $warnings);
    }

    public function mergeSocietes($id_soc_to_merge, $import_soc_to_merge_data = true)
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            $soc_to_merge = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', (int) $id_soc_to_merge);

            if (!BimpObject::objectLoaded($soc_to_merge)) {
                if ((int) $id_soc_to_merge) {
                    $errors[] = BimpTools::ucfirst($this->getLabel('the')) . ' à fusionner d\'ID ' . $id_soc_to_merge . ' n\'existe pas';
                } else {
                    $errors[] = BimpTools::ucfirst($this->getLabel()) . ' à fusionner non spécifié' . $this->e();
                }
            } elseif (($this->isClient() && !$soc_to_merge->isClient()) ||
                    $this->isFournisseur() && !$soc_to_merge->isFournisseur()) {
                $errors[] = 'Il n\'est pas possible de fusionner un client avec un fournisseur';
            } else {
                global $db, $user, $langs;
                $db->begin();

                $soc_origin_id = (int) $id_soc_to_merge;
                $soc_origin = $soc_to_merge->dol_object;
                $object = $this->dol_object;

                // Code repris tel quel depuis societe/card.php: 

                /* moddrsi */
                $sql = $db->query('SELECT Count(*) as nb, `ref_fourn`, `fk_product` FROM `llx_product_fournisseur_price` WHERE `fk_soc` IN (' . $soc_origin_id . ', ' . $object->id . ') GROUP BY `ref_fourn`, `fk_product` HAVING nb > 1');
                while ($ln = $db->fetch_object($sql)) {
                    $db->query('UPDATE `llx_product_fournisseur_price` SET ref_fourn = CONCAT(ref_fourn, "-B") WHERE fk_product = ' . $ln->fk_product . ' AND ref_fourn = "' . $ln->ref_fourn . '" AND fk_soc = ' . $soc_origin_id . ' ');
                }
                /* fmoddrsi */


                if ($import_soc_to_merge_data) {
                    // Recopy some data
                    $object->client = $object->client | $soc_origin->client;
                    $object->fournisseur = $object->fournisseur | $soc_origin->fournisseur;
                    $listofproperties = array(
                        'address', 'zip', 'town', 'state_id', 'country_id', 'phone', 'phone_pro', 'fax', 'email', 'skype', 'url', 'barcode',
                        'idprof1', 'idprof2', 'idprof3', 'idprof4', 'idprof5', 'idprof6',
                        'tva_intra', 'effectif_id', 'forme_juridique', 'remise_percent', 'remise_supplier_percent', 'mode_reglement_supplier_id', 'cond_reglement_supplier_id', 'name_bis',
                        'stcomm_id', 'outstanding_limit', 'price_level', 'parent', 'default_lang', 'ref', 'ref_ext', 'import_key', 'fk_incoterms', 'fk_multicurrency',
                        'code_client', 'code_fournisseur', 'code_compta', 'code_compta_fournisseur',
                        'model_pdf', 'fk_projet'
                    );
                    foreach ($listofproperties as $property) {
                        if (empty($object->$property))
                            $object->$property = $soc_origin->$property;
                    }

                    // Concat some data
                    $listofproperties = array(
                        'note_public', 'note_private'
                    );
                    foreach ($listofproperties as $property) {
                        $object->$property = dol_concatdesc($object->$property, $soc_origin->$property);
                    }

                    // Merge extrafields
                    if (is_array($soc_origin->array_options)) {
                        foreach ($soc_origin->array_options as $key => $val) {
                            if (empty($object->array_options[$key]))
                                $object->array_options[$key] = $val;
                        }
                    }

                    // Merge categories
                    BimpTools::loadDolClass('categories', 'categorie');
                    $static_cat = new Categorie($db);

                    $custcats_ori = $static_cat->containing($soc_origin->id, 'customer', 'id');
                    $custcats = $static_cat->containing($object->id, 'customer', 'id');
                    $custcats = array_merge($custcats, $custcats_ori);
                    $object->setCategories($custcats, 'customer');

                    $suppcats_ori = $static_cat->containing($soc_origin->id, 'supplier', 'id');
                    $suppcats = $static_cat->containing($object->id, 'supplier', 'id');
                    $suppcats = array_merge($suppcats, $suppcats_ori);
                    $object->setCategories($suppcats, 'supplier');

                    // If thirdparty has a new code that is same than origin, we clean origin code to avoid duplicate key from database unique keys.
                    if ($soc_origin->code_client == $object->code_client || $soc_origin->code_fournisseur == $object->code_fournisseur || $soc_origin->barcode == $object->barcode) {
                        dol_syslog("We clean customer and supplier code so we will be able to make the update of target");
                        $soc_origin->code_client = '';
                        $soc_origin->code_fournisseur = '';
                        $soc_origin->barcode = '';
                        $soc_origin->update($soc_origin->id, $user, 0, 1, 1, 'merge');
                    }

                    // Update
                    $result = $object->update($object->id, $user, 0, 1, 1, 'merge');
                    if ($result < 0) {
                        $errors[] = 'Echec de la mise à jour des données  ' . $this->getLabel('of_the') . ' à conserver';
                    }
                }

                // Move links
                if (!count($errors)) {
                    $objects = array(
                        'Adherent'            => '/adherents/class/adherent.class.php',
                        'Societe'             => '/societe/class/societe.class.php',
                        //'Categorie' => '/categories/class/categorie.class.php',
                        'ActionComm'          => '/comm/action/class/actioncomm.class.php',
                        'Propal'              => '/comm/propal/class/propal.class.php',
                        'Commande'            => '/commande/class/commande.class.php',
                        'Facture'             => '/compta/facture/class/facture.class.php',
                        'FactureRec'          => '/compta/facture/class/facture-rec.class.php',
                        'LignePrelevement'    => '/compta/prelevement/class/ligneprelevement.class.php',
                        'Contact'             => '/contact/class/contact.class.php',
                        'Contrat'             => '/contrat/class/contrat.class.php',
                        'Expedition'          => '/expedition/class/expedition.class.php',
                        'Fichinter'           => '/fichinter/class/fichinter.class.php',
                        'CommandeFournisseur' => '/fourn/class/fournisseur.commande.class.php',
                        'FactureFournisseur'  => '/fourn/class/fournisseur.facture.class.php',
                        'SupplierProposal'    => '/supplier_proposal/class/supplier_proposal.class.php',
                        'ProductFournisseur'  => '/fourn/class/fournisseur.product.class.php',
                        'Livraison'           => '/livraison/class/livraison.class.php',
                        'Product'             => '/product/class/product.class.php',
                        'Project'             => '/projet/class/project.class.php',
                        'User'                => '/user/class/user.class.php',
                    );

                    //First, all core objects must update their tables
                    foreach ($objects as $object_name => $object_file) {
                        require_once DOL_DOCUMENT_ROOT . $object_file;

                        if (!count($errors) && !$object_name::replaceThirdparty($db, $soc_origin->id, $object->id)) {
                            $errors[] = $db->lasterror();
                        }
                    }
                }

                // External modules should update their ones too
                if (!count($errors)) {
                    $hm = new HookManager($db);
                    $hm->initHooks(array('thirdpartycard', 'globalcard'));

                    $soc_dest = '';
                    $action = '';

                    $reshook = $hm->executeHooks('replaceThirdparty', array(
                        'soc_origin' => $soc_origin->id,
                        'soc_dest'   => $object->id
                            ), $soc_dest, $action);

                    if ($reshook < 0) {
                        $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($hm));
                    }
                }


                if (!count($errors)) {
                    $object->context = array('merge' => 1, 'mergefromid' => $soc_origin->id);

                    // Call trigger
                    $result = $object->call_trigger('COMPANY_MODIFY', $user);
                    if ($result < 0) {
                        $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($object));
                    }
                }

                if (!count($errors)) {
                    //We finally remove the old thirdparty
                    if ($soc_origin->delete($soc_origin->id, $user) < 1) {
                        $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($soc_origin), 'Echec de la suppression ' . $this->getLabel('of_the') . ' à fusionner');
                    }
                }

                if (!count($errors)) {
                    $db->commit();
                } else {
                    $db->rollback();
                }
            }

            $this->fetch($this->id);
        }

        return $errors;
    }

    public function addCommercial($id_user)
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            $rowid = (int) $this->db->getValue('societe_commerciaux', 'rowid', 'fk_user = ' . (int) $id_user . ' AND fk_soc = ' . (int) $this->id);

            if ($rowid) {
                $errors[] = 'Cet utilisateur est déjà enregistré en tant que commercial pour ' . $this->getLabel('this');
            } else {
                if ($this->db->insert('societe_commerciaux', array(
                            'fk_user' => $id_user,
                            'fk_soc'  => $this->id
                        )) <= 0) {
                    $errors[] = 'Echec de l\'enregistrement du commercial - ' . $this->db->db->lasterror();
                }
            }
        }

        return $errors;
    }

    public function removeCommercial($id_user)
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            $rowid = (int) $this->db->getValue('societe_commerciaux', 'rowid', 'fk_user = ' . (int) $id_user . ' AND fk_soc = ' . (int) $this->id);

            if (!$rowid) {
                $errors[] = 'Cet utilisateur n\'est pas enregistré en tant que commercial pour ' . $this->getLabel('this');
            } else {
                if ($this->db->delete('societe_commerciaux', 'rowid = ' . (int) $rowid) <= 0) {
                    $errors[] = 'Echec du retrait du commercial - ' . $this->db->db->lasterror();
                }
            }
        }

        return $errors;
    }

    public function checkSiren($field, $value, &$data = array())
    {
        $errors = array();

        $siret = '';
        $siren = '';

        switch ($field) {
            case 'siret':
                if (!$this->Luhn($value, 14)) {
                    $errors[] = 'SIREN invalide';
                }
                $siret = $value;
                $siren = substr($siret, 0, 9);
                break;

            case 'siren':
            default:
                if (!$this->Luhn($value, 9)) {
                    $errors[] = 'SIREN invalide';
                }
                $siren = $value;
                break;
        }

        if (!count($errors)) {
            if ($siren) {
                require_once DOL_DOCUMENT_ROOT . '/includes/nusoap/lib/nusoap.php';
                $xml_data = file_get_contents(DOL_DOCUMENT_ROOT . '/bimpcreditsafe/request.xml');

                $link = 'https://www.creditsafe.fr/getdata/service/CSFRServices.asmx';

                $sClient = new SoapClient($link . "?wsdl", array('trace' => 1));
                $returnData = $sClient->GetData(array("requestXmlStr" => str_replace("SIREN", str_replace(" ", "", $siren), $xml_data)));

                $returnData = htmlspecialchars_decode($returnData->GetDataResult);
                $returnData = str_replace("&", "et", $returnData);
                $returnData = str_replace(" < ", " ", $returnData);
                $returnData = str_replace(" > ", " ", $returnData);

                $result = simplexml_load_string($returnData);

                if (stripos($result->header->reportinformation->reporttype, "Error") !== false) {
                    $errors[] = 'Erreur lors de la vérification du n° SIREN (Code: ' . $result->body->errors->errordetail->code . ')';
                } else {
                    $note = "";
                    $limit = 0;

                    $summary = $result->body->company->summary;
                    $base = $result->body->company->baseinformation;
                    $branches = $base->branches->branch;
                    $adress = "" . $summary->postaladdress->address . " " . $summary->postaladresse->additiontoaddress;

                    foreach (array("", "2013") as $annee) {
                        $champ = "rating" . $annee;
                        if ($summary->$champ > 0) {
                            $note = dol_print_date(dol_now()) . ($annee == '' ? '' : '(Methode ' . $annee . ')') . " : " . $summary->$champ . "/100";
                            foreach (array("", "desc1", "desc2") as $champ2) {
                                $champT = $champ . $champ2;
                                if (isset($summary->$champT))
                                    $note .= " " . str_replace($summary->$champ, "", $summary->$champT);
                            }
                        }
                        $champ2 = "creditlimit" . $annee;
                        if (isset($summary->$champ2))
                            $limit = $summary->$champ2;
                    }

                    $tabCodeP = explode(" ", $summary->postaladdress->distributionline);
                    $codeP = $tabCodeP[0];
                    $ville = str_replace($tabCodeP[0] . " ", "", $summary->postaladdress->distributionline);
                    $tel = $summary->telephone;
                    $nom = $summary->companyname;

                    foreach ($branches as $branche) {
                        if ($branche->companynumber == $siret || ($siret == $siren && stripos($branche->type, "Siège") !== false)) {
                            $adress = $branche->full_address->address;
                            //$nom = $branche->full_address->name;
                            $codeP = $branche->postcode;
                            $ville = $branche->municipality;
                            $siret = $branche->companynumber;
                        }
                    }

                    $data = array(
                        'siren'             => $siren,
                        'siret'             => $siret,
                        "nom"               => "" . $nom,
                        "tva_intra"         => "" . $base->vatnumber,
                        "phone"             => "" . $tel,
                        "ape"               => "" . $summary->activitycode,
                        "note_private"      => "" . $note,
                        "address"           => "" . $adress,
                        "zip"               => "" . $codeP,
                        "town"              => "" . $ville,
                        "outstanding_limit" => "" . price(intval($limit)),
                        "capital"           => "" . str_replace(" Euros", "", $summary->sharecapital));
                }
            }
        }

        return $errors;
    }

    // Actions: 

    public function actionAddCommercial($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Commercial ajouté avec succès';

        $id_commercial = (isset($data['id_commercial']) ? (int) $data['id_commercial'] : 0);

        if (!$id_commercial) {
            $errors[] = 'Veuillez sélectionner un commercial';
        } else {
            $errors = $this->addCommercial($id_commercial);
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionRemoveCommercial($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Commercial retiré avec succès';

        $id_commercial = (isset($data['id_commercial']) ? (int) $data['id_commercial'] : 0);

        if (!$id_commercial) {
            $errors[] = 'Aucun commercial spécifié';
        } else {
            $errors = $this->removeCommercial($id_commercial);
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionMerge($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $id_soc_to_merge = BimpTools::getArrayValueFromPath($data, 'id_soc_to_merge', 0);
        $import_soc_to_merge_data = BimpTools::getArrayValueFromPath($data, 'import_soc_to_merge_data', 1);

        if (!$id_soc_to_merge) {
            $errors[] = BimpTools::ucfirst($this->getLabel()) . ' à fusionner non spécifié' . $this->e();
        } else {
            $errors = $this->mergeSocietes($id_soc_to_merge, $import_soc_to_merge_data);
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrides: 

    public function validatePost()
    {
        $errors = parent::validatePost();

        if (!count($errors)) {
            if (BimpTools::isSubmit('is_company')) {
                if ((int) BimpTools::getValue('is_company')) {
                    if ($this->isSirenRequired()) {
                        $siret = $this->getData('siret');
                        $siren = $this->getData('siren');

                        if ($siren === 'p') {
                            $siren = '';
                        }

                        if ($siret) {
                            if (!$siren || $siret !== $this->getInitData('siret')) {
                                $siren = substr($siret, 0, 9);
                            } elseif ($siren !== substr($siret, 0, 9)) {
                                $errors[] = 'Le n° SIRET et le n° SIREN ne correspondent pas';
                            }
                        }

                        if (!$siren) {
                            $errors[] = 'N° SIREN absent';
                        }

                        if (!count($errors)) {
                            if ($siren !== $this->getInitData('siren')) {
                                if (!(int) BimpTools::getValue('siren_ok', 0)) {
                                    $errors[] = 'Veuillez saisir un n° SIREN valide';
                                }
                            }
                        }
                    }
                } else {
                    if (BimpTools::isSubmit('prenom')) {
                        $prenom = BimpTools::getValue('prenom', '');
                        if ($prenom) {
                            $nom = strtoupper($this->getData('nom')) . ' ' . BimpTools::ucfirst($prenom);
                            $this->set('nom', $nom);
                        }
                    }
                    $this->set('fk_typent', 8);
                    $this->set('siren', 'p');
                    $this->set('siret', '');
                }
            }
        }

        return $errors;
    }

    public function validate()
    {
        $errors = parent::validate();

        if (!count($errors)) {
            
        }
    }
}
