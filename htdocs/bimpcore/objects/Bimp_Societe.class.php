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
        71 => array('A', 'success', 'Risque très faible'),
        51 => array('B', 'info', 'Risque faible'),
        30 => array('C', 'dark', 'Risque modéré'),
        21 => array('D', 'warning', 'Risque Elevé'),
        1  => array('D', 'warning', 'Risque très Elevé'),
        0  => array('E', 'danger', 'Entreprise en situation de défaillance et ayant un très fort risque de radiation')
    );
    public static $types_educ = array(
        ''   => '',
        '1R' => 'Etudes supérieures',
        'HS' => 'Lycée',
        'M8' => 'Institutions éducatives',
        'VO' => 'Ecole primaire',
        'VQ' => 'Ecole secondaire',
        'E4' => 'Enseignants / étudiants'
    );
    public static $regions = array(
        1 => array(
            'A' => array('01', 69),
            'B' => array('07', 26, 38, 73, 74),
            'C' => array(63, '03', 15, 42, 43),
            'D' => array(70, 21, 25, 39, 58, 71, 89, 90)
        ),
        2 => array(
            'A' => array('09', 11, 12, 30, 31, 32, 34, 46, 48, 65, 66, 81, 82),
            'B' => array('04', '05', '06', 13, 83, 84, '2A', '2B'),
            'C' => array(16, 17, 19, 23, 24, 33, 40, 47, 64, 79, 86, 87)
        ),
        3 => array(
            'A' => array(75, 77, 78, 91, 92, 93, 94, 95),
            'B' => array('02', 59, 60, 62, 80),
            'C' => array('08', 10, 51, 52, 54, 55, 57, 67, 68, 88)
        ),
        4 => array(
            'A' => array(18, 28, 36, 37, 41, 44, 45, 49, 53, 72, 85),
            'B' => array(22, 29, 35, 56),
            'C' => array(14, 27, 50, 61, 76)
        ),
        5 => array(
            'A' => array(971, 972, 973, 974, 976, 977, 98)
        )
    );
    public static $anonymization_fields = array('nom', 'name_alias', 'address', 'zip', 'town', 'email', 'skype', 'url', 'phone', 'fax', 'siren', 'siret', 'ape', 'idprof4', 'idprof5', 'idprof6', 'tva_intra');
    private $debug = array();

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

        return (int) ($user->admin || $user->rights->societe->supprimer);
    }

    public function canEditField($field_name)
    {
        global $user;
//        if($field_name == 'datec' && $user->id != 62)
//            return 0;
        if ($this->isLoaded() && $this->isAnonymised()) {
            // Champs anonymisés non éditables par user: doit utiliser action "Annuler anonymisation" (revertAnonymization).
            if (in_array($field_name, self::$anonymization_fields)) {
                return 0;
            }
        }

        global $user;
        switch ($field_name) {
            case 'outstanding_limit_atradius':
            case 'outstanding_limit_icba':
            case 'outstanding_limit_manuel':
            case 'outstanding_limit_credit_check':
            case 'date_depot_icba':
            case 'date_atradius':
                if ($user->admin || $user->rights->bimpcommercial->admin_recouvrement || $user->rights->bimpcommercial->admin_compta || $user->rights->bimpcommercial->admin_financier) {
                    return 1;
                }
                return 0;

            case 'outstanding_limit':
                return 0;

            case 'outstanding_limit_credit_safe':
                return 0;

            case 'solvabilite_status':
                return ($user->admin ||
                        $user->rights->bimpcommercial->gestion_recouvrement ||
                        $user->rights->bimpcommercial->admin_recouvrement ? 1 : 0);

            case 'status':
                return (($user->admin || $user->rights->bimpcommercial->admin_recouvrement) ? 1 : 0);

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
            case 'deleteInCompta':
                return ($user->id == 460) ? 1 : 0;
            case 'bulkEditField':
                // admin_recouvrement: autorisé pour le champ "solvabilite_status"
                return ($user->admin/* || $user->rights->bimpcommercial->admin_recouvrement */ ? 1 : 0);

            case 'addCommercial':
            case 'removeCommercial':
            case 'merge':
                return $this->canEdit();

            case 'relancePaiement':
            case 'setActivity':
                return 1;

            case 'anonymize':
            case 'revertAnonymization':
            case 'listClientsToExcludeForCreditLimits':
                return (int) $user->admin;
        }

        return (int) parent::canSetAction($action);
    }

    public function canSwitchIsCompany()
    {
        if (!$this->isLoaded()) {
            return 1;
        }

        global $user;

        return (int) ($user->admin || $user->login == 'jc.cannet' || $user->rights->commande->supprimer);
    }

    // Getters booléens: 

    public function isCompany()
    {
        if (BimpObject::objectLoaded($this->dol_object)) {
            if (in_array($this->dol_object->typent_code, array('TE_PRIVATE', 'TE_UNKNOWN'))) {
                return 0;
            }

            return 1;
        } elseif ($this->isLoaded()) {
            $id_typeent = (int) $this->getData('fk_typent');
            if ($id_typeent) {
                if (!in_array($this->db->getValue('c_typent', 'code', '`id` = ' . $id_typeent), array('TE_PRIVATE', 'TE_UNKNOWN'))) {
                    return 1;
                }
                return 0;
            }
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
        if ($this->isSirenRequired()) {
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
        if(BimpCore::getConf('validation_strict')){
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
        }

        if (count($errors))
            return 0;

        return 1;
    }

    public function isActionAllowed($action, &$errors = array())
    {
        if (in_array($action, array('addCommercial', 'removeCommercial', 'merge', 'checkSolvabilite', 'releveFacturation', 'anonymize', 'revertAnonymization', 'setActivity'))) {
            if (!$this->isLoaded($errors)) {
                return 0;
            }
        }

        switch ($action) {
            case 'anonymize':
                if ($this->isAnonymised()) {
                    $errors[] = 'Ce client est déjà anonymisé';
                    return 0;
                }
                return 1;

            case 'revertAnonymization':
                if (!$this->isAnonymised()) {
                    $errors[] = 'Ce client n\'est pas anonymisé';
                    return 0;
                }
                $id_saved_data = (int) $this->db->getValue('societe_saved_data', 'id', 'type = \'societe\' AND id_object = ' . (int) $this->id);
                if (!$id_saved_data) {
                    $errors[] = 'Pas de données sauvegardées';
                    return 0;
                }
                return 1;

            case 'setActivity':
                if ($this->isAnonymised()) {
                    $errors[] = 'Ce client a été anonymisé';
                    return 0;
                }

                return 1;
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

        if (!(int) BimpCore::getConf('siren_required')) {
            return 0;
        }

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
            $warnings[] = "Attention ce client a le statut : " . static::$solvabilites[$this->getData('solvabilite_status')]['label'];
            return 1;
        }

        if (in_array((int) $this->getData('solvabilite_status'), array(Bimp_Societe::SOLV_SOLVABLE, Bimp_Societe::SOLV_A_SURVEILLER, Bimp_Societe::SOLV_A_SURVEILLER_FORCE))) {
            return 1;
        }

        return 0;
    }

    public function isAdministration()
    {
        if (isset($this->dol_object->typent_code) && $this->dol_object->typent_code) {
            return (int) in_array($this->dol_object->typent_code, array('TE_ADMIN', 'TE_OTHER_ADM'));
        }

        $id_type = (int) $this->getData('fk_typent');

        if ($id_type) {
            $code = $this->db->getValue('c_typent', 'code', 'id = ' . $id_type);

            if ($code) {
                return (int) in_array($code, array('TE_ADMIN', 'TE_OTHER_ADM'));
            }
        }

        return 0;
    }

    public function isAnonymizable(&$errors = array())
    {
        $check = 1;
        if ($this->isLoaded($errors)) {
            if ($this->isAnonymised()) {
                $errors[] = ucfirst($this->getLabel('this')) . ' est déjà anonymisé';
                $check = 0;
            }

            if ((int) $this->getData('solvabilite_status') !== self::SOLV_SOLVABLE) {
                $errors[] = ucfirst($this->getLabel('this')) . ' n\'est pas solvable';
                $check = 0;
            }

            if (!count($errors)) {
                $available_discounts = $this->getAvailableDiscountsAmounts();
                if ($available_discounts) {
                    $errors[] = ucfirst($this->getLabel('this')) . ' dispose de remises non consommées (' . BimpTools::displayMoneyValue($available_discounts) . ')';
                    $check = 0;
                }

                $convertible_amounts = $this->getConvertibleToDiscountAmount();
                if ($convertible_amounts) {
                    $errors[] = ucfirst($this->getLabel('this')) . ' dispose de trop perçus non convertis en remise (' . BimpTools::displayMoneyValue($convertible_amounts) . ')';
                    $check = 0;
                }

                $paiements_inc = $this->getTotalPaiementsInconnus();
                if ($paiements_inc) {
                    $errors[] = ucfirst($this->getLabel('this')) . ' dispose de paiements inconnus pour un montant de (' . BimpTools::displayMoneyValue($paiements_inc) . ')';
                    $check = 0;
                }
            }
        }

        return $check;
    }

    public function isAnonymised()
    {
        return (int) $this->getData('is_anonymized');
//        if ($this->isLoaded()) {
//            $cache_key = 'is_client_' . $this->id . '_anonymised';
//
//            if (!isset(self::$cache[$cache_key])) {
//                $log = BimpObjectLog::getLastObjectLogByCodes($this, array(
//                            'ANONYMISED', 'UNANONYMISED'
//                ));
//
//                if (BimpObject::objectLoaded($log) && $log->getData('code') === 'ANONYMISED') {
//                    self::$cache[$cache_key] = 1;
//                } else {
//                    self::$cache[$cache_key] = 0;
//                }
//            }
//
//            return self::$cache[$cache_key];
//        }
//
//        return 0;
    }

    // Getters params: 

    public function getFilesDir()
    {
        global $conf;
        if ($this->isLoaded()) {
            return $conf->societe->multidir_output[$this->dol_object->entity].'/' . $this->id . '/';
        } else {
            echo 'NOT LOADED';
            exit;
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

        return DOL_URL_ROOT . '/' . $page . '.php?modulepart=societe&entity='.$this->dol_object->entity.'&file=' . urlencode($file);
    }

    public function getActionsButtons()
    {
        $buttons = array();

        if ($this->isLoaded()) {

            if ($this->canSetAction('deleteInCompta')) {
                $buttons[] = array(
                    'label'   => 'DeleteInCompta',
                    'icon'    => 'fas_time',
                    'onclick' => $this->getJsActionOnclick('deleteInCompta')
                );
            }

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

            if ($this->isActionAllowed('releveFacturation') && $this->canSetAction('releveFacturation')) {
                $sql = 'SELECT datef FROM ' . MAIN_DB_PREFIX . 'facture WHERE fk_soc = ' . $this->id . ' AND fk_statut IN (1,2,3)';
                $sql .= ' ORDER BY datef ASC LIMIT 1';

                $result = $this->db->executeS($sql, 'array');

                if (isset($result[0]['datef'])) {
                    $debut = $result[0]['datef'];
                } else {
                    $debut = date('Y-m-d');
                }

                $buttons[] = array(
                    'label'   => 'Relevé facturation',
                    'icon'    => 'fas_clipboard-list',
                    'onclick' => $this->getJsActionOnclick('releveFacturation', array(
                        'date_debut' => $debut,
                        'date_fin'   => date('Y-m-d')
                            ), array(
                        'form_name' => 'releve_facturation'
                    ))
                );
            }

            if ($this->isLoaded()) {
                //if ($user->admin) {
                $buttons[] = array(
                    'label'   => 'Relevé interventions',
                    'icon'    => 'fas_clipboard-list',
                    'onclick' => $this->getJsActionOnclick('releveIntervention', array(
                        'id_client' => $this->id
                            ), array(
                        'form_name' => 'releverInter'
                    ))
                );
                //}

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

    public function getAllTiersListExtraButtons()
    {
        $buttons = array();

        if ($this->isLoaded() && $this->can('view')) {
            if ($this->isClient()) {
                $url = DOL_URL_ROOT . '/bimpcore/index.php?fc=client&id=' . $this->id;

                $buttons[] = array(
                    'label'   => 'Fiche Client',
                    'icon'    => 'fas_user-circle',
                    'onclick' => 'window.open(\'' . $url . '\');'
                );
            }

            if ($this->isFournisseur()) {
                $url = DOL_URL_ROOT . '/bimpcore/index.php?fc=fournisseur&id=' . $this->id;

                $buttons[] = array(
                    'label'   => 'Fiche Fournisseur',
                    'icon'    => 'fas_building',
                    'onclick' => 'window.open(\'' . $url . '\');'
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

    public function getDefaultRib($createIfNotExist = true)
    {
        $rib = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_SocBankAccount', array('default_rib' => 1, 'fk_soc' => $this->id), true, false);

        if (BimpObject::objectLoaded($rib)) {
            return $rib;
        }

        if ($createIfNotExist) {
            $rib = BimpObject::createBimpObject('bimpcore', 'Bimp_SocBankAccount', array(
                        'fk_soc'      => $this->id,
                        'label'       => 'Default',
                        'default_rib' => 1
                            ), true);

            if (BimpObject::objectLoaded($rib)) {
                return $rib;
            }
        }

        return null;
    }

    public function getDefaultRibId()
    {
        if ($this->isLoaded()) {
            return (int) $this->db->getValue('societe_rib', 'rowid', 'fk_soc = ' . (int) $this->id . ' AND default_rib = 1', 'rowid', 'desc');
        }

        return 0;
    }

    public function getPdfModelFileName($model)
    {
        if (!$this->isLoaded()) {
            return '';
        }

        switch ($model) {
            case 'cepa':
                $rib = $this->getDefaultRib(true);
                if (BimpObject::objectLoaded($rib)) {
                    return $rib->getFileName(false, '');
                }
                break;
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
                        return $user->dol_object->getFullName(1);
                    }
                } else {
                    return 'Aucun';
                }
                break;
        }

        return parent::getCustomFilterValueLabel($field_name, $value);
    }

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, $main_alias = 'a', &$errors = array(), $excluded = false)
    {
        switch ($field_name) {
            case 'custom':
                $tabSql = array();
//                print_r($values);die;
                foreach ($values as $value) {
                    $tabSql[] = '(' . $value['value'] . ')';
                }

                $filters[$main_alias . '___custom_custom'] = array(
                    'custom' => '(' . implode(" || ", $tabSql) . ')'
                );
                break;
            case 'marche':
                $tabSql = array();
                foreach ($values as $value) {
                    $tabSql[] = '(ef.marche LIKE "' . $value . '" || ef.marche LIKE "%,' . $value . '" || ef.marche LIKE "%,' . $value . ',%" || ef.marche LIKE "' . $value . ',%")';
                }

                $filters[$main_alias . '___custom_marche'] = array(
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

                $sql = '';

                $nbCommerciaux = 'SELECT COUNT(sc.rowid) FROM ' . MAIN_DB_PREFIX . 'societe_commerciaux sc WHERE sc.fk_soc = ' . $main_alias . '.rowid';

                if (!empty($ids)) {
                    $sql .= '(' . $nbCommerciaux . ' AND sc.fk_user IN (' . implode(',', $ids) . ')) ';
                    $sql .= ($excluded ? '=' : '>') . ' 0';
                }

                if ($empty) {
                    $sql .= ($sql ? ($excluded ? ' AND ' : ' OR ') : '');
                    $sql .= '(' . $nbCommerciaux . ') ' . ($excluded ? '>' : '=') . ' 0';
                }

                if ($sql) {
                    $filters[$main_alias . '___commerciaux_custom'] = array(
                        'custom' => '(' . $sql . ')'
                    );
                }
                break;
        }

        parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $main_alias, $errors, $excluded);
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

    public function getCardFields($card_name)
    {
        $fields = parent::getCardFields($card_name);

        switch ($card_name) {
            case 'default':
                $fields[] = 'address';
                $fields[] = 'zip';
                $fields[] = 'town';
                $fields[] = 'fk_departement';
                $fields[] = 'fk_pays';

                $fields[] = 'phone';
                $fields[] = 'email';
                $fields[] = 'fax';
                $fields[] = 'skype';
                $fields[] = 'url';
                break;
        }

        return $fields;
    }

    // Getters données: 

    public function getRefProperty()
    {
        return 'code_client';
    }

    public function getRef($with_generic = true)
    {
        $return = '';
        if ($this->isClient()) {
            $return .= $this->getData('code_client');
        } elseif ($this->isFournisseur()) {
            $return .= $this->getData('code_fournisseur');
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

    public function getCountryCode()
    {
        $fk_pays = (int) $this->getData('fk_pays');
        if ($fk_pays) {
            return $this->db->getValue('c_country', 'code', '`rowid` = ' . (int) $fk_pays);
        }
    }

    public function getTotalPaiementsInconnus()
    {
        if ($this->isLoaded() && $this->isClient()) {
            return (float) $this->db->getSum('Bimp_PaiementInc', 'total', 'fk_soc = ' . (int) $this->id);
        }

        return 0;
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

                $sql .= ' AND (SELECT COUNT(cdet.rowid) FROM ' . MAIN_DB_PREFIX . 'commandedet cdet,  ' . MAIN_DB_PREFIX . 'commande comm ';
                $sql .= 'WHERE comm.rowid = cdet.fk_commande AND comm.fk_statut NOT IN (-1,3) AND cdet.fk_remise_except = r.rowid' . $and_where . ') = 0';

                $and_where = '';
                if (isset($allowed['propales']) && !empty($allowed['propales'])) {
                    $and_where = ' AND pdet.fk_propal NOT IN (' . implode(',', $allowed['propales']) . ')';
                }

                $sql .= ' AND (SELECT COUNT(pdet.rowid) FROM ' . MAIN_DB_PREFIX . 'propaldet pdet,  ' . MAIN_DB_PREFIX . 'propal prop ';
                $sql .= 'WHERE prop.rowid = pdet.fk_propal AND prop.fk_statut NOT IN (3,4) AND pdet.fk_remise_except = r.rowid' . $and_where . ') = 0';
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
                                    if (BimpObject::objectLoaded($commande) && !in_array($commande->getData('fk_statut'), array(-1, 3))) {
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
                                            if (!(int) $bdb->getValue('element_element', 'rowid', '`fk_source` = ' . $r['fk_propal'] . ' AND `sourcetype` = \'propal\'  AND `targettype` = \'commande\'') &&
                                                    !(int) $bdb->getValue('element_element', 'rowid', '`fk_source` = ' . $r['fk_propal'] . ' AND `sourcetype` = \'propal\'  AND `targettype` = \'contrat\'')) {
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

    public function getCommercials($with_default = true, $first = false)
    {
        $commerciaux = $this->getCommerciauxArray(false, $with_default);

        $users = array();
        foreach ($commerciaux as $id_comm => $comm_label) {
            $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $id_comm);
            if (BimpObject::objectLoaded($user)) {
                if ($first)
                    return array($user);
                else
                    $users[] = $user;
            }
        }
        if (count($users))
            return $users;

        if ($with_default) {
            $default_id_commercial = (int) BimpCore::getConf('default_id_commercial');
            if ($default_id_commercial) {
                $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $default_id_commercial);
                if (BimpObject::objectLoaded($user)) {
                    return array($user);
                }
            }
        }

        return array();
    }

    public function getCommercial($with_default = true)
    {
        $users = $this->getCommercials($with_default, true);
        if (count($users))
            return $users[0];
        return null;
    }

    public function getCommercialEmail($with_default = true, $only_first = true)
    {
        if ($only_first) {
            $comm = $this->getCommercial($with_default);

            if (BimpObject::objectLoaded($comm)) {
                return BimpTools::cleanEmailsStr($comm->getData('email'));
            }
        } else {
            $users = $this->getCommercials($with_default, $only_first);

            if (!empty($users)) {
                $email = '';
                foreach ($users as $user) {
                    if ((int) $user->getData('statut')) {
                        $email .= ($email ? ',' : '') . BimpTools::cleanEmailsStr($user->getData('email'));
                    }
                }

                return $email;
            }
        }

        return '';
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

    public function getBimpObjectsLinked($not_for = '')
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

    public function getDefautlModeReglement()
    {
        $code = '';
        if ((int) $this->getData('fk_typent')) {
            $code = $this->db->getValue('c_typent', 'code', 'id = ' . (int) $this->getData('fk_typent'));
        }
        if (BimpTools::getPostFieldValue('is_company') == '0')
            $code = 'TE_PRIVATE';
        if ($code != '') {
            if ($code == 'TE_ADMIN') {
                return (int) BimpCore::getConf('societe_id_default_mode_reglement_admin', BimpCore::getConf('societe_id_default_mode_reglement', 0));
            }
            if ($code === 'TE_PRIVATE') {
                return (int) BimpCore::getConf('particulier_id_default_mode_reglement', BimpCore::getConf('societe_id_default_mode_reglement', 0));
            }
        }

        return (int) BimpCore::getConf('societe_id_default_mode_reglement', 0);
    }

    public function getDefaultCondReglement()
    {
        $code = '';
        if ((int) $this->getData('fk_typent')) {
            $code = $this->db->getValue('c_typent', 'code', 'id = ' . (int) $this->getData('fk_typent'));
        }
        if (BimpTools::getPostFieldValue('is_company') == '0') {
            $code = 'TE_PRIVATE';
        }
        if ($code != '') {
            if (in_array($code, array('TE_ADMIN', 'TE_OTHER_ADM'))) {
                return (int) BimpCore::getConf('societe_id_default_cond_reglement_admin', BimpCore::getConf('societe_id_default_cond_reglement', 0));
            }
            if ($code === 'TE_PRIVATE') {
                return (int) BimpCore::getConf('particulier_id_default_cond_reglement', BimpCore::getConf('societe_id_default_cond_reglement', 0));
            }
        }

        return (int) BimpCore::getConf('societe_id_default_cond_reglement', 0);
    }

    public function getRemainToPay($true_value = false, $round = true, &$debug = '')
    {
        $amount = 0;
        $facts = BimpObject::getBimpObjectObjects('bimpcommercial', 'Bimp_Facture', array('fk_soc' => $this->id, 'paye' => 0, 'fk_statut' => array(0, 1)));
        foreach ($facts as $fact) {
            $value = $fact->getRemainToPay($true_value = false, $round = true);
            $debug .= '<br/>Fact : ' . $fact->getLink() . ' ' . BimpTools::displayMoneyValue($value);
            $amount += $value;
        }
        return $amount;
    }

    public function getEncours($withOtherSiret = true, &$debug = '')
    {
        $encours = 0;
        $first = false;
        if ($debug == '') {
            $debug = '<h3>Calcul de l\'encours client : ' . $this->getLink() . '</h3>';
            $first = true;
        }
        if ($withOtherSiret && $this->getData('siren') . 'x' != 'x' && strlen($this->getData('siren')) == 9) {
            $lists = BimpObject::getBimpObjectObjects($this->module, $this->object_name, array('siren' => $this->getData('siren')));
            $debug .= '<br/>Autre clients<br/>';
            foreach ($lists as $idO => $obj) {
                $encoursTmp = $obj->getEncours(false, $debug);
                $debug .= $obj->getLink() . ' - ' . $encoursTmp . '<br/>';
                $encours += $encoursTmp;
            }
        } else {
//            $values = $this->dol_object->getOutstandingBills();
//
//            if (isset($values['opened'])) {
//                $encours = $values['opened'];
//            }
            $encoursFact = $this->getRemainToPay(true, false, $debug);

            $encoursPAYNI = (float) $this->getTotalPaiementsInconnus();
            $encoursDicsount = (float) $this->getAvailableDiscountsAmounts();

            $encours = $encoursFact - $encoursPAYNI - $encoursDicsount;

            $debug .= '<br/>Encours du client sur les factures impayée :' . BimpTools::displayMoneyValue($encoursFact);
            $debug .= '<br/> - Paiment Inconue : ' . BimpTools::displayMoneyValue($encoursPAYNI);
            $debug .= '<br/> - Reduction dispo : ' . BimpTools::displayMoneyValue($encoursDicsount);
        }
        $debug .= '<br/> Total encours : ' . BimpTools::displayMoneyValue($encours);

        if ($first) {
            $this->debug[] = $debug;
            BimpDebug::addDebug('divers', 'Calcul de l\'encours', $debug, array('open' => 1));
        }
        return $encours;
    }

    public function getEncoursNonFacture($withOtherSiret = true, &$debug = '')
    {
        if (!$this->isLoaded()) {
            return 0;
        }

        $encours = 0;

        $ids = array($this->id);

        if ($withOtherSiret) {
            $siren = $this->getData('siren');

            if ($siren . 'x' != 'x' && strlen($siren) == 9) {
                $result = $this->db->getRows('societe', 'siren = \'' . $siren . '\' AND rowid != ' . $this->id, null, 'array', array('rowid'));

                if (is_array($result)) {
                    foreach ($result as $r) {
                        if (!in_array((int) $r['rowid'], $ids)) {
                            $ids[] = (int) $r['rowid'];
                        }
                    }
                }
            }
        }

        $sql = BimpTools::getSqlSelect(array('a.qty_modif', 'a.factures', 'det.qty', 'det.subprice', 'det.tva_tx', 'det.remise_percent', 'a.id_obj', 'a.position'));
        $sql .= BimpTools::getSqlFrom('bimp_commande_line', array(
                    'c'   => array(
                        'table' => 'commande',
                        'alias' => 'c',
                        'on'    => 'c.rowid = a.id_obj'
                    ),
                    'det' => array(
                        'table' => 'commandedet',
                        'alias' => 'det',
                        'on'    => 'det.rowid = a.id_line'
                    )
        ));

        $sql .= BimpTools::getSqlWhere(array(
                    'c.fk_statut'      => 1,
//                    'c.fk_soc'         => $ids,
//                    'c.id_client_facture'         => $ids,
                    'custom'           => array('custom' => '(c.id_client_facture IN (' . implode(',', $ids) . ') || (c.fk_soc IN (' . implode(',', $ids) . ') && c.id_client_facture = 0))'),
                    'c.invoice_status' => array(
                        'operator' => '!=',
                        'value'    => 2
                    )
        ));

        $rows = $this->db->executeS($sql, 'array');

        if (is_array($rows)) {
            $facs_status = array();

            foreach ($rows as $r) {
                $full_qty = $r['qty'] + $r['qty_modif'];

                $qty_billed = 0;

                if (!in_array((string) $r['factures'], array('', '[]'))) {
                    $factures = json_decode($r['factures'], 1);

                    if (is_array($factures)) {
                        foreach ($factures as $id_facture => $data) {
                            if (!isset($facs_status[$id_facture])) {
                                $facs_status[$id_facture] = (int) $this->db->getValue('facture', 'fk_statut', 'rowid = ' . $id_facture);
                            }

                            if (in_array($facs_status[$id_facture], array(1, 2)) || $id_facture == -1) {
                                $qty_billed += (float) $data['qty'];
                            }
                        }
                    }
                }

                if ($full_qty - $qty_billed) {
                    $pu = $r['subprice'];

                    if ($r['remise_percent']) {
                        $pu -= ($pu * ($r['remise_percent'] / 100));
                    }

                    $pu *= (1 + ($r['tva_tx'] / 100)); //PASSAGE EN TTC
                    $totLn = (($full_qty - $qty_billed) * $pu);
                    $encours += $totLn;

                    if ($totLn != 0)
                        $debug .= '<br/>Commande : <a href="' . DOL_URL_ROOT . '/commande/card.php?id=' . $r['id_obj'] . '" target="_blanck">' . $r['id_obj'] . '</a> ln : ' . $r['position'] . ' (' . ($full_qty - $qty_billed) . ' X ' . BimpTools::displayMoneyValue($pu) . ') = ' . BimpTools::displayMoneyValue(($full_qty - $qty_billed) * $pu);
                }
            }
        }

        return $encours;
    }

    public function getAllEncoursForSiret($with_commandes_non_facturees = false, $details = false, &$debug = '')
    {
        $encours = array(
            'factures'  => array(
                'socs'  => array(
                    $this->id => 0
                ),
                'total' => 0
            ),
            'commandes' => array(
                'socs'  => array(
                    $this->id => 0
                ),
                'total' => 0
            ),
            'total'     => 0
        );

        if ($this->isLoaded()) {
            $value = $this->getEncours(false);

            if ($value) {
                $encours['factures']['socs'][$this->id] += $value;
                $encours['factures']['total'] += $value;
                $encours['total'] += $value;
            }

            if ($with_commandes_non_facturees) {
                $value = $this->getEncoursNonFacture(false, $debug);

                if ($value) {
                    $encours['commandes']['socs'][$this->id] += $value;
                    $encours['commandes']['total'] += $value;
                    $encours['total'] += $value;
                }
            }

            $siren = $this->getData('siren');

            if ($siren . 'x' != 'x' && strlen($siren) == 9) {
                foreach (BimpCache::getBimpObjectObjects($this->module, $this->object_name, array(
                    'siren' => $siren,
                    'rowid' => array(
                        'operator' => '!=',
                        'value'    => $this->id
                    )
                )) as $id_soc => $soc) {
                    if (BimpObject::objectLoaded($soc)) {
                        if (!isset($encours['factures']['socs'][$id_soc])) {
                            $encours['factures']['socs'][$id_soc] = 0;
                        }

                        $value = $soc->getEncours(false);

                        if ($value) {
                            $encours['factures']['socs'][$id_soc] += $value;
                            $encours['factures']['total'] += $value;
                            $encours['total'] += $value;
                        }

                        if ($with_commandes_non_facturees) {
                            $value = $soc->getEncoursNonFacture(false);

                            if ($value) {
                                $encours['commandes']['socs'][$id_soc] += $value;
                                $encours['commandes']['total'] += $value;
                                $encours['total'] += $value;
                            }
                        }
                    }
                }
            }
        }

        return $encours;
    }

    public static function getCommercialCsvValue($needed_fields = array())
    {
        global $db;

        $list = static::getCommercialClients();

        if (isset($list[$needed_fields['rowid']]))
            return implode("\n", $list[$needed_fields['rowid']]);
        return '';
    }

    public static function getCodeClientNameCsvValue($needed_fields = array())
    {
        return $needed_fields['code_client'] . ' - ' . $needed_fields['nom'];
    }

    public static function getFull_addressCsvValue($needed_fields = array())
    {
        return static::concatAdresse($needed_fields['address'], $needed_fields['zip'], $needed_fields['town'], $needed_fields['fk_dep'], $needed_fields['fk_pays']);
    }

    public function displayCodeClientNom()
    {
        return $this->getData('code_client') . ' - ' . $this->getData('nom');
    }

    public function displayOutstandingLimitTtc()
    {
        return '<div style="float:left">' . $this->displayData('outstanding_limit') . ' </div><div>. HT soit : ' . BimpTools::displayMoneyValue($this->getData('outstanding_limit') * 1.2) . ' TTC</div>';
    }

    public static function getRegionCsvValue($needed_fields = array())
    {
        if (isset($needed_fields['fk_pays']) && (int) $needed_fields['fk_pays'] !== 1) {
            return 'Hors France';
        }

        if (isset($needed_fields['zip'])) {
            $dpt = substr($needed_fields['zip'], 0, 3);

            if ($dpt) {
                foreach (self::$regions as $region => $secteurs) {
                    foreach ($secteurs as $secteur => $codes) {
                        foreach ($codes as $code) {
                            if (stripos($dpt, $code) === 0) {
                                return 'Région ' . $region;
                            }
                        }
                    }
                }
            }
        }

        return 'nc';
    }

    public static function getSecteurCsvValue($needed_fields = array())
    {
        if (isset($needed_fields['fk_pays']) && (int) $needed_fields['fk_pays'] !== 1) {
            return 'Hors France';
        }

        if (isset($needed_fields['zip'])) {
            $dpt = substr($needed_fields['zip'], 0, 3);

            if ($dpt) {
                foreach (self::$regions as $region => $secteurs) {
                    foreach ($secteurs as $secteur => $codes) {
                        foreach ($codes as $code) {
                            if (stripos($dpt, $code) === 0) {
                                return 'R' . $region . $secteur;
                            }
                        }
                    }
                }
            }
        }

        return 'nc';
    }

    public function getFirstDateContrat()
    {

        if ($this->isLoaded()) {
            $sql = 'SELECT MIN(date_start) FROM llx_contrat_extrafields LEFT JOIN llx_contrat ON llx_contrat.rowid = llx_contrat_extrafields.fk_object WHERE llx_contrat.fk_soc = ' . $this->id;
            $res = $this->db->executeS($sql, 'array');
            return $res[0]['MIN(date_start)'];
        }

        return date('Y-m-d');
    }

    public function getContratsList()
    {
        return BimpCache::getBimpObjectObjects('bimpcontract', 'BContract_contrat', ['fk_soc' => $this->id], 'id', 'desc');
    }

    public function getSiret()
    {
        return substr($this->getData('siret'), 0, 14);
    }

    // Getters array: 

    public function getContactsArray($include_empty = true, $empty_label = '')
    {
        if ($this->isLoaded()) {
            return self::getSocieteContactsArray($this->id, $include_empty, $empty_label);
        }

        if ($include_empty) {
            return array(
                0 => $empty_label
            );
        }

        return array();
    }

    public function getAvailableDiscountsArray($is_fourn = false, $allowed = array())
    {
        $discounts = array();

        if ($this->isLoaded()) {
            global $conf;

            $sql = 'SELECT r.rowid as id, r.description, r.amount_ttc as amount, r.fk_facture';
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

                    $label = BimpTools::getRemiseExceptLabel($r['description']);
                    if ($r['fk_facture'] > 0) {
                        $fact = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $r['fk_facture']);
                        $label .= ' ' . $fact->getData('ref');
                    }
                    $label .= ' (' . BimpTools::displayMoneyValue((float) $r['amount'], '') . ' TTC)' . ($disabled_label ? ' - ' . $disabled_label : '');

                    $discounts[(int) $r['id']] = array(
                        'label'    => $label,
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

    public function getCommerciauxArray($include_empty = false, $with_default = true, $active_only = false)
    {
        if ($this->isLoaded()) {
            return self::getSocieteCommerciauxArray($this->id, $include_empty, $with_default, $active_only);
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
        $fk_fj = (int) $this->getData('fk_forme_juridique');
        if ($fk_fj) {
            $status = BimpCache::getJuridicalstatusArray((int) $this->getData('fk_pays'), 'rowid');
            if (isset($status[$fk_fj])) {
                return $status[$fk_fj];
            }
        }

        return '';
    }

    public static function staticDisplayCountry($id)
    {
        if ($id) {
            $countries = BimpCache::getCountriesArray();
            if (isset($countries[$id])) {
                return $countries[$id];
            }
        }
        return '';
    }

    public function displayCountry($id = 0)
    {
        if (!$id)
            $id = (int) $this->getData('fk_pays');
        return self::staticDisplayCountry($id);
    }

    public static function staticDisplayDepartement($fk_dep, $fk_pays)
    {
        if ((int) $fk_dep) {
            $deps = BimpCache::getStatesArray((int) $fk_pays);
            if (isset($deps[$fk_dep])) {
                return $deps[$fk_dep];
            }
        }
        return '';
    }

    public function displayDepartement($fk_dep = 0, $fk_pays = 0)
    {
        if (!$fk_dep)
            $fk_dep = (int) $this->getData('fk_departement');
        if (!$fk_pays)
            $fk_pays = (int) $this->getData('fk_pays');
        return static::staticDisplayDepartement($fk_dep, $fk_pays);
    }

    public static function concatAdresse($address, $zip, $town, $fk_dep = 0, $fk_pays = 0, $icon = false, $single_line = false)
    {
        return BimpTools::displayAddress($address, $zip, $town, static::staticDisplayDepartement($fk_dep, $fk_pays), static::staticDisplayCountry($fk_pays));
    }

    public function displayFullAddress($icon = false, $single_line = false)
    {
        return static::concatAdresse($this->getData('address'), $this->getData('zip'), $this->getData('town'), $this->getData('fk_departement'), $this->getData('fk_pays'), $icon, $single_line);
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
            $url = $this->getUrl() . '&navtab-maintabs=commercial&navtab-commercial_view=client_remises_except_list_tab';
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

        $users = $this->getCommerciauxArray(false, false);
        $default_id_commercial = (int) BimpCore::getConf('default_id_commercial');

        $edit = $this->canEditField('commerciaux');

        if(count($users)){
            foreach ($users as $id_user => $label) {
                if ((int) $id_user) {
                    $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $id_user);
                    if (BimpObject::objectLoaded($user)) {
                        $html .= ($html ? '<br/>' : '') . $user->getLink() . ' ';
                        if ($edit && $with_button) {
                            $onclick = $this->getJsActionOnclick('removeCommercial', array(
                                'id_commercial' => (int) $user->id
                                    ), array(
                                'confirm_msg' => htmlentities('Veuillez confirmer le retrait du commercial "' . $user->getName() . '"')
                            ));
                            $html .= BimpRender::renderRowButton('Retirer', 'fas_trash-alt', $onclick);
                        }
                    } else {
                        $html .= '<span class="danger">L\'utilisatuer #' . $id_user . ' n\'existe plus</span>';
                    }
                }
            }
        }
        else{
            $users = $this->getCommerciauxArray(false, true);
            foreach ($users as $id_user => $label) {
                    $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $id_user);
                    if (BimpObject::objectLoaded($user)) {
                        $html .= ($html ? '<br/>' : '') . $user->getLink() . ' ';
                        $html .= '&nbsp;<span class="small">(commercial par défaut)</span>';
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

    public function displayContratRefList()
    {

        $contrats = $this->getContratsList();
        $array = [];

        if (count($contrats) > 0) {

            foreach ($contrats as $contrat) {
                if ($contrat->getData('statut') != 0)
                    $ref = htmlentities($contrat->getRef() . ' - ' . $contrat->displayData('statut'));
                $ref .= ($contrat->getData('label') ? ' - ' . $contrat->getData('label') : '');

                $array[$contrat->id] = $ref;
            }
        }

        return $array;
    }

    public function displayCommercials($first = false, $link = true)
    {
        global $modeCSV;

        $return = array();
        $ids = $this->getIdCommercials();
        if (count($ids) > 0) {
            BimpTools::loadDolClass('contact');
            foreach ($ids as $id) {
                $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id);
                if (BimpObject::objectLoaded($user)) {
                    if ($modeCSV || !$link)
                        $return[] = $user->getName();
                    else
                        $return[] = $user->getLink();
                }
                if ($first)
                    break;
            }
        }

        if ($modeCSV)
            return implode("\n", $return);
        else
            return implode("<br/>", $return);
    }

    public function displayRegion()
    {
        if ((int) $this->getData('fk_pays') !== 1) {
            return 'Hors France';
        }

        $zip = $this->getData('zip');

        if ($zip) {
            $dpt = substr($zip, 0, 3);

            if ($dpt) {
                foreach (self::$regions as $region => $secteurs) {
                    foreach ($secteurs as $secteur => $codes) {
                        foreach ($codes as $code) {
                            if (stripos($dpt, $code) === 0) {
                                return 'Région ' . $region;
                            }
                        }
                    }
                }
            }
        }

        return 'nc';
    }

    public function displaySecteur()
    {
        if ((int) $this->getData('fk_pays') !== 1) {
            return 'Hors France';
        }

        $zip = $this->getData('zip');

        if ($zip) {
            $dpt = substr($zip, 0, 3);

            if ($dpt) {
                foreach (self::$regions as $region => $secteurs) {
                    foreach ($secteurs as $secteur => $codes) {
                        foreach ($codes as $code) {
                            if (stripos($dpt, $code) === 0) {
                                return 'R' . $region . $secteur;
                            }
                        }
                    }
                }
            }
        }

        return 'nc';
    }

    public function displayEncoursDetail()
    {
        $this->displayEncoursNonFacture();

        if (isset($this->debug)) {
            if (is_array($this->debug)) {
                return implode('<br/><br/>', $this->debug);
            } elseif ((string) $this->debug) {
                return $this->debug;
            }
        }

        return '';
    }

    public function displayEncoursNonFacture()
    {
        if (!$this->isLoaded()) {
            return '';
        }
        $debug = '';

        $encours = $this->getAllEncoursForSiret(true, false, $debug);

//        if($encours['factures']['total'] != 0){
        $html .= '<b>Encours sur factures restant dues</b> : ' . BimpTools::displayMoneyValue($encours['factures']['total']) . ' TTC<br/><br/>';
//        }
//        if($encours['commandes']['socs'][$this->id] != 0){
        $html .= '<b>Encours sur les commandes non facturées</b> : ' . BimpTools::displayMoneyValue($encours['commandes']['socs'][$this->id]) . ' TTC<br/>';
//        }
//        $html .= BimpTools::displayMoneyValue($encours['commandes']['socs'][$this->id]).' TTC';


        if (count($encours['commandes']['socs']) > 1) {
            $html .= '<br/>';
            foreach ($encours['commandes']['socs'] as $id_soc => $soc_encours) {
                if ($id_soc == (int) $this->id) {
                    continue;
                }

                $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $id_soc);
                $html .= '<br/>Client ';

                if (BimpObject::objectLoaded($soc)) {
                    $html .= $soc->getLink();
                } else {
                    $html .= '#' . $id_soc;
                }

                $html .= ' : ';

                $html .= BimpTools::displayMoneyValue($soc_encours);
            }

            $html .= '<br/><br/>';
            $html .= '<b>Total encours sur commandes non facturées pour l\'entreprise (Siren): </b>' . BimpTools::displayMoneyValue($encours['commandes']['total']);
        }

        if ($encours['commandes']['total'] && $encours['factures']['total']) {
            $html .= '<br/><b>Total encours</b> : ' . BimpTools::displayMoneyValue($encours['total']) . ' TTC';
        }

        $this->debug[] = $debug;
        BimpDebug::addDebug('divers', 'Calcul de l\'encours sur les commandes', $debug, array('open' => 1));

        return $html;
    }

    // Rendus HTML: 

    public function renderHeaderExtraLeft()
    {
        $html = '';

        if ($this->isLoaded()) {
            $isAnonymised = $this->isAnonymised();

            if (!$isAnonymised) {
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
            }

            $contrat = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat');
            $liste = $contrat->getList(array(
                'fk_soc' => $this->id,
                'or_version' => array('or' => array(
                    'or1' => array('and_fields' => array('version' => 1, 'statut' => 11)),
                    'or2' => array('and_fields' => array('version' => 2, 'statut' => 1))
                ))
            ));

            if (count($liste)) {
                $s = (count($liste) > 1) ? 's' : '';
                $html .= 'Contrat' . $s . ' actif' . $s . ': ';
                $fl = true;
                foreach ($liste as $infos) {
                    $contrat->fetch($infos['rowid']);
                    $card = new BC_Card($contrat);
                    if ($fl) {
                        $html .= '<span class=\'bs-popover\' ' . BimpRender::renderPopoverData($card->renderHtml(), 'top', true) . ' >' . $contrat->getNomUrl() . ' </span>';
                        $fl = false;
                    } else {
                        $html .= ', ' . '<span class=\'bs-popover\' ' . BimpRender::renderPopoverData($card->renderHtml(), 'top', true) . ' >' . $contrat->getNomUrl() . ' </span>';
                    }
                }
            }
            $contrat = null;

            if ($this->dol_object->date_creation) {
                $dt = new DateTime(BimpTools::getDateFromTimestamp($this->dol_object->date_creation));
                $date_regle_encoure = new DateTime("2021-05-01");
                $html .= '<div class="object_header_infos">';
                $html .= 'Créé le ';
                $class = ($dt->getTimestamp() >= $date_regle_encoure->getTimestamp()) ? " class='danger'" : "";
                $html .= "<strong" . $class . ">" . $dt->format('d / m / Y') . "</strong>";

                if ((int) $this->dol_object->user_creation) {
                    $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $this->dol_object->user_creation);
                    if (BimpObject::objectLoaded($user)) {
                        $html .= ' par ' . $user->getLink();
                    }
                }

                $html .= '</div>';
            }

            if ($this->dol_object->date_modification) {
                $dt = new DateTime(BimpTools::getDateFromTimestamp($this->dol_object->date_modification));

                $html .= '<div class="object_header_infos">';
                $html .= 'Dernière mise à jour le ' . $dt->format('d / m / Y');

                // User pas toujours juste...
//                 if ((int) $this->dol_object->user_modification) {
//                    $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $this->dol_object->user_modification);
//                    if (BimpObject::objectLoaded($user)) {
//                        $html .= ' par ' . $user->getLink();
//                    }
//                }

                $html .= '</div>';
            }

            if ($isAnonymised) {
                $log = BimpObjectLog::getLastObjectLogByCodes($this, array('ANONYMISED'));

                if (BimpObject::objectLoaded($log)) {
                    $html .= '<div class="object_header_infos">';
                    $html .= '<span class="danger">';
                    $html .= BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft');
                    $html .= ucfirst($this->getLabel()) . ' anonymisé le ' . date('d / m / Y', strtotime($log->getData('date')));
                    $html .= '</span>';
                    $html .= '</div>';
                }
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

    public function renderIsCompanyInput()
    {
        $html = '';

        $isCompany = (int) $this->isCompany();

        if (!$this->isLoaded() || $this->canSwitchIsCompany()) {
            $html .= BimpInput::renderInput('toggle', 'is_company', $isCompany);
        } else {
            if ($isCompany) {
                $html .= '<span class="success">OUI</span>';
            } else {
                $html .= '<span class="danger">NON</span>';
            }
            $html .= '<input type="hidden" name="is_company" value="' . $isCompany . '"/>';
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
        $tableauChiffresNumero = array();
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
            if ((int) BimpTools::getValue('no_logo', 0)) {
                if ($this->db->update($this->getTable(), array(
                            'logo' => ''
                                ), 'rowid = ' . (int) $this->id) <= 0) {
                    $errors[] = 'Echec de la suppression du logo - ' . $this->db->err();
                }
            } elseif (is_uploaded_file($_FILES['logo']['tmp_name'])) {
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
//                        'Livraison'           => '/livraison/class/livraison.class.php',
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

    public function majEncourscreditSafe($majOutstandingLimit = false, $maxOutstandingLimit = 100000, &$warnings = array())
    {
        $data = $errors = array();

        $code = (string) $this->getData('siret');
        $code_type = 'siret';

        if (!$code) {
            $code = (string) $this->getData('siren');
            $code_type = 'siren';
        }

        if ($code) {
            $errors = BimpTools::merge_array($errors, $this->checkSiren($code_type, $code, $data, $warnings));

            if (!count($errors)) {
                if (isset($data['lettrecreditsafe'])) {
                    $this->set('lettrecreditsafe', $data['lettrecreditsafe']);
                }
                if (isset($data['notecreditsafe'])) {
                    $this->set('notecreditsafe', $data['notecreditsafe']);
                }

                if ($majOutstandingLimit && isset($data['outstanding_limit'])) {
                    if ($data['outstanding_limit'] > $maxOutstandingLimit)
                        $data['outstanding_limit'] = $maxOutstandingLimit;
                    $this->set('outstanding_limit', $data['outstanding_limit']);
                }

                if (isset($data['capital'])) {
                    $this->set('capital', $data['capital']);
                }
                if (isset($data['tva_intra'])) {
                    $this->set('tva_intra', $data['tva_intra']);
                }
                if (isset($data['siret'])) {
                    $this->set('siret', $data['siret']);
                }
                if (isset($data['siren'])) {
                    $this->set('siren', $data['siren']);
                }

                $errors = $this->update($warnings, true);
            }
        }

        return $errors;
    }
    
    public function useCreditSafe(){
        return BimpTools::isModuleDoliActif('BIMPCREDITSAFE');
    }
    
    public function useEncours(){
        return BimpCore::getConf('useEncours');
    }
    public function useAtradius(){
        return ($this->useEncours() && BimpCore::getConf('useAtradius'));
    }

    public function checkSiren($field, $value, &$data = array(), &$warnings = array())
    {
        if ($value == "356000000")
            return array('Siren de la Poste, trop de résultats');

        $errors = array();

        $siret = '';
        $siren = '';

        $value = str_replace(' ', '', $value);
        if (strlen($value) == 9 && $field == 'siret')
            $field = 'siren';

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

//                if (method_exists($sClient, 'GetData')) { TODO remettre en place pour les dev qui n'ont pas php-soap
                $objReturn = $sClient->GetData(array("requestXmlStr" => str_replace("SIREN", ($siret ? $siret : $siren), $xml_data)));

                if (isset($objReturn->GetDataResult) && !empty($objReturn->GetDataResult)) {
                    $returnData = $objReturn->GetDataResult;
                    //                $returnData = htmlspecialchars_decode($returnData);
                    //
                    //                $returnData = BimpTools::replaceBr($returnData, '<br/>');
                    //                $returnData = str_replace("&", "et", $returnData);
                    //                $returnData = str_replace(" < ", " ", $returnData);
                    //                $returnData = str_replace(" > ", " ", $returnData);

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
                        $warnings[] = 'Erreur lors de la vérification du n° ' . ($siret ? 'SIRET' : 'SIREN') . ' (Code: ' . $result->body->errors->errordetail->code . ')';
                    } else {
                        $ville = '';
                        $codeP = '';
                        $tel = '';
                        $nom = '';
                        $note = $alert = "";
                        $limit = 0;

                        $summary = $result->body->company->summary;
                        $base = $result->body->company->baseinformation;
                        $branches = $base->branches->branch;
                        $adress = "" . $summary->postaladdress->address . " " . $summary->postaladresse->additiontoaddress;

                        $rcs = $summary->courtregistrydescription . ' ' . $siren;
                        if ($summary->status == 'Fermé') {
                            $note = 'Fermé';
                            $alert = 'Fermé';
                            $lettrecreditsafe = 0;
                        } else {
                            $lettrecreditsafe = 0;
                            foreach (array("", "2013") as $annee) {
                                $champ = "rating" . $annee;
                                if ($summary->$champ > 0) {
                                    $lettrecreditsafe = $summary->$champ;
                                    $note = dol_print_date(dol_now()) . ($annee == '' ? '' : '(Methode ' . $annee . ')') . " : " . $summary->$champ . "/100";
                                    foreach (array("", "desc1", "desc2", 'commentaries') as $champ2) {
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

                            if (is_array($branches)) {
                                foreach ($branches as $branche) {
                                    if (($siret && $branche->companynumber == $siret) || (!$siret && stripos($branche->type, "Siège") !== false)) {
//                                    die('gggggg');
                                        $adress = $branche->full_address->address;
                                        //$nom = $branche->full_address->name;
                                        $codeP = $branche->postcode;
                                        $ville = $branche->municipality;
                                        if (!$siret) {
                                            $siret = (string) $branche->companynumber;
                                        }
                                        break;
                                    }
                                }
                            }

                            if ($limit) {
                                $note .= ($note ? ' - ' : '') . 'Limite: ' . price(intval($limit)) . ' €';
                            }

                            //                        if ($limit < 1 && $lettrecreditsafe == 100)
                            //                            $limit = 10000000;
                        }
                        if (isset($result->body->company->ratings2013->commentaries->comment)) {
                            if (is_array($result->body->company->ratings2013->commentaries->comment))
                                foreach ($result->body->company->ratings2013->commentaries->comment as $comment)
                                    $note .= "
    " . $comment;
                            else
                                $note .= "
    " . $result->body->company->ratings2013->commentaries->comment;
                        }

                        $data = array(
                            'siren'             => $siren,
                            'siret'             => $siret,
                            "nom"               => "" . $nom,
                            "tva_intra"         => "" . $base->vatnumber,
                            "phone"             => "" . $tel,
                            "ape"               => "" . $summary->activitycode,
                            "alert"             => "" . $alert,
                            "notecreditsafe"    => "" . $note,
                            "lettrecreditsafe"  => "" . $lettrecreditsafe,
                            "address"           => "" . $adress,
                            "zip"               => "" . $codeP,
                            "town"              => "" . $ville,
                            "outstanding_limit" => "" . intval($limit),
                            "rcs"               => "" . $rcs,
                            "capital"           => "" . trim(str_replace(array(" Euros", '-'), "", $summary->sharecapital)));
                    }

                    if ($this->field_exists('date_check_credit_safe')) {
                        $this->updateField('date_check_credit_safe', date('Y-m-d H:i:s'));
                        return $errors;
                    }
                } else {
                    $warnings[] = 'Echec de la connexion à Credit Safe. Les données Credit Safe n\'ont pas pu être récupérées';
                }
            }
        }

        return $errors;
    }

    public function getCreditSafeLettre($noHtml = false)
    {
        global $modeCSV;
        $note = $this->getData('lettrecreditsafe');
        if ($note == '')
            return '';
        foreach (self::$tabLettreCreditSafe as $id => $tabLettre) {
            if ($note >= $id) {
                if ($noHtml || $modeCSV)
                    return $tabLettre[0];
                else
                    return BimpTools::getBadge($tabLettre[0], 25, $tabLettre[1], $note . '/100 ' . $tabLettre[2]);
//                    return '<span class="bs-popover" ' . BimpRender::renderPopoverData($note . '/100 ' . $tabLettre[2]) . '><img src="http://placehold.it/35/' . $tabLettre[1] . '/fff&amp;text=' . $tabLettre[0] . '" alt="User Avatar" class="img-circle"></span>';
            }
        }
    }

    public function traiteNoteCreditSafe()
    {
        
    }

    public function checkSolvabiliteStatus(&$warnings = array(), &$errors = array())
    {
        if (!$this->isLoaded()) {
            return;
        }

        if (!(int) BimpCore::getConf('check_solvabilite_client', 0)) {
            $warnings[] = 'Vérification de la solvabilité désactivée';
            return;
        }

        $cur_status = (int) $this->getData('solvabilite_status');

        if (in_array($cur_status, array(self::SOLV_INSOLVABLE, self::SOLV_DOUTEUX_FORCE, self::SOLV_A_SURVEILLER_FORCE))) {
            $warnings[] = 'Statut actuel non modifiable automatiquement';
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
            $warnings[] = 'Total impayés: ' . $total_unpaid;
            if ($total_contentieux > 0) {
                $warnings[] = 'Total contentieux: ' . $total_contentieux;
                $new_status = self::SOLV_DOUTEUX;
            } elseif ($cur_status !== self::SOLV_DOUTEUX) {
                if ($total_med > 0) {
                    $warnings[] = 'Total mises en demeure : ' . $total_med;
                    $new_status = self::SOLV_MIS_EN_DEMEURE;
                } elseif ($has_contentieux) {
                    $warnings[] = 'A surveiller car dispose d\'au moins un contentieux';
                    $new_status = self::SOLV_A_SURVEILLER;
                } else {
                    $warnings[] = 'Pas de contentieux ni de mises en demeure';
                    $new_status = self::SOLV_SOLVABLE;
                }
            } else {
                $warnings[] = 'Client déjà mis en douteux';
            }
        } else {
            if ($has_contentieux) {
                $warnings[] = 'Pas d\'impayé mais possède au moins un contentieux';
                $new_status = self::SOLV_A_SURVEILLER;
            } else {
                $warnings[] = 'Aucun contentieux ni impayé';
                $new_status = self::SOLV_SOLVABLE;
            }
        }

        if ($new_status !== $cur_status) {
            $warnings[] = 'Nouveau statut: ' . self::$solvabilites[$new_status]['label'];
            $err = $this->updateField('solvabilite_status', $new_status, null, true, true);

            if (!count($err)) {
                $this->onNewSolvabiliteStatus('auto');
            } else {
                $errors[] = BimpTools::getMsgFromArray($err, 'Echec mise à jour du statut solvabilité');
                BimpCore::addlog('Echec de l\'enregistrement du nouveau statut de solvabilité d\'un client', Bimp_Log::BIMP_LOG_URGENT, 'bimpcore', $this, array(
                    'Statut courant' => $cur_status . ' (' . self::$solvabilites[$cur_status]['label'] . ')',
                    'Nouveau statut' => $new_status . ' (' . self::$solvabilites[$new_status]['label'] . ')',
                    'Erreurs'        => $err
                ));
            }
        } else {
            $warnings[] = 'Pas de changement du statut';
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

            if (strlen($this->getData('siren')) == 9) {
                $this->db->db->query("UPDATE " . MAIN_DB_PREFIX . "societe SET outstanding_limit = '" . $this->getData('outstanding_limit') . "' WHERE siren = '" . $this->getData('siren') . "'");
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
            BimpObject::createBimpObject('bimpcore', 'Bimp_Client_Suivi_Recouvrement', array('id_societe' => $this->id, 'mode' => 4, 'sens' => 2, 'content' => 'Changement ' . ($mode == 'auto' ? 'auto' : 'manuel') . ' statut solvabilité : ' . self::$solvabilites[$status]['label']));

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

    public function anonymiseData($save_data = true, $reason = '', &$warnings = array(), &$rgpd_process = null)
    {
        $errors = array();

        if (!$this->isAnonymizable($errors)) {
            return $errors;
        }

        $data = array();
        $saved_data = array();

        foreach (self::$anonymization_fields as $field) {
            $saved_data[$field] = $this->getData($field);

            if ((string) $saved_data[$field]) {
                $data[$field] = '*****';
            } else {
                unset($saved_data[$field]);
            }
        }

        if ($save_data) {
            $id_cur_saved_data = (int) $this->db->getValue('societe_saved_data', 'id', 'type = \'societe\' AND id_object = ' . (int) $this->id);

            if ($id_cur_saved_data) {
                if ($this->db->update('societe_saved_data', array(
                            'date' => date('Y-m-d'),
                            'data' => base64_encode(json_encode($saved_data))
                                ), 'id = ' . $id_cur_saved_data) <= 0) {
                    $errors[] = 'Echec de l\'enregistrement des données de sauvegarde. Pas d\'anonymisation - Erreur SQL ' . $this->db->err();
                }
            } else {
                if ($this->db->insert('societe_saved_data', array(
                            'type'      => 'societe',
                            'id_object' => (int) $this->id,
                            'date'      => date('Y-m-d'),
                            'data'      => base64_encode(json_encode($saved_data))
                        )) <= 0) {
                    $errors[] = 'Echec de l\'enregistrement des données de sauvegarde. Pas d\'anonymisation - Erreur SQL ' . $this->db->err();
                }
            }
        }

        if (!count($errors)) {
            if (!empty($data)) {
                $data['is_anonymized'] = 1;

                // On fait un update direct en base pour contourner les validations de formats des données: 
                if ($this->db->update('societe', $data, 'rowid = ' . (int) $this->id) <= 0) {
                    $errors[] = 'Echec anonymisation des données - Erreur sql: ' . $this->db->err();
                }
            }


            if (!count($errors)) {
                $msg = 'Effacement des données personelles du client';
                if ($reason) {
                    $msg .= '<br/>(' . $reason . ')';
                }
                $this->addObjectLog($msg, 'ANONYMISED');

                // Anonymisation des contacts: 
                $contacts = $this->getContactsArray(false);
                if (!empty($contacts)) {
                    foreach ($contacts as $id_contact => $contact_label) {
                        $contact = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $id_contact);

                        if (BimpObject::objectLoaded($contact)) {
                            $contact_errors = $contact->anonymiseData($save_data);

                            if (count($contact_errors)) {
                                $warnings[] = BimpTools::getMsgFromArray($contact_errors, 'Erreurs anonymisation du contact "' . $contact_label . '"');
                            }
                        }
                    }
                }

                // Anonymisation des comptes utilisateurs: 
                $bic_users = BimpCache::getBimpObjectObjects('bimpinterfaceclient', 'BIC_UserClient', array(
                            'id_client' => $this->id
                ));

                foreach ($bic_users as $bic_user) {
                    if (BimpObject::objectLoaded($bic_user)) {
                        $user_errors = $bic_user->anonymiseData($save_data);

                        if (count($user_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($user_errors, 'Erreurs anonymisation du compte utilisateur #' . $bic_user->id);
                        }
                    }
                }

                // Traitement fichiers client: 
                $dir = $this->getFilesDir();
                if (preg_match('/^(.+)\/+$/', $dir, $matches)) {
                    $dir = $matches[1];
                }

                if (is_dir($dir)) {
                    if (count(scandir($dir)) > 2) {
                        // Renommage dossier: 
                        global $bimp_errors_handle_locked;
                        $bimp_errors_handle_locked = true;

                        error_clear_last();
                        if (!rename($dir, $dir . '_anonymized')) {
                            $err = error_get_last();
                            $warnings[] = 'Echec renommage du dossier "' . $dir . '" en "' . $dir . '_anonymized"' . (isset($err['message']) ? ' - ' . $err['message'] : '');
                        } else {
                            $warnings[] = 'Rename ' . $dir . ' => ' . $dir . '_anonymized';
                        }

                        $bimp_errors_handle_locked = false;
                    }
                }

                // Traitement fichiers des pièces liées:
                $files_errors = array();
                if (is_null($rgpd_process)) {
                    if (!class_exists('BDS_RgpdProcess')) {
                        require_once DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/process_overrides/BDS_RgpdProcess.php';
                    }
                    $rgpd_process = BDSProcess::createProcessByName('Rgpd', $files_errors);
                }

                if (!count($files_errors)) {
                    $files_errors = $rgpd_process->onClientAnonymised($this);
                }

                if (count($files_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($files_errors, 'Erreurs lors du traitement des fichiers client');
                }
            }
        }

        return $errors;
    }

    public function revertAnonymisedData($date, $origin, $reason = '', &$warnings = array())
    {
        $errors = array();

        $rows = $this->db->getRows('societe_saved_data', 'type = \'societe\' AND id_object = ' . $this->id, 1, 'array', null, 'date', 'desc');

        if (isset($rows[0]['data'])) {
            $values = base64_decode($rows[0]['data']);

            if ($values) {
                $values = json_decode($values, 1);
            }

            if (is_array($values) && !empty($values)) {
                if ($date) {
                    $this->set('date_last_activity', $date);
                }

                if ($origin) {
                    $this->set('last_activity_origin', $origin);
                }

                foreach (self::$anonymization_fields as $field) {
                    if (isset($values[$field])) {
                        $this->set($field, $values[$field]);
                    }
                }

                $this->set('is_anonymized', 0);

                $errors = $this->update($warnings, true);

                if (!count($errors)) {
                    $msg = 'Annulation de l\'anonymisation';

                    if ($reason) {
                        $msg .= '<br/>Motif: ' . $reason;
                    }
                    $this->addObjectLog($msg, 'UNANONYMISED');

                    if ((int) $rows[0]['id']) {
                        $this->db->delete('societe_saved_data', 'id = ' . (int) $rows[0]['id']);
                    }

                    // Annulation contacts: 
                    $contacts = $this->getContactsArray(false);
                    foreach ($contacts as $id_contact => $contact_label) {
                        $contact = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $id_contact);

                        if (BimpObject::objectLoaded($contact)) {
                            $contact_errors = $contact->revertAnonymisedData();

                            if (count($contact_errors)) {
                                $warnings[] = BimpTools::getMsgFromArray($contact_errors, 'Echec récupération des données du contact "' . $contact_label . '"');
                            }
                        }
                    }

                    // Annulation comptes utilisateurs: 
                    $bic_users = BimpCache::getBimpObjectObjects('bimpinterfaceclient', 'BIC_UserClient', array(
                                'id_client' => $this->id
                    ));

                    foreach ($bic_users as $bic_user) {
                        if (BimpObject::objectLoaded($bic_user)) {
                            $user_errors = $bic_user->revertAnonymisedData();

                            if (count($user_errors)) {
                                $warnings[] = BimpTools::getMsgFromArray($user_errors, 'Echec récupération des données du compte utilisateur #' . $bic_user->id);
                            }
                        }
                    }

                    // Traitement fichiers des pièces liées:
                    $files_errors = array();
                    if (!class_exists('BDS_RgpdProcess')) {
                        require_once DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/process_overrides/BDS_RgpdProcess.php';
                    }
                    $rgpd_process = BDSProcess::createProcessByName('Rgpd', $files_errors);

                    if (!count($files_errors)) {
                        $files_errors = $rgpd_process->onClientUnAnonymised($this);
                    }

                    if (count($files_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($files_errors, 'Erreurs lors du traitement des fichiers client');
                    }
                }
            } else {
                $errors[] = 'Echec du décodage des données sauvegardées';
            }
        } else {
            $errors[] = 'Aucune donnée sauvegardée trouvée pour ce client';
        }

        return $errors;
    }

    public function setActivity($origin = '', $date = null)
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            if (is_null($date) || !strtotime($date)) {
                $date = date('Y-m-d');
            }
            if (!$origin) {
                $origin = 'Non spécifiée';
            }

            $cur_date = (string) $this->getData('date_last_activity');

            if (!strtotime($cur_date) || $cur_date < $date) {
                $this->updateField('date_last_activity', $date);
                $this->updateField('last_activity_origin', $origin);
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

    public function actionDeleteInCompta($data, &$success)
    {
        $errors = Array();
        $warnings = Array();

        $errors = $this->updateField('code_compta', '');
        $errors = BimpTools::merge_array($errors, $this->updateField('code_compta_fournisseur', ''));
        $errors = BimpTools::merge_array($errors, $this->updateField('exported', 0));

        return Array(
            'errors'   => $errors,
            'warnings' => $wawrnings,
            'success'  => $success
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

        $this->checkSolvabiliteStatus($warnings, $errors);

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

                $subject = 'Demande ' . $op . ' ' . $label . ' ' . $this->getRef();
                ;
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

    public function actionReleveIntervention($data, &$success)
    {

        global $langs, $conf;

        $errors = Array();
        $warnings = Array();
        $success_callback = '';

        if ($data['stop'] < $data['start'])
            $errors[] = 'La date de fin ne peut pas être plus petite que la date de début';

        if (!count($errors)) {

            if ($data['by_date']) {
                $this->dol_object->date_start_relever = $data['start'];
                $this->dol_object->date_stop_relever = $data['stop'];
            }

            if ($data['by_tech'])
                if ($data['id_tech'] > 0)
                    $this->dol_object->id_tech = $data['id_tech'];
                else
                    $errors[] = 'L\'id du tech est obligatoire';
            if ($data['by_client'])
                if ($data['id_client'] > 0)
                    $this->dol_object->id_client = $data['id_client'];
                else
                    $errors[] = 'L\'id du client en obligatoire';
            if ($data['by_contrat'])
                if ($data['id_contrat'] > 0) {
                    $this->dol_object->id_contrat = $data['id_contrat'];
                    $cacheContrat = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat', $data['id_contrat']);
                    if ($this->id != $cacheContrat->getData('fk_soc'))
                        $errors[] = 'Ce contrat n\'appartient pas à ' . $this->getName();
                } else {
                    $errors[] = 'L\'id du contrat est obligatoire';
                }


            if (!count($errors)) {
                if ($this->dol_object->generateDocument('interStatement', $langs) <= 0) {
                    $warnings[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'des erreurs sont survenues lors de la génération du document PDF');
                } else {
                    $callback = "window.open('" . DOL_URL_ROOT . "/document.php?modulepart=company&file=" . $this->id . "/Releve_interventions.pdf&entity=1', '_blank');";
                    $success = 'PDF généré avec succès';
                }
            }
        }
        return Array('errors' => $errors, 'warnings' => $warnings, 'success_callback' => $callback);
    }

    public function actionReleveFacturation($data, &$success)
    {
        global $langs;
        $errors = array();
        $warnings = array();

        $debut = BimpTools::getArrayValueFromPath($data, 'date_debut', '');

        if (!$debut) {
            $sql = 'SELECT datef FROM ' . MAIN_DB_PREFIX . 'facture WHERE fk_soc = ' . $this->id;
            $sql .= ' ORDER BY datef ASC LIMIT 1';

            $result = $this->db->executeS($sql, 'array');

            if (isset($result[0]['datef'])) {
                $debut = $result[0]['datef'];
            } else {
                $debut = date('Y-m-d');
            }
        }

        $fin = BimpTools::getArrayValueFromPath($data, 'date_fin', date('Y-m-d'));

        $this->dol_object->borne_debut = $debut;
        $this->dol_object->borne_fin = $fin;

        $files = BimpCache::getBimpObjectObjects('bimpcore', 'BimpFile', array(
                    'parent_module'      => 'bimpcore',
                    'parent_object_name' => array(
                        'in' => array('Bimp_Societe', 'Bimp_Client')
                    ),
                    'id_parent'          => $this->id,
                    'file_name'          => 'Releve_facturation',
                    'deleted'            => 0
        ));

        if ($this->dol_object->generateDocument('invoiceStatement', $langs) > 0) {
            if (!empty($files)) {
                foreach ($files as $file) {
                    $file->updateField('date_create', date('Y-m-d h:i:s'));
                    $file->updateField('date_update', date('Y-m-d h:i:s'));
                }
            }
            $success = "Relevé de facturation généré avec succès";
        } else {
            $errors[] = "Echec de la génération du relevé de facturation";
        }
        $callback = "window.open('" . DOL_URL_ROOT . "/document.php?modulepart=company&file=" . $this->id . "%2FReleve_facturation.pdf&entity=1', '_blank');";

        return [
            'success_callback' => $callback,
            'errors'           => $errors,
            'warnings'         => $warnings
        ];
    }

    public function actionCheckLastActivity($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $clients = array();

        if ($this->isLoaded()) {
            $clients[] = $this->id;
        } else {
            $clients = BimpTools::getArrayValueFromPath($data, 'id_objects', array());
        }

        if (empty($clients)) {
            $errors[] = 'Aucun client sélectionné';
        } else {
            require_once DOL_DOCUMENT_ROOT . '/bimpdatasync/BDS_Lib.php';

            $process = BDSProcess::createProcessByName('Rgpd', $errors);

            if (!is_a($process, 'BDS_RgpdProcess') || !(int) $process->process->getData('active')) {
                $errors[] = 'Cette opération n\'est pas disponible';
            }

            if (!count($errors)) {
                $process->checkClientsActivity($clients, $errors, $warnings, $success);

                if (!count($errors) && !$success) {
                    if (!count($warnings)) {
                        if (count($clients) > 1) {
                            $success = 'Toutes les dates de dernières activités des clients sélectionnés sont à jour';
                        } else {
                            $success = 'La date de dernière activité du client est à jour';
                        }
                    } else {
                        $success = 'Aucune mise à jour n\'a été faite';
                    }
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionSetActivity($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $date = BimpTools::getArrayValueFromPath($data, 'date_activity', '');
        $origin = BimpTools::getArrayValueFromPath($data, 'activity_origin', '');

        if (!$date) {
            $errors[] = 'Veuillez sélectionner la date de dernière activité';
        } elseif ($date > date('Y-m-d')) {
            $errors[] = 'Vous ne pouvez pas saisir une date postérieure à aujourd\'hui';
        }

        if (!$origin) {
            $errors[] = 'Veullez indiquer l\'origine de la dernière activité du client';
        }

        if (!count($errors)) {
            $cur_date = (string) $this->getData('date_last_activity');

            if (!strtotime($cur_date) || $date > $cur_date) {
                $this->set('date_last_activity', $date);
                $this->set('last_activity_origin', $origin);

                $errors = $this->update($warnings, true);
            } else {
                $errors[] = 'La date indiquée est antérieure à la date de dernière activité actuellement enregistrée';
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionAnonymize($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Anonymisation effectuée avec succès';

        $reason = BimpTools::getArrayValueFromPath($data, 'reason', '');
        $errors = $this->anonymiseData(true, $reason, $warnings);

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionRevertAnonymization($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Récupération des données sauvegardées effectuée avec succès';

        $date = BimpTools::getArrayValueFromPath($data, 'date_activity', '');
        $origin = BimpTools::getArrayValueFromPath($data, 'activity_origin', '');
        $reason = BimpTools::getArrayValueFromPath($data, 'reason', '');

        if (!$date) {
            $errors[] = 'Veuillez sélectionner la nouvelle date de dernière activité';
        }

        if (!$origin) {
            $errors[] = 'Veuillez saisir l\'origine de la dernière activité';
        }

        if (!count($errors)) {
            $errors = $this->revertAnonymisedData($date, $origin, $reason, $warnings);
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionListClientsToExcludeForCreditLimits($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $scb = '';

        $date = BimpTools::getArrayValueFromPath($data, 'date_max');

        if (is_null($date) || !strtotime($date)) {
            $errors[] = 'Veuillez sélectionner une date max';
        } else {
            $dt = new DateTime($date);
//            $dt->sub(new DateInterval('P1D'));
            $date = $dt->format('Y-m-d');

            if (!$date) {
                $errors[] = 'Date max invalide';
            } else {
                $html = '';

                foreach (array(
            'outstanding_limit_credit_safe',
            'outstanding_limit_icba',
            'outstanding_limit_credit_check',
            'outstanding_limit_atradius'
                ) as $field) {
                    $sql = "SELECT DISTINCT id_object as id FROM " . MAIN_DB_PREFIX . "bimpcore_history a WHERE a.object IN ('Bimp_Client', 'Bimp_Societe')";
                    $sql .= " AND a.field = '" . $field . "' AND a.date > '" . $date . " 23:59:59' AND a.id_user != 0 AND a.value != '-1'";
                    $sql .= " AND (SELECT COUNT(DISTINCT id) FROM " . MAIN_DB_PREFIX . "bimpcore_history b WHERE b.object IN ('Bimp_Client', 'Bimp_Societe')";
                    $sql .= " AND b.field = '" . $field . "' AND a.id_object = b.id_object AND (b.id_user = 0 OR b.date <= '" . $date . " 23:59:59') AND b.value != '-1') = 0";

                    $rows = $this->db->executeS($sql, 'array');
                    $label = $this->getConf('fields/' . $field . '/label', $field);

                    $html .= ($html ? '<br/><br/>' : '') . '<h3>' . $label . '</h3>';

//                    $html .= $sql;
//                    $html .= '<br/><br/>';

                    if (is_array($rows) && !empty($rows)) {
                        $html .= '<b>' . count($rows) . ' client(s) à exclure</b><br/><br/>';
                        $fl = true;
                        foreach ($rows as $r) {
                            if (!$fl) {
                                $html .= ';';
                            } else {
                                $fl = false;
                            }

                            $html .= $r['id'];
                        }
                    } else {
                        $html .= '<b>Aucun client à exclure trouvé</b>';
                    }
                }

                $title = 'Liste des ID clients à exclure';

                $scb = 'setTimeout(function() {bimpModal.newContent(\'' . $title . '\', \'' . str_replace("'", "\'", $html) . '\', false, \'\', $());}, 500);';
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $scb
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

        if ($_REQUEST['outstanding_limit_credit_safe'] != $this->getData('outstanding_limit_credit_safe'))
            $this->updateField('outstanding_limit_credit_safe', $_REQUEST['outstanding_limit_credit_safe']);

        if (!count($errors)) {
            if (BimpTools::isSubmit('is_company')) {
                if ((int) BimpTools::getValue('is_company')) {
                    if (!(int) $this->getData('fk_typent')) {
                        $errors[] = 'Veuillez sélectionner le type de tiers';
                    } elseif ((int) $this->getData('fk_typent') == 8) {
                        $errors[] = 'Il n\'est pas possible de sélectionner le type "particulier" pour les entreprises';
                    }

                    $siret = $this->getData('siret');
                    $siren = $this->getData('siren');

                    if ($siret) {
                        if ((int) $this->getData('fk_typent') == 5 && !preg_match('/^[12].+$/', $siret)) {
                            $errors[] = 'Le SIRET doit commencer par 1 ou 2 pour les clients de type "Administration"';
                        }
                    } elseif ($siren) {
                        if ((int) $this->getData('fk_typent') == 5 && !preg_match('/^[12].+$/', $siren)) {
                            $errors[] = 'Le SIREN doit commencer par 1 ou 2 pour les clients de type "Administration"';
                        }
                    }

                    if ($this->isSirenRequired()) {
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
//                            if ($siret !== $this->getInitData('siret')) {
//                                if (!(int) BimpTools::getValue('siren_ok', 0)) {
//                                if(!$this->isSirenOk()){
//                                    $errors[] = 'Veuillez saisir un n° SIRET valide : '.$this->getData('siret');
//                                }
//                            }
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
                    $errors[] = 'Numéro SIRET absent pour le client d\'id ' . $this->id;
                } elseif (!$this->Luhn($siret, 14)) {
                    $errors[] = 'Numéro SIRET invalide';
                } else {
                    $this->set('siren', substr($siret, 0, 9));
                }
            }

            $note = $this->getData('note_private');
            if ($note) {
                $note = BimpTools::cleanStringMultipleNewLines($note);
                $this->set('note_private', $note);
            }

            $note = $this->getData('note_public');
            if ($note) {
                $note = BimpTools::cleanStringMultipleNewLines($note);
                $this->set('note_public', $note);
            }

            $have_already_code_comptable = (BimpTools::getValue('has_already_code_comptable_client') == 1) ? true : false;
            if ($have_already_code_comptable && empty(BimpTools::getValue('code_compta'))) {
                $errors[] = "Vous devez rensseigner un code comptable client";
            }

            if (!count($errors) && $have_already_code_comptable) {
                $this->set('exported', 1);
            }

            if ($this->getData('type_educ') == 'E4') {
                if (!$this->getData('type_educ_fin_validite')) {
                    $errors[] = 'Veuillez saisir la date de fin de validité du statut "Enseignant / étudiant"';
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
        $init_relance_actives = (int) $this->getInitData('relances_actives');

        $limit = 0;

        if ($this->getData('outstanding_limit_atradius') > -1)
            $limit = $this->getData('outstanding_limit_atradius');
        if ($this->getData('outstanding_limit_icba') > $limit)
            $limit = $this->getData('outstanding_limit_icba');
        if ($this->getData('outstanding_limit_credit_check') > $limit)
            $limit = $this->getData('outstanding_limit_credit_check');
        if ($this->getData('outstanding_limit_manuel') > $limit)
            $limit = $this->getData('outstanding_limit_manuel');
        if ($limit > -1 && $limit != $this->getInitData('outstanding_limit'))
            $this->set('outstanding_limit', $limit);

//        if ($this->getInitData('fk_typent') != $this->getData('fk_typent') && !$this->canEditField('status')) {
////            if (stripos($this->getData('code_compta'), 'P') === 0 && $this->getData('fk_typent') != 8)
////                return array("Code compta particulier, le type de tiers ne peut être différent.");
////            if (stripos($this->getData('code_compta'), 'E') === 0 && $this->getData('fk_typent') == 8)
////                return array("Code compta entreprise, le type de tiers ne peut être différent.");
//        }

        if ($init_solv != $this->getData('solvabilite_status') && (int) $this->getData('solvabilite_status') === self::SOLV_A_SURVEILLER_FORCE) {
            global $user;
            if (!$user->admin && $user->id != 1499) {
                return array('Vous n\'avez pas la permission de passer le statut solvabilité à "Client à surveiller (forcé)"');
            }
        }

//        if ($this->getData('fk_typent') == 5) { // Géré via bimpcore_conf (via getDefaultCondReglement)
//            $this->set('mode_reglement', 63);
//            $this->set('cond_reglement', 7);
//        }

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

            if ($init_relance_actives && !(int) $this->getData('relances_actives')) {
                $this->updateField('date_relances_deactivated', date('Y-m-d'));
            } elseif (!$init_relance_actives = (int) $this->getData('relances_actives')) {
                $this->updateField('date_relances_deactivated', null);
            }
        }

        $fc = BimpTools::getValue('fc');

        if (in_array($fc, array('client', 'fournisseur')) && ($init_client != $this->getData('client') || $init_fourn != $this->getData('fournisseur'))) {
            $this->reloadPage = true;
        }

        return $errors;
    }

    public function delete(&$warnings = [], $force_delete = false)
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            $count_errors = array();

            // Vérifs clients: 
            $nb = $this->db->getCount('propal', 'fk_soc = ' . (int) $this->id, 'rowid');
            if ($nb) {
                $count_errors[] = $nb . ' proposition(s) commerciale(s) créée(s)';
            }

            $nb = $this->db->getCount('commande', 'fk_soc = ' . (int) $this->id . ' OR id_client_facture = ' . $this->id, 'rowid');
            if ($nb) {
                $count_errors[] = $nb . ' commande(s) créée(s)';
            }

            $nb = $this->db->getCount('facture', 'fk_soc = ' . (int) $this->id, 'rowid');
            if ($nb) {
                $count_errors[] = $nb . ' facture(s) créée(s)';
            }

            $nb = $this->db->getCount('contrat', 'fk_soc = ' . (int) $this->id, 'rowid');
            if ($nb) {
                $count_errors[] = $nb . ' contrat(s) créé(s)';
            }

            $nb = $this->db->getCount('bs_sav', 'id_client = ' . (int) $this->id, 'id');
            if ($nb) {
                $count_errors[] = $nb . ' sav(s) créé(s)';
            }

            $nb = $this->db->getCount('bs_ticket', 'id_client = ' . (int) $this->id, 'id');
            if ($nb) {
                $count_errors[] = $nb . ' ticket(s) hotline créé(s)';
            }

            $nb = $this->db->getCount('fichinter', 'fk_soc = ' . (int) $this->id, 'rowid');
            if ($nb) {
                $count_errors[] = $nb . ' fiche(s) d\'intervention créée(s)';
            }

            // Vérifs Fournisseurs:
            $nb = $this->db->getCount('commande_fournisseur', 'fk_soc = ' . (int) $this->id, 'rowid');
            if ($nb) {
                $count_errors[] = $nb . ' commande(s) fournisseur(s) créée(s)';
            }

            $nb = $this->db->getCount('facture_fourn', 'fk_soc = ' . (int) $this->id, 'rowid');
            if ($nb) {
                $count_errors[] = $nb . ' facture(s) fournisseur(s) créée(s)';
            }

            if (count($count_errors)) {
                $errors[] = BimpTools::getMsgFromArray($count_errors, 'Impossible de supprimer ce tiers');
            } else {
                $errors = parent::delete($warnings, $force_delete);
            }
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
