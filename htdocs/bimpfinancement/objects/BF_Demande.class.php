<?php

require_once DOL_DOCUMENT_ROOT . '/bimpfinancement/BF_Lib.php';

class BF_Demande extends BimpObject
{

    const STATUS_NEW = -1;
    const STATUS_DRAFT = 0;
    const STATUS_VALIDATED = 1;
    const STATUS_ATTENTE = 2;
    const STATUS_ACCEPTED = 10;
    const STATUS_REFUSED = 20;
    const STATUS_CANCELED = 21;
    const STATUS_CANCELED_BY_SOURCE = 22;

    public static $status_list = array(
        self::STATUS_NEW                => array('label' => 'Nouvelle demande', 'far_file', 'classes' => array('info')),
        self::STATUS_DRAFT              => array('label' => 'Brouillon', 'icon' => 'far_file', 'classes' => array('warning')),
        self::STATUS_VALIDATED          => array('label' => 'Demande refinanceur à effectuer', 'icon' => 'fas_hourglass-start', 'classes' => array('warning')),
        self::STATUS_ATTENTE            => array('label' => 'Acceptation refinanceur en attente', 'icon' => 'fas_hourglass-start', 'classes' => array('warning')),
        self::STATUS_ACCEPTED           => array('label' => 'Acceptée', 'icon' => 'fas_check', 'classes' => array('success')),
        self::STATUS_REFUSED            => array('label' => 'Refusée', 'icon' => 'fas_times', 'classes' => array('danger')),
        self::STATUS_CANCELED           => array('label' => 'Annulée', 'icon' => 'fas_times', 'classes' => array('danger')),
        self::STATUS_CANCELED_BY_SOURCE => array('label' => 'Annulée par source externe', 'icon' => 'fas_times', 'classes' => array('danger'))
    );

    const DOC_NONE = 0;
    const DOC_GENERATED = 10;
    const DOC_SEND = 11;
    const DOC_ACCEPTED = 20;
    const DOC_REFUSED = 30;
    const DOC_CANCELLED = 31;

    public static $doc_status_list = array(
        self::DOC_NONE      => array('label' => 'Non généré', 'icon' => 'fas_exclamation-circle', 'classes' => array('info')),
        self::DOC_GENERATED => array('label' => 'A envoyer pour signature', 'icon' => 'fas_exclamation-circle', 'classes' => array('warning')),
        self::DOC_SEND      => array('label' => 'En attende de signature', 'icon' => 'fas_hourglass-start', 'classes' => array('warning')),
        self::DOC_ACCEPTED  => array('label' => 'Accepté / signé', 'icon' => 'fas_check', 'classes' => array('success')),
        self::DOC_REFUSED   => array('label' => 'Refusé', 'icon' => 'fas_times', 'classes' => array('danger')),
        self::DOC_CANCELLED => array('label' => 'Annulé', 'icon' => 'fas_times', 'classes' => array('danger'))
    );
    public static $doc_types = array(
        'devis'   => 'Offre de location',
        'contrat' => 'Contrat de location'
    );
    public static $durations = array(
        24 => '24 mois',
        36 => '36 mois',
        48 => '48 mois',
        60 => '60 mois'
    );
    public static $periodicities = array(
        1  => 'Mensuelle',
        3  => 'Trimestrielle',
        6  => 'Semestrielle',
        12 => 'Annuelle'
    );
    public static $calc_modes = array(
        0 => 'A terme échu',
        1 => 'A terme à échoir'
    );
    public static $default_devis_signature_params = array();
    public static $default_contrat_signature_params = array();
    public static $marges = array(
        0     => 12,
        1801  => 12,
        5001  => 11,
        12001 => 10,
        50001 => 10
    );
    public static $formules = array(
        'none' => 'Non définie',
        'evo'  => 'Formule évolutive',
        'dyn'  => 'Formule dynamique'
    );
    protected $values = null;
    protected $default_values = null;

    // Getters booléens:

    public function isEditable($force_edit = false, &$errors = [])
    {
        if (!$force_edit && (int) $this->getData('status') < 0) {
            return 0;
        }

        return 1;
    }

    public function isDeletable($force_delete = false, &$errors = [])
    {
        if ($force_delete) {
            return 1;
        }

        if ((int) $this->getData('id_main_source')) {
            return 0;
        }

        if ((int) $this->getData('status') <= 0) {
            return 1;
        }

        return 0;
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        if ($force_edit) {
            return 1;
        }

        $status = (int) $this->getData('status');
        switch ($field) {
            case 'marge_souhaitee':
            case 'tx_cession':
            case 'loyer_mensuel_evo_ht':
            case 'loyer_mensuel_dyn_ht':
            case 'loyer_mensuel_suppl_ht':
                if ($status < self::STATUS_ACCEPTED) {
                    return 0; // Car déternminé en auto dans un premier temps via refinanceur
                }

            case 'def_tx_cession':
            case 'duration':
            case 'periodicity':
            case 'mode_calcul':
            case 'vr_achat':
            case 'vr_vente':
                if ((int) $this->getData('devis_status') >= self::DOC_ACCEPTED) {
                    return 0;
                }
                return 1;

            case 'formule':
                if ((int) $this->getData('contrat_status') >= self::DOC_ACCEPTED) {
                    return 0;
                }
                return 1;
        }
        return parent::isFieldEditable($field, $force_edit);
    }

    public function areLinesEditable()
    {
        if (!$this->isLoaded()) {
            return 0;
        }

        if ((int) $this->getData('status')) {
            return 0;
        }

        return 1;
    }

    public function showDemandesRefinanceurs()
    {
        if ($this->isLoaded() && (int) $this->getData('status') > 0) {
            return 1;
        }
        return 0;
    }

    public function areDemandesRefinanceursEditable(&$errors = array())
    {
        if (!$this->isLoaded()) {
            return 0;
        }

        $status = (int) $this->getData('status');

        if ($status <= 0 || in_array($status, array(BF_Demande::STATUS_ACCEPTED, BF_Demande::STATUS_CANCELED))) {
            $errors[] = 'Le statut actuel de la demande de location ne permet pas d\'ajouter des demandes refinanceurs';
            return 0;
        }

        if ($this->isClosed()) {
            $errors[] = 'Cette demande de location est fermée';
            return 0;
        }

        return 1;
    }

    public function isActionAllowed($action, &$errors = [])
    {
        if (in_array($action, array('mergeDemandes'))) {
            return 1;
        }

        if (!$this->isLoaded($errors)) {
            return 0;
        }

        // La DF doit obligatoirement être prise en charge pour effectuer toute action 
        if ($action !== 'takeCharge' && !(int) $this->getData('id_user_resp')) {
            $errors[] = 'Cette demande de location n\'est pas prise en charge';
            return 0;
        }

        if (!in_array($action, array()) && $this->isClosed()) {
            $errors[] = 'Cette demande de location est fermée';
            return 0;
        }

        if (in_array($action, array('submitDevis', 'submitContrat', 'submitRefuse', 'submitCancel'))) {
            if (!$this->getData('id_main_source')) {
                $errors[] = 'Pas de source externe';
                return 0;
            }

            if ((int) $this->isClosed()) {
                $errors[] = $this->getLabel('this', 1) . ' est fermé' . $this->e();
                return 0;
            }
        }

        switch ($action) {
            case 'takeCharge':
                if ((int) $this->getData('id_user_resp')) {
                    $errors[] = 'Déjà pris en charge par un utilisateur';
                    return 0;
                }
                return 1;

            case 'cancel':
                if ((int) $this->getData('status') >= 20) {
                    $errors[] = 'Cette demande de location est déjà annulée ou refusée';
                    return 0;
                }

                if ((int) $this->getData('contrat_status') === self::DOC_ACCEPTED) {
                    $errors[] = 'Le contrat de location est au statut ' . $this->displayData('contrat_status', 'default', false, true);
                    return 0;
                }
                return 1;

            case 'reopen':
                if ((int) $this->getData('status') !== self::STATUS_CANCELED) {
                    $errors[] = ucfirst($this->getLabel('this')) . ' n\'est pas au statut ' . self::$status_list[self::STATUS_CANCELED]['label'];
                    return 0;
                }
                return 1;

            case 'generateDevisFinancement':
            case 'uploadDevisFinancement':
                if ((int) $this->getData('status') !== self::STATUS_ACCEPTED) {
                    $errors[] = ucfirst($this->getLabel('this')) . ' n\'est pas au statut ' . self::$status_list[self::STATUS_ACCEPTED]['label'];
                }

                $devis_status = (int) $this->getData('devis_status');
                if ($devis_status > 10 && $devis_status < 30) {
                    $errors[] = 'Le devis de location a déjà été généré et envoyé au client';
                }
                return (count($errors) ? 0 : 1);

            case 'generateContratFinancement':
            case 'uploadContratFinancement':
                if ((int) $this->getData('status') !== self::STATUS_ACCEPTED) {
                    $errors[] = ucfirst($this->getLabel('this')) . ' n\'est pas au statut ' . self::$status_list[self::STATUS_ACCEPTED]['label'];
                }

                $devis_status = (int) $this->getData('devis_status');
                if ($devis_status !== self::DOC_ACCEPTED) {
                    $errors[] = 'Le devis de location n\'est pas encore accepté par le client';
                }

                $contrat_status = (int) $this->getData('contrat_status');
                if ($contrat_status > 10 && $contrat_status < 30) {
                    $errors[] = 'Le contrat de location a déjà été généré';
                }
                return (count($errors) ? 0 : 1);

            case 'createSignatureDevis':
                if ((int) $this->getData('id_main_source')) {
                    $errors[] = 'La signature doit être proposée par la source externe';
                    return 0;
                }
                if ((int) $this->getData('devis_status') !== self::DOC_GENERATED) {
                    $errors[] = 'le devis de location n\'est pas au statut "généré"';
                    return 0;
                }
                if ((int) $this->getData('id_signature_devis')) {
                    $errors[] = 'La fiche signature du devis a déjà été créée';
                    return 0;
                }
                return 1;

            case 'createSignatureContrat':
                if ((int) $this->getData('id_main_source')) {
                    $errors[] = 'La signature doit être proposée par la source externe';
                    return 0;
                }
                if ((int) $this->getData('contrat_status') !== self::DOC_GENERATED) {
                    $errors[] = 'le contrat de location n\'est pas au statut "généré"';
                    return 0;
                }
                if ((int) $this->getData('id_signature_contrat')) {
                    $errors[] = 'La fiche signature du contrat a déjà été créée';
                    return 0;
                }
                return 1;

            case 'submitDevis':
                if (!(int) $this->getData('id_main_source')) {
                    $errors[] = 'Pas de source externe';
                    return 0;
                }

                $status = (int) $this->getData('status');
                if ($status !== self::STATUS_ACCEPTED) {
                    $errors[] = $this->getLabel('this', 1) . ' n\'est pas au statut "Accepté' . $this->e() . '"';
                    return 0;
                }
                if ((int) $this->getData('devis_status') !== self::DOC_GENERATED) {
                    $errors[] = 'Le devis de location n\'est pas en attente d\'envoi à ' . $this->displaySourceName();
                    return 0;
                }
                return 1;

            case 'submitContrat':
                if (!(int) $this->getData('id_main_source')) {
                    $errors[] = 'Pas de source externe';
                    return 0;
                }

                $file_name = $this->getSignatureDocFileName('contrat');
                if (!$file_name || !file_exists($this->getFilesDir() . $file_name)) {
                    $errors[] = 'Le contrat de location n\'a pas été généré';
                    return 0;
                }

                if ((int) $this->getData('contrat_status') !== self::DOC_GENERATED) {
                    $errors[] = 'Le contrat de location n\'est pas en attente d\'envoi à ' . $this->displaySourceName();
                    return 0;
                }
                return 1;

            case 'submitRefuse':
                if (!(int) $this->getData('id_main_source')) {
                    $errors[] = 'Pas de source externe';
                    return 0;
                }

                $status = (int) $this->getData('status');
                if ($status !== self::STATUS_REFUSED) {
                    $errors[] = $this->getLabel('this', 1) . ' n\'est pas au statut "Refusé' . $this->e() . '"';
                    return 0;
                }

                $main_source = $this->getSource();
                if (!BimpObject::objectLoaded($main_source)) {
                    $errors[] = 'La source #' . $this->getData('id_main_source') . ' n\'existe plus';
                    return 0;
                }

                if ((int) $main_source->getData('cancel_submitted')) {
                    $errors[] = 'Annulation déjà soumise à ' . $this->displaySourceName();
                    return 0;
                }
                return 1;

            case 'submitCancel':
                if (!(int) $this->getData('id_main_source')) {
                    $errors[] = 'Pas de source externe';
                    return 0;
                }

                $status = (int) $this->getData('status');
                if ($status !== self::STATUS_CANCELED) {
                    $errors[] = $this->getLabel('this', 1) . ' n\'est pas au statut "Annulé' . $this->e() . '"';
                    return 0;
                }

                $main_source = $this->getSource();
                if (!BimpObject::objectLoaded($main_source)) {
                    $errors[] = 'La source #' . $this->getData('id_main_source') . ' n\'existe plus';
                    return 0;
                }

                if ((int) $main_source->getData('refuse_submitted')) {
                    $errors[] = 'Refus déjà soumis à ' . $this->displaySourceName();
                    return 0;
                }

                return 1;
        }

        return parent::isActionAllowed($action, $errors);
    }

    public function isNewStatusAllowed($new_status, &$errors = array())
    {
        switch ($new_status) {
            case self::STATUS_VALIDATED:
                if ((int) $this->getData('status') !== self::STATUS_DRAFT) {
                    $errors[] = ucfirst($this->getLabel('this')) . ' n\'est pas au statut ' . self::$status_list[self::STATUS_DRAFT]['label'];
                    return 0;
                }
                $lines = $this->getLines('not_text');
                if (empty($lines)) {
                    $errors[] = 'Aucun élément à financer ajouté à cette demande';
                    return 0;
                }
                return 1;

            case self::STATUS_DRAFT:
                if ((int) $this->getData('status') !== self::STATUS_VALIDATED) {
                    $errors[] = ucfirst($this->getLabel('this')) . ' n\'est pas au statut ' . self::$status_list[self::STATUS_VALIDATED]['label'];
                    return 0;
                }
                return 1;
        }
        return parent::isNewStatusAllowed($new_status, $errors);
    }

    public function isDemandeValid(&$errors = array())
    {
        $id_user = (int) $this->getData('id_user_resp');

        if (!$id_user) {
            $errors[] = 'Utilisateur responsable non sélectionné';
        }

        if (!(int) $this->getData('periodicity')) {
            $errors[] = 'Périodicité non définie';
        }

        if (!(int) $this->getData('duration')) {
            $errors[] = 'Durée totale non définie';
        }

        if (!(float) $this->getData('loyer_mensuel_evo_ht')) {
            $errors[] = 'Loyer mensuel (form. évolutive) non défini';
        }

        if (!(float) $this->getData('loyer_mensuel_dyn_ht')) {
            $errors[] = 'Loyer mensuel (form. dynamique) non défini';
        }

        if (!(float) $this->getData('loyer_mensuel_suppl_ht')) {
            $errors[] = 'Loyer mensuel supplémentaire (form. dynamique) non défini';
        }

        if ((int) $this->getData('devis_status') === self::DOC_ACCEPTED) {
            if ($this->getData('formule') === 'none') {
                $errors[] = 'Formule non sélectionnéee';
            }
        }

        return (count($errors) ? 0 : 1);
    }

    public function isClosed()
    {
        return (int) $this->getData('closed');
    }

    public function hasSignature()
    {
        return (int) ($this->getData('id_signature_devis') || $this->getData('id_signature_contrat'));
    }

    public function isMergeable(&$errors = array())
    {
        if (!$this->isLoaded($errors)) {
            return 0;
        }

        if ((int) $this->getData('status') > 0) {
            $errors[] = 'Cette demande n\'est plus au statut brouillon';
            return 0;
        }

        return 1;
    }

    public function showLoyers()
    {
        if ((int) $this->getData('status') < self::STATUS_ACCEPTED) {
            return 0;
        }
        return 1;
    }

    // Getters Params: 

    public function getActionsButtons()
    {
        $buttons = array();

        if ($this->isActionAllowed('takeCharge') && $this->canSetAction('takeCharge')) {
            $buttons['take_charge'] = array(
                'label'   => 'Prendre en charge',
                'icon'    => 'fas_cogs',
                'onclick' => $this->getJsActionOnclick('takeCharge', array(), array())
            );
        }

        if ($this->isNewStatusAllowed(self::STATUS_VALIDATED) && $this->canSetStatus(self::STATUS_VALIDATED)) {
            $buttons['validate'] = array(
                'label'   => 'Valider les élements financés',
                'icon'    => 'fas_check',
                'onclick' => $this->getJsNewStatusOnclick(self::STATUS_VALIDATED, array(), array(
                    'confirm_msg'      => 'Veuillez confirmer',
                    'success_callback' => 'function($result, bimpAjax) {bimp_reloadPage()}'
                ))
            );
        }
        if ($this->isNewStatusAllowed(self::STATUS_DRAFT) && $this->canSetStatus(self::STATUS_DRAFT)) {
            $buttons['unvalidate'] = array(
                'label'   => 'Remettre en brouillon',
                'icon'    => 'fas_undo',
                'onclick' => $this->getJsNewStatusOnclick(self::STATUS_DRAFT, array(), array(
                    'confirm_msg'      => 'Veuillez confirmer',
                    'success_callback' => 'function($result, bimpAjax) {bimp_reloadPage()}'
                ))
            );
        }

        foreach (array('devis', 'contrat') as $doc_type) {
            $action = 'generate' . ucfirst($doc_type) . 'Financement';
            if ($this->isActionAllowed($action) && $this->canSetAction($action)) {
                $label = 'Générer';
                if (file_exists($this->getFilesDir() . $this->getSignatureDocFileName($doc_type))) {
                    $label .= ' à nouveau';
                }
                $label .= ' le ' . $doc_type . ' de location';

                $buttons['generate_' . $doc_type] = array(
                    'label'   => $label,
                    'icon'    => 'fas_cogs',
                    'onclick' => $this->getJsActionOnclick($action, array(), array(
                        'form_name' => 'generate_' . $doc_type
                    ))
                );
            }

            $action = 'upload' . ucfirst($doc_type) . 'Financement';
            if ($this->isActionAllowed($action) && $this->canSetAction($action)) {
                $label = 'Déposer';
                if (file_exists($this->getFilesDir() . $this->getSignatureDocFileName($doc_type))) {
                    $label .= ' à nouveau';
                }
                $label .= ' le ' . $doc_type . ' de location';
                $buttons['upload_' . $doc_type] = array(
                    'label'   => $label,
                    'icon'    => 'fas_file-download',
                    'onclick' => $this->getJsLoadModalForm('upload_' . $doc_type, 'Déposer le ' . $doc_type . ' de location')
                );
            }

            $action = 'createSignature' . ucfirst($doc_type);
            if ($this->isActionAllowed($action) && $this->canSetAction($action)) {
                $buttons['create_signature_' . $doc_type] = array(
                    'label'   => 'Créer la fiche signature du ' . $doc_type,
                    'icon'    => 'fas_signature',
                    'onclick' => $this->getJsActionOnclick($action, array(), array(
                        'form_name' => 'create_signature_' . $doc_type
                    ))
                );
            }
        }

        if ($this->isActionAllowed('cancel') && $this->canSetAction('cancel')) {
            $buttons['cancel'] = array(
                'label'   => 'Abandonner cette demande',
                'icon'    => 'fas_times-circle',
                'onclick' => $this->getJsActionOnclick('cancel', array(), array(
                    'form_name' => 'cancel'
                ))
            );
        }

        if ($this->isActionAllowed('reopen') && $this->canSetAction('reopen')) {
            $buttons['reopen'] = array(
                'label'   => 'Réouvrir cette demande',
                'icon'    => 'fas_redo',
                'onclick' => $this->getJsActionOnclick('reopen', array(), array())
            );
        }

//        if ((int) $this->getData('id_main_source')) {
//            if (isset($buttons['generate_devis'])) {
//                $buttons['generate_devis']['onclick'] = $this->getJsActionOnclick('generateDevisFinancement', array(
//                    'create_signature' => 0
//                        ), array(
//                    'confirm_msg' => 'Veuillez confirmer'
//                ));
//            }
//
//            if (isset($buttons['generate_contrat'])) {
//                $buttons['generate_contrat']['onclick'] = $this->getJsActionOnclick('generateContratFinancement', array(
//                    'create_signature' => 0
//                        ), array(
//                    'confirm_msg' => 'Veuillez confirmer'
//                ));
//            }
//        }

        if ($this->isActionAllowed('submitDevis') && $this->canSetAction('submitDevis')) {
            $buttons[] = array(
                'label'   => 'Envoyer le devis à ' . $this->displaySourceName(),
                'icon'    => 'fas_arrow-circle-right',
                'onclick' => $this->getJsActionOnclick('submitDevis', array(), array(
                    'confirm_msg' => 'Veuillez confirmer'
                ))
            );
        }

        if ($this->isActionAllowed('submitContrat') && $this->canSetAction('submitContrat')) {
            $buttons[] = array(
                'label'   => 'Envoyer le contrat à ' . $this->displaySourceName(),
                'icon'    => 'fas_arrow-circle-right',
                'onclick' => $this->getJsActionOnclick('submitContrat', array(), array(
                    'confirm_msg' => 'Veuillez confirmer'
                ))
            );
        }

        if ($this->isActionAllowed('submitCancel') && $this->canSetAction('submitCancel')) {
            $buttons[] = array(
                'label'   => 'Soumettre l\'abandon à ' . $this->displaySourceName(),
                'icon'    => 'fas_arrow-circle-right',
                'onclick' => $this->getJsActionOnclick('submitCancel', array(), array(
                    'form_name' => 'submit_cancel'
                ))
            );
        }

        if ($this->isActionAllowed('submitRefuse') && $this->canSetAction('submitRefuse')) {
            $buttons[] = array(
                'label'   => 'Soumettre le refus à ' . $this->displaySourceName(),
                'icon'    => 'fas_arrow-circle-right',
                'onclick' => $this->getJsActionOnclick('submitRefuse', array(), array(
                    'form_name' => 'submit_refuse'
                ))
            );
        }

        return $buttons;
    }

    public function getListExtraButtons()
    {
        $buttons = array();

        if ($this->isActionAllowed('takeCharge') && $this->canSetAction('takeCharge')) {
            $url = $this->getUrl();
            $buttons[] = array(
                'label'   => 'Prendre en charge',
                'icon'    => 'fas_cogs',
                'onclick' => $this->getJsActionOnclick('takeCharge', array(), array(
                    'success_callback' => 'function() {window.open(\'' . $url . '\');}'
                ))
            );
        }

        return $buttons;
    }

    public function getListExtraBulkActions()
    {
        $buttons = array();

        if ($this->isActionAllowed('mergeDemandes') && $this->canSetAction('mergeDemandes')) {
            $buttons[] = array(
                'label'   => 'Fusionner les demandes sélectionnées',
                'icon'    => 'fas_object-group',
                'onclick' => $this->getJsBulkActionOnclick('mergeDemandes', array(), array(
                    'form_name'     => 'merge_demandes',
                    'single_action' => 1
                ))
            );
        }

        return $buttons;
    }

    public function getDemandesToMergeErrorsMsgs()
    {
        $errors = array();

        $id_demandes = BimpTools::getPostFieldValue('id_objects', array());

        if (empty($id_demandes)) {
            $errors[] = 'Aucune demande sélectionnée';
        } else {
            foreach ($id_demandes as $id_demande) {
                $demande = BimpCache::getBimpObjectInstance('bimpfinancement', 'BF_Demande', $id_demande);

                if (!BimpObject::objectLoaded($demande)) {
                    $errors[] = 'La demande #' . $id_demande . ' n\'existe plus';
                } else {
                    $demande_errors = array();
                    if (!$demande->isMergeable($demande_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($demande_errors, 'Demande ' . $demande->getRef());
                    }
                }
            }
        }
        if (count($errors)) {
            return array(
                array(
                    'type'    => 'danger',
                    'content' => BimpTools::getMsgFromArray($errors, 'Les demandes sélectionnées ne peuvent pas être fusionnées')
                )
            );
        }

        return array();
    }

    public function getContratFormMsgs()
    {
        $msg = BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft');
        $msg .= '<b>Toutes les informations demandées ci-dessous sont obligatoires pour l\'établissement du contrat de location</b>';

        $is_company = 0;
        $siren = '';
        if ((int) $this->getData('id_main_source')) {
            $source = $this->getSource();
            if (BimpObject::objectLoaded($source)) {
                $client_data = $source->getData('client_data');
                $is_company = (int) BimpTools::getArrayValueFromPath($client_data, 'is_company', 0);
                $siren = BimpTools::getArrayValueFromPath($client_data, 'siren', '');
            }
        }

        if ($is_company) {
            $url = 'https://www.societe.com';

            if ($siren) {
                $url .= '/cgi-bin/search?champs=' . $siren;
            }

            $msg .= '<br/>';
            $msg .= '<div style="text-align: right; margin-top: 10px">';
            $msg .= '<span class="btn btn-default" onclick="window.open(\'' . $url . '\')">';
            $msg .= 'Obtenir des infos sur societe.com' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight');
            $msg .= '</span>';
            $msg .= '</div>';
        }

        return array(
            array(
                'type'    => 'warning',
                'content' => $msg
            )
        );
    }

    // Getters array: 

    public function getClientContactsArray($include_empty = true, $active_only = true)
    {
        $id_client = (int) $this->getData('id_client');

        if ($id_client) {
            return self::getSocieteContactsArray($id_client, $include_empty, '', $active_only);
        }

        return array();
    }

    public function getSupplierContactsArray($include_empty = true, $active_only = true)
    {
        $id_supplier = (int) $this->getData('id_supplier');

        if ($id_supplier) {
            return self::getSocieteContactsArray($id_supplier, $include_empty, '', $active_only);
        }

        return array();
    }

    public function getDemandeFacturesArray()
    {
        $factures = array();

        if ($this->isLoaded()) {
            $asso = new BimpAssociation($this, 'factures');

            $list = $asso->getAssociatesList();

            foreach ($list as $id_facture) {
                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_facture);
                if (BimpObject::objectLoaded($facture)) {
                    if ((int) $facture->getData('fk_statut') === 0) {
                        $factures[$id_facture] = $facture->getRef();
                    }
                }
            }
        }

        $factures[0] = 'Nouvelle facture';

        return $factures;
    }

    public function getDemandesSourcesArray()
    {
        $sources = array();
        $id_demandes = BimpTools::getPostFieldValue('id_objects', array());

        if (!empty($id_demandes)) {
            foreach ($id_demandes as $id_demande) {
                $demande = BimpCache::getBimpObjectInstance('bimpfinancement', 'BF_Demande', $id_demande);

                if (BimpObject::objectLoaded($demande)) {
                    if (!$demande->isMergeable()) {
                        $sources = array();
                        break;
                    }

                    foreach ($demande->getChildrenObjects('sources') as $source) {
                        $sources[$source->id] = 'Demande ' . $demande->getRef() . ' - ' . $source->displayOrigine(true, false, false);
                    }
                }
            }
        }


        return $sources;
    }

    // Getters montants:

    public function getNbLoyers()
    {
        if ((int) $this->getData('periodicity')) {
            return ((int) $this->getData('duration') / (int) $this->getData('periodicity'));
        }

        return 0;
    }

    public function getTotalDemandeHT()
    {
        $tot = $this->getData('montant_materiels') + (float) $this->getData('montant_services') + (float) $this->getData('montant_logiciels');
        return (float) $tot;
    }

    public function getCalcValues($recalculate = false, &$errors = array())
    {
        if (is_null($this->values) || $recalculate) {
            $total_materiels = $this->getData('montant_materiels');
            $total_services = $this->getData('montant_services') + $this->getData('montant_logiciels');
            $this->values = BFTools::getCalcValues($total_materiels, $total_services, (float) $this->getData('tx_cession'), (int) $this->getData('duration'), (float) $this->getData('marge_souhaitee') / 100, (float) $this->getData('vr_achat'), (int) $this->getData('mode_calcul'), (int) $this->getData('periodicity'), $errors);
        }

        return $this->values;
    }

    public static function getDefaultMargePercent($total_demande_ht = null)
    {
        $marge_percent = 0;
        foreach (static::$marges as $min_amount_ht => $percent) {
            if ($total_demande_ht < $min_amount_ht) {
                break;
            }

            $marge_percent = $percent;
        }

        return $marge_percent;
    }

    public function getDefaultTxCession($total_demande_ht = null)
    {
        if (is_null($total_demande_ht)) {
            $total_demande_ht = $this->getTotalDemandeHT();
        }

        $type_def = $this->getData('def_tx_cession');

        switch ($type_def) {
            case 'reel':
                $id_refin = $this->getSelectedDemandeRefinanceurData('id_refinanceur');
                if ($id_refin) {
                    $refin = BimpCache::getBimpObjectInstance('bimpfinancement', 'BF_Refinanceur', $id_refin);
                    if (BimpObject::objectLoaded($refin)) {
                        return (float) $refin->getTaux($total_demande_ht);
                    }
                }
                break;

            case 'moyen':
            default:
                BimpObject::loadClass('bimpfinancement', 'BF_Refinanceur');
                return BF_Refinanceur::getTauxMoyen($total_demande_ht);
        }

        return 0;
    }

    public function getDefaultValues($recalculate = false)
    {
        if (is_null($this->default_values) || $recalculate) {
            $total_demande_ht = $this->getTotalDemandeHT();
            $calc_values = $this->getCalcValues();

            $this->default_values = array(
                'vr_achat'               => (float) BimpCore::getConf('def_vr_achat', null, 'bimpfinancement'),
                'vr_vente'               => (float) BimpCore::getConf('def_vr_vente', null, 'bimpfinancement'),
                'marge_souhaitee'        => (float) $this->getDefaultMargePercent($total_demande_ht),
                'tx_cession'             => $this->getDefaultTxCession($total_demande_ht),
                'loyer_mensuel_evo_ht'   => BimpTools::getArrayValueFromPath($calc_values, 'loyer_evo_mensuel', 0),
                'loyer_mensuel_dyn_ht'   => BimpTools::getArrayValueFromPath($calc_values, 'loyer_dyn_mensuel', 0),
                'loyer_mensuel_suppl_ht' => BimpTools::getArrayValueFromPath($calc_values, 'loyer_dyn_suppl_mensuel', 0)
            );
        }

        return $this->default_values;
    }

    // getters données: 

    public function addNotificationNote($content, $type_author = BimpNote::BN_AUTHOR_USER, $email = '', $auto = 0, $visibility = BimpNote::BN_MEMBERS, $delete_on_view = 0)
    {
        $errors = array();

        if ((int) $this->getData('id_user_resp')) {
            $this->addNote($content, $visibility, 0, $auto, $email, $type_author, BimpNote::BN_DEST_USER, 0, (int) $this->getData('id_user_resp'), $delete_on_view);
        } else {
            $id_group = (int) BimpCore::getConf('id_group_commerciaux', null, 'bimpfinancement');

            if ($id_group) {
                $this->addNote($content, $visibility, 0, $auto, $email, $type_author, BimpNote::BN_DEST_GROUP, $id_group, 0, $delete_on_view);
            } else {
                $errors[] = 'Groupe commerciaux non défini';
            }
        }

        return $errors;
    }

    public function getNextRef()
    {
        $min_chars = 5;
        $max_ref = $this->db->getMax($this->getTable(), 'ref');

        if ($max_ref) {
            if (preg_match('/DF(\d{4})\-(\d+)/', $max_ref, $matches)) {
                $year = (int) $matches[1];
                $new_num = '';

                if ($year < date('Y')) {
                    $new_num = 1;
                } else {
                    $num = $matches[2];
                    $new_num = (string) ((int) $num + 1);
                }

                if (strlen($new_num) < $min_chars) {
                    $new_num = BimpTools::addZeros($new_num, $min_chars);
                }
                return 'DF' . date('Y') . '-' . $new_num;
            }
        } else {
            if (!(int) $this->db->getCount($this->getTable())) {
                return 'DF' . date('Y') . '-' . BimpTools::addZeros(1, $min_chars);
            }
        }

        return '';
    }

    public function getInputValue($field_name)
    {
        switch ($field_name) {
            case 'id_user_resp':
                if (!$this->isLoaded()) {
                    global $user;
                    return $user->id;
                }
                return $this->getData('id_user_resp');

            // Génération contrat: 
            case 'client_name':
            case 'client_is_company':
            case 'client_forme_juridique':
            case 'client_capital':
            case 'client_siren':
            case 'client_address':
            case 'client_representant':
            case 'client_repr_qualite':
            case 'client_sites':
                if ((int) $this->getData('id_main_source')) {
                    $source = $this->getSource();
                    if (BimpObject::objectLoaded($source)) {
                        $client_data = $source->getData('client_data');
                        switch ($field_name) {
                            case 'client_name':
                                return BimpTools::getArrayValueFromPath($client_data, 'nom', 0);

                            case 'client_is_company':
                                return (int) BimpTools::getArrayValueFromPath($client_data, 'is_company', 0);

                            case 'client_forme_juridique':
                                return BimpTools::getArrayValueFromPath($client_data, 'forme_juridique', '');

                            case 'client_capital':
                                return str_replace('&euro;', '€', BimpTools::getArrayValueFromPath($client_data, 'capital', 0));

                            case 'client_siren':
                                return BimpTools::getArrayValueFromPath($client_data, 'siren', 0);

                            case 'client_address':
                                return BimpTools::replaceBr($source->getClientFullAddress(0, 1));

                            case 'client_representant':
                                return $source->getSignataireName();

                            case 'client_repr_qualite':
                                return strtolower(BimpTools::getArrayValueFromPath($client_data, 'signataire/fonction', ''));

                            case 'client_sites':
                                return BimpTools::replaceBr($source->getAdressesLivraisons());
                        }
                    }
                } else {
                    switch ($field_name) {
                        case 'client_name':
                        case 'client_is_company':
                        case 'client_forme_juridique':
                        case 'client_type_entrepise':
                        case 'client_capital':
                        case 'client_siren':
                        case 'client_adress':
                        case 'client_representant':
                        case 'client_repr_qualite':
                        case 'client_sites':
                    }
                }
                return '';
        }

        return $this->getData($field_name);
    }

    public function getLines($types = null)
    {
        if ($this->isLoaded()) {
            BimpObject::loadClass('bimpfinancement', 'BF_Line');

            $filters = array();
            if (!is_null($types)) {
                if (is_string($types)) {
                    $type_code = $types;
                    $types = array();
                    switch ($type_code) {
                        case 'product':
                            $types[] = BF_Line::TYPE_PRODUCT;
                            break;

                        case 'free':
                            $types[] = BF_Line::TYPE_FREE;
                            break;

                        case 'text':
                            $types[] = BF_Line::TYPE_TEXT;
                            break;

                        case 'not_text':
                            $types[] = BF_Line::TYPE_PRODUCT;
                            $types[] = BF_Line::TYPE_FREE;
                            break;
                    }
                }

                if (is_array($types) && !empty($types)) {
                    $filters = array(
                        'type' => array(
                            'in' => $types
                        )
                    );
                }
            }

            return $this->getChildrenObjects('lines', $filters, 'position', 'asc');
        }

        return array();
    }

    public function getDefaultIdUserResp()
    {
        if (!$this->isLoaded()) {
            global $user;
            if (BimpObject::objectLoaded($user)) {
                return (int) $user->id;
            }
        }
        return 0;
    }

    public function getSelectedDemandeRefinanceurData($returned_field = 'id')
    {
        if ($this->isLoaded() && (int) $this->getData('status') === static::STATUS_ACCEPTED) {
            BimpObject::loadClass('bimpfinancement', 'BF_DemandeRefinanceur');
            $where = 'id_demande = ' . $this->id . ' AND status = ' . BF_DemandeRefinanceur::STATUS_SELECTIONNEE;
            return $this->db->getValue('bf_demande_refinanceur', $returned_field, $where);
        }

        return null;
    }

    public function getRemainingElementsToOrder()
    {
        $elements = array();

        if ($this->isLoaded()) {
            BimpObject::loadClass('bimpfinancement', 'BF_Line');
            $lines = $this->getLines('not_text');

            foreach ($lines as $line) {
                $qty = (float) $line->getData('qty');

                $line_commandes = $line->getData('commandes_fourn');
                if (is_array($line_commandes)) {
                    foreach ($line_commandes as $id_commande => $commande_qty) {
                        $qty -= (float) $commande_qty;
                    }
                }

                if ($qty > 0) {
                    $elements[(int) $line->id] = $qty;
                }
            }
        }

        return $elements;
    }

    public function getCommandesFournisseurData()
    {
        $commFourns = array();

        $lines = $this->getLines('not_text');

        foreach ($lines as $line) {
            $line_comm = $line->getData('commandes_fourn');
            if (is_array($line_comm)) {
                foreach ($line_comm as $id_comm => $qty) {
                    $comm = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', (int) $id_comm);
                    if (BimpObject::objectLoaded($comm)) {
                        $id_fourn = (int) $comm->getData('fk_soc');

                        if (!isset($commFourns[$id_fourn])) {
                            $commFourns[$id_fourn] = array();
                        }
                        if (!isset($commFourns[$id_fourn][(int) $id_comm])) {
                            $commFourns[$id_fourn][(int) $id_comm] = array(
                                'comm'  => $comm,
                                'lines' => array()
                            );
                        }
                        $commFourns[$id_fourn][(int) $id_comm]['lines'][(int) $line->id] = array(
                            'qty'  => (float) $qty,
                            'line' => $line
                        );
                    }
                }
            }
        }

        return $commFourns;
    }

    // Getters statics: 

    public static function getDocTypeLabel($doc_type)
    {
        if (isset(static::$doc_types[$doc_type])) {
            return static::$doc_types[$doc_type];
        }

        return $doc_type;
    }

    // Getters Sources: 

    public function getMainSource()
    {
        if ((int) $this->getData('id_main_source')) {
            return $this->getChildObject('main_source');
        }

        return null;
    }

    public function getSource($id_source = 'main', &$errors = array())
    {
        if ($id_source === 'main') {
            $id_source = (int) $this->getData('id_main_source');
        }

        if ((int) $id_source) {
            $source = BimpCache::getBimpObjectInstance('bimpfinancement', 'BF_DemandeSource', $id_source);

            if (BimpObject::objectLoaded($source)) {
                return $source;
            }

            $errors[] = 'La source #' . $id_source . ' n\'existe plus';
        } else {
            $errors[] = 'ID de la source non spécifié';
        }

        return null;
    }

    public function getMainSourceAPI(&$errors = array(), $check_validity = true)
    {
        $main_source = $this->getMainSource();

        if (BimpObject::objectLoaded($main_source)) {
            return $main_source->getAPI($errors, $check_validity);
        }

        return null;
    }

    public function getSourceClientFullAddress($id_source = 'main', $icon = true, $single_line = false)
    {
        if ($id_source === 'main') {
            $id_source = (int) $this->getData('id_main_source');
        }

        $source = $this->getSource($id_source);

        if (BimpObject::objectLoaded($source)) {
            return $source->getClientFullAddress($icon, $single_line);
        }

        return '';
    }

    public function getSourceClientInfosContact($id_source = 'main', $icon = true, $single_line = false)
    {
        if ($id_source === 'main') {
            $id_source = (int) $this->getData('id_main_source');
        }

        $source = $this->getSource($id_source);

        if (BimpObject::objectLoaded($source)) {
            return $source->getClientInfosContact($icon, $single_line);
        }

        return '';
    }

    public function getSourceCommercialInfosContact($id_source = 'main', $icon = true, $single_line = false)
    {
        if ($id_source === 'main') {
            $id_source = (int) $this->getData('id_main_source');
        }

        $source = $this->getSource($id_source);

        if (BimpObject::objectLoaded($source)) {
            return $source->getCommercialInfosContact($icon, $single_line);
        }

        return '';
    }

    // Affichages: 

    public function displaySourceName($id_source = 'main')
    {
        if ($id_source === 'main') {
            $id_source = (int) $this->getData('id_main_source');
        }

        if ($id_source) {
            $source = $this->getSource($id_source);

            if (BimpObject::objectLoaded($source)) {
                return $source->displayName();
            }
        }

        return '';
    }

    public function displayClient($with_popover_infos = false)
    {
        if ((int) $this->getData('id_client')) {
            return $this->displayData('id_client');
        }

        return $this->displaySourceClient('main', $with_popover_infos);
    }

    public function displaySourceClient($id_source = 'main', $with_popover_infos = false)
    {
        if ($id_source === 'main') {
            $id_source = (int) $this->getData('id_main_source');
        }

        $source = $this->getSource($id_source);

        if (BimpObject::objectLoaded($source)) {
            return $source->displayClient($with_popover_infos);
        }

        return '';
    }

    public function displaySourceCommercial($id_source = 'main', $with_popover_infos = false)
    {
        if ($id_source === 'main') {
            $id_source = (int) $this->getData('id_main_source');
        }

        $source = $this->getSource($id_source);

        if (BimpObject::objectLoaded($source)) {
            return $source->displayCommercial($with_popover_infos);
        }

        return '';
    }

    public function displayData($field, $display_name = 'default', $display_input_value = true, $no_html = false, $no_history = false)
    {
        if (in_array($field, array('status', 'devis_status', 'contrat_status'))) {
            if ((int) $this->getData('id_main_source')) {
                self::$status_list[self::STATUS_CANCELED_BY_SOURCE]['label'] = 'Annulée par ' . $this->displaySourceName();
                self::$doc_status_list[self::DOC_GENERATED]['label'] = 'A envoyer à ' . $this->displaySourceName();
                self::$doc_status_list[self::DOC_SEND]['label'] = 'En attente de traitement par ' . $this->displaySourceName();
            } else {
                self::$doc_status_list[self::DOC_GENERATED]['label'] = 'A envoyer au client';
            }
        }
        return parent::displayData($field, $display_name, $display_input_value, $no_html, $no_history);
    }

    public function displayDuration()
    {
        return $this->getData('duration') . ' mois';
    }

    public function displayTotalDemande()
    {
        $total = $this->getTotalDemandeHT();
        return '<span style="font-size: 14px; font-weight: bold">' . BimpTools::displayMoneyValue($total, 'EUR', 1, 0, 0, 2, 1) . '</span>';
    }

    public function displayTotalAFinancer()
    {
        return BimpTools::displayMoneyValue($this->getTotalAFinancer(), 'EUR', 1, 0, 0, 2, 1);
    }

    // Rendus HTML: 

    public function renderHeaderExtraLeft()
    {
        $html = '';
        if ($this->isLoaded()) {
            if ((int) $this->getData('id_main_source')) {
                $client_label = $this->displaySourceClient('main', true);
                if ($client_label) {
                    $html .= '<div style="margin-top: 10px">';
                    $html .= $client_label;
                    $html .= '</div>';
                }

                $comm_label = $this->displaySourceCommercial('main', true);
                if ($comm_label) {
                    $html .= '<div style="margin-top: 10px">';
                    $html .= BimpRender::renderIcon('fas_user', 'iconLeft');
                    $html .= 'Commercial ' . $this->displaySourceName() . ' : ' . $comm_label;
                    $html .= '</div>';
                }
            } else {
                $client = $this->getChildObject('client');
                if (BimpObject::objectLoaded($client)) {
                    $html .= '<b>Client : </b>' . $client->getLink();
                }
            }
        }

        if (!(int) $this->getData('id_main_source')) {
            // Messages signature devis: 
            $signature_devis = $this->getChildObject('signature_devis');
            if (BimpObject::objectLoaded($signature_devis)) {
                if (!$signature_devis->getData('signed') && (int) $signature_devis->getData('type') >= 0) {
                    $html .= '<div style="margin-top: 10px">';
                    $msg = BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft');
                    $msg .= '<a href="' . $signature_devis->getUrl() . '" target="_blank">Signature du devis de location en attente' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight') . '</a>';

                    $btn_html = $signature_devis->renderSignButtonsGroup();
                    if ($btn_html) {
                        $msg .= '<div style="margin-top: 8px; text-align: right">';
                        $msg .= $btn_html;
                        $msg .= '</div>';
                    }

                    $html .= BimpRender::renderAlerts($msg, 'warning');
                    $html .= '</div>';
                }
            }

            // Messages signature contrat: 
            $signature_contrat = $this->getChildObject('signature_contrat');
            if (BimpObject::objectLoaded($signature_contrat)) {
                if (!$signature_contrat->getData('signed') && (int) $signature_contrat->getData('type') >= 0) {
                    $html .= '<div style="margin-top: 10px">';
                    $msg = BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft');
                    $msg .= '<a href="' . $signature_contrat->getUrl() . '" target="_blank">Signature du devis de location en attente' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight') . '</a>';

                    $btn_html = $signature_contrat->renderSignButtonsGroup();
                    if ($btn_html) {
                        $msg .= '<div style="margin-top: 8px; text-align: right">';
                        $msg .= $btn_html;
                        $msg .= '</div>';
                    }

                    $html .= BimpRender::renderAlerts($msg, 'warning');
                    $html .= '</div>';
                }
            }
        }

        return $html;
    }

    public function renderHeaderExtraRight($no_div = false)
    {
        $html = '';

        $dir = $this->getFilesDir();

        foreach (array('devis', 'contrat') as $doc_type) {
            foreach (array(1, 0) as $signed) {
                $file_name = $this->getSignatureDocFileName($doc_type, $signed);
                if (file_exists($dir . $file_name)) {
                    $url = $this->getFileUrl($file_name);
                    if ($url) {
                        $label = ucfirst($doc_type) . ' de location';
                        $html .= '<a class="btn btn-default" href="' . $url . '" target="_blank">';
                        $html .= BimpRender::renderIcon('fas_file-pdf', 'iconLeft') . $label . ($signed ? ' (signé)' : '');
                        $html .= BimpRender::renderIcon('fas_external-link-alt', 'iconRight');
                        $html .= '</a>';
                    }
                    break;
                }
            }
        }

        return $html;
    }

    public function renderHeaderStatusExtra()
    {
        $html = '';

        if ($this->isClosed()) {
            $status = (int) $this->getData('status');
            $class = 'danger';
            if ($status >= 10 && $status < 20) {
                $class = 'success';
            }
            $html .= '<span class="' . $class . '">' . BimpRender::renderIcon('fas_times', 'iconLeft') . 'Fermée</span>';
        }

        if ((int) $this->getData('devis_status') > 0) {
            $html .= '<br/>Devis: ' . $this->displayData('devis_status', 'default', false, false);
        }
        if ((int) $this->getData('contrat_status') > 0) {
            $html .= '<br/>Contrat: ' . $this->displayData('contrat_status', 'default', false, false);
        }

        return $html;
    }

    public function renderCommandesInfos()
    {
        $html = '';

        $total = 0;
        $total_ordered = 0;
        $total_paid = 0;

        $lines = $this->getLines('not_text');

        foreach ($lines as $line) {
            $pa_ttc = (float) BimpTools::calculatePriceTaxIn((float) $line->getData('pa_ht'), (float) $line->getData('tva_tx'));
            $total += $pa_ttc * (float) $line->getData('qty');
            $total_ordered += $pa_ttc * (float) $line->getQtyOrdered();
        }

        $commandes = $this->getCommandesFournisseurData();

        foreach ($commandes as $id_fourn => $commandes) {
            foreach ($commandes as $comm_data) {
                foreach (BimpTools::getDolObjectLinkedObjectsList($comm_data['comm']->dol_object, $this->db) as $item) {
                    if ($item['type'] !== 'invoice_supplier') {
                        continue;
                    }

                    $facture_fourn_instance = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureFourn', (int) $item['id_object']);
                    if (BimpObject::objectLoaded($facture_fourn_instance)) {
                        $total_paid += $facture_fourn_instance->getTotalPaid();
                    }
                }
            }
        }

        $to_order = $total - $total_ordered;
        $to_pay = $total - $total_paid;

        $html .= '<table class="bimp_list_table">';
        $html .= '<tr>';
        $html .= '<th>Total Eléments financés</th>';
        $html .= '<td>' . BimpTools::displayMoneyValue($total, 'EUR', true) . '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<tr>';
        $html .= '<th>Total Commandé</th>';
        $html .= '<td>' . BimpTools::displayMoneyValue($total_ordered, 'EUR', true) . '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<th>Total Commandes payées</th>';
        $html .= '<td>' . BimpTools::displayMoneyValue($total_paid, 'EUR', true) . '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<th>Reste à commander</th>';
        $html .= '<td>' . BimpTools::displayMoneyValue($to_order, 'EUR', true) . '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<th>Reste à payer</th>';
        $html .= '<td>' . BimpTools::displayMoneyValue($to_pay, 'EUR', true) . '</td>';
        $html .= '</tr>';
        $html .= '</table>';

        return BimpRender::renderPanel('Infos', $html, '', array(
                    'type' => 'secondary',
                    'icon' => 'fas_info',
        ));
    }

    public function renderCommandesFournisseursList()
    {
        $html = '';

        $commandes_fourn = $this->getCommandesFournisseurData();
        $remains = $this->getRemainingElementsToOrder();

        $buttons = '';
        if (!is_array($commandes_fourn) || !count($commandes_fourn)) {
            $html .= BimpRender::renderAlerts('Aucune commmande fournisseur enregistée pour cette demande de location', 'info');
        } else {
            $view_id = 'BF_Demande_fournisseurs_view_' . $this->id;

            $buttons .= '<div style="display: none; text-align: right;" class="buttonsContainer commandes_fourn_modif_buttons">';
            $buttons .= '<button type="button" class="btn btn-default" onclick="cancelCommandesFournLinesModifs($(this), \'' . $view_id . '\', ' . $this->id . ');">';
            $buttons .= '<i class="' . BimpRender::renderIconClass('fas_undo') . ' iconLeft"></i>Annuler toutes les modifications';
            $buttons .= '</button>';

            $buttons .= '<button type="button" class="btn btn-primary" onclick="saveCommandesFournLinesModifs($(this), \'' . $view_id . '\', ' . $this->id . ');">';
            $buttons .= '<i class="' . BimpRender::renderIconClass('fas_save') . ' iconLeft"></i>Enregistrer toutes les modifications';
            $buttons .= '</button>';
            $buttons .= '</div>';

            $html .= $buttons;

            $html .= '<table class="bimp_list_table">';
            $html .= '<tbody>';

            foreach ($commandes_fourn as $id_fourn => $commandes) {
                $html .= '<tr class="fourn_row">';
                $fourn = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Fournisseur', (int) $id_fourn);
                if (!$fourn->isLoaded()) {
                    $html .= '<th>Fournisseur ' . $id_fourn . '</th>';
                    $html .= '<td>';
                    $html .= BimpRender::renderAlerts('Erreur: le fournisseur d\'ID ' . $id_fourn . ' n\'existe pas');
                    $html .= '</td>';
                } else {
                    if (isset($remains[$fourn->id]) && is_array($remains[$fourn->id])) {
                        foreach ($remains[$fourn->id] as $id_line => $remain_data) {
                            $html .= '<input type="hidden" id="fourn_' . $fourn->id . '_line_' . $id_line . '_remain_qty" value="' . (float) $remain_data['qty'] . '"/>';
                        }
                    }

                    $html .= '<th style="max-width: 2000px;">';
                    $html .= $fourn->getNomUrl(true, false, true, 'default');
                    $html .= '</th>';
                    $html .= '<td>';

                    if (!is_array($commandes) || !count($commandes)) {
                        $html .= BimpRender::renderAlerts('Aucune commande enregistrée pour ce fournisseur', 'info');
                    } else {
                        $html .= '<table class="objectSubList">';
                        $html .= '<thead>';
                        $html .= '<tr>';
                        $html .= '<th>Commande</th>';
                        $html .= '<th>Total TTC</th>';
                        $html .= '<th>Statut</th>';
                        $html .= '<th>PDF Commande</th>';
                        $html .= '<th>Facture(s)</th>';
                        $html .= '<th></th>';
                        $html .= '</tr>';
                        $html .= '</thead>';
                        foreach ($commandes as $commande_data) {
                            $commande = $commande_data['comm'];

                            $factures = array();

                            foreach (BimpTools::getDolObjectLinkedObjectsList($commande->dol_object, $this->db) as $item) {
                                if ($item['type'] !== 'invoice_supplier') {
                                    continue;
                                }

                                $facture_fourn_instance = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureFourn', (int) $item['id_object']);
                                if (BimpObject::objectLoaded($facture_fourn_instance)) {
                                    $factures[] = $facture_fourn_instance;
                                }
                            }

                            $html .= '<tr>';
                            $html .= '<td>' . $commande->getNomUrl(true, true, true, 'full') . '</td>';
                            $html .= '<td>' . BimpTools::displayMoneyValue($commande->getData('total_ttc'), 'EUR', true) . '</td>';
                            $html .= '<td>' . $commande->displayData('fk_statut') . '</td>';
                            $html .= '<td>';
                            $html .= $commande->displayPDFButton(true, false);
                            $html .= '</td>';
                            $html .= '<td>';
                            if (count($factures)) {
                                $html .= '<table>';
                                $html .= '<tbody>';
                                foreach ($factures as $fac_data) {
                                    $html .= '<tr>';
                                    $html .= '<td>' . $fac_data->getNomUrl(1, 1, 1, 'full') . '</td>';
                                    $html .= '<td>' . $fac_data->displayData('fk_statut') . '</td>';
                                    $html .= '<td>';
                                    if ((int) $fac_data->getData('paye')) {
                                        $html .= '<span class="success">' . BimpRender::renderIcon('fas_check', 'iconLeft') . 'Payée</span>';
                                    } else {
                                        $html .= '<span class="danger">' . BimpRender::renderIcon('fas_times', 'iconLeft') . 'Non payée</span>';
                                    }
                                    $html .= '</td>';
                                    $html .= '<td>' . $fac_data->displayPDFButton(true, false) . '</td>';
                                    $html .= '</tr>';
                                }
                                $html .= '</tbody>';
                                $html .= '</table>';
                            }
                            $html .= '</td>';
                            $html .= '<td class="buttons">';
                            $html .= '<span class="displayDetailButton btn btn-light-default" onclick="toggleBfCommandeFournDetailDisplay($(this));">';
                            $html .= '<i class="' . BimpRender::renderIconClass('fas_list') . ' iconLeft"></i>Détail';
                            $html .= '<i class="' . BimpRender::renderIconClass('fas_caret-up') . ' iconRight"></i>';
                            $html .= '</span>';
                            $html .= '</td>';
                            $html .= '</tr>';

                            $html .= '<tr class="commande_fourn_elements_rows">';
                            $html .= '<td colspan="5" style="padding: 10px 30px; border-top-color: #fff">';
                            $html .= '<table class="objectSubList">';
                            $html .= '<tbody>';

                            // TODO: A refondre - id_fournisseur remplacé par commandes_fourn (id_comm_fourn => qty) 
//                            $fourn_lines = $this->getChildrenObjects('lines', array('id_fournisseur' => (int) $id_fourn), 'position', 'asc');
//                            foreach ($fourn_lines as $fourn_line) {
//                                $line = $fourn_line;
//                                $qty = 0;
//                                if (array_key_exists($fourn_line->id, $commande_data['lines'])) {
//                                    $line = $commande_data['lines'][$fourn_line->id]['line'];
//                                    $qty = $commande_data['lines'][$fourn_line->id]['qty'];
//                                }
//
//                                if (BimpObject::objectLoaded($line)) {
//                                    if ((int) $commande->getData('fk_statut') !== 0 && !(float) $qty) {
//                                        continue;
//                                    }
//                                    $html .= '<tr class="commande_fourn_element_row fourn_' . $fourn->id . '_line_' . $line->id . (!$qty ? ' deactivated' : '') . '"';
//                                    $html .= ' data-id_fourn="' . $fourn->id . '"';
//                                    $html .= ' data-id_commande="' . $commande->id . '"';
//                                    $html .= ' data-id_bf_line="' . $line->id . '">';
//                                    $html .= '<td>' . $line->displayDescription() . '</td>';
//                                    $html .= '<td>Qté: ';
//                                    if ((int) $commande->getData('fk_statut') === 0) {
//                                        $max = $qty;
//                                        if (isset($remains[(int) $fourn->id][(int) $line->id])) {
//                                            $max += (float) $remains[(int) $fourn->id][(int) $line->id]['qty'];
//                                        }
//                                        $html .= BimpInput::renderInput('qty', 'fourn_' . $fourn->id . '_comm_' . $commande->id . '_line_' . $line->id . '_qty', $qty, array(
//                                                    'extra_class' => 'line_qty_input',
//                                                    'step'        => $line->getQtyStep(),
//                                                    'data'        => array(
//                                                        'initial_qty' => $qty,
//                                                        'data_type'   => 'number',
//                                                        'decimals'    => $line->getQtyDecimals(),
//                                                        'min'         => 0,
//                                                        'max'         => $max,
//                                                        'unsigned'    => 1
//                                                    )
//                                        ));
//                                        $html .= '<p class="inputHelp" style="display: inline-block">Max: <span class="qty_max_value">' . $max . '</span></p>';
//                                    } else {
//                                        $html .= $qty;
//                                    }
//                                    $html .= '</td>';
//                                    $html .= '</tr>';
//                                }
//                            }
                            $html .= '</tbody>';
                            $html .= '</table>';
                            $html .= '</td>';
                            $html .= '<td></td>';
                            $html .= '</tr>';
                        }
                        $html .= '</table>';
                    }
                    $html .= '</td>';
                }
                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table>';
        }

        return BimpRender::renderPanel('Commandes fournisseurs', $html, $buttons, array(
                    'type'           => 'secondary',
                    'icon'           => 'fas_cart-arrow-down',
                    'header_buttons' => array(
                        array(
                            'label'       => 'Nouvelle(s) commande(s) fournisseur',
                            'classes'     => array('btn', 'btn-default'),
                            'icon_before' => 'fas_plus-circle',
                            'attr'        => array(
                                'onclick' => $this->getJsActionOnclick('generateCommandesFourn', array(), array(
                                    'form_name'      => 'new_commandes_fourn',
                                    'on_form_submit' => 'function($form, extra_data) {return onCommandesFournFormSubmit($form, extra_data);}'
                                ))
                            )
                        )
                    )
        ));
    }

    public function renderNewCommandesFournInputs()
    {
        $html = '';

        $elements = $this->getRemainingElementsToOrder();

        if (!count($elements)) {
            $html .= BimpRender::renderAlerts('Il n\'y a aucun élément à ajouter à une commande fournisseur', 'warning');
        } else {
            foreach ($elements as $id_fourn => $lines) {
                $fourn = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Fournisseur', (int) $id_fourn);
                if (BimpObject::objectLoaded($fourn)) {
                    $html .= '<div class="fournisseur_container" style="margin-bottom: 15px">';
                    $label = $fourn->getData('code_fournisseur');
                    $label .= ($label ? ' - ' : '') . $fourn->getData('nom');
                    $html .= BimpInput::renderInput('check_list', 'fournisseurs', $id_fourn, array(
                                'items' => array($id_fourn => $label)
                    ));
                    $html .= '<div class="commande_fourn_lines">';
                    $html .= '<table class="bimp_list_table" style="margin-left: 30px">';
                    $html .= '<tbody>';
                    foreach ($lines as $line) {
                        $bf_line = BimpCache::getBimpObjectInstance('bimpfinancement', 'BF_Line', (int) $line['id_line'], $this);
                        if (BimpObject::objectLoaded($bf_line)) {
                            $html .= '<tr>';
                            $html .= '<td><input type="checkbox" class="fourn_line_check" name="fourn_' . $fourn->id . '_lines[]" value="' . $bf_line->id . '" checked/></td>';
                            $html .= '<td>';
                            $html .= $bf_line->displayDescription();
                            $html .= '</td>';
                            $html .= '<td>Qté: ';
                            $html .= BimpInput::renderInput('qty', 'line_' . $bf_line->id . '_qty', $line['qty'], array(
                                        'step' => $bf_line->getQtyStep(),
                                        'data' => array(
                                            'data_type' => 'number',
                                            'decimals'  => $bf_line->getQtyDecimals(),
                                            'min'       => 0,
                                            'max'       => $line['qty'],
                                            'unsigned'  => 1
                                        )
                            ));
                            $html .= '<p class="inputHelp" style="display: inline-block">Max: ' . $line['qty'] . '</p>';
                            $html .= '</td>';
                            $html .= '</tr>';
                        }
                    }
                    $html .= '</tbody>';
                    $html .= '</table>';
                    $html .= '</div>';
                    $html .= '</div>';
                } else {
                    $html .= BimpRender::renderAlerts('Le fournisseur d\'ID ' . $id_fourn . ' n\'existe pas');
                }
            }
        }

        return $html;
    }

    public function renderFacturesFraisList()
    {
        $html = '';

        if ($this->isLoaded()) {
            $asso = new BimpAssociation($this, 'factures');
            $list = $asso->getAssociatesList();

            if (count($list)) {
                $facture = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture');
                $bc_list = new BC_ListTable($facture, 'default', 1, null, 'Factures frais divers et loyers intercalaires', 'fas_file-invoice-dollar');
                $bc_list->addObjectAssociationFilter($this, $this->id, 'factures');
                $bc_list->addObjectChangeReload('BF_FraisDivers');
                $bc_list->addObjectChangeReload('BF_RentExcept');
                $html = $bc_list->renderHtml();
            }
        }

        return $html;
    }

    public function renderAllFacturesList()
    {
        $html = '';

        if ($this->isLoaded()) {
            $factures = array();

            if ((int) $this->getData('id_facture')) {
                $facture = $this->getChildObject('facture_banque');
                if (BimpObject::objectLoaded($facture)) {
                    $factures[] = array(
                        'type'      => 'Facture Banque',
                        'nom_url'   => $facture->getNomUrl(0, true, true, 'full'),
                        'date'      => $facture->displayData('datef'),
                        'status'    => $facture->displayData('fk_statut'),
                        'amount_ht' => $facture->displayData('total'),
                        'paid'      => $facture->displayPaid(),
                        'file'      => $facture->displayPDFButton(true, true)
                    );
                }
            }

            if ((int) $this->getData('id_facture_client')) {
                $facture = $this->getChildObject('facture_client');
                if (BimpObject::objectLoaded($facture)) {
                    $factures[] = array(
                        'type'      => 'Facture Client',
                        'nom_url'   => $facture->getNomUrl(0, true, true, 'full'),
                        'date'      => $facture->displayData('datef'),
                        'status'    => $facture->displayData('fk_statut'),
                        'amount_ht' => $facture->displayData('total'),
                        'paid'      => $facture->displayPaid(),
                        'file'      => $facture->displayPDFButton(true, true)
                    );
                }
            }

            if ((int) $this->getData('id_facture_fournisseur')) {
                $facture = $this->getChildObject('facture_fournisseur');
                if (BimpObject::objectLoaded($facture)) {
                    $factures[] = array(
                        'type'      => 'Facture Fournisseur',
                        'nom_url'   => $facture->getNomUrl(0, true, true, 'full'),
                        'date'      => $facture->displayData('datef'),
                        'status'    => $facture->displayData('fk_statut'),
                        'amount_ht' => $facture->displayData('total'),
                        'paid'      => $facture->displayPaid(),
                        'file'      => $facture->displayPDFButton(true, true)
                    );
                }
            }

            $asso = new BimpAssociation($this, 'factures');

            foreach ($asso->getAssociatesList() as $id_facture) {
                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_facture);
                if (BimpObject::objectLoaded($facture)) {
                    $factures[] = array(
                        'type'      => 'Facture Frais divers',
                        'nom_url'   => $facture->getNomUrl(0, true, true, 'full'),
                        'date'      => $facture->displayData('datef'),
                        'status'    => $facture->displayData('fk_statut'),
                        'amount_ht' => $facture->displayData('total'),
                        'paid'      => $facture->displayPaid(),
                        'file'      => $facture->displayPDFButton(true, true)
                    );
                }
            }

            $content .= '<table class="bimp_list_table">';
            $content .= '<thead>';
            $content .= '<tr>';
            $content .= '<th>Type</th>';
            $content .= '<th>Facture</th>';
            $content .= '<th>Date</th>';
            $content .= '<th>Statut</th>';
            $content .= '<th>Montant HT</th>';
            $content .= '<th>Payé</th>';
            $content .= '<th>Fichier PDF</th>';
            $content .= '</tr>';
            $content .= '</thead>';

            $content .= '<tbody>';

            if (count($factures)) {
                foreach ($factures as $fac) {
                    $content .= '<tr>';
                    $content .= '<td><strong>' . $fac['type'] . '</strong></td>';
                    $content .= '<td>' . $fac['nom_url'] . '</td>';
                    $content .= '<td>' . $fac['date'] . '</td>';
                    $content .= '<td>' . $fac['status'] . '</td>';
                    $content .= '<td>' . $fac['amount_ht'] . '</td>';
                    $content .= '<td>' . $fac['paid'] . '</td>';
                    $content .= '<td>' . $fac['file'] . '</td>';
                    $content .= '</tr>';
                }
            } else {
                $content .= '<tr>';
                $content .= '<td colspan="7" style="text-align: center">';
                $content .= BimpRender::renderAlerts('Il n\'y a aucune facture client enregistrée pour cette demande de location pour le moment', 'info');
                $content .= '</td>';
                $content .= '</tr>';
            }

            $content .= '</tbody>';
            $content .= '</table>';

            $html .= BimpRender::renderPanel('Factures clients', $content, '', array(
                        'type' => 'secondary',
                        'icon' => 'fas_file-invoice-dollar'
            ));

            // Factures fournisseurs: 
            $factures = array();
            $facture = null;
            $content = '';

            $commandes_fourn = $this->getCommandesFournisseurData();

            foreach ($commandes_fourn as $id_fourn => $commandes) {
                if (is_array($commandes) && count($commandes)) {
                    foreach ($commandes as $commande_data) {
                        $commande = $commande_data['comm'];
                        foreach (BimpTools::getDolObjectLinkedObjectsList($commande->dol_object, $this->db) as $item) {
                            if ($item['type'] !== 'invoice_supplier') {
                                continue;
                            }

                            $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureFourn', (int) $item['id_object']);
                            if (BimpObject::objectLoaded($facture)) {
                                $factures[] = array(
                                    'nom_url'   => $facture->getNomUrl(0, true, true, 'full'),
                                    'date'      => $facture->displayData('datef'),
                                    'status'    => $facture->displayData('fk_statut'),
                                    'amount_ht' => $facture->displayData('total_ttc'),
                                    'paid'      => $facture->displayPaid(),
                                    'file'      => $facture->displayPDFButton(true, true)
                                );
                            }
                        }
                    }
                }
            }

            $content .= '<table class="bimp_list_table">';
            $content .= '<thead>';
            $content .= '<tr>';
            $content .= '<th>Type</th>';
            $content .= '<th>Facture</th>';
            $content .= '<th>Date</th>';
            $content .= '<th>Statut</th>';
            $content .= '<th>Montant HT</th>';
            $content .= '<th>Payé</th>';
            $content .= '<th>Fichier PDF</th>';
            $content .= '</tr>';
            $content .= '</thead>';

            if (count($factures)) {
                foreach ($factures as $fac) {
                    $content .= '<tr>';
                    $content .= '<td><strong>Facture fournisseur</strong></td>';
                    $content .= '<td>' . $fac['nom_url'] . '</td>';
                    $content .= '<td>' . $fac['date'] . '</td>';
                    $content .= '<td>' . $fac['status'] . '</td>';
                    $content .= '<td>' . $fac['amount_ht'] . '</td>';
                    $content .= '<td>' . $fac['paid'] . '</td>';
                    $content .= '<td>' . $fac['file'] . '</td>';
                    $content .= '</tr>';
                }
            } else {
                $content .= '<tr>';
                $content .= '<td colspan="7" style="text-align: center">';
                $content .= BimpRender::renderAlerts('Il n\'y a aucune facture fournisseur enregistrée pour cette demande de location pour le moment', 'info');
                $content .= '</td>';
                $content .= '</tr>';
            }

            $content .= '</tbody>';
            $content .= '</table>';

            $html .= BimpRender::renderPanel('Factures fournisseurs', $content, '', array(
                        'type' => 'secondary',
                        'icon' => 'fas_file-invoice-dollar'
            ));
        }

        return $html;
    }

    public function renderInfosFin()
    {
//        $this->checkObject();
//        $html .= '<table class="bimp_list_table">';
//        $html .= '<tr>';
//        $html .= '<th>Total emprunt</th>';
//        $html .= '<td>' . BimpTools::displayMoneyValue($this->getTotalEmprunt()) . '</td>';
//        $html .= '</tr>';
//        $html .= '<tr>';
//        $html .= '<th>Marge sur le financement</th>';
//        $html .= '<td>' . BimpTools::displayMoneyValue($this->marges['marge1']) . '</td>';
//        $html .= '</tr>';
//        $html .= '<tr>';
//        $html .= '<th>Marge loyers inter + frais divers</th>';
//        $html .= '<td>' . BimpTools::displayMoneyValue($this->marges['marge2']) . '</td>';
//        $html .= '</tr>';
//        $html .= '<tr>';
//        $html .= '<th>Marge totale</th>';
//        $html .= '<td>' . BimpTools::displayMoneyValue($this->marges['total_marge']) . '</td>';
//        $html .= '</tr>';
//        $html .= '</table>';

        $html .= BimpRender::renderAlerts('En cours de dev', 'warning');
        return BimpRender::renderPanel('Totaux', $html, '', array(
                    'type' => 'secondary',
                    'icon' => 'fas_euro-sign'
        ));
    }

    public function renderCommissionInputs($field_name)
    {
        if ($this->field_exists($field_name) && $this->isFieldEditable($field_name)) {
            $html = '';

            $bc_field = new BC_Field($this, $field_name, 1);
            $html .= $bc_field->renderHtml();

            $html .= '<div style="margin-top: 5px;padding-left: 4px">';
            $html .= BimpInput::renderInput('text', $field_name . '_amount', 0, array(
                        'data'        => array(
                            'data_type' => 'number',
                            'decimals'  => 2,
                            'min'       => 'none',
                            'max'       => 'none'
                        ),
                        'addon_right' => BimpRender::renderIcon('fas_euro-sign')
            ));
            $html .= '</div>';

            return $html;
        }

        return $this->displayData($field_name);
    }

    public function renderSourcesView()
    {
        $html = '';

        $sources = array();

        $id_main_source = (int) $this->getData('id_main_source');

        if ($id_main_source) {
            $sources[] = $id_main_source;
        }

        foreach ($this->getChildrenList('sources') as $id_source) {
            if (!in_array($id_source, $sources)) {
                $sources[] = $id_source;
            }
        }

        foreach ($sources as $id_source) {
            $title = '';
            $content = '';
            $source = BimpCache::getBimpObjectInstance('bimpfinancement', 'BF_DemandeSource', $id_source);
            if (BimpObject::objectLoaded($source)) {
                $title = $source->displayOrigine(true);
                if (count($sources) > 1 && $id_main_source == $id_source) {
                    $title .= ' (source principale)';
                }
                $content = $source->renderView();
            } else {
                $title = 'Source #' . $id_source;
                $content = BimpRender::renderAlerts('La source d\'ID ' . $id_source . ' n\'existe plus');
            }

            $html .= BimpRender::renderPanel($title, $content, '', array(
                        'type'     => 'secondary',
                        'foldable' => true,
                        'open'     => true
            ));
        }

        return $html;
    }

    public function renderCalcValues()
    {
        $html = '';

        $primary_color = BimpCore::getParam('colors/primary', '000000');

        $periodicity = (int) $this->getData('periodicity');
        $nb_mois = (int) $this->getData('duration');
        $materiel = $this->getData('montant_materiels');
        $services = $this->getData('montant_services') + $this->getData('montant_logiciels');
        $total = $materiel + $services;
        $marge = $this->getData('marge_souhaitee');
        $vr = $this->getData('vr_achat');
        $mode_calcul = (int) $this->getData('mode_calcul');

        BimpObject::loadClass('bimpfinancement', 'BF_DemandeRefinanceur');
        $refinanceurs = BF_DemandeRefinanceur::getRefinanceursArray(false, true);

        $html .= '<table class="bimp_list_table" style="text-align: center">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<td colspan="2" style="background-color: #fff"></td>';
        $html .= '<th colspan="' . ($periodicity > 1 ? '2' : '1') . '" style="text-align: center; border-left: 2px solid #' . $primary_color . '; background-color: #' . $primary_color . '; color: #fff">Formule Evolutive</th>';
        $html .= '<th colspan="' . ($periodicity > 1 ? '4' : '2') . '" style="text-align: center; background-color: #' . $primary_color . '; color: #fff">Formule Dynamique</th>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<th></th>';
        $html .= '<th style="text-align: center">Tx cession</th>';
        $html .= '<th style="text-align: center; border-left: 2px solid #' . $primary_color . '">Loyer mensuel HT</th>';

        if ($periodicity > 1) {
            $html .= '<th style="text-align: center">Loyer ' . BFTools::$periodicities_masc[$periodicity] . ' HT</th>';
        }
        $html .= '<th style="text-align: center; border-left: 2px solid #' . $primary_color . '">Loyer mensuel HT</th>';

        if ($periodicity > 1) {
            $html .= '<th style="text-align: center">Loyer ' . BFTools::$periodicities_masc[$periodicity] . ' HT</th>';
        }
        $html .= '<th style="text-align: center">Loyer suppl. mensuel HT</th>';
        if ($periodicity > 1) {
            $html .= '<th style="text-align: center">Loyer suppl. ' . BFTools::$periodicities_masc[$periodicity] . ' HT</th>';
        }
        $html .= '</tr>';
        $html .= '</thead>';

        $html .= '<tbody>';

        if ((int) $this->getData('status') === self::STATUS_ACCEPTED) {
            $html .= '<tr style="font-weight: bold">';
            $loyer_evo = $this->getData('loyer_mensuel_evo_ht');
            $loyer_dyn = $this->getData('loyer_mensuel_dyn_ht');
            $loyer_dyn_suppl = $this->getData('loyer_mensuel_suppl_ht');

            $html .= '<td style="text-align: left">Actuels</td>';
            $html .= '<td></td>';

            $html .= '<td style="border-left: 2px solid #' . $primary_color . '">' . BimpTools::displayMoneyValue($loyer_evo) . '</td>';
            if ($periodicity > 1) {
                $html .= '<td>' . BimpTools::displayMoneyValue($loyer_evo * $periodicity) . '</td>';
            }
            $html .= '<td style="border-left: 2px solid #' . $primary_color . '">' . BimpTools::displayMoneyValue($loyer_dyn) . '</td>';
            if ($periodicity > 1) {
                $html .= '<td>' . BimpTools::displayMoneyValue($loyer_dyn * $periodicity) . '</td>';
            }
            $html .= '<td>' . BimpTools::displayMoneyValue($loyer_dyn_suppl) . '</td>';
            if ($periodicity > 1) {
                $html .= '<td>' . BimpTools::displayMoneyValue($loyer_dyn_suppl * $periodicity) . '</td>';
            }

            $html .= '</tr>';
        }

        $total_tx = 0;
        $nb_refin = 0;
        foreach ($refinanceurs as $id_refin => $refin_name) {
            $refin = BimpCache::getBimpObjectInstance('bimpfinancement', 'BF_Refinanceur', $id_refin);
            if (BimpObject::objectLoaded($refin)) {
                $refin_errors = array();
                $tx = $refin->getTaux($total);
                $total_tx += $tx;
                $values = BFTools::getCalcValues($materiel, $services, $tx, $nb_mois, $marge / 100, $vr, $mode_calcul, $periodicity, $refin_errors);

                if ($tx) {
                    $nb_refin++;
                }
                $html .= '<tr>';

                $html .= '<td style="text-align: left">' . $refin_name . '</td>';
                $html .= '<td>' . BimpTools::displayFloatValue($tx) . ' %</td>';

                if (count($refin_errors)) {
                    $html .= '<td colspan="' . ($periodicity > 1 ? '6' : '3') . '">';
                    $html .= BimpRender::renderAlerts($refin_errors);
                    $html .= '</td>';
                } else {
                    $html .= '<td style="border-left: 2px solid #' . $primary_color . '">' . BimpTools::displayMoneyValue($values['loyer_evo_mensuel']) . '</td>';
                    if ($periodicity > 1) {
                        $html .= '<td>' . BimpTools::displayMoneyValue($values['loyer_evo']) . '</td>';
                    }
                    $html .= '<td style="border-left: 2px solid #' . $primary_color . '">' . BimpTools::displayMoneyValue($values['loyer_dyn_mensuel']) . '</td>';
                    if ($periodicity > 1) {
                        $html .= '<td>' . BimpTools::displayMoneyValue($values['loyer_dyn']) . '</td>';
                    }
                    $html .= '<td>' . BimpTools::displayMoneyValue($values['loyer_dyn_suppl_mensuel']) . '</td>';
                    if ($periodicity > 1) {
                        $html .= '<td>' . BimpTools::displayMoneyValue($values['loyer_dyn_suppl']) . '</td>';
                    }
                }

                $html .= '</tr>';
            }
        }

        if ($nb_refin > 1) {
            $html .= '<tr>';

            $refin_errors = array();
            $tx_moyen = $total_tx / $nb_refin;
            $values = BFTools::getCalcValues($materiel, $services, $tx_moyen, $nb_mois, $marge / 100, $vr, $mode_calcul, $periodicity, $refin_errors);

            $html .= '<td style="text-align: left; background-color: #F0F0F0!important">Tx cession moyen</td>';
            $html .= '<td style="background-color: #F0F0F0!important">' . BimpTools::displayFloatValue($tx_moyen) . ' %</td>';

            if (count($refin_errors)) {
                $html .= '<td colspan="' . ($periodicity > 1 ? '6' : '3') . '">';
                $html .= BimpRender::renderAlerts($refin_errors);
                $html .= '</td>';
            } else {
                $html .= '<td style="background-color: #F0F0F0!important; border-left: 2px solid #' . $primary_color . '">' . BimpTools::displayMoneyValue($values['loyer_evo_mensuel']) . '</td>';
                if ($periodicity > 1) {
                    $html .= '<td style="background-color: #F0F0F0!important">' . BimpTools::displayMoneyValue($values['loyer_evo']) . '</td>';
                }
                $html .= '<td style="background-color: #F0F0F0!important; border-left: 2px solid #' . $primary_color . '">' . BimpTools::displayMoneyValue($values['loyer_dyn_mensuel']) . '</td>';
                if ($periodicity > 1) {
                    $html .= '<td style="background-color: #F0F0F0!important">' . BimpTools::displayMoneyValue($values['loyer_dyn']) . '</td>';
                }
                $html .= '<td style="background-color: #F0F0F0!important">' . BimpTools::displayMoneyValue($values['loyer_dyn_suppl_mensuel']) . '</td>';
                if ($periodicity > 1) {
                    $html .= '<td style="background-color: #F0F0F0!important">' . BimpTools::displayMoneyValue($values['loyer_dyn_suppl']) . '</td>';
                }
            }

            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        return BimpRender::renderPanel(BimpRender::renderIcon('fas_calculator', 'iconLeft') . 'Loyers calculés', $html, '', array(
                    'type'     => 'secondary',
                    'foldable' => 1
        ));
    }

    public function renderOptionInputExtraContent($field_name)
    {
        $html = '';

        if ($this->isLoaded() && $this->field_exists($field_name) && $this->isFieldEditable($field_name) && $this->canEditField($field_name)) {
            $def_values = $this->getDefaultValues();
            if (isset($def_values[$field_name]) && (float) $def_values[$field_name]) {
                $cur_value = (float) $this->getData($field_name);
                $nb_decimals = (int) $this->getConf('fields/' . $field_name . '/decimals', 2);
                if (round($cur_value, $nb_decimals) != round($def_values[$field_name], $nb_decimals)) {
                    $value_str = BimpTools::displayFloatValue($def_values[$field_name]);
                    $field_label = $this->getConf('fields/' . $field_name . '/label', $field_name);

                    $onclick = 'var $c = $(this).findParentByClass(\'inputContainer\'); if ($.isOk($c)) {$c.find(\'input[name=' . $field_name . ']\').val(' . round($def_values[$field_name], $nb_decimals) . ').change();}';
                    $html .= '<span class="btn btn-default" onclick="' . $onclick . '">';
                    $html .= BimpRender::renderIcon('fas_undo', 'iconLeft') . 'Par défaut : <b>' . $value_str . '</b>';
                    $html .= '</span>';

                    $onclick = $this->getJsActionOnclick('resetDefaultValue', array(
                        'field_name' => $field_name
                            ), array(
                        'confirm_msg' => 'Veuillez confimer la mise à la valeur par défaut ' . strip_tags($value_str) . ' du champ \\\'\\\'' . $field_label . '\\\'\\\''
                    ));

                    $html .= '<span class="btn btn-default bs-popover" onclick="' . $onclick . '"';
                    $html .= BimpRender::renderPopoverData('Enregistrer directement la valeur par défaut');
                    $html .= '>';
                    $html .= BimpRender::renderIcon('fas_save');
                    $html .= '</span>';
                }
            }
        }

        return $html;
    }

    // Traitements: 

    public function checkObject($context = '', $field = '')
    {
        
    }

    public function calcLinesMontants()
    {
        $errors = array();

        if ($this->isLoaded()) {
            $montant_produits = 0;
            $montant_services = 0;
            $montant_logiciels = 0;

            $lines = $this->getLines('not_text');
            foreach ($lines as $line) {
                $total_ht = (float) $line->getTotalHT();
                switch ((int) $line->getData('product_type')) {
                    case BF_Line::PRODUIT:
                        $montant_produits += $total_ht;
                        break;

                    case BF_Line::SERVICE:
                        $montant_services += $total_ht;
                        break;

                    case BF_Line::LOGICIEL:
                        $montant_logiciels += $total_ht;
                        break;
                }
            }

            $up = false;
            if ((float) $montant_produits !== $this->getInitData('montant_materiels')) {
                $this->set('montant_materiels', $montant_produits);
                $up = true;
            }
            if ((float) $montant_services !== $this->getInitData('montant_services')) {
                $this->set('montant_services', $montant_services);
                $up = true;
            }
            if ((float) $montant_logiciels !== $this->getInitData('montant_logiciels')) {
                $this->set('montant_logiciels', $montant_logiciels);
                $up = true;
            }

            $new_marge = $this->getDefaultMargePercent($this->getTotalDemandeHT());
            if ($new_marge != (float) $this->getData('marge_souhaitee')) {
                $this->set('marge_souhaitee', $new_marge);
                $up = true;
            }

            if ($up) {
                $up_warnings = array();
                $up_errors = $this->update($up_warnings, true);

                if (count($up_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($up_errors, 'Des erreurs sont survenues lors de la mise à jour des montants de la demande de location');
                }

                if (count($up_warnings)) {
                    $errors[] = BimpTools::getMsgFromArray($up_warnings, 'Des erreurs sont survenues suite à la mise à jour des montants de la demande de location');
                }
            }
        }

        return $errors;
    }

    public function checkStatus(&$warnings = array())
    {
        $errors = array();
        if ($this->isLoaded()) {
            if ((int) $this->getData('closed')) {
                return;
            }
            $cur_status = (int) $this->getData('status');

            switch ($cur_status) {
                case self::STATUS_CANCELED:
                case self::STATUS_CANCELED_BY_SOURCE:
                    return;

                case self::STATUS_REFUSED:
                    if ((int) $this->getData('id_main_source')) {
                        $source = $this->getSource();
                        if (BimpObject::objectLoaded($source)) {
                            if ((int) $source->getData('refuse_submitted')) {
                                return;
                            }
                        }
                    }
                    break;
            }

            if ($cur_status >= self::STATUS_VALIDATED) {
                $new_status = self::STATUS_VALIDATED;
                $drs = $this->getChildrenObjects('demandes_refinanceurs');

                if (count($drs)) {
                    $has_attente = false;
                    $has_accepted = false;
                    $has_refused = false;

                    foreach ($drs as $dr) {
                        $dr_status = (int) $dr->getData('status');
                        if ($dr_status == BF_DemandeRefinanceur::STATUS_SELECTIONNEE) {
                            $has_accepted = true;
                        } elseif ($dr_status < 20 && $dr_status > 0) {
                            $has_attente = true;
                        } elseif ($dr_status == BF_DemandeRefinanceur::STATUS_REFUSEE) {
                            $has_refused = true;
                        }
                    }

                    if ($has_accepted) {
                        $new_status = self::STATUS_ACCEPTED;
                    } elseif ($has_attente) {
                        $new_status = self::STATUS_ATTENTE;
                    } elseif ($has_refused) {
                        $new_status = self::STATUS_REFUSED;
                    }
                }

                if ($new_status !== $cur_status) {
                    $errors = $this->setNewStatus($new_status, array(), $warnings, true);
                }
            }
        }
    }

    public function onChildSave($child)
    {
        if (is_a($child, 'BF_DemandeRefinanceur')) {
            $this->checkStatus();
        } elseif (is_a($child, 'BF_Line') && !isset($this->multiple_lines_adding)) {
            $this->calcLinesMontants();
        } elseif (is_a($child, 'BF_DemandeSource')) {
            if (!(int) $this->getData('id_main_source') && BimpObject::objectLoaded($child)) {
                $this->updateField('id_main_source', $child->id);
            }
        }

        return array();
    }

    public function onChildDelete($child, $id_child_deleted)
    {
        if (is_a($child, 'BF_DemandeRefinanceur')) {
            $this->checkStatus();
        } elseif (is_a($child, 'BF_Line')) {
            $this->calcLinesMontants();
        } elseif (is_a($child, 'BF_DemandeSource')) {
            if ($id_child_deleted == $this->getData('id_main_source')) {
                $sources = $this->getChildrenObjects('sources', array(
                    'id' => array(
                        'operator' => '!=',
                        'value'    => $id_child_deleted
                    )
                        ), 'id', 'asc');

                $id_main_source = 0;
                if (is_array($sources)) {
                    foreach ($sources as $source) {
                        $id_main_source = (int) $source->id;
                        break;
                    }
                }
                $this->updateField('id_main_source', $id_main_source);
            }
        }

        return array();
    }

    public function generateDocument($doc_type, $data = array(), &$warnings = array(), &$success = '')
    {
        $errors = array();

        if (!in_array($doc_type, array('devis', 'contrat'))) {
            $errors[] = 'Type de document à générer invalide: "' . $doc_type . '"';
            return $errors;
        }

        if ($this->isDemandeValid($errors)) {
            $options = array(
                'formules' => array()
            );

            foreach (BimpTools::getArrayValueFromPath($data, 'formules') as $formule) {
                $options['formules'][$formule] = 1;
            }

            global $db;

            if ((int) $this->getData('id_main_source')) {
                $source = $this->getSource('main', $errors);

                if (!count($errors)) {
                    $pdfClassName = ucfirst($doc_type) . 'FinancementProleasePDF';
                    require_once DOL_DOCUMENT_ROOT . '/bimpfinancement/extends/entities/prolease/pdf/' . $pdfClassName . '.php';

                    $client = $source->getData('client_data');
                    $client_data = array(
                        'ref'         => BimpTools::getArrayValueFromPath($client, 'ref', ''),
                        'is_company'  => (int) BimpTools::getArrayValueFromPath($client, 'is_company', 0),
                        'nom'         => BimpTools::getArrayValueFromPath($client, 'nom', ''),
                        'full_adress' => $source->getClientFullAddress(false, false)
                    );

                    $pdf = new $pdfClassName($db, $this, $client_data, $options);

                    $file_name = $this->getSignatureDocFileName($doc_type);
                    $file_path = $this->getFilesDir() . $file_name;

                    if (!$pdf->render($file_path, 'F')) {
                        $errors[] = BimpTools::getMsgFromArray($pdf->errors, 'Echec de la création du fichier');
                    } else {
                        $this->addObjectLog(ucfirst($doc_type) . ' de location généré', strtoupper($doc_type) . '_FIN_GENERE');
                        $up_errors = $this->updateField($doc_type . '_status', self::DOC_GENERATED);
                        if (count($up_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($up_errors, 'Echec de l\'enregistrement du nouveau statut du ' . $doc_type);
                        }
                    }
                }
            } else {
                $pdfClassName = ucfirst($doc_type) . 'FinancementPDF';
                require_once DOL_DOCUMENT_ROOT . '/bimpfinancement/pdf/' . $pdfClassName . '.php';

                $pdf = new $pdfClassName($db, $this, $options);

                $file_name = $this->getSignatureDocFileName($doc_type);
                $file_path = $this->getFilesDir() . $file_name;

                if (!$pdf->render($file_path, 'F')) {
                    $errors[] = BimpTools::getMsgFromArray($pdf->errors, 'Echec de la création du fichier');
                } else {
                    $doc_status = self::DOC_GENERATED;
                    $this->addObjectLog(ucfirst($doc_type) . ' de location généré', strtoupper($doc_type) . '_FIN_GENERE');

                    if ((int) BimpTools::getArrayValueFromPath($data, 'create_signature', 0)) {
                        $signature_errors = $this->createSignature($doc_type, $data, $warnings);

                        if (count($signature_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($signature_errors, 'Echec de la création de la fiche signature');
                        } else {
                            $success .= 'Fiche signature du ' . $doc_type . ' créée avec succès';
                            if ((int) BimpTools::getArrayValueFromPath($data, 'open_public_access', 1)) {
                                $doc_status = self::DOC_SEND;
                            }
                        }
                    }

                    $status_field_name = $doc_type . '_status';
                    $up_errors = $this->updateField($status_field_name, $doc_status);
                    if (count($up_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($up_errors, 'Echec de l\'enregistrement du nouveau statut du ' . $doc_type);
                    }
                }
            }
        }

        return $errors;
    }

    public function uploadDocument($doc_type, &$warnings = array())
    {
        $errors = array();

        if (!in_array($doc_type, array('devis', 'contrat'))) {
            $errors[] = 'Type de document à déposer invalide: "' . $doc_type . '"';
            return $errors;
        }

        if (!isset($_FILES['doc_file'])) {
            $errors[] = 'Fichier absent';
        } else {
            $file_ext = pathinfo($_FILES['doc_file']['name'], PATHINFO_EXTENSION);

            if ($file_ext !== 'pdf') {
                $errors[] = 'Le fichier doit obligatoirement être au format PDF';
            } else {
                require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
                $file_name = $this->getSignatureDocFileName($doc_type);
                $dir = $this->getFilesDir();

                $_FILES['doc_file']['name'] = $file_name;

                $result = dol_add_file_process($dir, 0, 0, 'doc_file');
                if ($result <= 0) {
                    $errors = BimpTools::getDolEventsMsgs(array('errors', 'warnings'));
                    if (!count($errors)) {
                        $errors[] = 'Echec de l\'enregistrement du fichier pour une raison inconnue';
                    }
                } else {
                    $doc_status = self::DOC_GENERATED;
                    $this->addObjectLog(ucfirst($doc_type) . ' de location déposé', strtoupper($doc_type) . '_FIN_DEPOSE');

                    $status_field_name = $doc_type . '_status';
                    $up_errors = $this->updateField($status_field_name, $doc_status);
                    if (count($up_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($up_errors, 'Echec de l\'enregistrement du nouveau statut du ' . $doc_type);
                    }
                }
                BimpTools::cleanDolEventsMsgs();
            }
        }

        return $errors;
    }

    public function submitDoc($doc_type)
    {
        $errors = array();

        if (!$doc_type) {
            $errors[] = 'Type de document non spécifié';
        } elseif (!array_key_exists($doc_type, static::$doc_types)) {
            $errors[] = 'Type de document invalide : ' . $doc_type;
        }

        $source = $this->getSource('main', $errors);

        if (!count($errors)) {
            $type_origine = $source->getData('type_origine');
            $id_origine = (int) $source->getData('id_origine');

            $file = $this->getFilesDir() . $this->getSignatureDocFileName($doc_type);

            if (!file_exists($file)) {
                $errors[] = 'PDF du ' . $this->getDocTypeLabel($doc_type) . ' trouvé';
            }

            if (!$type_origine) {
                $errors[] = 'Type de la pièce d\'origine absent';
            }

            if (!$id_origine) {
                $errors[] = 'ID de la pièce d\'origine absent';
            }

            if (!count($errors)) {
                $api = $this->getMainSourceAPI($errors);

                if (!count($errors)) {
                    $req_errors = array();
                    $doc_content = base64_encode(file_get_contents($file));
                    $signature_params = json_encode($this->getData('signature_' . $doc_type . '_params'));
                    $api->sendDocFinancement($this->id, $type_origine, $id_origine, $doc_type, $doc_content, $signature_params, $req_errors, $warnings);

                    if (count($req_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($req_errors, 'Echec de la requête');
                    } else {
                        $up_errors = $this->updateField($doc_type . '_status', self::DOC_SEND);
                        if (count($up_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($up_errors, 'Echec de la mise à jour du statut du ' . static::getDocTypeLabel($doc_type));
                        } else {
                            $this->addObjectLog(static::getDocTypeLabel($doc_type) . ' envoyé à ' . $this->displaySourceName(), strtoupper($doc_type) . '_SEND');
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function onDocSignedFromSource($doc_type, $doc_content)
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            $file_name = $this->getSignatureDocFileName($doc_type, true);

            if (!$file_name) {
                $errors[] = 'Type de document invalide: ' . $doc_type;
            } else {
                $dir = $this->getFilesDir();

                if ($dir && !is_dir($dir)) {
                    $dir_err = BimpTools::makeDirectories($dir);
                    if ($dir_err) {
                        $errors[] = 'Echec de la création du dossier de destination du fichie signé';
                    }
                }

                if (!count($errors)) {
                    $file = $this->getFilesDir() . $file_name;

                    if (!file_put_contents($file, base64_decode($doc_content))) {
                        $errors[] = 'Echec de l\'enregistrement du fichier signé';
                    } else {
                        $field_name = $doc_type . '_status';
                        if ($this->field_exists($field_name)) {
                            $errors = $this->updateField($field_name, self::DOC_ACCEPTED);
                        } else {
                            $errors[] = 'Type de document signé invalide: "' . $doc_type . '"';
                        }

                        if (!count($errors)) {
                            $this->addObjectLog(ucfirst($doc_type) . ' de location signé', strtoupper($doc_type) . '_SIGNE');
                            $this->addNote(ucfirst($doc_type) . ' de location signé', null, 0, 0, '', BimpNote::BN_AUTHOR_USER, BImpNote::BN_DEST_USER, 0, (int) $this->getData('id_user_resp'), 1);

//                            $user_resp = $this->getChildObject('user_resp');
//                            if (BimpObject::objectLoaded($user_resp)) {
//                                $email = $user_resp->getData('email');
//                                if ($email) {
//                                    $doc_ref = $this->getSignatureDocRef($doc_type);
//                                    $subject = ucfirst($doc_type) . ' de location ' . $doc_ref . ' signé par le client';
//                                    $msg = 'Bonjour,<br/><br/>';
//                                    $msg .= 'Le document signé est accessible sur la page de la demande de location ' . $this->getLink() . '<br/><br/>';
//                                    mailSyn2($subject, $email, '', $msg);
//                                }
//                            }
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function onDocRefused($doc_type, $note = '')
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            if (!array_key_exists($doc_type, static::$doc_types)) {
                $errors[] = 'Type de document invalide: ' . $doc_type;
            } else {
//                $this->set('closed', 1);
                $this->set($doc_type . '_status', self::DOC_REFUSED);

                $warnings = array();
                $up_errors = $this->update($warnings, true);
                if (count($up_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec de la mise à jour du statut du ' . $this->getDocTypeLabel($doc_type));
                } else {
                    $msg = ucfirst($this->getDocTypeLabel($doc_type)) . ' refusé' . ($note ? '.<br/><b>Raisons : </b>' . $note : '');
                    $this->addObjectLog($msg, strtoupper($doc_type) . '_REFUSED');
                    $this->addNote($msg, null, 0, 0, '', BimpNote::BN_AUTHOR_USER, BImpNote::BN_DEST_USER, 0, (int) $this->getData('id_user_resp'), 1);
                }
            }
        }

        return $errors;
    }

    public function onDemandeCancelledBySource($note = '')
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            if ((int) $this->getData('status') > 0 && !$this->isActionAllowed('cancel', $errors)) {
                return $errors;
            }

            $this->set('closed', 1);
            $this->set('status', self::STATUS_CANCELED_BY_SOURCE);

            $devis_status = (int) $this->getData('devis_status');
            if ($devis_status > 0 && $devis_status < 10) {
                $this->set('devis_status', self::DOC_CANCELLED);
            }

            $contrat_status = (int) $this->getData('contrat_status');
            if ($contrat_status > 0 && $contrat_status < 10) {
                $this->set('contrat_status', self::DOC_CANCELLED);
            }

            $warnings = array();
            $up_errors = $this->update($warnings, true);
            if (count($up_errors)) {
                $errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec de la mise à jour du statut de la demande de location');
            } else {
                $msg = 'Demande de location annulée par ' . $this->displaySourceName();
                if ($note) {
                    $msg .= '<br/><b>Motif : </b>' . $note;
                }
                $this->addObjectLog($msg, 'CANCELLED_BY_SOURCE');
                $this->addNote($msg, null, 0, 0, '', BimpNote::BN_AUTHOR_USER, BImpNote::BN_DEST_USER, 0, (int) $this->getData('id_user_resp'), 1);
            }
        }

        return $errors;
    }

    public function createCommandeFournisseur($id_entrepot, $id_fournisseur, $lines)
    {
        $errors = array();

        $fourn = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Fournisseur', (int) $id_fournisseur);

        if (!BimpObject::objectLoaded($fourn)) {
            $errors[] = 'Le fournisseur d\'ID ' . $id_fournisseur . ' n\'existe pas';
        } elseif (!is_array($lines) || !count($lines)) {
            $errors[] = 'Aucune ligne à ajouter à la commande';
        } else {
            foreach ($lines as $i => $line) {
                if ((float) $line['qty'] > 0) {
                    if (!(int) $line['id_line']) {
                        $errors[] = 'ID absent pour la ligne n° ' . ($i + 1);
                        unset($lines[$i]);
                    } else {
                        $bf_line = BimpCache::getBimpObjectInstance('bimpfinancement', 'BF_Line', (int) $line['id_line']);
                        if (!$bf_line->isLoaded()) {
                            $errors[] = 'La ligne d\'élément à financer d\'ID ' . $line['id_line'] . 'n\'existe pas';
                            unset($lines[$i]);
                        } else {
                            $lines[$i]['bf_line'] = $bf_line;
                        }
                    }
                }
            }
            if (!count($lines)) {
                $errors[] = 'Aucune ligne à ajouter à la commande';
            }
        }

        if (!count($errors)) {
            $commFourn = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFourn');
            $errors = $commFourn->validateArray(array(
                'entrepot'     => (int) $id_entrepot,
                'fk_soc'       => (int) $id_fournisseur,
                'note_private' => 'Demande de location ' . $this->id
            ));

            if (!count($errors)) {
                $errors = $commFourn->create();

                if (!count($errors)) {
                    foreach ($lines as $i => $line) {
                        $bf_line = $line['bf_line'];
                        $line_errors = $bf_line->createCommandeFournLine((int) $commFourn->id, (float) $line['qty']);

                        if (count($line_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de l\'ajout à la commande de la ligne n°' . ($i + 1));
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function traiteLinesFacture($facture, $totalFact)
    {
        $lines = $this->getChildrenObjects('lines', array(), 'position', 'asc');
        $totNonCache = $totCache = 0;

        foreach ($lines as $line) {
            if ($line->getData("in_contrat")) {
                $totNonCache += $line->getTotalLine();
            } else
                $totCache += $line->getTotalLine();
        }
//                if(($totNonCache + $totCache) != $total_emprunt)
//                    $errors[] = "Problémes dans les totaux !!! " . ($totNonCache + $totCache)." ".$total_emprunt;

        $coef = $totalFact / $totNonCache;

        $lines = $this->getChildrenObjects('lines', array(), 'position', 'asc');
        foreach ($lines as $lineT) {
            $line = $facture->getLineInstance();
            $line->reset();
            if ($lineT->getData("in_contrat")) {
                if (!$line->find(array(
                            'id_obj'             => (int) $facture->id,
                            'linked_id_object'   => $lineT->id,
                            'linked_object_name' => 'df_line'
                                ), true, true)) {
                    $line->validateArray(array(
                        'id_obj'             => (int) $facture->id,
                        'type'               => (int) ObjectLine::LINE_FREE,
                        'deletable'          => 1,
                        'editable'           => 1,
                        'remisable'          => 1,
                        'linked_id_object'   => (int) $lineT->id,
                        'linked_object_name' => 'df_line'
                    ));
                }

                $getDescSerials = $lineT->getSerialDesc();
                // Verif
                $line->desc = $getDescSerials->label . " " . $getDescSerials->serials;
                $line->qty = $lineT->getData("qty");
                $line->pu_ht = $lineT->getData("pu_ht") * $coef;
                $line->tva_tx = $lineT->getData("tva_tx");

                if (!$line->isLoaded()) {
                    $w = array();
                    $line_errors = $line->create($w, true);
                    if (count($line_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de l\'ajout de la ligne à la facture');
                    }
                } else {
                    $line_errors = $line->update();
                    if (count($line_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de la mise à jour de la ligne de facture');
                    }
                }
            } else {
                if ($line->find(array(
                            'id_obj'             => (int) $facture->id,
                            'linked_id_object'   => $lineT->id,
                            'linked_object_name' => 'df_line'
                                ), true, true))
                    $line->delete();
            }
        }
    }

    public function afterCreateNote($note)
    {
        if ((int) $this->getData('id_main_source') && $note->getData('visibility') >= BimpNote::BN_PARTNERS) {
            $errors = array();
            $source = $this->getSource('main', $errors);

            if (!count($errors)) {
                $type_origine = $source->getData('type_origine');
                $id_origine = (int) $source->getData('id_origine');

                if ($type_origine && $id_origine) {
                    $api = $this->getMainSourceAPI($errors);

                    if (!count($errors)) {
                        $api->newDemandeFinancementNote($this->id, $type_origine, $id_origine, $note->getData('content'));
                    }
                }
            }
        }
    }

    // Gestion signatures : 

    public function createSignature($doc_type, $data, &$warnings = array())
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            $id_contact = BimpTools::getArrayValueFromPath($data, 'id_contact_signature', (int) $this->getData('id_contact'));
            $open_public_acces = BimpTools::getArrayValueFromPath($data, 'open_public_access', 1);
            $email_content = BimpTools::getArrayValueFromPath($data, 'email_content', $this->getDefaultSignDistEmailContent($doc_type));

            $field_name = 'id_signature_' . $doc_type;

            if (!$field_name) {
                $errors[] = 'Type de signature invalide "' . $doc_type . '"';
            } elseif (!$this->field_exists($field_name)) {
                $errors[] = 'Signature non disponible';
            } else if ((int) $this->getData('id_signature_' . $doc_type)) {
                $errors[] = 'La fiche signature du ' . $doc_type . ' de financemnet a déjà été créée';
            } else {
                $id_client = (int) $this->getData('id_client');
                if (!$id_client) {
                    $errors[] = 'Client absent';
                } else {
                    $signature = BimpObject::createBimpObject('bimpcore', 'BimpSignature', array(
                                'obj_module' => 'bimpfinancement',
                                'obj_name'   => 'BF_Demande',
                                'id_obj'     => $this->id,
                                'doc_type'   => $doc_type,
                                'id_client'  => $id_client,
                                'id_contact' => $id_contact
                                    ), true, $errors, $warnings);

                    if (!count($errors) && BimpObject::objectLoaded($signature)) {
                        $errors = $this->updateField($field_name, (int) $signature->id);
                        $this->addObjectLog('Fiche signature du ' . $doc_type . ' de location créée', 'SIGNATURE_' . strtoupper($doc_type) . '_CREEE');

                        if ($open_public_acces) {
                            $open_errors = $signature->openSignDistAccess($email_content, true);

                            if (count($open_errors)) {
                                $warnings[] = BimpTools::getMsgFromArray($open_errors, 'Echec de l\'ouverture de l\'accès à la signature à distance');
                            }
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function getDefaultSignatureContact()
    {
        return (int) $this->getData('id_contact_client');
    }

    public function getDefaultSignDistEmailContent($doc_type)
    {
        BimpObject::loadClass('bimpcore', 'BimpSignature');
        return BimpSignature::getDefaultSignDistEmailContent();
    }

    public function getSignatureDocFileDir($doc_type)
    {
        return $this->getFilesDir();
    }

    public function getSignatureDocRef($doc_type)
    {
        switch ($doc_type) {
            case 'devis':
                return $this->getRef();

            case 'contrat':
                return str_replace('DF', 'CTF', $this->getRef());
        }
        return '';
    }

    public function getSignatureDocFileName($doc_type, $signed = false)
    {
        if ($this->isLoaded()) {
            return $this->getSignatureDocRef($doc_type) . ($signed ? '_signe' : '') . '.pdf';
        }

        return '';
    }

    public function getSignatureDocFileUrl($doc_type, $forced_context = '', $signed = false)
    {
        if (!$this->isLoaded()) {
            return '';
        }
        $context = BimpCore::getContext();

        if ($forced_context) {
            $context = $forced_context;
        }

        $fileName = $this->getSignatureDocFileName($doc_type, $signed);

        if ($fileName) {
            if ($context === 'public') {
                return self::getPublicBaseUrl() . 'fc=doc&doc=' . $doc_type . '_financement' . ($signed ? '_signed' : '') . '&docid=' . $this->id . '&docref=' . $doc_type . '-' . $this->getRef();
            } else {
                return $this->getFileUrl($fileName);
            }
        }

        return '';
    }

    public function getSignatureParams($doc_type)
    {
        if (in_array($doc_type, array('devis', 'contrat'))) {
            return BimpTools::overrideArray(self::${'default_' . $doc_type . '_signature_params'}, (array) $this->getData('signature_' . $doc_type . '_params'));
        }

        return array();
    }

    public function getSignatureCommercialEmail($doc_type, &$use_as_from = false)
    {
        $user_resp = $this->getChildObject('user_resp');
        if (BimpObject::objectLoaded($user_resp)) {
            return $user_resp->getData('email');
        }

        $client = $this->getChildObject('client');

        if (BimpObject::objectLoaded($client)) {
            $use_as_from = false;
            return $client->getCommercialEmail(false);
        }

        return '';
    }

    public function getOnSignedEmailExtraInfos($doc_type)
    {
        return '';
    }

    public function onSigned($bimpSignature, $data)
    {
        $errors = array();

        if (BimpObject::objectLoaded($bimpSignature) && is_a($bimpSignature, 'BimpSignature')) {
            $doc_type = $bimpSignature->getData('doc_type');
            $field_name = $doc_type . '_status';
            if ($this->field_exists($field_name)) {
                $errors = $this->updateField($field_name, self::DOC_ACCEPTED);
            } else {
                $errors[] = 'Type de document signé invalide: "' . $doc_type . '"';
            }

            if (!count($errors)) {
                $this->addObjectLog(ucfirst($doc_type) . ' de location signé', strtoupper($doc_type) . '_SIGNE');
            }
        } else {
            $errors[] = 'Objet signature invalide';
        }

        return $errors;
    }

    public function isSignatureCancellable()
    {
        return 0; // TODO
    }

    public function isSignatureReopenable($doc_type, &$errors = array())
    {
        return 0; // TODO
    }

    // Actions:

    public function actionTakeCharge($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Prise en charge effectuée';

        global $user;

        if (!BimpObject::objectLoaded($user)) {
            $errors[] = 'Aucun utilisateur connecté';
        } else {
            if ((int) $this->getData('status') < 0) {
                $errors = $this->updateField('status', 0); // Pour éviter log auto
            }

            if (!count($errors)) {
                $errors = $this->updateField('id_user_resp', $user->id);

                if (!BimpObject::objectLoaded($errors)) {
                    $this->addObjectLog('Prise en charge', 'PRIS_EN_CHARGE');
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionCancel($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Demande de location abandonnée';

        $reasons = BimpTools::getArrayValueFromPath($data, 'reasons', '');

        $this->set('status', self::STATUS_CANCELED);

        $devis_status = (int) $this->getData('devis_status');
        if ($devis_status > 0 && $devis_status < 10) {
            $this->set('devis_status', self::DOC_CANCELLED);
        }

        $contrat_status = (int) $this->getData('contrat_status');
        if ($contrat_status > 0 && $contrat_status < 10) {
            $this->set('contrat_status', self::DOC_CANCELLED);
        }

        $errors = $this->update($warnings, true);

        if (!count($errors)) {
            $this->addObjectLog('Demande de location abandonnée' . ($reasons ? '<br/><b>Raisons : <b/><br/>' . $reasons : ''), 'CANCELED');
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionReopen($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Demande de location réouverte';

        $errors = $this->updateField('status', self::STATUS_DRAFT);

        if (!count($errors)) {
            $this->addObjectLog('Demande de location réouverte', 'REOPEN');
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionGenerateDevisFinancement($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $errors = $this->generateDocument('devis', $data, $warnings, $success);

        if (!count($errors)) {
            $file_name = $this->getSignatureDocFileName('devis');
            $file = $this->getFilesDir() . $file_name;

            if (file_exists($file)) {
                $url = $this->getFileUrl($file_name);
                if ($url) {
                    $sc = 'window.open(\'' . $url . '\')';
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $sc
        );
    }

    public function actionGenerateContratFinancement($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Création du fichier PDF effectué avec succès';

        $formule = BimpTools::getArrayValueFromPath($data, 'formule', $this->getData('formule'));
        if ($formule != $this->getData('formule')) {
            $this->updateField('formule', $formule);
        }

        $client_data = array(
            'is_company'      => (int) BimpTools::getArrayValueFromPath($data, 'client_is_company', 0),
            'nom'             => BimpTools::getArrayValueFromPath($data, 'client_name', ''),
            'address'         => BimpTools::getArrayValueFromPath($data, 'client_address', ''),
            'forme_juridique' => BimpTools::getArrayValueFromPath($data, 'client_forme_juridique', ''),
            'capital'         => BimpTools::getArrayValueFromPath($data, 'client_capital', ''),
            'siren'           => BimpTools::getArrayValueFromPath($data, 'client_siren', ''),
            'rcs'             => BimpTools::getArrayValueFromPath($data, 'client_rcs', ''),
            'representant'    => BimpTools::getArrayValueFromPath($data, 'client_representant', ''),
            'repr_qualite'    => BimpTools::getArrayValueFromPath($data, 'client_repr_qualite', '')
        );

        if ($this->isDemandeValid($errors)) {
            global $db;
            $files_dir = $this->getFilesDir();
            $ref = $this->getRef();

//            // PDF Consignes: 
//            $file_name = 'consignes_' . $ref . '.pdf';
//
//            require_once DOL_DOCUMENT_ROOT . '/bimpfinancement/pdf/ConsignesContratFinancementPDF.php';
//            $pdf = new ConsignesContratFinancementPDF($db, $this);
//            if (!$pdf->render($files_dir . $file_name, 'F')) {
//                $errors[] = BimpTools::getMsgFromArray($pdf->errors, 'Echec de la création du fichier PDF des consignes client');
//            }
            // PDF contrat de location: 
            $file_name = $this->getSignatureDocFileName('contrat');
            require_once DOL_DOCUMENT_ROOT . '/bimpfinancement/pdf/ContratFinancementPDF.php';
            $pdf = new ContratFinancementPDF($db, $this, $client_data);

            $pdf->render($files_dir . $file_name, 'F');

            if (count($pdf->errors)) {
                $errors[] = BimpTools::getMsgFromArray($pdf->errors, 'Echec de la création du fichier PDF du contrat de location');
            } else {
                if (file_exists($files_dir . $file_name)) {
                    $url = $this->getFileUrl($file_name);
                    if ($url) {
                        $sc = 'window.open(\'' . $url . '\')';
                    }
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $sc
        );
    }

    public function actionCreateSignatureDevis($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Fiche signature du devis de location créée avec succès';

        $signature_errors = $this->createSignature('devis', $data, $warnings);

        if (count($signature_errors)) {
            $errors[] = BimpTools::getMsgFromArray($signature_errors, 'Echec de la création de la fiche signature');
        } else {
            if ((int) BimpTools::getArrayValueFromPath($data, 'open_public_access', 1)) {
                $up_errors = $this->updateField('devis_status', self::DOC_SEND);
                if (count($up_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($up_errors, 'Echec de l\'enregistrement du nouveau statut du devis');
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionCreateSignatureContrat($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Fiche signature du contrat de location créée avec succès';

        $signature_errors = $this->createSignature('contrat', $data, $warnings);

        if (count($signature_errors)) {
            $errors[] = BimpTools::getMsgFromArray($signature_errors, 'Echec de la création de la fiche signature');
        } else {
            if ((int) BimpTools::getArrayValueFromPath($data, 'open_public_access', 1)) {
                $up_errors = $this->updateField('contrat_status', self::DOC_SEND);
                if (count($up_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($up_errors, 'Echec de l\'enregistrement du nouveau statut du contrat');
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionSubmitDevis($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Envoi du devis de location à ' . $this->displaySourceName() . ' effectué avec succès';

        $errors = $this->submitDoc('devis');

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionSubmitContrat($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Envoi du contrat de location à ' . $this->displaySourceName() . ' effectué avec succès';

        $errors = $this->submitDoc('contrat');

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionSubmitRefuse($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Soumission du refus auprès de ' . $this->displaySourceName() . ' effectuée avec succès';

        $source = $this->getSource('main', $errors);

        if (!count($errors)) {
            $type_origine = $source->getData('type_origine');
            $id_origine = (int) $source->getData('id_origine');

            if (!$type_origine) {
                $errors[] = 'Type de la pièce d\'origine absent';
            }

            if (!$id_origine) {
                $errors[] = 'ID de la pièce d\'origine absent';
            }

            if (!count($errors)) {
                $api = $this->getMainSourceAPI($errors);

                if (!count($errors)) {
                    $req_errors = array();
                    $note = BimpTools::getArrayValueFromPath($data, 'note', '');
                    $api->setDemandeFinancementStatus($this->id, $type_origine, $id_origine, 20, $note, $req_errors, $warnings);

                    if (count($req_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($req_errors, 'Echec de la requête');
                    } else {
                        $up_errors = $this->updateField('closed', 1);
                        if (count($up_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($up_errors, 'Echec de l\'enregistrement du statut "Fermé' . $this->e() . '"');
                        } else {
                            $this->addObjectLog('Refus définitif - Demande fermée' . ($note ? '<br/><b>Note : </b>' . $note : ''), 'CLOSED');
                        }
                    }
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionSubmitCancel($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Soumission de l\'abandon auprès de ' . $this->displaySourceName() . ' effectuée avec succès';

        $source = $this->getSource('main', $errors);

        if (!count($errors)) {
            $type_origine = $source->getData('type_origine');
            $id_origine = (int) $source->getData('id_origine');

            if (!$type_origine) {
                $errors[] = 'Type de la pièce d\'origine absent';
            }

            if (!$id_origine) {
                $errors[] = 'ID de la pièce d\'origine absent';
            }

            if (!count($errors)) {
                $api = $this->getMainSourceAPI($errors);

                if (!count($errors)) {
                    $req_errors = array();
                    $note = BimpTools::getArrayValueFromPath($data, 'note', '');
                    $api->setDemandeFinancementStatus($this->id, $type_origine, $id_origine, 21, $note, $req_errors, $warnings);

                    if (count($req_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($req_errors, 'Echec de la requête');
                    } else {
                        $source->updateField('cancel_submitted', 1);
                        $up_errors = $this->updateField('closed', 1);
                        if (count($up_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($up_errors, 'Echec de l\'enregistrement du statut "Fermé' . $this->e() . '"');
                        } else {
                            $this->addObjectLog('Abandon définitif - Demande fermée' . ($note ? '<br/><b>Note : </b>' . $note : ''), 'CLOSED');
                        }
                    }
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionMergeDemandes($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Demandes fusionnées avec succès';

        $demandes = array();
        $id_demandes = BimpTools::getArrayValueFromPath($data, 'id_objects', array());

        if (empty($id_demandes)) {
            $errors[] = 'Aucune demande de location sélectionnée';
        } else {
            foreach ($id_demandes as $id_demande) {
                $demande = BimpCache::getBimpObjectInstance('bimpfinancement', 'BF_Demande', $id_demande);

                if (!BimpObject::objectLoaded($demande)) {
                    $errors[] = 'La demande #' . $id_demande . ' n\'existe plus';
                } else {
                    $demande_errors = array();
                    if (!$demande->isMergeable($demande_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($demande_errors, 'Demande ' . $demande->getRef());
                    } else {
                        $demandes[$id_demande] = $demande;
                    }
                }
            }
        }

        if (!count($errors)) {
            if (count($demandes) < 2) {
                $errors[] = 'Veuillez sélectionner au moins deux demandes de financements brouillons à fusionner';
            } else {
                $main_source = null;
                $id_main_source = (int) BimpTools::getArrayValueFromPath($data, 'id_main_source', 0);

                if (!$id_main_source) {
                    $errors[] = 'Aucune source principale sélectionnée';
                } else {
                    $main_source = BimpCache::getBimpObjectInstance('bimpfinancement', 'BF_DemandeSource', $id_main_source);

                    if (!BimpObject::objectLoaded($main_source)) {
                        $errors[] = 'La source #' . $id_main_source . ' n\'existe plus';
                    }
                }
            }
        }

        if (!count($errors)) {
            $id_demande_to_keep = (int) $main_source->getData('id_demande');

            if (!array_key_exists($id_demande_to_keep, $demandes)) {
                $errors[] = 'La demande correspondant à la source principale sélectionnée ne correspond à aucune des demandes à fusionner sélectionnées';
            } else {
                $next_line_position = (int) $this->db->getMax('bf_demande_line', 'position', 'id_demande = ' . $id_demande_to_keep);
                foreach ($demandes as $demande) {
                    if ($demande->id == $id_demande_to_keep) {
                        continue;
                    }

                    // Transfert des sources: 
                    foreach ($demande->getChildrenObjects('sources') as $source) {
                        $source_errors = $source->updateField('id_demande', $id_demande_to_keep);
                        if (count($source_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($source_errors, 'Echec du transfert de la source "' . $source->displayOrigine(true) . '" vers la demande à conserver');
                        }
                    }

                    // Transferts des lignes: 
                    foreach ($demande->getLines() as $line) {
                        if (BimpObject::objectLoaded($line)) {
                            $next_line_position++;
                            if ($this->db->update('bf_demande_line', array(
                                        'id_demande' => $id_demande_to_keep,
                                        'position'   => $next_line_position
                                            ), 'id = ' . $line->id) <= 0) {
                                $errors[] = 'Echec du transfert de la ligne "' . $line->displayDesc(1, 1) . '" - ' . $this->db->err();
                            }
                        }
                    }

                    // Suppression de la demande: 
                    $demande_warnings = array();
                    $demande_errors = $demande->delete($demande_warnings, true);

                    if (count($demande_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($demande_errors, 'Echec de la suppression de la demande ' . $demande->getRef());
                    }
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionResetDefaultValue($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        if ($this->isLoaded($errors)) {
            $field_name = BimpTools::getArrayValueFromPath($data, 'field_name', '');
            if (!$field_name) {
                $errors[] = 'Champ à mettre à jour absent';
            } elseif (!$this->field_exists($field_name)) {
                $errors[] = 'Le champ "' . $field_name . '" n\'existe pas';
            } else {
                $field_label = $this->getConf('fields/' . $field_name . '/label', $field_name);
                if (!$this->isFieldEditable($field_name)) {
                    $errors[] = 'Le champ "' . $field_label . '" n\'est plus modifiable';
                } elseif (!$this->canEditField($field_name)) {
                    $errors[] = 'Vous n\'avez pas la permission de modifier le champ "' . $field_label . '"';
                } else {
                    $def_values = $this->getDefaultValues();
                    if (!isset($def_values[$field_name])) {
                        $errors[] = 'Aucune valeur par défaut trouvée pour le champ "' . $field_label . '"';
                    } else {
                        $cur_val = (float) $this->getData($field_name);

                        if (round($cur_val, 2) == round($def_values[$field_name], 2)) {
                            $errors[] = 'Le champ "' . $field_label . '" est déjà à la valeur par défault';
                        } else {
                            $success = 'Mise à jour du chmap "' . $field_label . '" effectuée avec succès';
                            $this->set($field_name, round($def_values[$field_name], 2));
                            $errors = $this->update($warnings, true);
                        }
                    }
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrides:

    public function create(&$warnings = [], $force_create = false)
    {
        $errors = array();

        $ref = $this->getNextRef();

        if (!$ref) {
            $errors[] = 'Echec de la génération de la référence';
        } else {
            $this->set('ref', $ref);
        }

        if (!count($errors)) {
            return parent::create($warnings, $force_create);
        }

        return $errors;
    }

    public function update(&$warnings = [], $force_update = false)
    {
        if (BimpTools::isSubmit('updload_doc_type')) {
            $doc_type = BimpTools::getValue('updload_doc_type');
            unset($_POST['updload_doc_type']); // Pour éviter boucle infinie si nouvel appel à update()
            return $this->uploadDocument($doc_type, $warnings);
        }

        return parent::update($warnings, $force_update);
    }

    // Méthodes statiques: 

    public static function createFromSource($data, &$errors = array(), &$warnings = array())
    {
        BimpCache::getBdb()->db->begin();
        $errors = array();

        BimpObject::loadClass('bimpfinancement', 'BF_DemandeSource');

        // Vérif données: 
        $type_source = BimpTools::getArrayValueFromPath($data, 'type_source', '');

        if (!$type_source) {
            $errors[] = 'Type de source non spécifiée';
        } elseif (!isset(BF_DemandeSource::$types[$type_source])) {
            $errors[] = 'Type de source invalide: ' . $type_source;
        }

        $type_origine = BimpTools::getArrayValueFromPath($data, 'type_origine', '');

        if (!$type_origine) {
            $errors[] = 'Type de pièce d\'origine absent';
        } elseif (!isset(BF_DemandeSource::$types_origines[$type_origine])) {
            $errors[] = 'Type de pièce d\'origine invalide: ' . $type_origine;
        }

        if (!isset($data['origine'])) {
            $errors[] = 'Données de la pièce d\'origine absentes';
        }

        if (!isset($data['lines'])) {
            $errors[] = 'Lignes de la pièce d\'origine absentes';
        }

        if (!isset($data['client'])) {
            $errors[] = 'Données du client absentes';
        }

        if (count($errors)) {
            return null;
        }

        $origine = $data['origine'];
        $lines = $data['lines'];
        $client = $data['client'];
        $demande_data = (isset($data['demande']) ? $data['demande'] : array());
        $commercial = (isset($data['commercial']) ? $data['commercial'] : array());

        $id_origine = BimpTools::getArrayValueFromPath($origine, 'id', 0);
        $ref_origine = BimpTools::getArrayValueFromPath($origine, 'ref', '');

        if (!$id_origine && !$ref_origine) {
            $errors[] = 'ID ou référence de la pièce d\'origine absent';
        }

        $id_client = BimpTools::getArrayValueFromPath($client, 'id', 0);
        $ref_client = BimpTools::getArrayValueFromPath($client, 'ref', '');
        if (!$id_client && !$ref_client) {
            $errors[] = 'ID ou référence du client absent';
        }

        if (count($errors)) {
            return null;
        }

        // Création de la demande: 
        $source_label = BF_DemandeSource::$types[$type_source];
        $origine_label = BimpTools::getArrayValueFromPath(BF_DemandeSource::$types_origines, $type_origine . '/label', $type_origine);

        $df_data = array(
            'status' => BF_Demande::STATUS_NEW,
            'label'  => $origine_label . ' ' . $source_label . ' ' . $ref_origine,
        );

        if (isset($demande_data['duration'])) {
            $df_data['duration'] = $demande_data['duration'];
        }
        if (isset($demande_data['periodicity'])) {
            $df_data['periodicity'] = $demande_data['periodicity'];
        } else {
            $df_data['periodicity'] = 'none';
        }
        if (isset($demande_data['mode_calcul'])) {
            $df_data['mode_calcul'] = $demande_data['mode_calcul'];
        }

        $demande = BimpObject::createBimpObject('bimpfinancement', 'BF_Demande', $df_data, true, $errors, $warnings);

        if (BimpObject::objectLoaded($demande) && !count($errors)) {
            // Création de la source: 
            $source = BimpObject::createBimpObject('bimpfinancement', 'BF_DemandeSource', array(
                        'id_demande'      => $demande->id,
                        'type'            => $type_source,
                        'type_origine'    => $type_origine,
                        'id_origine'      => $id_origine,
                        'ref_origine'     => $ref_origine,
                        'id_client'       => $id_client,
                        'ref_client'      => $ref_client,
                        'id_commercial'   => BimpTools::getArrayValueFromPath($commercial, 'id', 0),
                        'origine_data'    => $origine,
                        'client_data'     => $client,
                        'commercial_data' => $commercial
                            ), true, $errors, $warnings);

            if (BimpObject::objectLoaded($source)) {
                // Création des lignes: 
                BimpObject::loadClass('bimpfinancement', 'BF_Line');
                BimpObject::createBimpObject('bimpfinancement', 'BF_Line', array(
                    'id_demande'  => $demande->id,
                    'id_source'   => $source->id,
                    'type'        => BF_Line::TYPE_TEXT,
                    'description' => 'Selon ' . $source->displayOrigine(true, false, true),
                        ), true);

                if (!empty($lines)) {
                    foreach ($lines as $line) {
                        $line_data = array();

                        switch ((int) BimpTools::getArrayValueFromPath($line, 'type', BF_Line::TYPE_FREE)) {
                            case BF_Line::TYPE_FREE:
                                $line_data = array(
                                    'id_demande'      => $demande->id,
                                    'id_source'       => $source->id,
                                    'id_line_origine' => (int) BimpTools::getArrayValueFromPath($line, 'id', 0),
                                    'type'            => BF_Line::TYPE_FREE,
                                    'ref'             => BimpTools::getArrayValueFromPath($line, 'ref', ''),
                                    'label'           => BimpTools::getArrayValueFromPath($line, 'label', ''),
                                    'description'     => BimpTools::getArrayValueFromPath($line, 'description', ''),
                                    'product_type'    => (int) BimpTools::getArrayValueFromPath($line, 'product_type', 0),
                                    'qty'             => (float) BimpTools::getArrayValueFromPath($line, 'qty', 0),
                                    'pu_ht'           => (float) BimpTools::getArrayValueFromPath($line, 'pu_ht', 0),
                                    'tva_tx'          => (float) BimpTools::getArrayValueFromPath($line, 'tva_tx', 0),
                                    'remise'          => (float) BimpTools::getArrayValueFromPath($line, 'remise', 0),
                                    'pa_ht'           => (float) BimpTools::getArrayValueFromPath($line, 'pa_ht', 0),
                                    'serialisable'    => (int) BimpTools::getArrayValueFromPath($line, 'serialisable', 0),
                                    'serials'         => BimpTools::getArrayValueFromPath($line, 'serials', '')
                                );
                                break;

                            case BF_Line::TYPE_TEXT:
                                $line_data = array(
                                    'id_demande'      => $demande->id,
                                    'id_source'       => $source->id,
                                    'id_line_origine' => (int) BimpTools::getArrayValueFromPath($line, 'id', 0),
                                    'type'            => BF_Line::TYPE_TEXT,
                                    'description'     => BimpTools::getArrayValueFromPath($line, 'description', '')
                                );
                                break;
                        }

                        $line_errors = array();
                        BimpObject::createBimpObject('bimpfinancement', 'BF_Line', $line_data, true, $line_errors);

                        if (count($line_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de l\'ajout de la ligne du devis #' . BimpTools::getArrayValueFromPath($line, 'id', '(ID inconnu)'));
                        }
                    }
                }
            }
        }

        if (!count($errors)) {
            BimpCache::getBdb()->db->commit();
            $msg = 'Nouvelle demande de location de la part de ' . $source_label;
            $this->addNotificationNote($msg, BimpNote::BN_AUTHOR_USER, '', 0, BimpNote::BN_MEMBERS, 1);
        } else {
            BimpCache::getBdb()->db->rollback();
        }

        return $demande;
    }
}
