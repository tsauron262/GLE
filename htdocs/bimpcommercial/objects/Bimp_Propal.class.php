<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/BimpComm.class.php';

class Bimp_Propal extends BimpComm
{

    public static $dol_module = 'propal';
    public static $email_type = 'propal_send';
    public static $mail_event_code = 'PROPAL_SENTBYMAIL';
    public static $element_name = 'propal';
    public $id_sav = null;
    public $sav = null;
    public static $status_list = array(
        0 => array('label' => 'Brouillon', 'icon' => 'fas_file-alt', 'classes' => array('warning')),
        1 => array('label' => 'Validée', 'icon' => 'check', 'classes' => array('info')),
        2 => array('label' => 'Signée / Acceptée', 'icon' => 'check', 'classes' => array('success')),
        3 => array('label' => 'Refusée', 'icon' => 'exclamation-circle', 'classes' => array('danger')),
        4 => array('label' => 'Facturée', 'icon' => 'check', 'classes' => array('success')),
    );
    public $redirectMode = 4; //5;//1 btn dans les deux cas   2// btn old vers new   3//btn new vers old   //4 auto old vers new //5 auto new vers old
    public $acomptes_allowed = true;
    public static $default_signature_params = array(
        'x_pos'             => 146,
        'width'             => 43,
        'date_x_offset'     => -16,
        'date_y_offset'     => 7,
        'nom_x_offset'      => -32,
        'nom_y_offset'      => 0,
        'nom_width'         => 30,
        'fonction_x_offset' => -32,
        'fonction_y_offset' => 11,
        'fonction_width'    => 30
    );

    // Gestion des droits users

    public function canCreate()
    {
        global $user;
        if (isset($user->rights->propal->creer)) {
            return (int) $user->rights->propal->creer;
        }
        return 0;
    }

    public function canEdit()
    {
        return $this->canCreate();
    }

    public function canSetAction($action)
    {
        global $conf, $user;

        switch ($action) {
            case 'validate':
                if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->propal->creer)) ||
                        (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->propal->propal_advance->validate))) {
                    return 1;
                }
                return 0;

            case 'addContact':
                if (!empty($user->rights->propale->creer)) {
                    return 1;
                }
                return 0;

            case 'close':
            case 'reopen':
            case 'classifyBilled':
                if (!empty($user->rights->propal->cloturer)) {
                    return 1;
                }
                return 0;

            case 'sendMail':
                if (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->propal->propal_advance->send) {
                    return 1;
                }
                return 0;

            case 'modify':
                return (int) $user->admin;

            case 'review':
                return $this->can("edit");

            case 'createOrder':
                $commande = BimpObject::getInstance('bimpcommercial', 'Bimp_Commande');
                return $commande->can("create") /* && (int) $user->rights->bimpcommercial->edit_comm_fourn_ref */;

            case 'createContract':
                if ($user->rights->contrat->creer) {
                    return 1;
                }
                return 0;

            case 'createInvoice':
                $facture = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture');
                return $facture->can("create");

            case 'createContrat':
                if ($user->admin) {
                    return 1;
                }

                if ($conf->contrat->enabled && in_array($this->getData('fk_statut'), array(1, 2))) {
                    return 1;
                }

                if ($user->rights->bimpcontract->to_create_from_propal_all_status) {
                    return 1;
                }
                return 0;

            case 'setRemiseGlobale':
                return $this->can("edit");

            case 'createSignature':
                return 1;
        }
        return 1;
    }

    // Getters booléens:

    public function isActionAllowed($action, &$errors = array())
    {
        if ($this->erreurFatal)
            return 0;

        global $conf;
        $status = $this->getData('fk_statut');

        if (in_array($action, array('validate', 'modify', 'review', 'close', 'reopen', 'sendEmail', 'createOrder', 'createContract', 'createInvoice', 'classifyBilled', 'createContrat', 'createSignature'))) {
            if (!$this->isLoaded()) {
                $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent';
                return 0;
            }
            if (is_null($status)) {
                $errors[] = 'Statut absent';
                return 0;
            }
        }

        $status = (int) $status;
        $soc = $this->getChildObject('client');

        switch ($action) {
            case 'validate':
                if (!BimpObject::objectLoaded($soc)) {
                    $errors[] = 'Client absent';
                }
                if ($status !== Propal::STATUS_DRAFT) {
                    $errors[] = BimpTools::ucfirst($this->getLabel('the')) . ' n\'est pas au statut brouillon';
                }

                $lines = $this->getLines('not_text');
                if (!count($lines)) {
                    $errors[] = 'Aucune ligne enregistrée pour ' . $this->getLabel('this') . ' (Hors text)';
                }
                return (count($errors) ? 0 : 1);

            case 'modify':
                return 0;

            case 'close':
                if ($status !== Propal::STATUS_VALIDATED) {
                    $errors[] = 'Le statut actuel ' . $this->getLabel('of_this') . ' ne permetffff pas cette opération';
                    return 0;
                }

                if ($this->isSigned()) {
                    $errors[] = ucfirst($this->getLabel('this')) . ' est déjà signé' . $this->e();
                    return 0;
                }

                return 1;

            case 'review':
                if ($status === 0) {
                    $errors[] = ucfirst($this->getLabel('this')) . ' est encore au statut brouillon';
                }

                if ((int) $this->getIdSav()) {
                    $sav = $this->getSav();
                    if (BimpObject::objectLoaded($sav)) {
                        $errors[] = ucfirst($this->getLabel('this')) . ' est liée au SAV ' . $sav->getNomUrl(0, 1, 1, 'default') . '. Veuillez utiliser le bouton réviser depuis la fiche SAV';
                    }
                }
                if (!in_array($status, array(Propal::STATUS_VALIDATED, Propal::STATUS_SIGNED, Propal::STATUS_NOTSIGNED))) {
                    $errors[] = ucfirst($this->getLabel('the')) . ' n\'a pas le statut validé' . $this->e() . ' ou refusé' . $this->e();
                }

                $where = '`fk_source` = ' . $this->id . ' AND `sourcetype` = \'propal\'';
                $where .= ' AND `targettype` = \'commande\'';
                $id_commande = (int) $this->db->getValue('element_element', 'fk_target', $where);
                if ($id_commande) {
                    $errors[] = 'Une commande a été créée à partir de cette proposition commerciale';
                }

                return (count($errors) ? 0 : 1);

            case 'reopen':
//                if (!in_array($status, array(Propal::STATUS_SIGNED, Propal::STATUS_NOTSIGNED, Propal::STATUS_BILLED))) {
//                    $errors[] = 'Statut actuel ' . $this->getLabel('of_the') . ' invalide';
//                    return 0;
//                }
                return 0;

            case 'sendEmail':
                if (!in_array($status, array(Propal::STATUS_VALIDATED, Propal::STATUS_SIGNED))) {
                    $errors[] = 'Statut actuel ' . $this->getLabel('of_the') . ' invalide';
                    return 0;
                }
                return 1;

            case 'createOrder':
                if ($status !== Propal::STATUS_SIGNED) {
                    $errors[] = 'Statut actuel ' . $this->getLabel('of_the') . ' invalide';
                    return 0;
                }
                if (empty($conf->commande->enabled)) {
                    $errors[] = 'Création des commandes désactivée';
                    return 0;
                }
                return 1;

            case 'createInvoice':
                if ($status !== Propal::STATUS_SIGNED) {
                    $errors[] = 'Statut actuel ' . $this->getLabel('of_the') . ' invalide';
                    return 0;
                }
                if (empty($conf->facture->enabled)) {
                    $errors[] = 'Création des factures désactivée';
                    return 0;
                }
                return 1;

            case 'classifyBilled':
                $factures = $this->dol_object->getInvoiceArrayList();
                if ($status !== Propal::STATUS_SIGNED) {
                    $errors[] = 'Statut actuel ' . $this->getLabel('of_the') . ' invalide';
                    return 0;
                }
                $factures = $this->dol_object->getInvoiceArrayList();
                if (!empty($conf->global->WORKFLOW_PROPAL_NEED_INVOICE_TO_BE_CLASSIFIED_BILLED) && (!is_array($factures) || !count($factures))) {
                    $errors[] = 'Aucune facture créée pour ' . $this->getLabel('this');
                    return 0;
                }
                return 1;

            case 'createContrat':
                if ($status == 0) {
                    $errors[] = ucfirst($this->getLabel('this')) . ' est au statut brouillon';
                    return 0;
                }

                $linked_contrat = getElementElement('propal', 'contrat', $this->id);
                foreach ($linked_contrat as $ln) {
                    $ct = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat', $ln['d']);
                    if ($ct->getData('statut') != BContract_contrat::CONTRAT_STATUT_ABORT) {
                        $errors[] = 'Un contrat existe déjà pour cette proposition commerciale';
                        return 0;
                    }
                }
                return 1;

            case 'createSignature':
                if ($status != 1) {
                    $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' n\'est pas au statut "Validé' . $this->e() . '"';
                    return 0;
                }

                if ((int) $this->getData('id_signature')) {
                    $signature = BimpCache::getBimpObjectInstance('bimpcore', 'BimpSignature', (int) $this->getData('id_signature'));

                    if (!BimpObject::objectLoaded($signature)) {
                        $this->updateField('id_signature', 0);
                    } else {
                        $errors[] = 'La signature est déjà en place pour ' . $this->getLabel('this');
                        return 0;
                    }
                }
                return 1;
        }

        return (int) parent::isActionAllowed($action, $errors);
    }

    public function isNewStatusAllowed($new_status, &$errors = array())
    {
        switch ($new_status) {
            case 2: // Signé
                if ((int) $this->getData('fk_statut') !== 1) {
                    $errors[] = 'Statut actuel doit être "validé' . $this->e() . '"';
                    return 0;
                }

                if ((int) $this->getData('id_signature') && (int) BimpCore::getConf('propal_use_signatures', null, 'bimpcommercial')) {
                    $errors[] = 'Utiliser la fiche signature';
                    return 0;
                }
                return 1;
        }
        return parent::isNewStatusAllowed($new_status, $errors);
    }

    public function iAmAdminRedirect()
    {
        global $user;
        if (in_array($user->id, array(60, 282)))
            return true;
        return parent::iAmAdminRedirect();
    }

    public function isNotRenouvellementContrat()
    {
        $clef = $this->id . 'isNotRenouvellementContrat';
        if (!isset(BimpCache::$cache[$clef])) {
            BimpCache::$cache[$clef] = 1;
            if (!$this->isServiceAutorisedInContrat()) {
                BimpCache::$cache[$clef] = 0;
            }

            if (count(getElementElement("contrat", "propal", null, $this->id)) > 0) {
                BimpCache::$cache[$clef] = 0;
            }
        }
        return BimpCache::$cache[$clef];
    }

    public function isServiceAutorisedInContrat($return_array = false)
    {
        $clef = $this->id . 'isServiceAutorisedInContrat' . $return_array;
        if (!isset(BimpCache::$cache[$clef])) {
            if (!BimpCore::getConf('use_autorised_service', 1, 'bimpcontract'))
                BimpCache::$cache[$clef] = 1;
            else {
                $children = $this->getChildrenList('lines');
                $id_services = [];

                foreach ($children as $id_child) {
                    $child = $this->getChildObject("lines", $id_child);
                    $dol_line = $child->getChildObject('dol_line');
                    if ($dol_line->getData('fk_product') > 0) {
                        $service = $this->getInstance('bimpcore', 'Bimp_Product', $dol_line->getData('fk_product'));
                        if (!$service->isInContrat() && $dol_line->getData('total_ht') != 0) {
                            $id_services[] = $service->getData('ref');
                        }
                    }
                }

                if (count($id_services) > 0 && !$return_array) {
                    BimpCache::$cache[$clef] = 0;
                } else {
                    if ($return_array) {
                        BimpCache::$cache[$clef] = $id_services;
                    } else {
                        BimpCache::$cache[$clef] = 1;
                    }
                }
            }
        }
        return BimpCache::$cache[$clef];
    }

    public function isSigned()
    {
        if (in_array($this->getData('fk_statut'), array(2, 4))) {
            return 1;
        }

        if ((int) $this->getData('id_signature')) {
            $signature = $this->getChildObject('signature');

            if (BimpObject::objectLoaded($signature)) {
                if ((int) $signature->getData('signed')) {
                    return 1;
                }
            }
        }

        return 0;
    }

    // Getters: 

    public function getIdSav()
    {
        global $conf;
        if (isset($conf->global->MAIN_MODULE_BIMPSUPPORT) && $conf->global->MAIN_MODULE_BIMPSUPPORT && is_null($this->id_sav)) {
            if ($this->isLoaded()) {
                $this->id_sav = (int) $this->db->getValue('bs_sav', 'id', '`id_propal` = ' . (int) $this->id);
            } else {
                return 0;
            }
        }

        return $this->id_sav;
    }

    public function getSav()
    {
        if (is_null($this->sav)) {
            $id_sav = (int) $this->getIdSav();
            if ($id_sav) {
                $this->sav = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SAV', $id_sav);
            }
        }

        return $this->sav;
    }

    public function getDureeValidite()
    {
        if ($this->isLoaded()) {
            return 1;
        }

        return 15;
    }

    public function getHelpDateRenouvellementContrat()
    {
        $help = "";

        if (!$this->isNotRenouvellementContrat()) {
            $help = "Cette case correspond à la date d'effet du contrat, si cette case n'est pas renseignée, lors du formulaire de création du contrat vous pourrez le faire";
        }

        return $help;
    }

    public function displayIfMessageFormContrat()
    {
        $array = $this->isServiceAutorisedInContrat(true);
        $msgs = [];
        if (count($array) > 0 && BimpCore::getConf('use_autorised_service', 1, 'bimpcontract')) {

            $content = "<h4><b>Vous ne pouvez pas créer de contrat à partir de ce devis car certains services ne sont pas autorisés dans un contrat<br /><br />";
            if (count($array) > 1) {
                $content .= "<i><u>Liste des services en cause</u></i>";
            } else {
                $content .= "<i><u>Liste du service en cause</u></i><br />";
            }

            $content .= "<p>";

            foreach ($array as $ref_service) {
                $content .= "- " . $ref_service . "<br />";
            }

            $content .= "</p>";

            $msgs[] = Array(
                'type'    => 'warning',
                'content' => $content
            );
        }

        return $msgs;
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

    public function getDefaultSignDistEmailContent()
    {
        BimpObject::loadClass('bimpcore', 'BimpSignature');
        return BimpSignature::getDefaultSignDistEmailContent();
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

    // Getters - overrides BimpComm

    public function getModelPdf()
    {
        if ((int) $this->getIdSav()) {
            return 'bimpdevissav';
        }

        return $this->getData('model_pdf');
    }

    public function getModelsPdfArray()
    {
        if (!class_exists('ModelePDFPropales')) {
            require_once DOL_DOCUMENT_ROOT . '/core/modules/propale/modules_propale.php';
        }

        return ModelePDFPropales::liste_modeles($this->db->db);
    }

    public function getCloseStatusArray()
    {
        return array(
            2 => self::$status_list[2]['label'],
            3 => self::$status_list[3]['label']
        );
    }

    public function getFilteredListActions()
    {
        $actions = array();

        if ($this->canSetAction('bulkEditField')) {
            $actions[] = array(
                'label'  => 'Annuler',
                'icon'   => 'fas_times',
                'action' => 'cancel'
            );
        }
        if ($this->canSetAction('sendEmail')) {
            $actions[] = array(
                'label'  => 'Fichiers PDF',
                'icon'   => 'fas_file-pdf',
                'action' => 'generateBulkPdf'
            );
            $actions[] = array(
                'label'  => 'Fichiers Zip des PDF',
                'icon'   => 'fas_file-pdf',
                'action' => 'generateZipPdf'
            );
        }

        return $actions;
    }

    public function getListExtraBulkActions()
    {
        $actions = array();

        if ($this->canSetAction('sendEmail')) {
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

    public function getActionsButtons()
    {
        global $langs;
        $langs->load('propal');

        if ($this->isLoaded()) {
            $buttons[] = array(
                'label'   => 'Générer le PDF',
                'icon'    => 'fas_sync',
                'onclick' => $this->getJsActionOnclick('generatePdf', array(), array())
            );

            $status = $this->getData('fk_statut');
            $use_signature = (int) BimpCore::getConf('propal_use_signatures', null, 'bimpcommercial');

            if (!is_null($status)) {
                $status = (int) $status;
                // Valider:
                if ($status === 0) {
                    $errors = array();
                    if ($this->isActionAllowed('validate', $errors)) {
                        if ($this->canSetAction('validate')) {
                            $params = array();
                            if ($use_signature) {
                                $params['form_name'] = 'validate';
                            } else {
                                $params['confirm_msg'] = 'Veuillez confirmer la validation ' . $this->getLabel('of_this');
                            }

                            $buttons[] = array(
                                'label'   => 'Valider',
                                'icon'    => 'check',
                                'onclick' => $this->getJsActionOnclick('validate', array(), $params)
                            );
                        } else {
                            $errors[] = 'Vous n\'avez pas la permission de valider cette proposition commerciale';
                        }
                    }
                    if (count($errors)) {
                        $buttons[] = array(
                            'label'    => 'Valider',
                            'icon'     => 'check',
                            'onclick'  => '',
                            'disabled' => 1,
                            'popover'  => BimpTools::getMsgFromArray($errors)
                        );
                    }
                } else {
                    $no_signature = false;
                    $signed = in_array($status, array(2, 4));

                    if (!$signed) {
                        if ($use_signature) {
                            // Boutons signature: 
                            $signature = $this->getChildObject('signature');
                            if (BimpObject::objectLoaded($signature)) {
                                if (!(int) $signature->getData('signed')) {
                                    $buttons = BimpTools::merge_array($buttons, $signature->getSignButtons());
                                } else {
                                    $signed = true;
                                }
                            } elseif ($this->isActionAllowed('createSignature') && $this->canSetAction('createSignature')) {
                                $no_signature = true;
                                // Créer Signature: 
                                $buttons[] = array(
                                    'label'   => 'Créer la fiche signature',
                                    'icon'    => 'fas_signature',
                                    'onclick' => $this->getJsActionOnclick('createSignature', array(), array(
                                        'form_name' => 'create_signature'
                                    ))
                                );
                            }
                        }

                        // Refuser
                        if ($this->isActionAllowed('close')) {
                            if ($this->canSetAction('close')) {
                                $clientFact = $this->getClientFacture();
                                $buttons[] = array(
                                    'label'   => 'Devis Refusé',
                                    'icon'    => 'times',
                                    'onclick' => $this->getJsActionOnclick('close', array(
                                        'new_status' => 3
                                            ), array(
                                        'form_name' => 'close'
                                    ))
                                );
                            }
                        }

                        if ($no_signature || !$use_signature) {
                            // Accepter (sans signature)
                            if ($this->isNewStatusAllowed(2)) {
                                $buttons[] = array(
                                    'label'   => 'Devis accepté',
                                    'icon'    => 'fas_check',
                                    'onclick' => $this->getJsNewStatusOnclick(2, array(), array(
                                        'confirm_msg' => 'Veuillez confirmer'
                                    ))
                                );
                            }

                            // Modifier
                            if ($this->isActionAllowed('modify')) {
                                if ($this->canSetAction('modify')) {
                                    $buttons[] = array(
                                        'label'   => 'Modifier',
                                        'icon'    => 'fas_undo',
                                        'onclick' => $this->getJsActionOnclick('modify', array())
                                    );
                                }
                            }
                        }
                    }

                    if ($signed || $no_signature || !$use_signature) {
                        // Créer commande: 
                        $errors = array();
                        if ($this->isActionAllowed('createOrder', $errors) && $this->canSetAction('createOrder')) {
                            $commande = BimpObject::getInstance('bimpcommercial', 'Bimp_Commande');
                            $clientFact = $this->getClientFacture();

                            $values = array(
                                'fields' => array(
                                    'entrepot'           => (int) $this->getData('entrepot'),
                                    'ef_type'            => $this->getData('ef_type'),
                                    'fk_soc'             => (int) $this->getData('fk_soc'),
                                    'ref_client'         => $this->getData('ref_client'),
                                    'fk_cond_reglement'  => (int) $this->getData('fk_cond_reglement'),
                                    'fk_mode_reglement'  => (int) $this->getData('fk_mode_reglement'),
                                    'fk_availability'    => (int) $this->getData('fk_availability'),
                                    'fk_input_reason'    => (int) $this->getData('fk_input_reason'),
                                    'date_commande'      => date('Y-m-d'),
                                    'date_livraison'     => $this->getData('date_livraison'),
                                    'libelle'            => $this->getData('libelle'),
                                    'pdf_hide_pu'        => $this->getData('pdf_hide_pu'),
                                    'pdf_hide_reduc'     => $this->getData('pdf_hide_reduc'),
                                    'pdf_hide_total'     => $this->getData('pdf_hide_total'),
                                    'pdf_hide_ttc'       => $this->getData('pdf_hide_ttc'),
                                    'pdf_periodicity'    => $this->getData('pdf_periodicity'),
                                    'pdf_periods_number' => $this->getData('pdf_periods_number'),
                                    'expertise'          => $this->getData('expertise'),
                                    'note_public'        => addslashes(htmlentities($this->getData('note_public'))),
                                    'note_private'       => addslashes(htmlentities($this->getData('note_private'))),
                                    'origin'             => 'propal',
                                    'origin_id'          => (int) $this->id,
                                    'close_propal'       => 0
                                )
                            );

                            if (BimpObject::objectLoaded($clientFact) && ($clientFact->id != $this->getData('fk_soc'))) {
                                $values['fields']['id_client_facture'] = $clientFact->id;
                            }

                            $buttons[] = array(
                                'label'   => 'Créer une commande',
                                'icon'    => 'fas_dolly',
                                'onclick' => $commande->getJsLoadModalForm('default', 'Création d\\\'une commande', $values, '', 'redirect')
                            );
                        }

                        // Créer facture: 
                        if ($this->isActionAllowed('createInvoice') && $this->canSetAction('createInvoice')) {
                            $onclick = '';
                            if (!BimpCore::getConf('commande_required_for_factures', null, 'bimpcommercial')) {
                                $clientFact = $this->getClientFacture();
                                $facture = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture');
                                $values = array(
                                    'fields' => array(
                                        'entrepot'          => (int) $this->getData('entrepot'),
                                        'ef_type'           => $this->getData('ef_type'),
                                        'fk_soc'            => (BimpObject::objectLoaded($clientFact) ? (int) $clientFact->id : 0),
                                        'ref_client'        => $this->getData('ref_client'),
                                        'fk_cond_reglement' => (int) $this->getData('fk_cond_reglement'),
                                        'fk_mode_reglement' => (int) $this->getData('fk_mode_reglement'),
                                        'fk_availability'   => (int) $this->getData('fk_availability'),
                                        'fk_input_reason'   => (int) $this->getData('fk_input_reason'),
                                        'note_public'       => addslashes(htmlentities($this->getData('note_public'))),
                                        'note_private'      => addslashes(htmlentities($this->getData('note_private'))),
                                        'date_commande'     => date('Y-m-d'),
                                        'date_livraison'    => $this->getData('date_livraison'),
                                        'libelle'           => $this->getData('libelle'),
                                        'origin'            => 'propal',
                                        'origin_id'         => (int) $this->id,
                                    )
                                );
                                $onclick = $facture->getJsLoadModalForm('default', 'Création d\\\'une facture', $values, '', 'redirect');
                            } else {
                                $url = DOL_URL_ROOT . '/compta/facture/card.php?action=create&origin=propal&originid=' . $this->id . '&socid=' . (int) $clientFact->id;
                                $onclick = 'window.location = \'' . $url . '\'';
                            }

                            $buttons[] = array(
                                'label'   => 'Créer une facture ou un avoir',
                                'icon'    => 'fas_file-invoice-dollar',
                                'onclick' => $onclick
                            );
                        }
                    }

                    // Créer un contrat
                    if ($this->isActionAllowed('createContrat') && $this->canSetAction('createContrat')) {
                        $buttons[] = array(
                            'label'   => 'Créer un contrat',
                            'icon'    => 'fas_file-signature',
                            'onclick' => $this->getJsActionOnclick('createContrat', array(), array(
                                'form_name' => "contrat"
                            ))
                        );
                    }

                    // Réviser: 
                    if ($status > 0) {
                        $errors = array();
                        if ($this->isActionAllowed('review', $errors)) {
                            if ($this->canSetAction('review')) {
                                $buttons[] = array(
                                    'label'   => 'Réviser',
                                    'icon'    => 'fas_undo',
                                    'onclick' => $this->getJsActionOnclick('review', array(), array(
                                        'confirm_msg' => 'Veuillez confirmer la mise en révision de cette proposition commerciale'
                                    ))
                                );
                            } else {
                                $errors = 'Vous n\'avez pas la permission';
                            }
                        }
                        if (is_array($errors) && count($errors)) {
                            $buttons[] = array(
                                'label'    => 'Réviser',
                                'icon'     => 'fas_undo',
                                'onclick'  => '',
                                'disabled' => 1,
                                'popover'  => BimpTools::getMsgFromArray($errors)
                            );
                        }
                    }

                    // Réouvrir:
                    if ($this->isActionAllowed('reopen')) {
                        if ($this->canSetAction('reopen')) {
                            $text = $langs->trans('ConfirmReOpenProp', $this->getRef());
                            $buttons[] = array(
                                'label'   => 'Réouvrir',
                                'icon'    => 'undo',
                                'onclick' => $this->getJsActionOnclick('reopen', array(), array(
                                    'confirm_msg' => strip_tags($text)
                                ))
                            );
                        } else {
                            $buttons[] = array(
                                'label'    => 'Réouvrir',
                                'icon'     => 'undo',
                                'onclick'  => '',
                                'disabled' => 1,
                                'popover'  => 'Vous n\'avez pas la permission de réouvrir cette proposition commerciale'
                            );
                        }
                    }
                }

                // Envoi mail:
                if ($this->isActionAllowed('sendEmail') && $this->canSetAction('sendEmail')) {
                    $onclick = $this->getJsActionOnclick('sendEmail', array(), array(
                        'form_name' => 'email'
                    ));
                    $buttons[] = array(
                        'label'   => 'Envoyer par email',
                        'icon'    => 'envelope',
                        'onclick' => $onclick,
                    );
                }

                // Classer facturée
                if ($this->isActionAllowed('classifyBilled') && $this->canSetAction('classifyBilled')) {
                    $buttons[] = array(
                        'label'   => 'Classer facturée',
                        'icon'    => 'check',
                        'onclick' => $this->getJsActionOnclick('classifyBilled')
                    );
                }

                // Cloner: 
                if ($this->can("create")) {
                    $buttons[] = array(
                        'label'   => 'Cloner',
                        'icon'    => 'copy',
                        'onclick' => $this->getJsActionOnclick('duplicate', array(
                            'datep' => date('Y-m-d')
                                ), array(
                            'form_name' => 'duplicate_propal'
                        ))
                    );
                }
            }
        }

        $buttons = BimpTools::merge_array($buttons, parent::getActionsButtons());

        return $buttons;
    }

    public function getDirOutput()
    {
        global $conf;

        return $conf->propal->dir_output;
    }

    // Rendus HTML - overrides BimpObject

    public function renderHeaderExtraLeft()
    {
        $html = parent::renderHeaderExtraLeft();

        if ($this->isLoaded()) {
            $html .= '<div class="object_header_infos">';
            $html .= 'Créée le <strong>' . BimpTools::printDate($this->getData('datec'), 'strong') . '</strong>';

            $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $this->dol_object->user_author_id);
            if (BimpObject::objectLoaded($user)) {
                $html .= ' par ' . $user->getLink();
            }

            $html .= '</div>';

            $status = (int) $this->getData('fk_statut');
            if ($status >= 1 && (int) $this->dol_object->user_valid_id) {
                $html .= '<div class="object_header_infos">';
                $html .= 'Validée le <strong>' . BimpTools::printDate($this->dol_object->datev, 'strong') . '</strong>';

                $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $this->dol_object->user_valid_id);
                if (BimpObject::objectLoaded($user)) {
                    $html .= ' par ' . $user->getLink();
                }

                $html .= '</div>';
            }

            if ($status >= 2 && (int) $this->dol_object->user_close_id) {
                $date_cloture = $this->db->getValue('propal', 'date_cloture', '`rowid` = ' . (int) $this->id);
                if (!is_null($date_cloture) && $date_cloture) {
                    $html .= '<div class="object_header_infos">';
                    $html .= 'Fermée le <strong>' . date('d / m / Y', BimpTools::getDateForDolDate($date_cloture)) . '</strong>';

                    $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $this->dol_object->user_close_id);
                    if (BimpObject::objectLoaded($user)) {
                        $html .= ' par ' . $user->getLink();
                    }

                    $html .= '</div>';
                }
            }

            $client = $this->getChildObject('client');
            if (BimpObject::objectLoaded($client)) {
                $html .= '<div style="margin-top: 10px">';
                $html .= '<strong>Client: </strong>';
                $html .= $client->getLink();
                $html .= '</div>';
            }
        }

        return $html;
    }

    public function renderHeaderExtraRight()
    {
        $html = '';

        $html .= '<div class="buttonsContainer">';

        $pdf_dir = $this->getDirOutput();
        $ref = dol_sanitizeFileName($this->getRef());
        $pdf_file = $pdf_dir . '/' . $ref . '/' . $ref . '.pdf';
        if (file_exists($pdf_file)) {
            $url = DOL_URL_ROOT . '/document.php?modulepart=' . static::$dol_module . '&file=' . htmlentities($ref . '/' . $ref . '.pdf');
//            $onclick = 'window.open(\'' . $url . '\');';

            $html .= BimpRender::renderButton(array(
                        'classes'     => array('btn', 'btn-default'),
                        'label'       => $ref . '.pdf',
                        'icon_before' => 'fas_file-pdf',
                        'attr'        => array(
                            'href'   => $url,
                            'target' => '_blank',
                        )
                            ), "a");
        }

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
                }
            }
        }


        $html .= BimpRender::renderButton(array(
                    'classes'     => array('btn', 'btn-default'),
                    'label'       => 'Ancienne version',
                    'icon_before' => 'fa_file',
                    'attr'        => array(
                        'href' => "../comm/propal/card.php?id=" . $this->id
                    )
                        ), "a");

        $html .= '</div>';

        return $html;
    }

    // Traitements: 

    public function review($check = true, &$errors = array(), &$warnings = array())
    {
        global $user, $langs;

        $errors = array();
        if ($check) {
            if (!$this->isActionAllowed('review', $errors)) {
                return 0;
            }
        }

        require_once(DOL_DOCUMENT_ROOT . "/bimpcore/classes/BimpRevision.php");

        $revision = new BimpRevisionPropal($this->dol_object);
        $new_id_propal = (int) $revision->reviserPropal(false, true, $this->getData('model_pdf'), $errors);

        if (!$new_id_propal) {
            return 0;
        }



        $newPropal = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', $new_id_propal);

        if (!BimpObject::objectLoaded($newPropal)) {
            $errors[] = 'Echec de la création de la révision pour une raison inconnue';
            return 0;
        }

        $signature = $this->getChildObject('signature');

        if (BimpObject::objectLoaded($signature)) {
            $signature->cancelSignature();
        }

        $newPropal->set('zone_vente', $this->getData('zone_vente'));
        $pw = array();
        $newPropal->update($pw, true);

        $totHt = (float) $this->dol_object->total_ht;

        // Ajout des notes: 
        $this->addNote('Proposition commerciale mise en révision le ' . date('d / m / Y') . ' par ' . $user->getFullName($langs) . "\n" . 'Révision: ' . $newPropal->getRef());
        $newPropal->addNote('Révision de la proposition: ' . $this->getRef());

        // Copie des lignes: 
        $warnings = BimpTools::merge_array($warnings, $newPropal->createLinesFromOrigin($this, array(
                            'is_review' => true
        )));

        // Copie des contacts: 
        $newPropal->copyContactsFromOrigin($this, $warnings);

        // Copie des remises globales:
        $newPropal->copyRemisesGlobalesFromOrigin($this, $warnings);

        // Ajout de la ligne "Proposition commerciale révisée" dans la propale actuelle: 
        $line = BimpObject::getInstance('bimpcommercial', 'Bimp_PropalLine');
        $line->desc = 'Proposition commerciale révisée';
        $line->tva_tx = (($this->dol_object->total_ttc / ($totHt != 0 ? $totHt : 1) - 1) * 100);
        $line->pu_ht = -$totHt;
        $line->pa_ht = -$totHt;
        $line->qty = 1;

        $line->validateArray(array(
            'id_obj'    => (int) $this->id,
            'type'      => ObjectLine::LINE_FREE,
            'deletable' => 1,
            'editable'  => 0,
            'remisable' => 0
        ));

        $line_warnings = array();
        $line_errors = $line->create($line_warnings, true);

        if (count($line_errors)) {
            $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de la création de la ligne "révision"');
        }

        if (count($line_warnings)) {
            $warnings[] = BimpTools::getMsgFromArray($line_warnings, 'Erreurs suite à la création de la ligne "révision"');
        }

        return $new_id_propal;
    }

    public function createSignature($open_public_acces = true, $id_contact = 0, $email_content = '', &$warnings = array())
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
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
                        'obj_module' => 'bimpcommercial',
                        'obj_name'   => 'Bimp_Propal',
                        'id_obj'     => $this->id,
                        'doc_type'   => 'devis',
                        'id_client'  => $id_client,
                        'id_contact' => $id_contact
                            ), true, $errors, $warnings);

            if (!count($errors) && BimpObject::objectLoaded($signature)) {
                $errors = $this->updateField('id_signature', (int) $signature->id);
                if ($open_public_acces) {
                    $open_errors = $signature->openSignDistAccess($email_content, true);

                    if (count($open_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($open_errors, 'Echec de l\'ouverture de l\'accès à la signature à distance');
                    }
                }
            }
        }

        return $errors;
    }

    // Traitements - overrides BimpComm: 

    public function duplicate($new_data = array(), &$warnings = array(), $force_create = false)
    {

        if (!$this->isLoaded()) {
            return array('ID ' . $this->getLabel('of_the') . ' absent');
        }

        if (!$this->fetch($this->id)) {
            return array(BimpTools::ucfirst($this->getLabel('this')) . ' est invalide. Copie impossible');
        }

        $date_diff = 0;
        if (isset($new_data['date_livraison']) && $new_data['date_livraison'] !== $this->getData('date_livraison')) {
            $date_diff = (int) BimpTools::getDateForDolDate($new_data['date_livraison']) - (int) BimpTools::getDateForDolDate($this->getData('date_livraison'));
        }

        $now = date('Y-m-d H:i:s');
        $new_data['datep'] = $now;
        $new_data['datec'] = $now;
        $fin_validite = BimpTools::getDateForDolDate($now) + ($this->dol_object->duree_validite * 24 * 3600);
        $new_data['fin_validite'] = BimpTools::getDateFromDolDate($fin_validite);
        $new_data['id_signature'] = 0;
        $new_data['signature_params'] = array();

        $errors = parent::duplicate($new_data, $warnings, $force_create);

        if ($date_diff) {
            $lines = $this->getChildrenObjects('lines');
            foreach ($lines as $line) {
                $update = false;
                if (isset($line->date_from) && (string) $line->date_from) {
                    $new_date_from = (BimpTools::getDateForDolDate($line->date_from) + $date_diff);
                    $line->date_from = BimpTools::getDateFromDolDate($new_date_from);
                    $update = true;
                }

                if (isset($line->date_to) && (string) $line->date_to) {
                    $new_date_to = (BimpTools::getDateForDolDate($line->date_to) + $date_diff);
                    $line->date_to = BimpTools::getDateFromDolDate($new_date_to);
                    $update = true;
                }

                if ($update) {
                    $line_warnings = array();
                    $line->update($line_warnings, true);
                }
            }
        }

        return $errors;
    }

    // Actions:

    public function actionValidate($data, &$success)
    {
        $use_signature = (int) BimpCore::getConf('propal_use_signatures', null, 'bimpcommercial');

        if ($use_signature) {
            $id_contact = (int) BimpTools::getArrayValueFromPath($data, 'id_contact_signature', 0);
            $open_public_access = (int) BimpTools::getArrayValueFromPath($data, 'open_public_access', 0);

            if ($open_public_access) {
                if (!$id_contact) {
                    return array(
                        'errors'   => array('Contact signataire obligatoire pour ouvrir l\'accès à la signature à distance'),
                        'warnings' => array()
                    );
                }
            }
        }

        $result = parent::actionValidate($data, $success);

        if (!count($result['errors'])) {
            if ($use_signature) {
                $email_content = BimpTools::getArrayValueFromPath($data, 'email_content', $this->getDefaultSignDistEmailContent());
                $result['warnings'] = BimpTools::merge_array($result['warnings'], $this->createSignature($open_public_access, $id_contact, $email_content));
            }
        }

        return $result;
    }

    public function actionClose($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Cloture de la proposition commerciale effectuée avec succès';

        if (!isset($data['new_status']) || !(int) $data['new_status']) {
            $errors[] = 'Nouveau statut non spécifié';
        } else {
            global $user;
            $note = isset($data['note']) ? $data['note'] : '';
            if ($this->dol_object->cloture($user, (int) $data['new_status'], $note) <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la cloture de la proposition commerciale');
            } else {
                if ((int) $this->getData('id_signature')) {
                    $signature = $this->getChildObject('signature');

                    if (BimpObject::objectLoaded($signature)) {
                        $signature_errors = $signature->cancelSignature();

                        if (count($signature_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($signature_errors, 'Echec de l\'annulation de la signature');
                        }
                    }
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'bimp_reloadPage();'
        );
    }

    public function actionReopen($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Réouverture effectuée avec succès';

        global $user;
        if ($this->dol_object->reopen($user, Propal::STATUS_VALIDATED) <= 0) {
            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la réouverture ' . $this->getLabel('of_the'));
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'bimp_reloadPage();'
        );
    }

    public function actionClassifyBilled($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = BimpTools::ucfirst($this->getLabel() . ' classé' . ($this->isLabelFemale() ? 'e' : '') . ' facturé' . ($this->isLabelFemale() ? 'e' : '') . ' avec succès');

        global $conf, $user;
        $factures = $this->dol_object->getInvoiceArrayList();
        if ((is_array($factures) && count($factures)) ||
                empty($conf->global->WORKFLOW_PROPAL_NEED_INVOICE_TO_BE_CLASSIFIED_BILLED)) {
            if ($this->dol_object->cloture($user, 4, '') < 0) {
                $errors[] = BimpTools::getErrorsFromDolObject($this->dol_object);
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionGeneratePdf($data, &$success = '', $errors = array(), $warnings = array())
    {
        $wanings = array();
        if ((int) $this->id && $data['model'] == "bimpdevissav") {
            if (!(int) $this->getIdSav()) {
                $data['model'] = "bimpdevis";
                $wanings[] = 'Aucun SAV associé à cette propale trouvé';
            }
        }

        return parent::actionGeneratePdf($data, $success, array(), $wanings);
    }

    public function actionReview($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Proposition commerciale révisée avec succès';

        $new_id_propal = $this->review(false, $errors, $warnings);

        $url = DOL_URL_ROOT . '/bimpcommercial/index.php?fc=propal&id=' . $new_id_propal;

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'window.location = \'' . $url . '\''
        );
    }

    public function actionCreateContrat($data, &$success = '')
    {
        $warnings = [];
        $errors = [];
        $instance = $this->getInstance('bimpcontract', 'BContract_contrat');
        $autre_erreurs = true;
        $id_new_contrat = 0;

        if (count($data) == 0) {
            $autre_erreurs = false;
            $errors[] = "La création du contrat  est impossible en l'état. Si cela est une erreur merci d'envoyer un mail à debugerp@bimp.fr. Cordialement.";
        }

        $client = $this->getInstance('bimpcore', 'Bimp_Societe', $this->getData('fk_soc'));
        if (!$client->getData('contact_default') && $autre_erreurs) {
            //$errors[] = "Pour créer le contrat le client doit avoir un contact par défaut <br /> Client : " . $client->getNomUrl();
            $errors[] = "Le Contact email facturation par défaut doit exister dans la fiche client <br /> Client : <a href='" . $client->getUrl() . "' target='_blank' >" . $client->getData('code_client') . "</a> ";
        }

        if ($data['re_new'] == 0 && $autre_erreurs) {
            $errors[] = "Vous devez obligatoirement choisir un type de renouvellement.";
        }


        if (!count($errors)) {
            $id_new_contrat = $instance->createFromPropal($this, $data);
            if ($id_new_contrat > 0) {
                if ($this->getData('fk_statut') < 2)
                    $this->updateField('fk_statut', 2);
                $callback = 'window.location.href = "' . DOL_URL_ROOT . '/bimpcontract/index.php?fc=contrat&id=' . $id_new_contrat . '"';

                $signature = $this->getChildObject('signature');

                if (BimpObject::objectLoaded($signature)) {
                    $cancel_errors = $signature->cancelSignature();

                    if (count($cancel_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($cancel_errors, 'Echec de l\'annulation de la signature');
                    }
                }
            } else {
                if ($client->getData('solvabilite_status') > 1) {
                    $errors[] = "Le contrat ne peut pas être créé car le client est bloqué";
                } else {
                    $errors[] = "Le contrat n'a pas été créé";
                }
            }
        }


        return [
            'success_callback' => $callback,
            'warnings'         => $warnings,
            'errors'           => $errors
        ];
    }

    public function actionCreateSignature($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Signature créée avec succès';
        $url = '';

        $open_public_access = (int) BimpTools::getArrayValueFromPath($data, 'open_public_access', 1);
        $id_contact = (int) BimpTools::getArrayValueFromPath($data, 'id_contact_signature', 0);
        $email_content = BimpTools::getArrayValueFromPath($data, 'email_content', $this->getDefaultSignDistEmailContent());

        if ($open_public_access && !$id_contact) {
            $errors[] = 'Contact signataire obligatoire pour ouvrir l\'accès à la signature à distance';
        }

        if (!count($errors)) {
            $errors = $this->createSignature($open_public_access, $id_contact, $email_content, $warnings);

            if (!count($errors)) {
                $signature = $this->getChildObject('signature');

                if (BimpObject::objectLoaded($signature)) {
                    $url = $signature->getUrl();
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => ($url ? 'window.open(\'' . $url . '\');' : '')
        );
    }

    // Overrides BimpObject: 

    public function validatePost()
    {
        $errors = parent::validatePost();

        if (BimpTools::isSubmit('duree_validite')) {
            $this->dol_object->duree_validite = (int) BimpTools::getValue('duree_validite');
        }

        return $errors;
    }

    protected function updateDolObject(&$errors = array(), &$warnings = array())
    {
        if (!$this->isLoaded()) {
            return 0;
        }
        if (is_null($this->dol_object)) {
            $errors[] = 'Objet Dolibarr invalide';
            return 0;
        }

        if (!isset($this->dol_object->id) || !$this->dol_object->id) {
            $errors[] = 'Objet Dolibarr invalide';
            return 0;
        }

        global $user;

        $bimpObjectFields = array();
        $this->hydrateDolObject($bimpObjectFields);

        if (method_exists($this, 'beforeUpdateDolObject')) {
            $this->beforeUpdateDolObject();
        }

        $result = $this->dol_object->update($user);
        if ($result <= 0) {
            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la mise à jour de la propale');
            return $errors;
        }

        // Mise à jour des champs Bimp_Propal:
        foreach ($bimpObjectFields as $field => $value) {
            $field_errors = $this->updateField($field, $value);
            if (count($field_errors)) {
                $errors[] = BimpTools::getMsgFromArray($field_errors, 'Echec de la mise à jour du champ "' . $field . '"');
            }
        }

        // Mise à jour des extra_fields: 
        if ($this->dol_object->insertExtraFields('', $user) <= 0) {
            $errors[] = 'Echec de la mise à jour des champs supplémentaires';
        }

        $this->dol_object->fetch($this->id);

        // Ref. client
        if ((string) $this->getData('ref_client') !== (string) $this->dol_object->ref_client) {
            $this->dol_object->error = '';
            $this->dol_object->errors = array();

            if ($this->dol_object->set_ref_client($user, $this->getData('ref_client')) <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la mise à jour de la référence client');
            }
        }

        // Origine:
        if ((int) $this->getData('fk_input_reason') !== (int) $this->dol_object->demand_reason_id) {
            $this->dol_object->error = '';
            $this->dol_object->errors = array();

            if ($this->dol_object->set_demand_reason($user, (int) $this->getData('fk_input_reason')) <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la mise à jour de l\'origine');
            }
        }

        // Conditions de réglement: 
        if ((int) $this->getData('fk_cond_reglement') !== (int) $this->dol_object->cond_reglement_id) {
            $this->dol_object->error = '';
            $this->dol_object->errors = array();

            if ($this->dol_object->setPaymentTerms((int) $this->getData('fk_cond_reglement')) <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la mise à jour des conditions de réglement');
            }
        }

        // Mode de réglement: 
        if ((int) $this->getData('fk_mode_reglement') !== (int) $this->dol_object->mode_reglement_id) {
            $this->dol_object->error = '';
            $this->dol_object->errors = array();

            if ($this->dol_object->setPaymentMethods((int) $this->getData('fk_mode_reglement')) <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la mise à jour du mode de réglement');
            }
        }

        // Note privée: 
        if ((string) $this->getData('note_private') !== (string) $this->dol_object->note_private) {
            $this->dol_object->error = '';
            $this->dol_object->errors = array();

            if ($this->dol_object->update_note((string) $this->getData('note_private'), '_private') <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la mise à jour de la note privée');
            }
        }

        // Note publique: 
        if ((string) $this->getData('note_public') !== (string) $this->dol_object->note_public) {
            $this->dol_object->error = '';
            $this->dol_object->errors = array();

            if ($this->dol_object->update_note((string) $this->getData('note_public'), '_public') <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la mise à jour de la note publique');
            }
        }

        // Date: 
        if ($this->getData('datep') != $this->getInitData('datep')) {
            $this->updateField('fin_validite', BimpTools::getDateForDolDate($this->getData('datep')) + ($this->dol_object->duree_validite * 24 * 3600));
        }


        if ((string) $this->getData('datep')) {
            $date = BimpTools::getDateForDolDate($this->getData('datep'));
        } else {
            $date = '';
        }
        if ($date !== $this->dol_object->date) {
            $this->dol_object->error = '';
            $this->dol_object->errors = array();

            if ($this->dol_object->set_date($user, $date) <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la mise à jour de la date');
            }
        }

        // Date fin validité: 
        if ((string) $this->getData('fin_validite')) {
            $date = BimpTools::getDateForDolDate($this->getData('fin_validite'));
        } else {
            $date = '';
        }
        if ($date !== $this->dol_object->fin_validite) {
            $this->dol_object->error = '';
            $this->dol_object->errors = array();

            if ($this->dol_object->set_echeance($user, $date) <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la mise à jour de la date de fin de validité');
            }
        }

        // Date livraison: 
        if ((string) $this->getData('date_livraison')) {
            $date = BimpTools::getDateForDolDate($this->getData('date_livraison'));
        } else {
            $date = '';
        }
        if ($date !== $this->dol_object->date_livraison) {
            $this->dol_object->error = '';
            $this->dol_object->errors = array();

            if ($this->dol_object->set_date_livraison($user, $date) <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la mise à jour de la date de livraison');
            }
        }

        // Délai de livraison: 
        if ((int) $this->getData('fk_availability') !== (int) $this->dol_object->availability_id) {
            $this->dol_object->error = '';
            $this->dol_object->errors = array();

            if ($this->dol_object->set_availability($user, (int) $this->getData('fk_availability')) <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la mise à jour du délai de livraison');
            }
        }


        if (!count($errors)) {
            return 1;
        }

        return 0;
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            $list = $this->dol_object->liste_type_contact('internal', 'position', 1);
            if (isset($list['SALESREPSIGN'])) {
                $id_user = (int) BimpTools::getValue('id_user_commercial', 0);
                if ($id_user) {
                    if ($this->dol_object->add_contact($id_user, 'SALESREPSIGN', 'internal') <= 0) {
                        $warnings[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de l\'enregistrement du commercial signataire');
                    }
                }
            }

            $client = $this->getChildObject('client');
            if (BimpObject::objectLoaded($client)) {
                $client->setActivity('Création ' . $this->getLabel('of_the') . ' {{Devis:' . $this->id . '}}');
            }
        }

        return $errors;
    }

    // Gestion Signature: 

    public function getSignatureDocFileDir($doc_type)
    {
        return $this->getFilesDir();
    }

    public function getSignatureDocFileName($doc_type, $signed = false)
    {
        switch ($doc_type) {
            case 'devis':
                return dol_sanitizeFileName($this->getRef()) . ($signed ? '_signe' : '') . '.pdf';
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
            switch ($doc_type) {
                case 'devis':
                    if ($context === 'public') {
                        return self::getPublicBaseUrl() . 'fc=doc&doc=devis' . ($signed ? '_signed' : '') . '&docid=' . $this->id . '&docref=' . $this->getRef();
                    } else {
                        return $this->getFileUrl($fileName);
                    }
                    break;
            }
        }

        return '';
    }

    public function getSignatureDocRef($doc_type)
    {
        return $this->getRef();
    }

    public function getSignatureParams($doc_type)
    {
        return BimpTools::overrideArray(self::$default_signature_params, (array) $this->getData('signature_params'));
    }

    public function getSignatureCommercialEmail($doc_type)
    {
        $sav = $this->getSav();
        if (BimpObject::objectLoaded($sav)) {
            return $sav->getSignatureCommercialEmail($doc_type);
        }

        $client = $this->getChildObject('client');

        if (BimpObject::objectLoaded($client)) {
            return $client->getCommercialEmail(false);
        }

        return '';
    }

    public function getOnSignedEmailExtraInfos($doc_type)
    {
        $sav = $this->getSav();

        if (BimpObject::objectLoaded($sav)) {
            return $sav->getOnSignedEmailExtraInfos('devis_sav');
        }

        return '';
    }

    public function onSigned($bimpSignature, $data)
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            $sav = $this->getSav();

            if (BimpObject::objectLoaded($sav)) {
                $errors = $sav->onPropalSigned($bimpSignature);
            } else {
                $this->updateField('fk_statut', Propal::STATUS_SIGNED);
            }
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
}
