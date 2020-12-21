<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/BimpComm.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.product.class.php';

class Bimp_CommandeFourn extends BimpComm
{

    const DELIV_ENTREPOT = 0;
    const DELIV_SIEGE = 1;
    const DELIV_CUSTOM = 2;
    const DELIV_DIRECT = 3;

    public $idLdlc = 230880;
    public $redirectMode = 4; //5;//1 btn dans les deux cas   2// btn old vers new   3//btn new vers old   //4 auto old vers new //5 auto new vers old
    public static $dol_module = 'commande_fournisseur';
    public static $email_type = 'order_supplier_send';
    public static $mail_event_code = 'ORDER_SUPPLIER_SENTBYMAIL';
    public static $element_name = 'order_supplier';
    public static $external_contact_type_required = false;
    public static $internal_contact_type_required = false;
    public static $discount_lines_allowed = false;
    public static $remise_globale_allowed = false;
    public static $cant_edit_zone_vente_secteurs = array();
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
    public static $edi_status = array(0    => "Pas d'info",
        1    => "Flux valide",
        -50  => array('label' => 'Partiel', 'icon' => 'fas_times', 'classes' => array('danger')),
        -1   => array('label' => 'Flux non identifié (XSD)', 'icon' => 'fas_times', 'classes' => array('danger')),
        -2   => array('label' => "Flux de commande non valide (XSD)", 'icon' => 'fas_times', 'classes' => array('danger')),
        -3   => array('label' => 'Compte client inexistant ou inactif (supprimé ou bloqué)', 'icon' => 'fas_times', 'classes' => array('danger')),
        -4   => array('label' => 'Violation d’unicité (référence commande) (Une commande comportant le même « external_identifier » existe déjà.)', 'icon' => 'fas_times', 'classes' => array('danger')),
        -5   => array('label' => 'Produit absent du catalogue', 'icon' => 'fas_times', 'classes' => array('danger')),
        -6   => array('label' => 'Prix incorrect par rapport aux prix du catalogue négocié', 'icon' => 'fas_times', 'classes' => array('danger')),
        -7   => array('label' => 'Erreur lors de la création du compte client', 'icon' => 'fas_times', 'classes' => array('danger')),
        -8   => array('label' => 'Erreur lors de la création de la commande', 'icon' => 'fas_times', 'classes' => array('danger')),
        -9   => array('label' => 'Le mode d’identification du client n’est pas défini', 'icon' => 'fas_times', 'classes' => array('danger')),
        -50  => array('label' => 'Partiel', 'icon' => 'fas_times', 'classes' => array('danger')),
        -100 => array('label' => 'Annulée', 'icon' => 'fas_times', 'classes' => array('danger')),
        -105 => array('label' => 'Supprimée', 'icon' => 'fas_times', 'classes' => array('danger')),
        91   => array('label' => 'Confirmée', 'icon' => 'fas_file-alt', 'classes' => array('info')),
        95   => array('label' => 'Préparation', 'icon' => 'fas_file-alt', 'classes' => array('info')),
        100  => array('label' => 'Expédiée', 'icon' => 'fas_file-alt', 'classes' => array('info')),
        105  => array('label' => 'Facturé', 'icon' => 'fas_file-alt', 'classes' => array('info')),
        95   => array('label' => 'Préparation', 'icon' => 'fas_file-alt', 'classes' => array('info')),
        95   => array('label' => 'Préparation', 'icon' => 'fas_file-alt', 'classes' => array('info')));
    public static $cancel_status = array(6, 7, 9);
    public static $livraison_types = array(
        ''    => '',
        'tot' => array('label' => 'Complète', 'classes' => array('success')),
        'par' => array('label' => 'Partielle', 'classes' => array('warning')),
        'nev' => array('label' => 'Jamais reçue', 'classes' => array('danger')),
        'can' => array('label' => 'Annulée', 'classes' => array('danger')),
    );
    public static $logistique_active_status = array(3, 4, 5, 7);
    public static $delivery_types = array(
        self::DELIV_ENTREPOT => 'Entrepôt de la commande',
        self::DELIV_SIEGE    => 'Siège social',
        self::DELIV_CUSTOM   => 'Personnalisée',
        self::DELIV_DIRECT   => 'Contact livraison directe'
    );
    protected static $types_entrepot = array();

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
        if (in_array($action, array('validate', 'approve', 'approve2', 'refuse', 'modify', 'sendEmail', 'reopen', 'make_order', 'receive', 'receive_products', 'classifyBilled', 'forceStatus'))) {
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
//                if ((int) $this->getData('fk_user_approve')) {
//                    $errors[] = BimpTools::ucfirst($this->getData('this')) . ' a déjà été approuvé' . ($this->isLabelFemale() ? 'e' : '');
//                }
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
                if ($this->hasReceptions()) {
                    $errors[] = 'Une ou plusieurs réceptions ont déjà été enregistrées pour cette commande fournisseur';
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
                if ($this->isLoaded()) {
                    if ((int) $this->getData('invoice_status') === 2) {
                        $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' a déjà été entièrement facturée';
                        return 0;
                    }
                    if (!$this->isLogistiqueActive()) {
                        $errors[] = 'La logistique n\'est pas active';
                        return 0;
                    }
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

    public function getAdresseLivraison(&$warnings = array())
    {
        $result = array('name' => '', 'adress' => '', 'adress2' => '', 'adress3' => '', 'zip' => '', 'town' => '', 'contact' => '', 'country' => '');

        switch ($this->getData('delivery_type')) {
            case Bimp_CommandeFourn::DELIV_ENTREPOT:
            default:
                $entrepot = $this->getChildObject('entrepot');
                if (BimpObject::objectLoaded($entrepot)) {
                    if ($entrepot->address) {
                        $tabAdd = explode("<br/>", $entrepot->address);
                        $result['adress'] = $tabAdd[0];
                        if (isset($tabAdd[1]))
                            $result['adress2'] = $tabAdd[1];
                        if (isset($tabAdd[2]))
                            $result['adress3'] = $tabAdd[2];
                        if ($entrepot->zip) {
                            $result['zip'] = $entrepot->zip;
                        } else {
                            $warnings[] = 'Code postal non défini';
                        }
                        if ($entrepot->town) {
                            $result['town'] = $entrepot->town;
                        } else {
                            $warnings[] = 'Ville non définie';
                        }
                    } else {
                        $warnings[] = 'Erreur: adresse non définie pour l\'entrepôt "' . $entrepot->label . ' - ' . $entrepot->lieu . '"';
                    }
                } elseif ((int) $this->getData('entrepot')) {
                    $warnings[] = 'Erreur: l\'entrepôt #' . $this->getData('entrepot') . ' n\'existe pas';
                } else {
                    $warnings[] = 'Entrepôt absent';
                }
                break;

            case Bimp_CommandeFourn::DELIV_SIEGE:
                global $mysoc;
                if (is_object($mysoc)) {
                    if ($mysoc->name) {
                        $result['name'] = $mysoc->name;
                    }
                    if ($mysoc->address) {
                        $result['adress'] = $mysoc->address;
                    }
                    if ($mysoc->zip) {
                        $result['zip'] = $mysoc->zip . ' ';
                    }
                    if ($mysoc->town) {
                        $result['town'] = $mysoc->town;
                    }
                } else {
                    $warnings[] = 'Erreur: Siège social non configuré';
                }
                break;

            case Bimp_CommandeFourn::DELIV_CUSTOM:
                $address = $this->getData('custom_delivery');
                if ($address) {
                    $address = str_replace("\r", "", $address);
                    $dataAdd = explode("\n", $address);

                    $result['name'] = $dataAdd[0];
                    $result['adress'] = $dataAdd[1];



                    if (count($dataAdd) >= 5 && count(explode(" ", $dataAdd[4])) > 1) {
                        $result['adress2'] = $dataAdd[2];
                        $result['adress3'] = $dataAdd[3];
                        $tabZipTown = explode(" ", $dataAdd[4]);
                        $town = str_replace($tabZipTown[0] . " ", "", $dataAdd[4]);
                        if (count($dataAdd) == 6)
                            $result['country'] = $dataAdd[5];
                    }elseif (count($dataAdd) >= 4 && count(explode(" ", $dataAdd[3])) > 1) {
                        $result['adress2'] = $dataAdd[2];
                        $tabZipTown = explode(" ", $dataAdd[3]);
                        $town = str_replace($tabZipTown[0] . " ", "", $dataAdd[3]);
                        if (count($dataAdd) == 5)
                            $result['country'] = $dataAdd[4];
                    }
                    elseif ((count($dataAdd) >= 3 && count(explode(" ", $dataAdd[2])) > 1)) {
                        $tabZipTown = explode(" ", $dataAdd[2]);
                        $town = str_replace($tabZipTown[0] . " ", "", $dataAdd[2]);
                        if (count($dataAdd) == 4)
                            $result['country'] = $dataAdd[3];
                    } else
                        $warnings[] = "Impossible de parser l'adresse personalisée";
                    if (count($tabZipTown) > 1) {
                        $result['zip'] = $tabZipTown[0];
                        $result['town'] = $town;
                    }
                    if (strlen($result['zip']) != 5)
                        $warnings[] = "Code postal : " . $result['zip'] . ' incorrect';
                } else {
                    $warnings[] = 'Adresse non renseignée';
                }
                break;

            case Bimp_CommandeFourn::DELIV_DIRECT:
                $id_contact = (int) $this->getIdContact();
                if ($id_contact) {
                    $contact = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $id_contact);
                    if (BimpObject::objectLoaded($contact)) {
                        $soc = $contact->getParentInstance();
                        if (BimpObject::objectLoaded($soc) && $soc->isCompany()) {
                            $result['name'] = $soc->getData('nom');
                        }
                        $result['contact'] = $contact->getData('firstname') . ' ' . $contact->getData('lastname');
                        $result['adress'] = $contact->getData('address');
                        $result['zip'] = $contact->getData('zip') . ' ';
                        $result['town'] = $contact->getData('town');
                        $result['country'] = $contact->displayCountry();
                    } else {
                        $warnings[] = 'Le contact d\'ID ' . $id_contact . ' n\'existe pas';
                    }
                } else {
                    $warnings[] = 'Contact livraison directe absent';
                }
                break;
        }
        return $result;
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

    public function hasReceptions()
    {
        if ($this->isLoaded()) {
            $receptions = BimpCache::getBimpObjectList('bimplogistique', 'BL_CommandeFournReception', array(
                        'id_commande_fourn' => (int) $this->id
            ));

            return (int) (count($receptions) ? 1 : 0);
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
                } elseif (in_array((int) $this->getData('fk_statut'), array(2, 3))) {
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
                } elseif (in_array((int) $this->getData('fk_statut'), array(6, 7, 9))) {
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
                if ((int) $user->admin) {
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
            $status = (int) $this->getData('fk_statut');

            // Valider: 
            $errors = array();
            if ($this->isActionAllowed('validate', $errors)) {
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
                    $msg = 'Vous n\'avez pas la permission de valider ' . $this->getLabel('this');
                }
            } elseif ($status === 0) {
                $msg = BimpTools::getMsgFromArray($errors);
            }

            if ($msg) {
                $buttons[] = array(
                    'label'    => 'Valider',
                    'icon'     => 'fas_check',
                    'onclick'  => '',
                    'disabled' => 1,
                    'popover'  => $msg
                );
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
                        'popover'  => 'Vous n\\\'avez pas la permission de ' . $perm_label
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


                if ($this->getData('fk_soc') == $this->idLdlc) {
                    $onclick = $this->getJsActionOnclick('makeOrderEdi', array(), array(
                    ));
                    $buttons[] = array(
                        'label'   => 'Commander en EDI',
                        'icon'    => 'fas_arrow-circle-right',
                        'onclick' => $onclick,
                    );
                }
            }

            if ($this->getData('fk_statut') == 3) {
                if ($this->getData('fk_soc') == $this->idLdlc && $this->canSetAction('makeOrder')) {
                    $onclick = $this->getJsActionOnclick('verifMajLdlc', array(), array());
                    $buttons[] = array(
                        'label'   => 'MAJ EDI',
                        'icon'    => 'fas_arrow-circle-right',
                        'onclick' => $onclick,
                    );
                }
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
                $receptions_facturables = $this->getFactureReceptionsArray();

                $values = array();
                foreach ($receptions_facturables as $id_reception => $reception_label) {
                    $values[] = $id_reception;
                }

                if (!empty($values)) {
                    $onclick = $this->getJsActionOnclick('createInvoice', array(
                        'ref_supplier'      => $this->getData('ref_supplier'),
                        'libelle'           => $this->getData('libelle'),
                        'id_cond_reglement' => (int) $this->getData('fk_cond_reglement'),
                        'id_mode_reglement' => (int) $this->getData('fk_mode_reglement'),
                        'receptions'        => json_encode($values)
                            ), array(
                        'form_name' => 'invoice'
                    ));

                    $buttons[] = array(
                        'label'   => 'Facturer des réceptions',
                        'icon'    => 'fas_file-invoice-dollar',
                        'onclick' => $onclick,
                    );
                } else {
                    $buttons[] = array(
                        'label'    => 'Facturer des réceptions',
                        'icon'     => 'fas_file-invoice-dollar',
                        'onclick'  => '',
                        'disabled' => 1,
                        'popover'  => 'Aucune réception validée non facturée'
                    );
                }
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
                    $data = array(
                        'reception_status' => -1,
                        'invoice_status'   => -1
                    );
                    $forced = $this->getData('status_forced');

                    if (isset($forced['reception']) && (int) $forced['reception']) {
                        $data['reception_status'] = (int) $this->getData('fk_statut');
                    }
                    if (isset($forced['invoice']) && (int) $forced['invoice']) {
                        $data['invoice_status'] = (int) $this->getData('invoice_status');
                    }

                    $buttons[] = array(
                        'label'   => 'Forcer un statut',
                        'icon'    => 'far_check-square',
                        'onclick' => $this->getJsActionOnclick('forceStatus', $data, array(
                            'form_name' => 'force_status'
                        ))
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

    public function getReceptionsArray($include_empty = false, $draft_only = false)
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

        $cache_key = 'commande_fourn_' . $this->id . '_receptions';

        if ($draft_only) {
            $cache_key .= '_draft_only';
        }

        $cache_key .= '_array';

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            $filters = array();

            if ($draft_only) {
                $filters['status'] = 0;
            }

            $receptions = $this->getChildrenObjects('receptions', $filters, 'id', 'desc');
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

    public function getFactureReceptionsArray($id_facture = 0)
    {
        if ($this->isLoaded()) {
            BimpObject::loadClass('bimplogistique', 'BL_CommandeFournReception');

            $receptions = $this->getChildrenObjects('receptions', array(
                'status'     => BL_CommandeFournReception::BLCFR_RECEPTIONNEE,
                'id_facture' => (int) $id_facture
            ));

            $items = array();
            foreach ($receptions as $reception) {
                $items[(int) $reception->id] = $reception->getData('num_reception') . ' - ' . $reception->getData('ref');
            }
            return $items;
        }

        return array();
    }

    public function getFacturesFournisseurArray()
    {
        $return = array(
            0 => 'Nouvelle facture'
        );

        $factures = BimpCache::getBimpObjectObjects('bimpcommercial', 'Bimp_FactureFourn', array(
                    'fk_statut' => 0,
                    'fk_soc'    => (int) $this->getData('fk_soc'),
                    /* 'entrepot'  => (int) $this->getData('entrepot'), */
                    'ef_type'   => $this->getData('ef_type')
        ));

        foreach ($factures as $facture) {
            $dt = new DateTime($facture->getData('datec'));
            $return[(int) $facture->id] = $facture->getRef() . ' Créée le ' . $dt->format('d / m / Y');
        }

        return $return;
    }

    public function getPostReceptionsArray()
    {
        $receptions = BimpTools::getPostFieldValue('receptions', array());

        $array = array();
        foreach ($receptions as $id_reception) {
            $reception = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeFournReception', (int) $id_reception);
            if (BimpObject::objectLoaded($reception)) {
                $commande = $reception->getParentInstance();

                $label = '';

                if (BimpObject::objectLoaded($commande)) {
                    $label .= 'Commande ' . $commande->getRef() . ' - réception n°' . $reception->getData('num_reception');
                    if ($reception->getData('ref')) {
                        $label .= ' (ref: ' . $reception->getRef() . ')';
                    }
                }
                $array[(int) $id_reception] = $label;
            }
        }

        return $array;
    }

    public static function getTypesEntrepotArray()
    {
        if (empty(static::$types_entrepot)) {
            self::loadClass('bimpequipment', 'BE_Place');
            foreach (BE_Place::$types as $key => $label) {
                if (in_array($key, BE_Place::$entrepot_types)) {
                    static::$types_entrepot[$key] = $label;
                }
            }
        }

        return static::$types_entrepot;
    }

    // Rendus HTML - overrides BimpObject:

    public function renderHeaderExtraLeft()
    {
        $html = '';

        if ($this->isLoaded()) {
            $html .= '<div class="object_header_infos">';
            $fourn = $this->getChildObject("fournisseur");
            $html .= $fourn->getLink();
            $html .= '</div>';

            $html .= '<div class="object_header_infos">';
            $html .= 'Créée le <strong>' . $this->displayData('date_creation', 'default', false, true) . '</strong>';

            $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $this->getData('fk_user_author'));
            if (BimpObject::objectLoaded($user)) {
                $html .= ' par&nbsp;&nbsp;' . $user->getLink();
            }

            $html .= '</div>';

            if ((int) $this->getData('fk_user_valid')) {
                $html .= '<div class="object_header_infos">';
                $html .= 'Validée le <strong>' . $this->displayData('date_valid', 'default', false, true) . '</strong>';
                $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $this->getData('fk_user_valid'));
                if (BimpObject::objectLoaded($user)) {
                    $html .= ' par&nbsp;&nbsp;' . $user->getLink();
                }
                $html .= '</div>';
            }
            if ((int) $this->getData('fk_user_approve')) {
                $html .= '<div class="object_header_infos">';
                $html .= '1ère approbation le <strong>' . $this->displayData('date_approve', 'default', false, true) . '</strong>';
                $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $this->getData('fk_user_approve'));
                if (BimpObject::objectLoaded($user)) {
                    $html .= ' par&nbsp;&nbsp;' . $user->getLink();
                }
                $html .= '</div>';
            }
            if ((int) $this->getData('fk_user_approve2')) {
                $html .= '<div class="object_header_infos">';
                $html .= '2ème approbation le <strong>' . $this->displayData('date_approve2', 'default', false, true) . '</strong>';
                $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $this->getData('fk_user_approve2'));
                if (BimpObject::objectLoaded($user)) {
                    $html .= ' par&nbsp;&nbsp;' . $user->getLink();
                }
                $html .= '</div>';
            }
            if ((int) $this->getData('fk_user_resp')) {
                $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $this->getData('fk_user_resp'));
                if (BimpObject::objectLoaded($user)) {
                    $html .= '<div class="object_header_infos">';
                    $html .= 'Personne en charge:&nbsp;&nbsp;';
                    $user->fetch((int) $this->getData('fk_user_resp'));
                    $html .= $user->getLink();
                    $html .= '</div>';
                }
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
        $html = parent::renderHeaderStatusExtra();

        $forced = $this->getData('status_forced');

        if (isset($forced['reception']) && (int) $forced['reception']) {
            $html .= ' (forcé)';
        }

        if ((int) $this->getData('invoice_status') > 0) {
            $html .= '<span>' . $this->displayData('invoice_status') . '</span>';

            if (isset($forced['invoice']) && (int) $forced['invoice']) {
                $html .= ' (forcé)';
            }
        }
        if ((int) $this->getData('attente_info')) {
            $html .= '<br/><span class="warning">' . BimpRender::renderIcon('fas_hourglass-start', 'iconLeft') . 'Attente Infos</span>';
        }

        return $html;
    }

    public function renderMarginsTable()
    {
        return '';
    }

    // Traitements:

    public function createFacture()
    {
        
    }

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

    public function checkReceptionStatus($log_change = false)
    {
        $status_forced = $this->getData('status_forced');

        if (isset($status_forced['reception']) && (int) $status_forced['reception']) {
            return;
        }

        $current_status = (int) $this->getData('fk_statut');

        if (in_array($current_status, array(0, 1, 2, 6, 7, 9))) {
            return;
        }

        BimpObject::loadClass('bimpcommercial', 'ObjectLine');

        $lines = $this->getLines('not_text');

        $hasReception = 0;
        $isFullyReceived = 1;

        foreach ($lines as $line) {
            $received_qty = (float) $line->getReceivedQty(null, true);
            if (abs($received_qty) > 0) {
                $hasReception = 1;
            }

            if (abs($received_qty) < abs((float) $line->getFullQty())) {
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

            if ($log_change) {
                BimpCore::addlog('Correction auto du statut Réception', Bimp_Log::BIMP_LOG_NOTIF, 'bimpcore', $this, array(
                    'Ancien statut'  => $current_status,
                    'Nouveau statut' => $new_status
                ));
            }
        }
    }

    public function checkInvoiceStatus($log_change = false)
    {
        if (!$this->isLoaded()) {
            return;
        }

        $status_forced = $this->getData('status_forced');

        if (isset($status_forced['invoice']) && (int) $status_forced['invoice']) {
            return;
        }

        $invoice_status = 0;

        BimpObject::loadClass('bimplogistique', 'BL_CommandeFournReception');

        $lines = $this->getLines('not_text');

        $receptions = $this->getChildrenList('receptions', array(
            'status'     => BL_CommandeFournReception::BLCFR_RECEPTIONNEE,
            'id_facture' => array(
                'operator' => '>',
                'value'    => 0
            )
        ));

        if (count($lines)) {
            $has_billed = 0;
            $all_billed = 1;

            foreach ($lines as $line) {
                $line_qty = (float) $line->getFullQty();
                $billed_qty = 0;

                foreach ($receptions as $id_reception) {
                    $reception = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeFournReception', (int) $id_reception);
                    if (BimpObject::objectLoaded($reception)) {
                        $fac = $reception->getChildObject('facture_fourn');
                        if (BimpObject::objectLoaded($fac)) {
                            $reception_data = $line->getReceptionData((int) $id_reception);
                            $billed_qty += isset($reception_data['qty']) ? (float) $reception_data['qty'] : 0;
                            $has_billed = 1;
                        }
                    }
                }

                if (abs($line_qty) > abs($billed_qty)) {
                    $all_billed = 0;
                }
            }

            if ($all_billed) {
                $invoice_status = 2;
            } elseif ($has_billed) {
                $invoice_status = 1;
            } else {
                $invoice_status = 0;
            }
        } else {
            $invoice_status = 0;
        }

        if ($invoice_status !== (int) $this->getInitData('invoice_status')) {
            if ($log_change) {
                BimpCore::addlog('Correction auto du statut Facturation', Bimp_Log::BIMP_LOG_NOTIF, 'bimpcore', $this, array(
                    'Ancien statut'  => (int) $this->getInitData('invoice_status'),
                    'Nouveau statut' => $invoice_status
                ));
            }
            $this->updateField('invoice_status', $invoice_status);
        }

        if ((int) $all_billed !== (int) $this->getInitData('billed')) {
            $this->updateField('billed', (int) $all_billed);
        }
    }

    public function onValidate(&$warnings = array())
    {
        if ($this->isLoaded($warnings)) {
            // Mise à jour des PA courant des produits: 
            $products = array();

            $lines = $this->getLines('not_text');
            foreach ($lines as $line) {
                if (!(int) $line->id_product) {
                    continue;
                }

                if (!isset($products[(int) $line->id_product])) {
                    $products[(int) $line->id_product] = 0;
                }

                if ((float) $line->pu_ht > $products[(int) $line->id_product]) {
                    $products[(int) $line->id_product] = (float) $line->pu_ht;
                }
            }
            $fk_soc = (int) $this->getData('fk_soc');

            foreach ($products as $id_product => $pa_ht) {
                if (!$pa_ht) {
                    continue;
                }

                $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $id_product);

                if ((int) $product->getData('no_fixe_prices')) {
                    continue;
                }

                $id_fp = 0;

                if (BimpObject::objectLoaded($product)) {
                    $id_fp = (int) $product->findFournPriceIdForPaHt($pa_ht, $fk_soc);
                }

                $product->setCurrentPaHt($pa_ht, $id_fp, 'commande_fourn', (int) $this->id);
            }

            // Mise à jour des PA des lignes de factures associées: 
            foreach ($lines as $line) {
                $line->checkFactureClientLinesPA();
            }
        }

        return array();
    }

    public function traitePdfFactureFtp($conn, $facNumber)
    {
        $folder = "/FTP-BIMP-ERP/invoices";
        $tab = ftp_nlist($conn, $folder);
        $errors = array();

        $list = $this->getFilesArray();
        $ok = false;
        foreach ($list as $nom) {
            if (stripos($nom, (string) $facNumber))
                $ok = true;
        }
        if (!$ok) {
            $newName = (string) $facNumber . ".pdf";
            foreach ($tab as $fileEx) {
                if (!$ok && stripos($fileEx, (string) $facNumber) !== false) {
                    ftp_get($conn, $this->getFilesDir() . "/" . $newName, $fileEx, FTP_BINARY);
                    $this->addNote('Le fichier PDF fournisseur ' . $newName . ' à été ajouté.');
                    $ok = true;
                    if ($this->getData('entrepot') == 164) {
                        mailSyn2("Nouvelle facture LDLC", 'AchatsBimp@bimp.fr', null, "Bonjour la facture " . $facNumber . " de la commande : " . $this->getLink() . " en livraison direct a été téléchargé");
                    }
                }
            }
            if (!$ok) {
                $errors[] = "Fichier " . $newName . ' introuvable';
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
                $result['warnings'] = BimpTools::merge_array($result['warnings'], $result2['warnings']);
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



        $lines = $this->getLines('not_text');
        foreach ($lines as $line) {
            $prod = $line->getChildObject('product');
            if ($prod->isLoaded())
                if (!$prod->isAchetable($errors, false, false))
                    return array('errors' => $errors);
        }

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
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'bimp_reloadPage();'
        );
    }

    public function actionVerifMajLdlc($data, &$success)
    {
        $tabConvertionStatut = array("processing" => 95, "shipped" => 100, "billing" => 105, "canceled" => -100, "deleted" => -105);

        $success .= '<br/>Commandes MAJ';
        $errors = array();

//            error_reporting(E_ALL);
//            ini_set('display_errors', 1);
        $url = "ftp-edi.groupe-ldlc.com";
        $login = "bimp-erp";
        $mdp = "MEDx33w+3u(";
        $folder = "/FTP-BIMP-ERP/tracing/";


//            $url = "exportftp.techdata.fr";
//            $login = "bimp";
//            $mdp = "=bo#lys$2003";
//            $folder = "/";
        if ($conn = ftp_connect($url)) {
            if (ftp_login($conn, $login, $mdp)) {
                ftp_pasv($conn, 0);
                // Change the dir
//                    if(ftp_chdir($conn, $folder)){
                $tab = ftp_nlist($conn, $folder);

                foreach ($tab as $fileEx) {
                    if (stripos($fileEx, '.xml') !== false) {
                        $errorLn = array();
                        $dir = PATH_TMP . "/bimpcore/";
                        $file = "tmpftp.xml";
                        if (ftp_get($conn, $dir . $file, $fileEx, FTP_BINARY)) {
                            if (!stripos($fileEx, ".xml"))
                                continue;
                            $data = simplexml_load_string(file_get_contents($dir . $file));


                            if (isset($data->attributes()['date'])) {
                                $date = (string) $data->attributes()['date'];
                                $type = (string) $data->attributes()['type'];
                                $ref = (string) $data->Stream->Order->attributes()['external_identifier'];

                                $commFourn = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn');
                                if ($commFourn->find(['ref' => $ref])) {
                                    $statusCode = (isset($data->attributes()['statuscode'])) ? -$data->attributes()['statuscode'] : 0;
                                    if ($statusCode < 0 && isset(static::$edi_status[(int) $statusCode]))
                                        $errorLn[] = 'commande en erreur ' . $ref . ' Erreur : ' . static::$edi_status[(int) $statusCode]['label'];
                                    elseif ($type == "error")
                                        $errorLn[] = 'commande en erreur ' . $ref . ' Erreur Inconnue !!!!!';

                                    if ($type == "acknowledgment")
                                        $statusCode = 91;

                                    $statusCode2 = $data->Stream->Order->attributes()['status'];

                                    if ($statusCode2 != '') {
                                        if (isset($tabConvertionStatut[(string) $statusCode2]))
                                            $statusCode = $tabConvertionStatut[(string) $statusCode2];
                                        else
                                            $errorLn[] = "Statut LDLC inconnue |" . $statusCode2 . "|";
                                    }

                                    $prods = (array) $data->Stream->Order->Products;
                                    $total = 0;

                                    if (!is_array($prods['Item']))
                                        $prods['Item'] = array($prods['Item']);
                                    foreach ($prods['Item'] as $prod) {
                                        $total += (float) $prod->attributes()['quantity'] * (float) $prod->attributes()['unitPrice'];
                                    }
                                    $diference = abs($commFourn->getData('total_ht') - $total);
                                    if ($diference > 0.08) {
                                        $statusCode = -50;
                                    }


                                    if (isset($data->Stream->Order->attributes()['identifier']) && $data->Stream->Order->attributes()['identifier'] != '') {
                                        if (stripos($commFourn->getData('ref_supplier'), (string) $data->Stream->Order->attributes()['identifier']) === false)
                                            $commFourn->updateField('ref_supplier', ($commFourn->getData('ref_supplier') == "" ? '' : $commFourn->getData('ref_supplier') . " ") . $data->Stream->Order->attributes()['identifier']);
                                    }

                                    if (isset($data->Stream->Order->attributes()['invoice']) && $data->Stream->Order->attributes()['invoice'] != '') {
                                        if (stripos($commFourn->getData('ref_supplier'), (string) $data->Stream->Order->attributes()['invoice']) === false)
                                            $errorLn = BimpTools::merge_array($errorLn, $commFourn->updateField('ref_supplier', ($commFourn->getData('ref_supplier') == "" ? '' : $commFourn->getData('ref_supplier') . " ") . $data->Stream->Order->attributes()['invoice']));
                                        $errorLn = BimpTools::merge_array($errorLn, $commFourn->traitePdfFactureFtp($conn, $data->Stream->Order->attributes()['invoice']));
                                    }


                                    $colis = array();
                                    if (isset($data->Stream->Order->Parcels)) {
                                        $parcellesBrut = (array) $data->Stream->Order->Parcels;
                                        if (!is_array($parcellesBrut['Parcel']))
                                            $parcellesBrut['Parcel'] = array($parcellesBrut['Parcel']);
                                        $notes = $commFourn->getNotes();
                                        foreach ($parcellesBrut['Parcel'] as $parcel) {
                                            $text = 'Colis : ' . (string) $parcel->attributes()['code'] . ' de ' . (string) $parcel->attributes()['service'];
                                            if (isset($parcel->attributes()['TrackingUrl']) && $parcel->attributes()['TrackingUrl'] != '')
                                                $text = '<a target="_blank" href="' . (string) $parcel->attributes()['TrackingUrl'] . '">' . $text . "</a>";
                                            $noteOK = false;
                                            foreach ($notes as $note) {
                                                if ($noteOK)
                                                    continue;
                                                if (stripos($note->getData('content'), $text) !== false)
                                                    $noteOK = true;
                                            }
                                            if (!$noteOK)
                                                $commFourn->addNote($text, null, 1);
                                        }
                                    }


                                    if ($commFourn->getData('edi_status') != $statusCode) {
                                        $commFourn->updateField('edi_status', (int) $statusCode);
                                        $commFourn->addNote('Changement de statut EDI : ' . static::$edi_status[(int) $statusCode]['label']);
                                    }



                                    if (count($colis))
                                        $success .= "<br/>" . count($colis) . " Colis envoyées ";

                                    $success .= "<br/>Comm : " . $ref . "<br/>Status " . static::$edi_status[(int) $statusCode]['label'];
                                }
                                else {
                                    $errorLn[] = 'pas de comm ' . $ref;
                                }
                            } else {
                                $errorLn[] = 'Structure XML non reconnue';
                            }
                            if (!count($errorLn)) {
                                ftp_rename($conn, $fileEx, str_replace("tracing/", "tracing/importedAuto/", $fileEx));
                            } else
                                ftp_rename($conn, $fileEx, str_replace("tracing/", "tracing/quarentaineAuto/", $fileEx));
                        }
                        $errors = BimpTools::merge_array($errors, $errorLn);
                    }
                }
            }

            ftp_close($conn);
        }


        return array(
            'errors'           => $errors,
            'warnings'         => array(),
            'success_callback' => ''
        );
    }

    public function actionMakeOrderEdi($data, &$success)
    {
        $success = "Commande OK";

        $errors = array();


//        $errors = BimpTools::merge_array($errors, $this->verifMajLdlc($data, $success));
        if ($this->getData("fk_soc") != $this->idLdlc)
            $errors[] = "Cette fonction n'est valable que pour LDLC";


        if (!count($errors)) {
            require_once DOL_DOCUMENT_ROOT . '/bimpdatasync_old/classes/BDS_ArrayToXml.php';
            $arrayToXml = new BDS_ArrayToXml();


            $products = array();

            $lines = $this->getLines('not_text');
            foreach ($lines as $line) {
                //            $line = new Bimp_CommandeFournLine();
                $prod = $line->getChildObject('product');
                if (is_object($prod) && $prod->isLoaded()) {
                    $diference = 999;
                    $ref = $prod->findRefFournForPaHtPlusProche($line->getUnitPriceHTWithRemises(), $this->idLdlc, $diference);


                    if (strpos($ref, "AR") !== 0)
                        $errors[] = "La référence " . $ref . "ne semble pas être une ref LDLC correct  pour le produit " . $prod->getLink();
                    elseif ($diference > 0.08)
                        $errors[] = "Prix de l'article " . $prod->getLink() . " différent du prix LDLC. Différence de " . price($diference) . " € vous ne pourrez pas passer la commande par cette méthode.";
                    else
                        $products[] = array("tag" => "Item", "attrs" => array("id" => $ref, "quantity" => $line->qty, "unitPrice" => round($line->getUnitPriceHTWithRemises(), 2), "vatIncluded" => "false"));
                } else
                    $errors[] = "Pas de produit pour la ligne " . $line->id;
            }


            global $mysoc;
            $adresseFact = array("tag"      => "Address", "attrs"    => array("type" => "billing"),
                "children" => array(
                    "ContactName"  => $mysoc->name,
                    "AddressLine1" => $arrayToXml->xmlentities($mysoc->address),
                    "AddressLine2" => "",
                    "AddressLine3" => "",
                    "City"         => $mysoc->town,
                    "ZipCode"      => $mysoc->zip,
                    "CountryCode"  => "FR",
                )
            );
            $dataLiv = $this->getAdresseLivraison($errors);
//            echo "<pre>";print_r($dataLiv);

            $name = ($dataLiv['name'] != '' ? $dataLiv['name'] . ' ' : '') . $dataLiv['contact'];
            if ($name == "")
                $name = "BIMP";
            $adresseLiv = array("tag"      => "Address", "attrs"    => array("type" => "shipping"),
                "children" => array(
                    "ContactName"  => substr($name, 0, 49),
                    "AddressLine1" => $arrayToXml->xmlentities($dataLiv['adress']),
                    "AddressLine2" => $arrayToXml->xmlentities($dataLiv['adress2']),
                    "AddressLine3" => $arrayToXml->xmlentities($dataLiv['adress3']),
                    "City"         => $arrayToXml->xmlentities($dataLiv['town']),
                    "ZipCode"      => $dataLiv['zip'],
                    "CountryCode"  => ($dataLiv['country'] != "FR" && $dataLiv['country'] != "") ? strtoupper(substr($dataLiv['country'], 0, 2)) : "FR",
                )
            );

            $portHt = $portTtc = 0;
            $shipping_mode = "";
            if (in_array($this->getData('delivery_type'), array(Bimp_CommandeFourn::DELIV_ENTREPOT, Bimp_CommandeFourn::DELIV_SIEGE)))
                $shipping_mode = "PNS6";
            $tab = array(
                array("tag"      => "Stream", "attrs"    => array("type" => "order", 'version' => "1.0"),
                    "children" => array(
                        array("tag"      => "Order", "attrs"    => array("date" => date("Y-m-d H:i:s"), 'reference' => $this->getData('ref'), "external_identifier" => $this->getData('ref'), "currency" => "EUR", "source" => "BIMP", "shipping_vat_on" => $portHt, "shipping_vat_off" => $portTtc, "shipping_mode" => $shipping_mode),
                            "children" => array(
                                array("tag"      => "Customer", "attrs"    => array("identifiedby" => "code", 'linked_entity_code' => "PRO"),
                                    "children" => array(
                                        "Owner"          => "FILI",
                                        "CustomerNumber" => "E69OLYSBI0095",
                                        "FirstName"      => "",
                                        "LastName"       => "",
                                        "PhoneNumber"    => "0812211211",
                                        "Email"          => "achat@bimp.fr",
                                        $adresseLiv,
                                        $adresseFact,
                                    )
                                ),
                                array("tag"      => "Products",
                                    "children" => $products
                                ),
                                $adresseLiv,
                                $adresseFact,
                            )
                        )
                    )
                )
            );
        }


        if (!count($errors)) {
            $arrayToXml->writeNodes($tab);

//            $remote_file = DOL_DATA_ROOT.'/importldlc/exportCommande/'.$this->getData('ref').'.xml';
//            $remote_file = "ftp://bimp-erp:MEDx33w+3u(@ftp-edi.groupe-ldlc.com/FTP-BIMP-ERP/orders/".$this->getData('ref').'.xml';


            $url = "ftp-edi.groupe-ldlc.com";
            $login = "bimp-erp";
            $mdp = "MEDx33w+3u(";

            if ($conn = ftp_connect($url)) {
                if (ftp_login($conn, $login, $mdp)) {
                    $localFile = PATH_TMP . '/bimpcore/tmpUpload.xml';
                    if (!file_put_contents($localFile, $arrayToXml->getXml()))
                        $errors[] = 'Probléme de génération du fichier';
                    $dom = new DOMDocument;
                    $dom->Load($localFile);
                    libxml_use_internal_errors(true);
                    if (!$dom->schemaValidate(DOL_DOCUMENT_ROOT . '/bimpcommercial/ldlc.orders.valid.xsd')) {
                        $errors[] = 'Ce document est invalide contactez l\'équipe dév';

                        BimpCore::addlog('Probléme CML LDLC', Bimp_Log::BIMP_LOG_ERREUR, 'bimpcore', $this, array(
                            'LIBXML Errors' => libxml_get_errors()
                        ));
                    }

                    if (!count($errors)) {
                        ftp_pasv($conn, 0);
                        if (!ftp_put($conn, "/FTP-BIMP-ERP/orders/" . $this->getData('ref') . '.xml', $localFile, FTP_BINARY))
                            $errors[] = 'Probléme d\'upload du fichier';
                        else {
                            //                        global $user;
                            //                        mailSyn2("Commande BIMP", "a.schlick@ldlc.pro, tommy@bimp.fr", $user->email, "Bonjour, la commande " . $this->getData('ref') . ' de chez bimp vient d\'être soumise, vous pourrez la valider dans quelques minutes ?');
                            $this->addNote('Commande passée en EDI');
                        }
                    }
                } else
                    $errors[] = 'Probléme de connexion LDLC';
            } else
                $errors[] = 'Probléme de connexion LDLC';

//            if(!file_put_contents($remote_file, $arrayToXml->getXml()))
//                    $errors[] = 'Probléme de génération du fichier';
//            die("<textarea>".$arrayToXml->getXml().'</textarea>fin');
        }


        if (!count($errors)) {
            $data['date_commande'] = date('Y-m-d');
            $data['fk_input_method'] = 7;
            return $this->actionMakeOrder($data, $success);
        } else
            return array(
                'errors'           => $errors,
                'warnings'         => array(),
                'success_callback' => ''
            );
    }

    public function actionMakeOrder($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Commande effectuée avec succès';

        if (!isset($data['date_commande']) || !$data['date_commande']) {
            $errors[] = 'Date de la commande absente' . $data['date_commande'];
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

        // Check des réceptions: 
        if (!isset($data['receptions']) || !is_array($data['receptions']) || empty($data['receptions'])) {
            $errors[] = 'Aucune réception sélectionnée';
        } else {
            $receptions_list = array();
            $base_commande = null;
            $other_commandes = array();
            $id_facture = (isset($data['id_facture']) ? (int) $data['id_facture'] : 0);
            $facture = null;

            if ($id_facture) {
                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureFourn', $id_facture);
                if (!BimpObject::objectLoaded($facture)) {
                    $errors[] = 'La facture fournisseur d\'ID ' . $id_facture . ' n\'existe pas';
                } elseif ((int) $facture->getData('fk_statut')) {
                    $errors[] = 'La facture fournisseur "' . $facture->getRef() . '" n\'est plus au statut "brouillon"';
                }
            }

            if (!count($errors)) {
                if ($this->isLoaded()) {
                    $base_commande = $this;
                }

                $fk_soc = 0;
                if (BimpObject::objectLoaded($facture)) {
                    $fk_soc = (int) $facture->getData('fk_soc');
                } elseif (BimpObject::objectLoaded($base_commande)) {
                    $fk_soc = (int) $base_commande->getData('fk_soc');
                } elseif (isset($data['fk_soc'])) {
                    $fk_soc = (int) $data['fk_soc'];
                }
            }

            if (!count($errors)) {
                foreach ($data['receptions'] as $id_reception) {
                    $reception = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeFournReception', (int) $id_reception);
                    if (!BimpObject::objectLoaded($reception)) {
                        $errors[] = 'La réception d\'ID ' . $id_reception . ' n\'existe pas';
                    } elseif ((int) $reception->getData('status') !== BL_CommandeFournReception::BLCFR_RECEPTIONNEE) {
                        $errors[] = 'La réception #' . $id_reception . ' (ref "' . $reception->getData('ref') . '") n\'est pas validée';
                    } elseif ((int) $reception->getData('id_facture')) {
                        $errors[] = 'La réception #' . $id_reception . ' (ref "' . $reception->getData('ref') . '") a déjà été facturée';
                    } else {
                        $parent_commande = $reception->getParentInstance();

                        if (!BimpObject::objectLoaded($parent_commande)) {
                            $errors[] = 'ID de la commande fournisseur absent pour la réception #' . $id_reception . ' (ref "' . $reception->getData('ref') . '")';
                        } else {
                            if (!BimpObject::objectLoaded($base_commande)) {
                                $base_commande = $parent_commande;
                                if (!$fk_soc) {
                                    $fk_soc = (int) $base_commande->getData('fk_soc');
                                }
                            } elseif ((int) $base_commande->id !== (int) $parent_commande->id) {
                                if (!array_key_exists((int) $parent_commande->id, $other_commandes)) {
                                    $other_commandes[(int) $parent_commande->id] = $parent_commande;
                                }
                            }

                            if ((int) $parent_commande->getData('fk_soc') !== (int) $fk_soc) {
                                $errors[] = 'Toutes les réceptions doivent provenir de commandes au même fournisseur';
                                break;
                            }

                            if (!isset($receptions_list[(int) $parent_commande->id])) {
                                $receptions_list[(int) $parent_commande->id] = array();
                            }
                            if (!in_array((int) $id_reception, $receptions_list[(int) $parent_commande->id])) {
                                $receptions_list[(int) $parent_commande->id][] = $reception->id;
                            }
                        }
                    }
                }

                if (!count($errors)) {
                    if (!$fk_soc) {
                        $errors[] = 'Aucun fournisseur trouvé';
                    } else {
                        $fourn = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Fournisseur', (int) $fk_soc);
                        if (!BimpObject::objectLoaded($fourn)) {
                            $errors[] = 'Le fournisseur d\'ID ' . $fk_soc . ' n\'existe pas';
                        }
                    }
                }
            }

            if (!count($errors)) {
                if (!BimpObject::objectLoaded($facture)) {

                    // Création de la facture: 
                    $ref_supplier = (isset($data['ref_supplier']) ? $data['ref_supplier'] : '');
                    $datef = (isset($data['datef']) ? $data['datef'] : date('Y-m-d'));
                    $date_lim_reglement = (isset($data['date_lim_reglement']) ? $data['date_lim_reglement'] : '');
                    $id_entrepot = (isset($data['id_entrepot']) ? (int) $data['id_entrepot'] : (int) $base_commande->getData('entrepot'));
                    $libelle = (isset($data['libelle']) ? $data['libelle'] : $base_commande->getData('libelle'));
                    $ef_type = (isset($data['ef_type']) ? $data['ef_type'] : $base_commande->getData('ef_type'));
                    $id_cond_reglement = (isset($data['id_cond_reglement']) ? (int) $data['id_cond_reglement'] : (int) $base_commande->getData('fk_cond_reglement'));
                    $id_mode_reglement = (isset($data['id_mode_reglement']) ? (int) $data['id_mode_reglement'] : (int) $base_commande->getData('fk_mode_reglement'));
                    $note_public = (isset($data['note_public']) ? $data['note_public'] : '');
                    $note_private = (isset($data['note_private']) ? $data['note_private'] : '');

                    $facture = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureFourn');

                    $errors = $facture->validateArray(array(
                        'libelle'            => $libelle,
                        'ef_type'            => $ef_type,
                        'entrepot'           => $id_entrepot,
                        'fk_soc'             => $fk_soc,
                        'ref_supplier'       => $ref_supplier,
                        'datef'              => $datef,
                        'date_lim_reglement' => $date_lim_reglement,
                        'fk_cond_reglement'  => $id_cond_reglement,
                        'fk_mode_reglement'  => $id_mode_reglement,
                        'note_public'        => $note_public,
                        'note_private'       => $note_private
                    ));

                    $facture->dol_object->linked_objects['order_supplier'] = array((int) $base_commande->id);
                    foreach ($other_commandes as $id_oth_comm => $oth_comm) {
                        $facture->dol_object->linked_objects['order_supplier'][] = $id_oth_comm;
                    }

                    if (!count($errors)) {
                        $fac_warnings = array();
                        $fac_errors = $facture->create($fac_warnings, true);

                        if (count($fac_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($fac_errors, 'Echec de la création de la facture');
                        }

                        if (count($fac_warnings)) {
                            $warnings[] = BimpTools::getMsgFromArray($fac_warnings, 'Erreurs suite à la création de la facture');
                        }
                    }
                } else {
                    $facture->dol_object->fetchObjectLinked();

                    if (!is_array($facture->dol_object->linked_objects['order_supplier'])) {
                        if (isset($facture->dol_object->linked_objects['order_supplier']) && (int) $facture->dol_object->linked_objects['order_supplier']) {
                            $facture->dol_object->linked_objects['order_supplier'] = array($facture->dol_object->linked_objects['order_supplier']);
                        } else {
                            $facture->dol_object->linked_objects['order_supplier'] = array();
                        }
                    }

                    if (!in_array((int) $base_commande->id, $facture->dol_object->linked_objects['order_supplier'])) {
                        $facture->dol_object->add_object_linked('order_supplier', (int) $base_commande->id);
                    }

                    foreach ($other_commandes as $id_oth_comm => $oth_comm) {
                        if (!in_array((int) $id_oth_comm, $facture->dol_object->linked_objects['order_supplier'])) {
                            $facture->dol_object->add_object_linked('order_supplier', (int) $id_oth_comm);
                        }
                    }

                    $facture->dol_object->fetchObjectLinked();
                }

                if (!count($errors) && BimpObject::objectLoaded($facture)) {

                    // Ajout des lignes à la facture: 
                    $addOrigineLine = (isset($data['add_origin_line']) ? (int) $data['add_origin_line'] : 0);

                    $i = 0;
                    foreach ($receptions_list as $id_commande => $commande_receptions_list) {
                        $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', (int) $id_commande);

                        if ($addOrigineLine) {
                            $new_line = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureFournLine');
                            $new_line->validateArray(array(
                                'type'   => ObjectLine::LINE_TEXT,
                                'id_obj' => (int) $facture->id,
                            ));

                            $new_line->desc = 'Selon la commande fournisseur ' . $commande->getRef();
                            $w = array();
                            $new_line->create($w, true);
                        }

                        $lines = $commande->getLines('not_text');
                        foreach ($lines as $line) {
                            $lines_data = $line->getLinesDataByUnitPriceAndTva($commande_receptions_list);
                            if (!empty($lines_data)) {
                                $isSerialisable = $line->isProductSerialisable();
                                $isReturn = ((float) $line->getFullQty() < 0);

                                foreach ($lines_data as $pu_ht => $pu_data) {
                                    foreach ($pu_data as $tva_tx => $line_data) {
                                        $i++;
                                        if ($isSerialisable) {
                                            if (!$isReturn) {
                                                $qty = count($line_data['equipments']);
                                            } else {
                                                $qty = count($line_data['equipments']) * -1;
                                            }
                                        } else {
                                            $qty = (float) $line_data;
                                        }

                                        $pu_ht = (float) $pu_ht;
                                        $tva_tx = (float) $tva_tx;
                                        $fac_line = null;

                                        // Recherche d'une ligne existante: 
                                        $fac_instance = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureFournLine');

                                        $rows = $fac_instance->getList(array(
                                            'a.id_obj'             => (int) $facture->id,
                                            'a.linked_object_name' => 'commande_fourn_line',
                                            'a.linked_id_object'   => (int) $line->id,
                                            'dl.fk_product'        => (int) $line->id_product,
                                            'dl.pu_ht'             => $pu_ht,
                                            'dl.tva_tx'            => $tva_tx
                                                ), null, null, 'id', 'asc', 'array', array('id'), array(
                                            'dl' => array(
                                                'table' => 'facture_fourn_det',
                                                'alias' => 'dl',
                                                'on'    => 'dl.rowid = a.id_line'
                                            )
                                        ));

                                        if (!is_null($rows)) {
                                            foreach ($rows as $r) {
                                                $fac_line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureFournLine', (int) $r['id']);
                                                if (BimpObject::objectLoaded($fac_line)) {
                                                    break;
                                                }
                                            }
                                        }

                                        // Si ligne trouvée, ajout des qtés: 
                                        $line_errors = array();
                                        $line_warnings = array();
                                        if (BimpObject::objectLoaded($fac_line)) {
                                            $fac_line->qty += (float) $qty;
                                            $line_errors = $fac_line->update($line_warnings, true);

                                            if (count($line_errors)) {
                                                $warnings[] = BimpTools::getMsgFromArray($line_warnings, 'Echec de la mise à jour des quantités de la ligne de facture n°' . $fac_line->getData('position'));
                                            }

                                            if (count($line_warnings)) {
                                                $warnings[] = BimpTools::getMsgFromArray($line_warnings, 'Erreurs suite à la mise à jour des quantités de la ligne de facture n°' . $fac_line->getData('position'));
                                            }
                                        } else {
                                            // Création d'une nouvelle ligne: 
                                            $fac_line = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureFournLine');
                                            $fac_line->validateArray(array(
                                                'id_obj'             => (int) $facture->id,
                                                'type'               => $line->getData('type'),
                                                'deletable'          => 0,
                                                'editable'           => 1,
                                                'linked_object_name' => 'commande_fourn_line',
                                                'linked_id_object'   => (int) $line->id,
                                                'remisable'          => $line->getData('remisable')
                                            ));

                                            $fac_line->desc = $line->desc;
                                            $fac_line->tva_tx = $tva_tx;
                                            $fac_line->id_product = $line->id_product;
                                            $fac_line->qty = $qty;
                                            $fac_line->pu_ht = $pu_ht;
                                            $fac_line->pa_ht = $line->pa_ht;
                                            $fac_line->id_fourn_price = $line->id_fourn_price;
                                            $fac_line->date_from = $line->date_from;
                                            $fac_line->date_to = $line->date_to;
                                            $fac_line->id_remise_except = $line->id_remise_except;

                                            $line_errors = $fac_line->create($line_warnings, true);
                                            if (count($line_errors)) {
                                                $warnings[] = BimpTools::getMsgFromArray($line_errors, 'Echec de la création de la ligne de facture n°' . $i);
                                                continue;
                                            }

                                            if (count($line_warnings)) {
                                                $warnings[] = BimpTools::getMsgFromArray($line_warnings, 'Erreurs suite à la création de la ligne de facture n°' . $i);
                                            }
                                        }

                                        /* NOTE: on n'intègre pas les remises de la ligne de commande: celles-ci sont déjà déduites dans le pu_ht. */

                                        // Ajout des équipements: 
                                        if (!count($line_errors) && $isSerialisable) {
                                            $equipments = (isset($line_data['equipments']) ? $line_data['equipments'] : array());
                                            foreach ($equipments as $id_equipment) {
                                                $eq_errors = $fac_line->attributeEquipment((int) $id_equipment);
                                                if (count($eq_errors)) {
                                                    $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                                                    if (BimpObject::objectLoaded($equipment)) {
                                                        $eq_label = '"' . $equipment->getData('serial') . '"';
                                                    } else {
                                                        $eq_label = 'd\'ID ' . $id_equipment;
                                                    }
                                                    $warnings[] = BimpTools::getMsgFromArray($eq_errors, 'Ligne n°' . $i . ': échec de l\'attribution de l\'équipement ' . $eq_label);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        $asso = new BimpAssociation($commande, 'factures');
                        $asso->addObjectAssociation($facture->id);

                        foreach ($commande_receptions_list as $id_reception) {
                            $reception = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeFournReception', (int) $id_reception);

                            if (BimpObject::objectLoaded($reception)) {
                                $recep_errors = $reception->updateField('id_facture', (int) $facture->id);

                                if (count($recep_errors)) {
                                    $warnings[] = BimpTools::getMsgFromArray($recep_errors, 'Echec de l\'enregistrement de l\'ID facture pour la réception n°' . $reception->getData('num_reception'));
                                }
                            }
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

        $status_forced = $this->getData('status_forced');

        if (isset($data['reception_status'])) {
            if (!in_array((int) $data['reception_status'], array(-1, 3, 4, 5))) {
                $errors[] = 'Statut réception invalide';
            } else {
                if ((int) $data['reception_status'] === -1) {
                    if (isset($status_forced['reception'])) {
                        unset($status_forced['reception']);
                    }
                } else {
                    if (!in_array((int) $this->getData('fk_statut'), array(3, 4, 5))) {
                        $errors[] = 'Le statut actuel de la commande fournisseur ne permet pas de forcer le statut de la réception';
                    } else {
                        $status_forced['reception'] = 1;
                        $sub_errors = $this->updateField('fk_statut', (int) $data['reception_status']);
                        if (count($sub_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($sub_errors, 'Echec de la mise à jour du statut réception de la commande fournisseur');
                        }
                    }
                }
            }
        }

        if (isset($data['invoice_status'])) {
            if (!in_array((int) $data['invoice_status'], array(-1, 0, 1, 2))) {
                $errors[] = 'Statut facturation invalide';
            } else {
                if ((int) $data['invoice_status'] === -1) {
                    if (isset($status_forced['invoice'])) {
                        unset($status_forced['invoice']);
                    }
                } else {
                    $status_forced['invoice'] = 1;
                    $sub_errors = $this->updateField('invoice_status', (int) $data['invoice_status']);
                    if (count($sub_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($sub_errors, 'Echec de la mise à jour du statut réception de la commande fournisseur');
                    } else {
                        if ($data['invoice_status'] == 2) {
                            $this->updateField("billed", 1);
                        } else {
                            $this->updateField("billed", 0);
                        }
                    }
                }
            }
        }

        $errors = $this->updateField('status_forced', $status_forced);
        $this->checkReceptionStatus();
        $this->checkInvoiceStatus();

        $lines = $this->getLines('not_text');
        foreach ($lines as $line) {
            $line->checkQties();
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionAddContact($data, &$success)
    {
        $errors = array();

        if (isset($data['type']) && (int) $data['type'] === 1) {
            if (isset($data['tiers_type_contact']) && (int) $data['tiers_type_contact'] &&
                    BimpTools::getTypeContactCodeById((int) $data['tiers_type_contact']) === 'SHIPPING') {
                if ((int) $this->getIdContact()) {
                    $errors[] = 'Un contact livraison a déjà été ajouté';
                }
            }
        }

        if (count($errors)) {
            return array(
                'errors'   => $errors,
                'warnings' => array()
            );
        }

        return parent::actionAddContact($data, $success);
    }

    // Overrides - BimpComm: 

    public function checkObject($context = '', $field = '')
    {
        if ($context === 'fetch') {
            global $current_bc, $modeCSV;
            if ((is_null($current_bc) || !is_a($current_bc, 'BC_List')) &&
                    (is_null($modeCSV) || !$modeCSV)) {
                $this->checkReceptionStatus(false);
                $this->checkInvoiceStatus(false);
            }
        }

        if ($context === 'render_msgs') {
            $this->msgs['warnings'] = array();

            switch ((int) $this->getData('delivery_type')) {
                case self::DELIV_CUSTOM:
                    if (!(string) $this->getData('custom_delivery')) {
                        $this->msgs['warnings'][] = 'Attention: adresse personnalisée non spécifiée';
                    }
                    break;

                case self::DELIV_DIRECT:
                    if (!(int) $this->getIdContact()) {
                        $this->msgs['warnings'][] = 'Veullez ajouter un contact livraison';
                    }
                    break;
            }
        }
    }

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

        $new_data['status_forced'] = array();

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

        $this->updateField('model_pdf', $this->getData('model_pdf'));

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

        // Mise à jour des champs Bimp_CommandeFourn:
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

    // Méthodes statiques: 

    public static function checkStatusAll()
    {
        $rows = self::getBdb()->getRows('commande_fournisseur', 'fk_statut > 0 AND fk_statut < 6', null, 'array', array('rowid'));

        if (!is_null($rows)) {
            foreach ($rows as $r) {
                $comm = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFourn', (int) $r['rowid']);

                if (BimpObject::objectLoaded($comm)) {
                    $comm->checkReceptionStatus(true);
                    $comm->checkInvoiceStatus(true);
                }
            }
        }
    }
}
