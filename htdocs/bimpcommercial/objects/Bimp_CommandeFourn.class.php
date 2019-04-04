<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/BimpComm.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.product.class.php';

class Bimp_CommandeFourn extends BimpComm
{

    public static $dol_module = 'commande_fournisseur';
    public static $email_type = 'order_supplier_send';
    public static $external_contact_type_required = false;
    public static $internal_contact_type_required = false;
    public static $status_list = array(
        0 => array('label' => 'Brouillon', 'icon' => 'fas_file-alt', 'classes' => array('warning')),
        1 => array('label' => 'Validée', 'icon' => 'fas_check', 'classes' => array('info')),
        2 => array('label' => 'Approuvée', 'icon' => 'fas_check-circle', 'classes' => array('info')),
        3 => array('label' => 'En attente de réception', 'icon' => 'fas_shipping-fast', 'classes' => array('important')),
        4 => array('label' => 'Reçue partiellement', 'icon' => 'fas_sign-in-alt', 'classes' => array('important')),
        5 => array('label' => 'Reçue entièrement', 'icon' => 'fas_arrow-alt-circle-down', 'classes' => array('success')),
        6 => array('label' => 'Annulée', 'icon' => 'fas_times', 'classes' => array('danger')),
        7 => array('label' => 'Annulée après commande', 'icon' => 'fas_times', 'classes' => array('danger')),
        9 => array('label' => 'Refusée', 'icon' => 'fas_times', 'classes' => array('danger'))
    );
    public static $cancel_status = array(6, 7, 9);
    public static $livraison_types = array(
        ''    => '',
        'tot' => array('label' => 'Complète', 'classes' => array('success')),
        'par' => array('label' => 'Partielle', 'classes' => array('warning')),
        'nev' => array('label' => 'Jamais reçue', 'classes' => array('danger')),
        'can' => array('label' => 'Annulée', 'classes' => array('danger')),
    );
    
    // Gestion des autorisations objet: 

    public function isFieldEditable($field)
    {
        if (in_array($field, array('date_commande', 'fk_input_method'))) {
            return (int) $this->isActionAllowed('make_order');
        }

        return parent::isFieldEditable($field);
    }

    public function isActionAllowed($action, &$errors = array())
    {
        $status = $this->getData('fk_statut');
        if (in_array($action, array('validate', 'approve', 'approve2', 'refuse', 'modify', 'sendEmail', 'reopen', 'make_order', 'receive', 'receive_products', 'createInvoice', 'classifyBilled'))) {
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

        global $conf;

        switch ($action) {
            case 'validate':
                if ($status !== CommandeFournisseur::STATUS_DRAFT) {
                    $errors[] = 'Statut actuel ' . $this->getLabel('of_the') . ' invalide';
                    return 0;
                }
                if (!count($this->dol_object->lines)) {
                    $errors[] = 'Aucune ligne enregistrée pour ' . $this->getLabel('this');
                    return 0;
                }
                return 1;

            case 'approve':
                if ($status !== CommandeFournisseur::STATUS_VALIDATED) {
                    $errors[] = 'Statut actuel ' . $this->getLabel('of_the') . ' invalide';
                    return 0;
                }
                if ((int) $this->getData('fk_user_approve')) {
                    $errors[] = BimpTools::ucfirst($this->getData('this')) . ' a déjà été approuvé' . ($this->isLabelFemale() ? 'e' : '');
                }
                return 1;

            case 'approve2':
                if ($status !== CommandeFournisseur::STATUS_VALIDATED) {
                    $errors[] = 'Statut actuel ' . $this->getLabel('of_the') . ' invalide';
                    return 0;
                }
                if (empty($conf->global->SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED) || $conf->global->MAIN_FEATURES_LEVEL < 1 || $this->getData('total_ht') <= $conf->global->SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED){
                    $errors[] = '2ème approbation non nécessaire';
                    return 0;
                }
                if ((float) $this->getTotalHt() < $conf->global->SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED) {
                    $errors[] = BimpTools::ucfirst($this->getData('this')) . ' ne dépasse pas le montant minimal pour nécessiter une deuxième approbation';
                    return 0;
                }
                return 1;

            case 'refuse':
                if ($status !== CommandeFournisseur::STATUS_VALIDATED) {
                    $errors[] = 'Statut actuel ' . $this->getLabel('of_the') . ' invalide';
                    return 0;
                }
                return 1;

            case 'sendEmail':
                if (!in_array($status, array(2, 3, 4, 5))) {
                    $errors[] = 'Statut actuel ' . $this->getLabel('of_the') . ' invalide';
                    return 0;
                }
                return 1;

            case 'reopen':
                if (!in_array($status, array(1, 2, 3, 4, 5, 6, 7, 9))) {
                    $errors[] = 'Statut actuel ' . $this->getLabel('of_the') . ' invalide';
                    return 0;
                }
                return 1;

            case 'makeOrder':
                if ($status !== CommandeFournisseur::STATUS_ACCEPTED) {
                    $errors[] = 'Statut actuel ' . $this->getLabel('of_the') . ' invalide';
                    return 0;
                }
                return 1;

            case 'receive':
                if (!in_array($status, array(3, 4))) {
                    $errors[] = 'Statut actuel ' . $this->getLabel('of_the') . ' invalide';
                    return 0;
                }
                return 1;

            case 'receive_products':
                if (empty($conf->stock->enabled) || empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER) || !$conf->fournisseur->enabled) {
                    $errors[] = 'Réception de commande non activée';
                    return 0;
                }
                if (!in_array($status, array(3, 4, 5))) {
                    $errors[] = 'Statut actuel ' . $this->getLabel('of_the') . ' invalide';
                    return 0;
                }
                return 1;

            case 'createInvoice':
                if (empty($conf->facture->enabled)) {
                    $errors[] = 'Factures désactivées';
                    return 0;
                }
                if ((int) $this->getData('billed')) {
                    $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' a déjà été facturée';
                    return 0;
                }
                if (!in_array($status, array(2, 3, 4, 5))) {
                    $errors[] = 'Statut actuel ' . $this->getLabel('of_the') . ' invalide';
                    return 0;
                }
                return 1;

            case 'classifyBilled':
                if (!empty($conf->facture->enabled) && empty($this->dol_object->linkedObjectsIds['invoice_supplier'])) {
                    return 0;
                }
                return 1;

            case 'cancel':
                if ($status !== 2) {
                    $errors[] = 'Statut actuel ' . $this->getLabel('of_the') . ' invalide';
                    return 0;
                }
                return 1;
        }
        return parent::isActionAllowed($action);
    }

    // Gestion des droits user - overrides BimpObject: 

    public function canCreate()
    {
        global $user;
        return (int) $user->rights->fournisseur->commande->creer;
    }

    protected function canEdit()
    {
        return $this->can("create");
    }

    public function canDelete()
    {
        global $user;
        return (int) $user->rights->fournisseur->commande->supprimer;
    }

    public function canSetAction($action)
    {
        global $conf, $user;

        switch ($action) {
            case 'validate':
                if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->fournisseur->commande->creer)) ||
                        (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->fournisseur->supplier_order_advance->validate))) {
                    return 1;
                }
                return 0;

            case 'approve':
                if (!empty($user->rights->fournisseur->commande->approuver)) {
                    return 1;
                }
                return 0;

            case 'approve2':
                if (!empty($user->rights->fournisseur->commande->approve2)) {
                    return 1;
                }
                return 0;

            case 'refuse':
                if (!empty($user->rights->fournisseur->commande->approuver) || !empty($user->rights->fournisseur->commande->approve2)) {
                    return 1;
                }
                return 0;

            case 'sendEmail':
                if (!empty($user->rights->fournisseur->commande->commander)) {
                    return 1;
                }
                return 0;

            case 'reopen':
                if ((int) $this->getData('fk_statut') === 1) {
                    if (!empty($user->rights->fournisseur->commande->commander)) {
                        return 1;
                    }
                } elseif ((int) $this->getData('fk_statut') === 2) {
                    if (!empty($user->rights->fournisseur->commande->approuver)) {
                        if (empty($conf->global->SUPPLIER_ORDER_REOPEN_BY_APPROVER_ONLY) ||
                                (!empty($conf->global->SUPPLIER_ORDER_REOPEN_BY_APPROVER_ONLY) && (int) $user->id === (int) $this->getData('fk_user_approve'))) {
                            return 1;
                        }
                    }
                    if (!empty($user->rights->fournisseur->commande->approve2)) {
                        if (empty($conf->global->SUPPLIER_ORDER_REOPEN_BY_APPROVER2_ONLY) ||
                                (!empty($conf->global->SUPPLIER_ORDER_REOPEN_BY_APPROVER2_ONLY) && (int) $user->id === (int) $this->getData('fk_user_approve2'))) {
                            return 1;
                        }
                    }
                } elseif (in_array((int) $this->getData('fk_statut'), array(3, 4, 5, 6, 7, 9))) {
                    if ($user->rights->fournisseur->commande->commander) {
                        return 1;
                    }
                }

                return 0;

            case 'makeOrder':
                if (!empty($user->rights->fournisseur->commande->commander)) {
                    return 1;
                }
                return 0;

            case 'receive_products':
            case 'receive':
                if (!empty($user->rights->fournisseur->commande->receptionner)) {
                    return 1;
                }
                return 0;

            case 'createInvoice':
                if (!empty($user->rights->fournisseur->facture->creer)) {
                    return 1;
                }
                return 0;

            case 'classifyBilled':
                if (!empty($user->rights->fournisseur->commande->creer)) {
                    return 1;
                }
                return 0;

            case 'cancel':
                if (!empty($user->rights->fournisseur->commande->commander)) {
                    return 1;
                }
                return 0;
        }

        return parent::canSetAction($action);
    }

    public function canEditField($field)
    {
        if (in_array($field, array('date_commande', 'fk_input_method'))) {
            return (int) $this->canSetAction('make_order');
        }

        return (int) parent::canEditField($field);
    }

    // Getters - overrides BimpComm:

    public function getModelsPdfArray()
    {
        if (!class_exists('ModelePDFSuppliersOrders')) {
            require_once DOL_DOCUMENT_ROOT . '/core/modules/supplier_order/modules_commandefournisseur.php';
        }

        return ModelePDFSuppliersOrders::liste_modeles($this->db->db);
    }

    public function getDirOutput()
    {
        global $conf;

        return $conf->fournisseur->commande->dir_output;
    }

    public function getListFilters()
    {
        return array();
    }

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
                        'onclick' => $this->getJsActionOnclick('validate', array('approve' => 0), array(
                            'confirm_msg' => 'Veuillez confirmer la validation ' . $this->getLabel('of_this')
                        ))
                    );

                    if ($this->canSetAction('approve') && empty($conf->global->SUPPLIER_ORDER_NO_DIRECT_APPROVE)) {
                        $buttons[] = array(
                            'label'   => 'Valider et approuver',
                            'icon'    => 'fas_check',
                            'onclick' => $this->getJsActionOnclick('validate', array('approve' => 1), array(
                                'confirm_msg' => 'Veuillez confirmer la validation ' . $this->getLabel('of_this')
                            ))
                        );
                    }
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

            // Approuver: 
            if ($this->isActionAllowed('approve')) {
                if ($this->canSetAction('approve')) {
                    $buttons[] = array(
                        'label'   => 'Approuver',
                        'icon'    => 'fas_check',
                        'onclick' => $this->getJsActionOnclick('approve', array(), array(
                            'confirm_msg' => 'Veuillez confirmer l\\\'approbation ' . $this->getLabel('of_this')
                        ))
                    );
                } else {
                    $buttons[] = array(
                        'label'    => 'Approuver',
                        'icon'     => 'fas_check',
                        'onclick'  => '',
                        'disabled' => 1,
                        'popover'  => 'Vous n\'avez pas la permission d\'approuver ' . $this->getLabel('this')
                    );
                }
            }

            // Approuver 2:
            if ($this->isActionAllowed('approve2')) {
                if ($this->canSetAction('approve2')) {
                    $buttons[] = array(
                        'label'   => 'Approuver (2)',
                        'icon'    => 'fas_check',
                        'onclick' => $this->getJsActionOnclick('approve2', array(), array(
                            'confirm_msg' => 'Veuillez confirmer la deuxième approbation ' . $this->getLabel('of_this')
                        ))
                    );
                } else {
                    $buttons[] = array(
                        'label'    => 'Approuver (2)',
                        'icon'     => 'fas_check',
                        'onclick'  => '',
                        'disabled' => 1,
                        'popover'  => 'Vous n\'avez pas la permission d\'effectuer la deuxième approbation pour ' . $this->getLabel('this')
                    );
                }
            }

            // Refuser: 
            if ($this->isActionAllowed('refuse')) {
                if ($this->canSetAction('refuse')) {
                    $buttons[] = array(
                        'label'   => 'Refuser',
                        'icon'    => 'fas_times',
                        'onclick' => $this->getJsActionOnclick('refuse', array(), array(
                            'confirm_msg' => 'Veuillez confirmer le refus ' . $this->getLabel('of_this')
                        ))
                    );
                } else {
                    $buttons[] = array(
                        'label'    => 'Refuser',
                        'icon'     => 'fas_times',
                        'onclick'  => '',
                        'disabled' => 1,
                        'popover'  => 'Vous n\'avez pas la permission de refuser ' . $this->getLabel('this')
                    );
                }
            }

            // Envoyer par e-mail: 
            if ($this->isActionAllowed('sendEmail') && $this->canSetAction('sendEmail')) {
                $onclick = $this->getJsActionOnclick('sendEmail', array(), array(
                    'form_name' => 'email'
                ));
                $buttons[] = array(
                    'label'   => 'Envoyer par email',
                    'icon'    => 'fas_envelope',
                    'onclick' => $onclick,
                );
            }

            // Réouvrir: 
            if ($this->isActionAllowed('reopen')) {
                $status = (int) $this->getData('fk_statut');
                if ($status === 1) {
                    $label = 'Modifier';
                    $confirm_label = 'remise au statut \\\'\\\'brouillon\\\'\\\'';
                    $perm_label = 'remettre ' . $this->getLabel('this') . ' au statut brouillon';
                } elseif ($status === 2) {
                    $label = 'Désapprouver';
                    $confirm_label = 'désapprobation';
                    $perm_label = 'désapprouver ' . $this->getLabel('this');
                } else {
                    $label = 'Réouvrir';
                    $confirm_label = 'réouverture';
                    $perm_label = 'réouvrir ' . $this->getLabel('this');
                }
                if ($this->canSetAction('reopen')) {
                    $buttons[] = array(
                        'label'   => $label,
                        'icon'    => 'fas_undo',
                        'onclick' => $this->getJsActionOnclick('reopen', array(), array(
                            'confirm_msg' => 'Veuillez confirmer la ' . $confirm_label . $this->getLabel('of_this')
                        ))
                    );
                } else {
                    $buttons[] = array(
                        'label'    => $label,
                        'icon'     => 'fas_undo',
                        'onclick'  => '',
                        'disabled' => 1,
                        'popover'  => 'Vous n\'avez pas la permission de ' . $perm_label
                    );
                }
            }

            // Commander
            if ($this->isActionAllowed('makeOrder') && $this->canSetAction('makeOrder')) {
                $onclick = $this->getJsActionOnclick('makeOrder', array(), array(
                    'form_name' => 'make_order'
                ));
                $buttons[] = array(
                    'label'   => 'Commander',
                    'icon'    => 'fas_arrow-circle-right',
                    'onclick' => $onclick,
                );
            }

            // Réceptionner produits:
            if ($this->isActionAllowed('receive_products') && $this->canSetAction('receive_products')) {
                $onclick = 'window.location = \'' . DOL_URL_ROOT . '/fourn/commande/dispatch.php?id=' . $this->id . '\'';
                $buttons[] = array(
                    'label'   => 'Réceptionner des produits',
                    'icon'    => 'fas_arrow-circle-down',
                    'onclick' => $onclick,
                );
            }

            // Réceptionner commande:
            if ($this->isActionAllowed('receive') && $this->canSetAction('receive')) {
                $buttons[] = array(
                    'label'   => 'Réceptionner commande',
                    'icon'    => 'fas_arrow-circle-down',
                    'onclick' => $this->getJsActionOnclick('receive', array(), array(
                        'form_name' => 'receive'
                    )),
                );
            }

            // Créer facture: 
            if ($this->isActionAllowed('createInvoice') && $this->canSetAction('createInvoice')) {
                $url = DOL_URL_ROOT . '/fourn/facture/card.php?action=create&origin=' . $this->dol_object->element . '&originid=' . $this->id . '&socid=' . (int) $this->getData('fk_soc');
                $onclick = 'window.location = \'' . $url . '\';';
                $buttons[] = array(
                    'label'   => 'Créer une facture fournisseur',
                    'icon'    => 'fas_file-invoice-dollar',
//                        'onclick' => $this->getJsActionOnclick('createInvoice')
                    'onclick' => $onclick
                );
            }

            // Classer facturée: 
            if ($this->isActionAllowed('classifyBilled') && $this->canSetAction('classifyBilled')) {
                $buttons[] = array(
                    'label'   => 'Classer facturée',
                    'icon'    => 'fas_check',
                    'onclick' => $this->getJsActionOnclick('classifyBilled')
                );
            }

            // Annuler: 
            if ($this->isActionAllowed('cancel') && $this->canSetAction('cancel')) {
                $buttons[] = array(
                    'label'   => 'Annuler',
                    'icon'    => 'fas_times',
                    'onclick' => $this->getJsActionOnclick('cancel', array(), array(
                        'confirm_msg' => 'Veuillez confirmer l\\\'annulation de cette commande'
                    ))
                );
            }

            // Cloner: 
            if ($this->can("create")) {
                $buttons[] = array(
                    'label'   => 'Cloner',
                    'icon'    => 'fas_copy',
                    'onclick' => $this->getJsActionOnclick('duplicate', array(), array(
                        'confirm_msg' => 'Etes-vous sûr de vouloir cloner ' . $this->getLabel('this')
//                        'form_name' => 'duplicate_propal'
                    ))
                );
            }
        }

        return $buttons;
    }

    // Rendus HTML - overrides BimpObject:

    public function renderHeaderExtraLeft()
    {
        $html = '';

        if ($this->isLoaded()) {
            $user = new User($this->db->db);

            $html .= '<div class="object_header_infos">';
            $html .= 'Créée le <strong>' . $this->displayData('date_creation', 'default', false, true) . '</strong>';

            $user->fetch((int) $this->getData('fk_user_author'));
            $html .= ' par ' . $user->getNomUrl(1);
            $html .= '</div>';

            if ((int) $this->getData('fk_user_valid')) {
                $html .= '<div class="object_header_infos">';
                $html .= 'Validée le <strong>' . $this->displayData('date_valid', 'default', false, true) . '</strong>';
                $user->fetch((int) $this->getData('fk_user_valid'));
                $html .= ' par ' . $user->getNomUrl(1);
                $html .= '</div>';
            }
            if ((int) $this->getData('fk_user_approve')) {
                $html .= '<div class="object_header_infos">';
                $html .= '1ère approbation le <strong>' . $this->displayData('date_approve', 'default', false, true) . '</strong>';
                $user->fetch((int) $this->getData('fk_user_approve'));
                $html .= ' par ' . $user->getNomUrl(1);
                $html .= '</div>';
            }
            if ((int) $this->getData('fk_user_approve2')) {
                $html .= '<div class="object_header_infos">';
                $html .= '2ème approbation le <strong>' . $this->displayData('date_approve2', 'default', false, true) . '</strong>';
                $user->fetch((int) $this->getData('fk_user_approve2'));
                $html .= ' par ' . $user->getNomUrl(1);
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

    public function onCancelStatus()
    {
        if (!$this->isLoaded()) {
            return array('ID de la Commande fournisseur absent');
        }

        if (!in_array((int) $this->getData('fk_statut'), self::$cancel_status)) {
            return array('Statut invalide (' . $this->getData('fk_statut') . ')');
        }

        $errors = array();

        $lines = $this->getChildrenObjects('lines');

        foreach ($lines as $line) {
            if ($line->getData('linked_object_name') === 'bf_line') {
                $bf_line = BimpCache::getBimpObjectInstance('bimpfinancement', 'BF_Line', (int) $line->getData('linked_id_object'));
                if (BimpObject::objectLoaded($bf_line)) {
                    $line_errors = $bf_line->onCommandeFournCancel($this->id);
                    if (count($line_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($line_errors);
                    }
                }
                $bf_line->set('linked_object_name', '');
                $bf_line->set('linked_id_object', 0);
                $bf_line->update($warnings, true);
            }
        }

        return $errors;
    }

    // Actions:

    public function actionValidate($data, &$success)
    {
        $result = parent::actionValidate($data, $success);

        if (!count($result['errors'])) {
            global $conf;

            if (isset($data['approve']) && (int) $data['approve'] &&
                    empty($conf->global->SUPPLIER_ORDER_NO_DIRECT_APPROVE) && $this->canSetAction('approve')) {
                $success2 = '';
                $result2 = $this->setObjectAction('approve', 0, array(), $success2);
                if (count($result2['errors'])) {
                    $result['warnings'][] = BimpTools::getMsgFromArray($result2['errors'], 'Echec de l\'approbation ' . $this->getLabel('of_the'));
                } else {
                    $success .= '<br/>' . $success2;
                }
                $result['warnings'] = array_merge($result['warnings'], $result2['warnings']);
            }
        }

        return $result;
    }

    public function actionApprove($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = BimpTools::ucfirst($this->getLabel()) . ' approuvé' . ($this->isLabelFemale() ? 'e' : '') . ' avec succès';

        global $user, $conf, $langs;

        $result = $this->dol_object->approve($user, (int) $this->getData('entrepot'), 0);
        if ($result > 0) {
            if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
                $this->fetch($this->id);
                $this->dol_object->generateDocument($this->getModelPdf(), $langs);
            }
        } else {
            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object, null, null, $warnings), 'Des erreurs sont survenues lors de l\'approbation ' . $this->getLabel('of_this'));
        }
        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionApprove2($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Seconde approbation ' . $this->getLabel('of_this') . ' effectuée avec succès';

        global $user, $conf, $langs;

        $result = $this->dol_object->approve($user, (int) $this->getData('entrepot'), 1);
        if ($result > 0) {
            if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
                $this->fetch($this->id);
                $this->dol_object->generateDocument($this->getModelPdf(), $langs);
            }
        } else {
            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object, null, null, $warnings), 'Des erreurs sont survenues lors de la seconde approbation ' . $this->getLabel('of_this'));
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionRefuse($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = BimpTools::ucfirst($this->getLabel()) . ' refusé' . ($this->isLabelFemale() ? 'e' : '') . ' avec succès';

        BimpTools::resetDolObjectErrors($this->dol_object);

        global $user;
        if ($this->dol_object->refuse($user) <= 0) {
            $errors[] = BimpTools::getMsgFromArray($this->dol_object);
        } else {
            $this->fetch($this->id);
            $cancel_errors = $this->onCancelStatus();
            if (count($cancel_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($cancel_errors, 'Des erreurs sont survenues lors du traitements des lignes de financement associées à cette commande');
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
        $success = '';

        $status = (int) $this->getData('fk_statut');

        if ($status <= 5) {
            $new_status = $status - 1;
        } elseif ($status === 6) {
            $new_status = 2;
        } elseif ($status === 7) {
            $new_status = 3;
        } elseif ($status === 9) {
            $new_status = 1;
        }

        global $user;

        if (isset(self::$status_list[$new_status])) {
            $success .= 'Remise au statut "' . self::$status_list[$new_status]['label'] . '" effectuée avec succès';

            BimpTools::resetDolObjectErrors($this->dol_object);

            if ($this->dol_object->setStatus($user, $new_status)) {
                $this->updateField('billed', 0);
            } else {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la mise à jour du statut ' . $this->getLabel('of_the'));
            }
        } else {
            $errors[] = 'Nouveau statut invalide';
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionMakeOrder($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Commande effectuée avec succès';

        if (!isset($data['date_commande']) || !$data['date_commande']) {
            $errors[] = 'Date de la commande absente';
        }

        if (!isset($data['fk_input_method']) || !$data['fk_input_method']) {
            $errors[] = 'Méthode de commande absente';
        }

        if (!count($errors)) {
            global $user, $conf, $langs;

            BimpTools::resetDolObjectErrors($this->dol_object);

            if ($this->dol_object->commande($user, BimpTools::getDateForDolDate($data['date_commande']), (int) $data['fk_input_method']) <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object));
            } elseif (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
                $this->fetch($this->id);
                $this->dol_object->generateDocument($this->getModelPdf(), $langs);
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionReceive($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Livraison enregistrée avec succès';

        if (!isset($data['date_livraison']) || !$data['date_livraison']) {
            $errors[] = 'Date de livraison absente';
        } else {
            if (!isset($data['livraison_type'])) {
                $data['livraison_type'] = '';
            }
            if (!isset($data['comments'])) {
                $data['comments'] = '';
            }

            BimpTools::resetDolObjectErrors($this->dol_object);

            global $user;
            if ($this->dol_object->Livraison($user, $data['date_livraison'], $data['livraison_type'], $data['comments']) <= 0) {
                $errors[] = BimpTools::getMsgFromArray($this->dol_object);
            } else {
                $this->fetch($this->id);
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionCreateInvoice($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';


        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionClassifyBilled($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';


        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionCancel($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = BimpTools::ucfirst($this->getLabel()) . ' annulé' . ($this->isLabelFemale() ? 'e' : '') . ' avec succès';

        BimpTools::resetDolObjectErrors($this->dol_object);

        global $user;
        if ($this->dol_object->cancel($user) <= 0) {
            $errors[] = BimpTools::getMsgFromArray($this->dol_object);
        } else {
            $this->fetch($this->id);
            $cancel_errors = $this->onCancelStatus();
            if (count($cancel_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($cancel_errors, 'Des erreurs sont survenues lors du traitements des lignes de financement associées à cette commande');
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrides - BimpComm: 

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            if ((string) $this->getData('date_livraison')) {
                global $user;
                $this->dol_object->error = '';
                $this->dol_object->errors = array();
                if ($this->dol_object->set_date_livraison($user, BimpTools::getDateForDolDate($this->getData('date_livraison'))) <= 0) {
                    $warnings[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de l\'enregistrement de la date de livraison');
                }
            }
        }

        return $errors;
    }

    protected function updateDolObject(&$errors)
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

        $data = array(
            'fk_soc'            => (int) $this->getData('fk_soc'),
            'date_livraison'    => $this->getData('date_livraison'),
            'ref_supplier'      => $this->getData('ref_supplier'),
            'fk_cond_reglement' => $this->getData('fk_cond_reglement'),
            'fk_mode_reglement' => $this->getData('fk_mode_reglement'),
            'model_pdf'         => $this->getData('model_pdf'),
            'note_private'      => $this->getData('note_private'),
            'note_public'       => $this->getData('note_public'),
        );

        if ($this->db->update($this->dol_object->table_element, $data, '`rowid` = ' . (int) $this->id) <= 0) {
            $errorsSql = $this->db->db->lasterror();
            $errors[] = 'Echec de la mise à jour de la commande fournisseur' . ($errorsSql ? ' - ' . $errorsSql : '');
            return 0;
        }

        $bimpObjectFields = array();
        $this->hydrateDolObject($bimpObjectFields);

        // Mise à jour des champs Bimp_Propal:
        foreach ($bimpObjectFields as $field => $value) {
            $field_errors = $this->updateField($field, $value);
            if (count($field_errors)) {
                $errors[] = BimpTools::getMsgFromArray($field_errors, 'Echec de la mise à jour du champ "' . $field . '"');
            }
        }

        // Mise à jour des extra_fields: 
        global $user;
        if ($this->dol_object->insertExtraFields('', $user) <= 0) {
            $errors[] = 'Echec de la mise à jour des champs supplémentaires';
        }
        if (!count($errors)) {
            return 1;
        }

        return 1;
    }
}
