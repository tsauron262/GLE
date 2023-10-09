<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimptechnique/objects/BT_ficheInter.class.php';

if (!defined('BV_LIB')) {
    require_once DOL_DOCUMENT_ROOT . '/bimpvalidation/BV_Lib.php';
}

/*
 * *** Mémo ajout signature pour un objet: ***

 * - Gérer l'enregistrement des paramètres de position de la signature sur le PDF au moment de sa génération (Si besoin) / ou régler par défaut pour les PDF fixes
 * - Intégrer selon le context: marqueur signé (champ booléen ou statut) / indicateur signature dans l'en-tête / etc. 
 * - Gérer Annulation signature si besoin
 * - Gérer Duplication / Révision / Etc. 
 * - Gérer la visualisation du docuement sur l'interface publique (bimpinterfaceclient > docController) 
 * - Gérer le droit canClientView() pour la visualisation du document sur l'espace public. 
 */

/*
 * req maj expertise : 
 * SELECT f.* FROM llx_contrat_extrafields c, llx_element_element e, llx_facture_extrafields f WHERE e.sourcetype = 'contrat' AND e.fk_source = c.fk_object AND e.targettype = 'facture' AND e.fk_target = f.fk_object AND c.expertise != '' AND f.expertise = '';
 */

class BContract_contrat extends BimpDolObject
{

    //public $redirectMode = 4;
    public static $email_type = 'contract';
    public $email_group = "";
    public $email_facturation = "";
    public static $element_name = "contrat";
    public static $dol_module = 'contrat';
    public static $files_module_part = 'contract';
    public static $modulepart = 'contract';

    // Types: 
    CONST CONTRAT_GLOBAL = "CT";
    CONST CONTRAT_DE_MAINTENANCE = 'CMA';
    CONST CONTRAT_SUPPORT_TELEPHONIQUE = 'CST';
    CONST CONTRAT_MONITORING = 'CMO';
    CONST CONTRAT_DE_SPARE = 'CSP';
    CONST CONTRAT_DE_DELEGATION_DE_PERSONEL = 'CDP';
    CONST CONTRAT_MONETIQUE = 'CMQ';
    CONST CONTRAT_ASMX = 'ASMX';

    public static $objet_contrat = [
        self::CONTRAT_GLOBAL                    => ['label' => "Contrat global", 'classes' => [], 'icon' => 'globe'],
        self::CONTRAT_DE_MAINTENANCE            => ['label' => "Contrat de maintenance", 'classes' => [], 'icon' => 'cogs'],
        self::CONTRAT_SUPPORT_TELEPHONIQUE      => ['label' => "Contrat de support téléphonique", 'classes' => [], 'icon' => 'phone'],
        self::CONTRAT_MONITORING                => ['label' => "Contrat de monitoring", 'classes' => [], 'icon' => 'terminal'],
        self::CONTRAT_DE_SPARE                  => ['label' => "Contrat de spare", 'classes' => [], 'icon' => 'share'],
        self::CONTRAT_DE_DELEGATION_DE_PERSONEL => ['label' => "Contrat de délégation du personnel", 'classes' => [], 'icon' => 'male'],
        self::CONTRAT_MONETIQUE                 => ['label' => "Contrat monétique", 'classes' => [], 'icon' => 'fas_file-invoice-dollar'],
        self::CONTRAT_ASMX                      => ['label' => "Contrat ASMX", 'classes' => [], 'icon' => 'fas fa5-external-link-alt'],
    ];

    // Statuts: 
    CONST CONTRAT_STATUT_ABORT = -1;
    CONST CONTRAT_STATUS_BROUILLON = 0;
    CONST CONTRAT_STATUS_VALIDE = 1;
    CONST CONTRAT_STATUS_CLOS = 2;
    CONST CONTRAT_STATUT_WAIT_ACTIVER = 3;
    CONST CONTRAT_STATUS_REFUSE = 4;
    CONST CONTRAT_STATUS_WAIT = 10;
    CONST CONTRAT_STATUS_ACTIVER = 11;
    CONST CONTRAT_STATUS_ACTIVER_TMP = 12;
    CONST CONTRAT_STATUS_ACTIVER_SUP = 13;

    public static $status_list = Array(
        self::CONTRAT_STATUT_ABORT        => Array('label' => 'Abandonné', 'classes' => Array('danger'), 'icon' => 'fas_times'),
        self::CONTRAT_STATUS_BROUILLON    => Array('label' => 'Brouillon', 'classes' => Array('warning'), 'icon' => 'fas_trash-alt'),
        self::CONTRAT_STATUS_VALIDE       => Array('label' => 'Attente signatures', 'classes' => Array('success'), 'icon' => 'fas_retweet'),
        self::CONTRAT_STATUS_CLOS         => Array('label' => 'Clos', 'classes' => Array('danger'), 'icon' => 'fas_times'),
        self::CONTRAT_STATUS_REFUSE       => Array('label' => 'Refusé', 'classes' => Array('danger'), 'icon' => 'fas_times'),
        self::CONTRAT_STATUT_WAIT_ACTIVER => Array('label' => 'Attente d\'activation', 'classes' => Array('important'), 'icon' => 'fas_retweet'),
        self::CONTRAT_STATUS_WAIT         => Array('label' => 'En attente de validation', 'classes' => Array('warning'), 'icon' => 'fas_refresh'),
        self::CONTRAT_STATUS_ACTIVER      => Array('label' => 'Actif', 'classes' => Array('important'), 'icon' => 'fas_play'),
        self::CONTRAT_STATUS_ACTIVER_TMP  => Array('label' => 'Activation provisoire', 'classes' => Array('important'), 'icon' => 'fas_history'),
        self::CONTRAT_STATUS_ACTIVER_SUP  => Array('label' => 'Activation suspendue pour cause de non signature', 'classes' => Array('danger'), 'icon' => 'fas_stop')
    );

    // Périodicitées: 

    CONST CONTRAT_PERIOD_AUCUNE = 0;
    CONST CONTRAT_PERIOD_MENSUELLE = 1;
    CONST CONTRAT_PERIOD_BIMENSUELLE = 2;
    CONST CONTRAT_PERIOD_TRIMESTRIELLE = 3;
    CONST CONTRAT_PERIOD_SEMESTRIELLE = 6;
    CONST CONTRAT_PERIOD_ANNUELLE = 12;
    CONST CONTRAT_PERIOD_TOTAL = 1200;

    public static $period = Array(
        self::CONTRAT_PERIOD_MENSUELLE     => 'Mensuelle',
        self::CONTRAT_PERIOD_BIMENSUELLE   => 'Bimestrielle',
        self::CONTRAT_PERIOD_TRIMESTRIELLE => 'Trimestrielle',
        self::CONTRAT_PERIOD_SEMESTRIELLE  => 'Semestrielle',
        self::CONTRAT_PERIOD_ANNUELLE      => 'Annuelle',
        self::CONTRAT_PERIOD_TOTAL         => 'Une fois',
        self::CONTRAT_PERIOD_AUCUNE        => 'Aucune',
    );

    // Les délais d'intervention
    CONST CONTRAT_DELAIS_0_HEURES = 0;
    CONST CONTRAT_DELAIS_4_HEURES = 4;
    CONST CONTRAT_DELAIS_8_HEURES = 8;
    CONST CONTRAT_DELAIS_16_HEURES = 16;

    public static $gti = Array(
        self::CONTRAT_DELAIS_0_HEURES  => '',
        self::CONTRAT_DELAIS_4_HEURES  => '4 heures ouvrées',
        self::CONTRAT_DELAIS_8_HEURES  => '8 heures ouvrées',
        self::CONTRAT_DELAIS_16_HEURES => '16 heures ouvrées'
    );

    // Les renouvellements
    CONST CONTRAT_RENOUVELLEMENT_NON = 0; // 100
    CONST CONTRAT_RENOUVELLEMENT_1_FOIS = 1; // 101
    CONST CONTRAT_RENOUVELLEMENT_2_FOIS = 2; // 102
    CONST CONTRAT_RENOUVELLEMENT_3_FOIS = 3; // 103
    CONST CONTRAT_RENOUVELLEMENT_4_FOIS = 4; // 104
    CONST CONTRAT_RENOUVELLEMENT_5_FOIS = 5; // 105
    CONST CONTRAT_RENOUVELLEMENT_6_FOIS = 6; // 106
    CONST CONTRAT_RENOUVELLEMENT_SUR_PROPOSITION = 12; // 112
    CONST CONTRAT_RENOUVELLEMENT_AD_VITAM_ETERNAM = 666;

    public static $renouvellement = Array(
        self::CONTRAT_RENOUVELLEMENT_1_FOIS           => 'Tacite 1 fois',
        self::CONTRAT_RENOUVELLEMENT_2_FOIS           => 'Tacite 2 fois',
        self::CONTRAT_RENOUVELLEMENT_3_FOIS           => 'Tacite 3 fois',
        self::CONTRAT_RENOUVELLEMENT_4_FOIS           => 'Tacite 4 fois',
        self::CONTRAT_RENOUVELLEMENT_5_FOIS           => 'Tacite 5 fois',
        self::CONTRAT_RENOUVELLEMENT_6_FOIS           => 'Tacite 6 fois',
        self::CONTRAT_RENOUVELLEMENT_AD_VITAM_ETERNAM => 'Durée indéterminée',
        self::CONTRAT_RENOUVELLEMENT_SUR_PROPOSITION  => 'Sur proposition',
        self::CONTRAT_RENOUVELLEMENT_NON              => 'Non',
    );
    public static $renouvellement_create = Array(
        self::CONTRAT_RENOUVELLEMENT_NON              => "Choix du renouvellement",
        self::CONTRAT_RENOUVELLEMENT_1_FOIS           => 'Tacite 1 fois',
        self::CONTRAT_RENOUVELLEMENT_2_FOIS           => 'Tacite 2 fois',
        self::CONTRAT_RENOUVELLEMENT_3_FOIS           => 'Tacite 3 fois',
        self::CONTRAT_RENOUVELLEMENT_4_FOIS           => 'Tacite 4 fois',
        self::CONTRAT_RENOUVELLEMENT_5_FOIS           => 'Tacite 5 fois',
        self::CONTRAT_RENOUVELLEMENT_6_FOIS           => 'Tacite 6 fois',
        self::CONTRAT_RENOUVELLEMENT_AD_VITAM_ETERNAM => 'Durée indéterminée',
        self::CONTRAT_RENOUVELLEMENT_SUR_PROPOSITION  => 'Sur proposition'
    );
    public static $renouvellement_edit = Array(
        self::CONTRAT_RENOUVELLEMENT_NON              => 'Aucun',
        self::CONTRAT_RENOUVELLEMENT_1_FOIS           => 'Tacite 1 fois',
        self::CONTRAT_RENOUVELLEMENT_2_FOIS           => 'Tacite 2 fois',
        self::CONTRAT_RENOUVELLEMENT_3_FOIS           => 'Tacite 3 fois',
        self::CONTRAT_RENOUVELLEMENT_4_FOIS           => 'Tacite 4 fois',
        self::CONTRAT_RENOUVELLEMENT_5_FOIS           => 'Tacite 5 fois',
        self::CONTRAT_RENOUVELLEMENT_6_FOIS           => 'Tacite 6 fois',
        self::CONTRAT_RENOUVELLEMENT_AD_VITAM_ETERNAM => 'Durée indéterminée',
        self::CONTRAT_RENOUVELLEMENT_SUR_PROPOSITION  => 'Sur proposition'
    );

    // Contrat dénoncé
    CONST CONTRAT_DENOUNCE_NON = 0;
    CONST CONTRAT_DENOUNCE_OUI_DANS_LES_TEMPS = 1;
    CONST CONTRAT_DENOUNCE_OUI_HORS_DELAIS = 2;

    public static $denounce = Array(
        self::CONTRAT_DENOUNCE_NON                => Array('label' => 'Non', 'classes' => Array('success'), 'icon' => 'fas_check'),
        self::CONTRAT_DENOUNCE_OUI_DANS_LES_TEMPS => Array('label' => 'OUI, DANS LES TEMPS', 'classes' => Array('success'), 'icon' => 'fas_check'),
        self::CONTRAT_DENOUNCE_OUI_HORS_DELAIS    => Array('label' => 'OUI, HORS DELAIS', 'classes' => Array('danger'), 'icon' => 'fas_times'),
    );

    // Type mail interne
    CONST MAIL_DEMANDE_VALIDATION = 1;
    CONST MAIL_VALIDATION = 2;
    CONST MAIL_ACTIVATION = 3;
    CONST MAIL_SIGNED = 4;
    CONST MAIL_TEMPORAIRE = 5;
    CONST PRORATA_PERIODE = false;

    public static $true_objects_for_link = [
        'commande'      => 'Commande',
        'facture_fourn' => 'Facture fournisseur',
            //'propal' => 'Proposition commercial'
    ];
    private $totalContrat = null;

    function __construct($module, $object_name)
    {
        $this->redirectMode = 4;
        $this->email_group = BimpCore::getConf('email_groupe', '', 'bimpcontract');
        $this->email_facturation = BimpCore::getConf('email_facturation', '', 'bimpcontract');

        return parent::__construct($module, $object_name);
    }

    // Droirs users: 

    public function canSetAction($action)
    {

        global $user;

        return parent::canSetAction($action);
    }

    public function canEditField($field_name)
    {
        global $user;

        if (in_array($field_name, array('expertise', 'rib_client'))) {
            return 1;
        }
        if ($this->getData('statut') == self::CONTRAT_STATUS_REFUSE)
            return 0;

        if ($this->getData('statut') == self::CONTRAT_STATUS_CLOS)
            return 0;

        if ($this->getData('statut') == self::CONTRAT_STATUS_WAIT && $user->rights->bimpcontract->to_validate)
            return 1;

        if ($this->getData('statut') == self::CONTRAT_STATUS_BROUILLON)
            return 1;

        switch ($field_name) {
            case 'current_renouvellement':
            case 'tacite':
            case 'date_end_renouvellement':
                if ($user->admin)
                    return 1;
                break;
            case 'show_fact_line_in_pdf':
                if ($user->rights->bimpcontract->to_validate && ($this->getData("statut") != self::CONTRAT_STATUS_ACTIVER && $this->getData('statut') != self::CONTRAT_STATUS_ACTIVER_TMP && $this->getData('statut') != self::CONTRAT_STATUS_ACTIVER_SUP))
                    return 1;
                return 0;
                break;
            case 'periodicity':
                $linked_factures = getElementElement('contrat', 'facture', $this->id);
                if ($user->rights->bimpcontract->change_periodicity && !count($linked_factures))
                    return 1;
                else
                    return 0;
                break;
            case 'date_start':
                $linked_factures = getElementElement('contrat', 'facture', $this->id);
                if ($user->rights->bimpcontract->change_periodicity && !count($linked_factures))
                    return 1;
                else
                    return 0;
                break;
            case 'duree_mois':
                $linked_factures = getElementElement('contrat', 'facture', $this->id);
                if ($user->admin && !count($linked_factures))
                    return 1;
                break;
            case 'syntec':
                $linked_factures = getElementElement('contrat', 'facture', $this->id);
                if (($user->admin || $user->rights->bimpcommercial->priceVente) && !count($linked_factures))
                    return 1;
                break;
            case 'entrepot':
            case 'note_private':
            case 'fk_soc_facturation':
            case 'denounce':
            case 'fk_commercial_suivi':
            case 'fk_commercial_signature':
            case 'moderegl':
            case 'objet_contrat':
            case 'ref_customer':
            case 'relance_renouvellement':
            case 'facturation_echu':
            case 'label':
                return 1;

            case 'condregl':
                if ($user->rights->bimpcontract->change_periodicity)
                    return 1;
                break;
            default:
                return 0;
        }
    }

    public function canDelete()
    {
        if ($this->getData('statut') != self::CONTRAT_STATUS_BROUILLON)
            return 0;

        return 1;
    }

    public function canClientView()
    {
        global $userClient;

        if (!BimpObject::objectLoaded($userClient)) {
            return 0;
        }

        if ($this->isLoaded()) {
            if ((int) $userClient->getData('id_client') !== (int) $this->getData('fk_soc')) {
                return 0;
            }

            if ($userClient->isAdmin()) {
                return 1;
            }

            if (in_array($this->id, $userClient->getAssociatedContratsList())) {
                return 1;
            }

            return 0;
        }

        return 1;
    }

    public function canClientViewDetail()
    {
        global $userClient;
        if (BimpObject::objectLoaded($userClient) && $userClient->isAdmin()) {
            return 1;
        }
        return 0;
    }

    public function canShowAdmin()
    {
        global $user;
        if ($this->getData('statut') == self::CONTRAT_STATUS_ACTIVER || $this->getData('statut') == self::CONTRAT_STATUT_ABORT || $this->getData('statut') == self::CONTRAT_STATUS_CLOS)
            if ($user->admin == 1)
                return 1;
        return 0;
    }

    // Getters booléens: 

    public function isActionAllowed($action, &$errors = []): int
    {
        switch ($action) {
            case 'createEcheancier':
                if ((int) $this->getData('statut') != self::CONTRAT_STATUS_ACTIVER) {
                    $errors[] = 'Ce contrat n\'est pas actif';
                    return 0;
                }

                if ((int) $this->getData('periodicity') == self::CONTRAT_PERIOD_AUCUNE) {
                    $errors[] = 'Aucune périodicité';
                    return 0;
                }

                if ((int) $this->db->getValue('bcontract_prelevement', 'id', 'id_contrat = ' . $this->id)) {
                    $errors[] = 'Echéancier déjà créé';
                    return 0;
                }
                return 1;

            case 'createSignature':
            case 'createSignatureDocuSign':
                return !$this->getChildObject('signature')->isLoaded() and ((int) $this->getData('statut') == self::CONTRAT_STATUS_VALIDE || (int) $this->getData('statut') == self::CONTRAT_STATUS_ACTIVER_TMP);
        }
        return parent::isActionAllowed($action, $errors);
    }

    public function isContratDelegation(): bool
    {

        return (substr($this->getRef(), 0, 3) == 'CDP') ? 1 : 0;
    }

    public function isFactAuto()
    {

        $instance = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_echeancier');
        if ($instance->find(['id_contrat' => $this->id])) {

            if ($instance->getData('validate') == 1)
                return 1;
        }

        return 0;
    }

    public function isConformWithDate()
    {
        if (!$this->getData('end_date_contrat') && $this->getEndDate() == "") {
            return 0;
        }
        return 0;
    }

    public function isClientCompany()
    {
        $client = $this->getChildObject('client');

        if (BimpObject::objectLoaded($client)) {
            return $client->isCompany();
        }

        return 0;
    }

    public function isCommercialOfContrat()
    {
        global $user;

        $searchComm = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $this->getData('fk_commercial_suivi'));

        if ($user->admin)
            return 1;

        if ($user->id == $searchComm->id)
            return 1;

        if ($searchComm->getData('statut') == 0) {
            if ($user->id == $this->getCommercialClient())
                return 1;
        }

        if (isset($user->rights->synopsiscontrat->renouveller) && $user->rights->synopsiscontrat->renouveller)
            return 1;

        return 0;
    }

    public function verifDureeForOldToNew()
    {
        $can_merge = 1;
        $most_end = 0;
        $lines = $this->getChildrenList('lines');
        if (count($lines) > 1) {
            foreach ($lines as $id) {
                $line = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contratLine', $id);
                $end = new DateTime($line->getData('date_fin_validite'));
                if ($can_merge == 1 && ($end->getTimestamp() == $most_end || $most_end == 0)) {
                    $most_end = $end->getTimestamp();
                } else {
                    $can_merge = 0;
                }
            }
        }
        return $can_merge;
    }

    public function isValide()
    {
        if ($this->getData('statut') == 11) { // On est dans les nouveaux contrats
            return true;
        }
        return false;
    }

    public function is_not_finish()
    {
        if ($this->reste_periode() == 0) {
            return 0;
        }
        return 1;
    }

    public function isSigned($display = null)
    {

        if (!is_null($this->getData('date_contrat'))) {
            return (is_null($display) ? 1 : "<b class='success'>OUI</b>");
        } else {
            return (is_null($display) ? 0 : "<b class='danger'>NON</b>");
        }
    }

    public function isBySocId()
    {
        if (isset($_REQUEST['socid']) && $_REQUEST['socid'] > 0) {
            return 1;
        }
        return 0;
    }

    // Getters params: 

    public function getActionsButtons()
    {
        global $conf, $langs, $user;
        $buttons = Array();

        if ($user->admin) {
            $buttons[] = array(
                'label'   => 'TEST EN COURS',
                'icon'    => 'fas_retweet',
                'onclick' => $this->getJsActionOnclick('testContrat', array(), array(
                ))
            );
        }

        if ($this->isLoaded() && BimpTools::getContext() != 'public') {

            $status = $this->getData('statut');
            $callback = 'function(result) {if (typeof (result.file_url) !== \'undefined\' && result.file_url) {window.open(result.file_url)}}';

            if ($user->admin) {
                $buttons[] = array(
                    'label'   => 'Annuler renew(ADMIN)',
                    'icon'    => 'fas_retweet',
                    'onclick' => $this->getJsActionOnclick('redefineEcheancier', array(), array(
                        'form_name' => 'redefineEcheancier'
                    ))
                );
            }

            if (BT_ficheInter::isActive() && $status == self::CONTRAT_STATUS_ACTIVER && $user->rights->bimptechnique->plannified) {
                if ($user->admin == 1 || $user->id == 375) { // Pour les testes 
                    $buttons[] = array(
                        'label'   => 'Plannifier une intervention',
                        'icon'    => 'fas_calendar',
                        'onclick' => $this->getJsActionOnclick('planningInter', array(), array(
                            'form_name' => 'planningInter'
                        ))
                    );
                }
            }



            if ($status == self::CONTRAT_STATUS_VALIDE || $status == self::CONTRAT_STATUT_WAIT_ACTIVER) {
                $buttons[] = array(
                    'label'   => 'Contrat refusé par le client',
                    'icon'    => 'fas_times',
                    'onclick' => $this->getJsActionOnclick('refuse', array(), array(
                        'confirm_msg' => "Cette action est irréverssible, continuer ?",
                    ))
                );
            }

            if ($status == self::CONTRAT_STATUS_ACTIVER && $user->rights->bimpcontract->auto_billing) {
                if ($this->is_not_finish() && $this->reste_a_payer() > 0) {
                    $buttons[] = array(
                        "label"   => 'Facturation supplémentaire',
                        'icon'    => "fas_file-invoice",
                        'onclick' => $this->getJsActionOnclick('factureSupp', array(), array())
                    );
                }
            }
            if (($user->admin || $user->rights->bimpcontract->to_validate) && $this->getData('tacite') != 12 && $this->getData('tacite') != 0) {
                $buttons[] = array(
                    "label"   => 'Annuler la reconduction tacite',
                    'icon'    => "fas_hand-paper",
                    'onclick' => $this->getJsActionOnclick('stopTacite', array(), array(
                        'confirm_msg' => "Etes-vous sûr ? Cette action est  irréversible"
                    ))
                );
                if ($user->admin)
                    $buttons[] = array(
                        "label"   => 'Renouvellement tacite',
                        'icon'    => "fas_hand-paper",
                        'onclick' => $this->getJsActionOnclick('tacite', array(), array(
                            'confirm_msg' => "Etes-vous sûr ? Cette action est  irréversible"
                        ))
                    );
            }
            if (/* ($this->getData('tacite') == 12 || $this->getData('tacite') == 0) && */!$this->getData('next_contrat') && ($status == self::CONTRAT_STATUS_ACTIVER || $status == self::CONTRAT_STATUS_CLOS)) {
                $buttons[] = array(
                    'label'   => 'Renouveler par clonage du contrat (SN et sites inclus)',
                    'icon'    => 'fas_retweet',
                    'onclick' => $this->getJsActionOnclick('manuel', array(), array(
                        'form_name' => 'use_syntec'
                    ))
                );
            }

            $linked_factures = getElementElement('contrat', 'facture', $this->id);
            $e = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_echeancier');

            if (!$this->getData('periodicity') && $this->getData('statut') == 1) {
                $buttons[] = array(
                    'label'   => 'Ancienne vers Nouvelle version',
                    'icon'    => 'fas_info',
                    'onclick' => $this->getJsActionOnclick('oldToNew', array(), array(
                        'form_name' => 'old_to_new'
                    ))
                );
            }

            if (($this->getData('statut') == self::CONTRAT_STATUS_ACTIVER || $this->getData('statut') == self::CONTRAT_STATUS_CLOS) && !$this->getContratChild()) {
                if ($this->getData('tacite') == 12 || $this->getData('tacite') == 0) {
                    $button_label = "Renouveler par clonage du devis";
                    $button_icone = "fas_file-invoice";
                    $button_form = array();
                    $button_action = "createProposition";
                    $buttons[] = array(
                        'label'   => $button_label,
                        'icon'    => $button_icone,
                        'onclick' => $this->getJsActionOnclick($button_action, array(), $button_form)
                    );
                }
            }


            if ($e = BimpCache::findBimpObjectInstance('bimpcontract', 'BContract_echeancier', ['id_contrat' => $this->id])) {
                if ($this->getData('statut') == self::CONTRAT_STATUS_ACTIVER && $user->rights->bimpcontract->auto_billing) {
                    $for_action = ($e->getData('validate') == 1) ? 0 : 1;
                    $label = ($for_action == 1) ? "Activer la facturation automatique" : "Désactiver la facturation automatique";

                    $buttons[] = array(
                        'label'   => $label,
                        'icon'    => "fas_play",
                        'onclick' => $this->getJsActionOnclick('autoFact', array('to' => $for_action, 'e' => $e->id), array(
                        ))
                    );
                }
            }
            if (($this->getData('statut') == self::CONTRAT_STATUS_ACTIVER) && $user->rights->bimpcontract->to_anticipate) {
                if (!$this->getData('end_date_reel') && !$this->getData('anticipate_close_note')) {
                    $buttons[] = array(
                        'label'   => 'Anticiper la cloture du contrat',
                        'icon'    => 'fas_clock',
                        'onclick' => $this->getJsActionOnclick('anticipateClose', array(), array(
                            'form_name' => 'anticipate'
                        ))
                    );
                }
            }
            if (($user->rights->bimpcontract->to_validate || $user->admin) && $this->getData('statut') != self::CONTRAT_STATUT_ABORT && $this->getData('statut') != self::CONTRAT_STATUS_CLOS && $status != self::CONTRAT_STATUS_REFUSE) {
                $buttons[] = array(
                    'label'   => 'Abandonner le contrat',
                    'icon'    => 'fas_times',
                    'onclick' => $this->getJsActionOnclick('abort', array(), array(
                        'confirm_msg' => "Cette action est irréverssible, continuer ?",
                    ))
                );
            }

            if (($status == self::CONTRAT_STATUS_ACTIVER || $status == self::CONTRAT_STATUS_ACTIVER_TMP || $status == self::CONTRAT_STATUT_WAIT_ACTIVER) && $user->rights->contrat->desactiver) {
                $buttons[] = array(
                    'label'   => 'Clore le contrat',
                    'icon'    => 'fas_times',
                    'onclick' => $this->getJsActionOnclick('close', array(), array(
                        'confirm_msg' => "Voulez vous clore ce contrat ?",
                )));
            }
            if (($status == self::CONTRAT_STATUS_ACTIVER || $status == self::CONTRAT_STATUS_VALIDE || $status == self::CONTRAT_STATUS_ACTIVER_TMP || $status == self::CONTRAT_STATUT_WAIT_ACTIVER)) {
                $buttons[] = array(
                    'label'   => 'Envoyer par e-mail',
                    'icon'    => 'envelope',
                    'onclick' => $this->getJsActionOnclick('sendEmail', array(), array(
                        'form_name' => 'email'
                    ))
                );
            }
            if (($status == self::CONTRAT_STATUS_ACTIVER || $status == self::CONTRAT_STATUT_ABORT || $status == self::CONTRAT_STATUS_CLOS) && $user->rights->bimpcontract->to_reopen) {
                $buttons[] = array(
                    'label'   => 'Réouvrir le contrat',
                    'icon'    => 'fas_folder-open',
                    'onclick' => $this->getJsActionOnclick('reopen', array(), array())
                );

                $buttons[] = array(
                    'label'   => 'Mettre à jours l\'indice Syntec',
                    'icon'    => 'fas_sync',
                    'onclick' => $this->getJsActionOnclick('updateSyntec', array(), array())
                );
            }
            if (($status == self::CONTRAT_STATUS_WAIT || $status == self::CONTRAT_STATUS_ACTIVER_SUP || $status == self::CONTRAT_STATUS_ACTIVER_TMP || $status == self::CONTRAT_STATUT_WAIT_ACTIVER || $status == self::CONTRAT_STATUS_VALIDE) && $user->rights->bimpcontract->to_validate && $status != self::CONTRAT_STATUS_WAIT) {
                $buttons[] = array(
                    'label'   => 'Activer le contrat',
                    'icon'    => 'fas_play',
                    'onclick' => $this->getJsActionOnclick('activateContrat', array(), array(
                        'form_name' => "have_signed",
                )));
            }
            if ($status == self::CONTRAT_STATUS_WAIT && $user->rights->bimpcontract->to_validate) {
                $buttons[] = array(
                    'label'   => 'Valider la conformité du contrat',
                    'icon'    => 'fas_check',
                    'onclick' => $this->getJsActionOnclick('validation', array(), array())
                );
            }
            if (!is_null($this->getData('date_contrat')) && $status != self::CONTRAT_STATUS_ACTIVER && $status != self::CONTRAT_STATUT_WAIT_ACTIVER && $status != self::CONTRAT_STATUS_REFUSE) {
                $buttons[] = array(
                    'label'   => 'Dé-signer le contrat',
                    'icon'    => 'fas_undo',
                    'onclick' => $this->getJsActionOnclick('unSign', array(), array())
                );
            }
            if (is_null($this->getData('date_contrat')) &&
                    ($status == self::CONTRAT_STATUS_ACTIVER || $status == self::CONTRAT_STATUS_ACTIVER_SUP || $status == self::CONTRAT_STATUS_ACTIVER_TMP || $status == self::CONTRAT_STATUS_VALIDE || $status == self::CONTRAT_STATUT_WAIT_ACTIVER)) {
                $buttons[] = array(
                    'label'   => 'Contrat signé',
                    'icon'    => 'fas_signature',
                    'onclick' => $this->getJsActionOnclick('signed', array(), array(
                        'confirm_msg'      => "Voulez vous identifier ce contrat comme signé ?",
                        'success_callback' => $callback
                )));
            }

            if ($status == self::CONTRAT_STATUS_BROUILLON || $user->id == 460 || $user->admin) {
                $buttons[] = array(
                    'label'   => 'Générer le PDF du contrat',
                    'icon'    => 'fas_file-pdf',
                    'onclick' => $this->getJsActionOnclick('generatePdf', array(), array())
                );
                $buttons[] = array(
                    'label'   => 'Générer le PDF du courrier',
                    'icon'    => 'fas_file-pdf',
                    'onclick' => $this->getJsActionOnclick('generatePdfCourrier', array(), array())
                );
            }


            //trop bizarre ces conditions....
//            if (($status != self::CONTRAT_STATUS_BROUILLON || $status == self::CONTRAT_STATUS_WAIT ) && ($user->rights->bimpcontract->to_generate)) {
//
//                if ($status != self::CONTRAT_STATUS_CLOS && $status != self::CONTRAT_STATUS_ACTIVER && $status != self::CONTRAT_STATUS_ACTIVER_TMP && $status != self::CONTRAT_STATUS_ACTIVER_SUP) {
//                    $buttons[] = array(
//                        'label'   => 'Générer le PDF du contrat',
//                        'icon'    => 'fas_file-pdf',
//                        'onclick' => $this->getJsActionOnclick('generatePdf', array(), array())
//                    );
//                }
//
//                if ($status != self::CONTRAT_STATUS_CLOS && $status != self::CONTRAT_STATUS_ACTIVER && $status != self::CONTRAT_STATUS_ACTIVER_TMP && $status != self::CONTRAT_STATUS_ACTIVER_SUP) {
//                    $buttons[] = array(
//                        'label'   => 'Générer le PDF du courrier',
//                        'icon'    => 'fas_file-pdf',
//                        'onclick' => $this->getJsActionOnclick('generatePdfCourrier', array(), array())
//                    );
//                }
//            }

            if ($user->rights->contrat->creer && $status == self::CONTRAT_STATUS_BROUILLON) {
                $buttons[] = array(
                    'label'   => 'Demander la validation du contrat',
                    'icon'    => 'fas_share',
                    'onclick' => $this->getJsActionOnclick('demandeValidation', array(), array())
                );
            }
        }

        if ($user->id == 460 && $status == self::CONTRAT_STATUS_ACTIVER) {
            $buttons[] = array(
                'label'   => 'Ajouter un accompte',
                'icon'    => 'euro',
                'onclick' => $this->getJsActionOnclick('addAcompte', array(), array("form_name" => "addAcc"))
            );
        }

        $signature = $this->getChildObject('signature');
        if (BimpObject::objectLoaded($signature)) {
            if (!(int) $signature->isSigned()) {
                $buttons = BimpTools::merge_array($buttons, $signature->getActionsButtons());
            } else {
                $buttons = BimpTools::merge_array($buttons, $signature->getSignedButtons());
                $signed = true;
            }
        }

        $errors = array();
        $use_signature = (int) BimpCore::getConf('contrat_use_signatures', null, 'bimpcontract');

        if ($this->isActionAllowed('createSignature', $errors)) {
            if ($this->canSetAction('createSignature')) {
                $params = array();
                if ($use_signature) {
                    $params['form_name'] = 'create_signature';
                } else {
                    $params['confirm_msg'] = 'Veuillez confirmer la validation ' . $this->getLabel('of_this');
                }

                $buttons[] = array(
                    'label'   => 'Créer signature',
                    'icon'    => 'fas_signature',
                    'onclick' => $this->getJsActionOnclick('validate', array(), $params)
                );
            } else {
                $errors[] = 'Vous n\'avez pas la permission de valider ce contrat';
            }
        }

        if ($user->admin and 0 < (int) $this->getData('id_signature')) {
            $buttons[] = array(
                'label'   => 'Supprimer signature (ADMIN)',
                'icon'    => 'fas_trash',
                'onclick' => $this->getJsActionOnclick('deleteSignature', array(), array(
                    'confirm_msg' => "Cette action est irréverssible, continuer ?",
            )));
        }


        return $buttons;
    }

    public function getBulkActions()
    {
        $actions = array(
//            [
//                'label' => 'Fusionner les contrats sélectionnés',
//                'icon' => 'fas_sign-in-alt',
//                'onclick' => 'setSelectedObjectsAction($(this), \'list_id\', \'fusion\', {}, null, null, true)',
//                'btn_class' => 'setSelectedObjectsAction'
//            ],
            [
                'label'     => 'Facturer les contrats sélectionnés',
                'icon'      => 'fas_sign-in-alt',
                'onclick'   => 'setSelectedObjectsAction($(this), \'list_id\', \'multiFact\', {}, null, null, true)',
                'btn_class' => 'setSelectedObjectsAction'
            ]
        );
        if (1 || $this->canSetAction('sendEmail')) {
            $actions[] = array(
                'label'   => 'Fichiers PDF',
                'icon'    => 'fas_file-pdf',
                'onclick' => $this->getJsBulkActionOnclick('generateBulkPdf', array(), array('single_action' => true))
            );
            $actions[] = array(
                'label'   => 'Fichiers Zip des PDF',
                'icon'    => 'fas_file-pdf',
                'onclick' => $this->getJsBulkActionOnclick('generateZipPdf', array(), array('single_action' => true))
            );
        }
        return $actions;
    }

    public function getDirOutput()
    {
        global $conf;
        return $conf->contract->dir_output;
    }

    public function getProvLink()
    {
        return str_replace($this->getRef(), '(PROV' . $this->id . ')', $this->getLink());
    }

    // Getters données: 

    public function getEcheancier(&$errors = array())
    {
        $echeancier = BimpCache::findBimpObjectInstance('bimpcontract', 'BContract_echeancier', array(
                    'id_contrat' => $this->id
        ));

        if (!BimpObject::objectLoaded($echeancier)) {
            $errors[] = 'Aucun échéancier lié à ce contrat';
            return null;
        }

        return $echeancier;
    }

    public function getHeuresRestantesDelegation()
    {
        $reste = 0;
        $totalHeuresVendues = 0;
        $totalHeuresFaites = 0;

        $arrayHeuresVendues = $this->getTotalHeureDelegation(true);

        $instanceFiDet = BimpCache::getBimpObjectInstance('bimptechnique', 'BT_ficheInter_det');
        $debug = false;
        if (count($arrayHeuresVendues) > 0) {
            foreach ($arrayHeuresVendues as $code => $time) {

                $infoLigne = explode(':::::', $code);

                $list = $instanceFiDet->getList(array('id_line_contrat' => $infoLigne[1]));
                if (count($list) > 0) {
                    if ($debug)
                        echo '<h2>Services</h2>';

                    foreach ($list as $det) {
                        if ($debug) {
//                            print_r($det);
                            $instanceFiDetDebug = BimpCache::getBimpObjectInstance('bimptechnique', 'BT_ficheInter_det', $det['rowid']);
                            $fi = $instanceFiDetDebug->getParentInstance();
                            echo '<br/>' . $fi->getNomUrl() . ' ' . ($det['duree'] / 3600) . '<br/>';
                        }
                        $totalHeuresFaites += $det['duree'] / 3600;
                    }
                }

                $totalHeuresVendues += $time;
            }

            $list = $instanceFiDet->getList(array('parent:fk_contrat' => $this->id, 'type' => 5));
            if (count($list) > 0) {
                if ($debug)
                    echo '<h2>Déplacements</h2>';
                foreach ($list as $det) {

                    if ($debug) {
                        $fi = BimpCache::getBimpObjectInstance('bimptechnique', 'BT_ficheInter', $det['fk_fichinter']);
                        echo '<br/>' . $fi->getNomUrl() . ' ' . ($det['duree'] / 3600) . '<br/>';
                    }
                    $totalHeuresFaites += $det['duree'] / 3600;
                }
            }
        }

        if ($debug) {
            echo '<br/>Heure délégation vendue : ' . $totalHeuresVendues . '<br/>';
            echo '<br/>Heure délégation faite : ' . $totalHeuresFaites . '<br/>';
        }

        $reste = $totalHeuresVendues - $totalHeuresFaites;

        return $reste;
    }

    public function getTotalHeureDelegation($justActif = false): array
    {

        $return = Array();

        //$services = Array('SERV19-DP1', 'SERV19-DP2', 'SERV19-DP3', 'SAV-NIVEAU_5', 'SERV22-DPI-AAPEI', 'SERV19-FD01', 'AUTRE');

        $children = $this->getChildrenList('lines', ($justActif) ? Array('statut' => 4) : Array());

        foreach ($children as $id_child) {
            $child = $this->getChildObject('lines', $id_child);
            $instance = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $child->getData('fk_product'));
            //if(in_array($instance->getRef(), $services)) {
            $return[$instance->getRef() . ':::::' . $id_child] += (float) $child->getData('qty') * $instance->getData('duree_i') / 3600 * $this->getRatioWithAvProlongation();
            //}
        }

        return $return;
    }

    public function getHeuresDelegationFromInterByService(): array
    {
        $return = Array();

        $instance = BimpCache::getBimpObjectInstance('bimptechnique', 'BT_ficheInter');
        $list = $instance->getList(Array('fk_contrat' => $this->id));

        if (count($list) > 0) {

            foreach ($list as $index) {
                $child = BimpCache::getBimpObjectInstance('bimptechnique', 'BT_ficheInter_det');
                $children = $child->getList(array('fk_fichinter' => $index['rowid']));
                if (count($children) > 0) {
                    foreach ($children as $i) {

//                        if($index['fk_statut'] > 0) {
                        if ($i['type'] == 3) {
                            $return['AUTRE'] += $i['duree'] / 3600;
                        } else {
                            $childContrat = $this->getChildObject('lines', $i['id_line_contrat']);
                            $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $childContrat->getData('fk_product'));
                            $return[$product->getRef() . '_' . $childContrat->id] += $i['duree'] / 3600;
                        }

//                        }
                    }
                }
            }
        }

        return $return;
    }

    public function getClientFacture()
    {
        if ((int) $this->getData('fk_soc_facturation')) {
            $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $this->getData('fk_soc_facturation'));
            if (BimpObject::objectLoaded($client)) {
                return $client;
            }
        }

        if ((int) $this->getData('fk_soc')) {
            $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $this->getData('fk_soc'));
            if (BimpObject::objectLoaded($client)) {
                return $client;
            }
        }

        return null;
    }

    public function getDurreeVendu()
    {
        $tot = 0;
        $lines = $this->getChildrenObjects('lines');
        foreach ($lines as $line) {
            $prod = $line->getChildObject('produit');
            $tot += $prod->getData('duree_i') * $line->getData('qty');
        }
        $tot = $tot * $this->getRatioWithAvProlongation();
        return $tot;
    }

    public function getValueSecteurInPropal($type)
    {
        return (in_array($type, array('E', 'CTE')) ? 'CTE' : 'CTC');
    }

    public function getTotalPa($line_type = -1)
    {
        $total_PA = 0;
        $children_list = $this->getChildrenList('lines');
        foreach ($children_list as $nb => $id) {
            $child = $this->getChildObject('lines', $id);
            $total_PA += $child->getData('buy_price_ht') * $child->getData('qty');
        }
        return $total_PA;
    }

    public function getTitreAvenantSection()
    {
        $titre = "Avenants";

        $instance = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_avenant');
        $list = $instance->getList(["id_contrat" => $this->id]);

        $titre .= '<span style="margin-left: 10px" class="badge badge-primary">' . count($list) . '</span>';

        return $titre;
    }

    public function getAllSerialsForAvenant()
    {
        $html = "";
        foreach ($this->dol_object->lines as $line) {
            $p = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $line->fk_product);
            $l = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contratLine', $line->id);

            $html .= "<b>" . $p->getData('ref') . '</b><br />';

            $items = array();
            $serials = json_decode($l->getData('serials'));

            if (is_array($serials)) {
                foreach ($serials as $serial) {
                    $items[$serial] = $serial;
                }
            }

            $html .= BimpInput::renderInput('check_list', 'delserials_' . $l->id, '', ['items' => $items]);
        }

        return $html;
    }

    public function getInitialRenouvellement()
    {
        return $this->getData('tacite') + $this->getData('current_renouvellement');
    }

    public function getTotalFi($tms)
    {
        $ficheInter = BimpCache::getBimpObjectInstance('bimptechnique', 'BT_ficheInter');
        return $ficheInter->time_to_qty($ficheInter->timestamp_to_time($tms)) * BimpCore::getConf('cout_horaire_technicien', null, 'bimptechnique');
    }

    public function getMargePrevisionnel($total_fis)
    {
        return $total_fis / ($this->getJourTotal() - $this->getJourRestant()) * $this->getJourTotal();
    }

    public function getMargeInter()
    {
        if ($this->isLoaded()) {
            $total_contrat = $this->getTotalContrat();
            $in_out_tms = $this->getTmsArray();
            $total_fis = $this->getTotalFi($in_out_tms->in);

            return $total_contrat - $total_fis;
        }

        return null;
    }

    public function getListFi()
    {
        if ($this->isLoaded()) {
            return BimpCache::getBimpObjectObjects('bimptechnique', 'BT_ficheInter', ['fk_contrat' => $this->id]);
        }
        return Array();
    }

    public function getAllServices($field = 'fk_product')
    {
        $servicesId = [];
        foreach ($this->dol_object->lines as $line) {
            $servicesId[] = $line->$field;
        }
        return $servicesId;
    }

    public function getAcomptesClient()
    {

        $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $this->getData('fk_soc'));
        $liste = $acc->getList(['fk_soc' => $this->getData('fk_soc'), 'type' => 3]);
        $array_acc = [];
        foreach ($liste as $nb => $facture) {
            $acc = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $facture['rowid']);
            // Si l'accompte n'est pas déjà lier au contrat
            if (!count(getElementElement('contrat', 'facture', $this->id, $acc->id))) {
                $array_acc[$acc->id] = $acc->getData('facnumber');
            }
        }

        return $array_acc;
    }

    public function getRenouvellementNumberFromDate($date)
    {
        $datef = new DateTime();
        $datef->setTimestamp(strtotime($date));

        $debut = new DateTime();
        $fin = new DateTime();
        $Timestamp_debut = strtotime($this->getData('date_start'));
//            echo $datef->format('d / m / Y').'<br/>';
        $renouvellement = 0;
        if ($Timestamp_debut > 0 && $this->getData('duree_mois') > 0) {
            $debut->setTimestamp($Timestamp_debut);
            $fin->setTimestamp($Timestamp_debut);
            for ($i = 0; $i < 5; $i++) {
                $fin = $fin->add(new DateInterval("P" . $this->getData('duree_mois') . "M"));
                $fin = $fin->sub(new DateInterval("P1D"));
//                    echo($debut->format('d / m / Y').' '.$fin->format('d / m / Y').' '.$i.'av<br/>');
                if ($datef > $debut && $datef < $fin) {
                    $renouvellement = $i;
                    break;
                }
                $debut = $debut->add(new DateInterval("P" . $this->getData('duree_mois') . "M"));
//                    $fin = $fin->add(new DateInterval("P1D"));
            }
        }
        return $renouvellement;
    }

    public function getCommercialClient($object = false)
    {
        if ($this->isLoaded()) {
            $id_commercial = $this->db->getValue('societe_commerciaux', 'fk_user', 'fk_soc = ' . $this->getData('fk_soc'));

            $commercial = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_commercial);
            if (!$object)
                return $commercial->id;
            elseif ($object)
                return $commercial;
        }
    }

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, $main_alias = 'a', &$errors = array(), $excluded = false)
    {
        switch ($field_name) {
            case 'negatif_positif':
                if (count($values) > 0) {

                    $filters = Array('statut' => 11);

                    $list = BimpCache::getBimpObjectObjects("bimpcontract", 'BContract_contrat', $filters);

                    $in = [];

                    foreach ($list as $contrat) {
                        $marge = $contrat->getMargeInter();
                        if (in_array('0', $values)) {
                            if ($marge < 0)
                                $in[] = $contrat->id;
                        }
                        if (in_array('1', $values)) {
                            if ($marge > 0)
                                $in[] = $contrat->id;
                        }
                        if (in_array('2', $values)) {
                            if ($marge == 0)
                                $in[] = $contrat->id;
                        }
                    }

                    $filters[$main_alias . '.rowid'] = ['in' => $in];
                }
                break;

            case 'commercialclient':
                $alias = $main_alias . '___sc';
                $joins[$alias] = array(
                    'alias' => $alias,
                    'table' => 'societe_commerciaux',
                    'on'    => $alias . '.fk_soc = ' . $main_alias . '.fk_soc'
                );
                $filters[$alias . '.fk_user'] = array(
                    ($excluded ? 'not_' : '') . 'in' => $values
                );
                break;

            case 'use_syntec':
                if (count($values) == 1) {
                    $alias = $main_alias . '___ce';
                    $joins[$alias] = array(
                        'alias' => $alias,
                        'table' => 'contrat_extrafields',
                        'on'    => $alias . '.fk_object = ' . $main_alias . '.rowid'
                    );
                    if (in_array('0', $values)) {
                        $sql = '(' . $alias . '.syntec = 0 OR ' . $alias . '.syntec IS NULL)';
                        $filters[$alias . '___custom_syntec'] = array(
                            'custom' => $sql
                        );
                    }
                    if (in_array('1', $values)) {
                        $filters[$alias . '.syntec'] = array(
                            '>' => '0'
                        );
                    }
                }
                break;

            case 'have_fi':
                if (count($values) == 1) {
                    $sql = "SELECT DISTINCT c.rowid FROM llx_contrat as c, llx_fichinter as f WHERE c.rowid = f.fk_contrat";
                    $res = $this->db->executeS($sql, 'array');
                    $in = [];
                    foreach ($res as $nb => $i) {
                        $in[] = $i['rowid'];
                    }
                    if (in_array('1', $values)) {
                        $filters[$main_alias . '.rowid'] = [
                            'in' => $in
                        ];
                    }
                    if (in_array('0', $values)) {
                        $filters[$main_alias . '.rowid'] = [
                            'not_in' => $in
                        ];
                    }
                }
                break;
//            case 'end_date':
//                $in = [];
//                $borne = (object) $values[0];
//                $sql = "SELECT rowid FROM llx_contrat";
//                $all = $this->db->executeS($sql, 'array');
//                
//                $contrat = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat');
//                
//                foreach($all as $nb => $i) {
//                    $contrat->fetch($i['rowid']);
//                }
//                
//                echo '<pre>';
//                echo count($all);
//                
//                $filters['a.rowid'] = ['in' => $in];
//                break;
            case 'reconduction':
                $in = [];
                $included = [];
                $sql = "SELECT c.rowid FROM llx_contrat as c, llx_contrat_extrafields as e WHERE e.fk_object = c.rowid ";

                if (count($values) > 0) {

                    if (in_array('0', $values)) {
                        // Pas de reconduction
                        $included[] = 0;
                    }
                    if (in_array('1', $values)) {
                        $included[] = self::CONTRAT_RENOUVELLEMENT_SUR_PROPOSITION;
                    }
                    if (in_array('2', $values)) {
                        foreach (self::$renouvellement as $code => $text) {
                            if ($code != self::CONTRAT_RENOUVELLEMENT_NON && $code != self::CONTRAT_RENOUVELLEMENT_SUR_PROPOSITION)
                                $included[] = $code;
                        }
                    }

                    $sql .= ' AND e.tacite IN(' . implode(',', $included) . ')';

                    $res = $this->db->executeS($sql, 'array');
                    foreach ($res as $nb => $i) {
                        $in[] = $i['rowid'];
                    }
                    $filters[$main_alias . '.rowid'] = ['in' => $in];
                }
                break;
        }



        parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $main_alias, $errors, $excluded);
    }

    public function getCommercialclientSearchFilters(&$filters, $value, &$joins = array(), $main_alias = 'a')
    {
        $alias = 'sc';
        $joins[$alias] = array(
            'alias' => $alias,
            'table' => 'societe_commerciaux',
            'on'    => $alias . '.fk_soc = ' . $main_alias . '.fk_soc'
        );
        $filters[$alias . '.fk_user'] = $value;
    }

    public function isClosDansCombienDeTemps()
    {

        $aujourdhui = new DateTime();
        $finContrat = new DateTime($this->displayRealEndDate("Y-m-d"));
        $diff = $aujourdhui->diff($finContrat);
        if (!$diff->invert) {
            return $diff->d;
        }
        return 0;
    }

    public function getCurrentSyntecFromSyntecFr()
    {
        $syntec = file_get_contents("https://syntec.fr/");
        if (preg_match('/<div class="indice-number"[^>]*>(.*)<\/div>/isU', $syntec, $matches)) {
            $indice = str_replace(' ', "", strip_tags($matches[0]));
            return str_replace("\n", "", $indice);
        } else {
            return 0;
        }
    }

    public function getListClient($object)
    {
        $list = $this->db->getRows($object, 'fk_soc = ' . $this->getData('fk_soc'));
        $return = [];

        foreach ($list as $l) {
            $instance = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_' . ucfirst($object), $l->rowid);
            $return[$instance->id] = $instance->getData('ref') . " - " . $instance->getData('libelle');
        }

        return $return;
    }

    public function getModeReglementClient()
    {
        global $db;
        BimpTools::loadDolClass('societe');
        $client = new Societe($db);
        $client->fetch($this->getData('fk_soc'));
        return $client->mode_reglement_id;
    }

    public function getConditionReglementClient()
    {
        global $db;
        BimpTools::loadDolClass('societe');
        $client = new Societe($db);
        $client->fetch($this->getData('fk_soc'));
        return $client->cond_reglement_id;
    }

    public function getEndDate()
    {
        $debut = new DateTime();
        $fin = new DateTime();
        $Timestamp_debut = strtotime($this->getData('date_start'));
        if ($Timestamp_debut > 0) {
            $debut->setTimestamp($Timestamp_debut);
            $fin->setTimestamp($Timestamp_debut);
            if ($this->getData('duree_mois') > 0)
                $fin = $fin->add(new DateInterval("P" . $this->getData('duree_mois') . "M"));
            $fin = $fin->sub(new DateInterval("P1D"));
            return $fin;
        }
        return '';
    }

    public function getTitleEcheancier()
    {
        return '&Eacute;ch&eacute;ancier du contrat N°' . $this->displayRef();
    }

    public function getMsgsPlanningFi()
    {
        $html = '';

        $html .= "<b>" . BimpRender::renderIcon('warning') . ' Informations sur la création des fiches d\'interventions via ce formulaire</b>';
        $html .= "<p>Si une FI existe déjà pour le ou les techniciens choisis dans la formaulaire, alors la fiche d'intervention ne sera pas créer.</p>";
        $html .= "<p>Par contre la description et les eventuelles commandes et tickets seront rajoutés à ces FI</p>";
        return $html;
    }

    public function getIndiceSyntec()
    {
        return BimpCore::getConf('current_indice_syntec');
    }

    public function getAddContactIdClient()
    {
        $id_client = (int) BimpTools::getPostFieldValue('id_client');

        if (!$id_client) {
            $id_client = (int) $this->getData('fk_soc');
        }

        return $id_client;
    }

    public function getLinesContrat()
    {
        $return = [];
        $lines = $this->getChildrenList('lines');
        foreach ($lines as $id) {
            $line = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contratLine', $id);
            $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $line->getData('fk_product'));
            if ($product->getData('fk_product_type') == 1) {
                $content = $product->getData('ref') . " - " . $product->getData('label');
                if (count(json_decode($line->getData('serials')))) {
                    $content .= '<br />Numéros de série: ' . implode(', ', json_decode($line->getData('serials')));
                }
                $content .= "<br />Vendu HT: " . $line->getData('subprice') * $line->getData('qty') . "€";
                $return[$line->id] = $content;
            }
        }
        return $return;
    }

    public function getLinesForList()
    {
        $lines = Array();

        $children = $this->getChildrenList("lines");

        foreach ($children as $id_child) {
            $child = $this->getChildObject("lines", $id_child);
            $lines[$id_child] = "Renouvellement: " . $child->getData('renouvellement') . " -> " . $child->displayData('fk_product');
        }

        return $lines;
    }

    public function getTotalContratAll()
    {
        
    }

    public function getSyntecSite()
    {
        return "Pour connaitre l'indice syntec en vigueur, veuillez vous rendre sur le site internet <a href='https://www.syntec.fr' target='_blank'>https://www.syntec.fr</a>";
    }

    public function getPdfNamePrincipal($signed = false, $ext = 'pdf')
    {
        if ($signed)
            return 'Contrat_' . $this->getRef() . '_signed.' . $ext;

        return 'Contrat_' . $this->getRef() . '.' . $ext;
    }

    public function getStartDateForOldToNew()
    {
        $lines = $this->getChildrenList('lines');
        $line = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contratLine', $lines[0]);
        return $line->getData('date_ouverture_prevue');
    }

    public function getEndDateForOldToNew()
    {
        $lines = $this->getChildrenList('lines');
        $line = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contratLine', $lines[0]);
        return $line->getData('date_fin_validite');
    }

    public function getDureeForOldToNew()
    {
        $start = new DateTime($this->getStartDateForOldToNew());
        $end = new DateTime($this->getEndDateForOldToNew());
        $interval = $start->diff($end);
        $total = ($interval->y * 12) + $interval->m;
        return $total;
    }

    public function getNextDateFactureOldToNew()
    {
        $lines = $this->getChildrenList('lines');
        print_r($lines, 1) . " hucisduchids";
        $today = new DateTime();
        $line = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contratLine', $lines[0]);
        $start = new DateTime($line->getData('date_ouverture_prevue'));

        if ($today->format('m') < 10) {
            $mois = '0' . ($today->format('m') + 1);
        } else {
            $mois = $today->format('m');
        }

        return $today->format('Y') . '-' . $mois . '-' . $start->format('d');
    }

    public function getTotalHtForOldToNew()
    {
        $total = 0;
        $factures = getElementElement('contrat', 'facture', $this->id);
        foreach ($factures as $nb => $infos) {

            $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $infos['d']);
            if ($facture->getData('fk_statut') == 1 || $facture->getData('fk_statut') == 2) {
                if ($facture->getData('type') == 0) {
                    $total += $facture->getData('total_ht');
                }
            }
        }
        return $total;
    }

    public function resteMoisForOldToNew()
    {
        $today = date('Y-m-d');
        $end = new DateTime($this->getEndDateForOldToNew());
        $today = new DateTime($today);
        $interval = $today->diff($end);
        return ($interval->y * 12) + $interval->m;
    }

    public function infosForOldToNew()
    {
        $content = "";

        $content .= "Déjà facturé: " . $this->getTotalHtForOldToNew() . "€ <br />";
        $content .= "Total du contrat: " . $this->getTotalContrat() . "€";

        return $content;
    }

    public function reste_a_payer($num_renouvellement = 0)
    {
        $list_factures = getElementElement('contrat', 'facture', $this->id);
        $montant = 0;
        if (count($list_factures)) {
            foreach ($list_factures as $link) {
                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $link['d']);
                if (BimpObject::objectLoaded($facture) && (int) $facture->getData('type') != Facture::TYPE_DEPOSIT) {
                    $montant += $facture->getData('total_ht');
                }
            }
        }

        return $this->getTotalContrat() - $montant;
    }

    public function reste_days()
    {
        $today = new DateTime();
        $diff = new DateTime($this->displayRealEndDate("Y-m-d"));
        $reste = $today->diff($diff);
        return $reste->days;
    }

    public function reste_periode($num_renouvellement = 0)
    {
        if ($this->isLoaded()) {
            $instance = BimpCache::findBimpObjectInstance('bimpcontract', 'BContract_echeancier', ['id_contrat' => $this->id]);

            if ($instance && $instance->isLoaded())
                $date_1 = new DateTime($instance->getData('next_facture_date'));
            else
                $date_1 = new DateTime();

            $date_2 = new DateTime($this->displayRealEndDate("Y-m-d"));
            $date_1->sub(new DateInterval('P1D'));
            $interval = $date_1->diff($date_2);

            $totalReste = $interval->m + $interval->y * 12;
            if (!self::PRORATA_PERIODE) {
                if ($interval->d >= 15) {
                    $totalReste += 1;
                }
            } else {
                $totalReste += $interval->d / 30;
            }

            if ($this->getData('periodicity') > 0)
                $return = ($totalReste / $this->getData('periodicity'));
            else
                $return = $totalReste;

            return $return;
        }
    }

    public function getTotalContrat()
    {
        if (!$this->totalContrat) {
            $montant = 0;
            foreach ($this->dol_object->lines as $line) {
//                $child = $this->getChildObject("lines", $line->id);
                //if($child->getData('renouvellement') == $this->getData('current_renouvellement')) {
                $montant += $line->total_ht;
                //}
            }
            $montant += $this->getAddAmountAvenantProlongation();
            $montant += $this->getAddAmountAvenantModification();
            $this->totalContrat = $montant;
            return $montant;
        }
        return round($this->totalContrat, 4);
    }

    public function getCurrentTotal($taxe = 0)
    {
        return $this->getTotal($this->getData('current_renouvellement'), $taxe);
    }

    public function getDureeInitial()
    {
        $children = $this->getChildrenList('avenant', array(
            'type'   => 1,
            'statut' => 2
        ));

        $dureePrlong = 0;

        foreach ($children as $id_child) {
            $av = $this->getChildObject('avenant', $id_child);
            $dureePrlong += $av->getNbMois();
        }

        return ($this->getData('duree_mois') - $dureePrlong) / ($this->getData('current_renouvellement') + 1);
    }

    public function getTotal($renouvellement, $taxe = 0)
    {
        $montant = 0;
        foreach ($this->dol_object->lines as $line) {
            $child = $this->getChildObject("lines", $line->id);
            if ($child->getData('renouvellement') == $renouvellement) {
                if ($taxe)
                    $montant += $line->total_ttc;
                else
                    $montant += $line->total_ht;
            }
        }

        return $montant;
    }

    public function getAddAmountAvenantProlongation($idAvenant = 0, $taxe = 0)
    {
        $total = $this->getCurrentTotal($taxe) * ($this->getRatioWithAvProlongation($idAvenant, 0) - 1);

        return $total;
    }

    public function getRatioWithAvProlongation($idAvenant = 0, $ratioTotalOrRatioInitDuree = 1)
    {//si 0 tous les avenant validée, sinon l'avenant en question
//        $now = new DateTime();
        $ratio = 1;

        $filters = [
            'type' => 1,
//            'want_end_date' => [
//                'operator' => '>=',
//                'value'    => $now->format('Y-m-d')
//            ]
        ];
        if ($idAvenant == 0)//veut le total des valide
            $filters['statut'] = 2;

        $children = $this->getChildrenList('avenant', $filters);

        $dureeContratSansProlongation = $this->getData('duree_mois');

        $dureePrlong = 0;
        foreach ($children as $id_child) {
            $av = $this->getChildObject('avenant', $id_child);
            if ($av->getData('statut') == 2)
                $dureeContratSansProlongation -= $av->getNbMois();

            if (!$idAvenant || $idAvenant == $id_child)
                $dureePrlong += $av->getNbMois();
        }

        if ($ratioTotalOrRatioInitDuree)
            $ratio += $dureePrlong / $dureeContratSansProlongation;
        else
            $ratio += $dureePrlong / $this->getDureeInitial();

        return $ratio;
    }

    public function getAddAmountAvenantModification($idAvenant = 0)
    {

        $now = new DateTime();

        $total = 0;

        $filters = [
            'type' => 0,
        ];
        if ($idAvenant == 0)//veut le total des valide
            $filters['statut'] = 2;

        $children = $this->getChildrenList('avenant', $filters);

        $dureePrlong = 0;

        foreach ($children as $id_child) {
            $av = $this->getChildObject('avenant', $id_child);
            if (!$idAvenant || $idAvenant == $id_child)
                $total += $av->getCoutTotal();
        }

        return $total;
    }

    public function getTotalBeforeRenouvellement()
    {
        $montant = 0;
        foreach ($this->dol_object->lines as $line) {
            $child = $this->getChildObject("lines", $line->id);
            if ($child->getData('renouvellement') == ($this->getData('current_renouvellement') - 1)) {
                $montant += $line->total_ht;
            }
        }

        return $montant;
    }

    public function getTotalDejaPayer($paye_distinct = false, $field = 'total_ht')
    {
        $element_factures = getElementElement('contrat', 'facture', $this->id);
        $montant = 0;
        if (count($element_factures)) {
            foreach ($element_factures as $element) {
                $instance = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $element['d']);
                if ($instance->getData('type') != 3) {
                    if ($paye_distinct) {
                        if ($instance->getData('paye')) {
                            if ($field == 'total_ht')
                                $montant += $instance->getData('total_ht');
                            elseif ($field == 'pa')
//                                $montant += $instance->getData('total_achat_reval_ok');
                                $montant += $instance->getTotalMargeWithReval(array('correction_pa'));
                        }
                    } else {
                        if (/* $instance->getData('type') == 0 */1) {
                            if ($field == 'total_ht')
                                $montant += $instance->getData('total_ht');
                            elseif ($field == 'pa')
//                                $montant += $instance->getData('total_achat_reval_ok');
                                $montant += $instance->getTotalMargeWithReval(array('correction_pa'));
                        }
                    }
                }
            }
        }
//        echo 'ooo'.$montant;
        $montant = round($montant, 4);
        return $montant;
    }

    public function getContratSource()
    {

        if (!is_null($this->getData('contrat_source'))) {
            $source = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat', $this->getData('contrat_source'));
            return $source;
        }

        return null;
    }

    public function getContratChild()
    {
        $instance = BimpCache::findBimpObjectInstance('bimpcontract', 'BContract_contrat', array('contrat_source' => $this->id));
        if ($instance && is_object($instance) && $instance->isLoaded()) {
            return $instance;
        }

        return null;
    }

    public function getIdAvenantActif()
    {
        $avs = $this->getChildrenList('avenant', ['statut' => 2]);
        if (count($avs) > 0)
            return $avs[0];
        return 0;
    }

    public function showInFieldsTable($field)
    {
        if ($this->getData($field))
            return 1;
        return 0;
    }

    public function getJourRestant()
    {
        $now = new DateTime();
        $diff = new DateTime($this->displayRealEndDate("Y-m-d"));
        $interval = $now->diff($diff);
        //print_r($interval);
        return $interval->days;
    }

    public function getJourRestantReel()
    {
        $now = new DateTime();
        $diff = new DateTime($this->displayRealEndDate("Y-m-d"));
        $interval = $now->diff($diff);
        //print_r($interval);
        //if($interval->hours > 0 || $insterval->)

        $signe = ($interval->invert == 1) ? "-" : "";

        return $signe . $interval->days;
    }

    public function getJourTotal()
    {
        $debut = new DateTime($this->getData('date_start'));
        $diff = new DateTime($this->displayRealEndDate("Y-m-d"));

        $interval = $debut->diff($diff);

        return $interval->days;
    }

    public function getJoinFilesValues()
    {
        $values = BimpTools::getValue('fields/join_files', array());

        $id_main_pdf_file = (int) $this->getDocumentFileId();

        if (!in_array($id_main_pdf_file, $values)) {
            $values[] = $id_main_pdf_file;
        }

        $list = $this->getAllFiles();
        foreach ($list as $id => $elem) {
            if (stripos($elem, $this->getRef()) !== FALSE) {
                $values[] = $id;
            }
        }

        return $values;
    }

    public function getIdLinkedPropal()
    {
        if ($this->isLoaded()) {
            $where = 'targettype = \'contrat\' and fk_target = ' . $this->id . ' AND sourcetype = \'propal\'';
            $id_propal = (int) $this->db->getValue('element_element', 'fk_source', $where, 'rowid');

            if (!$id_propal) {
                $where = 'sourcetype = \'contrat\' and fk_source = ' . $this->id . ' AND targettype = \'propal\'';
                $id_propal = (int) $this->db->getValue('element_element', 'fk_target', $where, 'rowid');
            }

            return $id_propal;
        }

        return 0;
    }

    // Getters array: 

    public function getContratSecteursArray()
    {
        $sql = $this->db->getRows('bimp_c_secteur', 'clef = "CTC" OR clef = "CTE"');
        $return = Array();

        foreach ($sql as $index => $i) {
            $return[$i->clef] = $i->valeur;
        }

        return $return;
    }

    public function getTmsArray()
    {
        $tms_in_contrat = 0;
        $tms_out_contrat = 0;

        $lines = BimpCache::getBimpObjectObjects('bimptechnique', 'BT_ficheInter_det', ['p.fk_contrat' => $this->id], 'id', 'asc', array('p' => array(
                        'table' => 'fichinter',
                        'on'    => 'p.rowid = a.fk_fichinter',
                        'alias' => 'p'
        )));
        foreach ($lines as $child) {
            $duration = $child->getData('duree');
            if ($child->getData('id_line_contrat') || $child->getData('type') == 5) {
                $tms_in_contrat += $duration;
            } else {
                $tms_out_contrat += $duration;
            }
        }

//        foreach ($this->getListFi() as $ficheInter) {
//            $childrenFiche = $ficheInter->getChildrenList("inters");
//            foreach ($childrenFiche as $id_child) {
//                $child = $ficheInter->getChildObject('inters', $id_child);
//                $duration = $child->getData('duree');
//                if ($child->getData('id_line_contrat') || $child->getData('type') == 5 || $ficheInter->getData(('new_fi')) < 1) {
//                    $tms_in_contrat += $duration;
//                } else {
//                    $tms_out_contrat += $duration;
//                }
//            }
//        }

        return (object) Array(
                    'in'  => $tms_in_contrat,
                    'out' => $tms_out_contrat
        );
    }

    public static function getSearchRenouvellementInputArray()
    {
        return [0 => "Aucun", 1 => "Proposition", 2 => "Tacite"];
    }

    public function getClientContactsArray()
    {
        $id_client = $this->getAddContactIdClient();
        return self::getSocieteContactsArray($id_client, false);
    }

    public function getTicketsSupportClientArray()
    {
        $tickets = [];

        $ticket = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_Ticket');
        $list = $ticket->getList(['id_client' => $this->getData('fk_soc')]);

        foreach ($list as $nb => $infos) {
            $ticket = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_Ticket', $infos['id']);
            $statut = $ticket->getData('status');

            $display_statut = "<strong class='" . BS_Ticket::$status_list[$statut]['classes'][0] . "' >";
            $display_statut .= BimpRender::renderIcon(BS_Ticket::$status_list[$statut]['icon']);
            $display_statut .= " " . BS_Ticket::$status_list[$statut]['label'] . "</strong>";

            $tickets[$ticket->id] = $ticket->getRef() . " (" . $display_statut . ") <br /><small style='margin-left:10px'>" . $ticket->getData('sujet') . '</small>';
        }

        return $tickets;
    }

    public function getTypeActionCommArray()
    {

        $actionComm = [];
        $acceptedCode = ['ATELIER', 'DEP_EXT', 'HOT', 'INTER', 'INTER_SG', 'AC_INT', 'LIV', 'RDV_INT', 'RDV_EXT', 'AC_RDV', 'TELE', 'VIS_CTR'];
        $list = $this->db->getRows('c_actioncomm', 'active = 1');
        foreach ($list as $nb => $stdClass) {
            if (in_array($stdClass->code, $acceptedCode)) {
                $actionComm[$stdClass->id] = $stdClass->libelle;
            }
        }
        return $actionComm;
    }

    public function getCommandesClientArray()
    {
        // ??? 
        $commandes = [];
        $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande');
        $list = $commande->getList(['fk_soc' => $this->getData('fk_soc')]);

        foreach ($list as $nb => $infos) {
            $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $infos['rowid']);
            $statut = $commande->getData('fk_statut');

            $display_statut = "<strong class='" . Bimp_Commande::$status_list[$statut]['classes'][0] . "' >";
            $display_statut .= BimpRender::renderIcon(Bimp_Commande::$status_list[$statut]['icon']);
            $display_statut .= " " . Bimp_Commande::$status_list[$statut]['label'] . "</strong>";

            $commandes[$commande->id] = $commande->getRef() . " (" . $display_statut . ") ";
        }
        return $commandes;
    }

    public function getClientRibsArray()
    {
        $id_client = (int) $this->getData('fk_soc_facturation');
        if (!$id_client) {
            $id_client = (int) $this->getData('fk_soc');
        }

        return BimpCache::getSocieteRibsArray($id_client, true);
    }

    // Affichages: 

    public function displayPeriode()
    {
        return self::$period[$this->getData('periodicity')];
    }

    public function displayMargeInter()
    {
        return BimpTools::displayMoneyValue($this->getMargeInter(), "EUR", true);
    }

    public function displayCommercialClient()
    {

        if ($this->isLoaded()) {
            $id_commercial = $this->db->getValue('societe_commerciaux', 'fk_user', 'fk_soc = ' . $this->getData('fk_soc'));

            $commercial = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_commercial);

            return $commercial->getLink();
        }
    }

    public function displayRenouvellement()
    {
        if (($this->isLoaded())) {
            switch ($this->getData("tacite")) {
                case self::CONTRAT_RENOUVELLEMENT_NON:
                    $return = "Aucun";
                    break;
                case self::CONTRAT_RENOUVELLEMENT_SUR_PROPOSITION:
                    $return = "Proposition";
                    break;
                case self::CONTRAT_RENOUVELLEMENT_1_FOIS:
                case self::CONTRAT_RENOUVELLEMENT_2_FOIS:
                case self::CONTRAT_RENOUVELLEMENT_3_FOIS:
                case self::CONTRAT_RENOUVELLEMENT_4_FOIS:
                case self::CONTRAT_RENOUVELLEMENT_5_FOIS:
                case self::CONTRAT_RENOUVELLEMENT_6_FOIS:
                    $return = "Tacite";
                    break;
            }
            return '<b>' . $return . '</b>';
        }
    }

    public function displayDateNextFacture()
    {
        if ($this->isLoaded()) {
            $fin = false;
            if ($echeancier = BimpCache::findBimpObjectInstance('bimpcontract', 'BContract_echeancier', ['id_contrat' => $this->id])) {
                $next_facture_date = $echeancier->getData('next_facture_date');
                if ($next_facture_date == 0) {
                    $fin = true;
                    $return = "<b class='important'>&Eacute;chéancier totalement facturé</b>";
                } else {
                    $return = $next_facture_date;
                }
            } else {
                $return = $this->getData('date_start');
            }
            if (!$fin) {
                $return = new DateTime($return);
                $return = $return->format('d / m / Y');
            }
            return $return;
        }
    }

    public function displayRef()
    {
        return $this->getData('ref') . ' - ' . $this->getName();
    }

    public function displayEndDate()
    {
        $fin = new DateTime($this->displayRealEndDate("Y-m-d"));
        if ($fin > 0)
            return $fin->format('d/m/Y');
    }

    public function displayRealEndDate($format = "d / m / Y")
    {
        $fin = null;
        $suup_all = false;

        if ($this->getData('end_date_reel') && $this->getData('anticipate_close_note')) {
            $suup_all = true;
            $fin = new DateTime($this->getData('end_date_reel'));
        }

        if (!$suup_all) {
            if (!$this->getData('date_end_renouvellement')) {
                if ($this->getData('end_date_reel')) {
                    $fin = new DateTime($this->getData('end_date_reel'));
                } elseif ($this->getData('end_date_contrat')) {
                    $fin = new DateTime($this->getData('end_date_contrat'));
                } else {
                    $fin = $this->getEndDate();
                }
            } else {
                $fin = new DateTime($this->getData('date_end_renouvellement'));
            }
        }

        if (is_object($fin))
            return $fin->format($format);
        else
            return '';
    }

    public function display_card()
    {
        $card = "";

        $card .= '<div class="col-md-4">';

        $card .= "<div class='card_interface'>";
        //$card .= "<img src='".DOL_URL_ROOT."/viewimage.php?modulepart=societe&entity=1&file=381566%2F%2Flogos%2Fthumbs%2F".$societe->dol_object->logo."&cache=0' alt=''><br />";
        $card .= "<div class='img' ><i class='fas fa-" . self::$objet_contrat[$this->getData('objet_contrat')]['icon'] . "' ></i></div>";

        $card .= "<h1>" . $this->getRef() . "</h1>";

        if ($this->getData('label') != "")
            $card .= "<h1>" . $this->getData('label') . "</h1>";
        else {
            $card .= "<h1>" . self::$objet_contrat[$this->getData('objet_contrat')]['label'] . "</h1>";
        }
        $card .= '<h2>Durée du contrat : ' . $this->getData('duree_mois') . ' mois</h2>';
        if ($this->getData('periodicity')) {
            $card .= '<h2>Facturation : ' . self::$period[$this->getData('periodicity')] . '</h2>';
        }

        $card .= '<div>';
        if ($this->canClientViewDetail())
            $card .= '<a tool="Voir le contrat" flow="down" class="button" href="' . self::getPublicBaseUrl() . 'tab=contrats&content=card&id_contrat=' . $this->id . '"><i class="fas fa-eye"></i></a>';
        if ((int) BimpCore::getConf('use_tickets', null, 'bimpsupport') && $this->isValide()) {
            $card .= '<span tool="Nouveau ticket support" flow="down" class="button" onclick="' . $this->getNewTicketSupportOnClick() . '"><i class="fas fa-plus"></i></span>';
        }
        $card .= '</div>';

        //$card .= '<a tool="Statistiques du contrat" flow="down" class="button" href="https://instagram.com/chynodeluxe"><i class="fas fa-leaf"></i></a>';
        $card .= '</div></div>';

        return $card;
    }

    public function displayContratSource()
    {
        $obj = $this->getContratSource();
        if ($obj) {
            return $obj->getNomUrl();
        }

        return 'Ce contrat est le contrat initial';
    }

    public function displayMessagesFormActivate()
    {
        $msgs = [];

        $date = new DateTime($this->getData('date_start'));
        $now = new DateTime();

        $diff = $date->diff($now);

        if ($diff->invert) {
            $msgs[] = Array(
                'type'    => 'danger',
                'content' => "Le contrat dûment signé doit être obligatoirement présent dans les fichiers pour une activation définitive

<br/><br/>Si ce n'est pas le cas, le contrat sera activé provisoirement pour une période de 15 jours
<br/>Si le contrat signé ne nous ait pas parvenu durant cette période, l'activation sera suspendue"
            );
        }


        return $msgs;
    }

    public function displayCommercial()
    {

        BimpTools::loadDolClass('user');
        $commercial = new User($this->db->db);
        $commercial->fetch($this->getData('fk_commercial_suivi'));

        return $commercial->getNomUrl(1);
    }

    public function display_reste_a_payer()
    {
        return "<b>" . $this->reste_a_payer() . "€</b>";
    }

    public function displayContratChild()
    {
        $obj = $this->getContratChild();
        if (BimpObject::objectLoaded($obj)) {
            return $obj->getLink();
        }

        return 'Ce contrat n\'a pas de contrat de remplacement';
    }

    public function displayNumberRenouvellement()
    {
        $html = "";
        if ($this->getData('current_renouvellement') > 0) {
            if ($this->getData('tacite') != 12 && $this->getData('tacite') != 0) {
                
            } else {
                $html .= "<strong>Pas de renouvellement</strong>";
            }
        }
        return $html;
    }

    // Rendus HTML: 

    public function renderHeaderExtraLeft()
    {
        $html = '';

        if ($this->isLoaded()) {
            $status = $this->getData('statut');
            $now = date('Y-m-d');

            if ($this->getData('ref_ext')) {
                $html .= '<div style="margin-bottom: 8px">';
                $html .= '<span class="warning" style="font-size: 15px">Annule et remplace ' . $this->getLabel('the') . ' "' . $this->getData('ref_ext') . '"</span>';
                $html .= '</div>';
            }

            // Date création : 
            $user_create = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $this->getData('fk_user_author'));
            $dt_create = new DateTime($this->getData('datec'));

            $html .= '<div class="object_header_infos">';
            $html .= 'Créé le <strong >' . $dt_create->format('d / m / Y') . '</strong>';
            if (BimpObject::objectLoaded($user_create)) {
                $html .= ' par ' . $user_create->getLink();
            }
            $html .= '</div>';

            // Date Clôture: 
            if ($this->getData('fk_user_cloture')) {
                $dateCloture = new DateTime($this->getData('date_cloture'));
                $userCloture = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $this->getData('fk_user_cloture'));

                $html .= '<div class="object_header_infos">';
                $html .= '<br />Clos le <strong >' . $dateCloture->format('d / m / Y') . '</strong>';
                if (BimpObject::objectLoaded($userCloture)) {
                    $html .= ' par ' . $userCloture->getLink();
                }
                $html .= '</div>';
            }

            // Infos: 
            $client = $this->getChildObject('client');
            $html .= '<div style="margin-top: 10px">';
            $html .= '<strong>Client: </strong>';
            $html .= $client->getLink();
            $html .= '</div>';

            // Alertes: 

            if ($this->getData('end_date_reel') && $this->getData('anticipate_close_note')) {
                $dateAnticipateClose = new DateTime($this->getData('end_date_reel'));

                $html .= "<strong class='danger' ><h3>Date de clôture anticipée pour ce contrat: <i>" . $dateAnticipateClose->format('d/m/Y') . "</i>"
                        . " <span class='rowButton bs-popover' " . BimpRender::renderPopoverData($this->getData('anticipate_close_note'), 'right', true) . "> " . BimpRender::renderIcon('fas fa-info') . "</span></h3></strong>";
            }

            if (in_array($status, array(self::CONTRAT_STATUS_VALIDE, self::CONTRAT_STATUS_ACTIVER))) {
                $jours_restant = $this->getJourRestantReel();

                $html .= '<div style="margin-top: 10px">';
                if ($jours_restant >= 0) {
                    $html .= '<span class="info">Ce contrat expire dans ' . $jours_restant . ' jours</span>';
                } else {
                    $html .= '<span class="important">Ce contrat est expiré depuis ' . abs($jours_restant) . ' jour(s), merci de le clore</span>';
                }
                $html .= '</div>';

                if ($status == self::CONTRAT_STATUS_ACTIVER) {
                    if ($this->getData('date_start') > $now) {
                        $msg = '<b>Ce contrat a été activé par avance mais sa date de prise d\'effet n\'est pas encore atteinte</b><br/>';
                        $msg .= 'Date visible ci-dessous dans "Information sur la durée de validité du contrat"';
                        $html .= BimpRender::renderAlerts($msg, 'warning', false);
                    }
                }
//
                if (!$this->getData('duree_mois') || !$this->getData('date_start')) {
                    $val = $this->db->getMax('contratdet', 'date_fin_validite', 'fk_contrat = ' . $this->id);
                    $date_fin = new DateTime($val);
                    $html .= BimpRender::renderAlerts('Ceci est un ancien contrat dont la date d\'expiration est le : <b> ' . $date_fin->format('d / m / Y') . ' </b>', 'info');
                }

                if (count($this->getChildrenList('avenant', ['statut' => 5])) > 0) {
                    $html .= BimpRender::renderAlerts('<b>ATTENTION : il y a un avenant en activation provisoire sur ce contrat</b>', 'warning');
                }
            }

            if ($this->getdata('statut') == self::CONTRAT_STATUS_ACTIVER) {
                $idAvenantActif = $this->getIdAvenantActif();
                if ($idAvenantActif > 0) {
                    $msg = BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft');
                    $msg .= '<b>Ce contrat fait l\'objet d\'avenant(s) actif(s) à prendre en compte pour toute mise à jour contractuelle ou intervention technique</b>';
                    $html .= BimpRender::renderAlerts($msg, 'warning');
                }
            }
        }
        return $html;
    }

    public function renderHeaderExtraRight($no_div = false)
    {

        $html = '';

        if ((int) $this->getData('id_signature')) {
            $signature = $this->getChildObject('signature');

            if (BimpObject::objectLoaded($signature)) {
                $url = $signature->getUrl();

                if ($url) {
                    $html .= BimpRender::renderButton(array(
                                'classes'     => array('btn', 'btn-default'),
                                'label'       => 'Signature',
                                'icon_before' => 'fas_signature',
                                'icon_after'  => 'fas_external-link-alt',
                                'attr'        => array(
                                    'href'   => $url,
                                    'target' => '_blank',
                                )
                                    ), "a");
                } else
                    $html .= BimpRender::renderAlerts('Signature en attente de création', 'danger', false);
            }
        }

        return $html;
    }

    public function renderInitialRenouvellement()
    {
        return self::$renouvellement[$this->getInitialRenouvellement()];
    }

    public function renderFi()
    {
        $html = BimpRender::renderPanel('Stats des Fi ' . $this->getLabel('of_the'), $this->renderThisStatsFi(), '', array(
                    'icon'     => 'fas_file',
                    'type'     => 'secondary',
                    'foldable' => true
        ));
//        $objects = BimpCache::getBimpObjectInstance('bimptechnique', 'BT_ficheInter');
//        $html .= $objects->renderList('contrat');

        return $html;
    }

    public function renderAvenant()
    {

        $html = "";
        $av = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_avenant');
        $list = $av->getList(['id_contrat' => $this->id, 'statut' => 0]);
        $errors = [];
        $buttons = [];

        if (count($list) > 0) {
            $html .= $av->renderList('avenant_brouillon');
        } else {
            $buttons[] = array(
                'label'   => 'Ajouter un avenant à ce contrat',
                'icon'    => 'fas_plus',
                'onclick' => $this->getJsActionOnclick('avenant', array(), array())
            );
            $html .= BimpRender::renderButtonsGroup($buttons, $params);
        }


        return $html;
    }

    public function renderTechsInput()
    {
        global $user, $langs;
        $html = '';
        $values = "";
        $input = BimpInput::renderInput('search_user', 'techs_add_value');
        $content = BimpInput::renderMultipleValuesInput($this, 'techs', $input, $values);
        $html .= BimpInput::renderInputContainer('techs', '', $content, '', 0, 1, '', array('values_field' => 'techs'));

        return $html;
    }

    public function renderThisStatsFi($display = true, $in_contrat = true)
    {
        $html = "";

//        $fis = $this->getListFi();
        $fis = BimpCache::getBimpObjectList('bimptechnique', 'BT_ficheInter', ['fk_contrat' => $this->id]);
        $in_out_tms = $this->getTmsArray();
        $ficheInter = BimpCache::getBimpObjectInstance('bimptechnique', 'BT_ficheInter');
        $total_fis = 0;
        $total_tms = $in_out_tms->in;
        $total_tms_not_contrat = $in_out_tms->out;
        $total_fis = $this->getTotalFi($total_tms);
        $previsionelle = 0;

        if ($this->getJourTotal() > 0 && $this->getJourTotal() > $this->getJourRestant())
            $previsionelle = $this->getMargePrevisionnel($total_fis);

        $marge = $this->getMargeInter();
        $marge_previsionelle = ($this->getTotalContrat() - $previsionelle);

        $class = 'warning';
        $icone = 'arrow-right';

        if ($marge > 0) {
            $class = 'success';
            $icone = 'arrow-up';
        } elseif ($marge < 0) {
            $class = 'danger';
            $icone = 'arrow-down';
        }
        if ($marge_previsionelle > 0) {
            $class2 = 'success';
            $icone2 = 'arrow-up';
        } elseif ($marge_previsionelle < 0) {
            $class2 = 'danger';
            $icone2 = 'arrow-down';
        }

        $html .= "<strong>";
        if ($in_contrat) {

            $html .= "Nombre de FI: " . count($fis) . '<br />';
            $html .= "Nombre d'heures vendue : " . $ficheInter->timestamp_to_time($this->getDurreeVendu()) . '<br/>';
//            $html .= "Nombre d'heures de délégation vendue : ".print_r($this->getTotalHeureDelegation(true),true).'<br/>';
            $html .= "Nombre d'heures FI dans le contrat : " . $ficheInter->timestamp_to_time($total_tms) . '<br />';
            $html .= "Nombre d'heures FI hors du contrat : " . $ficheInter->timestamp_to_time($total_tms_not_contrat) . ' (non pris en compte)<br />';
            $html .= "Nombre d'heures de délégations restante : " . $this->getHeuresRestantesDelegation() . '<br/>';
            $html .= "Coût technique: " . price($total_fis) . " € (" . BimpCore::getConf('cout_horaire_technicien', null, 'bimptechnique') . " €/h * " . $ficheInter->timestamp_to_time($total_tms) . ")<br />";
            $html .= "Coût prévisionel: " . price($previsionelle) . " €<br />";
            $html .= "Vendu: " . "<strong class='warning'>" . price($this->getTotalContrat()) . "€</strong><br />";
            $html .= "Marge: " . "<strong class='$class'>" . BimpRender::renderIcon($icone) . " " . price($marge) . "€</strong><br />";
            $html .= "Marge Prévisionelle: " . "<strong class='$class2'>" . BimpRender::renderIcon($icone2) . " " . price($marge_previsionelle) . "€</strong><br />";
        } else {
            $html .= "Contrat: " . "<strong class='$class'>" . BimpRender::renderIcon($icone) . " " . price($marge) . "€</strong><br />";
        }
        $html .= '</strong>';

        if ($display)
            return $html;
        else
            return $marge;
    }

    public function renderFilesTable()
    {
        $html = '';

        if ($this->isLoaded()) {
            global $conf;
            $ref = $this->getRef();

            $dir = $this->getFilesDir();

            if (!function_exists('dol_dir_list')) {
                require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
            }

            $files_list = dol_dir_list($dir, 'files', 0, '', '(\.meta|_preview.*.*\.png)$', 'date', SORT_DESC);
            $html .= '<table class="bimp_list_table">';

            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th>Fichier</th>';
            $html .= '<th>Taille</th>';
            $html .= '<th>Date</th>';
            $html .= '<th></th>';
            $html .= '</tr>';
            $html .= '</thead>';

            $html .= '<tbody>';

            if (count($files_list)) {
                $url = DOL_URL_ROOT . '/document.php?modulepart=' . static::$dol_module . '&file=' . $ref . urlencode('/');
                foreach ($files_list as $file) {
                    $html .= '<tr>';

                    $html .= '<td><a class="btn btn-default" href="' . $url . $file['name'] . '" target="_blank">';
                    $html .= '<i class="' . BimpRender::renderIconClass(BimpTools::getFileIcon($file['name'])) . ' iconLeft"></i>';
                    $html .= $file['name'] . '</a></td>';

                    $html .= '<td>';
                    if (isset($file['size']) && $file['size']) {
                        $html .= $file['size'];
                    } else {
                        $html .= 'taille inconnue';
                    }
                    $html .= '</td>';

                    $html .= '<td>';
                    if ((int) $file['date']) {
                        $html .= date('d / m / Y H:i:s', $file['date']);
                    }
                    $html .= '</td>';

                    $html .= '<td class="buttons">';
                    $html .= BimpRender::renderRowButton('Aperçu', 'search', '', 'documentpreview', array(
                                'attr' => array(
                                    'target' => '_blank',
                                    'mime'   => dol_mimetype($file['name'], '', 0),
                                    'href'   => $url . $file['name'] . '&attachment=0'
                                )
                                    ), 'a');

                    $onclick = $this->getJsActionOnclick('deleteFile', array('file' => htmlentities($file['fullname'])), array(
                        'confirm_msg'      => 'Veuillez confirmer la suppression de ce fichier',
                        'success_callback' => 'function() {bimp_reloadPage();}'
                    ));
                    $html .= BimpRender::renderRowButton('Supprimer', 'trash', $onclick);
                    $html .= '</td>';
                    $html .= '</tr>';
                }
            } else {
                $html .= '<tr>';
                $html .= '<td colspan="4">';
                $html .= BimpRender::renderAlerts('Aucun fichier', 'info', false);
                $html .= '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table>';

            $html = BimpRender::renderPanel('Documents PDF ' . $this->getLabel('of_the'), $html, '', array(
                        'icon'     => 'fas_file',
                        'type'     => 'secondary',
                        'foldable' => true
            ));
        }

        return $html;
    }

    public function renderEcheancier()
    {
        $html = '';
        $errors = array();
        $warnings = array();

        if ($this->isLoaded()) {
            $echeancier = $this->getEcheancier($errors);

            if (!count($errors)) {
                if ((int) $this->getData('statut') != self::CONTRAT_STATUS_ACTIVER) {
                    $errors[] = 'Ce contrat n\'est pas activé';
                }
                if (!$this->getData('date_start') || !$this->getData('periodicity') || !$this->getData('duree_mois')) {
                    $warnings[] = 'Le contrat a été facturé à partir d\'une commande, il ne comporte donc pas d\'échéancier';
                }

                $html .= $echeancier->displayEcheancier();
            }

            if (count($errors)) {
                $html .= BimpRender::renderAlerts($errors);
            }

            if (count($warnings)) {
                $html .= BimpRender::renderAlerts($warnings, 'warning');
            }

            if ($this->isActionAllowed('createEcheancier')) {
                $onclick = $this->getJsActionOnclick('createEcheancier');
                $html .= '<span class="btn btn-default" onclick="' . $onclick . '">';
                $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Créer l\'échéancier';
                $html .= '</span>';
            }
        }

        return $html;
    }

    public function renderContacts()
    {
        $html = '';

        $html .= '<table class="bimp_list_table">';

        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Nature</th>';
        $html .= '<th>Tiers</th>';
        $html .= '<th>Utilisateur / Contact</th>';
        $html .= '<th>Type de contact</th>';
        $html .= '<th></th>';
        $html .= '</tr>';
        $html .= '</thead>';

        $list_id = $this->object_name . ((int) $this->id ? '_' . $this->id : '') . '_contacts_list';
        $html .= '<tbody id="' . $list_id . '">';
        $html .= $this->renderContactsList();

        $html .= '</tbody>';

        $html .= '</table>';

        return BimpRender::renderPanel('Liste des contacts', $html, '', array(
                    'type'           => 'secondary',
                    'icon'           => 'user-circle',
                    'header_buttons' => array(
                        array(
                            'label'       => 'Ajouter un contact',
                            'icon_before' => 'plus-circle',
                            'classes'     => array('btn', 'btn-default'),
                            'attr'        => array(
                                'onclick' => $this->getJsActionOnclick('addContact', array('id_client' => (int) $this->getData('fk_soc')), array(
                                    'form_name'        => 'contact',
                                    'success_callback' => 'function(result) {if (result.contact_list_html) {$(\'#' . $list_id . '\').html(result.contact_list_html);}}'
                                ))
                            )
                        )
                    )
        ));
    }

    public function renderHeaderStatusExtra()
    {
        $extra = '';
        $notes = $this->getNotes();
        $nb = count($notes);

        if ($this->getData('statut') == self::CONTRAT_STATUS_ACTIVER_TMP) {
            $date = new DateTime($this->getData('date_start_provisoire'));
            $extra .= " depuis le " . $date->format('d/m/Y');
            $end = new DateTime($this->getData('date_start_provisoire'));
            $end->add(New DateInterval("P14D"));
            $extra .= "<br />Si le contrat signé par le client ne nous parvient pas avant le <b class='bs-popover' " . BimpRender::renderPopoverData($date->format('d/m/Y') . " + 14 Jours", "top") . " >" . $end->format('d/m/Y') . "</b> l’activation provisoire de ce contrat sera suspendue.";
        }

        if ($nb > 0)
            $extra .= '<br/><span class="warning"><span class="badge badge-warning">' . $nb . '</span> Note' . ($nb > 1 ? 's' : '') . '</span>';

        if (!is_null($this->getData('date_contrat'))) {
            $date = new DateTime($this->getData('date_contrat'));
            $extra .= '<br/><span class="important">' . BimpRender::renderIcon('fas_signature', 'iconLeft') . 'Contrat marqué comme signé</span> depuis le ' . $date->format('d/m/Y');
        }

        if ($this->isFactAuto()) {
            $extra .= "<br /><span class='info' >Facturation automatique activée</strong></span>";
        }

        if ($this->getData('current_renouvellement') > 0) {
            $arrayTacite = Array(
                self::CONTRAT_RENOUVELLEMENT_1_FOIS => "1",
                self::CONTRAT_RENOUVELLEMENT_2_FOIS => "2",
                self::CONTRAT_RENOUVELLEMENT_3_FOIS => "3",
                self::CONTRAT_RENOUVELLEMENT_4_FOIS => "4",
                self::CONTRAT_RENOUVELLEMENT_5_FOIS => "5",
                self::CONTRAT_RENOUVELLEMENT_6_FOIS => "6",
            );
            $extra .= "<br /><strong>Renouvellement N°</strong><strong>" . $this->getData('current_renouvellement') . "/" . $arrayTacite[$this->getInitialRenouvellement()] . "</strong>";
        }

        return $extra;
    }

    // Traitements: 

    public function createFromClient($data)
    {
        global $user;

        $serials = explode("\n", $data->note);

        $nombreServices = count($data->services);
        $tmpCountServices = 0;
        $mostHightDuring = 0;

        foreach ($data->services as $nb => $infos) {
            if ($infos['value'] == 'Non') {
                $tmpCountServices++;
            } else {
                $mostHightDuring = ($infos['value'] > $mostHightDuring) ? $infos['value'] : $mostHightDuring;
            }
        }
        $missService = ($tmpCountServices == $nombreServices) ? true : false;

        if ($missService)
            return "Il doit y avoir au moin un service";
        else {
            $date = new DateTime();
            $date->setTimestamp($data->dateDeb);
            $contrat = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat');

            // Data du contrat
            $contrat->set('fk_soc', $data->socid);
            $contrat->set('date_contrat', null);
            $contrat->set('date_start', $date->format('Y-m-d'));
            $contrat->set('objet_contrat', 'CMA');
            $contrat->set('duree_mois', $mostHightDuring);
            $contrat->set('fk_commercial_suivi', $user->id);
            $contrat->set('fk_commercial_signature', $user->id);
            $contrat->set('gti', 16);
            $contrat->set('moderegle', 60);
            $contrat->set('tacite', 12);
            $contrat->set('periodicity', 12);
            $contrat->set('note_public', '');
            $contrat->set('note_private', '');
            $contrat->set('ref_ext', '');
            $contrat->set('ref_customer', '');
            $contrat->set('label', '');
            $contrat->set('relance_renouvellement', 1);
            $contrat->set('syntec', 0);

             $errors = $contrat->create();

            if (!count($errors)) {
                foreach ($data->services as $nb => $infos) {
                    if ($infos['value'] != 'Non') {
                        $service = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $infos['id']);
                        $end_date = new DateTime($date->format('Y-m-d'));
                        $end_date->add(new DateInterval("P" . $mostHightDuring . "M"));
                        $idLine = $contrat->dol_object->addLine(
                                $service->getData('description'), $service->getData('price'), 1, 20, 0, 0, $infos['id'], 0, $date->format('Y-m-d'), $end_date->format('Y-m-d'), 'HT', 0.0, 0, null, 0, 0, null, $nb);
                    }
                    $line = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contratLine', $idLine);
                    $line->updateField('serials', json_encode($serials));
                }
                $contrat->addLog('Contrat créé avec BimpContratAuto');
                return $contrat->id;
            }
        }
    }

    public function createFromPropal($propal, $data)
    {
        global $user;
        $errors = [];
        //echo '<pre>';
        $propalIsRenouvellement = (!$propal->isNotRenouvellementContrat()) ? true : false;
        $elementElement = getElementElement("contrat", "propal", null, $propal->id);
        if ($propalIsRenouvellement) {

            $serials = [];
            $source = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat', $elementElement[0]['s']);

            $objet_contrat = $source->getData('objet_contrat');
            $fk_soc = $source->getData('fk_soc');
            $commercial_signature = $source->getData('fk_commercial_signature');
            $commercial_suivi = $source->getData('fk_commercial_suivi');
            $periodicity = $source->getData('periodicity');
            $gti = $source->getData('gti');
            $duree_mois = $source->getData('duree_mois');
            $tacite = 12;
            $mode_reglement = $source->getData('moderegl');
            $cond_reglement = $source->getData('condregl');
            $note_public = $source->getData('note_public') . "\n" . $data['note_public'];
            $note_private = $source->getData('note_private') . "\n" . $data['note_private'];
            $ref_ext = $source->getData('ref_ext');
            $secteur = $source->getData('secteur');
            $ref_customer = $source->getData('ref_customer');

            $lines = $propal->getChildrenList("lines");
            $lines_of_contrat = $source->getChildrenList("lines");

            foreach ($lines as $id_child) {
                $child = $propal->getChildObject('lines', $id_child);
            }

            //echo print_r($lines,1);
        } else {
            $fk_soc = $data['fk_soc'];
            $objet_contrat = $data['objet_contrat'];
            $commercial_signature = $data['commercial_signature'];
            $commercial_suivi = $data['commercial_suivi'];
            $periodicity = $data['periodicity'];
            $gti = $data['gti'];
            $duree_mois = $data['duree_mois'];
            $tacite = $data['re_new'];
            $mode_reglement = $data['fk_mode_reglement'];
            $cond_reglement = $data['fk_cond_reglement'];
            $note_public = $data['note_public'];
            $note_private = $data['note_private'];
            $ref_ext = $data['ref_ext'];
            $ref_customer = $data['ref_customer'];
            $secteur = $data['secteur_contrat'];
        }

        $commercial_for_entrepot = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $data['commercial_suivi']);

        $new_contrat = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat');
        if ((int) BimpCore::getConf('USE_ENTREPOT'))
            $new_contrat->set('entrepot', ($commercial_for_entrepot->getData('defaultentrepot')) ? $commercial_for_entrepot->getData('defaultentrepot') : $propal->getData('entrepot'));
        $new_contrat->set('fk_soc', $fk_soc);
        $new_contrat->set('date_contrat', null);
        $new_contrat->set('date_start', $data['valid_start']);
        $new_contrat->set('objet_contrat', $objet_contrat);
        $new_contrat->set('fk_commercial_signature', $commercial_signature);
        $new_contrat->set('fk_commercial_suivi', $commercial_suivi);
        $new_contrat->set('periodicity', $periodicity);
        $new_contrat->set('gti', $gti);
        $new_contrat->set('duree_mois', $duree_mois);
        $new_contrat->set('tacite', $tacite);
        $new_contrat->set('moderegl', $mode_reglement);
        $new_contrat->set('condregl', $cond_reglement);
        $new_contrat->set('note_public', $note_public);
        $new_contrat->set('note_private', $note_private);
        $new_contrat->set('ref_ext', $ref_ext);
        $new_contrat->set('ref_customer', $ref_customer);
        $new_contrat->set('label', $data['label']);
        $new_contrat->set('relance_renouvellement', 1);
        $new_contrat->set('secteur', $secteur);

        if ($new_contrat->field_exists('expertise') && $propal->field_exists('expertise')) {
            $new_contrat->set('expertise', $propal->getData('expertise'));
        }

        if ($propalIsRenouvellement)
            $new_contrat->set('syntec', BimpCore::getConf('current_indice_syntec'));
        if (isset($data['use_syntec']) && $data['use_syntec'] == 1) {
            $new_contrat->set('syntec', BimpCore::getConf('current_indice_syntec'));
        }
        if (!count($errors)) {
            $errors = $new_contrat->create();
        }
        if (!count($errors)) {
            foreach ($propal->dol_object->lines as $line) {
                $produit = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $line->fk_product);
                if ($produit->getData('fk_product_type') == 1 || !BimpCore::getConf('just_code_service', null, 'bimpcontract') || $line->pa_ht == 0) {
                    $description = ($line->desc && $line->desc != '<br>') ? $line->desc : $line->libelle;
                    $end_date = new DateTime($data['valid_start']);
                    $end_date->add(new DateInterval("P" . $duree_mois . "M"));
                    $new_contrat->dol_object->pa_ht = $line->pa_ht; // BUG DéBILE DOLIBARR
                    $new_contrat->dol_object->addLine($description, $line->subprice, $line->qty, $line->tva_tx, 0, 0, $line->fk_product, $line->remise_percent, $data['valid_start'], $end_date->format('Y-m-d'), 'HT', 0.0, 0, null, (float) $line->pa_ht, 0, null, $line->rang);
                }
            }

            $contacts_suivi = $new_contrat->dol_object->liste_contact(-1, 'external', 0, 'BILLING2');
            if (count($contacts_suivi) == 0) {
                // Get id of the default contact
                global $db;
                $id_client = $data['fk_soc'];
                if ($id_client > 0) {

                    $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $id_client);
                    $contact_default = $soc->getData('contact_default');

                    if (!count($errors) && $contact_default > 0) {
                        if ($new_contrat->dol_object->add_contact($contact_default, 'BILLING2', 'external') <= 0)
                            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($new_contrat->dol_object), 'Echec de l\'ajout du contact');
                    }
                }
            }
            $new_contrat->copyContactsFromOrigin($propal);
            addElementElement('propal', 'contrat', $propal->id, $new_contrat->id);
            $elementListPropal = getElementElement('propal', 'facture', $propal->id);
            foreach ($elementListPropal as $element => $type) {
                $fact = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $type['d']);
                if ($fact->getData('type') == 3) {
                    addElementElement('contrat', 'facture', $new_contrat->id, $type['d']);
                }
            }
            return $new_contrat->id;
        } else {
            return -1;
        }
    }

    public function tryToValidate(&$errors = array(), &$infos = array(), &$successes = array())
    {
        global $user;

        if (BimpCore::isModuleActive('bimpvalidation')) {
            if (!BimpValidation::tryToValidate($this, $errors, $infos, $successes)) {
                if (!count($errors)) {
                    $errors[] = 'Vous ne pouvez pas valider complètement ce contrat';
                }
            }
        } elseif (BimpCore::isModuleActive('bimpvalidateorder')) {
            $validComm = BimpCache::getBimpObjectInstance('bimpvalidateorder', 'ValidComm');
            $validComm->tryToValidate($this, $user, $errors, $successes);
        }


        return $errors;
    }

    public function addLog($text)
    {
        $errors = array();

        if ($this->isLoaded($errors) && $this->field_exists('logs')) {
            $logs = (string) $this->getData('logs');
            if ($logs) {
                $logs .= '<br/>';
            }
            global $user, $langs;
            $logs .= ' - <strong> Le ' . date('d / m / Y à H:i') . '</strong> par ' . $user->getFullName($langs) . ': ' . $text;
            $errors = $this->updateField('logs', $logs, null, true);
        }

        return $errors;
    }

    public function createEcheancier(&$warnings = array())
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            $date = new DateTime($this->getData('date_start'));
            $instance = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_echeancier');
            $instance->set('id_contrat', $this->id);
            $instance->set('next_facture_date', $date->format('Y-m-d H:i:s'));
            $instance->set('next_facture_amount', $this->reste_a_payer());
            $instance->set('validate', 1);
            $instance->set('client', $this->getData('fk_soc'));
            $instance->set('commercial', $this->getData('fk_commercial_suivi'));
            $instance->set('statut', 1);
            $errors = $instance->create($warnings, true);
        }

        return $errors;
    }

    public function closeFromCron($reason = "Contrat clos automatiquement")
    {
        $echeancier = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_echeancier');
        global $user;
        if ($this->dol_object->closeAll($user) >= 1) {
            $this->updateField('statut', self::CONTRAT_STATUS_CLOS);
            $this->updateField('date_cloture', date('Y-m-d H:i:s'));
            $this->updateField('fk_user_cloture', 1);
            $this->addLog($reason);
            if ($echeancier->fetchBy('id_contrat', $this->id)) {
                $echeancier->updateField('statut', 0);
            }
        }
    }

    public function closeContratChildWhenActivateRenewManual($fromCron = false): bool
    {

        global $user;

        $beforeContrat = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat');

        if ($beforeContrat->find(Array('next_contrat' => $this->id))) {

            if ($beforeContrat->isLoaded()) {

                if (!in_array($this->getData('statut'), Array(self::CONTRAT_STATUS_ACTIVER, self::CONTRAT_STATUS_ACTIVER_SUP, self::CONTRAT_STATUS_ACTIVER_TMP)) && $fromCron) {
                    return 0;
                }

                $echeancier = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_echeancier');

                if ($echeancier->find(Array('id_contrat' => $beforeContrat->id))) {

                    $dateFinNow = new DateTime();
                    $dateFinBefore = $this->displayRealEndDate('Y-m-d');

                    if ($dateFinBefore > $dateFinNow) {
                        if ($beforeContrat->dol_object->closeAll($user) >= 1) {

                            $beforeContrat->updateField('statut', self::CONTRAT_STATUS_CLOS);
                            $beforeContrat->updateField('date_cloture', date('Y-m-d H:i:s'));
                            $beforeContrat->updateField('fk_user_cloture', $user->id);
                            $echeancier->updateField('statut', 0);
                            $echeancier->updateField('validate', 0);

                            $beforeContrat->addLog('Clos car contrat de renouvellement ' . $this->getRef());

                            if (!$fromCron)
                                $this->addLog($beforeContrat->getRef() . ' clos suite à l\'activation de ce contrat');
                            else
                                $this->addLog($beforeContrat->getRef() . ' clos automatiquement car ce contrat en est le renouvellement');

                            return 1;
                        }
                    }
                }
            }
        }

        return 0;
    }

    public function checkContacts()
    {
        $errors = array();

        if (in_array($this->object_name, array('Bimp_Propal', 'Bimp_Commande', 'Bimp_Facture'))) {
            global $user;
            $client = $this->getChildObject('client');
            if (BimpObject::objectLoaded($client)) {
                // Vérif commercial suivi: 
                $tabContact = $this->dol_object->getIdContact('internal', 'SALESREPFOLL');
                if (count($tabContact) < 1) {
                    $ok = false;
                    $tabComm = $client->dol_object->getSalesRepresentatives($user);

                    // Il y a un commercial pour ce client
                    if (count($tabComm) > 0) {
                        $this->dol_object->add_contact($tabComm[0]['id'], 'SALESREPFOLL', 'internal');
                        $ok = true;

                        // Il y a un commercial définit par défaut (bimpcore)
                    } elseif ((int) BimpCore::getConf('user_as_default_commercial', null, 'bimpcommercial')) {
                        $this->dol_object->add_contact($user->id, 'SALESREPFOLL', 'internal');
                        $ok = true;
                        // L'objet est une facture et elle a une facture d'origine
                    } elseif ($this->object_name === 'Bimp_Facture' && (int) $this->getData('fk_facture_source')) {
                        $fac_src = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $this->getData('fk_facture_source'));
                        if (BimpObject::objectLoaded($fac_src)) {
                            $contacts = $fac_src->dol_object->getIdContact('internal', 'SALESREPFOLL');
                            if (count($contacts) > 0) {
                                $this->dol_object->add_contact($contacts[0]['id'], 'SALESREPFOLL', 'internal');
                                $ok = true;
                            }
                        }
                    }

                    if (!$ok) {
                        $errors[] = 'Pas de Commercial Suivi';
                    }
                }

                // Vérif contact signataire: 
                $tabContact = $this->dol_object->getIdContact('internal', 'SALESREPSIGN');
                if (count($tabContact) < 1) {
                    $this->dol_object->add_contact($user->id, 'SALESREPSIGN', 'internal');
                }
            }
        }
        return $errors;
    }

    public function turnOffEcheancier()
    {
        $echeancier = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_echeancier');
        if ($echeancier->find(['id_contrat' => $this->id])) {
            $echeancier->updateField('statut', 0);
        }
    }

    public function manuel()
    {

        $errors = Array();
        $warnings = Array();

        $callback = "";
        $this->actionUpdateSyntec();
        $for_date_end = new DateTime($this->displayRealEndDate("Y-m-d"));
        $new_contrat = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat');
        if ((int) BimpCore::getConf('USE_ENTREPOT'))
            $new_contrat->set('entrepot', $this->getData('entrepot'));
        $new_contrat->set('fk_soc', $this->getData('fk_soc'));
        $new_contrat->set('date_contrat', null);
        $new_contrat->set('date_start', $for_date_end->add(New DateInterval('P1D'))->format('Y-m-d'));
        $new_contrat->set('objet_contrat', $this->getData('objet_contrat'));
        $new_contrat->set('fk_commercial_signature', $this->getData('fk_commercial_signature'));
        $new_contrat->set('fk_commercial_suivi', $this->getdata('fk_commercial_suivi'));
        $new_contrat->set('periodicity', $this->getData('periodicity'));
        $new_contrat->set('gti', $this->getData('gti'));
        $new_contrat->set('duree_mois', $this->getData('duree_mois'));
        $new_contrat->set('tacite', $this->getInitialRenouvellement());
//        $new_contrat->set('initial_renouvellement', $this->getData('initial_renouvellement'));
        $new_contrat->set('moderegl', $this->getData('moderegl'));
        $new_contrat->set('note_public', $this->getData('note_public'));
        $new_contrat->set('note_private', $this->getData('note_private'));
        $new_contrat->set('ref_ext', $this->getData('ref_ext'));
        $new_contrat->set('ref_customer', $this->getData('ref_customer'));
        if (/* $this->getData('syntec') > 0 && */ BimpTools::getValue('use_syntec')) {
            $new_contrat->set('syntec', BimpCore::getConf('current_indice_syntec'));
        } else {
            $new_contrat->set('syntec', 0);
        }

        $addLabel = "";
        if ($this->getData('label')) {
            $addLabel = " - " . $this->getData('label');
        }

        $new_contrat->set('label', "Renouvellement contrat: " . $this->getRef() . $addLabel);
        $new_contrat->set('relance_renouvellement', 1);
        $new_contrat->set('secteur', $this->getData('secteur'));

        $errors = $new_contrat->create($warnings);

        if (!count($errors)) {

            $callback = "window.open('" . DOL_URL_ROOT . "/bimpcontract/?fc=contrat&id=" . $new_contrat->id . "')";
            $count = $this->db->getCount('contrat', 'ref LIKE "' . $this->getRef() . '%"', 'rowid');
            $new_contrat->updateField('ref', $this->getRef() . '-' . $count);
            $this->addLog("Création du contrat de renouvellement numéro " . $new_contrat->getData('ref'));
            addElementElement('contrat', 'contrat', $this->id, $new_contrat->id);
            $new_contrat->copyContactsFromOrigin($this);
            $this->updateField('next_contrat', $new_contrat->id);
            $children = $this->getChildrenList("lines", Array("renouvellement" => 0));
            foreach ($children as $id_child) {

                $child = $this->getChildObject("lines", $id_child);

                $neew_price = $child->getData('subprice');
                if ($this->getData('syntec') > 0 && BimpTools::getValue('use_syntec')) {
                    $neew_price = $child->getData('subprice') * (BimpCore::getConf('current_indice_syntec') / $this->getData('syntec'));
                }
                $new_contrat->dol_object->pa_ht = $child->getData('buy_price_ht'); // BUG DéBILE DOLIBARR
                $createLine = $new_contrat->dol_object->addLine(
                        $child->getData('description'), $neew_price, $child->getData('qty'), $child->getData('tva_tx'), 0, 0, $child->getData('fk_product'), $child->getData('remise_percent'), $for_date_end->add(new DateInterval("P1D"))->format('Y-m-d'), $for_date_end->add(new DateInterval('P' . $this->getData('duree_mois') . "M"))->format('Y-m-d'), 'HT', 0.0, 0, null, $child->getData('buy_price_ht'), Array('fk_contrat' => $new_contrat->id)
                );

                if ($createLine > 0) {
                    $new_line = $new_contrat->getChildObject('lines', $createLine);
                    $new_line->updateField('serials', $child->getData('serials'));
                } else {
                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($new_contrat));
                }
            }
        }

        return Array('errors' => $errors, 'warnings' => $warnings, 'success_callback' => $callback);
    }

    public function tacite($auto)
    {
        $errors = [];
        $warnings = [];

        $this->actionUpdateSyntec();
        $current_indice_syntec = $this->getData('syntec');
        $new_indice_syntec = BimpCore::getConf('current_indice_syntec');
        $current_renouvellement = $this->getData('current_renouvellement');
        $next_renouvellement = ($current_renouvellement + 1);
        $syntec_for_use_this_renouvellement = ($current_renouvellement == 0) ? $this->getData('syntec') : $this->getData('syntec_renouvellement');
        $duree_contratIni = $this->getData('duree_mois') / ($this->getData('current_renouvellement') + 1);

        $new_date_start = new DateTime($this->displayRealEndDate("Y-m-d"));

        $new_date_start->add(new DateInterval("P1D"));
        $new_date_end = new dateTime($new_date_start->format('Y-m-d'));
        $new_date_end->add(new DateInterval("P" . $duree_contratIni . "M"));
        $new_date_end->sub(new DateInterval('P1D'));

        $new_renouvellementTacite = self::CONTRAT_RENOUVELLEMENT_NON;

        switch ($this->getData('tacite')) {
            case self::CONTRAT_RENOUVELLEMENT_1_FOIS:
                $new_renouvellementTacite = self::CONTRAT_RENOUVELLEMENT_NON;
                break;
            case self::CONTRAT_RENOUVELLEMENT_2_FOIS:
                $new_renouvellementTacite = self::CONTRAT_RENOUVELLEMENT_1_FOIS;
                break;
            case self::CONTRAT_RENOUVELLEMENT_3_FOIS:
                $new_renouvellementTacite = self::CONTRAT_RENOUVELLEMENT_2_FOIS;
                break;
            case self::CONTRAT_RENOUVELLEMENT_4_FOIS:
                $new_renouvellementTacite = self::CONTRAT_RENOUVELLEMENT_3_FOIS;
                break;
            case self::CONTRAT_RENOUVELLEMENT_5_FOIS:
                $new_renouvellementTacite = self::CONTRAT_RENOUVELLEMENT_4_FOIS;
                break;
            case self::CONTRAT_RENOUVELLEMENT_6_FOIS:
                $new_renouvellementTacite = self::CONTRAT_RENOUVELLEMENT_5_FOIS;
                break;
        }
        $errors[] = "SYNTEC: " . $current_indice_syntec;
        $errors[] = "NEW SYNTEC: " . $new_indice_syntec;
        $errors[] = "USE SYNTEC: " . $syntec_for_use_this_renouvellement;
        $errors[] = "CURRENT Renouvellement: " . $current_renouvellement;
        $errors[] = "NEXT Renouvellement: " . $next_renouvellement;
        $errors[] = "NEW DATE START: " . $new_date_start->format('d / m / Y');
        $errors[] = "NEW DATE END: " . $new_date_end->format('d / m / Y');
        $errors[] = '';

        $children = $this->getChildrenList("lines", ['renouvellement' => $current_renouvellement]);
        foreach ($children as $id_child) {
            $child = $this->getChildObject("lines", $id_child);
            if ($current_indice_syntec > 0) {
                $new_price = ($child->getData('subprice') * ($new_indice_syntec / $current_indice_syntec));
            } else {
                $new_price = $child->getData('subprice');
            }

            $errors[] = "NEW PRICE LINE: " . price($new_price);
            $this->dol_object->pa_ht = $child->getData('buy_price_ht'); // BUG DéBILE DOLIBARR
            $createLine = $this->dol_object->addLine(
                    $child->getData('description'), $new_price, $child->getData('qty'), $child->getData('tva_tx'), 0, 0, $child->getData('fk_product'), $child->getData('remise_percent'), $new_date_start->format('Y-m-d'), $new_date_end->format('Y-m-d'), 'HT', 0.0, 0, null, $child->getData('buy_price_ht'), Array('fk_contrat' => $this->id)
            );

            if ($createLine > 0) {
                $tmpChild = $this->getChildObject("lines", $createLine);
                $tmpChild->updateField('serials', $child->getData('serials'));
                $tmpChild->updateField('renouvellement', $next_renouvellement);
                $child->updateField('statut', 5);
                $tmpChild->updateField('statut', 4);
                $errors = [];
                $success = "Contrat renouvellé avec succès";
            } else {
                $errors[] = 'Probléme création ligne';
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this));
            }
        }

        if (!count($errors)) {
            $this->updateField('tacite', $new_renouvellementTacite);
            $this->updateField('duree_mois', $this->getData('duree_mois') + $duree_contratIni);
            $this->updateField('current_renouvellement', $next_renouvellement);
            $this->updateField('syntec_renouvellement', $new_indice_syntec);
            $this->updateField('relance_renouvellement', 1);
            $this->addLog('Renouvellement tacite N°' . $next_renouvellement);
            $this->updateField('date_end_renouvellement', $new_date_end->format('Y-m-d'));

            $echeancier = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_echeancier');
            $echeancier->fetchBy('id_contrat', $this->id);
            $echeancier->updateField('next_facture_date', $new_date_start->format('Y-m-d') . "  00:00:00");
        }

        if ($auto) {
            return 1;
        } else {
            return [
                'success'  => $success,
                'warnings' => $warnings,
                'errors'   => $errors
            ];
        }
    }

    public function renouvellementTaciteCron()
    {
        return 0;
    }

    public function createSignature($init_docu_sign = false, $open_public_acces = true, $id_contact = 0, $email_content = '', &$warnings = array(), &$success = '')
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            if (!(int) BimpCore::getConf('contrat_use_signatures', null, 'bimpcontract')) {
                $errors[] = 'Les signatures ne sont pas activées pour les contrats';
                return $errors;
            }

            if ((int) $this->getData('id_signature')) {
                $errors[] = 'La signature a déjà été créée pour ' . $this->getLabel('this');
                return $errors;
            }

            $id_client = (int) $this->getData('fk_soc');

            if (!$id_client) {
                $errors[] = 'Client absent';
                return $errors;
            }

            $signature = BimpObject::createBimpObject('bimpcore', 'BimpSignature', array(
                        'obj_module' => 'bimpcontract',
                        'obj_name'   => 'BContract_contrat',
                        'id_obj'     => $this->id,
                        'doc_type'   => 'contrat'
                            ), true, $errors, $warnings);

            if (!count($errors) && BimpObject::objectLoaded($signature)) {
                $errors = $this->updateField('id_signature', (int) $signature->id);

                if (!count($errors)) {
                    $success .= '<br/>Fiche signature créée avec succès';
                    $signataire_errors = array();
                    $allow_dist = $this->isSignDistAllowed();
                    $ds_required = false;
                    $ds_errors = array();
                    $allow_docusign = (int) $this->isDocuSignAllowed($ds_errors, $ds_required);
                    if ($allow_docusign && $ds_required) {
                        $allow_dist = 0;
                    }
                    $allow_refuse = (int) BimpCore::getConf('contrat_signature_allow_refuse', null, 'bimpcontract');

                    // Client
                    $contact = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', (int) $id_contact);
                    if (!BimpObject::objectLoaded($contact)) {
                        $errors[] = "Contact client absent, merci de le définir";
                    } else {
                        BimpObject::loadClass('bimpcore', 'BimpSignataire');
                        $signataire_client = BimpObject::createBimpObject('bimpcore', 'BimpSignataire', array(
                                    'id_signature'   => $signature->id,
                                    'label'          => 'Client',
                                    'id_client'      => $id_client,
                                    'id_contact'     => $id_contact,
                                    'allow_dist'     => $allow_dist,
                                    'allow_docusign' => $allow_docusign,
                                    'allow_refuse'   => $allow_refuse,
                                    'type'           => BimpSignataire::TYPE_CLIENT,
                                    'nom'            => $contact->getData('firstname') . ' ' . $contact->getData('lastname'),
                                    'code'           => 'client',
                                        ), true, $signataire_errors, $warnings);
                    }

                    if (!BimpObject::objectLoaded($signataire_client)) {
                        $errors[] = BimpTools::getMsgFromArray($signataire_errors, 'Echec de l\'ajout du contact signataire à la fiche signature');
                    } else {
                        // Responsable
                        if ($this->getTotalContrat() < 15000) {
                            if ($this->getData('secteur') == 'CTE') {
                                $id_user = (int) BimpCore::getConf('id_responsable_education', null, 'bimpcontract');
                            } else {
                                $id_user = (int) BimpCore::getConf('id_responsable_commercial', null, 'bimpcontract');
                            }
                        } else {
                            $id_user = (int) BimpCore::getConf('id_responsable_general', null, 'bimpcontract');
                        }

                        $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $id_user);

                        if ($id_user && BimpObject::objectLoaded($user)) {
                            $signataire_user = BimpObject::createBimpObject('bimpcore', 'BimpSignataire', array(
                                        'id_signature'   => $signature->id,
                                        'label'          => 'Responsable',
                                        'id_user'        => $id_user,
                                        'type'           => BimpSignataire::TYPE_USER,
                                        'nom'            => $user->getData('firstname') . ' ' . $user->getData('lastname'),
                                        'allow_dist'     => $allow_dist,
                                        'allow_docusign' => $allow_docusign,
                                        'allow_refuse'   => $allow_refuse,
                                        'code'           => 'user',
                                            ), true, $signataire_errors, $warnings);

                            if (!BimpObject::objectLoaded($signataire_user)) {
                                $errors[] = BimpTools::getMsgFromArray($signataire_errors, 'Echec de l\'ajout du contact signataire à la fiche signature');
                            } else {
                                if ($init_docu_sign && $allow_docusign) {
                                    $docusign_success = '';
                                    $docusign_result = $signature->setObjectAction('initDocuSign', 0, array(
                                        'email_content' => $email_content
                                            ), $docusign_success, true);

                                    if (count($docusign_result['errors'])) {
                                        $errors[] = BimpTools::getMsgFromArray($docusign_result['errors'], 'Echec de l\'envoi de la demande de signature via DocuSign');
                                    } else {
                                        $success .= '<br/>' . $docusign_success;
                                    }
                                    if (!empty($docusign_result['warnings'])) {
                                        $warnings[] = BimpTools::getMsgFromArray($docusign_result['warnings'], 'Envoi de la demande de signature via DocuSign');
                                    }
                                } elseif ($open_public_acces && $allow_dist) {
                                    $open_errors = $signataire_client->openSignDistAccess(true, $email_content, true);

                                    if (count($open_errors)) {
                                        $warnings[] = BimpTools::getMsgFromArray($open_errors, 'Echec de l\'ouverture de l\'accès à la signature à distance');
                                    }

                                    $open_errors = $signataire_user->openSignDistAccess(true, $email_content, true);

                                    if (count($open_errors)) {
                                        $warnings[] = BimpTools::getMsgFromArray($open_errors, 'Echec de l\'ouverture de l\'accès à la signature à distance');
                                    }
                                }
                            }
                        } else {
                            $errors[] = 'Responsable inconnu pour le secteur "' . $this->displayDataDefault('secteur') . '"';
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function relance_renouvellement_commercial()
    {
        
    }

    public function facturationIsDemainCron($heure_cron = 23)
    {
        $echeancier = BimpCache::findBimpObjectInstance('bimpcontract', 'BContract_echeancier', ['id_contrat' => $this->id], true);
        if ($echeancier && $echeancier->isLoaded()) {
            $today = new DateTime(date('Y-m-d ' . $heure_cron . ':00:00'));
            $nextFacturation = new DateTime($echeancier->getData('next_facture_date'));

            $diff = $today->diff($nextFacturation);

            if ($diff->y == 0 && $diff->m == 0 && $diff->d == 0 && $diff->h == 1) {
                return 1;
            }
        }

        return 0;
    }

    public function mail($destinataire, $type, $cc = "")
    {
        switch ($type) {
            case self::MAIL_DEMANDE_VALIDATION:
                $sujet = "Contrat en attente de validation";
                $action = "Valider la conformité du contrat";
                break;
            case self::MAIL_VALIDATION:
                $sujet = "Contrat validé par le service technique";
                $action = "Ce contrat a été validé par le service technique.<br/>Vous devez maintenant utiliser l'action <b>\"Créer signature\"</b> afin de le faire signer par le client, puis par votre direction commerciale</b>";
                break;
            case self::MAIL_SIGNED:
                $sujet = "Contrat signé par le client";
                $action = "Activer le contrat";
                break;
            case self::MAIL_ACTIVATION:
                $sujet = "Contrat activé";
                $action = "Facturer le contrat";
                break;
        }

        $commercial = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $this->getCommercialClient());
        $commercialContrat = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $this->getData('fk_commercial_suivi'));
        $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $this->getData('fk_soc'));

        $extra = "<h3 style='color:#EF7D00'><b>BIMP</b><b style='color:black'>contrat</b></h3>";
        $extra .= "Action à faire sur le contrat: <b>" . $action . "</b><br /><br />";
        $extra .= "<u><i>Informations <i></i> </i></u><br />";
        $extra .= "Contrat: <b>" . $this->getNomUrl() . "</b><br />";
        $extra .= "Client: <b>" . $client->dol_object->getNomUrl() . " (" . $client->getNomUrl() . ")</b><br /><br />";
        $extra .= "Commercial du contrat: <b>" . $commercialContrat->dol_object->getNomUrl() . "</b><br />";
        $extra .= "Commercial du client: <b>" . $commercial->dol_object->getNomUrl() . "</b><br />";

        //print_r(['dest' => $destinataire, 'sujet' => $sujet, 'type' => $type, 'msg' => $extra]);
        if ($cc == "")
            mailSyn2($sujet, $destinataire, BimpCore::getConf('devs_email'), $extra);
        else
            mailSyn2($sujet, $destinataire, BimpCore::getConf('devs_email'), $extra, array(), array(), array(), $cc);
    }

    public function autoClose()
    {//passer les contrat au statut clos quand toutes les enssiéne ligne sont close
        if ($this->id > 0 && $this->getData("statut") == 1 && new DateTime($this->displayRealEndDate("Y-m-d")) < new DateTime()) {
            $sql = $this->db->db->query("SELECT * FROM `" . MAIN_DB_PREFIX . "contratdet` WHERE statut != 5 AND `fk_contrat` = " . $this->id);
            if ($this->db->db->num_rows($sql) == 0) {
                $this->updateField("statut", 2);
            }
        }
    }

    // Actions: 

    public function actionCreateFi($data, &$success)
    {
        $errors = [];
        $warnings = [];
        $callback = "";
        if ($data['nature_inter'] == 0 || $data['type_inter'] == 0) {
            $errors[] = "Vous ne pouvez pas créer un fiche d'intervention avec comme Nature/Type 'FI ancienne version', Merci";
        }
        if (!count($errors)) {
            $fi = BimpCache::getBimpObjectInstance('bimptechnique', 'BT_ficheInter');
            $id_new_fi = $fi->createFrom('contrat', $this, $data);
        }
        if ($id_new_fi > 0) {
            $callback = 'window.open("' . DOL_URL_ROOT . '/bimpfi/index.php?fc=fi&id=' . $id_new_fi . '")';
        } else {
            $errors[] = "La FI n'a pas été créée";
        }
        return [
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $callback
        ];
    }

    public function actionAnticipateClose($data, &$success)
    {
        global $user;

        $errors = [];

        if ($data['have_courrier'] == 0) {
            $errors[] = "Vous ne pouvez pas anticiper la clôture de ce contrat sans lettre de résiliation";
        }

        if ($this->isLoaded()) {
            $warnings = [];

            if (!count($errors)) {
                $this->updateField("end_date_reel", $data['end_date_reel']);
                $this->updateField('anticipate_close_note', $data['note_close']);
                $this->updateField('relance_renouvellement', 0);
                $success = "Date de fin défini avec succès";
                $dateClose = new DateTime($date['end_date_reel']);
                $commercial = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $this->getData('fk_commercial_suivi'));
                $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $this->getData('fk_soc'));
                $sujet = "Clôture anticipée du contrat " . $this->getRef() . " [" . $client->getRef() . "] - " . $client->getName();
                $message = "Bonjour,<br />La date du " . $dateClose->format('d/m/Y') . " a été choisie par " . $user->getNomUrl() . " "
                        . "comme date de fin anticipée du contrat " . $this->getNomUrl() . " pour le client "
                        . $client->getNomUrl() . ' - ' . $client->getName() . ' pour la raison suivante:';
                $message .= "<br /><br />" . $data['note_close'] . "<br /><br />Ce contrat ce clôturera automatiquement à cette date.";
                $addr_cc = ($commercial->getData('email') == $user->email) ? '' : $user->email;
                $bimpMail = New BimpMail($this, $sujet, $commercial->getData('email'), null, $message, null, $addr_cc);
                $bimpMail->send($errors);
            }
        } else {
            $errors[] = "ID du contrat absent";
        }

        return [
            'warnings' => $warnings,
            'errors'   => $errors,
            'success'  => $success
        ];
    }

    public function actionActivateContrat($data, &$success)
    {
        global $user;
        $errors = [];

        $dateEffecte = new DateTime($this->getData('date_start'));
        $date_now = new DateTime();

        $diff = $date_now->diff($dateEffecte);

        //$errors[] = print_r($diff,1);

        if (($diff->days > 10) && !$diff->invert) {
            $errors[] = "Ce contrat ne peut pas être activé car sa date d'effet est trop éloignée. Le groupe contrat recevra une demande d'activation 10 jours avant cette date";
        }

        if ($this->isLoaded() && !count($errors)) {



            $signed_doc = ($data['have_contrat_signed']) ? true : false;
            if ($signed_doc) {
                $this->closeContratChildWhenActivateRenewManual();

                $this->updateField('statut', self::CONTRAT_STATUS_ACTIVER);

                $success = "Le contrat " . $this->getData('ref') . ' a été activé avec succès';
                $this->addLog('Contrat activé');
                if ($this->getEndDate() != '') {
                    $this->updateField('end_date_contrat', $this->getEndDate()->format('Y-m-d'));
                }
                $this->dol_object->activateAll($user);

                $commercial = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $this->getData('fk_commercial_suivi'));
                $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $this->getData('fk_soc'));

                if ($commercial->isLoaded() && $this->getData('periodicity') != self::CONTRAT_PERIOD_AUCUNE) {
                    $this->mail($this->email_facturation, self::MAIL_ACTIVATION, $commercial->getData('email'));
                } else {
                    $warnings[] = "Le mail n'a pas pu être envoyé, merci de contacter directement la personne concernée";
                }
                $id_echeancier = (int) $this->db->getValue('bcontract_prelevement', 'id', 'id_contrat = ' . $this->id);
                if (!$id_echeancier && $this->getData('periodicity') != self::CONTRAT_PERIOD_AUCUNE) {
                    $this->createEcheancier();
                }
            } else {
                if ($this->getData('statut') != self::CONTRAT_STATUS_ACTIVER_TMP) {
                    $this->dol_object->activateAll($user);
                    $this->updateField('statut', self::CONTRAT_STATUS_ACTIVER_TMP);
                    $this->updateField('date_start_provisoire', date('Y-m-d'));
                    $dateForCloseNoSigned = new DateTime();
                    $dateForCloseNoSigned->add(new DateInterval("P14D"));
                    $this->addLog('Activation provisoire');
                    $commercialContrat = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $this->getData('fk_commercial_suivi'));
                    $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $this->getData('fk_soc'));
                    $msg = "Votre contrat " . $this->getNomUrl() . " pour le client " . $client->getNomUrl() . " " . $client->getName() . " est activé provisoirement car il n'est pas revenu signé. Il sera automatiquement désactivé le " . $dateForCloseNoSigned->format('d / m / Y') . " si le nécessaire n'a pas été fait.";
                    //$errors[] = $msg;
                    mailSyn2("[CONTRAT] - Activation provisoire", $commercialContrat->getData('email'), null, $msg);
                    $this->addLog('Activation provisoire');
                } else {
                    $errors[] = "Ce contrat est déjà en activation provisoire";
                }
            }
        }

        return [
            'success'  => $success,
            'errors'   => $errors,
            'warnings' => $warnings
        ];
    }

    public function actionAddAcompte($data, &$success)
    {
        $errors = [];
        $warnings = [];
        $success = "";
        if (addElementElement('contrat', 'facture', $this->id, $data['acc'])) {
            $success = "Acompte lié avec succès";
        }
        return [
            "success"  => $success,
            "warnings" => $warnings,
            "errors"   => $errors
        ];
    }

    public function actionTestContrat($data, &$success)
    {
        $errors = [];
        $warnings = [];
        global $user;
        if ($user->id == 330)
            $errors[] = $this->getData('id_signature');

        return Array('errors' => $errors, 'warnings' => $warnings, 'success' => $success);
    }

    public function actionClose($data, &$success)
    {
        global $user;
        $errors = [];
        $warnings = [];
        $success = 'Contrat clos avec succès';
        $echeancier = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_echeancier');
        if ($this->dol_object->closeAll($user) >= 1) {
            $this->updateField('statut', self::CONTRAT_STATUS_CLOS);
            $this->updateField('date_cloture', date('Y-m-d H:i:s'));
            $this->updateField('fk_user_cloture', $user->id);
            $this->addLog('Contrat clos');
            if ($echeancier->find(['id_contrat' => $this->id])) {
                $echeancier->updateField('statut', 0);
            }
        }

        return [
            'errors'   => $errors,
            'warnings' => $warnings,
            'success'  => $success
        ];
    }

    public function actionUpdateSyntec($data = array(), &$success = '')
    {
        $syntec = file_get_contents("https://syntec.fr/");
        
        if (preg_match('/<div class="indice-number"[^>]*>(.*)<\/div>/isU', $syntec, $matches)) {
            $indice = str_replace(' ', "", strip_tags($matches[0]));
            BimpCore::setConf('current_indice_syntec', str_replace(' ', "", strip_tags($indice)));
            $success = "L'indice Syntec s'est mis à jours avec succès";
        } else {
            $errors [] = "Impossible de récupérer l'indice Syntec automatiquement, merci de le rensseigner manuellement";
        }
        
        return [
            'success'  => $success,
            'errors'   => $errors,
            'warnings' => array()
        ];
    }

    public function actionReopen($data, &$success)
    {
//        if (count(getElementElement('contrat', 'facture', $this->id))) {
//            $errors[] = "Vous ne pouvez pas supprimer cet échéancier car il y a une facture dans celui-ci";
//        }
        $errors = Array();
        if (!count($errors)) {
            $success = "Contrat ré-ouvert avec succès";
            $this->updateField('statut', self::CONTRAT_STATUS_ACTIVER);
            $this->addLog('Contrat ré-ouvert');
            foreach ($this->dol_object->lines as $line) {
                $the_line = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contratLine', $line->id);
                if ($the_line->getData('renouvellement') == $this->getData('current_renouvellement'))
                    $the_line->updateField('statut', $the_line->LINE_STATUT_OPEN);
            }
            $this->db->delete('bcontract_prelevement', 'id_contrat = ' . $this->id);
        }
        return [
            'errors'   => $errors,
            'warnings' => $warnings,
            'success'  => $success
        ];
    }

    public function actionAbort($data = [], &$success)
    {

        if ($this->isLoaded()) {

            $errors = $this->updateField('statut', self::CONTRAT_STATUT_ABORT);

            if (!count($errors)) {
                $this->turnOffEcheancier();
                $this->addLog("Contrat abandonné");
                $success = "Le contrat a bien été abandonné";
            }

            return [
                'errors'   => $errors,
                'warnings' => [],
                'success'  => $success
            ];
        }
    }

    public function actionRefuse($data = [], &$success)
    {

        if ($this->isLoaded()) {

            $errors = $this->updateField('statut', self::CONTRAT_STATUS_REFUSE);

            if (!count($errors)) {
                $this->turnOffEcheancier();
                $this->addLog("Contrat refusé par le client");
                $success = "Le contrat à bien été notifié comme refusé";
            }

            return [
                'errors'   => $errors,
                'warnings' => [],
                'success'  => $success
            ];
        }
    }

    public function actionAutoFact($data, &$success)
    {

        $warnings = [];
        $success = "";
        $errors = [];

        if (!$this->getData('entrepot') && $this->useEntrepot())
            $errors[] = "La facturation automatique ne peut être activée car le contrat n'a pas d'entrepot";

        if (!count($errors)) {
            $instance = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_echeancier', $data['e']);
            $errors = $instance->updateField('validate', $data['to']);

            if (!count($errors)) {
                if ($data['to'] == 1) {
                    // Le contrat passe en facturation auto ON
                    $success = 'La facturation automatique a été activée';
                } else {
                    // Le contrat passe en facturation auto OFF
                    $success = 'La facturation automatique a été désactivée';
                }
                $this->addLog($success);
            }
        }


        return [
            'errors'   => $errors,
            'warnings' => $warnings,
            'success'  => $success
        ];
    }

    public function actionPlanningInter($data, &$success = '')
    {
        $errors = [];
        $success = 'Fiche inter créée avec succès';

        if (isset($data['techs']) && is_array($data['techs']) && count($data['techs'])) {
            $errors[] = "Vous ne pouvez pas plannifier une intervention sans au moins un techhnicien";
        } else {
            $instance = BimpCache::getBimpObjectInstance('bimptechnique', 'BT_ficheInter');
            $errors = $instance->createFromContrat($this, $data);
        }

        return array(
            'errors'   => $errors,
            'warnings' => array()
        );
    }

    public function actionTacite($data, &$success)
    {

        return $this->tacite(false);
    }

    public function actionManuel($data, &$success)
    {
        return $this->manuel();
    }

    public function actionFactureSupp($data, &$success)
    {
        $warnings = [];
        $errors = [];

        $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture');
        $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $this->getData('fk_soc'));

        $facture->set('libelle', "Facture supplémentaire de votre contrat numéro " . $this->getRef());
        $facture->set('type', 0);
        $facture->set('fk_soc', $client->id);

        if (!$this->getData('entrepot') && $this->useEntrepot()) {
            return array("La facture ne peut pas être crée car le contrat n'a pas d'entrepôt");
        }

        if ($this->useEntrepot())
            $facture->set('entrepot', $this->getData('entrepot'));

        $facture->set('fk_cond_reglement', ($client->getData('cond_reglement')) ? $client->getData('cond_reglement') : 2);
        $facture->set('fk_mode_reglement', ($this->getData('moderegl')) ? $this->getData('moderegl') : 2);
        $facture->set('datef', date('Y-m-d H:i:s'));
        $facture->set('ef_type', $this->getData('secteur'));
        $facture->set('model_pdf', 'bimpfact');
        $facture->set('ref_client', $this->getData('ref_customer'));
        $facture->set('expertise', $this->getData('expertise'));

        if ($facture->field_exists('rib_client') && $this->field_exists('rib_client')) {
            $facture->set('rib_client', (int) $this->getData('rib_client'));
        }

        $errors = $facture->create($warnings, true);

        if (!count($errors)) {
            if ($facture->dol_object->addLine(
                            "Facturation du reste à payer de votre contrat numéro " . $this->getRef(),
                            $this->reste_a_payer(),
                            1, 20, 0, 0, 0, 0, '', '', 0, 0, '', 'HT', 0, 0
                    )) {
                addElementElement("contrat", "facture", $this->id, $facture->id);
                $success = "Facture " . $facture->getRef() . " créée avec succès";
            }
        }

        return [
            'errors'   => $errors,
            'warnings' => $warnings,
            'success'  => $success
        ];
    }

    public function actionStopTacite($data, &$success)
    {

        $errors = [];
        $warnings = [];

        $this->set("tacite", 0);
        $this->set("relance_renouvellement", 0);
        if ($this->update($warnings)) {
            $success = "La reconduction tacite a été annulée";
        }

        return Array('errors' => $errors, 'warnings' => $warnings, 'success' => $success);
    }

    public function actionRedefineEcheancier($data, &$success)
    {
        $errors = [];
        $warnings = [];

        $echeancier = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_echeancier');
        $echeancier->find(['id_contrat' => $this->id]);

        $idForDelete = [];
        $id_forUpdate = [];
        foreach ($data['lines'] as $id_line) {
            $idForDelete[] = $id_line;
        }
        if (count($idForDelete) > 0)
            $errors = $this->db->delete("contratdet", 'rowid IN (' . implode(",", $idForDelete) . ')');

        foreach ($data['lines_activate'] as $id_line) {
            $id_forUpdate[] = $id_line;
        }
        if (count($id_forUpdate) > 0)
            $errors = $this->db->update('contratdet', Array('statut' => 4, 'renouvellement' => $data['current_renouvellement']), 'rowid IN(' . implode(",", $id_forUpdate) . ')');

        $errors = $this->updateField('date_end_renouvellement', $data['date_end_renouvellement']);
        $errors = $this->updateField('end_date_contrat', $data['date_end_renouvellement']);
        $errors = $echeancier->updateField('next_facture_date', '0000-00-00 00:00:00');
        $errors = $this->updateField('current_renouvellement', $data['current_renouvellement']);
//        $errors = $this->updateField('initial_renouvellement', $data['initial_renouvellement']);
        $errors = $this->updateField('tacite', $data['tacite']);
        return [
            'errors'   => $errors,
            'warnings' => $warnings,
            'success'  => $success
        ];
    }

    public function actionCreateSignatureDocuSign($data, &$success)
    {
        // TODO Test des conditions de validation du contrat
//        global $user;
        $errors = array();
        $warnings = array();

        $errors[] = 'En cours de développement';

        if (!count($errors)) {
            $success_callback = '';

            $id_contact = BimpTools::getArrayValueFromPath($data, 'id_contact', '');
            if (!$id_contact) {
                $errors[] = 'Veuillez renseigner un contact';
            }

            $errors_signature = $this->createSignature($id_contact);
            $errors = BimpTools::merge_array($errors, $errors_signature);

            if (!count($errors)) {
                $signature = $this->getChildObject('signature');
                $success = "Enveloppe envoyée avec succès<br/>";
                $success .= $signature->getNomUrl() . ' créée avec succès';
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function actionCreateDI($data, &$success)
    {
        global $user;
        if ($data['lines'] == 0)
            $errors[] = "Il doit y avoir au moin une ligne de selectionnée";
        $techs = null;
        $lines = json_encode($data['lines']);
        $today = new DateTime();

        if (!count($errors)) {
            if ($data['techs'])
                $techs = json_encode($data['techs']);

            $di = BimpCache::getBimpObjectInstance('bimptechnique', 'BT_demandeInter');
            $di->set("fk_soc", $this->getData('fk_soc'));
            $di->set("fk_contrat", $this->id);
            BimpTools::loadDolClass('synopsisdemandeinterv');
            $tmp_di = new Synopsisdemandeinterv($this->db->db);
            $di->set("ref", $tmp_di->getNextNumRef($this->getData('fk_soc')));
            $tmp_di = null;
            $datei = new DateTime($data['date']);
            $di->set("datei", $datei->getTimestamp());
            $di->set("datec", $today->format('Y-m-d H:i:s'));
            $di->set("fk_user_author", $user->id);
            $di->set('fk_statut', 0);
            $di->set('duree', $data['duree']);
            $di->set('description', $data['titre']);
            $di->set('techs', $techs);
            $di->set('contratLine', $lines);
            $di->set('fk_user_target', $data['tech']);
            $di->set('description', "");

            $errors = $di->create();

            if (!count($errors)) {
                $callback = 'window.open("' . DOL_URL_ROOT . '/bimptechnique/index.php?fc=di&id=' . $di->id . '")';
            }
        }

        return [
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $callback
        ];
    }

    public function actionCreateProposition($data, &$success)
    {
        global $user, $langs;
        $errors = [];
        $warnings = [];

        $callback = "";

        $date_livraison = new dateTime($this->getData('end_date_contrat'));
        $date_livraison->add(new DateInterval("P1D"));

        $propal = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal');
        $propal->set('fk_soc', $this->getData('fk_soc'));
        $propal->set('entrepot', $this->getData('entrepot'));
        $propal->set('ef_type', $this->getData('secteur'));
        $propal->set('fk_cond_reglement', 1);
        $propal->set('fk_mode_reglement', $this->getData('moderegl'));
        $propal->set('datep', date('Y-m-d'));
        if ($this->getData('label'))
            $propal->set('libelle', $this->getData('label'));
        else
            $propal->set('libelle', 'Renouvellement du contrat N°' . $this->getRef());
        $propal->set('date_livraison', $date_livraison->format('Y-m-d'));
        $oldSyntec = $this->getData('syntec');
        $this->actionUpdateSyntec();
        $newSyntec = BimpCore::getConf('current_indice_syntec');

        $errors = $propal->create();

        if (!count($errors)) {
            foreach ($this->dol_object->lines as $line) {
                $new_price = ($oldSyntec == 0) ? $line->subprice : ($line->subprice * ($newSyntec / $oldSyntec));
                $propal->dol_object->addLine(
                        $line->desc, $new_price, $line->qty, 20, 0, 0, $line->fk_product, $line->remise_percent, "HT", 0, 0, 0, -1, 0, 0, 0, $line->pa_ht
                );
            }
            $callback = 'window.open("' . DOL_URL_ROOT . '/bimpcommercial/index.php?fc=propal&id=' . $propal->id . '")';
            $propal->copyContactsFromOrigin($this);
//            setElementElement('contrat', 'propal', $this->id, $propal->id);
            $success = "Creation du devis de renouvellement avec succès";
        }

        return [
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $callback
        ];
    }

    public function actionRenouvellementWithSyntecPropal($data, &$success)
    {
        global $user, $langs;
        $errors = [];
        $warnings = [];

        return [
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $callback
        ];
    }

    public function actionRenouvellementWithSyntec($data, &$success)
    {

        global $user, $langs;
        $errors = [];
        $warnings = [];

        $canRenew = true;
        $renovTaciteReconduction = 0;
        switch ($this->getData('tacite')) {
            case self::CONTRAT_RENOUVELLEMENT_1_FOIS:
                $renovTaciteReconduction = 1;
                break;
            case self::CONTRAT_RENOUVELLEMENT_2_FOIS:
                $renovTaciteReconduction = 2;
                break;
            case self::CONTRAT_RENOUVELLEMENT_3_FOIS:
                $renovTaciteReconduction = 3;
                break;
            case self::CONTRAT_RENOUVELLEMENT_4_FOIS:
                $renovTaciteReconduction = 4;
                break;
            case self::CONTRAT_RENOUVELLEMENT_5_FOIS:
                $renovTaciteReconduction = 5;
                break;
            case self::CONTRAT_RENOUVELLEMENT_6_FOIS:
                $renovTaciteReconduction = 6;
                break;
        }

        if ($canRenew) {
            $oldSyntec = $this->getData('syntec');
            $this->actionUpdateSyntec();
            $newSyntec = BimpCore::getConf('current_indice_syntec');

            $id_for_source = $this->id;
            $ref_for_count = $this->getData('ref');
            if ($this->getData('contrat_source') > 0) {
                $contrat_source = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat', $this->getData('contrat_source'));
                $ref_for_count = $contrat_source->getData('ref');
                $id_for_source = $contrat_source->id;
            }

            $count = $this->db->getCount('contrat', 'ref LIKE "' . $ref_for_count . '%"', 'rowid');

            $new = clone $this;
            $new->set('statut', self::CONTRAT_STATUS_BROUILLON);
            $new->set('ref', $this->getData('ref') . "_" . $count);
            $new->set('contrat_source', $id_for_source);
            $new->set('syntec', $newSyntec);
            $new->set('relance_renouvellement', 1);
            $new->set('date_contrat', null);
            $new->set('label', "RENOUVELLEMENT DU CONTRAT N°" . $this->getData('ref'));
            $date_for_dateTime = ($this->getData('end_date_contrat')) ? $this->getData('end_date_contrat') : $this->getEndDate()->format('Y-m-d');
            $date_start = new DateTime($date_for_dateTime);
            $date_start->add(new DateInterval("P1D"));
            $new->set('date_start', $date_start->format('Y-m-d'));
            $new->set('logs', "Contrat renouvellé TACITEMENT le <strong>" . date('d/m/Y') . "</strong> à <strong>" . date('H:i:s') . "</strong> par <strong>" . $user->getFullName($langs) . "</strong>");
            if ($this->getData('tacite') == 1) {
                $new->set('tacite', 12);
            } else {
                $new_renovTaciteReconduction = $renovTaciteReconduction - 1;
                switch ($new_renovTaciteReconduction) {
                    case 1:
                        $to_tacite = self::CONTRAT_RENOUVELLEMENT_1_FOIS;
                        break;
                    case 2:
                        $to_tacite = self::CONTRAT_RENOUVELLEMENT_2_FOIS;
                        break;
                    case 3:
                        $to_tacite = self::CONTRAT_RENOUVELLEMENT_3_FOIS;
                        break;
                    case 4:
                        $to_tacite = self::CONTRAT_RENOUVELLEMENT_4_FOIS;
                        break;
                    case 5:
                        $to_tacite = self::CONTRAT_RENOUVELLEMENT_5_FOIS;
                        break;
                    case 6:
                        $to_tacite = self::CONTRAT_RENOUVELLEMENT_6_FOIS;
                        break;
                }
                $new->set('tacite', $to_tacite);
            }



            if ($new->create() > 0) {
                $callback = 'window.location.href = "' . DOL_URL_ROOT . '/bimpcontract/index.php?fc=contrat&id=' . $new->id . '"';
                foreach ($this->dol_object->lines as $line) {
                    $new_price = ($oldSyntec == 0) ? $line->subprice : (($line->subprice * ($newSyntec / $oldSyntec)));
                    $new->dol_object->pa_ht = $line->pa_ht; // BUG DéBILE DOLIBARR
                    $newLineId = $new->dol_object->addLine($line->desc, $new_price, $line->qty, $line->tva_tx, 0, 0, $line->fk_product, $line->remise_percent, $date_start->format('Y-m-d'), $new->getEndDate()->format('Y-m-d'), 'HT', 0.0, 0, null, (float) $line->pa_ht, 0, null, $line->rang);
                    $old_line = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contratLine', $line->id);
                    $new_line = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contratLine', $newLineId);
                    $new_line->updateField('serials', $old_line->getData('serials'));
                }
                $new->updateField('ref', $this->getData('ref') . "_" . $count);
                $new->copyContactsFromOrigin($this);
                setElementElement('contrat', 'contrat', $this->id, $new->id);
            }
        }

        return [
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $callback
        ];
    }

    public function actionDuplicate($data, &$success = Array())
    {
        $success = "Contrat cloner avec succès";
        $warnings = [];
        if (!$this->isLoaded()) {
            return array('ID ' . $this->getLabel('of_the') . ' absent');
        }

        $new_contrat = clone $this;
        $new_contrat->id = null;
        $new_contrat->id = 0;
        $new_contrat->set('id', 0);
        $new_contrat->set('fk_statut', 1);
        $new_contrat->set('ref', '');
        $new_contrat->set('date_contrat', null);

        if ($new_contrat->getData('objet_contrat') != 'CDP') {
            $arrayServiceDelegation = Array('SERV19-DP1', 'SERV19-DP2', 'SERV19-DP3');
            $lines = $this->getChildrenObjects('lines');
            foreach ($lines as $line) {
                $product = $line->getChildObject('produit');
                if ($product && $product->isLoaded()) {
                    $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $line->fk_product);
                    if (in_array($product->getRef(), $arrayServiceDelegation)) {
                        $errors[] = 'Vous ne pouvez pas mettre le code service ' . $product->getRef() . ' dans un autre contrat que dans un contrat de délégation.';
                    }
                }
            }
        }

        if (!count($errors))
            $errors = $new_contrat->create();

        return Array(
            'success'  => $success,
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionUnSign($data, &$success = Array())
    {

        $warnings = [];
        $errors = [];

        if ($this->updateField('date_contrat', null)) {
            $this->addLog('Contrat marqué comme non-signé');
            $success = 'Contrat dé-signer';
        }

        return [
            'success'  => $success,
            'errors'   => $errors,
            'warnings' => $warnings
        ];
    }

    public function actionSigned($data, &$success)
    {
        $success = 'Contrat signé avec succes';
        $warnings = [];
        $errors = [];

        $this->addLog('Contrat marqué comme signé');
        $this->updateField('date_contrat', date('Y-m-d HH:ii:ss'));

        if ($this->getData('statut') == self::CONTRAT_STATUS_VALIDE) {
            $this->updateField("statut", self::CONTRAT_STATUT_WAIT_ACTIVER);
        }

        $now = new DateTime();
        $effect = new dateTime($this->getData('date_start'));
        $sendMail = (strtotime($effect->format('Y-m-d')) > strtotime($now->format('Y-m-d'))) ? false : true;

        if ($this->getData('statut') != self::CONTRAT_STATUS_ACTIVER && $sendMail) {
            $this->mail($this->email_group, self::MAIL_SIGNED);
        }

        return [
            'errors'   => $errors,
            'warnings' => $warnings,
            'success'  => $success
        ];
    }

    public function actionDemandeValidation($data, &$success)
    {
        $errors = [];

        if (!count($errors)) {
            $id_contact_type = $this->db->getValue('c_type_contact', 'rowid', 'code = "SITE" AND element = "contrat"');
            $id_contact_suivi_contrat = $this->db->getValue('c_type_contact', 'rowid', 'code = "CUSTOMER" AND element = "contrat"');
            $id_contact_facturation_email = $this->db->getValue('c_type_contact', 'rowid', 'code = "BILLING2" AND element = "contrat"');

            $have_contact = ($this->db->getValue('element_contact', 'rowid', 'element_id = ' . $this->id . ' AND fk_c_type_contact = ' . $id_contact_type)) ? true : false;
            $have_contact_suivi = ($this->db->getValue('element_contact', 'rowid', 'element_id = ' . $this->id . ' AND fk_c_type_contact = ' . $id_contact_suivi_contrat)) ? true : false;
            $have_facturation_email = ($this->db->getValue('element_contact', 'rowid', 'element_id = ' . $this->id . ' AND fk_c_type_contact = ' . $id_contact_facturation_email)) ? true : false;
            $verif_contact_suivi = true;

            if (!$have_contact) {
                $errors[] = "Il doit y avoir au moin un site d'intervention associé au contrat";
            } else {
                $liste_contact_site = $this->db->getRows('element_contact', 'element_id = ' . $this->id . ' AND fk_c_type_contact = ' . $id_contact_type);
                foreach ($liste_contact_site as $contact => $infos) {
                    $contact_site = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $infos->fk_socpeople);
                    if (!$contact_site->getData('address'))
                        $errors[] = "Il n'y a pas d'adresse pour le site d'intervention. Merci d'en renseigner une. <br /> Contact: <a target='_blank' href='" . $contact_site->getUrl() . "'>#" . $contact_site->id . "</a>";
                }
            }
            if (!$have_facturation_email) {
                $errors[] = "Le contrat ne compte pas de contact facturation email";
            }
            if (!$have_contact_suivi) {
                $verif_contact_suivi = false;
                $errors[] = "Le contrat ne compte pas de contact client de suivi du contrat";
            }
            if ($verif_contact_suivi) {
                $contact = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $this->db->getValue('element_contact', 'fk_socpeople', 'element_id = ' . $this->id . ' AND fk_c_type_contact = ' . $id_contact_suivi_contrat));
                if (!$contact->getData('email') || (!$contact->getData('phone') && !$contact->getData('phone_mobile'))) {
                    $errors[] = "L'email et le numéro de téléphone du contact est obligatoire pour demander la validation du contrat <br />Contact: <a target='_blank' href='" . $contact->getUrl() . "'>#" . $contact->id . "</a>";
                }
            }

            //        $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $this->getData('fk_soc'));
            //        if(!$client->getData('email') || !$client->getData('phone')) {
            //            $errors[] = "L'email et le numéro de téléphone du client sont obligatoire pour demander la validation du contrat <br /> Contact: <a target='_blank' href='".$client->getUrl()."'>#".$client->getData('code_client')."</a>";
            //        }
            //        if($this->dol_object->add_contact(1, 'SALESREPFOLL', 'internal') <= 0) {
            //            $errors[] = "Impossible d'ajouter un contact principal au contrat";
            //        }

            $have_serial = false;
            $serials = [];

            $contrat_lines = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contratLine');
            $lines = $contrat_lines->getList(['fk_contrat' => $this->id]);

            foreach ($lines as $line) {

                $serials = BimpTools::json_decode_array($line['serials']);

                if (count($serials))
                    $have_serial = true;
            }

            if (!$have_serial)
                $errors[] = "Il doit y avoir au moin un numéro de série dans une des lignes du contrat";
            if (!$this->getData('entrepot') && (int) BimpCore::getConf("USE_ENTREPOT"))
                $errors[] = "Il doit y avoir un entrepot pour le contrat";
//            $errors = array();
            $modeReglementId = $this->db->getValue('c_paiement', 'id', 'code = "PRE"');

            if (!count($errors) && $this->getData('periodicity') != self::CONTRAT_PERIOD_AUCUNE && $this->getData('moderegl') != $modeReglementId) {
                $this->tryToValidate($errors);
            }


            if (!count($errors)) {
                $success = 'Validation demandée';
                $this->updateField('statut', self::CONTRAT_STATUS_WAIT);
                $msg = "Un contrat est en attente de validation de votre part. Merci de faire le nécessaire <br />Contrat : " . $this->getNomUrl();
                $this->addLog("Demande de validation");
                $this->mail($this->email_group, self::MAIL_DEMANDE_VALIDATION);
            }
        }



        return [
            'success'  => $success,
            'warnings' => $warnings,
            'errors'   => $errors
        ];
    }

    public function actionValidation($data, &$success)
    {
        global $user, $langs, $conf;
        if (preg_match('/^[\(]?PROV/i', $this->getData('ref'))) {
            $ref = BimpTools::getNextRef('contrat', 'ref', $this->getData('objet_contrat') . '{AA}{MM}-', 4);
        } else {
            $ref = $this->getData('ref');
        }
        $errors = $this->updateField('statut', self::CONTRAT_STATUS_VALIDE);

        if (!count($errors)) {
            if ($this->getData('contrat_source') && $this->getData('ref_ext')) {
                $annule_remplace = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat');
                if ($annule_remplace->find(['ref' => $this->getData('ref_ext')])) {
                    if ($annule_remplace->dol_object->closeAll($user)) {
                        $annule_remplace->updateField('statut', self::CONTRAT_STATUS_CLOS);
                    } else {
                        return array(
                            'errors'   => array('Impossible de fermé les lignes du contrat annulé et remplacé'),
                            'warnings' => array()
                        );
                    }
                } else {
                    return array(
                        'errors'   => array('Impossible de charger le contrat annulé et remplacé'),
                        'warnings' => array()
                    );
                }
            }

            // Changement de nom du répertoir pour les fichier
            require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
            $oldref = $this->getData('ref');
            $newref = $ref;
            $dirsource = $conf->contract->dir_output . '/' . $oldref;
            $dirdest = $conf->contract->dir_output . '/' . $newref;

            // Pas génial, repris de la validation des contrats car impossible de valider un contrat avec un statut autre que 0 avec la fonction validate de la class contrat
            if (file_exists($dirsource) && $dirsource != $dirdest) {
                dol_syslog(get_class($this) . "::actionValidation Renomer => " . $dirsource . " => " . $dirdest);
                if (rename($dirsource, $dirdest)) {
                    dol_syslog("Renomer avec succès");
                    if (file_exists($dirdest . '/Contrat_' . $dirdest . '.pdf')) {
                        unlink($dirdest . '/Contrat_' . $dirdest . '.pdf');
                    }
//                    if (file_exists($dirdest . '/Contrat_' . $dirdest . '_Ex_Client.pdf')) {
//                        unlink($dirdest . '/Contrat_' . $dirdest . '_Ex_Client.pdf');
//                    }

                    $listoffiles = dol_dir_list($conf->contract->dir_output . '/' . $newref, 'files', 1, '^' . preg_quote($oldref, '/'));
                    foreach ($listoffiles as $fileentry) {
                        $dirsource = $fileentry['name'];
                        $dirdest = preg_replace('/^' . preg_quote($oldref, '/') . '/', $newref, $dirsource);
                        $dirsource = $fileentry['path'] . '/' . $dirsource;
                        $dirdest = $fileentry['path'] . '/' . $dirdest;
                        rename($dirsource, $dirdest);
                    }
                }
            }

            $this->updateField('ref', $ref);
            $this->addLog('Contrat validé');
            $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $this->getData('fk_soc'));
            $commercial = BimpCache::getBimpObjectInstance("bimpcore", 'Bimp_User', $this->getData('fk_commercial_suivi'));

            //mailSyn2("Contrat " . $this->getData('ref'), $commercial->getData('email'), null, $body_mail);

            $this->mail($commercial->getData('email'), self::MAIL_VALIDATION);

            $success = 'Le contrat ' . $ref . " a été validé avec succès";

            $this->fetch($this->id);
            $this->actionGeneratePdf([], $success);
        }
        return array(
            'errors'   => $errors,
            'warnings' => array()
        );
    }

    public function actionValidate($data, &$success)
    {
        $result = array('errors' => array(), 'warnings' => array());
        $use_signature = (int) BimpCore::getConf('contrat_use_signatures', null, 'bimpcontract');
        $id_contact_signature = 0;
        $open_public_access = 0;

        if ($use_signature) {
            if (BimpTools::getArrayValueFromPath($data, 'sign_dist', 0)) {
                $id_contact_signature = (int) BimpTools::getArrayValueFromPath($data, 'id_contact_signature', 0);
                $init_docusign = (int) BimpTools::getArrayValueFromPath($data, 'init_docusign', 0);
//                    $open_public_access = (int) BimpTools::getArrayValueFromPath($data, 'open_public_access', 0);
                $email_content = BimpTools::getArrayValueFromPath($data, 'email_content', '');

                if ($init_docusign /* || $open_public_access */) {
                    if (!$id_contact_signature) {
                        return array(
                            'errors'   => array('Contact signataire obligatoire pour la signature à distance'),
                            'warnings' => array()
                        );
                    }
                } else {
                    return array(
                        'errors'   => array('Merci de choisir une méthode de signature à distance'),
                        'warnings' => array()
                    );
                }
            } else {
                $id_contact_signature = 0;
                $init_docusign = 0;
                $open_public_access = 0;
                $email_content = '';
            }
        }

        if (!count($result['errors'])) {
            if ($use_signature) {
                $signature_errors = $this->createSignature($init_docusign, $open_public_access, $id_contact_signature, $email_content, $result['warnings'], $success);

                if (count($signature_errors)) {
                    $result['warnings'][] = BimpTools::getMsgFromArray($signature_errors, 'Echec de la création de la fiche signature');
                }
            }
        }

        return $result;
    }

    public function actionMultiFact($data, &$success)
    {

        $errors = [];
        $warnings = [];
        $success = "";

        $ids = $data['id_objects'];

        $today = date('Y-m-d');

        foreach ($ids as $id) {
            $contrat = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat', $id);
            if (!BimpObject::objectLoaded($contrat)) {
                $warnings[] = 'Le contrat #' . $id . ' n\'existe plus';
                continue;
            }

            $statut = $contrat->getData('statut');
            if ($statut == self::CONTRAT_STATUS_BROUILLON) {
                $warnings[] = "Le contrat " . $contrat->getRef() . ' ne peut être facturé car il est au statut brouillon';
                continue;
            }

            if ($statut == self::CONTRAT_STATUS_CLOS) {
                $warnings[] = "Le contrat " . $contrat->getRef() . " ne peut être facturé car il est au statut clos";
                continue;
            }

            if (($statut == self::CONTRAT_STATUS_VALIDE || $statut == self::CONTRAT_STATUS_WAIT)) {
                $warnings[] = "Le contrat " . $contrat->getRef() . " ne peut être facturé car il n'est pas encore actif";
                continue;
            }

            $echeancier = BimpCache::findBimpObjectInstance('bimpcontract', 'BContract_echeancier', array(
                        'id_contrat' => $contrat->id
            ));

            if (BimpObject::objectLoaded($echeancier)) {
                $next_facture_date = $echeancier->getData('next_facture_date');

                if ($next_facture_date <= $today) {
                    $err = array();
                    $nextFactureData = $echeancier->getNextFactureData($err);

                    if (count($err)) {
                        $warnings[] = BimpTools::getMsgFromArray($err, 'Contrat ' . $contrat->getRef());
                    } else {
                        $id_facture = $echeancier->actionCreateFacture($nextFactureData);
                        if ($id_facture) {
                            $f = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_facture);
                            $s = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $contrat->getData('fk_soc'));
                            $comm = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $contrat->getData('fk_commercial_suivi'));
                            $msg = "Une facture a été créée sur le contrat " . $contrat->getRef() . ". Cette facture est encore au statut brouillon. Merci de la vérifier et de la valider.<br />";
                            $msg .= "Client : " . $s->dol_object->getNomUrl() . '<br />';
                            $msg .= "Contrat : " . $contrat->dol_object->getNomUrl() . "<br/>Commercial : " . $comm->getNomUrl() . "<br />";
                            $msg .= "Facture : " . $f->dol_object->getNomUrl();
                            mailSyn2("Facturation Contrat [" . $contrat->getRef() . "]", $this->email_facturation, BimpCore::getConf('devs_email'), $msg);
                            $success .= ($success ? '<br/>' : '') . "Le contrat " . $contrat->getRef() . " facturé avec succès";
                        }
                    }
                } else {
                    $warnings[] = "Le contrat " . $contrat->getRef() . " ne peut être facturé car la période de facturation n'est pas encore atteinte";
                }
            } else {
                $warnings[] = "Le contrat " . $contrat->getRef() . " ne peut être facturé car il n'a pas d'échéancier";
            }
        }

        return [
            'success'  => $success,
            'errors'   => $errors,
            'warnings' => $warnings
        ];
    }

    public function actionFusion($data, &$success)
    {

        $errors = [];

        $ids_selected_contrats = $data['id_objects'];
        $success = "Les contrats ont bien été fusionnés";
        $last_socid = 0;

        if (count($ids_selected_contrats) == 1) {
            $errors[] = "Vous ne pouvez pas fusionner qu'un seul contrat";
        }

        foreach ($ids_selected_contrats as $id) {
            $contrat = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat', $id);

            if ($contrat->getData('statut') == self::CONTRAT_STATUS_BROUILLON) {
                $errors[] = 'Le contrat ' . $contrat->getRef() . ' ne peut être fusionné car il est au statut brouillon';
            }

            if ($contrat->getData('fk_soc') != $last_socid && $last_socid > 0) {
                $errors[] = 'Les contrat ne peuvent êtres fusionné car ce n\'est pas le même client';
            }

            $last_socid = $contrat->getData('fk_soc');
        }

        if (!count($errors)) {
            
        }

        return [
            'errors'   => $errors,
            'warnings' => array(),
            'success'  => $success
        ];
    }

    public function actionRemoveContact($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Suppression du contact effectué avec succès';

        if (!$this->isLoaded()) {
            $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent';
        } else {
            if (!isset($data['id_contact']) || !(int) $data['id_contact']) {
                $errors[] = 'Contact à supprimer non spécifié';
            } else {
                if ($this->dol_object->delete_contact((int) $data['id_contact']) <= 0) {
                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la suppression du contact');
                }
            }
        }

        return array(
            'errors'            => $errors,
            'warnings'          => $warnings,
            'contact_list_html' => $this->renderContactsList()
        );
    }

    public function actionGeneratePdf($data, &$success = '', $errors = Array(), $warnings = Array())
    {
        global $langs;

        $success = "PDF contrat généré avec Succes";
        if ($this->dol_object->generateDocument('contrat_BIMP_maintenance', $langs) <= 0) {
            $errors = BimpTools::getErrorsFromDolObject($this->dol_object, $error = null, $langs);
            $warnings[] = BimpTools::getMsgFromArray($errors, 'Echec de la création du fichier PDF');
        }

        return [
            'errors'   => $errors,
            'warnings' => $warnings,
            '"success' => $success
        ];
    }

    public function actionGeneratePdfCourrier($data, &$success)
    {
        global $langs;
        $errors = $warnings = array();
        $success = "PDF courrier généré avec Succes";
        $this->dol_object->generateDocument('contrat_courrier_BIMP_renvois', $langs);
        return array('errors' => $errors, 'warnings' => $warnings);
    }

    public function actionDeleteSignature($data, &$success)
    {

        $errors = $warnings = array();
        $success_callback = 'bimp_reloadPage();';

        if ((int) $this->getData('id_signature')) {
            $signature = $this->getChildObject('signature');

            if (BimpObject::objectLoaded($signature)) {
                $errors = $signature->delete($warnings, $force_delete = false);
                if (!count($errors))
                    $success .= 'Objet signature supprimée avec succès<br/>';
            }

            if (!count($errors)) {
                $errors = $this->updateField('id_signature', 0);
                if (!count($errors))
                    $success .= 'Champs id_signature mis à 0<br/>';
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function actionOldToNew($data, &$success)
    {
        global $user;
        if (!$this->verifDureeForOldToNew())
            return "Ce contrat ne peut pas être transféré à la nouvelle version";

        if ($data['total'] == 0) {
            $date_start = new DateTime($data['date_start']);
//            $this->set('date_start', $date_start->format('Y-m-d'));
//            $this->set('periodicity', $data['periode']);
//            $this->set('duree_mois', $data['duree']);
            $this->dol_object->array_options['options_duree_mois'] = $data['duree'];
            $this->dol_object->array_options['options_date_start'] = $date_start->getTimestamp();
            $this->dol_object->array_options['options_periodicity'] = $data['periode'];
            $this->dol_object->array_options['options_entrepot'] = 8;
            $this->dol_object->update($user);
            $this->updateField('statut', 11);
            $echeancier = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_echeancier');
            $echeancier->set('id_contrat', $this->id);
            $next = new DateTime($data['date_facture_date']);
            $echeancier->set('next_facture_date', $next->format('Y-m-d 00:00:00'));
            $echeancier->set('validate', 0);
            $echeancier->set('statut', 1);
            $echeancier->set('commercial', $this->getData('fk_commercial_suivi'));
            $echeancier->set('client', $this->getData('fk_soc'));
            $echeancier->set('old_to_new', 1);
            $echeancier->create();
        }
    }

    public function getEcheancierData($num_renouvellement = 0)
    {
        $returnedArray = Array(
            'factures_send' => getElementElement('contrat', 'facture', $this->id),
            'reste_a_payer' => $this->reste_a_payer(),
            'reste_periode' => $this->reste_periode($num_renouvellement),
            'periodicity'   => $this->getData('periodicity')
        );

        return (object) $returnedArray;
    }

    public function actionCreateAvenant($data, &$success)
    {
        global $user;
        $avLetters = ["A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O"];

        $contrat_source = ($this->getData('contrat_source') ? $this->getData('contrat_source') : $this->id);
        $count = count($this->db->getRows('contrat_extrafields', 'contrat_source = ' . $contrat_source));
        $explodeRef = explode("_", $this->getData('ref'));
        $next_ref = $explodeRef[0] . '_' . $avLetters[$count];

        if ($clone = $this->dol_object->createFromClone($user, $this->getData('fk_soc'))) {
            $next_contrat = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat', $clone);
            addElementElement('contrat', 'contrat', $this->id, $next_contrat->id);
            $next_contrat->updateField('contrat_source', $contrat_source);
            $next_contrat->updateField('date_contrat', NULL);
            $next_contrat->updateField('ref_ext', $this->getData('ref'));
            $next_contrat->updateField('ref', $next_ref);
            $success = "L'avenant N°" . $next_ref . " a été créé avec succes";
        }
    }

    public function actionAvenant($data, &$success)
    {

        $data = (object) $data;
        $errors = [];
        $warnings = [];

        $new = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_avenant');
        $new->set('id_contrat', $this->id);
        $new->set('number_in_contrat', (int) $this->getData('nb_avenant') + 1);
        $this->updateField('nb_avenant', (int) $this->getData('nb_avenant') + 1);
        $new->create();

        return [
            'success'  => $success,
            'errors'   => $errors,
            'warnings' => $warnings
        ];
    }

    public function actionCreateEcheancier($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Echéancier créé avec succès';
        $sc = '';

        $errors = $this->createEcheancier($warnings);

        if (!count($errors) && !count($warnings)) {
            $sc = 'bimp_reloadPage();';
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $sc
        );
    }

    // Overrrides: 

    public function fetch($id, $parent = null)
    {
        $return = parent::fetch($id, $parent);
        //$this->autoClose();
        //verif des vieux fichiers joints
        $dir = DOL_DATA_ROOT . "/bimpcore/bimpcontract/BContract_contrat/" . $this->id . "/";
        $newdir = DOL_DATA_ROOT . "/contract/" . str_replace("/", "_", $this->getData('ref')) . "/";
        if (!$this->getChildObject('signature')->isLoaded()) {
            self::$status_list[self::CONTRAT_STATUS_VALIDE]['label'] = 'A envoyer à la signature';
        }
        if (!is_dir($newdir))
            mkdir($newdir);

        if (is_dir($dir) && is_dir($newdir)) {
            $ok = true;
            $res = scandir($dir);
            foreach ($res as $file) {
                if (!in_array($file, array(".", "..")))
                    if (!rename($dir . $file, $newdir . $file))
                        $ok = false;
            }
            if (!$ok)
                mailSyn2("Probléme déplacement fichiers", 'tommy@bimp.fr', null, 'Probléme dep ' . $dir . $file . " to " . $newdir . $file);
            else
                rmdir($dir);
        }

        return $return;
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = [];

        if (BimpTools::getValue('use_syntec') && !BimpTools::getValue('syntec')) {
            $errors[] = 'Vous devez rensseigner un indice syntec';
        }

        if ((BimpTools::getValue('tacite') == 1 || BimpTools::getValue('tacite') == 1 || BimpTools::getValue('tacite') == 3)) {
            if (BimpTools::getValue('duree_mois') != 12 && BimpTools::getValue('duree_mois') != 24 && BimpTools::getValue('duree_mois') != 36) {
                $errors[] = 'Vous ne pouvez pas demander un renouvellement TACITE pour des périodes différentes de (12, 24 ou 36 mois)';
            }
        }

        if (!count($errors)) {
            $errors = parent::create($warnings, $force_create);

            if (!count($errors)) {
                $client = $this->getChildObject('client');
                if (BimpObject::objectLoaded($client)) {
                    $client->setActivity('Création ' . $this->getLabel('of_the') . ' {{Contrat:' . $this->id . '}}');
                }
            }
        }

        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        if (BimpTools::getValue('type_piece')) {
            $id = 0;
            switch (BimpTools::getValue('type_piece')) {
                case 'propal':
                    $id = BimpTools::getValue('propal_client');
                    break;
                case 'commande':
                    $id = BimpTools::getValue('commande_client');
                    break;
                case 'facture_fourn':
                    $id = BimpTools::getValue('facture_fourn_client');
                    break;
            }
            if ($id == 0) {
                return "Il n'y à pas de pièce " . self::$true_objects_for_link[BimpTools::getValue('type_piece')] . ' pour ce client';
            } else {
                if (getElementElement(BimpTools::getValue('type_piece'), 'contrat', $id, $this->id)) {
                    return "La piece " . self::$true_objects_for_link[BimpTools::getValue('type_piece')] . ' que vous avez choisi est déjà liée à ce contrat';
                } else {
                    addElementElement(BimpTools::getValue('type_piece'), 'contrat', $id, $this->id);
                    $success = "La " . self::$true_objects_for_link[BimpTools::getValue('type_piece')] . " a été liée au contrat avec succès";
                }
            }
            return ['success' => $success, 'warnings' => $warnings, 'errors' => $errors];
        } else {

//            $relance_renouvellement = BimpTools::getValue('relance_renouvellement');
            //  *******************************************************
            //  L'ajout des logs bug (entrées vides intempestives), à corriger avant réactivation
            //  *******************************************************
//            if ($this->getData('statut') == self::CONTRAT_STATUS_ACTIVER && (BimpTools::getValue('periodicity') != $this->getInitData('periodicity'))) {
//                $log = "Changement de la périodicitée de facturation de <strong>" . self::$period[$this->getInitData('periodicity')] . "</strong> à <strong>";
//                $log .= self::$period[BimpTools::getValue('periodicity')] . "</strong>";
//                $this->addLog($log);
//            }
//
//            if (BimpTools::getValue('relance_renouvellement') != $this->getInitData('relance_renouvellement') && $this->getData('statut') != self::CONTRAT_STATUS_BROUILLON) {
//                $new_state = (BimpTools::getValue('relance_renouvellement') == 0) ? 'NON' : 'OUI';
//                $this->addLog('Changement statut relance renouvellement à : ' . $new_state);
//            }
//            if (BimpTools::getValue('facturation_echu') != $this->getInitData('facturation_echu') && $this->getData('statut') != self::CONTRAT_STATUS_BROUILLON) {
//                $new_state = (BimpTools::getValue('facturation_echu') == 0) ? 'NON' : 'OUI';
//                $this->addLog('Changement statut facturation à terme échu à : ' . $new_state);
//            }
//            if (BimpTools::getValue('label') != $this->getInitData('label') && $this->getData(('statut')) != self::CONTRAT_STATUS_BROUILLON) {
//                $this->addLog('Nouveau label contrat: ' . BimpTools::getValue('label'));
//            }
//            if (BimpTools::getValue('date_start') != $this->getInitData('date_start') && $this->getData('statut') == self::CONTRAT_STATUS_ACTIVER) {
//                $echeancier = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_echeancier');
//                if ($echeancier->find(['id_contrat' => $this->id], 1)) {
//                    $errors[] = $echeancier->updateField("next_facture_date", BimpTools::getValue('date_start') . ' 00:00:00');
//                }
//                $this->addLog("Date d'effet du contrat changer à " . BimpTools::getValue('date_start'));
//            }
            // Maj de la signature si nécessaire: 
//            if ((int) $this->getData('id_signature')) {
//                $signature = $this->getChildObject('signature');
//                if (BimpObject::objectLoaded($signature)) {
//                    if (!(int) $signature->isSigned()) {
//                        $id_contact = $this->getcontact();
//
//                        if ($id_contact != (int) $signature->getData('id_contact')) {
//                            $contact = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $id_contact);
//                            if (BimpObject::objectLoaded($contact)) {
//                                $id_client = (int) $contact->getData('fk_soc');
//
//                                if ((int) $signature->getData('id_contact') != $id_contact or
//                                        (int) $signature->getData('id_client') != $id_client) {
//                                    $signature->set('id_contact', $id_contact);
//                                    $signature->set('id_client', $id_client);
//                                    $sw = array();
//                                    $signature->update($sw, true);
//                                }
//                            }
//                        }
//                    }
//                }
//            }

            return parent::update($warnings, $force_update);
        }
    }

    public function delete(&$warnings = array(), $force_delete = false)
    {

        $un_contrat = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat');

        if ($un_contrat->find(['next_contrat' => $this->id])) {
            $warnings = $un_contrat->updateField('next_contrat', null);
            $un_contrat->addLog("Contrat de renouvellement " . $this->getRef() . " supprimé");
        }

        return parent::delete($warnings, $force_delete);
    }

    // Public: 

    public function getNewTicketSupportOnClick()
    {
        if ($this->isLoaded()) {
            global $userClient;
            $contact = null;

            if (BimpObject::objectLoaded($userClient) && (int) $userClient->getData('id_contact')) {
                $contact = $userClient->getChildObject('contact');
            }

            $ticket = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_Ticket');
            return $ticket->getJsLoadModalForm('public_create_from_contrat', 'Nouveau ticket support (contrat ' . $this->getRef() . ')', array(
                        'fields' => array(
                            'id_contrat'       => (int) $this->id,
                            'id_client'        => (int) $this->getData('fk_soc'),
                            'id_user_client'   => (BimpObject::objectLoaded($userClient) ? (int) $userClient->id : 0),
                            'contact_in_soc'   => (BimpObject::objectLoaded($contact) ? $contact->getName() : ''),
                            'adresse_envois'   => (BimpObject::objectLoaded($contact) ? BimpTools::replaceBr($contact->displayFullAddress()) : ''),
                            'email_bon_retour' => (BimpObject::objectLoaded($userClient) ? $userClient->getData('email') : '')
                        )
            ));
        }

        return '';
    }

    public function getPublicUrlParams()
    {
        return 'tab=contrats&content=card&id_contrat=' . $this->id;
    }

    public function getPublicListPageUrlParams()
    {
        return 'tab=contrats';
    }

    public function getPublicListExtraButtons()
    {
        $buttons = array();

        if ($this->can('view') && $this->canClientViewDetail()) {
            $url = $this->getPublicUrl();

            if ($url) {
                $buttons[] = array(
                    'label'   => 'Voir le détail',
                    'icon'    => 'fas_eye',
                    'onclick' => 'window.location = \'' . $url . '\''
                );
            }
        }

        return $buttons;
    }

    public function getPublicActionsButtons()
    {
        $buttons = array();

        if ($this->isLoaded() && $this->isValide()) {
            $buttons[] = array(
                'label'   => 'Nouveau ticket support',
                'icon'    => 'fas_headset',
                'onclick' => $this->getNewTicketSupportOnClick()
            );
        }

        return $buttons;
    }

    public function renderDemandesList()
    {
        if ($this->isLoaded()) {
            if (BimpCore::isModuleActive('bimpvalidation')) {
                return BimpValidation::renderObjectDemandesList($this);
            } elseif (BimpCore::isModuleActive('bimpvalidateorder')) {
                BimpObject::loadClass('bimpvalidateorder', 'ValidComm');
                $objectName = ValidComm::getObjectClass($this);
                if ($objectName != -2) {
                    BimpObject::loadClass('bimpvalidateorder', 'ValidComm');
                    $demande = BimpCache::getBimpObjectInstance('bimpvalidateorder', 'DemandeValidComm');
                    $list = new BC_ListTable($demande);
                    $list->addFieldFilterValue('type_de_piece', $objectName);
                    $list->addFieldFilterValue('id_piece', (int) $this->id);

                    return $list->renderHtml();
                } else {
                    return '';
                }
            }
        }

        return BimpRender::renderAlerts('Impossible d\'afficher la liste des demande de validation (ID ' . $this->getLabel('of_the') . ' absent)');
    }

    // Signature DocuSign

    public function getSignatureDocFileName($doc_type = 'contrat', $signed = false, $file_idx = 0)
    {
        $ext = $this->getSignatureDocFileExt($doc_type, $signed);

        switch ($doc_type) {
            case 'contrat':
                $errors = array();
                return $this->getPdfNamePrincipal($signed, $ext);
        }

        return '';
    }

    public function getSignatureDocFileUrl($doc_type, $forced_context = '', $signed = false, $file_idx = 0)
    {
        if (!$this->isLoaded()) {
            return '';
        }
        $context = BimpCore::getContext();

        if ($forced_context) {
            $context = $forced_context;
        }

        $file_name = $this->getSignatureDocFileName($doc_type, $signed);

        if ($file_name) {
            switch ($doc_type) {
                case 'contrat':
                    return $this->getFileUrl($file_name);
            }
        }

        return '';
    }

    public function onSigned($bimpSignature)
    {
        $errors = array();
        $success = '';
        $data_post = array();
        $return_sign = $this->actionSigned($data_post, $success);
        $errors = BimpTools::merge_array($return_sign['errors'], $return_sign['errors']);

        return $errors;
    }

    public function getSignatureEmailContent($doc_type = '', $signature_type = null)
    {
        if (!$signature_type) {
            if (BimpTools::getPostFieldValue('init_docusign') && BimpCore::getConf('contrat_signature_allow_docusign', null, 'bimpcontract')) {
                $signature_type = 'docusign';
            } else {
                $signature_type = 'elec';
            }
        }

        BimpObject::loadClass('bimpcore', 'BimpSignature');
        return BimpSignature::getDefaultSignDistEmailContent($signature_type);
    }

    public function displaySignature()
    {
        $html = '';

        // Signatures non fonctionnelles sur les contrats pour le moments (remontées des params signature absente) 
//        if ((int) $this->getData('id_signature') < 1) {
//            if ($this->isActionAllowed('createSignature') && $this->canSetAction('createSignature')) {
//                $onclick = $this->getJsActionOnclick('createSignatureDocuSign', array(), array(
//                    'form_name' => 'create_signature_docu_sign'
//                ));
//
//                $html .= '<span class="warning">Non applicable</span>';
//                $html .= '&nbsp;&nbsp;';
//                $html .= '<span class="btn btn-default" onclick="' . $onclick . '">';
//                $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Créer signature';
//                $html .= '</span>';
//            }
//        }
//
//        if ((int) $this->getData('id_signature') > 0) {
//            $signature = $this->getChildObject('signature');
//
//            if (BimpObject::objectLoaded($signature)) {
//                $html .= $signature->getLink();
//            }
//        }

        return $html;
    }

    public function getDefaultContactInfo($field)
    {
        if (!isset($this->contact_external_customer)) {
            $id_contact = $this->dol_object->getIdContact('external', 'CUSTOMER')[0];
            $this->contact_external_customer = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', (int) $id_contact);
        }

        if (BimpObject::objectLoaded($this->contact_external_customer)) {
            return $this->contact_external_customer->getData($field);
        }

        return '';
    }

    public function getContactInfo($id_contact)
    {
        $html = '';

        if (0 < (int) $id_contact) {
            $contact = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $id_contact);
            if (BimpObject::objectLoaded($contact)) {
                $nom_client = $contact->getData('lastname');
                $prenom_client = $contact->getData('firstname');
                $fonction_client = $contact->getData('poste');
                $email_client = $contact->getData('email');
                $html .= $nom_client . $prenom_client;
            } else {
                $html .= "Contact inconnu";
            }
        } else {
            $html .= "Contact non renseigné";
        }
        $html .= time() . 'id = ' . $id_contact;
        return $html;
    }

    public function getSignatureContactCreateFormValues()
    {
        $client = $this->getChildObject('client');

        if (BimpObject::objectLoaded($client)) {
            $fields = array(
                'email' => $client->getData('email')
            );

            if (!$client->isCompany()) {
                $fields['address'] = $client->getData('address');
                $fields['zip'] = $client->getData('zip');
                $fields['town'] = $client->getData('town');
                $fields['fk_pays'] = $client->getData('fk_pays');
                $fields['fk_departement'] = $client->getData('fk_departement');
            }

            return array(
                'fields' => $fields
            );
        }
        return array();
    }

    public function getDefaultSignatureContact()
    {
        foreach (array('CUSTOMER'/* , 'SHIPPING', 'BILLING2', 'BILLING' */) as $type_contact) {
            $contacts = $this->dol_object->getIdContact('external', $type_contact);
            if (isset($contacts[0]) && $contacts[0]) {
                return (int) $contacts[0];
            }
        }

        return 0;
    }

    public function renderSignatureInitDocuSignInput()
    {
        $html = '';

        $errors = array();
        if (!$this->isDocuSignAllowed($errors)) {
            $html .= '<div class="danger">';
            $html .= BimpTools::getMsgFromArray($errors, 'Il n\'est pas possible d\'utiliser DocuSign pour la signature de ce contrat');
            $html .= '</div>';
            $html .= '<input type="hidden" value="0" name="init_docusign"/>';
        } else {
            $html .= BimpInput::renderInput('toggle', 'init_docusign', 1);
        }

        return $html;
    }

    public function renderSignatureOpenDistAccessInput()
    {
        $html = '';

        $errors = array();
        if (!$this->isSignDistAllowed($errors)) {
            $html .= '<div class="danger">';
            $html .= BimpTools::getMsgFromArray($errors, 'Il n\'est pas possible d\'utiliser la signature électronique à distance pour ce contrat');
            $html .= '</div>';
            $html .= '<input type="hidden" value="0" name="open_public_access"/>';
        } else {
            $html .= BimpInput::renderInput('toggle', 'open_public_access', 1);
        }

        return $html;
    }

    public function isDocuSignAllowed(&$errors = array(), &$is_required = false)
    {
        if (!(int) BimpCore::getConf('contrat_signature_allow_docusign', null, 'bimpcontract')) {
            $errors[] = 'Signature DocuSign non autorisée pour ce contrat';
            return 0;
        }

        $is_required = false;
        return 1;
    }

    public function isSignDistAllowed(&$errors = array())
    {
        $ds_errors = array();
        $ds_required = false;
        if ((int) $this->isDocuSignAllowed($ds_errors, $ds_required)) {
            if ($ds_required) {
                $errors[] = 'DocuSign requis pour la signature à distance de ce contrat';
                return 0;
            }
        }

        if (!(int) BimpCore::getConf('contrat_signature_allow_dist', null, 'bimpcontract')) {
            $errors[] = 'Signature éléctronique à distance non autorisée pour ce contrat';
            return 0;
        }

        return 1;
    }

    public function getSignatureParams($doc_type)
    {
        return self::$default_signature_params; // return  BimpTools::overrideArray(self::$default_signature_params, (array) $this->getData('signature_params'));
    }
}
