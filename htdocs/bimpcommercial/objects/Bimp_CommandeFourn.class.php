<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/BimpComm.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.product.class.php';

class Bimp_CommandeFourn extends BimpComm
{

    public $redirectMode = 4; //5;//1 btn dans les deux cas   2// btn old vers new   3//btn new vers old   //4 auto old vers new //5 auto new vers old
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
    public static $invoice_status = array(
        0 => array('label' => 'Non facturée', 'icon' => 'fas_file-invoice-dollar', 'classes' => array('danger')),
        1 => array('label' => 'Facturée partiellement', 'icon' => 'fas_file-invoice-dollar', 'classes' => array('warning')),
        2 => array('label' => 'Facturée', 'icon' => 'fas_file-invoice-dollar', 'classes' => array('success'))
    );
    public static $cancel_status = array(6, 7, 9);
    public static $livraison_types = array(
        ''    => '',
        'tot' => array('label' => 'Complète', 'classes' => array('success')),
        'par' => array('label' => 'Partielle', 'classes' => array('warning')),
        'nev' => array('label' => 'Jamais reçue', 'classes' => array('danger')),
        'can' => array('label' => 'Annulée', 'classes' => array('danger')),
    );
    public static $logistique_active_status = array(3, 4, 5, 7);

    // Gestion des autorisations objet: 

    public function isFieldEditable($field, $force_edit = false)
    {
        if (in_array($field, array('date_commande', 'fk_input_method'))) {
            return (int) $this->isActionAllowed('make_order');
        }

        return parent::isFieldEditable($field, $force_edit);
    }

    public function isActionAllowed($action, &$errors = array())
    {
        $status = $this->getData('fk_statut');
        if (in_array($action, array('validate', 'approve', 'approve2', 'refuse', 'modify', 'sendEmail', 'reopen', 'make_order', 'receive', 'receive_products', 'createInvoice', 'classifyBilled', 'forceStatus'))) {
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
                if (empty($conf->global->SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED) || $conf->global->MAIN_FEATURES_LEVEL < 1 || $this->getData('total_ht') <= $conf->global->SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED) {
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
                if (in_array($status, array(4, 5))) {
                    $errors[] = 'Une ou plusieurs réceptions ont déjà été enregistrées pour cette commande fournisseur';
                    return 0;
                }
                if (!in_array($status, array(1, 2, 3, 6, 7, 9))) {
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
                $this->checkInvoiceStatus();
                if (empty($conf->facture->enabled)) {
                    $errors[] = 'Factures désactivées';
                    return 0;
                }
                if ((int) $this->getData('invoice_status') === 2) {
                    $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' a déjà été facturée entièrement';
                    return 0;
                }
                if ($status < 4) {
                    $errors[] = 'La commande doit avoir au moins partiellement réceptionnée pour pouvoir créer une facture fournisseur';
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

            case 'forceStatus':
                if (!$this->isLogistiqueActive()) {
                    $errors[] = 'La logistique n\'est pas active';
                    return 0;
                }
                return 1;
        }
        return parent::isActionAllowed($action);
    }

    public function isLogistiqueActive()
    {
        if (in_array((int) $this->getData('fk_statut'), self::$logistique_active_status)) {
            return 1;
        }

        return 0;
    }

    public function isBilled()
    {
        if (!$this->isLoaded()) {
            return 0;
        }

        $this->checkInvoiceStatus();

        if ((int) $this->getData('billed') || (int) $this->getData('invoice_status') === 2) {
            return 1;
        }

        return 0;
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

            case 'forceStatus':
                return 1;
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
//            if ($this->isActionAllowed('receive_products') && $this->canSetAction('receive_products')) {
//                $onclick = 'window.location = \'' . DOL_URL_ROOT . '/fourn/commande/dispatch.php?id=' . $this->id . '\'';
//                $buttons[] = array(
//                    'label'   => 'Réceptionner des produits',
//                    'icon'    => 'fas_arrow-circle-down',
//                    'onclick' => $onclick,
//                );
//            }
//
//            // Réceptionner commande:
//            if ($this->isActionAllowed('receive') && $this->canSetAction('receive')) {
//                $buttons[] = array(
//                    'label'   => 'Réceptionner commande',
//                    'icon'    => 'fas_arrow-circle-down',
//                    'onclick' => $this->getJsActionOnclick('receive', array(), array(
//                        'form_name' => 'receive'
//                    )),
//                );
//            }
            // Créer facture: 
            if ($this->isActionAllowed('createInvoice') && $this->canSetAction('createInvoice')) {
//                $url = DOL_URL_ROOT . '/fourn/facture/card.php?action=create&origin=' . $this->dol_object->element . '&originid=' . $this->id . '&socid=' . (int) $this->getData('fk_soc');
//                $onclick = 'window.location = \'' . $url . '\';';
                $factureFourn = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureFourn');
                $values = array(
                    'fields' => array(
                        'origin'            => 'order_supplier',
                        'origin_id'         => (int) $this->id,
                        'entrepot'          => (int) $this->getData('entrepot'),
                        'fk_soc'            => (int) $this->getData('fk_soc'),
                        'ref_supplier'      => (string) $this->getData('ref_supplier'),
                        'fk_cond_reglement' => (int) $this->getData('fk_cond_reglement'),
                        'fk_mode_reglement' => (int) $this->getData('fk_mode_reglement')
                    )
                );
                $onclick = $factureFourn->getJsLoadModalForm('default', 'Création d\\\'une facture', $values, '', 'redirect');
                $buttons[] = array(
                    'label'   => 'Créer une facture fournisseur',
                    'icon'    => 'fas_file-invoice-dollar',
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
                        'form_name' => 'duplicate_commande_fourn'
                    ))
                );
            }

            // Forcer statut: 
            if ($this->isActionAllowed('forceStatus')) {
                if ($this->canSetAction('forceStatus')) {
                    $buttons[] = array(
                        'label'   => 'Forcer un statut',
                        'icon'    => 'far_check-square',
                        'onclick' => $this->getJsActionOnclick('forceStatus', array(), array(
                            'form_name' => 'force_status'
                        )),
                    );
                } else {
                    $buttons[] = array(
                        'label'    => 'Forcer un statut',
                        'icon'     => 'far_check-square',
                        'onclick'  => '',
                        'disabled' => 1,
                        'popover'  => 'Vous n\'avez pas la permission'
                    );
                }
            }
        }

        return $buttons;
    }

    public function getDefaultListExtraButtons()
    {
        $buttons = parent::getDefaultListExtraButtons();

        if ($this->isLoaded() && $this->isLogistiqueActive()) {
            $url = DOL_URL_ROOT . '/bimplogistique/index.php?fc=commandeFourn&id=' . $this->id;
            $buttons[] = array(
                'label'   => 'Page logistique',
                'icon'    => 'fas_truck-loading',
                'onclick' => 'window.open(\'' . $url . '\')'
            );
        }

        return $buttons;
    }

    // Getters Array: 

    public function getReceptionsArray($include_empty = false)
    {
        if (!$this->isLoaded()) {
            if ($include_empty) {
                return array(
                    0 => ''
                );
            } else {
                return array();
            }
        }

        $cache_key = 'commande_fourn_' . $this->id . '_receptions_array';

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            $receptions = $this->getChildrenObjects('receptions', array(), 'id', 'desc');
            foreach ($receptions as $reception) {
                $label = $reception->getData('num_reception') . ' - ' . $reception->getData('ref');
                $entrepot = $reception->getChildObject('entrepot');
                if (BimpObject::objectLoaded($entrepot)) {
                    $label .= ' (' . $entrepot->libelle . ')';
                } else {
                    $label .= ' (Entrepôt invalide)';
                }
                self::$cache[$cache_key][(int) $reception->id] = $label;
            }
        }

        return self::getCacheArray($cache_key, $include_empty);
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
            if ((int) $this->getData('fk_user_resp')) {
                $html .= '<div class="object_header_infos">';
                $html .= 'Personne en charge: ';
                $user->fetch((int) $this->getData('fk_user_resp'));
                $html .= $user->getNomUrl(1);
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

    public function renderLogistiqueButtons()
    {
        $html = '';

        if ($this->isLoaded()) {
            if (!((int) $this->isBilled())) {
                if (in_array((int) $this->getData('fk_statut'), array(3, 4, 5))) {
                    $reception = BimpObject::getInstance('bimplogistique', 'BL_CommandeFournReception');
                    $onclick = $reception->getJsLoadModalForm('default', 'Nouvelle réception', array(
                        'fields' => array(
                            'id_commande_fourn' => $this->id,
                            'id_entrepot'       => (int) $this->getData('entrepot')
                        )
                    ));
                    $html .= '<button class="btn btn-default" onclick="' . $onclick . '">';
                    $html .= BimpRender::renderIcon('fas_arrow-circle-down', 'iconLeft') . 'Nouvelle réception';
                    $html .= '</button>';
                }

                $line = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFournLine');
                $onclick = $line->getJsLoadModalForm('fournline_forced', 'Ajout d\\\'une ligne de commande supplémentaire', array(
                    'fields' => array(
                        'id_obj' => (int) $this->id
                    )
                ));
                $html .= '<button class="btn btn-default" onclick="' . $onclick . '">';
                $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Ajouter une ligne de commande';
                $html .= '</button>';
            }
        }

        return $html;
    }

    public function renderHeaderStatusExtra()
    {
        $html = '';

        if ((int) $this->getData('invoice_status') > 0) {
            $html .= '<span>' . $this->displayData('invoice_status') . '</span>';
        }
        if ((int) $this->getData('attente_info')) {
            $html .= '<br/><span class="warning">' . BimpRender::renderIcon('fas_hourglass-start', 'iconLeft') . 'Attente Infos</span>';
        }

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

    public function checkReceptionStatus()
    {
        $status_forced = $this->getData('status_forced');

        if (isset($status_forced['reception']) && (int) $status_forced['reception']) {
            return;
        }

        $current_status = (int) $this->getInitData('fk_statut');

        if (in_array($current_status, array(0, 1, 2, 6, 7, 9))) {
            return;
        }

        BimpObject::loadClass('bimpcommercial', 'ObjectLine');

        $lines = $this->getLines('not_text');

        $hasReception = 0;
        $isFullyReceived = 1;

        foreach ($lines as $line) {
            $received_qty = (float) $line->getReceivedQty(null, true);
            if ($received_qty > 0) {
                $hasReception = 1;
            }

            if ($received_qty < (float) $line->getFullQty()) {
                $isFullyReceived = 0;
            }
        }

        if ($isFullyReceived) {
            $new_status = 5;
        } elseif ($hasReception) {
            $new_status = 4;
        } else {
            $new_status = 3;
        }

        if ($current_status !== $new_status) {
            $this->updateField('fk_statut', $new_status);
        }
    }

    public function checkInvoiceStatus()
    {
        if (!$this->isLoaded()) {
            return;
        }

        $status_forced = $this->getData('status_forced');

        if (isset($status_forced['invoice']) && (int) $status_forced['invoice']) {
            return;
        }

        $invoice_status = 0;

        $total_factures_ht = 0;

        foreach (BimpTools::getDolObjectLinkedObjectsList($this->dol_object, $this->db) as $item) {
            if ($item['type'] === 'invoice_supplier') {
                $facture_fourn_instance = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureFourn', (int) $item['id_object']);
                if (BimpObject::objectLoaded($facture_fourn_instance)) {
                    if ((int) $facture_fourn_instance->getData('fk_statut') !== FactureFournisseur::STATUS_ABANDONED) {
                        $total_factures_ht += (float) $facture_fourn_instance->getData('total_ht');
                    }
                }
            }
        }

        if (abs($total_factures_ht) >= abs((float) $this->getData('total_ht'))) {
            $invoice_status = 2;
        } elseif (abs($total_factures_ht) > 0) {
            $invoice_status = 1;
        } else {
            $invoice_status = 0;
        }

        if ($invoice_status !== (int) $this->getInitData('invoice_status')) {
            $this->updateField('invoice_status', $invoice_status);
        }

        $billed = 0;
        if ($invoice_status > 0) {
            $billed = 1;
        }
        
        if ($billed !== (int) $this->getInitData('billed')) {
            $this->updateField('billed', $billed);
        }
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
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'bimp_reloadPage();'
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
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'bimp_reloadPage();'
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
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'bimp_reloadPage();'
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

    public function actionForceStatus($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Statut forcé enregistré avec succès';

        if (!isset($data['status_type']) || !(string) $data['status_type']) {
            $errors[] = 'Type de statut absent';
        }

        if (!count($errors)) {
            $status = (int) $this->getData('fk_statut');
            $status_forced = $this->getData('status_forced');

            switch ($data['status_type']) {
                case 'reception':
                    if (!isset($data['reception_status'])) {
                        $errors[] = 'Statut réception absent';
                    } elseif (!in_array($status, array(3, 4, 5))) {
                        $errors[] = 'Le statut actuel de la commande fournisseur ne permet pas de forcer le statut de la réception';
                    } elseif (!in_array((int) $data['reception_status'], array(-1, 3, 4, 5))) {
                        $errors[] = 'Statut réception invalide';
                    } else {
                        if ((int) $data['reception_status'] === -1) {
                            if (isset($status_forced['reception'])) {
                                unset($status_forced['reception']);
                                $errors = $this->updateField('status_forced', $status_forced);
                            }
                            if (!count($errors)) {
                                $this->checkReceptionStatus();
                            }
                        } else {
                            $status_forced['reception'] = 1;
                            $sub_errors = $this->updateField('fk_statut', (int) $data['reception_status']);
                            if (count($sub_errors)) {
                                $errors[] = BimpTools::getMsgFromArray($sub_errors, 'Echec de la mise à jour du statut de la commande');
                            } else {
                                $errors = $this->updateField('status_forced', $status_forced);
                            }
                        }
                    }
                    break;

                case 'invoice':
                    if (!isset($data['invoice_status'])) {
                        $errors[] = 'Statut facturation absent';
                    } elseif (!in_array((int) $data['invoice_status'], array(-1, 0, 1, 2))) {
                        $errors[] = 'Statut facturation invalide';
                    } else {
                        if ((int) $data['invoice_status'] === -1) {
                            if (isset($status_forced['invoice'])) {
                                unset($status_forced['invoice']);
                                $errors = $this->updateField('status_forced', $status_forced);
                            }
                            if (!count($errors)) {
                                $this->checkInvoiceStatus();
                            }
                        } else {
                            $status_forced['invoice'] = 1;
                            $sub_errors = $this->updateField('invoice_status', (int) $data['invoice_status']);
                            if (count($sub_errors)) {
                                $errors[] = BimpTools::getMsgFromArray($sub_errors, 'Echec de la mise à jour du statut de la facturation');
                            } else {
                                $errors = $this->updateField('status_forced', $status_forced);
                            }
                        }
                    }
                    break;
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrides - BimpComm: 

    public function duplicate($new_data = array(), &$warnings = array(), $force_create = false)
    {
        $new_data['billed'] = 0;
        $new_data['invoice_status'] = 0;
        $new_data['attente_info'] = 0;

        $new_data['date_creation'] = date('Y-m-d H:i:s');
        $new_data['date_valid'] = null;
        $new_data['date_approve'] = null;
        $new_data['date_approve2'] = null;

        $new_data['fk_user_author'] = 0;
        $new_data['fk_user_modif'] = 0;
        $new_data['fk_user_approve'] = 0;
        $new_data['fk_user_approve2'] = 0;
        $new_data['fk_user_resp'] = 0;

        return parent::duplicate($new_data, $warnings, $force_create);
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        if (is_null($this->data['fk_user_resp']) || !(int) $this->data['fk_user_resp']) {
            global $user;

            if (BimpObject::objectLoaded($user)) {
                $this->set('fk_user_resp', (int) $user->id);
            }
        }

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
