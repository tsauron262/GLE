<?php

class BimpRelanceClientsLine extends BimpObject
{

    const RELANCE_ATTENTE_MAIL = 0;
    const RELANCE_ATTENTE_COURRIER = 1;
    const RELANCE_OK_MAIL = 10;
    const RELANCE_OK_COURRIER = 11;
    const RELANCE_CONTENTIEUX = 12;
    const RELANCE_ABANDON = 20;
    const RELANCE_ANNULEE = 21;

    public static $status_list = array(
        self::RELANCE_ATTENTE_MAIL     => array('label' => 'En attente d\'envoi par e-mail', 'icon' => 'fas_hourglass-start', 'classes' => array('warning')),
        self::RELANCE_ATTENTE_COURRIER => array('label' => 'En attente d\'envoi par courrier', 'icon' => 'fas_hourglass-start', 'classes' => array('warning')),
        self::RELANCE_OK_MAIL          => array('label' => 'Envoyée par e-mail', 'icon' => 'fas_check', 'classes' => array('success')),
        self::RELANCE_OK_COURRIER      => array('label' => 'Envoyée par courrier', 'icon' => 'fas_check', 'classes' => array('success')),
        self::RELANCE_CONTENTIEUX      => array('label' => 'Dépôt contentieux', 'icon' => 'fas_check', 'classes' => array('success')),
        self::RELANCE_ABANDON          => array('label' => 'Abandonnée', 'icon' => 'fas_times', 'classes' => array('danger')),
        self::RELANCE_ANNULEE          => array('label' => 'Annulée', 'icon' => 'fas_times', 'classes' => array('danger')),
    );

    // Droits user:

    public function canSetAction($action)
    {
        global $user;

        switch ($action) {
            case 'sendEmail':
            case 'generatePdf':
            case 'reopen':
            case 'cancelEmail':
                if ($user->admin || (int) $user->id === 1237 ||
                        $user->rights->bimpcommercial->admin_relance_global ||
                        $user->rights->bimpcommercial->admin_relance_individuelle) {
                    return 1;
                }
                return 0;

            case 'cancelContentieux':
                if ($user->admin || $user->rights->bimpcommercial->admin_recouvrement) {
                    return 1;
                }
                return 0;
        }
        return parent::canSetAction($action);
    }

    public function canSetStatus($status)
    {
        global $user;
        if ($user->admin) {
            return 1;
        }

        if ($this->getInitData('status') === self::RELANCE_CONTENTIEUX) {
            if ($user->rights->bimpcommercial->admin_recouvrement) {
                return 1;
            }
        } elseif ((int) $user->id === 1237 ||
                $user->rights->bimpcommercial->admin_relance_global ||
                $user->rights->bimpcommercial->admin_relance_individuelle ||
                $user->rights->bimpcommercial->admin_recouvrement) {
            return 1;
        }
        return 0;
    }

    // Getters booléens: 

    public function isActionAllowed($action, &$errors = array())
    {
        if (in_array($action, array('generatePdf'))) {
            if (!$this->isLoaded($errors)) {
                return 0;
            }
        }

        $err_label = $this->getRelanceLineLabel();
        switch ($action) {
            case 'generatePdf':
                if ((int) $this->getData('status') === self::RELANCE_CONTENTIEUX) {
                    $errors[] = $err_label . ': il s\'agit d\'un dépôt contentieux';
                    return 0;
                }

                if ((int) $this->getData('status') >= 20) {
                    $errors[] = $err_label . ': relance ' . lcfirst(self::$status_list[(int) $this->getData('status')]['label']);
                    return 0;
                }
                return 1;

            case 'reopen':
                if ((int) $this->getData('status') < 10) {
                    $errors[] = $err_label . ': cette relance est déjà en attente d\\\'envoi';
                    return 0;
                }
                if ((int) $this->getData('status') === self::RELANCE_OK_MAIL) {
                    $errors[] = $err_label . ': cette relance ne peut pas être remise en attente d\'envoi par e-mail (e-mail déjà envoyé)';
                    return 0;
                }
                if ((int) $this->getData('relance_idx') === 5) {
                    $errors[] = $err_label . ': il s\'agit d\'un dépôt contentieux';
                    return 0;
                }
                return 1;

            case 'cancelEmail':
                if ((int) $this->getData('status') !== self::RELANCE_OK_MAIL) {
                    $errors[] = $err_label . ': cette relance n\'a pas le statut "e-mail envoyé"';
                    return 0;
                }
                return 1;

            case 'cancelContentieux':
                if ((int) $this->getData('status') !== self::RELANCE_CONTENTIEUX) {
                    $errors[] = $err_label . ': cette relance n\'a pas le statut "Dépôt contentieux"';
                    return 0;
                }
                return 1;
        }

        return parent::isActionAllowed($action, $errors);
    }

    public function isNewStatusAllowed($new_status, &$errors = array())
    {
        if (!$this->isActionAllowed($errors)) {
            return 0;
        }

        $current_status = (int) $this->getData('status');
        $relance_idx = (int) $this->getData('relance_idx');
        $err_label = $this->getRelanceLineLabel();

        switch ($new_status) {
            case self::RELANCE_ATTENTE_MAIL:
                if ($relance_idx > 3) {
                    $errors[] = $err_label . ': cette relance ne peut pas être envoyée par e-mail';
                    return 0;
                }
                return 1;

            case self::RELANCE_ATTENTE_COURRIER:
                if ($relance_idx > 4) {
                    $errors[] = $err_label . ': cette relance ne peut pas être envoyée par courrier';
                    return 0;
                }
                return 1;

            case self::RELANCE_OK_MAIL:
                if ($current_status !== self::RELANCE_ATTENTE_MAIL) {
                    $errors[] = $err_label . ': cette relance n\'est pas en attente d\'envoi par e-mail';
                    return 0;
                }
                return 1;

            case self::RELANCE_OK_COURRIER:
                if ($current_status !== self::RELANCE_ATTENTE_COURRIER) {
                    $errors[] = $err_label . ': cette relance n\'est pas en attente d\'envoi par courrier';
                    return 0;
                }
                return 1;

            case self::RELANCE_ABANDON:
            case self::RELANCE_ANNULEE:
                if ($current_status >= 10 && $current_status !== self::RELANCE_CONTENTIEUX) {
                    $errors[] = $err_label . ': cette relance n\'est pas en attente d\'envoi';
                    return 0;
                }
                return 1;
        }
        return parent::isNewStatusAllowed($new_status, $errors);
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        if ($this->isLoaded()) {
            $shipment = $this->getParentInstance();
            if (BimpObject::objectLoaded($shipment)) {
                if ($shipment->getData('mode') == 'free') {
                    return 1;
                }
            }
        }

        switch ($field) {
            case 'id_contact':
            case 'email':
                $client = $this->getChildObject('client');
                if (BimpObject::objectLoaded($client) && (int) $client->getData('id_contact_relances')) {
                    return 0;
                }

            case 'date_prevue':
            case 'factures':
                if ((int) $this->getData('status') >= 10) {
                    return 0;
                }
                break;
        }

        return parent::isFieldEditable($field, $force_edit);
    }

    public function isValidForRelance(&$errors = array())
    {
        $check = 1;

        if (!(int) $this->getData('id_client')) {
            $errors[] = 'Client absent';
            $check = 0;
        }

        return (int) $check;
    }

    public function isFactureRelancable(Bimp_Facture $facture, &$errors = array())
    {
        if (!BimpObject::objectLoaded($facture)) {
            $errors[] = 'Cette facture n\'existe plus';
            return 0;
        }

        $relance = $this->getParentInstance();

        return (int) $facture->isRelancable(BimpObject::objectLoaded($relance ? $relance->getData('mode') : 'global'), $errors);
    }

    public function isRelanceEmail()
    {
        if ((int) $this->getData('relance_idx') <= 3 || (int) $this->getData('status') === self::RELANCE_ATTENTE_MAIL) {
            return 1;
        }

        return 0;
    }

    public function isRelanceCourrier()
    {
        if (((int) $this->getData('relance_idx') > 3 && (int) $this->getData('relance_idx') < 5) || (int) $this->getData('status') === self::RELANCE_ATTENTE_COURRIER) {
            return 1;
        }

        return 0;
    }

    // Getters params:

    public function getPdfFileName()
    {
        if ($this->isLoaded()) {
            $client = $this->getChildObject('client');
            $name = 'Relance_' . (int) $this->getData('relance_idx') . '_' . $this->id;
            if (BimpObject::objectLoaded($client)) {
                $name .= '_' . BimpTools::cleanStringForUrl($client->getName());
            }
            $name .= '.pdf';
            return $name;
        }

        return '';
    }

    public function getPdfFilepath()
    {
        $fileName = $this->getPdfFileName();

        if ($fileName) {
            $client = $this->getChildObject('client');
            if (BimpObject::objectLoaded($client)) {
                return $client->getFilesDir() . '/' . $fileName;
            }
        }

        return '';
    }

    public function getPdfFileUrl()
    {
        $file = $this->getPdfFilepath();
        if ($file && file_exists($file)) {
            $client = $this->getChildObject('client');
            if (BimpObject::objectLoaded($client)) {
                return $client->getFileUrl($this->getPdfFileName());
            }
        }

        return '';
    }

    public function getListExtraButtons()
    {
        $buttons = array();

        if ($this->isLoaded()) {
            $status = (int) $this->getData('status');
            $relance_idx = (int) $this->getData('relance_idx');

            if ($status < 10) {
                if ($status === self::RELANCE_ATTENTE_MAIL) {

                    if ($this->isActionAllowed('sendEmail') && $this->canSetAction('sendEmail')) {
                        $buttons[] = array(
                            'label'   => 'Envoyer l\'e-mail de relance',
                            'icon'    => 'fas_share',
                            'onclick' => $this->getJsActionOnclick('sendEmail', array(), array(
                                'confirm_msg' => 'Veuillez confirmer l\\\'envoi de l\\\'e-mail de relance'
                            ))
                        );
                    }

                    if ($this->canSetStatus(self::RELANCE_ATTENTE_MAIL)) {
                        $buttons[] = array(
                            'label'   => 'E-mail envoyé',
                            'icon'    => 'fas_check',
                            'onclick' => $this->getJsNewStatusOnclick(self::RELANCE_OK_MAIL)
                        );
                    }

                    if ($this->canSetStatus(self::RELANCE_ATTENTE_COURRIER)) {
                        $buttons[] = array(
                            'label'   => 'Mettre en attente d\'envoi par courrier',
                            'icon'    => 'fas_envelope-open-text',
                            'onclick' => $this->getJsNewStatusOnclick(self::RELANCE_ATTENTE_COURRIER)
                        );
                    }
                } elseif ($status === self::RELANCE_ATTENTE_COURRIER) {
                    if ($this->canSetStatus(self::RELANCE_OK_COURRIER)) {
                        $buttons[] = array(
                            'label'   => 'Courrier envoyé',
                            'icon'    => 'fas_check',
                            'onclick' => $this->getJsNewStatusOnclick(self::RELANCE_OK_COURRIER)
                        );
                    }

                    if ($relance_idx <= 3) {
                        if ($this->canSetStatus(self::RELANCE_ATTENTE_MAIL)) {
                            $buttons[] = array(
                                'label'   => 'Mettre en attente d\'envoi par e-mail',
                                'icon'    => 'fas_at',
                                'onclick' => $this->getJsNewStatusOnclick(self::RELANCE_ATTENTE_MAIL)
                            );
                        }
                    }
                }

                if ($this->canSetStatus(self::RELANCE_ABANDON)) {
                    $buttons[] = array(
                        'label'   => 'Abandonner',
                        'icon'    => 'fas_times',
                        'onclick' => $this->getJsNewStatusOnclick(self::RELANCE_ABANDON)
                    );
                }

                if ($this->isActionAllowed('generatePdf') && $this->canSetAction('generatePdf')) {
                    $buttons[] = array(
                        'label'   => 'Fichier PDF',
                        'icon'    => 'fas_file-pdf',
                        'onclick' => $this->getJsActionOnclick('generatePdf'),
                    );
                }
            } else {
                if ($this->isActionAllowed('reopen') && $this->canSetAction('reopen')) {
                    $buttons[] = array(
                        'label'   => 'Remettre en attente d\'envoi',
                        'icon'    => 'fas_undo',
                        'onclick' => $this->getJsActionOnclick('reopen', array(), array(
                            'confirm_msg' => 'Veuillez confirmer la remise en attente d\\\'envoi de cette relance'
                        ))
                    );
                }

                if ($this->isActionAllowed('cancelEmail') && $this->canSetAction('cancelEmail')) {
                    $buttons[] = array(
                        'label'   => 'Annuler envoi de l\'e-mail',
                        'icon'    => 'fas_times',
                        'onclick' => $this->getJsActionOnclick('cancelEmail', array(), array(
                            'confirm_msg' => 'Veuillez confirmer l\\\'annulation de l\\\'envoi de l\\\'email'
                        ))
                    );
                }

                if ($this->isActionAllowed('cancelContentieux') && $this->canSetAction('cancelContentieux')) {
                    $buttons[] = array(
                        'label'   => 'Annuler la mise en dépôt contentieux',
                        'icon'    => 'fas_times',
                        'onclick' => $this->getJsActionOnclick('cancelContentieux', array(), array(
                            'form_name' => 'cancel_contentieux'
                        ))
                    );
                }

                if ((int) $this->getData('status') < 20) {
                    $url = $this->getPdfFileUrl();
                    if ($url) {
                        $buttons[] = array(
                            'label'   => 'Fichier PDF',
                            'icon'    => 'fas_file-pdf',
                            'onclick' => 'window.open(\'' . $url . '\');'
                        );
                    } else {
                        if ($this->isActionAllowed('generatePdf') && $this->canSetAction('generatePdf')) {
                            $buttons[] = array(
                                'label'   => 'Fichier PDF',
                                'icon'    => 'fas_file-pdf',
                                'onclick' => $this->getJsActionOnclick('generatePdf', array(
                                    'force' => 1
                                ))
                            );
                        }
                    }
                }
            }
        }

        return $buttons;
    }

    public function getListBulkActions()
    {
        $actions = array();

        if ($this->canSetAction('sendEmail')) {
            $actions[] = array(
                'label'   => 'Envoyer les e-mails en attente',
                'icon'    => 'fas_share',
                'onclick' => 'setSelectedObjectsAction($(this), \'list_id\', \'sendEmail\', {}, null, \'Veuillez confirmer l\\\'envoi par e-mail des relances sélectionnées\')'
            );
        }

        if ($this->canSetStatus(self::RELANCE_OK_MAIL)) {
            $actions[] = array(
                'label'   => 'Marquer comme envoyées par e-mail',
                'icon'    => 'fas_check',
                'onclick' => 'setSelectedObjectsNewStatus($(this), \'list_id\', ' . self::RELANCE_OK_MAIL . ', {}, \'Veuillez confirmer que les relances sélectionnées ont bien été envoyées par e-mail\')'
            );
        }

        if ($this->canSetStatus(self::RELANCE_OK_COURRIER)) {
            $actions[] = array(
                'label'   => 'Marquer comme envoyées par courrier',
                'icon'    => 'fas_check',
                'onclick' => 'setSelectedObjectsNewStatus($(this), \'list_id\', ' . self::RELANCE_OK_COURRIER . ', {}, \'Veuillez confirmer que les relances sélectionnées ont bien été envoyées par courrier\')'
            );
        }

        if ($this->canSetStatus(self::RELANCE_ABANDON)) {
            $actions[] = array(
                'label'   => 'Abandonner',
                'icon'    => 'fas_times',
                'onclick' => 'setSelectedObjectsNewStatus($(this), \'list_id\', ' . self::RELANCE_ABANDON . ', {}, \'Veuillez confirmer l\\\'abandon des relances sélectionnées\')'
            );
        }

        if ($this->canSetStatus(self::RELANCE_ATTENTE_MAIL)) {
            $actions[] = array(
                'label'   => 'Mettre en attente d\'envoi par e-mail',
                'icon'    => 'fas_at',
                'onclick' => 'setSelectedObjectsNewStatus($(this), \'list_id\', ' . self::RELANCE_ATTENTE_MAIL . ', {}, \'Veuillez confirmer la mise en attente d\\\'envoi par e-mail des relances sélectionnées\')'
            );
        }

        if ($this->canSetStatus(self::RELANCE_ATTENTE_COURRIER)) {
            $actions[] = array(
                'label'   => 'Mettre en attente d\'envoi par courrier',
                'icon'    => 'fas_envelope-open-text',
                'onclick' => 'setSelectedObjectsNewStatus($(this), \'list_id\', ' . self::RELANCE_ATTENTE_COURRIER . ', {}, \'Veuillez confirmer la mise en attente d\\\'envoi par courrier des relances sélectionnées\')'
            );
        }

        if ($this->canSetAction('reopen')) {
            $actions[] = array(
                'label'   => 'Remettre en attente d\'envoi',
                'icon'    => 'fas_undo',
                'onclick' => 'setSelectedObjectsAction($(this), \'list_id\', \'reopen\', {}, null, \'Veuillez confirmer la remise en attente d\\\'envoi des relances sélectionnées\')'
            );
        }

        if ($this->can('edit') && $this->canEditField('date_prevue')) {
            $actions[] = array(
                'label'   => 'Changer date d\'envoi prévue',
                'icon'    => 'far_calendar-alt',
                'onclick' => $this->getJsBulkActionOnclick('editDatePrevue', array(), array(
                    'form_name'     => 'edit_date_prevue',
                    'single_action' => true
                ))
            );
        }

        return $actions;
    }

    public function getContactInputHelp($field = '')
    {
        $client = $this->getChildObject('client');
        if (BimpObject::objectLoaded($client) && (int) $client->getData('id_contact_relances')) {
            return 'Non modifiable (contact pour les relances de paiement défini dans la fiche client)';
        }

        if ($field === 'id_contact' && $this->isFieldEditable('id_contact')) {
            return 'Attention: modifier ce contact affectera uniquement l\'adresse du destinataire dans le PDF mais ne modifiera pas l\'adresse e-mail de destination (pour cela, veuillez modifier le champ ci-dessous).';
        }

        return '';
    }

    public function getEditForm()
    {
        if ($this->isLoaded()) {
            $shipment = $this->getParentInstance();
            if (BimpObject::objectLoaded($shipment)) {
                if ($shipment->getData('mode') == 'free') {
                    return 'edit_free';
                }
            }
        }

        return 'edit';
    }

    // Getters données: 

    public function getRelanceLineLabel()
    {
        $client = $this->getData('client');
        $contact = $this->getChildObject('contact');

        $label = 'Relance n°' . (int) $this->getData('relance_idx');

        if (BimpObject::objectLoaded($client)) {
            $label .= ' - client: ' . $client->getRef() . ' ' . $client->getName();
        }

        if (BimpObject::objectLoaded($contact)) {
            $label .= ' - contact: ' . $contact->getName();
        }

        return $label;
    }

    public function getFreeRelanceActivateRelances()
    {
        $value = 1;

        foreach ($this->getData('factures') as $id_fac) {
            $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_fac);
            if (BimpObject::objectLoaded($fac)) {
                if (!(int) $fac->getData('relance_active')) {
                    $value = 0;
                }
            }
        }

        return $value;
    }

    // Getters Array: 

    public function getAvailableFacturesArray()
    {
        $facs = array();
        $factures = $this->getData('factures');

        if (is_array($factures)) {
            foreach ($factures as $id_fac) {
                $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_fac);
                if (BimpObject::objectLoaded($fac)) {
                    $facs[$id_fac] = $fac->getRef();
                }
            }
        }

        $client = $this->getChildObject('client');

        if (BimpObject::objectLoaded($client)) {
            $clients_factures = $client->getFacturesToRelanceByClients(true, null, array(), array((int) $this->getData('relance_idx')));

            foreach ($clients_factures as $id_client => $client_data) {
                if ((int) $id_client !== (int) $client->id) {
                    continue;
                }

                foreach ($client_data['relances'] as $relance_idx => $factures) {
                    if ((int) $relance_idx !== (int) $this->getData('relance_idx')) {
                        continue;
                    }

                    foreach ($factures as $id_fac => $fac_data) {
                        if (array_key_exists((int) $id_fac, $facs)) {
                            continue;
                        }

                        if ((int) $fac_data['id_cur_relance']) {
                            continue;
                        }

                        $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_fac);
                        if (BimpObject::objectLoaded($fac)) {
                            $id_contact = (int) $fac->getIdContactForRelance((int) $relance_idx);

                            if ($id_contact !== (int) $this->getData('id_contact')) {
                                continue;
                            }

                            $facs[(int) $id_fac] = $fac->getRef();
                        }
                    }
                }
            }
        }

        return $facs;
    }

    // Affichages: 

    public function displayFactures()
    {
        $html = '';

        $factures = $this->getData('factures');

        if (is_array($factures)) {
            foreach ($factures as $id_fac) {
                $html .= ($html ? '<br/>' : '');
                $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_fac);
                if (BimpObject::objectLoaded($fac)) {
                    $html .= $fac->getLink();
                } else {
                    $html .= '<span class="danger">La facture d\'ID ' . $id_fac . ' n\'existe plus</span>';
                }
            }
        }

        return $html;
    }

    // Rendus HTML: 

    public function renderBeforeClientListHtml($bc_List)
    {
        $html = '';

        if (isset($bc_List->initial_filters['id_client'])) {
            $id_client = (int) $bc_List->initial_filters['id_client'];

            if ($id_client) {
                $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $id_client);

                if (BimpObject::objectLoaded($client)) {
                    $html .= $client->renderUnpaidFactures();
                } else {
                    $html .= BimpRender::renderAlerts('Le client #' . $id_client . ' n\'existe pas', 'danger');
                }
            }
        }

        return $html;
    }

    // Traitements: 

    public function generatePdf(&$errors = array(), &$warnings = array(), &$facs_done = array(), $force = false)
    {
        if (!$this->isActionAllowed('generatePdf', $errors)) {
            return null;
        }

        $this->checkContact();

        $relance = $this->getParentInstance();
        $factures = $this->getData('factures');
        $client = $this->getChildObject('client');
        $contact = $this->getChildObject('contact');
        $relance_idx = (int) $this->getData('relance_idx');

        if (!BimpObject::objectLoaded($relance)) {
            $errors[] = 'Relance absente';
        }

        if (!BimpObject::objectLoaded($client)) {
            $errors[] = 'Client absent';
        } else {
            if (!$force) {
                $client->isRelanceAllowed($errors);
            }
        }

        if (!count($errors)) {
            $dir = $client->getFilesDir();

            // Création des données du PDF: 
            $pdf_data = array(
                'relance_idx'  => (int) $relance_idx,
                'factures'     => array(),
                'rows'         => array(),
                'solde'        => 0,
                'total_debit'  => 0,
                'total_credit' => 0
            );

            // Vérification et ajout des données de chaque facture: 
            foreach ($factures as $id_facture) {
                $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_facture);

                if (!BimpObject::objectLoaded($fac)) {
                    $warnings[] = 'La facture #' . $id_facture . ' n\'existe plus';
                } else {
                    $fac_errors = array();
                    if (!$force) {
                        if (!$this->isFactureRelancable($fac, $fac_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($fac_errors, 'La facture "' . $fac->getRef() . '" n\'a pas été incluse dans la relance');
                        }
                    }

                    if (!count($fac_errors)) {
                        $pdf_data['factures'][] = $fac;
                        $facs_done[] = (int) $id_facture;
                        $this->hydrateRelancePdfDataFactureRows($fac, $pdf_data);
                    }
                }
            }

            if (!empty($pdf_data['factures'])) {
                // Création du PDF: 
                require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/RelancePaiementPDF.php';
                $pdf = new RelancePaiementPDF($this->db->db);
                $pdf->client = $client;
                $pdf->contact = $contact;
                $pdf->data = $pdf_data;

                if (!count($pdf->errors)) {
                    // Génération du PDF: 
                    $pdf->render($dir . '/' . $this->getPdfFileName(), false);
                }

                if (count($pdf->errors)) {
                    $errors[] = BimpTools::getMsgFromArray($pdf->errors, 'Echec de la génération du PDF');
                } else {
                    return $pdf;
                }
            } else {
                $warnings[] = 'Il n\'y a aucune facture a relancer. Relance annulée';
                $this->updateField('status', self::RELANCE_ANNULEE);
            }
        }

        return null;
    }

    public function hydrateRelancePdfDataFactureRows($facture, &$pdf_data)
    {
//        $commandes_list = BimpTools::getDolObjectLinkedObjectsList($facture->dol_object, $this->db, array('commande'));
//        $comm_refs = '';
//        foreach ($commandes_list as $item) {
//            $comm_ref = $this->db->getValue('commande', 'ref', 'rowid = ' . (int) $item['id_object']);
//            if ($comm_ref) {
//                $comm_refs = ($comm_refs ? '<br/>' : '') . $comm_ref;
//            }
//
//            if (!preg_match('/^.*' . preg_quote($comm_ref) . '.*$/', $ref_cli)) {
//                $ref_cli .= ($ref_cli ? '<br/>' : '') . 'Commande ' . $comm_ref;
//            }
//        }

        $prop_list = BimpTools::getDolObjectLinkedObjectsList($facture->dol_object, $this->db, array('propal'));
        $fac_total = (float) $facture->getData('total_ttc');
        $ref_cli = (string) $facture->getData('ref_client');

        foreach ($prop_list as $item) {
            $prop_ref = $this->db->getValue('propal', 'ref', 'rowid = ' . (int) $item['id_object']);
            if (!preg_match('/^.*' . preg_quote($prop_ref) . '.*$/', $ref_cli)) {
                $ref_cli .= ($ref_cli ? '<br/>' : '') . 'Devis ' . $prop_ref;
            }
        }

        // Total facture: 
        $facture_label = $facture->getData('libelle');
        $pdf_data['rows'][] = array(
            'date'           => $facture->displayData('datef', 'default', false),
            'fac'            => $facture->getRef(),
            'fac_ref_client' => $ref_cli,
//            'comm'           => $comm_refs,
            'lib'            => BimpTools::ucfirst($facture->getLabel()) . ($facture_label ? ' "' . $facture_label . '"' : ''),
            'debit'          => ($fac_total > 0 ? BimpTools::displayMoneyValue($fac_total, '') . ' €' : ''),
            'credit'         => ($fac_total < 0 ? BimpTools::displayMoneyValue(abs($fac_total), '') . ' €' : ''),
            'echeance'       => $facture->displayData('date_lim_reglement', 'default', false),
            'retard'         => floor((strtotime(date('Y-m-d')) - strtotime($facture->getData('date_lim_reglement'))) / 86400)
        );

        if ($fac_total > 0) {
            $pdf_data['total_debit'] += $fac_total;
        } else {
            $pdf_data['total_credit'] += abs($fac_total);
        }

        // Ajout des avoirs utilisés:
        $rows = $this->db->getRows('societe_remise_except', '`fk_facture` = ' . (int) $facture->id, null, 'array');
        if (!is_null($rows) && count($rows)) {
            foreach ($rows as $r) {
                $amount = (float) $r['amount_ttc'];

                if (!$amount) {
                    continue;
                }

                $avoir_fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $r['fk_facture_source']);
                $label = '';
                if ($avoir_fac->isLoaded()) {
                    switch ((int) $avoir_fac->getData('type')) {
                        case Facture::TYPE_DEPOSIT:
                            $label = 'Acompte ' . $avoir_fac->getRef();
                            break;

                        case facture::TYPE_CREDIT_NOTE:
                            $label = 'Avoir ' . $avoir_fac->getRef();
                            break;

                        default:
                            $label = 'Trop perçu sur facture ' . $avoir_fac->getRef();
                            break;
                    }
                } else {
                    $label = ((string) $r['description'] ? $r['description'] : 'Remise');
                }

                $dt = new DateTime($r['datec']);
                $pdf_data['rows'][] = array(
                    'date'     => $dt->format('d / m / Y'),
                    'fac'      => $facture->getRef(),
                    'comm'     => '',
                    'lib'      => $label,
                    'debit'    => ($amount < 0 ? BimpTools::displayMoneyValue(abs($amount), '') . ' €' : ''),
                    'credit'   => ($amount > 0 ? BimpTools::displayMoneyValue($amount, '') . ' €' : ''),
                    'echeance' => ''
                );

                if ($amount < 0) {
                    $pdf_data['total_debit'] += abs($amount);
                } else {
                    $pdf_data['total_credit'] += $amount;
                }
            }
        }

        // Ajout des paiements de la facture: 
        $rows = $this->db->getRows('paiement_facture', '`fk_facture` = ' . (int) $facture->id, null, 'array');
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $amount = (float) $r['amount'];

                if (!$amount) {
                    continue;
                }

                $paiement = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Paiement', (int) $r['fk_paiement']);
                if (BimpObject::objectLoaded($paiement)) {
                    $pdf_data['rows'][] = array(
                        'date'     => $paiement->displayData('datep'),
                        'fac'      => $facture->getRef(),
                        'comm'     => '',
                        'lib'      => 'Paiement ' . $paiement->displayType(),
                        'debit'    => ($amount < 0 ? BimpTools::displayMoneyValue(abs($amount), '') . ' €' : ''),
                        'credit'   => ($amount > 0 ? BimpTools::displayMoneyValue($amount, '') . ' €' : ''),
                        'echeance' => ''
                    );
                    if ($amount < 0) {
                        $pdf_data['total_debit'] += abs($amount);
                    } else {
                        $pdf_data['total_credit'] += $amount;
                    }
                }
            }
        }
    }

    public function sendRelanceEmail(&$warnings = array(), $force_send = false)
    {
        $errors = array();

        if ((int) $this->getData('status') !== self::RELANCE_ATTENTE_MAIL) {
            $errors[] = 'Cette relance n\'est pas en attente d\'envoi par e-mail';
            return $errors;
        }

        $this->checkContact();

        $client = $this->getChildObject('client');
        $relance_idx = (int) $this->getData('relance_idx');
        $relance = $this->getParentInstance();

        if (!BimpObject::objectLoaded($client)) {
            $errors[] = 'Client absent';
        } else {
            $client_errors = array();
            $client->isRelanceAllowed($client_errors);
            if (count($client_errors)) {
                $errors[] = BimpTools::getMsgFromArray($client_errors, 'Le client ' . $client->getLink() . ' n\'est pas elligible à une relance de paiement');
            }
        }

        $email = $this->getData('email');
        if (!$email) {
            $errors[] = 'Adresse e-mail absente';
        } else {
            $email = BimpTools::cleanEmailsStr($email);
        }

        if (!$force_send) {
            if ($relance_idx > 3) {
                $errors[] = 'Cette relance ne peut pas être envoyée par mail (' . $this->getData('relance_idx') . 'ème relance)';
            } elseif ($this->getData('date_prevue') > date('Y-m-d')) {
                $errors[] = 'Cette relance ne peut pas être envoyée par mail (Date d\'envoi prévue ultérieure à aujourd\'hui)';
            }
        }

        if (!count($errors)) {
            $facs_done = array();
            $pdf = $this->generatePdf($errors, $warnings, $facs_done);

            if (!is_null($pdf) && !count($errors)) {
                $relance = $this->getParentInstance();
                if (!BimpObject::objectLoaded($relance)) {
                    $errors[] = 'ID de la relance absent';
                }

                if (!count($errors)) {
                    // Envoi du mail: 
                    $mail_body = $pdf->content_html;

                    if (!empty($facs_done)) {
                        $mail_body .= '<div style="font-size: 12px; font-weight: bold;">';
                        $url_base = 'https://erp.bimp.fr/pdf_fact.php?';
                        $mail_body .= '<br/><br/><br/>';

                        if (count($facs_done) > 1) {
                            $mail_body .= 'Vous pouvez utiliser les liens suivants pour télécharger les duplicata des factures concernées: ';
                        } else {
                            $mail_body .= 'Vous pouvez utiliser le lien suivant pour télécharger le duplicata de la facture concernée: ';
                        }

                        $mail_body .= '<br/>';

                        foreach ($facs_done as $id_facture) {
                            $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_facture);
                            if (BimpObject::objectLoaded($facture)) {
                                $fac_url = $url_base . 'r=' . urlencode($facture->getRef()) . '&i=' . $id_facture;
                                $mail_body .= '<br/><a href="' . $fac_url . '">' . $facture->getRef() . '</a>';
                            }
                        }
                        $mail_body .= '</div>';
                    }

                    $mail_body .= '<br/>' . $pdf->extra_html;

                    $mail_body = str_replace('font-size: 6px;', 'font-size: 8px;', $mail_body);
                    $mail_body = str_replace('font-size: 7px;', 'font-size: 9px;', $mail_body);
                    $mail_body = str_replace('font-size: 8px;', 'font-size: 10px;', $mail_body);
                    $mail_body = str_replace('font-size: 9px;', 'font-size: 11px;', $mail_body);
                    $mail_body = str_replace('font-size: 10px;', 'font-size: 12px;', $mail_body);

                    switch ($relance_idx) {
                        case 1:
                            $subject = 'LETTRE DE RAPPEL';
                            break;

                        case 2:
                            $subject = 'DEUXIEME RAPPEL';
                            break;

                        case 3:
                            $subject = 'TROISIEME RAPPEL';
                            break;
                    }

                    $subject .= ' - Client: ' . $client->getRef() . ' ' . $client->getName();

                    $from = 'recouvrementolys@bimp.fr';
                    $replyTo = '';
                    $cc = '';

                    $commercial = $client->getCommercial(false);

                    if (BimpObject::objectLoaded($commercial)) {
                        $replyTo = $commercial->getData('email');

                        if (!BimpObject::objectLoaded($relance) || in_array($relance->getData('mode'), array('global', 'indiv')) || $relance_idx > 1) {
                            $cc = $replyTo;
                        }
                    }

                    if (!$replyTo) {
                        $replyTo = $from;
                    }

                    $filePath = $this->getPdfFilepath();
                    $fileName = $this->getPdfFileName();

                    if (!mailSyn2($subject, $email, $from, $mail_body, array($filePath), array('application/pdf'), array($fileName), $cc, '', 0, 1, '', '', $replyTo)) {
                        // Mail KO
                        $errors[] = 'Echec de l\'envoi de la relance par e-mail';
                    } else {
                        // Mail OK
                        $this->set('status', BimpRelanceClientsLine::RELANCE_OK_MAIL);
                        $this->set('date_send', date('Y-m-d H:i:s'));

                        global $user;
                        if (in_array((string) $relance->getData('mode'), array('global', 'indiv')) && BimpObject::objectLoaded($user)) {
                            $this->set('id_user_send', (int) $user->id);
                        }
                        $up_errors = $this->update($w, true);
                        if (count($up_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($up_errors, 'Echec de la mise à jour de la ligne de relance');
                        }

                        $now = date('Y-m-d');
                        foreach ($facs_done as $id_facture) {
                            $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_facture);
                            if (BimpObject::objectLoaded($facture)) {
                                $facture->updateField('nb_relance', $relance_idx, null, true);
                                $facture->updateField('date_relance', $now, null, true);
                            }
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function onNewStatus($new_status, $current_status, $extra_data = array(), &$warnings = array())
    {
        $errors = array();
        if ($this->isLoaded($errors)) {
            $factures = $this->getData('factures');
            $init_date_send = $this->getData('date_send');

            if ($new_status >= 10) {
                global $user;
                $date = '';

                if ($new_status === self::RELANCE_CONTENTIEUX) {
                    $date = (string) $this->getData('date_prevue') . ' ' . date('H:i:s');
                }

                if (!$date) {
                    $date = date('Y-m-d H:i:s');
                }

                $this->set('date_send', $date);
                if (BimpObject::objectLoaded($user)) {
                    $this->set('id_user_send', (int) $user->id);
                }

                if ($new_status < 20) {
                    $relance_idx = (int) $this->getData('relance_idx');
                    $now = date('Y-m-d');
                    foreach ($factures as $id_facture) {
                        $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_facture);
                        if (BimpObject::objectLoaded($facture)) {
                            if ($this->isFactureRelancable($facture)) {
                                $facture->updateField('nb_relance', $relance_idx, null, true);
                                $facture->updateField('date_relance', $now, null, true);
                            }
                        }
                    }
                }
            } else {
                $this->set('date_send', '0000-00-00 00:00:00');
                $this->set('id_user_send', 0);
            }

            $err = $this->update($warnings, true);

            if (!count($err)) {
                if (($new_status < 10 || $new_status >= 20) && $current_status >= 10 && $current_status < 20) {
                    $relance_idx = (int) $this->getData('relance_idx');

                    foreach ($factures as $id_facture) {
                        $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_facture);
                        if (BimpObject::objectLoaded($facture)) {
                            if ($init_date_send) {
                                $dt = new DateTime($init_date_send);

                                $delay = 5;
                                if ($relance_idx > 2) {
                                    $delay = BimpCore::getConf('relance_paiements_facture_delay_days', 15);
                                } elseif ($relance_idx > 1) {
                                    $delay = 10;
                                }

                                $dt->sub(new DateInterval('P' . $delay . 'D'));
                                $date = $dt->format('Y-m-d');
                                $facture->updateField('date_relance', $date, null, true);
                            }

                            $facture->updateField('nb_relance', ($relance_idx - 1), null, true);
                        }
                    }
                }
            }

            $client = $this->getChildObject('client');

            if (BimpObject::objectLoaded($client)) {
                $client->checkSolvabiliteStatus();
            }
        }

        return array();
    }

    public function checkContact()
    {
        if ((int) $this->getData('status') >= 10) {
            return;
        }

        $client = $this->getChildObject('client');

        if (BimpObject::objectLoaded($client)) {
            $contact = $client->getChildObject('contact_relances');

            if (BimpObject::objectLoaded($contact)) {
                if ((int) $this->getData('id_contact') !== (int) $contact->id) {
                    $this->updateField('id_contact', (int) $contact->id, null, true);
                }
                if ((string) $this->getData('email') !== (string) $contact->getData('email')) {
                    $this->updateField('email', (string) $contact->getData('email'), null, true);
                }
            }
        }
    }

    // Actions: 

    public function actionGeneratePdf($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $success_callback = '';

        $force = BimpTools::getArrayValueFromPath($data, 'force', false);

        $facs_done = array();
        $pdf = $this->generatePdf($errors, $warnings, $facs_done, $force);


        if (!is_null($pdf) && !count($errors)) {
            $url = $this->getPdfFileUrl();
            if ($url) {
                $success_callback = 'window.open(\'' . $url . '\');';
            } else {
                $errors[] = 'Echec de la génération du PDF';
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function actionSendEmail($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Email envoyé avec succès';

        $errors = $this->sendRelanceEmail($warnings);

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionReopen($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Remise en attente d\'envoi effectuée avec succès';

        if ($this->isLoaded($errors)) {
            if ((int) $this->getData('relance_idx') <= 3) {
                $errors = $this->setNewStatus(self::RELANCE_ATTENTE_MAIL);
            } else {
                $errors = $this->setNewStatus(self::RELANCE_ATTENTE_COURRIER);
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionCancelEmail($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Annulation de l\'envoi de l\'email effctuée avec succès';

        if ($this->isLoaded($errors)) {
            if ((int) $this->getData('relance_idx') <= 3) {
                $errors = $this->setNewStatus(self::RELANCE_ATTENTE_MAIL);
            } else {
                $errors = $this->setNewStatus(self::RELANCE_ATTENTE_COURRIER);
            }

            if (!count($errors)) {
                $this->checkContact();
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionEditDatePrevue($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $id_lines = BimpTools::getArrayValueFromPath($data, 'id_objects', array());
        $date_prevue = BimpTools::getArrayValueFromPath($data, 'date_prevue', '');

        if (!is_array($id_lines) || empty($id_lines)) {
            $errors[] = 'Aucune ligne de relance sélectionnée';
        }

        if (!$date_prevue) {
            $errors[] = 'Nouvelle date d\'envoi prévue non spécifiée';
        }

        if (!count($errors)) {
            $nOk = 0;

            foreach ($id_lines as $id_line) {
                $line = BimpCache::getBimpObjectInstance($this->module, $this->object_name, (int) $id_line);

                if (!BimpObject::objectLoaded($line)) {
                    $warnings[] = 'La ligne de relance d\'ID ' . $id_line . ' n\'existe plus';
                } else {
                    $line->set('date_prevue', $date_prevue);
                    $line_warnings = array();
                    $line_errors = $line->update($line_warnings);

                    if (count($line_errors)) {
                        $line_label = $this->getRelanceLineLabel();
                        $warnings[] = BimpTools::getMsgFromArray($line_errors, $line_label . ': échec de la mise à jour');
                    } else {
                        $nOk++;
                    }

                    if (count($line_warnings)) {
                        $line_label = $this->getRelanceLineLabel();
                        $warnings[] = BimpTools::getMsgFromArray($line_warnings, $line_label . ': erreurs lors de la mise à jour');
                    }
                }
            }

            if ($nOk === count($id_lines)) {
                $success = 'Toutes les lignes de relance ont été mises à jour avec succès';
            } elseif ($nOk > 0) {
                $success = $nOk . ' ligne(s) de relance mise(s) à jour avec succès';
            } else {
                $errors[] = 'Aucune ligne de relance n\'a été mise à jour';
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionCancelContentieux($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Annulation dépôt contentieux effectué avec succès';

        $deactivate_relances_factures = BimpTools::getArrayValueFromPath($data, 'deactivate_relances_factures', 0);
        $deactivate_relances_client = BimpTools::getArrayValueFromPath($data, 'deactivate_relances_client', 0);

        $errors = $this->setNewStatus(self::RELANCE_ANNULEE, array(), $warnings);

        if (!count($errors)) {
            if ($deactivate_relances_factures) {
                $factures = $this->getData('factures');

                $fac = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture');

                foreach ($factures as $id_fac) {
                    $fac_err = $fac->updateField('relance_active', 0, (int) $id_fac, true);

                    if (count($fac_err)) {
                        $warnings[] = BimpTools::getMsgFromArray($fac_err, 'Facture #' . $id_fac . ': échec de la désactivation des relances');
                    }
                }
            }

            if ($deactivate_relances_client) {
                $client = $this->getChildObject('client');

                if (BimpObject::objectLoaded($client)) {
                    $cli_err = $client->updateField('relances_actives', 0, null, true);

                    if (count($cli_err)) {
                        $warnings[] = BimpTools::getMsgFromArray($fac_err, 'Echec de la désactivation des relances pour le client');
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

    public function validate()
    {
        if (!(int) $this->getData('status')) {
            $relance_idx = (int) $this->getData('relance_idx');
            if ($relance_idx === 5) {
                $this->set('status', self::RELANCE_CONTENTIEUX);
            } elseif ($relance_idx <= 3) {
                $this->set('status', self::RELANCE_ATTENTE_MAIL);
            } else {
                $this->set('status', self::RELANCE_ATTENTE_COURRIER);
            }
        }

        return parent::validate();
    }

    public function onSave(&$errors = array(), &$warnings = array())
    {
        parent::onSave($errors, $warnings);

        $client = $this->getChildObject('client');

        if (BimpObject::objectLoaded($client)) {
            $client->checkSolvabiliteStatus();
        }
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            $relance = $this->getParentInstance();

            if (BimpObject::objectLoaded($relance) && $relance->getData('mode') != 'free') {
                if ((int) $this->getData('status') === self::RELANCE_CONTENTIEUX) {
                    $this->onNewStatus(self::RELANCE_CONTENTIEUX, 0, array(), $warnings);
                }
            }
        }

        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $init_relance_idx = (int) $this->getInitData('relance_idx');
        
        $errors = parent::update($warnings, $force_update);

        if (!count($errors)) {
            $shipment = $this->getParentInstance();
            if (BimpObject::objectLoaded($shipment)) {
                if ($shipment->getData('mode') == 'free') {
                    $date_send = $this->getData('date_send');
                    $activate_relances = null;
                    if (BimpTools::isPostFieldSubmit('activate_relances')) {
                        $activate_relances = (int) BimpTools::getPostFieldValue('activate_relances');
                    }

                    $relance_idx = (int) $this->getData('relance_idx');
                    $date_send = $this->getData('date_send');
                    if ($date_send) {
                        $date_send = date('Y-m-d', strtotime($date_send));
                    }

                    foreach ($this->getData('factures') as $id_fac) {
                        $fac_errors = array();
                        $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_fac);

                        if (BimpObject::objectLoaded($facture)) {
                            if (!is_null($activate_relances) && $activate_relances != (int) $facture->getData('relance_active')) {
                                $up_errors = $facture->updateField('relance_active', $activate_relances);
                                if (count($up_errors)) {
                                    $fac_errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec ' . ($activate_relances ? 'activation' : 'désactivation') . ' des relances');
                                }
                            }
                            if ($init_relance_idx == (int) $facture->getData('nb_relance')) {
                                $up_errors = $facture->updateField('nb_relance', $relance_idx);
                                if (count($up_errors)) {
                                    $fac_errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec mise à jour du nombre de relances');
                                }
                            }
                            if ($date_send && $relance_idx == (int) $facture->getData('nb_relance') &&
                                    $date_send != $facture->getData('date_relance')) {
                                $up_errors = $facture->updateField('date_relance', $date_send);
                                if (count($up_errors)) {
                                    $fac_errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec mise à jour de la date de dernière relance');
                                }
                            }
                        }

                        if (count($fac_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($fac_errors, 'Facture ' . $facture->getRef());
                        }
                    }
                }
            }
        }
        
        return $errors;
    }
}
