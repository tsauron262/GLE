<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';

class Bimp_Societe extends BimpDolObject
{

    const SOLV_SOLVABLE = 0;
    const SOLV_A_SURVEILLER = 1;
    const SOLV_MIS_EN_DEMEURE = 2;
    const SOLV_DOUTEUX = 3;
    const SOLV_INSOLVABLE = 4;
    const SOLV_DOUTEUX_FORCE = 5;
    const SOLV_A_SURVEILLER_FORCE = 6;

    public static $types_ent_list = null;
    public static $types_ent_list_code = null;
    public static $effectifs_list = null;
    public $redirectMode = 4; //5;//1 btn dans les deux cas   2// btn old vers new   3//btn new vers old   //4 auto old vers new //5 auto new vers old
    public $soc_type = "";
    public static $status_list = array(
        0 => array('label' => 'Désactivé', 'icon' => 'fas_times', 'classes' => array('danger')),
        1 => array('label' => 'Actif', 'icon' => 'fas_check', 'classes' => array('success'))
    );
    public static $solvabilites = array(
        self::SOLV_SOLVABLE           => array('label' => 'Client solvable', 'icon' => 'fas_check', 'classes' => array('success')),
        self::SOLV_A_SURVEILLER       => array('label' => 'Client à surveiller', 'icon' => 'fas_exclamation', 'classes' => array('info')),
        self::SOLV_MIS_EN_DEMEURE     => Array('label' => 'Client mis en demeure', 'icon' => 'fas_exclamation-circle', 'classes' => array('warning')),
        self::SOLV_DOUTEUX            => array('label' => 'Client douteux', 'icon' => 'fas_exclamation-triangle', 'classes' => array('important')), // Ancien 1
        self::SOLV_INSOLVABLE         => array('label' => 'Client insolvable', 'icon' => 'fas_times', 'classes' => array('danger')), // Ancien 2
        self::SOLV_DOUTEUX_FORCE      => array('label' => 'Client douteux (forcé)', 'icon' => 'fas_exclamation-triangle', 'classes' => array('important')),
        self::SOLV_A_SURVEILLER_FORCE => array('label' => 'Client à surveiller (forcé) NPD', 'icon' => 'fas_exclamation', 'classes' => array('info')),
    );
    public static $ventes_allowed_max_status = self::SOLV_A_SURVEILLER;
    protected $reloadPage = false;
    
    public static $tabLettreCreditSafe = array(
        71 => array('A', '085E21', 'Risque très faible'),
        51 => array('B', '2BD15B', 'Risque faible'),
        30 => array('C', 'F1ED5C', 'Risque modéré'),
        21 => array('D', 'DEAF13', 'Risque Elevé'),
        1  => array('D', 'F6D35E', 'Risque très Elevé'),
        0 => array('E', 'F36139', 'Entreprise en situation de défaillance et ayant un très fort risque de radiation')
    );

//    public $fieldsWithAddNoteOnUpdate = array('solvabilite_status');

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
        global $user;
        switch ($field_name) {
            case 'outstanding_limit':
                return ($user->rights->bimpcommercial->admin_financier ? 1 : 0);

            case 'solvabilite_status':
            case 'status':
                return ($user->admin || $user->rights->bimpcommercial->admin_recouvrement ? 1 : 0);

            case 'commerciaux':
                if ($user->rights->bimpcommercial->commerciauxToSoc)
                    return 1;
                $comm = $this->getCommercial(false);
                if (!is_object($comm) || $comm->id == $user->id)
                    return 1;
                return 0;

            case 'relances_actives':
                return (int) $user->admin;

            case 'relances_infos':
                if ($user->admin || $user->rights->bimpcommercial->admin_deactivate_relances) {
                    return 1;
                }
                return 0;
        }

        return parent::canEditField($field_name);
    }

    public function canSetAction($action)
    {
        global $user;

        switch ($action) {
            case 'bulkEditField':
                // admin_recouvrement: autorisé pour le champ "solvabilite_status"
                return ($user->admin/* || $user->rights->bimpcommercial->admin_recouvrement */ ? 1 : 0);

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
            if (!in_array($this->db->getValue('c_typent', 'code', '`id` = ' . $id_typeent), array('TE_PRIVATE', 'TE_UNKNOWN'))) {
                return 1;
            }
            return 0;
        }

        return 1;
    }

    public function isClient()
    {
        return (in_array((int) $this->getData('client'), array(1, 2, 3)) ? 1 : 0);
    }

    public function isFournisseur()
    {
        return ((int) $this->getData('fournisseur') ? 1 : 0);
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
            if ($this->getData('parent') < 1) {//sinon maison mère
                if ($this->getData('fk_pays') == 1 || $this->getData('fk_pays') < 1)
                    if (strlen($this->getData("siret")) != 14 || !$this->Luhn($this->getData("siret"), 14)) {
                        $errors[] = "Siret client invalide :" . $this->getData("siret");
                    }
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
        if (in_array($action, array('addCommercial', 'removeCommercial', 'merge', 'checkSolvabilite'))) {
            if (!$this->isLoaded($errors)) {
                return 0;
            }
        }

        return parent::isActionAllowed($action, $errors);
    }

    public function isSirenOk()
    {
        if ($this->isLoaded()) {
            if (!$this->isSirenRequired()) {
                return 1;
            }

            if ((string) $this->getData('siret') && $this->Luhn($this->getData('siret'), 14)) {
                return 1;
            }
        }

        return 0;
    }

    public function isSirenRequired()
    {
        if ($this->isFournisseur() && !$this->isClient()) {
            return 0;
        }

        if (!BimpCore::getConf('siren_required', 0))
            return 0;

        $code = (string) $this->getData('siren');
        if (!$code) {
            $code = (string) $this->getData('siret');
        }

        if (in_array($code, array('p', 'h'))) {
            return 0;
        }

        $typecode = (string) $this->db->getValue('c_typent', 'code', 'id = ' . (int) $this->getData('fk_typent'));

        if (in_array($typecode, array('TE_PRIVATE', 'TE_ADMIN'))) {
            return 0;
        }

        if ((int) $this->getData('fk_pays') != 1)
            return 0;

        if ($this->dol_object->parent > 1)
            return 0;

        return 1;
    }

    public function showRelancesInfos()
    {
        return (int) ($this->isClient() && !$this->getData('relances_actives'));
    }

    public function isSolvable($object_name = '', &$warnings = array())
    {
        if (in_array($object_name, array('Bimp_Propal')) && in_array((int) $this->getData('solvabilite_status'), array(Bimp_Societe::SOLV_DOUTEUX, Bimp_Societe::SOLV_DOUTEUX_FORCE, Bimp_Societe::SOLV_MIS_EN_DEMEURE))) {
            $warnings[] = "Attention ce client à le statut : " . static::$solvabilites[$this->getData('solvabilite_status')]['label'];
            return 1;
        }

        if (in_array((int) $this->getData('solvabilite_status'), array(Bimp_Societe::SOLV_SOLVABLE, Bimp_Societe::SOLV_A_SURVEILLER, Bimp_Societe::SOLV_A_SURVEILLER_FORCE))) {
            return 1;
        }

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
            if ($this->can('edit') && $this->isEditable()) {
                $buttons[] = array(
                    'label'   => 'Créer Remote Token',
                    'icon'    => 'fas_gamepad',
                    'onclick' => $this->getJsActionOnclick('createRemoteToken')
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

            if ($this->isLoaded()) {
                $buttons[] = array(
                    'label'   => 'Demander ' . ((int) $this->getData('status') ? ' dés' : '') . 'activation du compte',
                    'icon'    => 'fas_paper-plane',
                    'onclick' => $this->getJsActionOnclick('statusChangeDemand', array(), array(
                        'confirm_msg' => 'Veuillez confirmer cette demande'
                    ))
                );
            }
        }

        return $buttons;
    }

    public function getDolObjectUpdateParams()
    {
        if ($this->isLoaded()) {
            global $user;
            return array($this->id, $user, 1, 1, 1);
        }
        return array();
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
            case 'marche':
                $tabSql = array();
                foreach ($values as $value)
                    $tabSql[] = '(ef.marche LIKE "' . $value . '" || ef.marche LIKE "%,' . $value . '" || ef.marche LIKE "%,' . $value . ',%" || ef.marche LIKE "' . $value . ',%")';
                $filters['marche'] = array(
                    'custom' => '(' . implode(" || ", $tabSql) . ')'
                );
                break;
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

    public function getInputExtra($field)
    {
        $html = '';
        if ($this->isLoaded()) {
            $value = $this->getData($field);
            if ($value) {
                switch ($field) {
                    case 'siret':
                        $html .= '<div style="text-align: right; margin-top: 10px">';
                        $onclick = 'onSocieteSiretOrSirenChange($(this).findParentByClass(\'inputContainer\').find(\'[name=' . $field . ']\'), \'' . $field . '\')';
                        $html .= '<span class="btn btn-default" onclick="' . $onclick . '">';
                        $html .= BimpRender::renderIcon('fas_cogs', 'iconLeft') . 'Vérifier';
                        $html .= '</span>';
                        $html .= '</div>';
                        break;

                    case 'siren':
                        $url = 'http://www.societe.com/cgi-bin/search?champs=' . $value;
                        $html .= '<div style="text-align: right; margin-top: 10px">';
                        $html .= '<a class="btn btn-default" href="' . $url . '" target="_blank">';
                        $html .= 'Vérifier' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight');
                        $html .= '</a>';
                        $html .= '</div>';
                        break;

                    case 'tva_intra':
                        $html .= '<div style="text-align: right; margin-top: 10px">';
                        $onclick = 'checkSocieteTva(\'' . $value . '\', \'Vérifier sur le site de la Commission Européenne\')';
                        $html .= '<span class="btn btn-default" onclick="' . $onclick . '">';
                        $html .= 'Vérifier' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight');
                        $html .= '</span>';
                        $html .= '</div>';
                        break;
                }
            }
        }

        return $html;
    }

    public function getUpdateJsCallback()
    {
        if ($this->reloadPage) {
            return 'bimp_reloadPage();';
        }

        return '';
    }

    // Getters données: 

    public function getRef($with_generic = true)
    {
        $return = '';
        if ($this->isClient()) {
            $return .=  $this->getData('code_client');
        } elseif ($this->isFournisseur()) {
            $return .=  $this->getData('code_fournisseur');
        }

        if ($return == '' && $with_generic) {
            $return .= $this->id;
        }

        return $return;
    }

    public function getSocieteLabel()
    {
        $label = '';

        if ($this->isClient()) {
            $label .= 'client';
        }

        if ($this->isFournisseur()) {
            $label .= ($label ? ' / ' : '') . 'fournisseur';
        }

        if (!$label) {
            $label = 'société';
        }

        return $label;
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
                return (float) round($result[0]['amount'], 2);
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
                                            if (!(int) $bdb->getValue('element_element', 'rowid', '`fk_source` = ' . $r['fk_propal'] . ' AND `sourcetype` = \'propal\'  AND `targettype` = \'commande\'') && !(int) $bdb->getValue('element_element', 'rowid', '`fk_source` = ' . $r['fk_propal'] . ' AND `sourcetype` = \'propal\'  AND `targettype` = \'contrat\'')) {
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
        $commerciaux = $this->getCommerciauxArray(false, $with_default);

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

    // Getters array: 

    public function getContactsArray($include_empty = true)
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
            self::$types_ent_list = self::getTypesSocietesArray(false, true);
            self::$types_ent_list_code = self::getTypesSocietesCodesArray(false, true);
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

    public function getCommerciauxArray($include_empty = false, $with_default = true)
    {
        if ($this->isLoaded()) {
            return self::getSocieteCommerciauxArray($this->id, $include_empty, $with_default);
        }

        return array();
    }

    public function getInputCommerciauxArray()
    {
        if ($this->isLoaded()) {
            return self::getSocieteCommerciauxArray($this->id, false, false);
        } else {
            global $user, $langs;

            if (BimpObject::objectLoaded($user)) {
                return array(
                    (int) $user->id => $user->getFullName($langs)
                );
            }
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
                $html .= ($icon ? BimpRender::renderIcon('fas_phone', 'iconLeft') : '') . BimpTools::displayPhone($phone);
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
                    $value = $this->getData($field);
                    if ($field === 'email') {
                        $html .= '<a href="mailto:' . $this->getData('email') . '">';
                    } elseif ($field === 'url') {
                        $url = $this->getData('url');
                        if (!preg_match('/^http.*/', $url)) {
                            $url = 'http://' . $url;
                        }
                        $html .= '<a href="' . $url . '" target="_blank">';
                    } elseif ($field == 'phone')
                        $value = BimpTools::displayPhone($value);

                    $html .= ($html ? '<br/>' : '') . ($icon ? BimpRender::renderIcon($icon_class, 'iconLeft') : '') . $value;

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
//            $url = DOL_URL_ROOT . '/comm/remx.php?id=' . $this->id
            $url = $this->getUrl() .'&navtab-maintabs=commercial&navtab-commercial_view=client_remises_except_list_tab';
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

        $edit = $this->canEditField('commerciaux');

        foreach ($users as $id_user => $label) {
            if ((int) $id_user) {
                $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $id_user);
                if (BimpObject::objectLoaded($user)) {
                    $html .= ($html ? '<br/>' : '') . $user->getLink() . ' ';

                    if ((int) $user->id !== $default_id_commercial) {
                        if ($edit) {
                            $onclick = $this->getJsActionOnclick('removeCommercial', array(
                                'id_commercial' => (int) $user->id
                                    ), array(
                                'confirm_msg' => htmlentities('Veuillez confirmer le retrait du commercial "' . $user->getName() . '"')
                            ));
                            $html .= BimpRender::renderRowButton('Retirer', 'fas_trash-alt', $onclick);
                        }
                    } else {
                        $html .= '&nbsp;<span class="small">(commercial par défaut)</span>';
                    }
                } else {
                    $html .= '<span class="danger">L\'utilisatuer #' . $id_user . ' n\'existe plus</span>';
                }
            }
        }

        if ($with_button && $edit) {
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

    public function renderCommerciauxInput()
    {
        if (!$this->canEditField('commerciaux'))
            return $this->displayCommerciaux();


        $html = '';
        $values = $this->getInputCommerciauxArray();
        $input = BimpInput::renderInput('search_user', 'soc_commerciaux_add_value');
        $content = BimpInput::renderMultipleValuesInput($this, 'soc_commerciaux', $input, $values);
        $html .= BimpInput::renderInputContainer('soc_commerciaux', '', $content, '', 0, 1, '', array('values_field' => 'soc_commerciaux'));

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

        if ($this->isLoaded()) {
            $new_comms = BimpTools::getPostFieldValue('soc_commerciaux', null);

            if (is_array($new_comms)) {
                $current_comms = $this->getCommerciauxArray(false, false);

                // Ajout des nouveaux commerciaux: 
                foreach ($new_comms as $id_comm) {
                    if (!(int) $id_comm) {
                        continue;
                    }

                    if (!array_key_exists($id_comm, $current_comms)) {
                        $comm = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $id_comm);

                        if (!BimpObject::objectLoaded($comm)) {
                            $warnings[] = 'Le commercial d\'ID ' . $id_comm . ' n\'existe pas';
                            continue;
                        }

                        $comm_errors = $this->addCommercial($id_comm);
                        if (count($comm_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($comm_errors, 'Erreur(s) lors de l\'ajout du commercial "' . $comm->getName() . '"');
                        }
                    }
                }

                // Suppr des commerciaux: 
                foreach ($current_comms as $id_comm => $comm_label) {
                    if (!in_array((int) $id_comm, $new_comms)) {
                        $comm_errors = $this->removeCommercial($id_comm);
                        if (count($comm_errors)) {
                            $comm = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $id_comm);
                            if (BimpObject::objectLoaded($comm)) {
                                $warnings[] = BimpTools::getMsgFromArray($comm_errors, 'Erreur(s) lors du retrait du commercial "' . $comm->getName() . '"');
                            } else {
                                $warnings[] = BimpTools::getMsgFromArray($comm_errors, 'Erreur(s) lors du retrait du commercial #' . $id_comm);
                            }
                        }
                    }
                }
            }
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
                    BimpTools::resetDolObjectErrors($object);
                    $result = $object->update($object->id, $user, 0, 1, 1, 'merge');
                    if ($result < 0) {
                        $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($object), 'Echec de la mise à jour des données  ' . $this->getLabel('of_the') . ' à conserver');
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

    public function checkSiren($field, $value, &$data = array(), &$warnings = array())
    {
        $errors = array();

        $siret = '';
        $siren = '';

        $value = str_replace(' ', '', $value);

        switch ($field) {
            case 'siret':
                if (!$this->Luhn($value, 14)) {
                    $errors[] = 'SIRET invalide';
                }
                $siret = $value;
                $siren = substr($siret, 0, 9);
                $data['siret'] = $siret;
                $data['siren'] = $siren;
                break;

            case 'siren':
            default:
//                if (!$this->Luhn($value, 9)) { // Apparemment ça bug... 
//                    $errors[] = 'SIREN invalide (' . $value . ')';
//                }
                if (strlen($value) != 9) {
                    $errors[] = 'SIREN invalide';
                }
                $siren = $value;
                $data['siren'] = $siren;
                break;
        }
        if (!count($errors) && BimpTools::isModuleDoliActif('BIMPCREDITSAFE')) {
            if ($siret || $siren) {
                require_once DOL_DOCUMENT_ROOT . '/includes/nusoap/lib/nusoap.php';

                $xml_data = file_get_contents(DOL_DOCUMENT_ROOT . '/bimpcreditsafe/request.xml');

                $link = 'https://www.creditsafe.fr/getdata/service/CSFRServices.asmx';

                $sClient = new SoapClient($link . "?wsdl", array('trace' => 1));
                $returnData = $sClient->GetData(array("requestXmlStr" => str_replace("SIREN", ($siret ? $siret : $siren), $xml_data)));

                $returnData = htmlspecialchars_decode($returnData->GetDataResult);
                $returnData = str_replace("&", "et", $returnData);
                $returnData = str_replace(" < ", " ", $returnData);
                $returnData = str_replace(" > ", " ", $returnData);

                global $bimpLogPhpWarnings;
                if (is_null($bimpLogPhpWarnings)) {
                    $bimpLogPhpWarnings = true;
                }

                // On déactive les logs warnings php (Trop de logs). 
                $prevLogWarnings = $bimpLogPhpWarnings;
                $bimpLogPhpWarnings = false;

                $result = simplexml_load_string($returnData);

                $bimpLogPhpWarnings = $prevLogWarnings;

                if (!is_object($result)) {
                    $warnings[] = 'Le service CreditSafe semble indisponible. Le n° ' . $field . ' ne peut pas être vérifié pour le moment';
                } elseif (stripos($result->header->reportinformation->reporttype, "Error") !== false) {
                    $errors[] = 'Erreur lors de la vérification du n° ' . ($siret ? 'SIRET' : 'SIREN') . ' (Code: ' . $result->body->errors->errordetail->code . ')';
                } else {
                    $note = "";
                    $limit = 0;

                    $summary = $result->body->company->summary;
                    $base = $result->body->company->baseinformation;
                    $branches = $base->branches->branch;
                    $adress = "" . $summary->postaladdress->address . " " . $summary->postaladresse->additiontoaddress;
                    
                    $lettrecreditsafe = 0;
                    foreach (array("", "2013") as $annee) {
                        $champ = "rating" . $annee;
                        if ($summary->$champ > 0) {
                            $lettrecreditsafe = $summary->$champ;
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
                        if (($siret && $branche->companynumber == $siret) || (!$siret && stripos($branche->type, "Siège") !== false)) {
                            $adress = $branche->full_address->address;
                            $nom = $branche->full_address->name;
                            $codeP = $branche->postcode;
                            $ville = $branche->municipality;
                            if (!$siret) {
                                $siret = (string) $branche->companynumber;
                            }
                            break;
                        }
                    }

                    if ($limit) {
                        $note .= ($note ? ' - ' : '') . 'Limite: ' . price(intval($limit)) . ' €';
                    }

                    $data = array(
                        'siren'             => $siren,
                        'siret'             => $siret,
                        "nom"               => "" . $nom,
                        "tva_intra"         => "" . $base->vatnumber,
                        "phone"             => "" . $tel,
                        "ape"               => "" . $summary->activitycode,
                        "notecreditsafe"    => "" . $note,
                        "lettrecreditsafe"  => "" . $lettrecreditsafe,
                        "address"           => "" . $adress,
                        "zip"               => "" . $codeP,
                        "town"              => "" . $ville,
                        "outstanding_limit" => "" . intval($limit),
                        "capital"           => "" . str_replace(" Euros", "", $summary->sharecapital));
                }
            }
        }

        return $errors;
    }
    
    public function getCreditSafeLettre($noHtml = false){
        global $modeCSV;
        $note = $this->getData('lettrecreditsafe');
        foreach(self::$tabLettreCreditSafe as $id => $tabLettre){
            if($note >= $id){
                if($noHtml || $modeCSV)
                    return $tabLettre[0];
                else
//                    return BimpRender::renderPopoverData
                    return '<span class="bs-popover" '.BimpRender::renderPopoverData($note.'/100 '.$tabLettre[2]).'><img src="https://placehold.it/35/'.$tabLettre[1].'/fff&amp;text='.$tabLettre[0].'" alt="User Avatar" class="img-circle"></span>';
            }
        }
    }
    
    public function traiteNoteCreditSafe(){
        
    }

    public function checkSolvabiliteStatus()
    {
        if (!$this->isLoaded()) {
            return;
        }

        if (!(int) BimpCore::getConf('check_solvabilite_client', 0)) {
            return;
        }

        $cur_status = (int) $this->getData('solvabilite_status');

        if (in_array($cur_status, array(self::SOLV_INSOLVABLE, self::SOLV_DOUTEUX_FORCE, self::SOLV_A_SURVEILLER_FORCE))) {
            return;
        }

        $new_status = $cur_status;

        $total_unpaid = 0;
        $total_med = 0;
        $total_contentieux = 0;

        $filters = array(
            'fk_soc'    => (int) $this->id,
            'paye'      => 0,
            'fk_statut' => 1,
        );

        // Pour les clients en contentieux, toutes les factures doivent être réglées pour repasser en "A surveiller"
        if ($cur_status !== self::SOLV_DOUTEUX) {
            $filters['date_lim_reglement'] = array(
                'operator' => '<',
                'value'    => date('Y-m-d')
            );
        }

        $factures = BimpCache::getBimpObjectObjects('bimpcommercial', 'Bimp_Facture', $filters);

        if (is_array($factures)) {
            foreach ($factures as $fac) {
                $rap = $fac->getRemainToPay();

                if ($rap > 0) {
                    $total_unpaid += $rap;
                }

                $nb_relances = (int) $fac->getData('nb_relance');

                if ($nb_relances === 4) {
                    $total_med += $rap;
                } elseif ($nb_relances === 5) {
                    $total_contentieux += $rap;
                }
            }
        }

        BimpObject::loadClass('bimpcommercial', 'BimpRelanceClientsLine');
        $has_contentieux = (int) $this->db->getCount('bimp_relance_clients_line', 'id_client = ' . (int) $this->id . ' AND  relance_idx = 5 AND status = ' . BimpRelanceClientsLine::RELANCE_CONTENTIEUX);

        if ($total_unpaid > 0) {
            if ($total_contentieux > 0) {
                $new_status = self::SOLV_DOUTEUX;
            } elseif ($cur_status !== self::SOLV_DOUTEUX) {
                if ($total_med > 0) {
                    $new_status = self::SOLV_MIS_EN_DEMEURE;
                } elseif ($has_contentieux) {
                    $new_status = self::SOLV_A_SURVEILLER;
                } else {
                    $new_status = self::SOLV_SOLVABLE;
                }
            }
        } else {
            if ($has_contentieux) {
                $new_status = self::SOLV_A_SURVEILLER;
            } else {
                $new_status = self::SOLV_SOLVABLE;
            }
        }

        if ($new_status !== $cur_status) {
            $err = $this->updateField('solvabilite_status', $new_status, null, true, true);

            if (!count($err)) {
                $this->onNewSolvabiliteStatus('auto');
            } else {
                BimpCore::addlog('Echec de l\'enregistrement du nouveau statut de solvabilité d\'un client', Bimp_Log::BIMP_LOG_URGENT, 'bimpcore', $this, array(
                    'Statut courant' => $cur_status . ' (' . self::$solvabilites[$cur_status]['label'] . ')',
                    'Nouveau statut' => $new_status . ' (' . self::$solvabilites[$new_status]['label'] . ')',
                    'Erreurs'        => $err
                ));
            }
        }
    }

    public function onNewOutstanding_limit($oldLimit)
    {
        if ($this->isLoaded()) {
            $emails = '';
            $commerciaux = $this->getIdCommercials();

            foreach ($commerciaux as $id_user) {
                $email = $this->db->getValue('user', 'email', 'rowid = ' . $id_user);
                if ($email) {
                    $emails .= ($emails ? ',' : '') . BimpTools::cleanEmailsStr($email);
                }
            }

            $subject = 'Modification encours client ' . $this->getName();
            $msg = 'L\'encours du client ' . $this->getLink() . ' a été modifié
<br/>Nouvel encours : ' . $this->getData('outstanding_limit') . ' €
<br/>Ancien encours : ' . $oldLimit . ' €';


            if ($emails != '')
                mailSyn2($subject, $emails, '', $msg);
            
            if(strlen($this->getData('siren')) == 9){
                $this->db->db->query("UPDATE ".MAIN_DB_PREFIX."societe SET outstanding_limit = '".$this->getData('outstanding_limit')."' WHERE siren = '".$this->getData('siren')."'");
            }
        }
    }

    public function onNewSolvabiliteStatus($mode = 'auto')
    {
        $update_infos = '';
        switch ($mode) {
            case 'auto':
                $update_infos = 'Mise à jour automatique';
                break;

            case 'man':
                $update_infos = 'Mise à jour manuelle';
                break;

            default:
                return;
        }

        if ($this->isLoaded()) {
            $status = (int) $this->getData('solvabilite_status');
            BimpObject::createBimpObject('bimpcore', 'Bimp_Client_Suivi_Recouvrement', array('id_societe' => $this->id, 'mode' => 4, 'sens' => 2, 'content' => 'Changement ' . ($mode == 'auto' ? 'auto' : 'manuel') . ' statut solvabilitée : ' . self::$solvabilites[$status]['label']));

            $emails = BimpCore::getConf('emails_notify_solvabilite_client_change_' . $mode, '');

            if ($emails) {

                $msg = 'Le client ' . $this->getLink() . ' a été mis au statut ' . self::$solvabilites[$status]['label'] . "\n";

                if ($update_infos) {
                    $msg .= ' (' . $update_infos . ')';
                }

                $msg .= "\n";

                $msg .= "\n" . 'Code comptable du client: ' . $this->getData('code_compta');

                global $user, $langs;

                if (BimpObject::objectLoaded($user)) {
                    $msg .= "\n" . 'Utilisateur: ' . $user->getFullName($langs);
                }

                $subject = 'Mise à jour solvabilité client ' . $this->getRef();

                mailSyn2($subject, $emails, '', $msg);

                $emails = '';
                $commerciaux = $this->getIdCommercials();

                foreach ($commerciaux as $id_user) {
                    $email = $this->db->getValue('user', 'email', 'rowid = ' . $id_user);
                    if ($email) {
                        $emails .= ($emails ? ',' : '') . BimpTools::cleanEmailsStr($email);
                    }
                }

                mailSyn2($subject, $emails, '', $msg);
            }
        }
//        if (!in_array($field, array('solvabilite_status', 'status'))) {
//            return;
//        }
//        if ($this->isLoaded() && $this->field_exists('status_logs')) {
//            $logs = (string) $this->getData('status_logs');
//            if ($logs) {
//                $logs .= '<br/>';
//            }
//            global $user, $langs;
//            $logs .= ' - <strong>Le ' . date('d / m / Y à H:i') . '</strong> par ' . $user->getFullName($langs) . ': ';
//
//            switch ($field) {
//                case 'solvabilite_status':
//                    $logs .= 'passage au statut "' . self::$solvabilites[(int) $this->getData('solvabilite_status')] . '"';
//                    break;
//
//                case 'status':
//                    $logs .= ' ' . (!(int) $this->getData('status') ? 'dés' : '') . 'activation du client';
//                    break;
//
//                default:
//                    return;
//            }
//
//            if ($udpate_infos) {
//                $logs .= ' (' . $udpate_infos . ')';
//            }
//            $this->updateField('status_logs', $logs, null, true);
//        }
    }

    public function onNewStatus()
    {
        if ($this->isLoaded() && $this->isClient()) {
            $id_user = (int) $this->getData('id_user_status_demand');

            if ($id_user) {
                $demand_user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_user);

                if (BimpObject::objectLoaded($demand_user)) {
                    $email = $demand_user->getData('email');

                    if ($email) {
                        global $user;
                        $email = BimpTools::cleanEmailsStr($email);
                        $msg = 'Bonjour, ' . "\n\n";
                        $msg .= 'Suite à votre demande, le client ' . $this->getLink() . ' a été ';
                        if ((int) $this->getData('status')) {
                            $subject = 'Client activé';
                            $msg .= 'activé';
                        } else {
                            $subject = 'Client désactivé';
                            $msg .= 'désactivé';
                        }

                        $msg .= ' par ' . $user->getNomUrl();

                        mailSyn2($subject, $email, '', $msg);
                        $this->updateField('id_user_status_demand', 0);
                    }
                }
            }
        }
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

    public function actionCreateRemoteToken($data, &$success)
    {
        $errors = $warnings = array();
        $success = 'Création réussie';
        $remoteToken = BimpObject::getInstance('bimpsupport', 'BS_Remote_Token');
        $errors = BimpTools::merge_array($errors, $remoteToken->validateArray(array('id_client' => $this->id)));
        $errors = BimpTools::merge_array($errors, $remoteToken->create());
        if (!count($errors)) {
            $warnings[] = "Token : " . $remoteToken->getData('token') . '<br/>'
                    . 'Server : <a href="stun.bimp.fr:' . $remoteToken->getData('port') . '">stun.bimp.fr:' . $remoteToken->getData('port') . '</a><br/>'
                    . 'Mdp : ' . $remoteToken->getData('mdp') . '<br/>'
                    . '<a href="' . DOL_URL_ROOT . "/bimpsupport/privatekey.php" . '">Certificat</a><br/>';
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionCheckSolvabilite($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Solvabilité vérifiée avec succès';

        $this->checkSolvabiliteStatus();

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionStatusChangeDemand($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        if ($this->isLoaded($errors)) {
            global $user;

            $emails = BimpCore::getConf('emails_notify_status_client_change', '');
            if ($emails) {
                $status = (int) $this->getData('status');

                if ($status) {
                    $op = 'déactivation';
                } else {
                    $op = 'activation';
                }

                if ($this->isClient()) {
                    $label = 'client';
                } else {
                    $label = 'fournisseur';
                }

                $subject = 'Demande ' . $op . ' ' . $label;
                $msg = 'Bonjour, ' . "\n\n";
                $msg .= 'L\'utilisateur ' . $user->getNomUrl() . ' demande ' . ($status ? 'la' : 'l\'') . ' ' . $op;
                $msg .= ' du ' . $label . ' ' . $this->getLink();

                mailSyn2($subject, $emails, '', $msg);
                $this->updateField('id_user_status_demand', $user->id);

                $success = 'Demande envoyée';
            } else {
                $errors[] = 'Aucune adresse email configurée pour cette demande';
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrides: 

    public function fetch($id, $parent = null)
    {
        $return = parent::fetch($id, $parent);
//        if ($this->isFournisseur())
//            $this->redirectMode = 5;
        return $return;
    }

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
                            if ($siret !== $this->getInitData('siret')) {
//                                if (!(int) BimpTools::getValue('siren_ok', 0)) {
//                                if(!$this->isSirenOk()){
//                                    $errors[] = 'Veuillez saisir un n° SIRET valide : '.$this->getData('siret');
//                                }
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
            if ($this->isSirenRequired()) {
                $siret = $this->getData('siret');
                if (!$siret) {
                    $errors[] = 'Numéro SIRET absent';
                } elseif (!$this->Luhn($siret, 14)) {
                    $errors[] = 'Numéro SIRET invalide';
                } else {
                    $this->set('siren', substr($siret, 0, 9));
                }
            }
        }
        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $init_client = $this->getInitData('client');
        $init_fourn = $this->getInitData('fournisseur');
        $init_solv = (int) $this->getInitData('solvabilite_status');
        $init_status = (int) $this->getInitData('status');
        $init_outstanding_limit = $this->getInitData('outstanding_limit');

        $errors = parent::update($warnings, $force_update);

        if (!count($errors)) {
            if ($init_status !== (int) $this->getData('status')) {
                $this->onNewStatus();
            }
            if ($init_solv !== (int) $this->getData('solvabilite_status')) {
                $this->onNewSolvabiliteStatus('man');
            }
            if ($init_outstanding_limit != $this->getData('outstanding_limit'))
                $this->onNewOutstanding_limit($init_outstanding_limit);
        }

        $fc = BimpTools::getValue('fc');

        if (in_array($fc, array('client', 'fournisseur')) && ($init_client != $this->getData('client') || $init_fourn != $this->getData('fournisseur'))) {
            $this->reloadPage = true;
        }

        return $errors;
    }

    // Méthodes statiques: 

    public static function checkSolvabiliteStatusAll()
    {
        $rows = self::getBdb()->getRows('societe', 'client = 1', null, 'array', array('rowid'));

        if (!is_null($rows)) {
            foreach ($rows as $r) {
                $soc = BimpObject::getInstance('bimpcore', 'Bimp_Client', (int) $r['rowid']);

                if (BimpObject::objectLoaded($soc)) {
                    $soc->checkSolvabiliteStatus();

                    if ((int) $soc->getData('solvabilite_status') > 0) {
                        echo '#' . $r['rowid'] . ': ' . $soc->getData('solvabilite_status') . '<br/>';
                    }
                }
            }
        }
    }
}
