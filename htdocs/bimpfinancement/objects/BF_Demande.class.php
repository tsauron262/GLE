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
        'contrat' => 'Contrat de location',
        'pvr'     => 'PV de réception'
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
    public static $modes_paiements = array(
        1 => 'Prélèvements automatiques'
    );
    public static $marges = array(
        0     => 12,
        1801  => 12,
        5001  => 11,
        12001 => 10,
        50001 => 10
    );
    public static $formules = array(
        'none'    => 'Non définie',
        'evo'     => 'Formule évolutive',
        'evo_afs' => 'Formule évolutive (AFS)',
        'dyn'     => 'Formule dynamique'
    );
    protected $values = null;
    protected $default_values = null;
    protected $missing_serials = null;

    // Droits users: 

    public function canClientView()
    {
        global $userClient;

        if (!BimpObject::objectLoaded($userClient)) {
            return 0;
        }

        if ($this->isLoaded()) {
            if ((int) $userClient->getData('id_client') == (int) $this->getData('id_client')) {
                return 1;
            }

            return 0;
        }

        return 1;
    }

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

//        if ((int) $this->getData('id_main_source')) {
//            return 0;
//        }

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
            case 'agreement_number': // Editable uniquement via demande refin. 
                return 0;

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
                if ((int) $this->getData('contrat_status') >= self::DOC_ACCEPTED) {
                    return 0;
                }
                return 1;

            case 'formule':
                if ((int) $this->getData('contrat_status') >= self::DOC_ACCEPTED) {
                    return 0;
                }
                return 1;

            case 'vr_vente':
                if ((int) $this->getData('id_facture_cli_rev')) {
                    return 0;
                }
                return 1;

            case 'total_rachat_ht':
                if ((int) $this->getData('id_facture_fourn_rev')) {
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

        if ((int) $this->getData('status') == self::STATUS_ACCEPTED) {
            return 0;
        }

        if ((int) $this->getData('contrat_status') >= self::DOC_ACCEPTED) {
            return 0;
        }

        return 1;
    }

    public function areDemandesRefinanceursEditable(&$errors = array())
    {
        if (!$this->isLoaded()) {
            return 0;
        }

        $contrat_status = (int) $this->getData('contrat_status');

        if ($contrat_status == self::DOC_ACCEPTED) {
            $errors[] = 'le contrat a été signé';
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
        if (in_array($action, array('generatePropositionLocation', 'generateListCsv'))) {
            return 1;
        }

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

        if (!in_array($action, array('reopen')) && $this->isClosed()) {
            $errors[] = 'Cette demande de location est fermée';
            return 0;
        }

        if (in_array($action, array('submitDevis', 'submitContrat'))) {
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
            case 'forceDevisSigned':
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
            case 'forceContratSigned':
                if ((int) $this->getData('status') !== self::STATUS_ACCEPTED) {
                    $errors[] = ucfirst($this->getLabel('this')) . ' n\'est pas au statut ' . self::$status_list[self::STATUS_ACCEPTED]['label'];
                }

                $devis_status = (int) $this->getData('devis_status');
                if ($devis_status !== self::DOC_ACCEPTED) {
                    $errors[] = 'Le devis de location n\'est pas encore accepté par le client';
                }

                $contrat_status = (int) $this->getData('contrat_status');
                if ($contrat_status > 10 && $contrat_status < 30) {
                    $errors[] = 'Le contrat de location a déjà été généré et envoyé au client';
                }
                return (count($errors) ? 0 : 1);

            case 'generatePVReception':
            case 'forcePvrSigned':
                if ((int) $this->getData('status') !== self::STATUS_ACCEPTED) {
                    $errors[] = ucfirst($this->getLabel('this')) . ' n\'est pas au statut ' . self::$status_list[self::STATUS_ACCEPTED]['label'];
                }

                $devis_status = (int) $this->getData('devis_status');
                if ($devis_status !== self::DOC_ACCEPTED) {
                    $errors[] = 'Le devis de location n\'est pas encore accepté par le client';
                }

                $missing_serials = $this->getMissingSerials();
                if ($missing_serials['total'] > 0) {
                    $errors[] = $missing_serials['total'] . 'n° de série absent(s)';
                }

                $pvr_status = (int) $this->getData('pvr_status');
                if ($pvr_status > 10 && $pvr_status < 30) {
                    $errors[] = 'Le PV de réception a déjà été généré et envoyé au client';
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

            case 'createSignaturePvr':
                if ((int) $this->getData('id_main_source')) {
                    $errors[] = 'La signature doit être proposée par la source externe';
                    return 0;
                }
                if ((int) $this->getData('pvr_status') !== self::DOC_GENERATED) {
                    $errors[] = 'le PV de réception n\'est pas au statut "généré"';
                    return 0;
                }
                if ((int) $this->getData('id_signature_pvr')) {
                    $errors[] = 'La fiche signature du PV de récpetion a déjà été créée';
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

                $files = $this->getDevisFilesArray(false);
                if (empty($files)) {
                    $this->updateField('devis_status', self::DOC_NONE);
                    $errors[] = 'Aucun devis de location généré';
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

            case 'submitPvr':
                if (!(int) $this->getData('id_main_source')) {
                    $errors[] = 'Pas de source externe';
                    return 0;
                }

                $file_name = $this->getSignatureDocFileName('pvr');
                if (!$file_name || !file_exists($this->getFilesDir() . $file_name)) {
                    $errors[] = 'Le PV de réception n\'a pas été généré';
                    return 0;
                }

                if ((int) $this->getData('pvr_status') !== self::DOC_GENERATED) {
                    $errors[] = 'Le PV de réception n\'est pas en attente d\'envoi à ' . $this->displaySourceName();
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

            case 'createFactures':
                if ((int) $this->getData('no_fac_fourn') && (int) $this->getData('no_fac_fin')) {
                    $errors[] = 'Les factures ont déjà été établies hors ERP';
                    return 0;
                }

                $contrat_status = (int) $this->getData('contrat_status');
                if ($contrat_status < 20 || $contrat_status >= 30) {
                    $errors[] = 'Le contrat de location n\'est pas encore signé';
                    return 0;
                }

                if ((int) $this->getData('id_facture_fourn') && (int) $this->getData('id_facture_fin')) {
                    $errors[] = 'Toutes les factures ont été créées';
                    return 0;
                }

                $missing_serials = $this->getMissingSerials();
                if ($missing_serials['total'] > 0) {
                    $errors[] = $missing_serials['total'] . 'n° de série absent(s)';
                    return 0;
                }
                return 1;

            case 'forceFinContrat':
                if ((int) $this->getData('status') !== self::STATUS_VALIDATED) {
                    $errors[] = 'Cette demande de location n\'est pas au statut "' . self::$status_list[self::STATUS_VALIDATED]['label'] . '"';
                    return 0;
                }
                return 1;

            case 'createFacturesRevente':
                if ((int) $this->getData('contrat_status') !== self::DOC_ACCEPTED) {
                    $errors[] = 'Le contrat n\'est pas accepté';
                    return 0;
                }

                if ((int) $this->getData('id_facture_fourn_rev') && (int) $this->getData('id_facture_cli_rev')) {
                    $errors[] = 'Les 2 factures de revente ont déjà été créées';
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

    public function hasFacture()
    {
        return (int) ($this->getData('id_facture_fourn') || $this->getData('id_facture_fin'));
    }

    public function hasFactureRevente()
    {
        return (int) ($this->getData('id_facture_fourn_rev') || $this->getData('id_facture_cli_rev'));
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

    public function showDemandesRefinanceurs()
    {
        if ($this->isLoaded() && (int) $this->getData('status') > 0) {
            return 1;
        }
        return 0;
    }

    public function showRevente()
    {
        if ($this->isLoaded() && (int) $this->getData('contrat_status') === self::DOC_ACCEPTED) {
            return 1;
        }
        return 0;
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
            $action = 'force' . ucfirst($doc_type) . 'Signed';
            if ($this->isActionAllowed($action) && $this->canSetAction($action)) {
                $label = 'Forcer ' . $doc_type . ' de location signé';
                $confirm_msg = htmlentities('ATTENTION : le ' . $doc_type . ' de location ne sera pas généré et sera directement marqué "Signé"');

                $buttons['force_' . $doc_type] = array(
                    'label'   => $label,
                    'icon'    => 'fas_check',
                    'onclick' => $this->getJsActionOnclick($action, array(), array(
                        'confirm_msg' => $confirm_msg
                    ))
                );
            }

            $action = 'createSignature' . ucfirst($doc_type);
            if ($this->isActionAllowed($action) && $this->canSetAction($action)) {
                $label = 'Envoyer le ' . $doc_type . ' de location pour signature';

                if ($doc_type == 'devis') {
                    $files = $this->getDevisFilesArray(false);
                    if (count($files) > 1) {
                        $label = 'Envoyer les ' . count($files) . ' devis de location pour signature';
                    }
                }
                $buttons['create_signature_' . $doc_type] = array(
                    'label'   => $label,
                    'icon'    => 'fas_signature',
                    'onclick' => $this->getJsActionOnclick($action, array(), array(
                        'form_name' => 'create_signature_' . $doc_type
                    ))
                );
            }
        }

        if ($this->isActionAllowed('generatePVReception') && $this->canSetAction('generatePVReception')) {
            $buttons[] = array(
                'label'   => 'Générer ' . ((int) $this->getData('pvr_status') >= 10 ? 'à nouveau ' : '') . 'le PV de réception',
                'icon'    => 'fas_cogs',
                'onclick' => $this->getJsActionOnclick('generatePVReception', array(), array(
                    'form_name' => 'generate_pvr'
                ))
            );
        }

        if ($this->isActionAllowed('forcePvrSigned') && $this->canSetAction('forcePvrSigned')) {
            $buttons[] = array(
                'label'   => 'Forcer PVR signé',
                'icon'    => 'fas_check',
                'onclick' => $this->getJsActionOnclick('forcePvrSigned', array(), array(
                    'confirm_msg' => htmlentities('ATTENTION : le PVR de ne sera pas généré et sera directement marqué "Signé"')
                ))
            );
        }

        if ($this->isActionAllowed('createSignaturePvr') && $this->canSetAction('createSignaturePvr')) {
            $buttons[] = array(
                'label'   => 'Envoyer le PVR pour signature',
                'icon'    => 'fas_signature',
                'onclick' => $this->getJsActionOnclick('createSignaturePvr', array(), array(
                    'form_name' => 'create_signature_pvr'
                ))
            );
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

        if ($this->isActionAllowed('submitDevis') && $this->canSetAction('submitDevis')) {
            $files = $this->getDevisFilesArray(false);

            if (count($files) > 1) {
                $label = 'Envoyer les ' . count($files) . ' devis à ' . $this->displaySourceName();
            } else {
                $label = 'Envoyer le devis à ' . $this->displaySourceName();
            }
            $buttons[] = array(
                'label'   => $label,
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

        if ($this->isActionAllowed('submitPvr') && $this->canSetAction('submitPvr')) {
            $buttons[] = array(
                'label'   => 'Envoyer le PV de réception à ' . $this->displaySourceName(),
                'icon'    => 'fas_arrow-circle-right',
                'onclick' => $this->getJsActionOnclick('submitPvr', array(), array(
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

        if ($this->isActionAllowed('createFactures') && $this->canSetAction('createFactures')) {
            $buttons[] = array(
                'label'   => 'Créer les factures',
                'icon'    => 'fas_file-invoice-dollar',
                'onclick' => $this->getJsActionOnclick('createFactures', array(), array(
                    'form_name' => 'factures'
                ))
            );
        }

        if ($this->isActionAllowed('forceFinContrat') && $this->canSetAction('forceFinContrat')) {
            $buttons[] = array(
                'label'   => 'Forcer fin de contrat',
                'icon'    => 'fas_check',
                'onclick' => $this->getJsActionOnclick('forceFinContrat', array(), array(
                    'form_name' => 'force_fin_contrat'
                ))
            );
        }

        if ($this->isActionAllowed('createFacturesRevente') && $this->canSetAction('createFacturesRevente')) {
            $buttons[] = array(
                'label'   => 'Créer factures revente',
                'icon'    => 'fas_file-invoice-dollar',
                'onclick' => $this->getJsActionOnclick('createFacturesRevente', array(), array(
                    'form_name' => 'factures_revente'
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

    public function getDefaultListHeaderButton()
    {
        $buttons = array();

        if ($this->canSetAction('generatePropositionLocation')) {
            $buttons[] = array(
                'label'   => 'Générer proposition de location',
                'icon'    => 'fas_cogs',
                'onclick' => $this->getJsActionOnclick('generatePropositionLocation', array(), array(
                    'form_name' => 'proposition'
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

    public function getFacturesFormMsgs()
    {
        $missing = $this->getMissingSerials();

        if ($missing['total'] > 0) {
            $msg = BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft');
            if ($missing['total'] > 1) {
                $msg .= $missing['total'] . ' numéros de série ne sont pas encore renseignés';
            } else {
                $msg .= '1 numéro de série n\'est pas encore renseigné';
            }

            return array(
                array(
                    'type'    => 'warning',
                    'content' => $msg
                )
            );
        }

        return array(
            array(
                'type'    => 'success',
                'content' => BimpRender::renderIcon('fas_check', 'iconLeft') . ' Aucun n° de série non renseigné'
            )
        );
    }

    public function getClientSearchFilters(&$filters, $value, &$joins = array(), $main_alias = 'a')
    {
        $where_source = 'source.ref_client LIKE \'%' . $this->db->db->escape((string) $value) . '%\' OR source.client_data LIKE \'%' . $this->db->db->escape((string) $value) . '%\'';

        $alias = $main_alias . '___client';
        $joins[$alias] = array(
            'alias' => $alias,
            'table' => 'societe',
            'on'    => $main_alias . '.id_client = ' . $alias . '.rowid'
        );

        $filters['or_client'] = array(
            'or' => array(
                $alias . '.code_client'         => array(
                    'part_type' => 'middle',
                    'part'      => $value
                ),
                $alias . '.nom'                 => array(
                    'part_type' => 'middle',
                    'part'      => $value
                ),
                $main_alias . '.id_main_source' => array(
                    'in' => 'SELECT source.id FROM ' . MAIN_DB_PREFIX . 'bf_demande_source source WHERE ' . $where_source
                )
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

    public function getCessionnaireContactsArray()
    {
        $id_refin = (int) BimpTools::getPostFieldValue('cessionnaire_id_refinanceur', 0);

        if ($id_refin) {
            $id_societe = (int) $this->db->getValue('bf_refinanceur', 'id_societe', 'id = ' . $id_refin);
            if ($id_societe) {
                return self::getSocieteContactsArray($id_societe, false, '', true);
            }
        }

        return array();
    }

    public function getRefinanceursArray($include_empty = true, $active_only = true, $empty_label = '')
    {
        BimpObject::loadClass('bimpfinancement', 'BF_DemandeRefinanceur');
        return BF_DemandeRefinanceur::getRefinanceursArray($include_empty, $active_only, $empty_label);
    }

    public function getDevisFilesArray($include_empty = true)
    {
        if (!$this->isLoaded()) {
            return ($include_empty ? array('' => '') : array());
        }

        $key = 'BF_Demande_' . $this->id . '_devis_files_array';

        if (!isset(self::$cache[$key])) {
            $files = array();
            $dir = $this->getFilesDir();

            $file_name_base = pathinfo($this->getSignatureDocFileName('devis'), PATHINFO_FILENAME);

            if (is_dir($dir)) {
                foreach (scandir($dir) as $f) {
                    if (in_array($f, array('.', '..'))) {
                        continue;
                    }

                    if (preg_match('/^' . preg_quote($file_name_base, '/') . '(\-\d+)?\.pdf$/', $f)) {
                        $files[$f] = $f;
                    }
                }
            }

            ksort($files, SORT_NATURAL);
            self::$cache[$key] = $files;
        }

        return self::getCacheArray($key, $include_empty, '', '');
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

    public function getTotalDemandeHTOnlyProd()
    {
        $totalHt = 0;
        $lines = $this->getLines('only_prod');
        foreach ($lines as $line) {
            $totalHt += $line->getData('total_ht');
        }
        return $totalHt;
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
            case 'client_email':
            case 'client_is_company':
            case 'client_forme_juridique':
            case 'client_capital':
            case 'client_siren':
            case 'client_address':
            case 'client_representant':
            case 'client_repr_qualite':
            case 'client_livraisons':
                if ((int) $this->getData('id_main_source')) {
                    $source = $this->getSource();
                    if (BimpObject::objectLoaded($source)) {
                        $client_data = $source->getData('client_data');
                        switch ($field_name) {
                            case 'client_name':
                                return BimpTools::getArrayValueFromPath($client_data, 'nom', 0);

                            case 'client_email':
                                return BimpTools::getArrayValueFromPath($client_data, 'contact/email', 0);

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

                            case 'client_livraisons':
                                return BimpTools::replaceBr($source->getAdressesLivraisons());
                        }
                    }
                } else {
                    $client = $this->getChildObject('client');
                    $contact = $this->getChildObject('contact_client');
                    $is_company = 0;

                    if (BimpObject::objectLoaded($client)) {
                        $is_company = (int) $client->isCompany();
                    }

                    switch ($field_name) {
                        case 'client_name':
                            if (BimpObject::objectLoaded($client)) {
                                return $client->getName();
                            }
                            return '';

                        case 'client_email':
                            $email = '';
                            if (BimpObject::objectLoaded($contact)) {
                                $email = $contact->getData('email');
                            }
                            if (!$email && BimpObject::objectLoaded($client)) {
                                $email = $client->getData('email');
                            }
                            return $email;

                        case 'client_is_company':
                            return $is_company;

                        case 'client_forme_juridique':
                            if ($is_company && BimpObject::objectLoaded($client)) {
                                return $client->displayData('fk_forme_juridique', 'default', false, true);
                            }
                            return '';

                        case 'client_type_entrepise':
                            if ($is_company && BimpObject::objectLoaded($client)) {
                                return $client->displayData('fk_forme_juridique', 'default', false, true);
                            }
                            return '';

                        case 'client_capital':
                            if ($is_company && BimpObject::objectLoaded($client)) {
                                return $client->displayData('capital', 'default', false, true);
                            }
                            return '';

                        case 'client_siren':
                            if ($is_company && BimpObject::objectLoaded($client)) {
                                return $client->getData('siren');
                            }
                            return '';

                        case 'client_address':
                            if (BimpObject::objectLoaded($client)) {
                                return strip_tags(BimpTools::replaceBr($client->displayFullAddress(false, false)));
                            }
                            return '';

                        case 'client_representant':
                            if (BimpObject::objectLoaded($contact)) {
                                return $contact->getName();
                            } elseif (!$is_company && BimpObject::objectLoaded($client)) {
                                return $client->getName();
                            }
                            return '';

                        case 'client_repr_qualite':
                            if ($is_company && BimpObject::objectLoaded($contact)) {
                                return $contact->getData('poste');
                            }
                            return '';

                        case 'client_livraisons':
                            $contacts_livraisons = $this->getData('contacts_livraisons');
                            if (!empty($contacts_livraisons)) {
                                $return = '';
                                $fl = true;
                                foreach ($contacts_livraisons as $id_contact_liv) {
                                    $contact_liv = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $id_contact_liv);
                                    if (BimpObject::objectLoaded($contact_liv)) {
                                        if (!$fl) {
                                            $return .= "\n\n";
                                        } else {
                                            $fl = false;
                                        }
                                        $return .= BimpTools::replaceBr($contact_liv->displayFullAddress(false));
                                    }
                                }

                                return $return;
                            } elseif (BimpObject::objectLoaded($contact)) {
                                return BimpTools::replaceBr($contact->displayFullAddress(false));
                            } elseif (BimpObject::objectLoaded($client)) {
                                return BimpTools::replaceBr($client->displayFullAddress(false, false));
                            }
                            return '';
                    }
                }
                return '';

            case 'loueur_nom':
                return BimpCore::getConf('loueur_signataire_nom', null, 'bimpfinancement');
            case 'loueur_email':
                return BimpCore::getConf('loueur_signataire_email', null, 'bimpfinancement');
            case 'loueur_qualite':
                return BimpCore::getConf('loueur_signataire_qualite', null, 'bimpfinancement');

            case 'cessionnaire_id_refinanceur':
                return (int) $this->getSelectedDemandeRefinanceurData('id_refinanceur');

            case 'cessionnaire_saison_sociale':
            case 'cessionnaire_siren':
                $id_refin = (int) BimpTools::getPostFieldValue('cessionnaire_id_refinanceur', 0);
                if ($id_refin) {
                    $id_soc = (int) $this->db->getValue('bf_refinanceur', 'id_societe', 'id = ' . $id_refin);
                    if ($id_soc) {
                        $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $id_soc);
                        if (BimpObject::objectLoaded($soc)) {
                            switch ($field_name) {
                                case 'cessionnaire_saison_sociale':
                                    return $soc->getName();
                                case 'cessionnaire_siren':
                                    return $soc->getData('siren');
                            }
                        }
                    }
                }
                return '';

            case 'cessionnaire_id_contact_signataire':
                $id_refin = (int) BimpTools::getPostFieldValue('cessionnaire_id_refinanceur', 0);
                if ($id_refin) {
                    return (int) $this->db->getValue('bf_refinanceur', 'id_def_contact_signataire', 'id = ' . $id_refin);
                }
                return 0;

            case 'cessionnaire_nom':
            case 'cessionnaire_email':
            case 'cessionnaire_qualite':
                $id_contact = (int) BimpTools::getPostFieldValue('cessionnaire_id_contact_signataire', 0);
                if ($id_contact) {
                    $contact = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $id_contact);
                    if (BimpObject::objectLoaded($contact)) {
                        switch ($field_name) {
                            case 'cessionnaire_nom':
                                return $contact->getName();
                            case 'cessionnaire_email':
                                return $contact->getData('email');
                            case 'cessionnaire_qualite':
                                return $contact->getData('poste');
                        }
                    }
                }
                return '';

            // Génération factures: 
            case 'fac_fourn_libelle':
            case 'fac_fin_libelle':
                return 'Contrat de location n° ' . str_replace('DF', '', $this->getRef());

            case 'fac_fourn_id_fourn':
                if ((int) $this->getData('id_main_source')) {
                    $source = $this->getSource();
                    if (BimpObject::objectLoaded($source)) {
                        return (int) BimpCore::getConf('id_fourn_' . $source->getData('type'), null, 'bimpfinancement');
                    }
                    return 0;
                }
                return (int) $this->getData('id_supplier');

            case 'fac_fourn_id_mode_reglement':
            case 'fac_fourn_id_cond_reglement':
                $id_fourn = 0;
                if ((int) $this->getData('id_main_source')) {
                    $source = $this->getSource();
                    if (BimpObject::objectLoaded($source)) {
                        $id_fourn = (int) BimpCore::getConf('id_fourn_' . $source->getData('type'), null, 'bimpfinancement');
                    }
                } else {
                    $id_fourn = (int) $this->getData('id_supplier');
                }

                if ($id_fourn) {
                    $fourn = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $id_fourn);
                    if (BimpObject::objectLoaded($fourn)) {
                        $id = 0;
                        switch ($field_name) {
                            case 'fac_fourn_id_mode_reglement':
                                $id = (int) $fourn->getData('mode_reglement_supplier');
                                if (!$id) {
                                    $id = (int) BimpCore::getConf('fac_fourn_def_id_mode_reglement', null, 'bimpfinancement');
                                }
                                break;

                            case 'fac_fourn_id_cond_reglement':
                                $id = (int) $fourn->getData('cond_reglement_supplier');
                                if (!$id) {
                                    $id = (int) BimpCore::getConf('fac_fourn_def_id_cond_reglement', null, 'bimpfinancement');
                                }
                                break;
                        }
                        return $id;
                    }
                }
                return 0;

            case 'fac_fin_id_client':
            case 'fac_fin_id_mode_reglement':
            case 'fac_fin_id_cond_reglement':
                $id_client = 0;
                $id_refin = (int) $this->getSelectedDemandeRefinanceurData('id_refinanceur');
                if ($id_refin) {
                    $refin = BimpCache::getBimpObjectInstance('bimpfinancement', 'BF_Refinanceur', $id_refin);
                    if (BimpObject::objectLoaded($refin)) {
                        $id_client = (int) $refin->getData('id_societe');
                    }
                }

                if ($id_client) {
                    switch ($field_name) {
                        case 'fac_fin_id_client':
                            return $id_client;

                        case 'fac_fin_id_mode_reglement':
                        case 'fac_fin_id_cond_reglement':
                            $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $id_client);
                            if (BimpObject::objectLoaded($client)) {
                                $id = 0;
                                switch ($field_name) {
                                    case 'fac_fin_id_mode_reglement':
                                        $id = (int) $client->getData('mode_reglement');
                                        if (!$id) {
                                            $id = (int) BimpCore::getConf('fac_fin_def_id_mode_reglement', null, 'bimpfinancement');
                                        }
                                        break;

                                    case 'fac_fin_id_cond_reglement':
                                        $id = (int) $client->getData('cond_reglement');
                                        if (!$id) {
                                            $id = (int) BimpCore::getConf('fac_fin_def_id_cond_reglement', null, 'bimpfinancement');
                                        }
                                        break;
                                }
                                return $id;
                            }
                    }
                }
                return 0;

            // Factures de revente: 
            case 'fac_fourn_rev_id_fourn':
                $id_refin = (int) $this->getSelectedDemandeRefinanceurData('id_refinanceur');
                if ($id_refin) {
                    $refin = BimpCache::getBimpObjectInstance('bimpfinancement', 'BF_Refinanceur', $id_refin);
                    if (BimpObject::objectLoaded($refin)) {
                        return (int) $refin->getData('id_societe');
                    }
                }
                return 0;

            case 'fac_fourn_rev_libelle':
                return 'Rachat fin de contrat de location n° ' . str_replace('DF', '', $this->getRef());

            case 'fac_fourn_rev_id_mode_reglement':
            case 'fac_fourn_rev_id_cond_reglement':
                $id_fourn = BimpTools::getPostFieldValue('fac_fourn_rev_id_fourn', 0);
                if ($id_fourn) {
                    $fourn = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $id_fourn);
                    if (BimpObject::objectLoaded($fourn)) {
                        $id = 0;
                        switch ($field_name) {
                            case 'fac_fourn_rev_id_mode_reglement':
                                $id = (int) $fourn->getData('mode_reglement_supplier');
                                if (!$id) {
                                    $id = (int) BimpCore::getConf('fac_fourn_rev_def_id_mode_reglement', null, 'bimpfinancement');
                                }
                                break;

                            case 'fac_fourn_rev_id_cond_reglement':
                                $id = (int) $fourn->getData('cond_reglement_supplier');
                                if (!$id) {
                                    $id = (int) BimpCore::getConf('fac_fourn_rev_def_id_cond_reglement', null, 'bimpfinancement');
                                }
                                break;
                        }
                        return $id;
                    }
                }
                return 0;

            case 'fac_cli_rev_id_client':
                if ((int) $this->getData('id_main_source')) {
                    $source = $this->getSource();
                    if (BimpObject::objectLoaded($source)) {
                        return (int) BimpCore::getConf('id_fourn_' . $source->getData('type'), null, 'bimpfinancement');
                    }
                    return 0;
                }
                return (int) $this->getData('id_client');

            case 'fac_cli_rev_libelle':
                return 'Revente fin de contrat de location n° ' . str_replace('DF', '', $this->getRef());

            case 'fac_cli_rev_id_mode_reglement':
            case 'fac_cli_rev_id_cond_reglement':
                $id_client = BimpTools::getPostFieldValue('fac_cli_rev_id_client', 0);
                if ($id_client) {
                    $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $id_client);
                    if (BimpObject::objectLoaded($client)) {
                        $id = 0;
                        switch ($field_name) {
                            case 'fac_cli_rev_id_mode_reglement':
                                $id = (int) $client->getData('mode_reglement');
                                if (!$id) {
                                    $id = (int) BimpCore::getConf('fac_cli_rev_def_id_mode_reglement', null, 'bimpfinancement');
                                }
                                break;

                            case 'fac_cli_rev_id_cond_reglement':
                                $id = (int) $client->getData('cond_reglement');
                                if (!$id) {
                                    $id = (int) BimpCore::getConf('fac_cli_rev_def_id_cond_reglement', null, 'bimpfinancement');
                                }
                                break;
                        }
                        return $id;
                    }
                }
                return 0;
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

                        case 'only_prod':
                            $filters['or_type_prod'] = array(
                                'or' => array(
                                    'free' => array('and_fields' =>
                                        array(
                                            'type'         => BF_Line::TYPE_FREE,
                                            'product_type' => '1'
                                        )
                                    ),
                                    'prod' => array('and_fields' =>
                                        array(
                                            'type'                    => BF_Line::TYPE_PRODUCT,
                                            'product:fk_product_type' => '0'
                                        )
                                    )
                                )
                            );

                            break;
                    }
                }

                if (is_array($types) && !empty($types)) {
                    $filters['type'] = array(
                        'in' => $types
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

    public function getMissingSerials($recalculate = false, $id_source = 0)
    {
        if (is_null($this->missing_serials) || $recalculate || $id_source) {
            $lines = $this->getLines('not_text');

            $this->missing_serials = array(
                'total' => 0,
                'refs'  => array()
            );

            foreach ($lines as $line) {
                if ($id_source && (int) $id_source !== (int) $line->getData('id_source')) {
                    continue;
                }

                if ($line->isProductSerialisable()) {
                    $serials = $line->getData('serials');
                    $diff = (int) $line->getData('qty') - count($serials);

                    if ($diff > 0) {
                        $this->missing_serials['total'] += $diff;

                        $ref = $line->getRefProduct();
                        if (!isset($this->missing_serials['refs'][$ref])) {
                            $this->missing_serials['refs'][$ref] = 0;
                        }

                        $this->missing_serials['refs'][$ref] += $diff;
                    }
                }
            }
        }


        return $this->missing_serials;
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
                if (!$signature_devis->isSigned()) {
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
                if (!$signature_contrat->isSigned()) {
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

            // Messages signature PVR: 
            $signature_pvr = $this->getChildObject('signature_pvr');
            if (BimpObject::objectLoaded($signature_pvr)) {
                if (!$signature_pvr->isSigned()) {
                    $html .= '<div style="margin-top: 10px">';
                    $msg = BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft');
                    $msg .= '<a href="' . $signature_pvr->getUrl() . '" target="_blank">Signature du PV de réception en attente' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight') . '</a>';

                    $btn_html = $signature_pvr->renderSignButtonsGroup();
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

        if ((int) $this->getData('devis_status') === self::DOC_ACCEPTED) {
            $missing_serials = $this->getMissingSerials();
            if ($missing_serials['total'] > 0) {
                $msg = BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft');

                if ($missing_serials['total'] > 1) {
                    $msg .= '<b>' . $missing_serials['total'] . '</b> numéros de série doivent encore être renseignés';
                } else {
                    $msg .= '<b>1</b> numéro de série doit encore être renseigné';
                }

                $html .= BimpRender::renderAlerts($msg, 'warning');
            }
        }

        return $html;
    }

    public function renderHeaderExtraRight($no_div = false)
    {
        $html = '';

        $dir = $this->getFilesDir();

        $docs_buttons = array();
        $factures_buttons = array();

        $idx = 1;
        $file_name_base = pathinfo($this->getSignatureDocFileName('devis', 0), PATHINFO_FILENAME);
        while (file_exists($dir . $file_name_base . '-' . $idx . '.pdf')) {
            $url = $this->getFileUrl($file_name_base . '-' . $idx . '.pdf');
            if ($url) {
                $docs_buttons[] = array(
                    'label'   => 'Devis n°' . $idx,
                    'icon'    => 'fas_file-pdf',
                    'onclick' => 'window.open(\'' . $url . '\');'
                );
            }
            $idx++;
        }

        foreach (array('devis', 'contrat', 'pvr') as $doc_type) {
            foreach (array(1, 0) as $signed) {
                $file_name = $this->getSignatureDocFileName($doc_type, $signed);
                if (file_exists($dir . $file_name)) {
                    $url = $this->getFileUrl($file_name);
                    if ($url) {
                        if ($doc_type === 'pvr') {
                            $label = 'PV de réception';
                        } else {
                            $label = ucfirst($doc_type) . ' de location';
                        }

                        $docs_buttons[] = array(
                            'label'   => $label . ($signed ? ' (signé)' : ''),
                            'icon'    => 'fas_file-pdf',
                            'onclick' => 'window.open(\'' . $url . '\');'
                        );
                    }
                    break;
                }
            }
        }

        if ((int) $this->getData('id_facture_fourn')) {
            $fac = $this->getChildObject('facture_fourn');
            if (BimpObject::objectLoaded($fac)) {
                $factures_buttons[] = array(
                    'label'   => 'Facture fournisseur',
                    'icon'    => 'fas_file-invoice-dollar',
                    'onclick' => 'window.open(\'' . $fac->getUrl() . '\');'
                );
            }
        }

        if ((int) $this->getData('id_facture_fin')) {
            $fac = $this->getChildObject('facture_fin');
            if (BimpObject::objectLoaded($fac)) {
                $factures_buttons[] = array(
                    'label'   => 'Facture refinanceur',
                    'icon'    => 'fas_file-invoice-dollar',
                    'onclick' => 'window.open(\'' . $fac->getUrl() . '\');'
                );
            }
        }

        if ((int) $this->getData('id_facture_fourn_rev')) {
            $fac = $this->getChildObject('facture_fourn_rev');
            if (BimpObject::objectLoaded($fac)) {
                $factures_buttons[] = array(
                    'label'   => 'Facture fourn rachat',
                    'icon'    => 'fas_file-invoice-dollar',
                    'onclick' => 'window.open(\'' . $fac->getUrl() . '\');'
                );
            }
        }

        if ((int) $this->getData('id_facture_cli_rev')) {
            $fac = $this->getChildObject('facture_cli_rev');
            if (BimpObject::objectLoaded($fac)) {
                $factures_buttons[] = array(
                    'label'   => 'Facture client revente',
                    'icon'    => 'fas_file-invoice-dollar',
                    'onclick' => 'window.open(\'' . $fac->getUrl() . '\');'
                );
            }
        }

        $groups = array();

        if (!empty($docs_buttons)) {
            $groups[] = array(
                'label'   => 'Documents',
                'icon'    => 'fas_file-pdf',
                'buttons' => $docs_buttons
            );
        }

        if (!empty($factures_buttons)) {
            $groups[] = array(
                'label'   => 'Factures',
                'icon'    => 'fas_file-invoice-dollar',
                'buttons' => $factures_buttons
            );
        }

        $html .= BimpRender::renderButtonsGroups($groups, array(
                    'max'                 => 1,
                    'dropdown_menu_right' => 1
        ));

        return $html;
    }

    public function renderHeaderStatusExtra()
    {
        $html = '';

        if ($this->isClosed()) {
            $html .= '<span class="danger">' . BimpRender::renderIcon('fas_times', 'iconLeft') . 'Terminée</span>';
        }

        if ((int) $this->getData('devis_status') > 0) {
            $html .= '<br/>Devis: ' . $this->displayData('devis_status', 'default', false, false);
        }
        if ((int) $this->getData('contrat_status') > 0) {
            $html .= '<br/>Contrat: ' . $this->displayData('contrat_status', 'default', false, false);
        }
        if ((int) $this->getData('pvr_status') > 0) {
            $html .= '<br/>PVR: ' . $this->displayData('pvr_status', 'default', false, false);
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
                        'amount_ht' => $facture->displayData('total_ht'),
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
                        'amount_ht' => $facture->displayData('total_ht'),
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
                        'amount_ht' => $facture->displayData('total_ht'),
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
                        'amount_ht' => $facture->displayData('total_ht'),
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
            $html .= '<td>';
            $html .= '<b>' . BimpTools::displayFloatValue($this->getData('tx_cession'), 3) . ' %</b>';
            $html .= '</td>';

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
                $html .= '<td>' . BimpTools::displayFloatValue($tx, 3) . ' %</td>';

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
            $html .= '<td style="background-color: #F0F0F0!important">' . BimpTools::displayFloatValue($tx_moyen, 3) . ' %</td>';

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

    public function renderCreateFacFournToggleInput()
    {
        $html = '';
        if ((int) $this->getData('no_fac_fourn')) {
            $html .= BimpRender::renderAlerts('La facture fournisseur a été indiquée comme ayant été créée hors ERP', 'warning');
            $html .= '<input type="hidden" value="0" name="create_fac_fourn"/>';
        } elseif ((int) $this->getData('id_facture_fourn')) {
            $html .= BimpRender::renderAlerts('La facture fournisseur a déjà été créée', 'warning');
            $html .= '<input type="hidden" value="0" name="create_fac_fourn"/>';
        } else {
            $html .= BimpInput::renderInput('toggle', 'create_fac_fourn', 1);
        }

        return $html;
    }

    public function renderCreateFacFinToggleInput()
    {
        $html = '';

        if ((int) $this->getData('no_fac_fin')) {
            $html .= BimpRender::renderAlerts('La facture client pour le financeur a été indiquée comme ayant été créée hors ERP', 'warning');
            $html .= '<input type="hidden" value="0" name="create_fac_fourn"/>';
        } elseif ((int) $this->getData('id_facture_fin')) {
            $html .= BimpRender::renderAlerts('La facture client pour le financeur a déjà été créée', 'warning');
            $html .= '<input type="hidden" value="0" name="create_fac_fin"/>';
        } else {
            $html .= BimpInput::renderInput('toggle', 'create_fac_fin', 1);
        }

        return $html;
    }

    public function renderCreateFacFournReventeToggleInput()
    {
        $html = '';

        if ((int) $this->getData('id_facture_fourn_rev')) {
            $html .= BimpRender::renderAlerts('La facture fournisseur revente a déjà été créée', 'warning');
            $html .= '<input type="hidden" value="0" name="create_fac_fourn_rev"/>';
        } else {
            $html .= BimpInput::renderInput('toggle', 'create_fac_fourn_rev', 1);
        }

        return $html;
    }

    public function renderCreateFacCliReventeToggleInput()
    {
        $html = '';

        if ((int) $this->getData('id_facture_cli_rev')) {
            $html .= BimpRender::renderAlerts('La facture client de revente a déjà été créée', 'warning');
            $html .= '<input type="hidden" value="0" name="create_fac_cli_rev"/>';
        } else {
            $html .= BimpInput::renderInput('toggle', 'create_fac_cli_rev', 1);
        }

        return $html;
    }

    // Traitements: 

    public function editClientDataFromSource($client_data)
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            $contrat_status = (int) $this->getData('contrat_status');

            if ($contrat_status >= 20 && $contrat_status < 30) {
                $errors[] = 'Il n\'est pas possible de mettre à jour les données du client car le contrat de location a été signé';
            } else {
                $source = $this->getSource();

                if (!BimpObject::objectLoaded($source)) {
                    $errors[] = 'Source absente';
                }

                $id_client = BimpTools::getArrayValueFromPath($client_data, 'id', 0);
                $ref_client = BimpTools::getArrayValueFromPath($client_data, 'ref', '');
                if (!$id_client && !$ref_client) {
                    $errors[] = 'ID ou référence du client absent';
                }

                if (!count($errors)) {
                    $source->validateArray(array(
                        'id_client'   => $id_client,
                        'ref_client'  => $ref_client,
                        'client_data' => $client_data
                    ));

                    $warnings = array();
                    $errors = $source->update($warnings, true);
                }
            }
        }

        return $errors;
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

            $drs = $this->getChildrenObjects('demandes_refinanceurs');
            if ($cur_status >= self::STATUS_VALIDATED || count($drs)) {
                $new_status = self::STATUS_VALIDATED;
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

                if ($new_status !== $cur_status) {
                    $errors = $this->setNewStatus($new_status, array(), $warnings, true);
                }
            }
        }
    }

    public function checkIsClosed()
    {
        $closed = 0;
        $devis_status = (int) $this->getData('devis_status');
        $contrat_status = (int) $this->getData('contrat_status');
        $pvr_status = (int) $this->getData('pvr_status');
        if (($devis_status >= 20 && $devis_status < 30) &&
                ($contrat_status >= 20 && $contrat_status < 30) &&
                ($pvr_status >= 20 && $pvr_status < 30) &&
                (int) $this->getData('id_facture_fourn') &&
                (int) $this->getData('id_facture_fin')) {
            $closed = 1;
        }

        if ($closed !== (int) $this->getData('closed')) {
            $this->updateField('closed', $closed);
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
        } elseif (is_a($child, 'BimpFile')) {
            $this->checkDevisFiles();
        }

        return array();
    }

    public function checkDevisFiles(&$next_file_idx = 1, $force_new_next_file = false)
    {
        if (!$this->isLoaded()) {
            return;
        }

        $files = $this->getDevisFilesArray(false);

        if (!empty($files)) {
            $signatureClassName = '';
            BimpObject::loadClass('bimpcore', 'BimpSignature', $signatureClassName);
            $dir = $this->getFilesDir();
            $file_name_base = pathinfo($this->getSignatureDocFileName('devis'), PATHINFO_FILENAME);
            $signatureClassName::checkMultipleFiles($files, $this, $dir, $file_name_base, 'signature_devis_params', $next_file_idx, $force_new_next_file);
        }
    }

    public function generateDocument($doc_type, $data = array(), &$warnings = array(), &$success = '')
    {
        $errors = array();

        if (!in_array($doc_type, array('devis', 'pvr'))) {
            $errors[] = 'Type de document à générer invalide: "' . $doc_type . '"';
            return $errors;
        }

        if ($this->isDemandeValid($errors)) {
            $options = array();

            foreach (BimpTools::getArrayValueFromPath($data, 'formules') as $formule) {
                $options['formules'][$formule] = 1;
            }

            global $db;

            $pdfClassName = ucfirst($doc_type) . 'FinancementPDF';
            require_once DOL_DOCUMENT_ROOT . '/bimpfinancement/pdf/' . $pdfClassName . '.php';

            $pdf = new $pdfClassName($db, $this, $data, $options);

            $file_dir = $this->getFilesDir();
            $file_name = $this->getSignatureDocFileName($doc_type);
            $file_idx = 0;

            if ($doc_type == 'devis') {
                if (BimpTools::getArrayValueFromPath($data, 'replace_devis', 0)) {
                    $file_name_base = pathinfo($file_name, PATHINFO_FILENAME);
                    $file_name = BimpTools::getArrayValueFromPath($data, 'replaced_devis', '');

                    if (!$file_name) {
                        $errors[] = 'Devis à remplacé non sélectionné';
                        return $errors;
                    }

                    if (preg_match('/^' . preg_quote($file_name_base, '/') . '(\-(\d+))\.pdf$/', $file_name, $matches)) {
                        $file_idx = (int) $matches[2];
                    }
                } else {
                    $this->checkDevisFiles($file_idx, true);

                    if ($file_idx > 1) {
                        $file_name = pathinfo($file_name, PATHINFO_FILENAME) . '-' . $file_idx . '.pdf';
                    }
                }
            }

            if ($file_idx) {
                $pdf->signature_file_idx = $file_idx;
            }

            $file_path = $file_dir . $file_name;

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

                if ($doc_type == 'devis') {
                    if ((int) BimpTools::getPostFieldValue('replace_devis', 0)) {
                        $file_name_base = pathinfo($file_name, PATHINFO_FILENAME);
                        $file_name = BimpTools::getPostFieldValue('replaced_devis', '');

                        if (!$file_name) {
                            $errors[] = 'Devis à remplacé non sélectionné';
                            return $errors;
                        }

                        if (preg_match('/^' . preg_quote($file_name_base, '/') . '(\-(\d+))?\.pdf$/', $file_name, $matches)) {
                            $file_idx = (int) $matches[2];

                            if ($file_idx) {
                                $signature_params = $this->getData('signature_devis_params');

                                if (isset($signature_params[$file_idx])) {
                                    unset($signature_params[$file_idx]);
                                    $this->updateField('signature_devis_params', $signature_params);
                                }
                            } else {
                                $this->updateField('signature_devis_params', array());
                            }
                        }

                        unlink($dir . $file_name);
                    } else {
                        $this->checkDevisFiles($file_idx, true);

                        if ($file_idx > 1) {
                            $file_name = pathinfo($file_name, PATHINFO_FILENAME) . '-' . $file_idx . '.pdf';
                        }
                    }
                }

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

    public function submitDoc($doc_type, &$warnings = array())
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

            $dir = $this->getFilesDir();
            $file = $dir . $this->getSignatureDocFileName($doc_type);
            $devis_files = array();

            if ($doc_type === 'devis') {
                $devis_files = $this->getDevisFilesArray(false);
            }

            if (!file_exists($file) && ($doc_type !== 'devis' || empty($devis_files))) {
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
                    if ($doc_type == 'devis') {
                        $docs_content = array();
                        foreach ($devis_files as $devis_file) {
                            $docs_content[] = base64_encode(file_get_contents($dir . $devis_file));
                        }
                    } else {
                        $docs_content = array(base64_encode(file_get_contents($file)));
                    }

                    $docs_content = json_encode($docs_content);

                    $signature_params = json_encode($this->getData('signature_' . $doc_type . '_params'));
                    $signataires_data = '';
                    switch ($doc_type) {
                        case 'contrat':
                            $signataires_data = $this->getData('contrat_signataires_data');
                            if (!BimpTools::getArrayValueFromPath($signataires_data, 'cessionnaire/nom', '')) {
                                $id_refin = (int) $this->getSelectedDemandeRefinanceurData('id_refinanceur');
                                if ($id_refin) {
                                    $refin = BimpCache::getBimpObjectInstance('bimpfinancement', 'BF_Refinanceur', $id_refin);
                                    if (BimpObject::objectLoaded($refin)) {
                                        
                                    }
                                }
                            }
                            $signataires_data = json_encode($signataires_data);
                            break;

                        case 'pvr':
                            $signataires_data = json_encode($this->getData('pvr_signataires_data'));
                            break;
                    }


                    $api->sendDocFinancement($this->id, $type_origine, $id_origine, $doc_type, $docs_content, $signature_params, $signataires_data, $req_errors);

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
                            $this->addObjectLog('Document "' . self::$doc_types[$doc_type] . '" signé', strtoupper($doc_type) . '_SIGNE');
                            $this->addNote('Document "' . self::$doc_types[$doc_type] . '" signé', null, 0, 0, '', BimpNote::BN_AUTHOR_USER, BImpNote::BN_DEST_USER, 0, (int) $this->getData('id_user_resp'), 1);
                            $this->checkIsClosed();

                            $sources = $this->getChildrenObjects('sources');

                            BimpObject::loadClass('bimpcommercial', 'BimpCommDemandeFin');
                            foreach ($sources as $source) {
                                if ((int) $this->getData('id_main_source') == $source->id) {
                                    continue;
                                }

                                $source->setDocFinStatus($doc_type, BimpCommDemandeFin::DOC_STATUS_ACCEPTED);
                            }
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
                $this->set($doc_type . '_status', self::DOC_REFUSED);

                $warnings = array();
                $up_errors = $this->update($warnings, true);
                if (count($up_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec de la mise à jour du statut du document "' . $this->getDocTypeLabel($doc_type)) . '"';
                } else {
                    $msg = 'Document "' . $this->getDocTypeLabel($doc_type) . '" refusé' . ($note ? '.<br/><b>Raisons : </b>' . $note : '');
                    $this->addObjectLog($msg, strtoupper($doc_type) . '_REFUSED');
                    $this->addNote($msg, null, 0, 0, '', BimpNote::BN_AUTHOR_USER, BImpNote::BN_DEST_USER, 0, (int) $this->getData('id_user_resp'), 1);
                    $this->checkIsClosed();

                    $sources = $this->getChildrenObjects('sources');

                    BimpObject::loadClass('bimpcommercial', 'BimpCommDemandeFin');
                    foreach ($sources as $source) {
                        if ((int) $this->getData('id_main_source') == $source->id) {
                            continue;
                        }

                        $source->setDocFinStatus($doc_type, BimpCommDemandeFin::DOC_STATUS_REFUSED);
                    }
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

            $pvr_status = (int) $this->getData('pvr_status');
            if ($pvr_status > 0 && $pvr_status < 10) {
                $this->set('pvr_status', self::DOC_CANCELLED);
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

                $sources = $this->getChildrenObjects('sources');

                BimpObject::loadClass('bimpcommercial', 'BimpCommDemandeFin');
                foreach ($sources as $source) {
                    if ((int) $this->getData('id_main_source') == $source->id) {
                        continue;
                    }

                    $source->setDemandeFinancementStatus(BimpCommDemandeFin::DOC_STATUS_CANCELED, $note);
                }
            }
        }

        return $errors;
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

    public function setSerialsFromSource($serials, $id_source = 0)
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            $lines = $this->getLines('not_text');
            $attributions = array();

            // Trie des serials à attribuer: 
            foreach ($serials as $ref_prod => $prod_serials) {
                foreach ($lines as $line) {
                    if (empty($prod_serials)) {
                        break;
                    }

                    if ($id_source && (int) $id_source !== (int) $line->getData('id_source')) {
                        continue;
                    }

                    $ref = $line->getRefProduct();
                    if ($ref && $ref === $ref_prod) {
                        if (isset($attributions[$line->id])) {
                            continue;
                        }

                        $line_qty = (int) $line->getData('qty');
                        $attributions[$line->id] = array();
                        $nDone = 0;
                        foreach ($prod_serials as $idx => $serial) {
                            if ($nDone >= $line_qty) {
                                break;
                            }

                            $attributions[$line->id][] = $serial;
                            $nDone++;
                            unset($prod_serials[$idx]);
                            unset($serials[$ref_prod][$idx]);
                        }
                    }
                }

                if (empty($serials[$ref_prod])) {
                    unset($serials[$ref_prod]);
                }
            }

            // Attribution des serials:
            foreach ($attributions as $id_line => $line_serials) {
                $line = BimpCache::getBimpObjectInstance('bimpfinancement', 'BF_Line', $id_line);
                if (BimpObject::objectLoaded($line)) {
                    $err = $line->updateField('serials', $line_serials);
                    if (count($err)) {
                        $errors[] = BimpTools::getMsgFromArray($err, 'Echec de l\'enregistrement des n° de série pour la réf. "' . $line->getRefProduct() . '" (Ligne n° ' . $line->getData('position') . ')');
                    }
                }
            }

            // Vérif des ns non attribués:
            if (!empty($serials)) {
                foreach ($serials as $ref_prod => $prod_serials) {
                    if (count($prod_serials)) {
                        $errors[] = 'Ref. "' . $ref_prod . '" : ' . count($prod_serials) . ' numéro(s) de série non atrribué(s)';
                    }
                }
            }
        }

        return $errors;
    }

    public function createFactureFournisseur($id_fourn = 0, $id_mode_reglement = 0, $id_cond_reglement = 0, $ref_supplier = '', $libelle = '', &$warnings = array())
    {
        $errors = array();

        if (!$this->isLoaded($errors)) {
            return $errors;
        }

        $source = null;

        if ((int) $this->getData('id_main_source')) {
            $source = $this->getSource();

            if (!$id_fourn) {
                $id_fourn = (int) BimpCore::getConf('id_fourn_' . $source->getData('type'), null, 'bimpfinancement');

                if (!$id_fourn) {
                    $errors[] = 'Paramètre de configuration "ID Fournisseur ' . $source::$types[$source->getData('type')] . '" non défini';
                }
            }
        }

        if (!(int) $id_fourn) {
            $errors[] = 'ID Fournisseur absent';
        }

        if (count($errors)) {
            return $errors;
        }

        $fourn = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Fournisseur', $id_fourn);
        if (!BimpObject::objectLoaded($fourn)) {
            $errors[] = 'Le fournisseur #' . $id_fourn . ' n\'existe pas';
            return $errors;
        }

        $note = '<b>Client Final : </b>' . strip_tags($this->displayClient());
        if ($this->getData('agreement_number')) {
            $note .= '<br/><b>N° D\'accord : </b>' . $this->getData('agreement_number');
        }

        $facture = BimpObject::createBimpObject('bimpcommercial', 'Bimp_FactureFourn', array(
                    'libelle'           => $libelle,
                    'ref_supplier'      => $ref_supplier,
                    'fk_soc'            => $fourn->id,
                    'fk_mode_reglement' => $id_mode_reglement,
                    'fk_cond_reglement' => $id_cond_reglement,
                    'datef'             => date('Y-m-d'),
                    'note_public'       => $note
                        ), true, $errors, $warnings);

        if (!BimpObject::objectLoaded($facture) && empty($errors)) {
            $errors[] = 'Echec de la création de la facture fournisseur pour une raison inconnue';
        }

        if (!count($errors)) {
            $lines_errors = $this->addBimpCommObjectLines($facture);

            if (count($lines_errors)) {
                $errors[] = BimpTools::getMsgFromArray($lines_errors, 'Erreurs lors de l\'ajout des lignes à la facture fournisseur');
            }
        }

        if (!count($errors)) {
            $this->updateField('id_facture_fourn', $facture->id);
            $facture->dol_object->add_object_linked('bf_demande', $this->id);
        }

        return $errors;
    }

    public function createFactureFin($id_client = 0, $libelle = '', $id_mode_reglement = 0, $id_cond_reglement = 0, &$warnings = array())
    {
        $errors = array();

        $id_refin = (int) $this->getSelectedDemandeRefinanceurData('id_refinanceur');
        if (!$id_refin) {
            $errors[] = 'Aucune demande refinanceur sélectionnée';
            return $errors;
        }

        $refin = BimpCache::getBimpObjectInstance('bimpfinancement', 'BF_Refinanceur', $id_refin);
        if (!BimpObject::objectLoaded($refin)) {
            $errors[] = 'Le refinanceur #' . $id_refin . ' n\'existe pas';
            return $errors;
        }

        if (!$id_client) {
            $id_client = (int) $refin->getData('id_societe');
        }

        if (!$id_client) {
            $errors[] = 'ID Client absent pour le refianceur ' . $refin->getLink();
            return $errors;
        }

        $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $id_client);
        if (!BimpObject::objectLoaded($client)) {
            $errors[] = 'Le client #' . $id_client . ' n\'existe pas';
            return $errors;
        }

        $prix_cession = (float) $this->getSelectedDemandeRefinanceurData('prix_cession_ht');

        if (!$prix_cession) {
            $errors[] = 'Prix de cession total HT non défini pour la demande refinanceur sélectionnée';
            return $errors;
        }

        $note = '<b>Client Final : </b>' . strip_tags($this->displayClient());
        if ($this->getData('agreement_number')) {
            $note .= '<br/><b>N° D\'accord : </b>' . $this->getData('agreement_number');
        }

        $facture = BimpObject::createBimpObject('bimpcommercial', 'Bimp_Facture', array(
                    'libelle'           => $libelle,
                    'fk_soc'            => $client->id,
                    'type_vente'        => 1,
                    'model_pdf'         => 'bimpfact',
                    'type'              => 0,
                    'fk_mode_reglement' => (int) $id_mode_reglement,
                    'fk_cond_reglement' => (int) $id_cond_reglement,
                    'datef'             => date('Y-m-d'),
                    'note_public'       => $note
                        ), true, $errors, $warnings);

        if (!BimpObject::objectLoaded($facture) && empty($errors)) {
            $errors[] = 'Echec de la création de la facture pour une raison inconnue';
        }

        if (!count($errors)) {
            $lines_errors = $this->addBimpCommObjectLines($facture, $prix_cession);

            if (count($lines_errors)) {
                $errors[] = BimpTools::getMsgFromArray($lines_errors, 'Erreurs lors de l\'ajout des lignes à la facture');
            }
        }

        if (!count($errors)) {
            $this->updateField('id_facture_fin', $facture->id);
            $facture->dol_object->add_object_linked('bf_demande', $this->id);
        }

        return $errors;
    }

    protected function addBimpCommObjectLines($bimpcomm, $total_attendu_ht = 0, $onlyProd = false, $total_achat_attendu = 0)
    {
        $errors = array();

        if (!$onlyProd)
            $lines = $this->getLines();
        else
            $lines = $this->getLines('only_prod');

        $pourcentage = 1;
        if ($total_attendu_ht) {
            if (!$onlyProd)
                $pourcentage = $total_attendu_ht / $this->getTotalDemandeHT();
            else
                $pourcentage = $total_attendu_ht / $this->getTotalDemandeHTOnlyProd();
        }

        $pourcentage_achat = 1;
        if ($total_achat_attendu) {
            $total_achat = 0;

            foreach ($lines as $line) {
                $total_achat += ($line->getData('pa_ht') * $line->getData('qty'));
            }

            if ($total_achat) {
                $pourcentage_achat = $total_achat_attendu / $total_achat;
            }
        }

        foreach ($lines as $line) {
            $line_type = (int) $line->getData('type');

            $fac_line = BimpObject::getInstance('bimpcommercial', $bimpcomm->object_name . 'Line');
            $fac_line->validateArray(array(
                'id_obj'             => (int) $bimpcomm->id,
                'type'               => $line->getTypeForBimpCommObjectLine(),
                'remisable'          => 1,
                'linked_id_object'   => (int) $line->id,
                'linked_object_name' => 'bf_demande_line',
                'pa_editable'        => 1
            ));

            if ($line_type !== BF_Line::TYPE_TEXT) {
                $fac_line->qty = $line->getData('qty');
                $fac_line->pu_ht = $line->getData('pu_ht') * $pourcentage;
                $fac_line->tva_tx = $line->getData('tva_tx');
                $fac_line->pa_ht = $line->getData('pa_ht') * $pourcentage_achat;
                $fac_line->product_type = ((int) $line->getData('product_type') === BF_Line::PRODUIT ? 0 : 1);
            }

            if ($line_type === BF_Line::TYPE_PRODUCT) {
                $fac_line->id_product = $line->getData('id_product');
                $fac_line->id_fourn_price = $line->getData('id_fourn_price');
            }

            $fac_line->desc = $line->displayDesc(false, true, true);

            $line_warnings = array();
            $line_errors = $fac_line->create($line_warnings, true);

            if (!count($line_errors)) {
                if ($line->getData('remise') > 0) {
                    BimpObject::createBimpObject('bimpcommercial', 'ObjectLineRemise', array(
                        'id_object_line'           => (int) $fac_line->id,
                        'object_type'              => $fac_line::$parent_comm_type,
                        'linked_id_remise_globale' => 0,
                        'type'                     => ObjectLineRemise::OL_REMISE_PERCENT,
                        'percent'                  => (float) $line->getData('remise')
                            ), $errors, $errors);
                }
            }

            if (count($line_errors)) {
                $errors[] = BimpTools::getMsgFromArray($line_errors, 'Ligne n° ' . $line->getData('position'));
            }
        }

        return $errors;
    }

    public function forceDocSigned($doc_type, &$warnings = array())
    {
        $errors = array();

        $field_name = $doc_type . '_status';
        $errors = $this->updateField($field_name, self::DOC_ACCEPTED);

        if (!count($errors)) {
            BimpObject::loadClass('bimpcommercial', 'BimpCommDemandeFin');
//            $sources = $this->getChildrenObjects('sources');
//            foreach ($sources as $source) {

            $source = $this->getSource();

            if (!BimpObject::objectLoaded($source)) {
//                $errors[] = 'Aucune source principale';
            } else {
                $src_errors = $source->setDocFinStatus($doc_type, BimpCommDemandeFin::DOC_STATUS_ACCEPTED);

                if (count($src_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($src_errors, 'Source "' . $source->displayName() . '"');
                }
            }

//            }

            if (!count($errors)) {
                $this->addObjectLog(static::getDocTypeLabel($doc_type) . ' forcé au statut "Signé"', strtoupper($doc_type) . '_FORCED_SIGNED');
            }
        }

        return $errors;
    }

    public function createFactureFournRevente($id_fourn = 0, $id_mode_reglement = 0, $id_cond_reglement = 0, $ref_supplier = '', $libelle = '', &$warnings = array())
    {
        $errors = array();

        if (!$this->isLoaded($errors)) {
            return $errors;
        }

        $total_rachat_ht = (float) $this->getData('total_rachat_ht');

        if (!$total_rachat_ht) {
            $errors[] = 'Total Rachat HT non défini';
            return $errors;
        }

        $id_refin = (int) $this->getSelectedDemandeRefinanceurData('id_refinanceur');
        if (!$id_refin) {
            $errors[] = 'Aucune demande refinanceur sélectionnée';
            return $errors;
        }

        $refin = BimpCache::getBimpObjectInstance('bimpfinancement', 'BF_Refinanceur', $id_refin);
        if (!BimpObject::objectLoaded($refin)) {
            $errors[] = 'Le refinanceur #' . $id_refin . ' n\'existe pas';
            return $errors;
        }

        if (!$id_fourn) {
            $id_fourn = (int) $refin->getData('id_societe');
        }

        if (!(int) $id_fourn) {
            $errors[] = 'Fournisseur absent';
        }

        if (count($errors)) {
            return $errors;
        }

        $fourn = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Fournisseur', $id_fourn);
        if (!BimpObject::objectLoaded($fourn)) {
            $errors[] = 'Le fournisseur #' . $id_fourn . ' n\'existe pas';
            return $errors;
        }

        $facture = BimpObject::createBimpObject('bimpcommercial', 'Bimp_FactureFourn', array(
                    'libelle'           => $libelle,
                    'ref_supplier'      => $ref_supplier,
                    'fk_soc'            => $fourn->id,
                    'fk_mode_reglement' => $id_mode_reglement,
                    'fk_cond_reglement' => $id_cond_reglement,
                    'datef'             => date('Y-m-d')
                        ), true, $errors, $warnings);

        if (!BimpObject::objectLoaded($facture) && empty($errors)) {
            $errors[] = 'Echec de la création de la facture fournisseur de rachat pour une raison inconnue';
        }

        if (!count($errors)) {
            $lines_errors = $this->addBimpCommObjectLines($facture, $total_rachat_ht, true);

            if (count($lines_errors)) {
                $errors[] = BimpTools::getMsgFromArray($lines_errors, 'Erreurs lors de l\'ajout des lignes à la facture fournisseur');
            }
        }

        if (!count($errors)) {
            $this->updateField('id_facture_fourn_rev', $facture->id);
            $facture->dol_object->add_object_linked('bf_demande', $this->id);
        }

        return $errors;
    }

    public function createFactureCliRevente($id_client = 0, $libelle = '', $id_mode_reglement = 0, $id_cond_reglement = 0, &$warnings = array())
    {
        $errors = array();

        if (!$id_client) {
            $errors[] = 'Aucun client sélectionné';
            return $errors;
        }

        $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $id_client);
        if (!BimpObject::objectLoaded($client)) {
            $errors[] = 'Le client #' . $id_client . ' n\'existe pas';
            return $errors;
        }

        $prix_cession_ht = (float) $this->getSelectedDemandeRefinanceurData('prix_cession_ht');

        if (!$prix_cession_ht) {
            $errors[] = 'Prix de cession total HT non défini pour la demande refinanceur sélectionnée';
        }

        $vr_vente = (float) $this->getData('vr_vente');

        if (!$vr_vente) {
            $errors[] = 'VR vente absente';
        }

        $total_rachat_ht = (float) $this->getData('total_rachat_ht');

        if (!$total_rachat_ht) {
            $errors[] = 'Total Rachat HT non défini';
        }

        if (!count($errors)) {
            $facture = BimpObject::createBimpObject('bimpcommercial', 'Bimp_Facture', array(
                        'libelle'           => $libelle,
                        'type_vente'        => 3,
                        'fk_soc'            => $client->id,
                        'model_pdf'         => 'bimpfact',
                        'type'              => 0,
                        'fk_mode_reglement' => (int) $id_mode_reglement,
                        'fk_cond_reglement' => (int) $id_cond_reglement,
                        'datef'             => date('Y-m-d')
                            ), true, $errors, $warnings);

            if (!BimpObject::objectLoaded($facture) && empty($errors)) {
                $errors[] = 'Echec de la création de la facture pour une raison inconnue';
            }

            if (!count($errors)) {
                $total_attendu_ht = $prix_cession_ht * $vr_vente / 100;
                $lines_errors = $this->addBimpCommObjectLines($facture, $total_attendu_ht, true, $total_rachat_ht);

                if (count($lines_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($lines_errors, 'Erreurs lors de l\'ajout des lignes à la facture');
                }
            }

            if (!count($errors)) {
                $this->updateField('id_facture_cli_rev', $facture->id);
                $facture->dol_object->add_object_linked('bf_demande', $this->id);
            }
        }

        return $errors;
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

        if (!(int) $this->getData('id_main_source')) {
            $this->set('closed', 1);
        }
        $this->set('status', self::STATUS_CANCELED);

        $devis_status = (int) $this->getData('devis_status');
        if ($devis_status > 0 && $devis_status < 10) {
            $this->set('devis_status', self::DOC_CANCELLED);
        }

        $contrat_status = (int) $this->getData('contrat_status');
        if ($contrat_status > 0 && $contrat_status < 10) {
            $this->set('contrat_status', self::DOC_CANCELLED);
        }

        $pvr_status = (int) $this->getData('pvr_status');
        if ($pvr_status > 0 && $pvr_status < 10) {
            $this->set('pvr_status', self::DOC_CANCELLED);
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

        if (!(int) $this->getData('id_user_resp')) {
            $this->set('status', self::STATUS_NEW);
        } else {
            $this->set('status', self::STATUS_DRAFT);

            if (!count($errors)) {
                $this->addObjectLog('Demande de location réouverte', 'REOPEN');
            }

            $this->set('closed', 0);

            $errors = $this->update($warnings, true);

            if (!count($errors)) {
                $sources = $this->getChildrenObjects('sources');
                if (is_array($sources) && !empty($sources)) {
                    $source = $this->getSource();

                    if (BimpObject::objectLoaded($source)) {
                        $source->updateField('cancel_submitted', 0);
                        $source->updateField('refuse_submitted', 0);
                        $errors = $this->checkStatus($warnings);
                    } else {
                        $errors[] = 'Source principale absente';
                    }

                    if (!count($errors)) {
                        foreach ($sources as $source) {
                            $src_warnings = array();
                            $src_errors = $source->reopenDemande((int) $this->getData('status'), $src_warnings);

                            if (count($src_errors)) {
                                $errors[] = BimpTools::getMsgFromArray($src_errors, 'Source "' . $source->displayName() . '"');
                            }
                        }
                    }
                } else {
                    $errors = $this->checkStatus($warnings);

                    $devis_status = 0;
                    $dir = $this->getFilesDir();
                    $signature = $this->getChildObject('signature_contrat');
                    if (BimpObject::objectLoaded($signature) && $signature->isSigned()) {
                        $this->set('contrat_status', self::DOC_ACCEPTED);
                        $devis_status = self::DOC_ACCEPTED;
                    } else {
                        $file = $this->getSignatureDocFileName('contrat');
                        if (file_exists($dir . $file)) {
                            $this->set('contrat_status', self::DOC_GENERATED);
                            $devis_status = self::DOC_ACCEPTED;
                        }
                    }

                    if (!$devis_status) {
                        $signature = $this->getChildObject('signature_devis');
                        if (BimpObject::objectLoaded($signature) && $signature->isSigned()) {
                            $devis_status = self::DOC_ACCEPTED;
                        } else {
                            $file = $this->getSignatureDocFileName('devis');
                            if (file_exists($dir . $file)) {
                                $devis_status = self::DOC_GENERATED;
                            }
                        }
                    }
                    $this->set('devis_status', $devis_status);
                }

                $signature = $this->getChildObject('signature_pvr');
                if (BimpObject::objectLoaded($signature) && $signature->isSigned()) {
                    $this->set('pvr_status', self::DOC_ACCEPTED);
                } else {
                    $file = $this->getSignatureDocFileName('pvr');
                    if (file_exists($dir . $file)) {
                        $this->set('pvr_status', self::DOC_GENERATED);
                    }
                }
                $errors = $this->update($warnings, true);
                $this->checkIsClosed();
            }
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
        $sc = '';

        $type_pdf = BimpTools::getArrayValueFromPath($data, 'type_pdf', '');
        if (!$type_pdf) {
            $errors[] = 'Aucun modèle de PDF sélectionné';
        }

        $formule = BimpTools::getArrayValueFromPath($data, 'formule', $this->getData('formule'));
        if ($formule != $this->getData('formule')) {
            $this->updateField('formule', $formule);
        }

        $is_company = (int) BimpTools::getArrayValueFromPath($data, 'client_is_company', 0);
        $client_data = array(
            'is_company'   => $is_company,
            'nom'          => BimpTools::getArrayValueFromPath($data, 'client_name', '', $errors, true, 'Nom du client absent'),
            'address'      => BimpTools::getArrayValueFromPath($data, 'client_address', '', $errors, true, 'Adresse du client absente'),
            'representant' => BimpTools::getArrayValueFromPath($data, 'client_representant', '', $errors, true, 'Nom du représenant du client absent'),
            'livraisons'   => BimpTools::getArrayValueFromPath($data, 'client_livraisons', '')
        );

        if ($is_company) {
            $client_data['forme_juridique'] = BimpTools::getArrayValueFromPath($data, 'client_forme_juridique', '', $errors, true, ' absente');
            $client_data['capital'] = BimpTools::getArrayValueFromPath($data, 'client_capital', '', $errors, true, 'Capital social du client absent');
            $client_data['siren'] = BimpTools::getArrayValueFromPath($data, 'client_siren', '', $errors, true, 'N° SIREN du client absent');
            $client_data['insee'] = (int) BimpTools::getArrayValueFromPath($data, 'client_insee', 0);
            $client_data['repr_qualite'] = BimpTools::getArrayValueFromPath($data, 'client_repr_qualite', '', $errors, true, 'Qualité du représenant du client absente');
            $client_data['email'] = BimpTools::getArrayValueFromPath($data, 'client_email', '', $errors, true, 'Adresse e-mail du client absente');

            if (!$client_data['insee']) {
                $client_data['rcs'] = BimpTools::getArrayValueFromPath($data, 'client_rcs', '', $errors, true, 'Ville d\'enregistrement au RCS absente');
            }
        }

        $loueur_data = array(
            'nom'     => BimpTools::getArrayValueFromPath($data, 'loueur_nom', '', $errors, true, 'Nom du signataire loueur absent'),
            'qualite' => BimpTools::getArrayValueFromPath($data, 'loueur_qualite', '', $errors, true, 'Qualité du signataire loueur absente')
        );

        $cessionnaire_data = array(
            'raison_social' => BimpTools::getArrayValueFromPath($data, 'cessionnaire_saison_sociale', ''),
            'siren'         => BimpTools::getArrayValueFromPath($data, 'cessionnaire_siren', ''),
            'nom'           => BimpTools::getArrayValueFromPath($data, 'cessionnaire_nom', ''),
            'qualite'       => BimpTools::getArrayValueFromPath($data, 'cessionnaire_qualite', '')
        );

        $loueur_email = BimpTools::getArrayValueFromPath($data, 'loueur_email', '', $errors, true, 'Adresse e-mail du signataire loueur absent');
        $cessionnaire_email = BimpTools::getArrayValueFromPath($data, 'cessionnaire_email', '');

        if (!$cessionnaire_email && $type_pdf !== 'papier') {
            $errors[] = 'Adresse e-mail du signataire cessionnaire absent (obligatoire pour signature électronique)';
        }

        if (!count($errors)) {
            $this->updateField('contrat_signataires_data', array(
                'locataire'    => array(
                    'nom'      => $client_data['representant'],
                    'fonction' => $client_data['repr_qualite']
                ),
                'loueur'       => array(
                    'nom'      => $loueur_data['nom'],
                    'email'    => $loueur_email,
                    'fonction' => $loueur_data['qualite']
                ),
                'cessionnaire' => array(
                    'raison_social' => $cessionnaire_data['raison_social'],
                    'nom'           => $cessionnaire_data['nom'],
                    'email'         => $cessionnaire_email,
                    'fonction'      => $cessionnaire_data['qualite']
                )
            ));
            if ($this->isDemandeValid($errors)) {
                global $db;
                $files_dir = $this->getFilesDir();
                $ref = $this->getRef();
                $file_name = $this->getSignatureDocFileName('contrat');

                switch ($type_pdf) {
                    case 'papier':
                        // PDF Consignes: 
                        $consignes_file_name = 'consignes_' . $ref . '.pdf';
                        require_once DOL_DOCUMENT_ROOT . '/bimpfinancement/pdf/ConsignesContratFinancementPDF.php';
                        $pdf = new ConsignesContratFinancementPDF($db, $this);
                        if (!$pdf->render($files_dir . $consignes_file_name, 'F')) {
                            $errors[] = BimpTools::getMsgFromArray($pdf->errors, 'Echec de la création du fichier PDF des consignes client');
                        }

                        $contrat_file_name = $this->getSignatureDocFileName('contrat') . '_tmp';
                        require_once DOL_DOCUMENT_ROOT . '/bimpfinancement/pdf/ContratFinancementPDF.php';
                        $pdf = new ContratFinancementPDF($db, $this, $client_data, $loueur_data, $cessionnaire_data, 'papier');
                        $pdf->render($files_dir . $contrat_file_name, 'F');
                        if (count($pdf->errors)) {
                            $errors[] = BimpTools::getMsgFromArray($pdf->errors, 'Echec de la création du fichier PDF du contrat de location');
                        }

                        $mandat_file_name = 'mandat_sepa_' . $ref . '.pdf';
                        require_once DOL_DOCUMENT_ROOT . '/bimpfinancement/pdf/MandatSepaFinancementPDF.php';
                        $pdf = new MandatSepaFinancementPDF($db, $client_data);
                        if (!$pdf->render($files_dir . $mandat_file_name, 'F')) {
                            $errors[] = BimpTools::getMsgFromArray($pdf->errors, 'Echec de la création du fichier PDF des consignes client');
                        }

                        if (!count($errors)) {
                            $pdf = new BimpConcatPdf();
                            $pdf->concatFiles($files_dir . $file_name, array(
                                $files_dir . $consignes_file_name,
                                $files_dir . $contrat_file_name,
                                $files_dir . $contrat_file_name,
                                $files_dir . $contrat_file_name,
                                $files_dir . $mandat_file_name
                                    ), 'F');
                        }
                        unlink($files_dir . $consignes_file_name);
                        unlink($files_dir . $contrat_file_name);
                        unlink($files_dir . $mandat_file_name);
                        break;

                    case 'elec':
                        // PDF contrat de location: 
                        require_once DOL_DOCUMENT_ROOT . '/bimpfinancement/pdf/ContratFinancementPDF.php';
                        $pdf = new ContratFinancementPDF($db, $this, $client_data, $loueur_data, $cessionnaire_data, 'elec');
                        $pdf->render($files_dir . $file_name, 'F');
                        if (count($pdf->errors)) {
                            $errors[] = BimpTools::getMsgFromArray($pdf->errors, 'Echec de la création du fichier PDF du contrat de location');
                        }
                        break;
                }

                if (!count($errors)) {
                    $up_errors = $this->updateField('contrat_status', self::DOC_GENERATED);
                    if (count($up_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($up_errors, 'Echec de l\'enregistrement du nouveau statut du contrat de location');
                    }

                    if (file_exists($files_dir . $file_name)) {
                        $url = $this->getFileUrl($file_name);
                        if ($url) {
                            $sc = 'window.open(\'' . $url . '\')';
                        }
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

    public function actionGeneratePVReception($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Génération du PV de réception effectuée avec succès';

        $errors = $this->generateDocument('pvr', $data, $warnings, $success);

        if (!count($errors)) {
            $signataires_data = array(
                'locataire' => array(
                    'nom'      => BimpTools::getArrayValueFromPath($data, 'client_representant', ''),
                    'fonction' => BimpTools::getArrayValueFromPath($data, 'client_repr_qualite', '')
                ),
                'loueur'    => array(
                    'nom'      => BimpTools::getArrayValueFromPath($data, 'loueur_nom', ''),
                    'fonction' => BimpTools::getArrayValueFromPath($data, 'loueur_qualite'),
                    'email'    => BimpTools::getArrayValueFromPath($data, 'loueur_email', '')
                )
            );

            $err = $this->updateField('pvr_signataires_data', $signataires_data);

            $file_name = $this->getSignatureDocFileName('pvr');
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

    public function actionForceDevisSigned($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Devis de location marqué signé';

        $errors = $this->forceDocSigned('devis', $warnings);

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionForceContratSigned($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Contrat de location marqué signé';

        $errors = $this->forceDocSigned('contrat', $warnings);

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionForcePvrSigned($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'PVR de réception marqué signé';

        $errors = $this->forceDocSigned('pvr', $warnings);

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
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
            $up_errors = $this->updateField('devis_status', self::DOC_SEND);
            if (count($up_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($up_errors, 'Echec de l\'enregistrement du nouveau statut du devis');
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
            $up_errors = $this->updateField('contrat_status', self::DOC_SEND);
            if (count($up_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($up_errors, 'Echec de l\'enregistrement du nouveau statut du contrat');
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionCreateSignaturePvr($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Fiche signature du PV de réception créée avec succès';

        $signature_errors = $this->createSignature('pvr', $data, $warnings);

        if (count($signature_errors)) {
            $errors[] = BimpTools::getMsgFromArray($signature_errors, 'Echec de la création de la fiche signature');
        } else {
            $up_errors = $this->updateField('pvr_status', self::DOC_SEND);
            if (count($up_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($up_errors, 'Echec de l\'enregistrement du nouveau statut du PVR');
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

        $errors = $this->submitDoc('devis', $warnings);

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

        $errors = $this->submitDoc('contrat', $warnings);

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionSubmitPvr($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Envoi du PV de réception à ' . $this->displaySourceName() . ' effectué avec succès';

        $errors = $this->submitDoc('pvr', $warnings);

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

        $note = BimpTools::getArrayValueFromPath($data, 'note', '');
        BimpObject::loadClass('bimpcommercial', 'BimpCommDemandeFin');

        $sources = $this->getChildrenObjects('sources');

        foreach ($sources as $source) {
            $src_errors = $source->setDemandeFinancementStatus(BimpCommDemandeFin::DOC_STATUS_REFUSED, $note);

            if (count($src_errors)) {
                $errors[] = BimpTools::getMsgFromArray($src_errors, 'Source "' . $source->displayName() . '"');
            } else {
                $success .= ($success ? '<br/>' : '') . 'Soumission du refus auprès de ' . $source->displayName() . ' effectuée avec succès';
            }
        }

        if (!count($errors)) {
            $up_errors = $this->updateField('closed', 1);
            if (count($up_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($up_errors, 'Echec de l\'enregistrement du statut "Fermé' . $this->e() . '"');
            } else {
                $this->addObjectLog('Refus définitif - Demande fermée' . ($note ? '<br/><b>Note : </b>' . $note : ''), 'CLOSED');
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

        $note = BimpTools::getArrayValueFromPath($data, 'note', '');
        $sources = $this->getChildrenObjects('sources');
        BimpObject::loadClass('bimpcommercial', 'BimpCommDemandeFin');

        foreach ($sources as $source) {
            $src_errors = $source->setDemandeFinancementStatus(BimpCommDemandeFin::DOC_STATUS_CANCELED, $note);

            if (count($src_errors)) {
                $errors[] = BimpTools::getMsgFromArray($src_errors, 'Source "' . $source->displayName() . '"');
            } else {
                $success .= ($success ? '<br/>' : '') . 'Soumission de l\'abandon auprès de ' . $source->displayName() . ' effectuée avec succès';
            }
        }

        if (!count($errors)) {
            $this->set('closed', 1);
            $this->set('devis_status', self::DOC_CANCELLED);
            $this->set('contrat_status', self::DOC_CANCELLED);
            $this->set('pvr_status', self::DOC_CANCELLED);
            $up_errors = $this->update($warnings, true);

            if (count($up_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($up_errors, 'Echec de l\'enregistrement du statut "Fermé' . $this->e() . '"');
            } else {
                $this->addObjectLog('Annulation notifiée auprès de ' . $this->displaySourceName() . ' - Demande fermée' . ($note ? '<br/><b>Note : </b>' . $note : ''), 'CLOSED');
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
        $merged_demandes = array();

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
                        $source_errors = $source->updateField('id_init_demande', $demande->id);
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

                    $merged_demandes[] = (int) $demande->id;
                    foreach ($demande->getData('merged_demandes') as $id_merged_demande) {
                        if (!in_array((int) $id_merged_demande, $merged_demandes)) {
                            $merged_demandes[] = (int) $id_merged_demande;
                        }
                    }

                    // Suppression de la demande: 
                    $demande_warnings = array();
                    $demande_errors = $demande->delete($demande_warnings, true);

                    if (count($demande_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($demande_errors, 'Echec de la suppression de la demande ' . $demande->getRef());
                    }
                }

                if (!count($errors)) {
                    $demande_to_keep = BimpCache::getBimpObjectInstance('bimpfinancement', 'BF_Demande', $id_demande_to_keep);

                    if (BimpObject::objectLoaded($demande_to_keep)) {
                        foreach ($demande_to_keep->getData('merged_demandes') as $id_merged_demande) {
                            if (!in_array((int) $id_merged_demande, $merged_demandes)) {
                                $merged_demandes[] = (int) $id_merged_demande;
                            }
                        }

                        $demande_to_keep->updateField('merged_demandes', $merged_demandes);

                        // Recalcul totaux: 
                        $demande_to_keep->calcLinesMontants();
                    } else {
                        $errors[] = 'Demande à conserver #' . $id_demande_to_keep . ' n\'existe plus';
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

    public function actionGeneratePropositionLocation($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $sc = '';

        $file_name = self::createPropositionPDF($data, $errors);

        if (!count($errors)) {
            $dir = DOL_DATA_ROOT . '/bimpfinancement/';
            $sub_dir = 'propositions/' . date('Y-m-d') . '/';
            if (!file_exists($dir . $sub_dir . $file_name)) {
                $errors[] = 'Echec de la création du document PDF pour une raison inconnue';
            } else {
                $url = DOL_URL_ROOT . '/document.php?modulepart=bimpfinancement&file=' . urlencode($sub_dir . $file_name);
                $sc = 'window.open(\'' . $url . '\');';
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $sc
        );
    }

    public function actionCreateFactures($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $sc = '';

//        $use_db_transactions = (int) BimpCore::getConf('use_db_transactions', null);
        $use_db_transactions = 0;
        if ($use_db_transactions) {
            $this->db->db->commit();
        }

        if ((int) BimpTools::getArrayValueFromPath($data, 'create_fac_fourn', 0)) {
            if ((int) $this->getData('id_facture_fourn')) {
                $errors[] = 'La facture fournisseur a déjà été créée';
            } else {
                if ($use_db_transactions) {
                    $this->db->db->begin();
                }

                $libelle = BimpTools::getArrayValueFromPath($data, 'fac_fourn_libelle', '');
                $ref_supplier = BimpTools::getArrayValueFromPath($data, 'fac_fourn_ref_supplier', '');
                $id_fourn = (int) BimpTools::getArrayValueFromPath($data, 'fac_fourn_id_fourn', 0);
                $id_mode_reglement = (int) BimpTools::getArrayValueFromPath($data, 'fac_fourn_id_mode_reglement', 0);
                $id_cond_reglement = (int) BimpTools::getArrayValueFromPath($data, 'fac_fourn_id_cond_reglement', 0);

                $fac_errors = $this->createFactureFournisseur($id_fourn, $id_mode_reglement, $id_cond_reglement, $ref_supplier, $libelle, $warnings);

                if (count($fac_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($fac_errors, 'Echec de la création de la facture fournisseur');

                    if ($use_db_transactions) {
                        $this->db->db->rollback();
                    }
                } else {
                    if ($use_db_transactions) {
                        $this->db->db->commit();
                    }

                    $success = 'Facture fournisseur créée avec succès';

                    $fac = $this->getChildObject('facture_fourn');
                    if (BimpObject::objectLoaded($fac)) {
                        $url = $fac->getUrl();
                        if ($url) {
                            $sc .= 'window.open(\'' . $url . '\');';
                        }
                    }
                }
            }
        }

        if ((int) BimpTools::getArrayValueFromPath($data, 'create_fac_fin', 0)) {
            if ((int) $this->getData('id_facture_fin')) {
                $errors[] = 'La facture financeur a déjà été créée';
            } else {
                if ($use_db_transactions) {
                    $this->db->db->begin();
                }

                $id_client = (int) BimpTools::getArrayValueFromPath($data, 'fac_fin_id_client', 0);
                $libelle = BimpTools::getArrayValueFromPath($data, 'fac_fin_libelle', '');
                $id_mode_reglement = (int) BimpTools::getArrayValueFromPath($data, 'fac_fin_id_mode_reglement', 0);
                $id_cond_reglement = (int) BimpTools::getArrayValueFromPath($data, 'fac_fin_id_cond_reglement', 0);

                $fac_errors = $this->createFactureFin($id_client, $libelle, $id_mode_reglement, $id_cond_reglement, $warnings);

                if (count($fac_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($fac_errors, 'Echec de la création de la facture client');

                    if ($use_db_transactions) {
                        $this->db->db->rollback();
                    }
                } else {
                    if ($use_db_transactions) {
                        $this->db->db->commit();
                    }

                    $success = 'Facture client créée avec succès';

                    $fac = $this->getChildObject('facture_fin');
                    if (BimpObject::objectLoaded($fac)) {
                        $url = $fac->getUrl();
                        if ($url) {
                            $sc .= 'window.open(\'' . $url . '\');';
                        }
                    }
                }
            }
        }

        if ($use_db_transactions) {
            $this->db->db->begin();
        }

        $this->checkIsClosed();

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $sc
        );
    }

    public function actionForceFinContrat($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $date_loyer = BimpTools::getArrayValueFromPath($data, 'date_loyer', '');
        if (!$date_loyer) {
            $errors[] = 'Date de mise en loyer non spécifiée';
        }

        $id_refinanceur = (int) BimpTools::getArrayValueFromPath($data, 'id_refinanceur', 0);
        $factures = BimpTools::getArrayValueFromPath($data, 'factures', array());
        $prix_cession = (float) BimpTools::getArrayValueFromPath($data, 'prix_cession_ht', 0);

        if (!$prix_cession) {
            $errors[] = 'Prix de cession non spécifié';
        }

        if (!$id_refinanceur) {
            $errors[] = 'Aucun refinanceur sélectionné';
        }

        if (!count($errors)) {
            $df = BimpCache::findBimpObjectInstance('bimpfinancement', 'BF_DemandeRefinanceur', array(
                        'id_demande'     => $this->id,
                        'id_refinanceur' => $id_refinanceur
                            ), true);

            if (!BimpObject::objectLoaded($df)) {
                BimpObject::loadClass('bimpfinancement', 'BF_Refinanceur');
                $df = BimpObject::createBimpObject('bimpfinancement', 'BF_DemandeRefinanceur', array(
                            'id_demande'      => $this->id,
                            'id_refinanceur'  => $id_refinanceur,
                            'status'          => BF_DemandeRefinanceur::STATUS_SELECTIONNEE,
                            'qty'             => $this->getNbLoyers(),
                            'periodicity'     => $this->getData('periodicity'),
                            'rate'            => BF_Refinanceur::getTauxMoyen($this->getTotalDemandeHT()),
                            'prix_cession_ht' => $prix_cession
                                ), true, $errors);
            }

            if (BimpObject::objectLoaded($df)) {
                $df->updateField('prix_cession_ht', $prix_cession);
                $df->updateField('status', BF_DemandeRefinanceur::STATUS_SELECTIONNEE);
            }

            $up_errors = $this->validateArray(array(
                'date_loyer'   => $date_loyer,
                'status'       => self::STATUS_ACCEPTED,
                'no_fac_fourn' => (in_array('fac_fourn', $factures) ? 1 : 0),
                'no_fac_fin'   => (in_array('fac_fin', $factures) ? 1 : 0),
            ));

            if (!count($up_errors)) {
                $up_errors = $this->update($warnings, true);

                if (count($up_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec mise à jour des données de la demande de location');
                } else {
                    if ((int) $this->getData('devis_status') !== self::DOC_ACCEPTED) {
                        $err = $this->forceDocSigned('devis');

                        if (count($err)) {
                            $warnings[] = BimpTools::getMsgFromArray($err, 'Erreurs lors de la mise au statut signé du devis');
                        }
                    }
                    if ((int) $this->getData('contrat_status') !== self::DOC_ACCEPTED) {
                        $err = $this->forceDocSigned('contrat');

                        if (count($err)) {
                            $warnings[] = BimpTools::getMsgFromArray($err, 'Erreurs lors de la mise au statut signé du contrat');
                        }
                    }
                    if ((int) $this->getData('pvr_status') !== self::DOC_ACCEPTED) {
                        $err = $this->forceDocSigned('pvr');

                        if (count($err)) {
                            $warnings[] = BimpTools::getMsgFromArray($err, 'Erreurs lors de la mise au statut signé du PVR');
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

    public function actionCreateFacturesRevente($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $sc = '';

//        $use_db_transactions = (int) BimpCore::getConf('use_db_transactions', null);
        $use_db_transactions = 0;
        if ($use_db_transactions) {
            $this->db->db->commit();
        }

        $total_rachat_ht = BimpTools::getArrayValueFromPath($data, 'total_rachat_ht', 0);
        $vr_vente = BimpTools::getArrayValueFromPath($data, 'vr_vente', 0);

        if (!$total_rachat_ht) {
            $errors[] = 'Total rachat HT non défini';
        } elseif ($total_rachat_ht !== (float) $this->getData('total_rachat_ht')) {
            $this->updateField('total_rachat_ht', $total_rachat_ht);
        }

        if (!$vr_vente) {
            $errors[] = 'VR Vente non défini';
        } elseif ($vr_vente !== (float) $this->getData('vr_vente')) {
            $this->updateField('vr_vente', $vr_vente);
        }

        if (!count($errors)) {
            if ((int) BimpTools::getArrayValueFromPath($data, 'create_fac_fourn_rev', 0)) {
                if ((int) $this->getData('id_facture_fourn_rev')) {
                    $errors[] = 'La facture fournisseur de rachat existe déjà';
                } else {
                    if ($use_db_transactions) {
                        $this->db->db->begin();
                    }

                    $libelle = BimpTools::getArrayValueFromPath($data, 'fac_fourn_rev_libelle', '');
                    $ref_supplier = BimpTools::getArrayValueFromPath($data, 'fac_fourn_rev_ref_supplier', '');
                    $id_fourn = (int) BimpTools::getArrayValueFromPath($data, 'fac_fourn_rev_id_fourn', 0);
                    $id_mode_reglement = (int) BimpTools::getArrayValueFromPath($data, 'fac_fourn_rev_id_mode_reglement', 0);
                    $id_cond_reglement = (int) BimpTools::getArrayValueFromPath($data, 'fac_fourn_rev_id_cond_reglement', 0);

                    $fac_errors = $this->createFactureFournRevente($id_fourn, $id_mode_reglement, $id_cond_reglement, $ref_supplier, $libelle, $warnings);

                    if (count($fac_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($fac_errors, 'Echec de la création de la facture fournisseur de rachat');

                        if ($use_db_transactions) {
                            $this->db->db->rollback();
                        }
                    } else {
                        if ($use_db_transactions) {
                            $this->db->db->commit();
                        }

                        $success = 'Facture fournisseur de rachat créée avec succès';

                        $fac = $this->getChildObject('facture_fourn_rev');
                        if (BimpObject::objectLoaded($fac)) {
                            $url = $fac->getUrl();
                            if ($url) {
                                $sc .= 'window.open(\'' . $url . '\');';
                            }
                        }
                    }
                }
            }

            if ((int) BimpTools::getArrayValueFromPath($data, 'create_fac_cli_rev', 0)) {
                if ((int) $this->getData('id_facture_cli_rev')) {
                    $errors[] = 'La facture client de revente existe déjà';
                } else {
                    if ($use_db_transactions) {
                        $this->db->db->begin();
                    }

                    $id_client = (int) BimpTools::getArrayValueFromPath($data, 'fac_cli_rev_id_client', 0);
                    $libelle = BimpTools::getArrayValueFromPath($data, 'fac_cli_rev_libelle', '');
                    $id_mode_reglement = (int) BimpTools::getArrayValueFromPath($data, 'fac_cli_rev_id_mode_reglement', 0);
                    $id_cond_reglement = (int) BimpTools::getArrayValueFromPath($data, 'fac_cli_rev_id_cond_reglement', 0);

                    $fac_errors = $this->createFactureCliRevente($id_client, $libelle, $id_mode_reglement, $id_cond_reglement, $warnings);

                    if (count($fac_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($fac_errors, 'Echec de la création de la facture client de revente');

                        if ($use_db_transactions) {
                            $this->db->db->rollback();
                        }
                    } else {
                        if ($use_db_transactions) {
                            $this->db->db->commit();
                        }

                        $success = 'Facture client créée avec succès';

                        $fac = $this->getChildObject('facture_fin');
                        if (BimpObject::objectLoaded($fac)) {
                            $url = $fac->getUrl();
                            if ($url) {
                                $sc .= 'window.open(\'' . $url . '\');';
                            }
                        }
                    }
                }
            }
        }

        if ($use_db_transactions) {
            $this->db->db->begin();
        }

        $this->checkIsClosed();

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $sc
        );
    }

    // Overrides:

    public function reset()
    {
        $this->values = null;
        $this->missing_serials = null;
        $this->default_values = null;

        parent::reset();
    }

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

    // Gestion signatures : 

    public function isDocuSignAllowed($doc_type, $errors = array(), &$is_required = false)
    {
        if (!(int) BimpCore::getConf($doc_type . '_loc_signature_allow_docusign', null, 'bimpfinancement')) {
            $errors[] = 'Signature DocuSign non autorisée pour le document "' . $this->getDocTypeLabel($doc_type) . '"';
            return 0;
        }

        $is_required = true;
        return 1;
    }

    public function isSignDistAllowed($doc_type, $errors = array())
    {
        if (!(int) BimpCore::getConf($doc_type . '_loc_signature_allow_dist', null, 'bimpfinancement')) {
            $errors[] = 'Signature DocuSign non autorisée pour le document "' . $this->getDocTypeLabel($doc_type) . '"';
            return 0;
        }

        $ds_errors = array();
        $ds_required = false;
        if ($this->isDocuSignAllowed($doc_type, $ds_errors, $ds_required)) {
            if ($ds_required) {
                $errors[] = 'Signature via DocuSign obligatoire pour le document "' . $this->getDocTypeLabel($doc_type) . '"';
                return 0;
            }
        }

        return 1;
    }

    public function renderSignatureTypeSelect($doc_type)
    {
        $errors = array();
        $options = array();
        $value = '';

        $ds_required = false;
        if ($this->isDocuSignAllowed($doc_type, $errors, $ds_required)) {
            $options['docusign'] = 'Signature via DocuSign';
            $value = 'docusign';
        }

        if (!$ds_required) {
            if ((int) $this->isSignDistAllowed($doc_type)) {
                $options['elec'] = 'Signature électronique à distance (via l\'espace client en ligne)';
                $value = 'elec';
            }

            $options['papier'] = 'Sigature papier';
            if (!$value) {
                $value = 'papier';
            }
        }

        return BimpInput::renderInput('select', 'signature_type', $value, array('options' => $options));
    }

    public function renderSignatureInitDocuSignInput($doc_type = '')
    {
        $html = '';

        $errors = array();
        if (!$this->isDocuSignAllowed($doc_type, $errors)) {
            $html .= '<div class="danger">';
            $html .= BimpTools::getMsgFromArray($errors, 'Il n\'est pas possible d\'utiliser DocuSign pour la signature du document "' . $this->getDocTypeLabel($doc_type) . '"');
            $html .= '</div>';
            $html .= '<input type="hidden" value="0" name="init_docusign"/>';
        } else {
            $html .= BimpInput::renderInput('toggle', 'init_docusign', 1);
        }

        return $html;
    }

    public function renderSignatureOpenDistAccessInput($doc_type = '')
    {
        $html = '';

        $errors = array();
        if (!$this->isSignDistAllowed($doc_type, $errors)) {
            $html .= '<div class="danger">';
            $html .= BimpTools::getMsgFromArray($errors, 'Il n\'est pas possible d\'utiliser la signature électronique à distance pour le document  "' . $this->getDocTypeLabel($doc_type) . '"');
            $html .= '</div>';
            $html .= '<input type="hidden" value="0" name="open_public_access"/>';
        } else {
            $html .= BimpInput::renderInput('toggle', 'open_public_access', 1);
        }

        return $html;
    }

    public function createSignature($doc_type, $data, &$warnings = array(), &$success = '')
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            $id_contact = BimpTools::getArrayValueFromPath($data, 'id_contact_signature', (int) $this->getData('id_contact'));
            $signature_type = BimpTools::getArrayValueFromPath($data, 'signature_type', '', $errors, true, 'Aucun type de signature sélectionné');
            $email_content = BimpTools::getArrayValueFromPath($data, 'email_content', $this->getSignatureEmailContent($signature_type));

            $field_name = 'id_signature_' . $doc_type;

            if (!$field_name) {
                $errors[] = 'Type de dpcument invalide "' . $doc_type . '"';
            } elseif (!$this->field_exists($field_name)) {
                $errors[] = 'Signature non disponible pour ce type de document';
            } else if ((int) $this->getData('id_signature_' . $doc_type)) {
                $errors[] = 'La fiche signature du document "' . $this->getDocTypeLabel($doc_type) . '" a déjà été créée';
            }

            $ds_required = false;
            $ds_errors = array();
            $allow_docusign = $this->isDocuSignAllowed($doc_type, $ds_errors, $ds_required);
            $allow_dist = $this->isSignDistAllowed($doc_type);

            switch ($signature_type) {
                case 'docusign':
                    if (!$allow_docusign) {
                        $errors[] = 'Signature via DocuSign non autorisée pour les contrats de location';
                    }
                    break;

                case 'elec':
                    if (!$allow_dist) {
                        $errors[] = 'Signature électronique à distance non autoriée pour les contrats de location';
                    }
                    break;
            }

            if (!count($errors)) {
                $id_client = (int) $this->getData('id_client');
                if (!$id_client) {
                    $errors[] = 'Client absent';
                } else {
                    $signature = BimpObject::createBimpObject('bimpcore', 'BimpSignature', array(
                                'obj_module'           => 'bimpfinancement',
                                'obj_name'             => 'BF_Demande',
                                'id_obj'               => $this->id,
                                'doc_type'             => $doc_type,
                                'obj_params_field'     => 'signature_' . $doc_type . '_params',
                                'allow_multiple_files' => ($doc_type === 'devis' ? 1 : 0)
                                    ), true, $errors, $warnings);

                    if (!count($errors) && BimpObject::objectLoaded($signature)) {
                        $errors = $this->updateField($field_name, (int) $signature->id);

                        $signataire_errors = array();
                        $signataire_label = (in_array($doc_type, array('contrat', 'pvr')) ? 'Locataire' : 'Signataire');
                        $signataire_locataire = BimpObject::createBimpObject('bimpcore', 'BimpSignataire', array(
                                    'id_signature'   => $signature->id,
                                    'code'           => (in_array($doc_type, array('contrat', 'pvr')) ? 'locataire' : 'default'),
                                    'label'          => $signataire_label,
                                    'id_client'      => $id_client,
                                    'id_contact'     => $id_contact,
                                    'allow_elec'     => (!$ds_required ? 1 : 0),
                                    'allow_dist'     => $allow_dist,
                                    'allow_docusign' => $allow_docusign,
                                    'allow_refuse'   => (int) BimpCore::getConf($doc_type . '_loc_signature_allow_refuse', null, 'bimpfinancement')
                                        ), true, $signataire_errors, $warnings);

                        if (!BimpObject::objectLoaded($signataire_locataire)) {
                            $errors[] = BimpTools::getMsgFromArray($signataire_errors, 'Echec de l\'ajout du signataire "' . $signataire_label . '" à la fiche signature');
                        }

                        if (in_array($doc_type, array('contrat', 'pvr'))) {
                            $signataire_data = $this->getData($doc_type . '_signataires_data');
                            $signataire_errors = array();
                            $signataire_loueur = BimpObject::createBimpObject('bimpcore', 'BimpSignataire', array(
                                        'id_signature'   => $signature->id,
                                        'type'           => BimpSignataire::TYPE_CUSTOM,
                                        'code'           => 'loueur',
                                        'label'          => 'Loueur',
                                        'nom'            => BimpTools::getArrayValueFromPath($signataire_data, 'loueur/nom', ''),
                                        'email'          => BimpTools::getArrayValueFromPath($signataire_data, 'loueur/email', ''),
                                        'fonction'       => BimpTools::getArrayValueFromPath($signataire_data, 'loueur/fonction', ''),
                                        'allow_elec'     => (!$ds_required ? 1 : 0),
                                        'allow_dist'     => $allow_dist,
                                        'allow_docusign' => $allow_docusign,
                                        'allow_refuse'   => (int) BimpCore::getConf($doc_type . '_loc_signature_allow_refuse', null, 'bimpfinancement')
                                            ), true, $signataire_errors, $warnings);

                            if (!BimpObject::objectLoaded($signataire_loueur)) {
                                $errors[] = BimpTools::getMsgFromArray($signataire_errors, 'Echec de l\'ajout du signataire "Loueur" à la fiche signature');
                            }
                        }

                        if ($doc_type === 'contrat') {
                            $signataire_errors = array();
                            $signataire_cessionnaire = BimpObject::createBimpObject('bimpcore', 'BimpSignataire', array(
                                        'id_signature'   => $signature->id,
                                        'type'           => BimpSignataire::TYPE_CUSTOM,
                                        'code'           => 'cessionnaire',
                                        'label'          => 'Cessionnaire',
                                        'nom'            => BimpTools::getArrayValueFromPath($signataire_data, 'cessionnaire/nom', ''),
                                        'email'          => BimpTools::getArrayValueFromPath($signataire_data, 'cessionnaire/email', ''),
                                        'fonction'       => BimpTools::getArrayValueFromPath($signataire_data, 'cessionnaire/fonction', ''),
                                        'allow_elec'     => (!$ds_required ? 1 : 0),
                                        'allow_dist'     => $allow_dist,
                                        'allow_docusign' => $allow_docusign,
                                        'allow_refuse'   => (int) BimpCore::getConf($doc_type . '_loc_signature_allow_refuse', null, 'bimpfinancement')
                                            ), true, $signataire_errors, $warnings);

                            if (!BimpObject::objectLoaded($signataire_cessionnaire)) {
                                $errors[] = BimpTools::getMsgFromArray($signataire_errors, 'Echec de l\'ajout du signataire "Cessionnaire" à la fiche signature');
                            }
                        }

                        if (!count($errors)) {
                            $this->addObjectLog('Fiche signature du document "' . $this->getDocTypeLabel($doc_type) . '" créée', 'SIGNATURE_' . strtoupper($doc_type) . '_CREEE');
                            $success = 'Création de la fiche signature effectuée avec succès';

                            switch ($signature_type) {
                                case 'docusign':
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
                                    break;

                                case 'elec':
                                    $open_errors = $signature->openAllSignDistAccess($email_content, $warnings, $success);
                                    if (count($open_errors)) {
                                        $errors[] = BimpTools::getMsgFromArray($open_errors, 'Echec de l\'ouverture de l\'accès à la signature à distance');
                                    }
                                    break;

                                case 'papier':
                                    $email_errors = $signataire_locataire->sendEmail($email_content);
                                    if (count($email_errors)) {
                                        $errors[] = BimpTools::getMsgFromArray($email_errors, 'Echec de l\'envoi de l\'e-mail au locataire');
                                    }
                                    break;
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

    public function getSignatureEmailContent($signature_type = '', $doc_type = '')
    {
        if (!$signature_type) {
            if (BimpTools::isPostFieldSubmit('signature_type')) {
                $signature_type = BimpTools::getPostFieldValue('signature_type', '');
            } else {
                if (BimpTools::isPostFieldSubmit('init_docusign')) {
                    if ((int) BimpTools::getPostFieldValue('init_docusign')) {
                        $signature_type = 'docusign';
                    }
                }
                if (!$signature_type) {
                    if (BimpTools::isPostFieldSubmit('open_public_access')) {
                        if ((int) BimpTools::getPostFieldValue('open_public_access')) {
                            $signature_type = 'elec';
                        }
                    }
                }
            }
        }

        if ($signature_type) {
            $message = 'Bonjour, <br/><br/>';

            $nb_files = 1;

            if ($doc_type === 'devis') {
                $files = $this->getDevisFilesArray(false);
                $nb_files = count($files);
            }

            if ($nb_files > 1) {
                $message .= 'Vous trouverez ci-joint ' . $nb_files . ' propositions de location pour votre matériel informatique.<br/><br/>';

                switch ($signature_type) {
                    case 'elec':
                        $message .= 'Vous pouvez effectuer la signature électronique du document correspondant à la proposition que aurez choisie depuis votre {LIEN_ESPACE_CLIENT} ou nous retourner ce document signé';
                        $message .= ' par courrier ou par e-mail';
                        $message .= '.<br/><br/>';
                        break;

                    case 'papier':
                    default:
                        $message .= 'Merci d\'imprimer le document correspondant à la proposition que vous aurez choise et de nous le retourner signé';
                        $message .= ' par courrier ou par e-mail';
                        $message .= '.<br/><br/>';
                        break;
                }
            } else {
                $message .= 'Le document "{NOM_DOCUMENT}" est en attente de validation.<br/><br/>';

                switch ($signature_type) {
                    case 'docusign':
                        $message .= 'Merci de bien vouloir le signer électroniquement en suivant les instructions DocuSign.<br/><br/>';
                        break;

                    case 'elec':
                    default:
                        $message .= 'Vous pouvez effectuer la signature électronique de ce document directement depuis votre {LIEN_ESPACE_CLIENT} ou nous retourner le document ci-joint signé';
                        if ($doc_type == 'contrat') {
                            $message .= ' <b>(par courrier uniquement)</b>';
                        } else {
                            $message .= ' par courrier ou par e-mail';
                        }
                        $message .= '.<br/><br/>';
                        break;

                    case 'papier':
                        $message .= 'Merci d\'imprimer ce document et de nous le retourner signé';
                        if ($doc_type == 'contrat') {
                            $message .= ' <b>par courrier uniquement</b>';
                        } else {
                            $message .= ' par courrier ou par e-mail';
                        }
                        $message .= '.<br/><br/>';
                        break;
                }
            }

            $message .= 'Vous en remerciant par avance, nous restons à votre disposition pour tout complément d\'information.<br/><br/>';
            $message .= 'Cordialement';

            $signature = BimpCore::getConf('signature_emails_client');
            if ($signature) {
                $message .= ', <br/><br/>' . $signature;
            }

            return $message;
        }

        return '';
    }

    public function getSignatureDocRef($doc_type)
    {
        switch ($doc_type) {
            case 'devis':
                return $this->getRef();

            case 'contrat':
                return str_replace('DF', 'CTF', $this->getRef());

            case 'pvr':
                return 'PVR_' . $this->getRef();
        }
        return '';
    }

    public function getSignatureDocFileName($doc_type, $signed = false, $file_idx = 0)
    {
        $ext = $this->getSignatureDocFileExt($doc_type, $signed);

        if ($this->isLoaded()) {
            return $this->getSignatureDocRef($doc_type) . ($signed ? '_signe' : ($file_idx ? '-' . $file_idx : '')) . '.' . $ext;
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

        $fileName = $this->getSignatureDocFileName($doc_type, $signed, $file_idx);

        if ($fileName) {
            if ($context === 'public') {
                return self::getPublicBaseUrl() . 'fc=doc&doc=' . $doc_type . '_financement' . ($signed ? '_signed' : '') . '&docid=' . $this->id . '&docref=' . $this->getRef() . ($file_idx ? '&file_idx=' . $file_idx : '');
            } else {
                return $this->getFileUrl($fileName);
            }
        }

        return '';
    }

    public function getSignatureParams($doc_type)
    {
        if (in_array($doc_type, array('devis', 'contrat', 'pvr'))) {
            return $this->getData('signature_' . $doc_type . '_params');
        }

        return array();
    }

    public function getOnSignedNotificationEmail($doc_type, &$use_as_from = false)
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

    public function onSigned($bimpSignature)
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
                $this->addObjectLog('Document "' . $this->getDocTypeLabel($doc_type) . '" signé', strtoupper($doc_type) . '_SIGNE');
                $this->checkIsClosed();
            }
        } else {
            $errors[] = 'Objet signature invalide';
        }

        return $errors;
    }

    public function isSignatureCancellable()
    {
        return 0;
    }

    public function isSignatureReopenable($doc_type, &$errors = array())
    {
        return 0;
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
                                $pu_ht = (float) BimpTools::getArrayValueFromPath($line, 'pu_ht', 0);
                                $remise = (float) BimpTools::getArrayValueFromPath($line, 'remise', 0);
                                $pa_ht = $pu_ht;

                                if ($remise) {
                                    $pa_ht -= ($pu_ht * ($remise / 100));
                                }

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
                                    'pu_ht'           => $pu_ht,
                                    'tva_tx'          => (float) BimpTools::getArrayValueFromPath($line, 'tva_tx', 0),
                                    'remise'          => $remise,
                                    'pa_ht'           => $pa_ht,
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
            $demande->addNotificationNote($msg, BimpNote::BN_AUTHOR_USER, '', 0, BimpNote::BN_MEMBERS, 1);
        } else {
            BimpCache::getBdb()->db->rollback();
        }

        return $demande;
    }

    public static function createPropositionPDF($data, &$errors = array())
    {
        $file_name = 'Proposition_Location_' . date('dmY_His') . '.pdf';
        $dir = DOL_DATA_ROOT . '/bimpfinancement/propositions';
        $now = date('Y-m-d');

        if (!is_dir($dir . '/' . $now)) {
            $error = BimpTools::makeDirectories($dir . '/' . $now);
            if ($error) {
                $errors[] = BimpTools::getMsgFromArray($error, 'Echec de la création du dossier de destination du document');
            }
        }

        if (is_dir($dir)) {
            // Epuration des anciens fichiers: 
            foreach (scandir($dir) as $f) {
                if (in_array($f, array('.', '..'))) {
                    continue;
                }

                if (is_dir($dir . '/' . $f) && preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $f) && $f < $now) {
                    foreach (scandir($dir . '/' . $f) as $f2) {
                        if (in_array($f2, array('.', '..'))) {
                            continue;
                        }

                        if (is_file($dir . '/' . $f . '/' . $f2)) {
                            unlink($dir . '/' . $f . '/' . $f2);
                        }
                    }
                    unlink($dir . '/' . $f);
                }
            }
        }

        if (!count($errors)) {
            require_once DOL_DOCUMENT_ROOT . '/bimpfinancement/pdf/PropositionLocationPDF.php';
            $pdf = new PropositionLocationPDF($data);
            $pdf->render($dir . '/' . $now . '/' . $file_name, 'F', false);

            if (count($pdf->errors)) {
                $errors = $pdf->errors;
            }
        }

        return $file_name;
    }
}
