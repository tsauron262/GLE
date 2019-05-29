<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/BimpComm.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.product.class.php';

class Bimp_FactureFourn extends BimpComm
{

    public static $dol_module = 'facture_fourn';
    public static $files_module_part = 'facture_fournisseur';
    public static $email_type = 'invoice_supplier_send'; // A checker
    public static $status_list = array(
        0 => array('label' => 'Brouillon', 'icon' => 'fas_file-alt', 'classes' => array('warning')),
        1 => array('label' => 'Validée', 'icon' => 'check', 'classes' => array('info')),
        2 => array('label' => 'Fermée', 'icon' => 'check', 'classes' => array('success')),
        3 => array('label' => 'Abandonnée', 'icon' => 'times-circle', 'classes' => array('danger')),
    );
    public static $types = array(
        0 => array('label' => 'Facture standard'),
        1 => array('label' => 'Facture de remplacement'),
        2 => array('label' => 'Avoir'),
        3 => array('label' => 'Facture d\'acompte')
    );

    // Gestion des autorisations objet: 

    public function isDeletable($force_delete = false)
    {
        if ($this->isLoaded()) {
            if ((int) $this->getData('fk_statut') === 0) {
                return 1;
            }

            return 0;
        }

        return 1;
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        return parent::isFieldEditable($field, $force_edit);
    }

    public function isActionAllowed($action, &$errors = array())
    {
        $errors = array();
        $status = $this->getData('fk_statut');

        if (in_array($action, array('validate', 'modify', 'reopen', 'sendEMail', 'makePayment', 'classifyPaid', 'duplicate', 'create_credit_note'))) {
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
        $type = (int) $this->getData('type');

        global $conf;

        switch ($action) {
            case 'validate':
                if ($status !== FactureFournisseur::STATUS_DRAFT) {
                    $errors[] = 'Statut actuel invalide';
                }
                if (!count($this->dol_object->lines)) {
                    $errors[] = 'Aucune ligne enregistrée pour ' . $this->getLabel('this');
                }
                break;

            case 'modify':
                if ($status !== FactureFournisseur::STATUS_VALIDATED) {
                    $errors[] = 'Statut actuel invalide';
                }
                if ($this->dol_object->getSommePaiement() != 0) {
                    $errors[] = 'Un paiement a déjà été effectué';
                }
                break;

            case 'reopen':
                if (!in_array($type, array(FactureFournisseur::TYPE_STANDARD, FactureFournisseur::TYPE_REPLACEMENT))) {
                    $errors[] = 'Type invalide';
                }
                if (!in_array($status, array(2, 3))) {
                    $errors[] = 'Statut actuel invalide';
                }
                if ((int) $this->dol_object->getIdReplacingInvoice() || $this->getData('close_code') === 'replaced') {
                    $errors[] = 'Cette facture a été remplacée par une autre';
                }

            case 'sendEMail':
                if (!in_array($status, array(FactureFournisseur::STATUS_VALIDATED, FactureFournisseur::STATUS_CLOSED))) {
                    $errors[] = 'Statut actuel invalide';
                }
                break;

            case 'makePayment':
                if ($type === FactureFournisseur::TYPE_CREDIT_NOTE) {
                    $errors[] = 'Paiements non autorisés pour les avoirs';
                }
                if ($status !== FactureFournisseur::STATUS_VALIDATED) {
                    $errors[] = 'La facture doit être au statut "Validée"';
                }
                if ((int) $this->getData('paye')) {
                    $errors[] = 'Cette facture est déjà payée';
                }
                break;


            case 'classifyPaid':

            case 'duplicate':

            case 'create_credit_note':

            default:
                return parent::isActionAllowed($action, $errors);
        }

        if (count($errors)) {
            return 0;
        }
        return 1;
    }

    // Gestion des droits - overrides BimpObject: 

    public function canCreate()
    {
        return 1;
    }

    protected function canEdit()
    {
        return $this->can("create");
    }

    public function canSetAction($action)
    {
        global $conf, $user;


        switch ($action) {
            case 'validate':
                if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->fournisseur->facture->creer)) ||
                        (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->fournisseur->supplier_invoice_advance->validate))) {
                    return 1;
                }
                return 0;

            case 'modify':
            case 'reopen':
                if ($user->rights->fournisseur->facture->creer) {
                    return 1;
                }
                return 0;

            case 'sendEMail':
                if (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->fournisseur->supplier_invoice_advance->send) {
                    return 1;
                }
                return 0;

            case 'makePayment':

            case 'classifyPaid':

            case 'duplicate':

            case 'create_credit_note':
        }

        return parent::canSetAction($action);
    }

    public function canEditField($field)
    {
        return (int) parent::canEditField($field);
    }

    // Getters - overrides BimpComm

    public function getActionsButtons()
    {
        global $conf, $langs, $user;

        $buttons = array();

        if ($this->isLoaded()) {
            // Valider: 
            if ($this->isActionAllowed('validate')) {
                if ($this->canSetAction('validate')) {
                    $buttons[] = array(
                        'label'   => 'Valider',
                        'icon'    => 'fas_check',
                        'onclick' => $this->getJsActionOnclick('validate', array(), array(
                            'confirm_msg' => 'Veuillez confirmer la validation ' . $this->getLabel('of_this')
                        ))
                    );
                } else {
                    $buttons[] = array(
                        'label'    => 'Valider',
                        'icon'     => 'fas_check',
                        'onclick'  => '',
                        'disabled' => 1,
                        'popover'  => 'Vous n\'avez pas la permission de valider ' . $this->getLabel('this')
                    );
                }
            }

            // Modifier (si aucun paiement): 
            if ($this->isActionAllowed('modify') && $this->canSetAction('modify')) {
                $buttons[] = array(
                    'label'   => 'Modifier',
                    'icon'    => 'fas_undo',
                    'onclick' => $this->getJsActionOnclick('modify', array(), array(
                        'confirm_msg' => 'Veuillez confirmer la remise au statut brouillon ' . $this->getLabel('of_this')
                    )),
                );
            }

            // Réouvrir une facture payée:
            if ($this->isActionAllowed('reopen')) {
                if ($this->canSetAction('reopen')) {
                    $buttons[] = array(
                        'label'   => 'Réouvrir',
                        'icon'    => 'fas_undo',
                        'onclick' => $this->getJsActionOnclick('reopen', array(), array(
                            'confirm_msg' => 'Veuillez confirmer la réouverture' . $this->getLabel('of_this')
                        ))
                    );
                } else {
                    $buttons[] = array(
                        'label'    => 'Réouvrir',
                        'icon'     => 'fas_undo',
                        'onclick'  => '',
                        'disabled' => 1,
                        'popover'  => 'Vous n\'avez pas la permission de réouvrir ' . $this->getLabel('this')
                    );
                }
            }

            // Envoyer par email: 
            if ($this->isActionAllowed('sendEMail') && $this->canSetAction('sendEMail')) {
                $onclick = $this->getJsActionOnclick('sendEmail', array(), array(
                    'form_name' => 'email'
                ));
                $buttons[] = array(
                    'label'   => 'Envoyer par email',
                    'icon'    => 'fas_envelope',
                    'onclick' => $onclick,
                );
            }

            // Réglement: 
            if ($this->isActionAllowed('makePayment') && $this->canSetAction('makePayment')) {
                $pf = BimpObject::getInstance('bimpcommercial', 'Bimp_PaiementFourn');
                $buttons[] = array(
                    'label'   => 'Saisir réglement',
                    'icon'    => 'fas_euro-sign',
                    'onclick' => $pf->getJsLoadModalForm('default', 'Paiement factures fournisseur', array(
                        'fields' => array(
                            'id_fourn' => (int) $this->getData('fk_soc')
                        )
                    )),
                );
            }

            // Classer payée: 
            if ($this->isActionAllowed('classifyPaid') && $this->canSetAction('classifyPaid')) {
                $buttons[] = array(
                    'label'   => 'Classer payée',
                    'icon'    => 'fas_check',
                    'onclick' => '',
                );
            }

            // Cloner: 
            if ($this->isActionAllowed('duplicate') && $this->canSetAction('duplicate')) {
                $buttons[] = array(
                    'label'   => 'Cloner',
                    'icon'    => 'fas_copy',
                    'onclick' => ''
//                    'onclick' => $this->getJsActionOnclick('duplicate', array(), array(
//                        'confirm_msg' => 'Etes-vous sûr de vouloir cloner ' . $this->getLabel('this')
////                        'form_name' => 'duplicate_propal'
//                    ))
                );
            }

            // Créer un avoir: 
            if ($this->isActionAllowed('create_credit_note') && $this->canSetAction('create_credit_note')) {
                $buttons[] = array(
                    'label'   => 'Créer un avoir',
                    'icon'    => 'far_file-alt',
                    'onclick' => ''
                );
            }
        }

        return $buttons;
    }

    public function getModelsPdfArray()
    {
        if (!class_exists('ModelePDFSuppliersInvoices')) {
            require_once DOL_DOCUMENT_ROOT . '/core/modules/supplier_invoice/modules_facturefournisseur.php';
        }

        return ModelePDFSuppliersInvoices::liste_modeles($this->db->db);
    }

    public function getFilesDir()
    {
        if ($this->isLoaded()) {
            return $this->getDirOutput() . '/';
        }

        return '';
    }

    public function getFileUrl($file_name)
    {
        $dir = $this->getFilesDir();
        if ($dir) {
            if (file_exists($dir . $file_name)) {
                $subdir = get_exdir($this->id, 2, 0, 0, $this->dol_object, 'invoice_supplier') . $this->getData('ref');
                return DOL_URL_ROOT . '/document.php?modulepart=' . static::$files_module_part . '&file=' . htmlentities($subdir . '/' . $file_name);
            }
        }

        return '';
    }

    public function getDirOutput()
    {
        if (!$this->isLoaded()) {
            return '';
        }

        global $conf;
        $ref = dol_sanitizeFileName($this->dol_object->ref);
        $subdir = get_exdir($this->id, 2, 0, 0, $this->dol_object, 'invoice_supplier') . $ref;
        return $conf->fournisseur->facture->dir_output . '/' . $subdir;
    }

    public function getTotalPaid()
    {
        $alreadypaid += $this->dol_object->getSommePaiement();
        $alreadypaid += $this->dol_object->getSumDepositsUsed();
        $alreadypaid += $this->dol_object->getSumCreditNotesUsed();
        return $alreadypaid;
    }

    public function getRemainToPay()
    {
        return (float) $this->getData('total_ttc') - (float) $this->getTotalPaid();
    }

    // Getters callbacks: 

    public function getCreateFromOriginCheckMsg()
    {
        if (BimpTools::getPostFieldValue('origin', '') === 'commande_fournisseur') {
            return array(array(
                    'content' => 'Attention: après la création de la facture, les quantités de la commande fournisseurs ne seront plus modifiables dans la logistique',
                    'type'    => 'warning'
            ));
        }
    }

    // Affichages: 

    public function displayPaid()
    {
        if ($this->isLoaded()) {
            $paid = $this->getTotalPaid();
            return BimpTools::displayMoneyValue($paid, 'EUR');
        }

        return '';
    }

    // Rendus HTML - overrides BimpObject:

    public function renderHeaderExtraLeft()
    {
        $html = '';

        if ($this->isLoaded()) {
            $user = new User($this->db->db);

            $html .= '<div class="object_header_infos">';
            $html .= 'Créée le <strong>' . $this->displayData('datec', 'default', false, true) . '</strong>';

            $user->fetch((int) $this->getData('fk_user_author'));
            $html .= ' par ' . $user->getNomUrl(1);
            $html .= '</div>';

            if ((int) $this->getData('fk_user_valid')) {
                $user->fetch((int) $this->getData('fk_user_valid'));
                $html .= '<div class="object_header_infos">';
                $html .= 'Validée par ' . $user->getNomUrl(1);
                $html .= '</div>';
            }
        }

        return $html;
    }

    public function renderHeaderExtraRight()
    {
        $html = '';

//        $html .= '<div class="buttonsContainer">';
//
//        $pdf_dir = $this->getDirOutput();
//        $ref = dol_sanitizeFileName($this->getRef());
//        $pdf_file = $pdf_dir . '/' . $ref . '/' . $ref . '.pdf';
//        if (file_exists($pdf_file)) {
//            $url = DOL_URL_ROOT . '/document.php?modulepart=' . static::$dol_module . '&file=' . htmlentities($ref . '/' . $ref . '.pdf');
//            $onclick = 'window.open(\'' . $url . '\');';
//
//            $html .= BimpRender::renderButton(array(
//                        'classes'     => array('btn', 'btn-default'),
//                        'label'       => $ref . '.pdf',
//                        'icon_before' => 'fas_file-pdf',
//                        'attr'        => array(
//                            'onclick' => $onclick
//                        )
//            ));
//        }
//
//        $html .= "<a class='btn btn-default' href='../comm/propal/card.php?id=" . $this->id . "'><i class='fa fa-file iconLeft'></i>Ancienne version</a>";
//
//        $html .= '</div>';

        return $html;
    }

    // Traitements: 

    public function onCreate()
    {
        if (!$this->isLoaded()) {
            return;
        }

        foreach (BimpTools::getDolObjectLinkedObjectsList($this->dol_object, $this->db) as $item) {
            if ($item['type'] === 'order_supplier') {
                $commande_fourn = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', (int) $item['id_object']);
                if (BimpObject::objectLoaded($commande_fourn)) {
                    $commande_fourn->checkInvoiceStatus();
                }
            }
        }
    }

    // Actions: 

    public function actionValidate($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Facture fournisseur validée avec succès';

        if (!(int) $this->getData('entrepot')) {
            $errors[] = 'Entrepôt absent. Veuillez sélectionner un entrepôt avant de valider';
        } else {
            BimpTools::resetDolObjectErrors($this->dol_object);
            global $user, $conf, $langs;

            if ($this->dol_object->validate($user, '', (int) $this->getData('entrepot')) < 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object, null, null, $warnings), 'Des erreurs sont survenues lors de la validation ' . $this->getLabel('of_the'));
            } else {
                if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
                    $this->fetch($this->id);
                    $this->dol_object->generateDocument($this->getModelPdf(), $langs);
                }
            }
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
        $success = 'Réouverture effectuée avec succès';

        global $user;
        BimpTools::resetDolObjectErrors($this->dol_object);
        if ($this->dol_object->set_unpaid($user) <= 0) {
            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object, null, null, $warnings), 'Echec de la réouverture');
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrides - BimpComm: 

    public function getDbData($fields)
    {
        $final_fields = array();

        foreach ($fields as $field) {
            if ($field === 'fk_user_valid') {
                continue;
            }

            $final_fields[] = $field;
        }
        return parent::getDbData($final_fields);
    }

    public function createLinesFromOrigin($origin)
    {
        $errors = array();

        if (!BimpObject::objectLoaded($origin) || !is_a($origin, 'BimpComm')) {
            return array('Element d\'origine absent ou invalide');
        }

        if (!is_a($origin, 'Bimp_CommandeFourn')) {
            return parent::createLinesFromOrigin($origin);
        }

        $lines = $origin->getChildrenObjects('lines', array(), 'position', 'asc');

        $warnings = array();
        $i = 0;

        // Création des lignes: 
        $lines_new = array();

        foreach ($lines as $line) {

            $lines_data = $line->getLinesDataByUnitPriceAndTva();

            $isSerialisable = $line->isProductSerialisable();

            foreach ($lines_data as $pu_ht => $pu_data) {
                foreach ($pu_data as $tva_tx => $line_data) {
                    $i++;
                    if ($isSerialisable) {
                        $qty = count($line_data['equipments']);
                    } else {
                        $qty = (float) $line_data;
                    }
                    $pu_ht = (float) $pu_ht;
                    $tva_tx = (float) $tva_tx;

                    $line_instance = BimpObject::getInstance($this->module, $this->object_name . 'Line');
                    $line_instance->validateArray(array(
                        'id_obj'    => (int) $this->id,
                        'type'      => $line->getData('type'),
                        'deletable' => 1,
                        'editable'  => 1,
                        'remisable' => $line->getData('remisable')
                    ));

                    $line_instance->desc = $line->desc;
                    $line_instance->tva_tx = $tva_tx;
                    $line_instance->id_product = $line->id_product;
                    $line_instance->qty = $qty;
                    $line_instance->pu_ht = $pu_ht;
                    $line_instance->pa_ht = $line->pa_ht;
                    $line_instance->id_fourn_price = $line->id_fourn_price;
                    $line_instance->date_from = $line->date_from;
                    $line_instance->date_to = $line->date_to;
                    $line_instance->id_remise_except = $line->id_remise_except;

                    $line_errors = $line_instance->create($warnings, true);
                    if (count($line_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de la création de la ligne n°' . $i);
                        continue;
                    }

                    $lines_new[(int) $line->id] = (int) $line_instance->id;

                    // NOTE: on intègre pas les remises de la ligne de commande: celles-ci sont déjà déduites dans le pu_ht.
                     
                    // Création des remises pour la ligne en cours:
//                    $remises = $line->getRemises();
//                    if (!is_null($remises) && count($remises)) {
//                        $j = 0;
//                        foreach ($remises as $r) {
//                            $j++;
//                            $remise = BimpObject::getInstance('bimpcommercial', 'ObjectLineRemise');
//                            $remise->validateArray(array(
//                                'id_object_line' => (int) $line_instance->id,
//                                'object_type'    => $line_instance::$parent_comm_type,
//                                'label'          => $r->getData('label'),
//                                'type'           => (int) $r->getData('type'),
//                                'percent'        => (float) $r->getData('percent'),
//                                'montant'        => (float) $r->getData('montant'),
//                                'per_unit'       => (int) $r->getData('per_unit')
//                            ));
//                            $remise_errors = $remise->create($warnings, true);
//                            if (count($remise_errors)) {
//                                $errors[] = BimpTools::getMsgFromArray($remise_errors, 'Echec de la création de la remise n°' . $j . ' pour la ligne n°' . $i);
//                            }
//                        }
//                    }

                    // Ajout des équipements: 
                    if ($isSerialisable) {
                        foreach ($line_data['equipments'] as $id_equipment) {
                            $eq_errors = $line_instance->attributeEquipment((int) $id_equipment);
                            if (count($eq_errors)) {
                                $equipment = BimpCache::getBimpObjectFullListArray('bimpequipment', 'Equipment', (int) $id_equipment);
                                if (BimpObject::objectLoaded($equipment)) {
                                    $eq_label = '"' . $equipment->getData('serial') . '"';
                                } else {
                                    $eq_label = 'd\'ID ' . $id_equipment;
                                }
                                $errors[] = BimpTools::getMsgFromArray($eq_errors, 'Ligne n°' . $i . ': échec de l\'attribution de l\'équipement ' . $eq_label);
                            }
                        }
                    }
                }
            }
        }

        // Attribution des lignes parentes: 
        foreach ($lines as $line) {
            $id_parent_line = (int) $line->getData('id_parent_line');

            if ($id_parent_line) {
                if (isset($lines_new[(int) $line->id]) && (int) $lines_new[(int) $line->id] && isset($lines_new[$id_parent_line]) && (int) $lines_new[$id_parent_line]) {
                    $line_instance = BimpCache::getBimpObjectInstance($this->module, $this->object_name . 'Line', (int) $lines_new[(int) $line->id]);
                    if (BimpObject::objectLoaded($line_instance)) {
                        $line_instance->updateField('id_parent_line', (int) $lines_new[(int) $id_parent_line]);
                    }
                }
            }
        }
        return $errors;
    }

    protected function updateDolObject(&$errors)
    {
        return parent::updateDolObject($errors);
    }
}
