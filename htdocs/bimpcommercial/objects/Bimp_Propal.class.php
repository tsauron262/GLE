<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/BimpComm.class.php';

if (defined('BIMP_EXTENDS_VERSION') && BIMP_EXTENDS_VERSION) {
    if (file_exists(DOL_DOCUMENT_ROOT . '/bimpcommercial/extends/versions/' . BIMP_EXTENDS_VERSION . '/objects/BimpComm.class.php')) {
        require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/extends/versions/' . BIMP_EXTENDS_VERSION . '/objects/BimpComm.class.php';
    }
}

if (BimpCore::getExtendsEntity() != '' && BimpCore::getExtendsEntity()) {
    if (file_exists(DOL_DOCUMENT_ROOT . '/bimpcommercial/extends/entities/' . BimpCore::getExtendsEntity() . '/objects/BimpComm.class.php')) {
        require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/extends/entities/' . BimpCore::getExtendsEntity() . '/objects/BimpComm.class.php';
    }
}

if (class_exists('BimpComm_ExtEntity')) {

    class Bimp_PropalTemp extends BimpComm_ExtEntity
    {
        
    }

} elseif (class_exists('BimpComm_ExtVersion')) {

    class Bimp_PropalTemp extends BimpComm_ExtVersion
    {
        
    }

} else {

    class Bimp_PropalTemp extends BimpComm
    {
        
    }

}

class Bimp_Propal extends Bimp_PropalTemp
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
        2 => array('label' => 'Acceptée', 'icon' => 'check', 'classes' => array('success')),
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

    public function canView()
    {
        global $user;
        if (isset($user->rights->propal->lire)) {
            return (int) $user->rights->propal->lire;
        }
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
            case 'close':
                return $this->can("edit");

            case 'createOrder':
                $commande = BimpObject::getInstance('bimpcommercial', 'Bimp_Commande');
                return $commande->can("create") /* && (int) $user->rights->bimpcommercial->edit_comm_fourn_ref */;

            case 'createContract':
            case 'createContratAbo':
                if ($user->admin || $user->rights->contrat->creer) {
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

            case 'downloadSignature':
            case 'redownloadSignature':
            case 'createSignatureDocuSign':
                return $user->admin;
        }

        return parent::canSetAction($action);
    }

    // Getters booléens:

    public function isActionAllowed($action, &$errors = array())
    {
        if ($this->erreurFatal)
            return 0;

        global $conf;
        $status = $this->getData('fk_statut');

        if (in_array($action, array('validate', 'modify', 'review', 'close', 'reopen', 'sendEmail', 'createOrder', 'createContract', 'createInvoice', 'createContratAbo', 'classifyBilled', 'createContrat', 'createSignature'))) {
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

                if (!parent::canSetAction('validate', $errors)) { // test important dans BimpComm
                    return 0;
                }
                return (count($errors) ? 0 : 1);

            case 'modify':
                return 0;

            case 'close':
                if ($status !== Propal::STATUS_VALIDATED) {
                    $errors[] = 'Le statut actuel ' . $this->getLabel('of_this') . ' ne permet pas cette opération';
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

            case 'createContratAbo':
                if ($status != Propal::STATUS_SIGNED) {
                    $errors[] = 'Statut actuel ' . $this->getLabel('of_the') . ' invalide';
                    return 0;
                }

                if (!BimpCore::isModuleActive('bimpcontrat')) {
                    $errors[] = 'Création des contrats d\'abonnement désactivée';
                    return 0;
                }

                if (!(int) $this->getNbAbonnements()) {
                    $errors[] = 'Aucune ligne d\'abonnement dans ce devis';
                    return 0;
                }

                $items = BimpTools::getDolObjectLinkedObjectsList($this->dol_object, $this->db, array('bimp_contrat'));
                if (!empty($items)) {
                    $errors[] = 'Contrat d\'abonnement déjà créé';
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
                if ($status !== Propal::STATUS_SIGNED) {
                    $errors[] = 'Statut actuel ' . $this->getLabel('of_the') . ' invalide';
                    return 0;
                }
                if ($status == 0) {
                    $errors[] = ucfirst($this->getLabel('this')) . ' est au statut brouillon';
                    return 0;
                }

                $linked_contrat = getElementElement('propal', 'contrat', $this->id);
                foreach ($linked_contrat as $ln) {
                    $ct = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat', $ln['d']);
                    if ($ct->getData('statut') != BContract_contrat::CONTRAT_STATUT_ABORT && $ct->getData('statut') != BContract_contrat::CONTRAT_STATUS_REFUSE) {
                        $errors[] = 'Un contrat existe déjà pour cette proposition commerciale';
                        return 0;
                    }
                }
                return 1;

            case 'createSignature':
                if ($status < 1) {
                    $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' n\'est pas validé' . $this->e();
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

            case 'downloadSignature':
                if ($status != 1) {
                    $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' n\'est pas au statut "Validé' . $this->e() . '"';
                    return 0;
                }

                $signature = BimpCache::getBimpObjectInstance('bimpcore', 'BimpSignature', (int) $this->getData('id_signature'));
                if (!BimpObject::objectLoaded($signature)) {
                    $errors[] = 'La signature n\'existe pas';
                    return 0;
                }

                $file_name = $this->getSignatureDocFileName('devis', true);
                $file_dir = $this->getSignatureDocFileDir('devis');
                if (file_exists($file_dir . $file_name)) {
                    return 0;
                }

                return 1;

            case 'redownloadSignature':
                if ($status != 1) {
                    $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' n\'est pas au statut "Validé' . $this->e() . '"';
                    return 0;
                }

                $signature = BimpCache::getBimpObjectInstance('bimpcore', 'BimpSignature', (int) $this->getData('id_signature'));
                if (!BimpObject::objectLoaded($signature)) {
                    $errors[] = 'La signature n\'existe pas';
                    return 0;
                }

                $file_name = $this->getSignatureDocFileName('devis', true);
                $file_dir = $this->getSignatureDocFileDir('devis');
                if (!file_exists($file_dir . $file_name)) {
                    return 0;
                }

                return 1;

            case 'createSignatureDocuSign':
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
                        $service = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $dol_line->getData('fk_product'));
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
                if ((int) $signature->isSigned()) {
                    return 1;
                }
            }
        }

        return 0;
    }

    public function isDocuSignAllowed(&$errors = array(), &$is_required = false)
    {
        // Attention : pas de conditions spécifiques à une version de l'ERP ici. 
        // Utiliser une extension.  
        if (!(int) BimpCore::getConf('propal_signature_allow_docusign', null, 'bimpcommercial')) {
            $errors[] = 'Signature DocuSign non autorisée pour ce devis';
            return 0;
        }

        $is_required = false;
        return 1;
    }

    public function isSignDistAllowed(&$errors = array())
    {
        // Attention : pas de conditions spécifiques à une version de l'ERP ici. 
        // Utiliser une extension.  
        $ds_errors = array();
        $ds_required = false;
        if ((int) $this->isDocuSignAllowed($ds_errors, $ds_required)) {
            if ($ds_required) {
                $errors[] = 'DocuSign requis pour la signature à distance de ce devis';
                return 0;
            }
        }

        if (!(int) BimpCore::getConf('propal_signature_allow_dist', null, 'bimpcommercial')) {
            $errors[] = 'Signature éléctronique à distance non autorisée pour ce devis';
            return 0;
        }

        return 1;
    }

    // Getters données : 

    public function getIdSav()
    {
        global $conf;
        if (isset($conf->global->MAIN_MODULE_BIMPSUPPORT) && $conf->global->MAIN_MODULE_BIMPSUPPORT && is_null($this->id_sav)) {
            if ($this->isLoaded()) {
                $this->id_sav = BimpCache::getIdSavFromIdPropal($this->id);
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

        return 7;
    }

    public function getHelpDateRenouvellementContrat()
    {
        $help = "";

        if (!$this->isNotRenouvellementContrat()) {
            $help = "Cette case correspond à la date d'effet du contrat, si cette case n'est pas renseignée, lors du formulaire de création du contrat vous pourrez le faire";
        }

        return $help;
    }

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
        global $langs, $user;
        $langs->load('propal');

        $buttons = array();
        $signature_buttons = array();

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
                    $signature = $this->getChildObject('signature');
                    $no_signature = false;
                    $signature_cancelled = false;
                    $accepted = in_array($status, array(2, 4));
                    $signed = false;

                    if (BimpObject::objectLoaded($signature)) {
                        $signature_buttons = BimpTools::merge_array($signature_buttons, $signature->getActionsButtons(true));

                        if ($signature->isSigned()) {
                            $signed = true;
                        } elseif ((int) $signature->getData('status') === BimpSignature::STATUS_CANCELLED) {
                            $signature_cancelled = true;
                        }
                    } else {
                        if ($use_signature) {
                            if ($this->isActionAllowed('createSignature') && $this->canSetAction('createSignature')) {
                                $no_signature = true;
                                // Créer Signature: 
                                $signature_buttons[] = array(
                                    'label'   => 'Créer la fiche signature',
                                    'icon'    => 'fas_signature',
                                    'onclick' => $this->getJsActionOnclick('createSignature', array(), array(
                                        'form_name' => 'create_signature'
                                    ))
                                );
                            }
                        } elseif ($accepted) {
                            $signed = true;
                        }
                    }

                    if (!$accepted) {
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

                        if ($no_signature || !$use_signature || $signature_cancelled) {
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
                                'note_public'        => $this->getData('note_public'),
                                'note_private'       => $this->getData('note_private'),
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

                    // Créer un contrat
                    if (1) {
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

                if ($user->admin) { // Mettre ça dans canSetAction()
                    // Téléchargement des fichiers
                    if (!isset($signature) || !BimpObject::objectLoaded($signature)) {
                        $signature = $this->getChildObject('signature');
                    }
                }
            }
        }

        $buttons = BimpTools::merge_array($buttons, parent::getActionsButtons());

        if (!empty($signature_buttons)) {
            return array(
                'buttons_groups' => array(
                    array(
                        'label'   => 'Actions',
                        'icon'    => 'fas_cogs',
                        'buttons' => $buttons
                    ),
                    array(
                        'label'   => 'Actions Signature',
                        'icon'    => 'fas_signature',
                        'buttons' => $signature_buttons
                    )
                )
            );
        }

        return $buttons;
    }

    public function getDirOutput()
    {
        global $conf;

        return $conf->propal->dir_output;
    }

    public function getAbonnementsLinesIds()
    {
        $lines = array();
        if ($this->isLoaded()) {
            BimpObject::loadClass('bimpcore', 'Bimp_Product');
            $where = 'pdet.fk_propal = ' . $this->id . ' AND pef.type2 IN(' . implode(',', Bimp_Product::$abonnements_sous_types) . ')';

            $rows = $this->db->getRows('bimp_propal_line a', $where, null, 'array', array('DISTINCT a.id'), null, null, array(
                'pdet' => array(
                    'table' => 'propaldet',
                    'on'    => 'pdet.rowid = a.id_line'
                ),
                'pef'  => array(
                    'table' => 'product_extrafields',
                    'on'    => 'pef.fk_object = pdet.fk_product'
                )
            ));

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    $lines[] = $r['id'];
                }
            }
        }

        return $lines;
    }

    public function getNbAbonnements()
    {
        return count($this->getAbonnementsLinesIds());
    }

    public function getAbonnementLines()
    {
        $lines = array();
        $items = $this->getAbonnementsLinesIds();

        foreach ($items as $id_line) {
            $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_PropalLine', $id_line);
            if (BimpObject::objectLoaded($line)) {
                $lines[$id_line] = $line;
            }
        }

        return $lines;
    }

    public function getClientContratsAboArray()
    {
        $contrats = array(
            0 => 'Nouveau contrat'
        );

        if ((int) $this->getData('fk_soc')) {
            foreach (BimpCache::getBimpObjectObjects('bimpcontrat', 'BCT_Contrat', array(
                'fk_soc'  => (int) $this->getData('fk_soc'),
                'statut'  => array(0, 1),
                'version' => 2
            )) as $contrat) {
                $contrats[$contrat->id] = $contrat->getRef();
            }
        }


        return $contrats;
    }

    public function getDefaultContratAbo()
    {
        if ($this->getData('fk_soc')) {
            $where = 'fk_soc = ' . (int) $this->getData('fk_soc');
            $where .= ' AND statut IN (0,1)';
            $where .= ' AND version = 2';
            return (int) $this->db->getValue('contrat', 'rowid', $where, 'rowid', 'DESC');
        }

        return 0;
    }

    // Affichages : 

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

    // Rendus HTML : 

    public function renderHeaderStatusExtra()
    {
        $html = '';

        if ((int) $this->getData('id_signature')) {
            $signature = $this->getChildObject('signature');

            if (BimpObject::objectLoaded($signature)) {
                $html .= '<br/>Signature du devis : ' . $signature->displayData('status', 'default', false);
            }
        }

        $html .= parent::renderHeaderStatusExtra();

        return $html;
    }

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
                    $html .= 'Fermée le <strong>' . date('d / m / Y', BimpTools::getDateTms($date_cloture)) . '</strong>';

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

            if ((int) $this->getData('fk_statut') == 1) {
                $signature = null;
                if ((int) $this->getData('id_signature')) {
                    $signature = $this->getChildObject('signature');
                }

                if (BimpObject::objectLoaded($signature)) {
                    $alertes = $signature->renderSignatureAlertes();
                    if ($alertes) {
                        $html .= '<div style="margin-top: 10px">';
                        $html .= $alertes;
                        $html .= '</div>';
                    }
                } elseif (!$this->field_exists('id_demande_fin') || !(int) $this->getData('id_demande_fin')) {
                    $msg = BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft');
                    $msg .= 'Si vous souhaitez <b>déposer le devis signé</b> ou demander la <b>signature électronique</b> à distance au client, veuillez cliquer sur "<b>Créer la fiche signature</b>"';
                    $html .= '<div style="margin-top: 10px">';
                    $html .= BimpRender::renderAlerts($msg, 'warning');
                    $html .= '</div>';
                }
            }

            $nb_abos = $this->getNbAbonnements();
            if ($nb_abos > 0) {
                $s = ($nb_abos > 1 ? 's' : '');
                $msg = BimpTools::ucfirst($this->getLabel('this')) . ' contient <b>' . $nb_abos . ' ligne' . $s . '</b> devant donner lieu à un contrat d\'abonnement.<br/>';

                if ($this->isActionAllowed('createContratAbo') && $this->canSetAction('createContratAbo')) {
                    $msg .= '<div class="buttonsContainer" style="text-align: right">';
                    $onclick = $this->getJsActionOnclick('createContratAbo', array(), array(
                        'form_name' => 'contrat_abo'
                    ));

                    $msg .= '<span class="btn btn-default" onclick="' . $onclick . '">';
                    $msg .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Créer un contrat d\'abonnement';
                    $msg .= '</span>';
                    $msg .= '</div>';
                }
                $html .= BimpRender::renderAlerts($msg, 'warning');
            }
        }

        return $html;
    }

    public function renderHeaderExtraRight($no_div = false)
    {
        $html = '';

        if (!$no_div) {
            $html .= '<div class="buttonsContainer" style="display: inline-block">';
        }

        $pdf_dir = $this->getDirOutput();
        $ref = dol_sanitizeFileName($this->getRef());
        $pdf_file = $pdf_dir . '/' . $ref . '/' . $ref . '.pdf';
        if (file_exists($pdf_file)) {
            $url = DOL_URL_ROOT . '/document.php?modulepart=' . static::$dol_module . '&file=' . htmlentities($ref . '/' . $ref . '.pdf');
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

        $file_signed_url = $this->getSignatureDocFileUrl('devis', 'private', true);
        if ($file_signed_url) {
            $html .= BimpRender::renderButton(array(
                        'classes'     => array('btn', 'btn-default'),
                        'label'       => 'Devis signé',
                        'icon_before' => 'fas_file-pdf',
                        'attr'        => array(
                            'href'   => $file_signed_url,
                            'target' => '_blank',
                        )
                            ), "a");
        }

//        $html .= BimpRender::renderButton(array(
//                    'classes'     => array('btn', 'btn-default'),
//                    'label'       => 'Ancienne version',
//                    'icon_before' => 'fa_file',
//                    'attr'        => array(
//                        'href' => "../comm/propal/card.php?id=" . $this->id
//                    )
//                        ), "a");

        if (!$no_div) {
            $html .= '</div>';
        }

        return $html;
    }

    public function renderSignatureInitDocuSignInput()
    {
        $html = '';

        $errors = array();
        $ds_required = 0;
        if (!$this->isDocuSignAllowed($errors, $ds_required)) {
            $html .= '<div class="danger">';
            $html .= BimpTools::getMsgFromArray($errors, 'Il n\'est pas possible d\'utiliser DocuSign pour la signature de ce devis');
            $html .= '</div>';
            $html .= '<input type="hidden" value="0" name="init_docusign"/>';
        } else {
            $html .= BimpInput::renderInput('toggle', 'init_docusign', ($ds_required ? 1 : 0));
        }

        return $html;
    }

    public function renderSignatureOpenDistAccessInput()
    {
        $html = '';

        $errors = array();
        if (!$this->isSignDistAllowed($errors)) {
            $html .= '<div class="danger">';
            $html .= BimpTools::getMsgFromArray($errors, 'Il n\'est pas possible d\'utiliser la signature électronique à distance pour ce devis');
            $html .= '</div>';
            $html .= '<input type="hidden" value="0" name="open_public_access"/>';
        } else {
            $html .= BimpInput::renderInput('toggle', 'open_public_access', 1);
        }

        return $html;
    }

    // Traitements: 

    public function review($check = true, &$errors = array(), &$warnings = array(), $is_refus = false)
    {
        global $user, $langs;

        $errors = array();
        if ($check) {
            if (!$this->isActionAllowed('review', $errors)) {
                return 0;
            }
        }

        $client = $this->getChildObject('client');
        if (!BimpObject::objectLoaded($client)) {
            $errors[] = 'Client absent';
            return 0;
        }

        $old_ref = $this->getRef();
        $new_propal = BimpObject::getInstance($this->module, $this->object_name);

        $dt = new DateTime();
        $new_data = $this->getDataArray(false, false);
        $new_data['ref'] = '';
        $new_data['fk_statut'] = 0;
        $new_data['datec'] = $dt->format('Y-m-d H:i:s');
        $new_data['datep'] = $dt->format('Y-m-d');
        $new_data['date_valid'] = null;
        $new_data['logs'] = '';

        if ((int) $this->dol_object->duree_validite > 0) {
            $dt->add(new DateInterval('P' . (int) $this->dol_object->duree_validite . 'D'));
            $new_data['fin_validite'] = $dt->format('Y-m-d');
        }

        $new_data['fk_user_author'] = $user->id;
        $new_data['fk_user_valid'] = 0;
        $new_data['fk_user_cloture'] = 0;
        $new_data['id_signature'] = 0;
        $new_data['signature_params'] = array();

        foreach ($new_data as $field => $value) {
            $new_propal->set($field, $value);
        }

        $new_propal->dol_object->duree_validite = $this->dol_object->duree_validite;
        $new_propal->dol_object->entity = $this->dol_object->entity;

        $errors = $new_propal->create($warnings, true);

        if (!count($errors) && !BimpObject::objectLoaded($new_propal)) {
            $errors[] = 'Echec de la création de la révision pour une raison inconnue';
        }

        if (count($errors)) {
            return 0;
        }

        require_once DOL_DOCUMENT_ROOT . '/bimpcore/classes/BimpRevision.php';

        // Maj Ref: 
        $new_ref = BimpRevision::convertRef($old_ref, "propal");
        BimpRevisionPropal::setLienRevision($old_ref, $this->id, $new_propal->id, $new_ref);

        // Ajout objets liés: 
        $elements = getElementElement("propal", null, $this->id);
        foreach ($elements as $item) {
            addElementElement($item['ts'], $item['td'], $new_propal->id, $item['d']);
        }

        $elements = getElementElement("propal", null, $this->id, null, 0);
        foreach ($elements as $item) {
            addElementElement($item['ts'], $item['td'], $new_propal->id, $item['d'], 0);
        }

        // Copie fichiers: 
        $dir = DOL_DATA_ROOT . '/propale/' . $old_ref . "/";
        $dir2 = DOL_DATA_ROOT . '/propale/' . $new_ref . "/";

        if (is_dir($dir)) {
            $cdir = scandir($dir);
            foreach ($cdir as $key => $value) {
                if (!is_dir($dir2))
                    mkdir($dir2);

                if (!in_array($value, array(".", "..")) && stripos($value, "/") !== false) {
                    link($dir . $value, $dir2 . $value);
                }
            }
        }

        // Annulation signature: 
        $signature = $this->getChildObject('signature');

        if (BimpObject::objectLoaded($signature)) {
            $signature->cancelAllSignatures();
        }

        // Ajout des notes: 
        $this->addObjectLog('Proposition commerciale mise en révision le ' . date('d / m / Y') . ' par ' . $user->getFullName($langs) . "\n" . 'Révision: ' . $new_propal->getRef());
        $new_propal->addObjectLog('Révision de la proposition: ' . $this->getRef());

        // Copie des lignes: 
        $errors = BimpTools::merge_array($errors, $new_propal->createLinesFromOrigin($this, array(
                            'is_review'                 => true,
                            'qty_to_zero_sauf_acomptes' => $is_refus
        )));

        // Copie des contacts: 
        $new_propal->copyContactsFromOrigin($this, $warnings);

        // Copie des remises globales:
        $new_propal->copyRemisesGlobalesFromOrigin($this, $warnings);

        // Ajout de la ligne "Proposition commerciale révisée / refusée" dans la révision: 
        $totHt = (float) $this->dol_object->total_ht;

        $line = BimpObject::getInstance('bimpcommercial', 'Bimp_PropalLine');
        $line->desc = ucfirst($this->getLabel() . ($is_refus ? ' refusé' : ' révisé') . $this->e());
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

        $this->checkLines();
        $new_propal->checkLines();

        return $new_propal->id;
    }

    public function createSignature($init_docu_sign = false, $open_public_acces = true, $id_contact = 0, $email_content = '', &$warnings = array(), &$success = '')
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            if (!(int) BimpCore::getConf('propal_use_signatures', null, 'bimpcommercial')) {
                $errors[] = 'Les signatures ne sont pas activées pour les devis';
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
                        'obj_module' => 'bimpcommercial',
                        'obj_name'   => 'Bimp_Propal',
                        'id_obj'     => $this->id,
                        'doc_type'   => 'devis'
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
                    $allow_refuse = (int) BimpCore::getConf('propal_signature_allow_refuse', null, 'bimpcommercial');

                    $signataire = BimpObject::createBimpObject('bimpcore', 'BimpSignataire', array(
                                'id_signature'   => $signature->id,
                                'id_client'      => $id_client,
                                'id_contact'     => $id_contact,
                                'allow_dist'     => $allow_dist,
                                'allow_docusign' => $allow_docusign,
                                'allow_refuse'   => $allow_refuse,
                                'code'           => 'default'
                                    ), true, $signataire_errors, $warnings);

                    if (!BimpObject::objectLoaded($signataire)) {
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
                            $open_errors = $signataire->openSignDistAccess(true, $email_content, true);

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

    public function createSignatureDocusign($id_contact)
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            if ((int) $this->getData('id_signature')) {
                $errors[] = 'La signature a déjà été créée pour ' . $this->getLabel('this');
                return $errors;
            }
        }

        global $user;

        // Vérification du contact
        $contact = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $id_contact);
        if (BimpObject::objectLoaded($contact)) {
            $nom_client = $contact->getData('lastname');
            $prenom_client = $contact->getData('firstname');
            $fonction_client = $contact->getData('poste');
            $email_client = $contact->getData('email');

            if (!$nom_client) {
                $errors[] = 'Nom du signataire absent';
            }

            if (!$prenom_client) {
                $errors[] = 'Prénom du signataire absent';
            }

            if (!$email_client) {
                $errors[] = 'Adresse e-mail du signataire absent' . $id_contact;
            }

            $client = $this->getChildObject('client');

            if (BimpObject::objectLoaded($client)) {
                if ($client->isCompany()) {
                    if (!$fonction_client) {
                        $errors[] = 'Fonction du signataire absent';
                    }
                }
            } else {
                $errors[] = 'ID du client absent';
            }
        } else {
            $errors[] = "Contact d'id " . $id_contact . " inconnu";
        }

        // Commercial
        $comm = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $user->id);
        if (!BimpObject::objectLoaded($comm)) {
            $errors[] = 'Utilisateur courant non renseigné';
        }


        if (!count($errors)) {

            require_once DOL_DOCUMENT_ROOT . '/bimpapi/BimpApi_Lib.php';
            $api = BimpAPI::getApiInstance('docusign');
            if (is_a($api, 'DocusignAPI')) {

                $dir = $this->getSignatureDocFileDir();
                $file_name = $this->getSignatureDocFileName('propal');
                $file = $dir . $file_name;

                $params = array(
                    'file'   => $file,
                    'client' => array(
                        'nom'      => $nom_client,
                        'prenom'   => $prenom_client,
                        'fonction' => $fonction_client,
                        'email'    => $email_client
                    )
                );
                $envelope = $api->createEnvelope($params, $this, $errors, $warnings);

                if (!count($errors)) {
                    BimpObject::loadClass('bimpcore', 'BimpSignature');

                    $signature = BimpObject::createBimpObject('bimpcore', 'BimpSignature', array(
                                'obj_module'            => 'bimpcommercial',
                                'obj_name'              => 'Bimp_Propal',
                                'id_obj'                => $this->id,
                                'doc_type'              => 'propal',
                                'id_client'             => $this->getData('fk_soc'),
                                'id_contact'            => $id_contact,
                                'dist_type'             => BimpSignature::DIST_DOCUSIGN,
                                'type'                  => BimpSignature::TYPE_ELEC,
                                'nom_signataire'        => $prenom_client . ' ' . $nom_client,
                                'fonction_signataire'   => $fonction_client,
                                'email_signataire'      => $email_client,
                                'id_envelope_docu_sign' => $envelope['envelopeId'],
                                'id_account_docu_sign'  => $comm->getData('id_docusign')
                                    ), true, $errors);

                    if (!count($errors) && BimpObject::objectLoaded($signature)) {
                        $errors = $this->updateField('id_signature', (int) $signature->id);
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
            return array('(432) ID ' . $this->getLabel('of_the') . ' absent');
        }

        if (!$this->fetch($this->id)) {
            return array(BimpTools::ucfirst($this->getLabel('this')) . ' est invalide. Copie impossible');
        }

        $date_diff = 0;
        if (isset($new_data['date_livraison']) && $new_data['date_livraison'] !== $this->getData('date_livraison')) {
            $date_diff = (int) BimpTools::getDateTms($new_data['date_livraison']) - (int) BimpTools::getDateTms($this->getData('date_livraison'));
        }

        $now = date('Y-m-d H:i:s');
        $new_data['datep'] = $now;
        $new_data['datec'] = $now;
        $fin_validite = BimpTools::getDateTms($now) + ($this->dol_object->duree_validite * 24 * 3600);
        $new_data['fin_validite'] = BimpTools::getDateFromTimestamp($fin_validite);
        $new_data['id_signature'] = 0;
        $new_data['signature_params'] = array();

        $errors = parent::duplicate($new_data, $warnings, $force_create);

        if ($date_diff) {
            $lines = $this->getChildrenObjects('lines');
            foreach ($lines as $line) {
                $update = false;
                if (isset($line->date_from) && (string) $line->date_from) {
                    $new_date_from = (BimpTools::getDateTms($line->date_from) + $date_diff);
                    $line->date_from = BimpTools::getDateFromTimestamp($new_date_from);
                    $update = true;
                }

                if (isset($line->date_to) && (string) $line->date_to) {
                    $new_date_to = (BimpTools::getDateTms($line->date_to) + $date_diff);
                    $line->date_to = BimpTools::getDateFromTimestamp($new_date_to);
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
        $id_contact_signature = 0;

        if ($use_signature) {
            if (!(int) BimpTools::getArrayValueFromPath($data, 'create_signature', 0)) {
                $use_signature = false;
            } else {
                if (BimpTools::getArrayValueFromPath($data, 'sign_dist', 0)) {
                    $id_contact_signature = (int) BimpTools::getArrayValueFromPath($data, 'id_contact_signature', 0);
                    $init_docusign = (int) BimpTools::getArrayValueFromPath($data, 'init_docusign', 0);
                    $open_public_access = (int) BimpTools::getArrayValueFromPath($data, 'open_public_access', 0);
                    $email_content = BimpTools::getArrayValueFromPath($data, 'email_content', '');

                    if ($init_docusign || $open_public_access) {
                        if (!$id_contact_signature) {
                            return array(
                                'errors'   => array('Contact signataire obligatoire pour la signature à distance'),
                                'warnings' => array()
                            );
                        }
                    } else {
                        return array(
                            'errors'   => array('Merci de choisir une méthode de signatre à distance'),
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
        }

        $result = parent::actionValidate($data, $success);

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
            if ($this->dol_object->closeProposal($user, (int) $data['new_status'], $note) <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la cloture de la proposition commerciale');
            } else {
                if ((int) $this->getData('id_signature')) {
                    $signature = $this->getChildObject('signature');

                    if (BimpObject::objectLoaded($signature)) {
                        $signature_errors = $signature->cancelAllSignatures();

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
            if ($this->dol_object->closeProposal($user, 4, '') < 0) {
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
    
    public function isFieldContratEditable(){
        if(BimpTools::getPostFieldValue('field_name') == 'duree_mois'){
            $fields = BimpTools::getPostFieldValue('fields');
            if($fields['objet_contrat'] == 'ASMX')
                return 1;
            return 0;
        }
    }

    public function actionCreateContrat($data, &$success = '')
    {
        $warnings = [];
        $errors = [];
        $instance = $this->getInstance('bimpcontract', 'BContract_contrat');
        $autre_erreurs = true;
        $id_new_contrat = 0;

        if ($data['objet_contrat'] != 'CDP') {
            $arrayServiceDelegation = Array('SERV19-DP1', 'SERV19-DP2', 'SERV19-DP3');
            foreach ($this->dol_object->lines as $line) {
                if ($line->fk_product) {
                    $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $line->fk_product);
                    if (in_array($product->getRef(), $arrayServiceDelegation)) {
                        $errors[] = 'Vous ne pouvez pas mettre le code service ' . $product->getRef() . ' dans un autre contrat que dans un contrat de délégation.';
                    }
                }
            }
        }

        if (!count($errors)) {
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
                        $cancel_errors = $signature->cancelAllSignatures();

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
        }

        return [
            'success_callback' => $callback,
            'warnings'         => $warnings,
            'errors'           => $errors
        ];
    }

    public function actionCreateContratAbo($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Contrat créé avec succès';
        $sc = '';

        $id_contrat = (int) BimpTools::getArrayValueFromPath($data, 'id_contrat', 0);
        if ($id_contrat) {
            $contrat = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_Contrat', $id_contrat);
            if (!BimpObject::objectLoaded($contrat)) {
                $errors[] = 'Le contrat #' . $id_contrat . ' n\'existe plus';
            }
        } else {
            $contrat = BimpObject::createBimpObject('bimpcontrat', 'BCT_Contrat', array(
                        'fk_soc'   => (int) $this->getData('fk_soc'),
                        'entrepot' => (int) $this->getData('entrepot'),
                        'secteur'  => $this->getData('ef_type'),
                        'moderegl' => BimpTools::getArrayValueFromPath($data, 'fk_mode_reglement', $this->getData('fk_mode_reglement')),
                        'condregl' => BimpTools::getArrayValueFromPath($data, 'fk_cond_reglement', $this->getData('fk_cond_reglement'))
                            ), true, $errors, $warnings);

            if (!count($errors)) {
                $success = 'Contrat ' . $contrat->getRef() . ' créé avec succès';
            }
        }

        if (!count($errors)) {
            addElementElement('propal', 'bimp_contrat', $this->id, $contrat->id);
            $nOk = 0;
            BimpObject::loadClass('bimpcontrat', 'BCT_ContratLine');
            $lines = $this->getAbonnementLines();

            foreach ($lines as $line) {
                $line_errors = array();
                $line_warnings = array();

                $prod = $line->getProduct();

                if (!BimpObject::objectLoaded($prod)) {
                    $line_errors[] = 'Produit absent';
                }

                if (!count($line_errors)) {
                    BimpObject::createBimpObject('bimpcontrat', 'BCT_ContratLine', array(
                        'fk_contrat'                   => $contrat->id,
                        'fk_product'                   => $line->id_product,
                        'line_type'                    => BCT_ContratLine::TYPE_ABO,
                        'description'                  => $line->desc,
                        'product_type'                 => $line->product_type,
                        'qty'                          => $line->qty,
                        'price_ht'                     => $line->pu_ht,
                        'tva_tx'                       => $line->tva_tx,
                        'remise_percent'               => $line->remise,
                        'fk_product_fournisseur_price' => $line->id_fourn_price,
                        'buy_price_ht'                 => $line->pa_ht,
                        'fac_periodicity'              => $line->getData('abo_fac_periodicity'),
                        'duration'                     => $line->getData('abo_duration'),
                        'fac_term'                     => $line->getData('abo_fac_term'),
                        'nb_renouv'                    => $line->getData('abo_nb_renouv'),
                        'achat_periodicity'            => $prod->getData('achat_def_periodicity'),
                        'variable_qty'                 => $prod->getData('variable_qty')
                            ), true, $line_errors, $line_warnings);
                }


                if (count($line_warnings)) {
                    $warnings[] = BimpTools::getMsgFromArray($line_warnings, 'Erreur lors l\'ajout de la ligne n° ' . $line->getData('position') . ' au contrat d\'abonnement');
                }

                if (count($line_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de l\'ajout de la ligne n° ' . $line->getData('position') . ' au contrat d\'abonnement');
                } else {
                    $nOk++;
                }
            }
        }

        if (!count($errors)) {
            if ($nOk) {
                $success .= ($success ? '<br/>' : '') . $nOk . ' ligne(s) ajoutée(s) au contrat ' . $contrat->getRef();
            } else {
                $warnings[] = 'Aucun ligne ajoutée au contrat ' . $contrat->getRef();
            }

            $url = $contrat->getUrl();

            if ($url) {
                $sc = 'window.open(\'' . $url . '\');';
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $sc
        );
    }

    public function actionCreateSignature($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Signature créée avec succès';
        $url = '';

        if (BimpTools::getArrayValueFromPath($data, 'sign_dist', 0)) {
            $id_contact_signature = (int) BimpTools::getArrayValueFromPath($data, 'id_contact_signature', 0);
            $init_docusign = (int) BimpTools::getArrayValueFromPath($data, 'init_docusign', 0);
            $open_public_access = (int) BimpTools::getArrayValueFromPath($data, 'open_public_access', 0);
            $email_content = BimpTools::getArrayValueFromPath($data, 'email_content', '');

            if ($init_docusign || $open_public_access) {
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

        $errors = $this->createSignature($init_docusign, $open_public_access, $id_contact_signature, $email_content, $warnings);

        if (!count($errors)) {
            $signature = $this->getChildObject('signature');

            if (BimpObject::objectLoaded($signature)) {
                $url = $signature->getUrl();
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => ($url ? 'window.open(\'' . $url . '\');' : '')
        );
    }

    public function actionCreateSignatureDocusign($data, &$success)
    {
        $errors = array();
        $warnings = array();

        $success_callback = '';

        $id_contact = BimpTools::getArrayValueFromPath($data, 'id_contact', '');
        if (!$id_contact) {
            $errors[] = 'Veuillez renseigner un contact';
        }

        $errors_signature = $this->createSignatureDocusign($id_contact);
        $errors = BimpTools::merge_array($errors, $errors_signature);

        if (!count($errors)) {
            $signature = $this->getChildObject('signature');
            $success = "Enveloppe envoyée avec succès<br/>";
            $success .= $signature->getNomUrl() . ' créée avec succès';
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
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
        if ($this->dol_object->insertExtraFields('', $user) < 0) {
            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la mise à jour des champs supplémentaires');
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
            $this->updateField('fin_validite', BimpTools::getDateTms($this->getData('datep')) + ($this->dol_object->duree_validite * 24 * 3600));
        }


        if ((string) $this->getData('datep')) {
            $date = BimpTools::getDateTms($this->getData('datep'));
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
            $date = BimpTools::getDateTms($this->getData('fin_validite'));
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
            $date = BimpTools::getDateTms($this->getData('date_livraison'));
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

    public function getSignatureDocFileDir($doc_type = '')
    {
        return $this->getFilesDir();
    }

    public function getSignatureDocFileName($doc_type = 'devis', $signed = false, $file_idx = 0)
    {
        $ext = $this->getSignatureDocFileExt($doc_type, $signed);

        switch ($doc_type) {
            case 'devis':
                return dol_sanitizeFileName($this->getRef()) . ($signed ? '_signe' : ($file_idx ? '-' . $file_idx : '')) . '.' . $ext;
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
                return self::getPublicBaseUrl() . 'fc=doc&doc=' . $doc_type . ($signed ? '_signed' : '') . '&docid=' . $this->id . '&docref=' . urlencode($this->getRef()) . ($file_idx ? '&file_idx=' . $file_idx : '');
            } else {
                return $this->getFileUrl($fileName);
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

    public function getSignatureEmailContent($doc_type = 'devis', $signature_type = '')
    {
        if (!$signature_type) {
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

        if ($signature_type) {
            if ($doc_type === 'devis') {
                $message = 'Bonjour, <br/><br/>';
                $message .= 'La signature du document "{NOM_DOCUMENT}" est en attente.<br/><br/>';

                switch ($signature_type) {
                    case 'docusign':
                        $message .= 'Merci de bien vouloir effectuer la signature électronique de ce document en suivant les instructions DocuSign.<br/><br/>';
                        break;

                    case 'elec':
                    default:
                        $message .= 'Vous pouvez effectuer la signature électronique de ce document directement depuis votre {LIEN_ESPACE_CLIENT} ou nous retourner le document ci-joint signé.<br/><br/>';
                        break;
                }

                $message .= 'Vous en remerciant par avance, nous restons à votre disposition pour tout complément d\'information.<br/><br/>';
                $message .= 'Cordialement';

                $signature = BimpCore::getConf('signature_emails_client');
                if ($signature) {
                    $message .= ', <br/><br/>' . $signature;
                }

                return $message;
            }
            BimpObject::loadClass('bimpcore', 'BimpSignature');
            BimpSignature::getDefaultSignDistEmailContent($signature_type);
        }

        return '';
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

    public function getSignatureContactCreateFormValues()
    {
        $client = $this->getChildObject('client');

        if (BimpObject::objectLoaded($client)) {
            $fields = array(
                'fk_soc' => $client->id,
                'email'  => $client->getData('email')
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

    public function getOnSignedNotificationEmail($doc_type, &$use_as_from = false)
    {
        $sav = $this->getSav();
        if (BimpObject::objectLoaded($sav)) {
            return $sav->getOnSignedNotificationEmail($doc_type, $use_as_from);
        }

        $use_as_from = false;
        $email = '';
        $commercial = $this->getCommercial();

        if (BimpObject::objectLoaded($commercial)) {
            $email = $commercial->getData('email');
        }

        return $email;
    }

    public function getOnSignedEmailExtraInfos($doc_type)
    {
        $sav = $this->getSav();

        if (BimpObject::objectLoaded($sav)) {
            return $sav->getOnSignedEmailExtraInfos('devis_sav');
        }

        return '';
    }

    public function onSigned($bimpSignature)
    {
        $errors = array();

        if ($this->isLoaded($errors)) {

            BimpObject::loadClass('bimpcore', 'BimpSignature');

            $sav = $this->getSav();

            if (BimpObject::objectLoaded($sav)) {
                $errors = $sav->onPropalSigned($bimpSignature);
            } else {
                $errors = $this->updateField('fk_statut', Propal::STATUS_SIGNED);
            }
        }

        return $errors;
    }

    public function onSignatureRefused($bimpSignature)
    {
        if ((int) $this->getData('fk_statut') === Propal::STATUS_VALIDATED) {
            $errors = $this->updateField('fk_statut', Propal::STATUS_NOTSIGNED);
        }

        return $errors;
    }

    public function onSignatureReopen($bimpSignature)
    {
        if ((int) $this->getData('fk_statut') === Propal::STATUS_NOTSIGNED) {
            $errors = $this->updateField('fk_statut', Propal::STATUS_VALIDATED);
        }

        return $errors;
    }

    public function isSignatureCancellable($doc_type, &$errors = array())
    {
        $sav = $this->getSav();
        if (!BimpObject::objectLoaded($sav)) {
            return 1;
        }

        return 1;
    }

    public function isSignatureReopenable($doc_type, &$errors = array())
    {
        return 1;
    }

    public function displaySignatureDocExtraInfos($doc_type)
    {
        $html = '';
        $errors = array();

        if ($this->isLoaded($errors)) {
            if ($doc_type == 'devis') {
                $lines = $this->getLines();

                if (count($lines)) {
                    $html .= '<table class="bimp_list_table">';
                    $html .= '<thead>';
                    $html .= '<tr>';
                    $html .= '<th>Désignation</th>';
                    $html .= '<th>Qté</th>';
                    $html .= '<th>P.U. HT</th>';
                    $html .= '<th>Tx. TVA</th>';
                    $html .= '<th>Remise</th>';
                    $html .= '<th>Total TTC</th>';
                    $html .= '</tr>';
                    $html .= '</thead>';

                    $html .= '<tbody>';

                    foreach ($lines as $line) {
                        $html .= '<tr>';

                        if ((int) $line->getData('type') === ObjectLine::LINE_TEXT) {
                            $html .= '<td colspan="99">' . $line->displayLineData('desc', 0, 'default', true) . '</td>';
                        } else {
                            $html .= '<td>' . $line->displayLineData('desc_light', 0, 'default', true) . '</td>';
                            $html .= '<td>' . $line->displayLineData('qty', 0, 'default', true) . '</td>';
                            $html .= '<td>' . $line->displayLineData('pu_ht', 0, 'default', true) . '</td>';
                            $html .= '<td>' . $line->displayLineData('tva_tx', 0, 'default', true) . '</td>';
                            $html .= '<td>' . $line->displayLineData('remise', 0, 'default', true) . '</td>';
                            $html .= '<td>' . $line->displayLineData('total_ttc', 0, 'default', true) . '</td>';
                        }

                        $html .= '</tr>';
                    }

                    $html .= '</tbody>';
                    $html .= '</table>';
                }

                $html .= '<div style="margin-top: 15px; text-align: right; font-weight: bold; font-size: 14px">';
                $html .= 'Total TTC : ' . BimpTools::displayMoneyValue($this->getTotalTtc());
                $html .= '</div>';
            }
        }

        if (count($errors)) {
            $html .= BimpRender::renderAlerts($errors);
        }

        return $html;
    }
}
