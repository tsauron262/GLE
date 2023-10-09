<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';

class BCT_Contrat extends BimpDolObject
{

    public $redirectMode = 4;
    public static $email_type = 'contract';
    public static $element_name = "contrat";
    public static $dol_module = 'contrat';
    public static $files_module_part = 'contract';
    public static $modulepart = 'contract';

    const STATUS_DRAFT = 0;
    const STATUS_VALIDATED = 1;
    const STATUS_CLOSED = 2;

    public static $status_list = Array(
        self::STATUS_DRAFT     => Array('label' => 'Brouillon', 'classes' => Array('warning'), 'icon' => 'fas_trash-alt'),
        self::STATUS_VALIDATED => Array('label' => 'Validé', 'classes' => Array('success'), 'icon' => 'fas_check'),
        self::STATUS_CLOSED    => Array('label' => 'Fermé', 'classes' => Array('danger'), 'icon' => 'fas_times')
    );

    // Types: 
    CONST CONTRAT_GLOBAL = "CT";
    CONST CONTRAT_DE_MAINTENANCE = 'CMA';
    CONST CONTRAT_SUPPORT_TELEPHONIQUE = 'CST';
    CONST CONTRAT_MONITORING = 'CMO';
    CONST CONTRAT_DE_SPARE = 'CSP';
    CONST CONTRAT_DE_DELEGATION_DE_PERSONEL = 'CDP';
    CONST CONTRAT_MONETIQUE = 'CMQ';
    CONST CONTRAT_ASMX = 'ASMX';

    public static $types = [
        self::CONTRAT_GLOBAL                    => ['label' => "Contrat global", 'classes' => [], 'icon' => 'globe'],
        self::CONTRAT_DE_MAINTENANCE            => ['label' => "Contrat de maintenance", 'classes' => [], 'icon' => 'cogs'],
        self::CONTRAT_SUPPORT_TELEPHONIQUE      => ['label' => "Contrat de support téléphonique", 'classes' => [], 'icon' => 'phone'],
        self::CONTRAT_MONITORING                => ['label' => "Contrat de monitoring", 'classes' => [], 'icon' => 'terminal'],
        self::CONTRAT_DE_SPARE                  => ['label' => "Contrat de spare", 'classes' => [], 'icon' => 'share'],
        self::CONTRAT_DE_DELEGATION_DE_PERSONEL => ['label' => "Contrat de délégation du personnel", 'classes' => [], 'icon' => 'male'],
        self::CONTRAT_MONETIQUE                 => ['label' => "Contrat monétique", 'classes' => [], 'icon' => 'fas_file-invoice-dollar'],
        self::CONTRAT_ASMX                      => ['label' => "Contrat ASMX", 'classes' => [], 'icon' => 'fas fa5-external-link-alt'],
    ];

    // Périodicités: 

    CONST CONTRAT_PERIOD_AUCUNE = 0;
    CONST CONTRAT_PERIOD_MENSUELLE = 1;
    CONST CONTRAT_PERIOD_BIMENSUELLE = 2;
    CONST CONTRAT_PERIOD_TRIMESTRIELLE = 3;
    CONST CONTRAT_PERIOD_SEMESTRIELLE = 6;
    CONST CONTRAT_PERIOD_ANNUELLE = 12;
    CONST CONTRAT_PERIOD_TOTAL = 1200;

    public static $periodicities = Array(
        self::CONTRAT_PERIOD_MENSUELLE     => 'Mensuelle',
        self::CONTRAT_PERIOD_BIMENSUELLE   => 'Bimestrielle',
        self::CONTRAT_PERIOD_TRIMESTRIELLE => 'Trimestrielle',
        self::CONTRAT_PERIOD_SEMESTRIELLE  => 'Semestrielle',
        self::CONTRAT_PERIOD_ANNUELLE      => 'Annuelle',
        self::CONTRAT_PERIOD_TOTAL         => 'Une fois',
        self::CONTRAT_PERIOD_AUCUNE        => 'Aucune',
    );

    // Délais d'intervention
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

    // Renouvellements
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

    // Droits user : 

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
                if ($user->admin && !count($linked_factures))
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

    public function canSetAction($action)
    {
        global $user;

        if ($user->admin) {
            return 1;
        }

        $status == self::CONTRAT_STATUS_WAIT && $user->rights->bimpcontract->to_validate;

        switch ($action) {
            case 'validation': 
                if ($user->rights->bimpcontract->to_validate) {
                    return 1;
                }
                return 0;
                
            case 'redefineEcheancier':
                return 0;
        }

        return parent::canSetAction($action);
    }

    // Getters booléens : 

    public function isDeletable($force_delete = false, &$errors = array())
    {
        if ((int) $this->getData('statut') != self::CONTRAT_STATUS_BROUILLON) {
            return 0;
        }

        return parent::isDeletable();
    }

    public function isActionAllowed($action, &$errors = []): int
    {
        $status = (int) $this->getData('statut');
        
        switch ($action) {
            case 'validation': 
                if ($status != self::STATUS_DRAFT) {
                    $errors[] = 'Ce contrat n\'est pas au satut brouillon';
                    return 0;
                }
                return 1;
                
            case 'createSignature':
            case 'createSignatureDocuSign':
                return !$this->getChildObject('signature')->isLoaded() and ((int) $this->getData('statut') == self::CONTRAT_STATUS_VALIDE || (int) $this->getData('statut') == self::CONTRAT_STATUS_ACTIVER_TMP);
        }
        return parent::isActionAllowed($action, $errors);
    }

    public function isContratDelegation()
    {
        return (substr($this->getRef(), 0, 3) == 'CDP') ? 1 : 0;
    }

    public function isFacturationAutoActive()
    {
        $echeancier = $this->getEcheancier();
        if (BimpObject::objectLoaded($echeancier)) {
            if ((int) $echeancier->getData('validate')) {
                return 1;
            }
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

    public function isContratActive()
    {
        if ($this->getData('statut') == 11) {
            return 1;
        }
        return 0;
    }

    public function isSigned()
    {
        if (!is_null($this->getData('date_contrat'))) {
            return 1;
        }
        return 0;
    }

    // Getters params: 

    public function getActionsButtons()
    {
        global $user;
        $buttons = Array();

        // Valider : 
        if ($this->isActionAllowed('validation') && $this->canSetAction('validation')) {
            $buttons[] = array(
                'label'   => 'Valider la conformité du contrat',
                'icon'    => 'fas_check',
                'onclick' => $this->getJsActionOnclick('validation', array(), array(
                    'confirm_msg' => 'Veuillez confirmer la validation du contrat'
                ))
            );
        }

        if ($this->isActionAllowed('redefineEcheancier') && $this->canSetAction('redefineEcheancier')) {
            $buttons[] = array(
                'label'   => 'Annuler renew (ADMIN)',
                'icon'    => 'fas_undo',
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
}
