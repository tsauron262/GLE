<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/ObjectLine.class.php';

class Bimp_CommandeLine extends ObjectLine
{

    public static $parent_comm_type = 'commande';
    public static $dol_line_table = 'commandedet';
    public static $dol_line_parent_field = 'fk_commande';
    public static $reservations_ordered_status = array(3, 100);
    public static $notShippableLines = array();
    public static $notEditableInLogistiqueLines = array('discount');
    public static $periodicities = array(
        0  => 'Aucune',
        1  => 'Mensuelle',
        2  => 'Bimensuelle',
        3  => 'Trimestrielle',
        4  => 'Quadrimestrielle',
        6  => 'Semestrielle',
        12 => 'Annuelle'
    );
    public static $periodicities_masc = array(
        1  => 'Mensuel',
        2  => 'Bimensuel',
        3  => 'Trimestriel',
        4  => 'Quadrimestriel',
        6  => 'Semestriel',
        12 => 'Annuel'
    );

    // droits users:

    public function canSetAction($action)
    {
        switch ($action) {
            case 'addToCommandeFourn':
                $comm = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFourn');
                return $comm->can('create');

            case 'processAllPeriodicOperations':
                return 1;
        }
        return parent::canSetAction($action);
    }

    // Getters booléens:

    public function isRemiseEditable()
    {
        $parent = $this->getParentInstance();

        if (BimpObject::objectLoaded($parent) && $parent->isLogistiqueActive() && !(float) $this->qty && !(float) $this->getBilledQty()) {
            return 1;
        }

        return (int) $this->isParentEditable();
    }

    public function isProductSerialisable()
    {
        if ((int) $this->getData('type') === self::LINE_PRODUCT) {
            $product = $this->getProduct();
            if (BimpObject::objectLoaded($product)) {
                return (int) $product->isSerialisable();
            }
        }

        return 0;
    }

    public function isReadyToShip($id_shipment, &$errors = array(), $auto_reserve_units = false)
    {
        $shipments = $this->getData('shipments');

        if (!isset($shipments[(int) $id_shipment]['qty']) || !(float) $shipments[(int) $id_shipment]['qty']) {
            $errors[] = 'Il n\'y a aucune unité ajouté à cette expédition';
            return 0;
        }

        if (BimpTools::isModuleDoliActif('bimpreservation')) {
            $ready_qty = abs((float) $this->getReadyToShipQty($id_shipment));
            if ($ready_qty < abs((float) $shipments[(int) $id_shipment]['qty'])) {
                if ((float) $this->getFullQty() >= 0) {
                    $diff = (float) $shipments[(int) $id_shipment]['qty'] - $ready_qty;

                    if ($auto_reserve_units) {
                        if ($this->autoReserveUnits($diff, $errors)) {
                            return 1;
                        }

                        $ready_qty = abs((float) $this->getReadyToShipQty($id_shipment));
                        $diff = (float) $shipments[(int) $id_shipment]['qty'] - $ready_qty;
                    }

                    $msg = 'Il manque ';
                    if ($diff > 1) {
                        $msg .= $diff . ' unités prêtes à être expédiées ';
                    } else {
                        $msg .= '1 unité prête à être expédiée ';
                    }
                    $errors[] = $msg;
                    return 0;
                } else {
                    $diff = abs((float) $shipments[(int) $id_shipment]['qty']) - $ready_qty;

                    if ($auto_reserve_units) {
                        if ($this->autoReserveUnits($diff, $errors)) {
                            return 1;
                        }

                        $ready_qty = abs((float) $this->getReadyToShipQty($id_shipment));
                        $diff = abs((float) $shipments[(int) $id_shipment]['qty']) - $ready_qty;
                    }

                    $msg = 'Il manque ';
                    if ($diff > 1) {
                        $msg .= $diff . ' unités prêtes à être retournée ';
                    } else {
                        $msg .= '1 unité prête à être retournée ';
                    }
                    $errors[] = $msg;
                    return 0;
                }
            }
        }
        return 1;
    }

    public function isActionAllowed($action, &$errors = array())
    {
        if (in_array($action, array('modifyQty', 'saveReturnedEquipmentEntrepot', 'removeAcompte'))) {
            if (!$this->isLoaded()) {
                $errors[] = 'ID de la ligne de commande absent';
                return 0;
            }
        }

        switch ($action) {
            case 'modifyQty':
                $commande = $this->getParentInstance();
                if ($this->isLineText()) {
                    $errors[] = 'Cette ligne est de type "Texte"';
                    return 0;
                }
                if (!BimpObject::objectLoaded($commande)) {
                    $errors[] = 'ID de la commande absent';
                    return 0;
                }
                if (!in_array((int) $commande->getData('fk_statut'), array(1, 2))) {
                    $errors[] = 'Le statut actuel de la commande ne permet pas cette action';
                    return 0;
                }
                if (in_array($this->getData('linked_object_name'), self::$notEditableInLogistiqueLines)) {
                    $errors[] = 'Modification des quantités non autorisée sur cette ligne';
                    return 0;
                }
                if ((int) $this->getData('exp_periodicity') > 0 && (float) $this->getShippedQty()) {
                    $errors[] = 'Les quantités ne peuvent pas être modifiées car des livraisons périodiques ont déjà été effctuées';
                    return 0;
                }
                if ((int) $this->getData('fac_periodicity') > 0 && (float) $this->getBilledQty()) {
                    $errors[] = 'Les quantités ne peuvent pas être modifiées car des périodes de facturation ont été ajoutées à au moins une facture';
                    return 0;
                }
                if ((int) $this->getData('achat_periodicity') > 0 && (float) $this->getBoughtQty()) {
                    $errors[] = 'Les quantités ne peuvent pas être modifiées car des achats périodiques ont déjà été effectuées';
                    return 0;
                }
                return 1;

            case 'removeAcompte':
                if (!(int) $this->id_remise_except) {
                    $errors[] = 'Aucun acompte';
                    return 0;
                }
                $commande = $this->getParentInstance();
                if (!BimpObject::objectLoaded($commande)) {
                    $errors[] = 'ID de la commande absent';
                    return 0;
                }
                if ((int) $commande->getData('fk_statut') === 0) {
                    $errors[] = 'La commande est au statut brouillon (l\'acompte peut être directement supprimé)';
                    return 0;
                }
                global $user;
                if ((float) $this->getShippedQty() > 0 && !$user->admin) {
                    $errors[] = 'L\'acompte a été attribué à une expédition';
                    return 0;
                }
                if ((float) $this->getBilledQty() > 0 && !$user->admin) {
                    $errors[] = 'L\'acompte a été attribué à une facture';
                    return 0;
                }
                return 1;

            case 'checkPeriodicityData':
                if ($this->isLoaded()) {
                    if ((int) $this->getData('id_contrat_line_export')) {
                        $errors[] = 'Gestion pérdiodique transférée vers un contrat d\'abonnement';
                        return 0;
                    }

                    if (!(int) $this->getData('exp_periodicity') && !$this->getData('fac_periodicity') && !$this->getData('achat_periodicity')) {
                        $errors[] = 'Aucune périodicité paramétrée';
                        return 0;
                    }
                }
                return 1;
        }

        return parent::isActionAllowed($action, $errors);
    }

    public function isShippable()
    {
        if (in_array((string) $this->getData('linked_object_name'), self::$notShippableLines)) {
            return 0;
        }

        return 1;
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        if (in_array($field, array('echeance_notif_send'))) {
            return 1;
        }

        if (in_array($field, array('exp_periodicity', 'exp_nb_periods', 'exp_periods_start'))) {
            if ((int) $this->getData('id_contrat_line_export')) {
                return 0;
            }
            if (!$this->isPeriodicityAllowed()) {
                return 0;
            }
            if ((float) $this->getShippedQty()) {
                return 0;
            }
            return 1;
        }

        if (in_array($field, array('fac_periodicity', 'fac_nb_periods', 'fac_periods_start', 'fact_echue'))) {
            if ((int) $this->getData('id_contrat_line_export')) {
                return 0;
            }
            if (!$this->isPeriodicityAllowed()) {
                return 0;
            }
            if ((float) $this->getBilledQty()) {
                return 0;
            }
            return 1;
        }

        if (in_array($field, array('achat_periodicity', 'achat_nb_periods', 'achat_periods_start'))) {
            if ((int) $this->getData('id_contrat_line_export')) {
                return 0;
            }
            if (!$this->isPeriodicityAllowed()) {
                return 0;
            }
            if ((float) $this->getBoughtQty()) {
                return 0;
            }
            return 1;
        }

        if ($force_edit) {
            return 1;
        }

        if ($this->isLoaded() && in_array($field, array('remisable')) && !(float) $this->qty) {
            return 1;
        }

        if (!(int) $this->isEditable()) {
            return 0;
        }

        $parent = $this->getParentInstance();

        if (!in_array($field, array('remisable', 'qty')) && BimpObject::objectLoaded($parent) && $parent->isLogistiqueActive() && !(float) $this->qty && !(float) $this->getBilledQty()) {
            return 1;
        }

        return parent::isFieldEditable($field, $force_edit);
    }

    public function isPeriodicityAllowed(&$errors = array())
    {
        if (!$this->isLoaded()) {
            return 1;
        }

        if ($this->getFullQty() < 0) {
            $errors[] = 'La facturation périodique n\'est pas possible pour les retours';
        }

        $product = $this->getProduct();
        if (BimpObject::objectLoaded($product)) {
            if ($product->isSerialisable()) {
                $errors[] = 'Les facturations périodiques ne sont pas possibles pour les produits sérialisés';
            }
        }

        return (count($errors) ? 0 : 1);
    }

    public function hasExpPeriodicity()
    {
        return (int) ($this->getData('exp_periodicity') > 0);
    }

    public function hasFacPeriodicity()
    {
        return (int) ($this->getData('fac_periodicity') > 0);
    }

    public function hasAchatPeriodicity()
    {
        return (int) ($this->getData('achat_periodicity') > 0);
    }

    public function hasMoresLine()
    {
        $parent = $this->getParentInstance();
        $instance = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeLine');
        $rows = $instance->getList(array('id_obj' => $parent->id));

        return ($rows > 100) ? 1 : 0;
    }

    // Getters params:

    public function getLogistiqueBulkActions()
    {
        $actions = array();

        $commande = $this->getParentInstance();

        if (BimpObject::objectLoaded($commande)) {
            $client_facture = $commande->getClientFacture();
            if (BimpObject::objectLoaded($client_facture)) {
                $id_client_facture = (int) $client_facture->id;
            } else {
                $id_client_facture = (int) $commande->getData('fk_soc');
            }

            $actions[] = array(
                'label'   => 'Quantités expédition',
                'icon'    => 'fas_shipping-fast',
                'onclick' => 'addSelectedCommandeLinesToShipment($(this), \'list_id\', ' . $commande->id . ')'
            );

            if ($commande->isActionAllowed('linesFactureQties') && $commande->canSetAction('linesFactureQties')) {
                $onclick = 'addSelectedCommandeLinesToFacture($(this), \'list_id\', ';
                $onclick .= $commande->id . ', ' . (int) $id_client_facture . ', ';
                $onclick .= (($id_client_facture === (int) $commande->getData('fk_soc')) ? (int) $commande->dol_object->contact_id : 0) . ', ';
                $onclick .= (int) $commande->getData('fk_cond_reglement') . ',';
                $onclick .= '\'' . addslashes(htmlentities($commande->getData('note_public'))) . '\', \'' . addslashes(htmlentities($commande->getData('note_private'))) . '\');';

                $actions[] = array(
                    'label'   => 'Quantités facture',
                    'icon'    => 'fas_file-invoice-dollar',
                    'onclick' => $onclick
                );
            }

            $onclick = 'setSelectedObjectsAction($(this), \'list_id\', \'addReturnsFromLines\', {}, ';
            $onclick .= '\'returns_from_lines\', null, true, ';
            $onclick .= 'function($form, extra_data) {return onAddReturnsFromLinesFormSubmit($form, extra_data);})';

            $actions[] = array(
                'label'   => 'Retours produits / équipements',
                'icon'    => 'fas_arrow-circle-left',
                'onclick' => $onclick
            );

            $onclick = $this->getJsBulkActionOnclick('setPeriodicity', array(), array('form_name' => 'periodicity'));
            $actions[] = array(
                'label'   => 'Facturation / Livraison / Achat périodique',
                'icon'    => 'fas_calendar-alt',
                'onclick' => $onclick
            );
        }

        return $actions;
    }

    public function getLogistiqueExtraButtons()
    {
        $buttons = array();

        if ($this->isLoaded()) {
            $commande = $this->getParentInstance();

            if (BimpObject::objectLoaded($commande)) {
                $type = (int) $this->getData('type');
                $reserved_qties = $this->getReservedQties();
                $product = $this->getProduct();

                if ($type === self::LINE_PRODUCT && $this->canSetAction('addToCommandeFourn') && !(int) $this->getData('id_contrat_line_export')) {
                    if (BimpObject::objectLoaded($product)) {
                        if ((int) $product->getData('fk_product_type') !== 0 || (isset($reserved_qties['status'][0]) && $reserved_qties['status'][0] > 0))
                            $buttons[] = array(
                                'label'   => 'Commander',
                                'icon'    => 'fas_cart-arrow-down',
                                'onclick' => $this->getJsActionOnclick('addToCommandeFourn', array(), array(
                                    'form_name' => 'commande_fourn'
                                ))
                            );
                    }
                }

                if ($type !== self::LINE_TEXT) {
                    if ((float) $this->getFullQty() != 0) {
                        $buttons[] = array(
                            'label'   => 'Gérer les expéditions ',
                            'icon'    => 'fas_shipping-fast',
                            'onclick' => $this->getJsLoadModalView('shipments', 'Gestion des expéditions')
                        );
                        $buttons[] = array(
                            'label'   => 'Gérer les factures',
                            'icon'    => 'fas_file-invoice-dollar',
                            'onclick' => $this->getJsLoadModalView('invoices', 'Gestion des factures')
                        );
                    }

                    if ($this->isActionAllowed('modifyQty')) {
                        $buttons[] = array(
                            'label'   => 'Modifier les quantités',
                            'icon'    => 'fas_edit',
                            'onclick' => $this->getJsActionOnclick('modifyQty', array(), array(
                                'form_name' => 'qty_modified'
                            ))
                        );
                    }

                    if ((float) $this->getFullQty() > 0 && BimpObject::objectLoaded($product)) {
                        if (!$product->isSerialisable()) {
                            $onclick = $this->getJsLoadModalForm('periodicity', 'Facturation / Livraison / Achat périodique');
                            $buttons[] = array(
                                'label'   => 'Facturation / Livraison / Achat périodique',
                                'icon'    => 'fas_calendar-alt',
                                'onclick' => $onclick
                            );
                        }
                    }

                    if ((float) $this->getFullQty() > 0 && $this->getShippedQty(null, true)) {
                        $product = $this->getProduct();
                        if (BimpObject::ObjectLoaded($product)) {
                            if ($product->isSerialisable()) {
                                $label = 'Retour équipement';
                            } else {
                                $label = 'Retour produit';
                            }
                        } else {
                            $label = 'Retour';
                        }

//                        if (!$commande->hasRemisesGlobales()) {
                        $buttons[] = array(
                            'label'   => $label,
                            'icon'    => 'fas_arrow-circle-left',
                            'onclick' => $this->getJsActionOnclick('addReturnsFromLines', array(), array(
                                'form_name'      => 'returns_from_lines',
                                'on_form_submit' => 'function($form, extra_data) {return onAddReturnsFromLinesFormSubmit($form, extra_data);}'
                            ))
                        );
//                        }
                    }
                }

                if ((int) $this->id_remise_except && $this->isActionAllowed('removeAcompte') && $this->canSetAction('removeAcompte')) {
                    $buttons[] = array(
                        'label'   => 'Supprimer',
                        'icon'    => 'fas_trash-alt',
                        'onclick' => $this->getJsActionOnclick('removeAcompte', array(), array(
                            'confirm_msg' => 'Veuillez confirmer le retrait de l\\\'acompte'
                        ))
                    );
                }

                if ($this->isRemisable() && in_array((int) $this->getData('type'), array(self::LINE_PRODUCT, self::LINE_FREE))) {
                    $onclick = 'loadModalList(\'bimpcommercial\', \'ObjectLineRemise\', \'default\', ' . $this->id . ', $(this), \'Remises\', {parent_object_type: \'' . static::$parent_comm_type . '\'})';
                    $buttons[] = array(
                        'label'   => 'Remises ligne',
                        'icon'    => 'fas_percent',
                        'onclick' => $onclick
                    );
                }

                if ($this->isActionAllowed('checkPeriodicityData') && $this->canSetAction('checkPeriodicityData')) {
                    $buttons[] = array(
                        'label'   => 'Vérifier les données de périodicité',
                        'icon'    => 'fas_check',
                        'onclick' => $this->getJsActionOnclick('checkPeriodicityData', array(), array())
                    );
                }
            }
        }

        return $buttons;
    }

    public function getGeneralListExtraButtons()
    {
        $buttons = array();
        if ($this->isLoaded()) {
            $commande = $this->getParentInstance();
            if (BimpObject::objectLoaded($commande)) {
                $url = DOL_URL_ROOT . '/bimpcommercial/index.php?fc=commande&id=' . $commande->id . '&navtab=content';
                $buttons[] = array(
                    'icon'    => 'fas_list',
                    'label'   => 'Contenu commande',
                    'onclick' => 'window.open(\'' . $url . '\')'
                );

                if ($commande->isLogistiqueActive()) {
                    $url = DOL_URL_ROOT . '/bimplogistique/index.php?fc=commande&id=' . $commande->id;
                    $buttons[] = array(
                        'icon'    => 'fas_truck-loading',
                        'label'   => 'Logistique commande',
                        'onclick' => 'window.open(\'' . $url . '\')'
                    );
                }
            }

            $product = $this->getProduct();
            if ((float) $this->getFullQty() > 0 && BimpObject::objectLoaded($product)) {
                if (!$product->isSerialisable()) {
                    $onclick = $this->getJsLoadModalForm('periodicity', 'Facturation / Livraison / Achat périodique');
                    $buttons[] = array(
                        'label'   => 'Facturation / Livraison / Achat périodique',
                        'icon'    => 'fas_calendar-alt',
                        'onclick' => $onclick
                    );
                }
            }

            if ($this->isActionAllowed('checkPeriodicityData') && $this->canSetAction('checkPeriodicityData')) {
                $buttons[] = array(
                    'label'   => 'Vérifier les données de périodicité',
                    'icon'    => 'fas_check',
                    'onclick' => $this->getJsActionOnclick('checkPeriodicityData', array(), array())
                );
            }
        }

        return $buttons;
    }

    public function getGeneralPeriodsListHeaderButtons($operation_type)
    {
        $id_client = (isset($this->periods_list_id_client) ? (int) $this->periods_list_id_client : 0);
        $id_product = (isset($this->periods_list_id_product) ? (int) $this->periods_list_id_product : 0);
        $id_fourn = (isset($this->periods_list_id_fourn) ? (int) $this->periods_list_id_fourn : 0);

        $buttons = array();

        if ($this->canSetAction('periodicityMassprocess')) {
            $label_extra = '';

            switch ($operation_type) {
                case 'exp':
                    $label_extra = 'livraisons périodiques';
                    break;

                case 'fac':
                    $label_extra = 'facturations périodiques';
                    break;

                case 'achat':
                    $label_extra = 'achats périodiques';
                    break;
            }

            if ($label_extra) {
                $buttons[] = array(
                    'label'   => 'Traitements en masse des ' . $label_extra,
                    'icon'    => 'fas_cogs',
                    'onclick' => $this->getJsActionOnclick('periodicityMassProcess', array(
                        'operation_type' => $operation_type,
                        'id_client'      => $id_client,
                        'id_fourn'       => $id_fourn,
                        'id_product'     => $id_product
                            ), array(
                        'form_name'        => 'periodicity_mass_process',
                        'on_form_submit'   => 'function($form, extra_data) { return onPeriodic' . ucfirst($operation_type) . 'MassProcessFormSubmit($form, extra_data); }',
                        'use_bimpdatasync' => true,
                        'use_report'       => true
                    ))
                );
            }
        }

        return $buttons;
    }

    public function getGeneralPeriodsListBulkActions($operation_type)
    {
        $buttons = array();

        if ($this->canSetAction('periodicityMassprocess')) {
            $label_extra = '';

            switch ($operation_type) {
                case 'exp':
                    $label_extra = 'livraisons périodiques';
                    break;

                case 'fac':
                    $label_extra = 'facturations périodiques';
                    break;

                case 'achat':
                    $label_extra = 'achats périodiques';
                    break;
            }

            if ($label_extra) {
                $buttons[] = array(
                    'label'   => 'Traiter les ' . $label_extra . ' des lignes sélectionnées',
                    'icon'    => 'fas_cogs',
                    'onclick' => $this->getJsBulkActionOnclick('periodicityMassProcess', array(
                        'operation_type' => $operation_type
                            ), array(
                        'form_name'        => 'periodicity_mass_process',
                        'on_form_submit'   => 'function($form, extra_data) { return onPeriodic' . ucfirst($operation_type) . 'MassProcessFormSubmit($form, extra_data); }',
                        'use_bimpdatasync' => 1,
                        'use_report'       => 1
                    ))
                );
            }
        }

        return $buttons;
    }

    public function getNewEquipmentToReturnCreateFormValues()
    {
        $commande = $this->getParentInstance();

        if (BimpObject::objectLoaded($commande)) {
            $client = $commande->getChildObject('client');
            if (BimpObject::objectLoaded($client)) {
                return array(
                    'fields'  => array(
                        'id_product' => (int) $this->id_product
                    ),
                    'objects' => array(
                        'places' => array(
                            'fields' => array(
                                'type'      => 1,
                                'id_client' => (int) $client->id
                            )
                        )
                    )
                );
            }
        }

        return array();
    }

    public function getCommandeFournIdPrice()
    {
        if ((int) $this->id_fourn_price) {
            return $this->id_fourn_price;
        }

        if ((float) $this->pa_ht && (int) $this->id_product) {
            $id_fourn = null;

            if ((int) $this->getData('achat_periodicity') && (int) $this->getData('achat_id_fourn')) {
                $id_fourn = $this->getData('achat_id_fourn');
            }
            $product = $this->getProduct();
            if (BimpObject::objectLoaded($product)) {
                return (int) $product->getCurrentFournPriceId($id_fourn);
            }
        }

        return 0;
    }

    public function getListExtraBtn()
    {
        $buttons = parent::getListExtraBtn();

        if ((int) $this->id_remise_except && $this->isActionAllowed('removeAcompte') && $this->canSetAction('removeAcompte')) {
            $buttons[] = array(
                'label'   => 'Supprimer',
                'icon'    => 'fas_trash-alt',
                'onclick' => $this->getJsActionOnclick('removeAcompte', array(), array(
                    'confirm_msg' => 'Veuillez confirmer le retrait de l\\\'acompte'
                ))
            );
        }

        $product = $this->getProduct();
        if (BimpObject::objectLoaded($product)) {
            if ($product->getData('2hotline') > 0) {
                $onclick = 'loadModalList(\'bimpsupport\', \'BS_Ticket\', \'default\', ' . $this->id . ', $(this), \'Tickets\', {id_service: \'' . $this->id . '\'}, {id_service: \'' . $this->id . '\'})';
                $buttons[] = array(
                    'icon'    => 'fas_headset',
                    'label'   => 'Tickets',
                    'onclick' => $onclick
                );
            }

            if ((int) BimpCore::getConf('use_logistique_periodicity', null, 'bimpcommercial') && (float) $this->getFullQty() > 0 && !$product->isSerialisable()) {
                $onclick = $this->getJsLoadModalForm('periodicity', 'Facturation / Livraison périodique');
                $buttons[] = array(
                    'label'   => 'Facturation / Livraison périodique',
                    'icon'    => 'fas_calendar-alt',
                    'onclick' => $onclick
                );
            }
        }

        return $buttons;
    }

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, $main_alias = 'a', &$errors = array(), $excluded = false)
    {
        switch ($field_name) {
            case 'reservations_status':
                if (!empty($values)) {
                    $sql = 'SELECT COUNT(DISTINCT reservation.id) FROM ' . MAIN_DB_PREFIX . 'br_reservation reservation';
                    $sql .= ' WHERE reservation.id_commande_client_line = a.id AND reservation.qty > 0 AND reservation.status = ';

                    $res_filters = array();

                    foreach ($values as $status) {
                        $res_filters['(' . $sql . $status . ')'] = array(
                            'operator' => ($excluded ? '=' : '>'),
                            'value'    => 0
                        );
                    }

                    if (!empty($res_filters)) {
                        if ($excluded) {
                            $filters[$main_alias . '___and_reservations_status'] = array(
                                'and_fields' => $res_filters
                            );
                        } else {
                            $filters[$main_alias . '___or_reservations_status'] = array(
                                'or' => $res_filters
                            );
                        }
                    }
                }
                break;

            case 'periodic_exps_to_process':
                if (!empty($values)) {
                    $lines = $this->getPeriodicExpLinesToProcess($errors);

                    if (!empty($lines)) {
                        foreach ($values as $value) {
                            $filters[$main_alias . '.id'] = array(
                                ((int) $value ? 'in' : 'not_in') => $lines
                            );
                        }
                    }
                }
                break;

            case 'periodic_facs_to_process':
                if (!empty($values)) {
                    $lines = $this->getPeriodicFacLinesToProcess($errors, null, null, true);

                    if (!empty($lines)) {
                        foreach ($values as $value) {
                            $filters[$main_alias . '.id'] = array(
                                ((int) $value ? 'in' : 'not_in') => $lines
                            );
                        }
                    }
                }
                break;

            case 'periodic_achats_to_process':
                if (!empty($values)) {
                    $lines = $this->getPeriodicAchatLinesToProcess($errors, null, null, null, true);

                    if (!empty($lines)) {
                        foreach ($values as $value) {
                            $filters[$main_alias . '.id'] = array(
                                ((int) $value ? 'in' : 'not_in') => $lines
                            );
                        }
                    }
                }
                break;
        }

        parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $main_alias, $errors, $excluded);
    }

    // Getters valeurs:

    public function getFullQty()
    {
        return (float) $this->qty + (float) $this->getData('qty_modif');
    }

    public function getMinQty()
    {
        if ($this->isParentEditable()) {
            return 'none';
        }

        $commande = $this->getParentInstance();

        if (BimpObject::objectLoaded($commande) && $commande->isLogistiqueActive()) {
            if ((float) $this->getFullQty() >= 0) {
                $reservedQties = $this->getReservedQties();
                $reserved_qty = $reservedQties['total'];
                if (isset($reservedQties['status'][0])) {
                    $reserved_qty -= $reservedQties['status'][0];
                }
                if (isset($reservedQties['status'][303])) {
                    $reserved_qty -= $reservedQties['status'][303];
                }

                $shipped_qty = (float) $this->getShippedQty();
                $billed_qty = (float) $this->getBilledQty();
                $equipments = array();

                if ($this->isProductSerialisable()) {
                    $shipments = $this->getData('shipments');
                    foreach ($shipments as $id_shipment => $shipment_data) {
                        if (isset($shipment_data['equipments'])) {
                            foreach ($shipment_data['equipments'] as $id_equipment) {
                                if (!in_array((int) $id_equipment, $equipments)) {
                                    $equipments[] = (int) $id_equipment;
                                }
                            }
                        }
                    }

                    $factures = $this->getData('factures');

                    foreach ($factures as $id_facture => $facture_data) {
                        if (isset($facture_data['equipments'])) {
                            foreach ($facture_data['equipments'] as $id_equipment) {
                                if (!in_array((int) $id_equipment, $equipments)) {
                                    $equipments[] = (int) $id_equipment;
                                }
                            }
                        }
                    }
                }

                $min = $shipped_qty;
                if ($billed_qty > $min) {
                    $min = $billed_qty;
                }
                if ($reserved_qty > $min) {
                    $min = $reserved_qty;
                }
                if (count($equipments) > $min) {
                    $min = count($equipments);
                }

                return $min;
            } else {
                $shipped_qty = (float) $this->getShippedQty();
                $billed_qty = (float) $this->getBilledQty();
                $equipments = array();

                if ($this->isProductSerialisable()) {
                    $equipments = $this->getData('equipments_returned');
                }

                $min = $shipped_qty;

                if ($billed_qty < $shipped_qty) {
                    $min = $billed_qty;
                }

                if ((count($equipments) * -1) < $min) {
                    $min = count($equipments) * -1;
                }

                return $min;
            }
        }

//        return $this->qty;
        return 'none';
    }

    public function getReservations($order_by = 'status', $order_way = 'asc', $status = null, $id_shipment = null, $params = array())
    {
        $params = BimpTools::overrideArray(array(
                    'ids_only'  => false,
                    'by_status' => false
                        ), $params);
        $reservations = array();

        if ($this->isLoaded()) {
            $commande = $this->getParentInstance();
            if (BimpObject::objectLoaded($commande)) {
                $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');
                $filters = array(
                    'type'                    => BR_Reservation::BR_RESERVATION_COMMANDE,
                    'id_commande_client'      => (int) $commande->id,
                    'id_commande_client_line' => $this->id
                );
                if (!is_null($status)) {
                    $filters['status'] = $status;
                }
                $rows = $reservation->getList($filters, null, null, $order_by, $order_way, 'array', array('id', 'status'));

                if (!is_null($rows)) {
                    if ((int) $status === 300 && (int) $id_shipment) {
                        $rows_temp = array();
                        foreach ($rows as $r) {
                            $res = BimpCache::getBimpObjectInstance('bimpreservation', 'BR_Reservation', (int) $r['id']);
                            if (BimpObject::objectLoaded($res) && $res->getData('origin') === 'commande_shipment' && $res->getData('id_origin') === (int) $id_shipment) {
                                $rows_temp[] = $r;
                            }
                        }
                        if (!empty($rows_temp)) {
                            $rows = $rows_temp;
                        }
                    }

                    foreach ($rows as $r) {
                        if ($params['by_status'] && !isset($reservations[$r['status']])) {
                            $reservations[$r['status']] = array();
                        }

                        if ($params['ids_only']) {
                            if ($params['by_status']) {
                                $reservations[$r['status']][] = (int) $r['id'];
                            } else {
                                $reservations[] = (int) $r['id'];
                            }
                        } else {
                            $res = BimpCache::getBimpObjectInstance('bimpreservation', 'BR_Reservation', (int) $r['id']);
                            if (BimpObject::objectLoaded($res)) {
                                if ($params['by_status']) {
                                    $reservations[$r['status']][] = $res;
                                } else {
                                    $reservations[] = $res;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $reservations;
    }

    public function getReservedQties()
    {
        if (BimpCore::isModuleActive('bimpreservation')) {
            $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');
            $qties = array(
                'total'        => 0,
                'not_reserved' => 0,
                'reserved'     => 0,
                'ordered'      => 0,
                'status'       => array()
            );

            foreach (BR_Reservation::$commande_status as $status) {
                $qties['status'][$status] = 0;
            }

            if ($this->isLoaded()) {
                $commande = $this->getParentInstance();
                if (BimpObject::objectLoaded($commande)) {
                    $rows = $reservation->getList(array(
                        'type'                    => BR_Reservation::BR_RESERVATION_COMMANDE,
                        'id_commande_client'      => (int) $commande->id,
                        'id_commande_client_line' => $this->id
                            ), null, null, 'id', 'asc', 'array', array('qty', 'status'));

                    if (is_array($rows)) {
                        foreach ($rows as $r) {
                            $qties['total'] += (float) $r['qty'];
                            if (!isset($qties['status'][(int) $r['status']])) {
                                $qties['status'][(int) $r['status']] = 0;
                            }
                            $qties['status'][(int) $r['status']] += (float) $r['qty'];
                            if ((int) $r['status'] === 300 || in_array((int) $r['status'], BR_Reservation::$unavailable_status)) {
                                $qties['reserved'] += (float) $r['qty'];
                            } else {
                                $qties['not_reserved'] += (float) $r['qty'];
                            }
                            if (in_array((int) $r['status'], self::$reservations_ordered_status)) {
                                $qties['ordered'] += (float) $r['qty'];
                            }
                        }
                    }
                }
            }

            return $qties;
        }
        return 0;
    }

    public function getReservationsQties($status = null)
    {
        $reservations = $this->getReservations('status', 'asc', $status);
        $qty = 0;

        foreach ($reservations as $reservation) {
            $qty += (int) $reservation->getData('qty');
        }

        return $qty;
    }

    public function getShipmentsQty()
    {
        if ((int) $this->getData('type') === self::LINE_TEXT) {
            return 0;
        }

        if (in_array($this->getData('linked_object_name'), self::$notShippableLines)) {
            return 0;
        }

        $qty = (float) $this->getFullQty();

        return $qty;
    }

    public function getShippedQty($id_shipment = null, $shipments_validated_only = false)
    {
        $shipments = $this->getData('shipments');

        $qty = 0;

        if (is_array($shipments)) {
            foreach ($shipments as $id_s => $shipment_data) {
                if (!is_null($id_shipment) && (int) $id_shipment !== (int) $id_s) {
                    continue;
                }
                $shipment = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeShipment', (int) $id_s);
                if ($shipments_validated_only && (!BimpObject::objectLoaded($shipment) || (int) $shipment->getData('status') !== BL_CommandeShipment::BLCS_EXPEDIEE)) {
                    continue;
                }
                if (!BimpObject::objectLoaded($shipment) || (int) $shipment->getData('status') == BL_CommandeShipment::BLCS_ANNULEE) {
                    continue;
                }
                if (isset($shipment_data['qty'])) {
                    $qty += (float) $shipment_data['qty'];
                }
            }
        }

        return $qty;
    }

    public function getBilledQty($id_facture = null, $invoices_validated_only = false)
    {
        $factures = $this->getData('factures');

        $qty = 0;

        if (is_array($factures)) {
            foreach ($factures as $id_f => $facture_data) {
                if (!is_null($id_facture) && ((int) $id_facture !== (int) $id_f)) {
                    continue;
                }

                if ($invoices_validated_only && (int) $id_f !== -1) {
                    $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_f);
                    if (!BimpObject::objectLoaded($facture) || (int) $facture->getData('fk_statut') === Facture::STATUS_DRAFT) {
                        continue;
                    }
                }

                if (isset($facture_data['qty'])) {
                    $qty += (float) $facture_data['qty'];
                }
            }
        }

        return $qty;
    }

    public function getBoughtQty($id_commande_fourn = null, $commandes_validated_only = false)
    {
        $qty = 0;

        if ($this->isLoaded()) {
            $sql = 'SELECT (det.qty + l.qty_modif) as qty FROM ' . MAIN_DB_PREFIX . 'bimp_commande_fourn_line l';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'commande_fournisseurdet det ON det.rowid = l.id_line';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'commande_fournisseur c ON l.id_obj = c.rowid';
            $sql .= ' WHERE l.linked_object_name = \'commande_line\' AND l.linked_id_object = ' . $this->id;
            $sql .= ' AND c.fk_statut < 6'; // Non annulée. 

            if ($commandes_validated_only) {
                $sql .= ' AND c.fk_statut > 0';
            }

            if ((int) $id_commande_fourn) {
                $sql .= ' AND c.rowid = ' . (int) $id_commande_fourn;
            }

            $rows = $this->db->executeS($sql, 'array');

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    $qty += (float) $r['qty'];
                }
            }
        }

        return $qty;
    }

    public function getShipmentData($id_shipment)
    {
        // *** Shipment Data *** 
        // qty: float
        // group: bool
        // shipped: bool
        // equipments: array (liste id_equipment) / si retour: id_equipment => id_entrepot_destination
        // id_entrepot (si retour prod non serial: entrepot de destination). 

        $shipments = $this->getData('shipments');

        if (isset($shipments[(int) $id_shipment])) {
            $data = array(
                'qty'        => (isset($shipments[(int) $id_shipment]['qty']) ? $shipments[(int) $id_shipment]['qty'] : 0),
                'group'      => (isset($shipments[(int) $id_shipment]['group']) ? $shipments[(int) $id_shipment]['group'] : 0),
                'shipped'    => (isset($shipments[(int) $id_shipment]['shipped']) ? $shipments[(int) $id_shipment]['shipped'] : 0),
                'equipments' => (isset($shipments[(int) $id_shipment]['equipments']) ? $shipments[(int) $id_shipment]['equipments'] : array()),
            );

            if (isset($shipments[(int) $id_shipment]['id_entrepot'])) {
                $data['id_entrepot'] = (int) $shipments[(int) $id_shipment]['id_entrepot'];
            }

            return $data;
        }

        return array(
            'qty'        => 0,
            'group'      => 0,
            'shipped'    => 0,
            'equipments' => array()
        );
    }

    public function getFactureData($id_facture)
    {
        $factures = $this->getData('factures');

        if (isset($factures[(int) $id_facture])) {
            return array(
                'qty'        => (isset($factures[(int) $id_facture]['qty']) ? $factures[(int) $id_facture]['qty'] : 0),
                'equipments' => (isset($factures[(int) $id_facture]['equipments']) ? $factures[(int) $id_facture]['equipments'] : array()),
            );
        }

        return array(
            'qty'        => 0,
            'equipments' => array()
        );
    }

    public function getReadyToShipQty($id_shipment)
    {
        if ((int) $this->getData('type') === self::LINE_PRODUCT) {
            $commande = $this->getParentInstance();

            if (!BimpObject::objectLoaded($commande)) {
                return 0;
            }

            $qty = 0;
            $fullQty = (float) $this->getFullQty();

            if ($this->isProductSerialisable()) {
                $shipments = $this->getData('shipments');
                if (is_array($shipments) && isset($shipments[(int) $id_shipment]['equipments']) && is_array($shipments[(int) $id_shipment]['equipments'])) {

                    if ($fullQty >= 0) {
                        foreach ($shipments[(int) $id_shipment]['equipments'] as $id_equipment) {
                            BimpObject::loadClass('bimpreservation', 'BR_Reservation');
                            $reservation = BimpCache::findBimpObjectInstance('bimpreservation', 'BR_Reservation', array(
                                        'type'                    => BR_Reservation::BR_RESERVATION_COMMANDE,
                                        'id_commande_client'      => (int) $commande->id,
                                        'id_commande_client_line' => $this->id,
                                        'status'                  => 200,
                                        'id_equipment'            => (int) $id_equipment
                            ));

                            if (BimpObject::objectLoaded($reservation)) {
                                $qty++;
                            }
                        }
                    } else {
                        $equipments_returned = $this->getData('equipments_returned');
                        foreach ($shipments[(int) $id_shipment]['equipments'] as $id_equipment) {
                            if (array_key_exists((int) $id_equipment, $equipments_returned)) {
                                $qty--;
                            }
                        }
                    }
                }
            } else {
                $product = $this->getProduct();
                if (BimpObject::objectLoaded($product)) {
                    $shippedQty = (float) $this->getShippedQty($id_shipment);
                    if ($fullQty > 0 && $product->getData('fk_product_type') === 0) {
                        $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');
                        $rows = $reservation->getList(array(
                            'type'                    => BR_Reservation::BR_RESERVATION_COMMANDE,
                            'id_commande_client'      => (int) $commande->id,
                            'id_commande_client_line' => $this->id,
                            'status'                  => 200
                                ), null, null, 'id', 'asc', 'array', array('qty'));
                        if (!is_null($rows)) {
                            foreach ($rows as $r) {
                                $qty += (int) $r['qty'];
                            }
                        }
                    } else {
                        $qty += $shippedQty;
                    }
                }
            }

            return $qty;
        }

        return (float) $this->getShippedQty($id_shipment);
    }

    public function getEquipmentIdShipment($id_equipment)
    {
        $shipments = $this->getData('shipments');

        if (is_array($shipments)) {
            foreach ($shipments as $id_shipment => $data) {
                if (isset($data['equipments']) && in_array($id_equipment, $data['equipments'])) {
                    return (int) $id_shipment;
                }
            }
        }

        return 0;
    }

    public function getEquipementsToAttributeToShipment()
    {
        $equipments = array();

        if ($this->isLoaded()) {
            if ((float) $this->getFullQty() >= 0) {
                $reservations = $this->getReservations('status', 'asc', 200);
                foreach ($reservations as $reservation) {
                    $id_equipment = (int) $reservation->getData('id_equipment');
                    if ($id_equipment) {
                        $id_s = (int) $this->getEquipmentIdShipment($id_equipment);
                        if (!$id_s) {
                            $equipments[] = $id_equipment;
                        }
                    }
                }
            } else {
                $returned_equipments = $this->getData('equipments_returned');
                foreach ($returned_equipments as $id_equipment => $id_entrepot) {
                    $id_shipment = (int) $this->getEquipmentIdShipment((int) $id_equipment);
                    if (!$id_shipment) {
                        $equipments[] = $id_equipment;
                    }
                }
            }
        }

        return $equipments;
    }

    public function getEquipmentIdFacture($id_equipment)
    {
        $factures = $this->getData('factures');

        if (is_array($factures)) {
            foreach ($factures as $id_facture => $data) {
                if (isset($data['equipments']) && in_array($id_equipment, $data['equipments'])) {
                    return (int) $id_facture;
                }
            }
        }

        return 0;
    }

    public function getEquipementsToAttributeToFacture()
    {
        $equipments = array();

        if ($this->isLoaded()) {
            if ((float) $this->getFullQty() >= 0) {
                $reservations = $this->getReservations();
                foreach ($reservations as $reservation) {
                    $id_equipment = (int) $reservation->getData('id_equipment');
                    if ($id_equipment) {
                        $id_facture = (int) $this->getEquipmentIdFacture($id_equipment);
                        if (!$id_facture) {
                            $equipments[] = $id_equipment;
                        }
                    }
                }
            } else {
                $returned_equipments = $this->getData('equipments_returned');
                foreach ($returned_equipments as $id_equipment => $id_entrepot) {
                    $id_facture = (int) $this->getEquipmentIdFacture($id_equipment);
                    if (!$id_facture) {
                        $equipments[] = $id_equipment;
                    }
                }
            }
        }

        return $equipments;
    }

    public function getShipmentTotalHT($id_shipment)
    {
        $data = $this->getShipmentData($id_shipment);
        return ((float) $this->getUnitPriceHTWithRemises() * (float) $data['qty']);
    }

    public function getShipmentTotalTTC($id_shipment)
    {
        $data = $this->getShipmentData($id_shipment);
        return ((float) $this->getUnitPriceTTC() * (float) $data['qty']);
    }

    public function getOldInfoExpe($field)
    {
        $shipments = $this->getData('shipments');

        if (!is_array($shipments)) {
            $shipments = array();
        }

        $date = null;
        $ref = '';
        $nbBrouillon = 0;
        foreach ($shipments as $idS => $shipment) {
            if ($shipment['qty'] > 0 || $shipment['qty'] < 0) {
                $shipmentObj = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeShipment', $idS);
                if ($shipmentObj->isLoaded()) {
                    if ($shipmentObj->getData('status') == BL_CommandeShipment::BLCS_EXPEDIEE) {
                        $dateT = strtotime($shipmentObj->getData('date_shipped'));
                        if ($dateT > $date) {
                            $date = $dateT;
                            $comm = $shipmentObj->getParentInstance();
                            $ref = 'LIV-' . $comm->getRef() . '-' . $shipmentObj->getData('num_livraison');
                        }
                    } elseif ($shipmentObj->getData('status') == BL_CommandeShipment::BLCS_BROUILLON) {
                        $nbBrouillon++;
                    }
                }
            }
        }
        if ($field == 'date') {
            if ($date > 0)
                return date('Y-m-d H:i:s', $date);
        } elseif ($field == 'ref')
            return $ref;
        elseif ($field == 'nbBrouillon')
            return $nbBrouillon;
        return '';
    }

    public function getOldInfoFact($field)
    {
        $facts = $this->getData('factures');

        if (!is_array($facts)) {
            $facts = array();
        }

        $date = null;
        $ref = '';
        foreach ($facts as $idS => $fact) {
            if ($fact['qty'] > 0 || $fact['qty'] < 0) {
                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $idS);
                if ($facture->isLoaded() && in_array($facture->getData('fk_statut'), array(1, 2))) {
                    $dateT = strtotime($facture->getData('datef'));
                    if ($dateT > $date) {
                        $date = $dateT;
                        $ref = $facture->getData('ref');
                    }
                }
            }
        }
        if ($field == 'date') {
            if ($date > 0)
                return date('Y-m-d H:i:s', $date);
        } elseif ($field == 'ref')
            return $ref;
        return '';
    }

    public function getReservationsEquipmentsList()
    {
        $equipments = array();

        $reservations = $this->getReservations();

        foreach ($reservations as $res) {
            $id_eq = (int) $res->getData('id_equipment');
            if ($id_eq && !in_array($id_eq, $equipments)) {
                $equipments[] = $id_eq;
            }
        }

        return $equipments;
    }

    // Getters opé périodiques: 

    public function getExpStartPeriode($check = false, &$infos = array())
    {
        $start = $this->getData('exp_periods_start');

        if (!$start || (int) strtotime($start) <= 0) {
            $start = null;
        }

        if ($check || !$start) {
            $shipments = $this->getData('shipments');

            if (!empty($shipments)) {

                $min_date = '';
                $min_date2 = '';

                foreach ($shipments as $id_shipment => $shipment_data) {
                    if ((int) $id_shipment) {
                        $shipment = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeShipment', (int) $id_shipment);
                        if (BimpObject::objectLoaded($shipment)) {
                            if ($shipment->getData('status') === BL_CommandeShipment::BLCS_EXPEDIEE) {
                                if (!$min_date || $shipment->getData('date_shipped') < $min_date) {
                                    $min_date = $shipment->getData('date_shipped');
                                }
                            }

                            if (!$min_date2 || $shipment->getData('date_create') < $min_date2) {
                                $min_date2 = $shipment->getData('date_create');
                            }
                        }
                    }
                }

                if (!$min_date && $min_date2) {
                    $min_date = $min_date2;
                }

                if ($min_date) {
                    $min_date = date('Y-m-d', strtotime($min_date));

                    if ($min_date != $this->getData('exp_periods_start')) {
                        $infos[] = 'Mise à jour de la date de début des livraisons (Ancienne : "' . $this->getData('exp_periods_start') . '" - Nouvelle : "' . $min_date . '")';
                        $this->updateField('exp_periods_start', $min_date, null, true, false, true);
                    }

                    $start = $min_date;
                }
            }
        }

        if (!$start || (int) strtotime($start) <= 0) {
            return null;
        }

        return $start;
    }

    public function getFacStartPeriode($check = false, &$infos = array())
    {
        $start = $this->getData('fac_periods_start');

        if (!$start || (int) strtotime($start) <= 0) {
            $start = null;
        }

        if ($check || !$start) {
            $factures = $this->getData('factures');

            if (!empty($factures)) {
                $min_date = '';
                $min_date2 = '';

                foreach ($factures as $id_fac => $fac_data) {
                    if ((int) $id_fac) {
                        $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_fac);
                        if (BimpObject::objectLoaded($fac)) {
                            if (!$min_date || $fac->getData('datef') < $min_date) {
                                $min_date = $fac->getData('datef');
                            }
                            if (!$min_date2 || $fac->getData('datec') < $min_date2) {
                                $min_date2 = $fac->getData('datec');
                            }
                        }
                    }
                }

                if (!$min_date && $min_date2) {
                    $min_date = $min_date2;
                }

                if ($min_date) {
                    $min_date = date('Y-m-d', strtotime($min_date));

                    if ($min_date != $this->getData('fac_periods_start')) {
                        $infos[] = 'Mise à jour de la date de début des facturations (Ancienne : "' . $this->getData('fac_periods_start') . ' - Nouvelle ": ' . $min_date . '")';
                        $this->updateField('fac_periods_start', $min_date, null, true, false, true);
                    }

                    $start = $min_date;
                }
            }
        }

        if (!$start || (int) strtotime($start) <= 0) {
            return null;
        }

        return $start;
    }

    public function getAchatStartPeriode($check = false, &$infos = array())
    {
        $start = $this->getData('achat_periods_start');

        if (!$start || (int) strtotime($start) <= 0) {
            $start = null;
        }

        if ($this->isLoaded() && ($check || !$start)) {
            $sql = 'SELECT c.rowid, c.date_commande, c.date_creation FROM ' . MAIN_DB_PREFIX . 'commande_fournisseur c';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bimp_commande_fourn_line l ON l.id_obj = c.rowid';
            $sql .= ' WHERE l.linked_object_name = \'commande_line\' AND l.linked_id_object = ' . $this->id;
            $sql .= ' AND c.fk_statut < 6'; // Non annulée. 

            $rows = $this->db->executeS($sql, 'array');

            if (is_array($rows) && !empty($rows)) {
                $min_date = '';
                $min_date2 = '';

                foreach ($rows as $r) {
                    if ($r['date_commande'] && (!$min_date || $r['date_commande'] < $min_date)) {
                        $min_date = $r['date_commande'];
                    }
                    if ($r['date_creation'] && (!$min_date2 || $r['date_creation'] < $min_date2)) {
                        $min_date2 = $r['date_creation'];
                    }
                }

                if (!$min_date && $min_date2) {
                    $min_date = $min_date2;
                }

                if ($min_date) {
                    $min_date = date('Y-m-d', strtotime($min_date));

                    if ($min_date != $this->getData('achat_periods_start')) {
                        $infos[] = 'Mise à jour de la date de début des achats (Ancienne : "' . $this->getData('achat_periods_start') . '" - Nouvelle : "' . $min_date . '")';
                        $this->updateField('achat_periods_start', $min_date, null, true, false, true);
                    }

                    $start = $min_date;
                }
            }
        }

        if (!$start || (int) strtotime($start) <= 0) {
            return null;
        }

        return $start;
    }

    public function getNbPeriodsToShipData($id_shipment = null, $check_qties = true)
    {
        $data = array(
            'nb_total_periods'        => 0, // Nombre total de périodes
            'nb_periods_shipped'      => 0, // Nombre de périodes déjà expédiées
            'nb_periods_max'          => 0, // Nombre total de périodes restantes
            'nb_periods_until_today'  => 0, // Nombre total de périodes écoulées jusqu'à aujourd'hui. 
            'nb_periods_toship_today' => 0, // Nombre de période à livrer aujourd'hui
            'shipment_nb_periods'     => 0, // Nombre de périodes à livrer pour l'expédition spécifiée
            'start_date'              => '',
            'nb_units_per_period'     => null,
            'nb_units_last_period'    => null,
            'qty_for_1_period'        => 0,
        );

        $fullQty = (float) $this->getFullQty();

        if ((float) $fullQty <= 0) {
            return $data;
        }

        $id_shipment = (int) $id_shipment;

        if ($this->isLoaded()) {
            if ((int) $this->getData('exp_periodicity') && (int) $this->getData('exp_nb_periods')) {
                if ($check_qties) {
                    $this->checkQties();
                }

                $periodicity = (int) $this->getData('exp_periodicity');
                $nb_total_periods = (int) $this->getData('exp_nb_periods');
                $nb_periods_until_today = 0;

                // Calcul du nombre total de périodes écoulées à la date du jour: 
                $start = $this->getExpStartPeriode();

                if ($start && $start !== '0000-00-00') {
                    $data['start_date'] = $start;
                    $dt_now = new DateTime();

                    if ($start < $dt_now->format('Y-m-d')) {
                        $dt_start = new DateTime($start);

                        $interval = $dt_now->diff($dt_start, true);

                        if ($interval !== false) {
                            // Nombre de mois écoulés: 
                            $nb_month = (($interval->y * 12) + $interval->m);

                            if ((int) $nb_month) {
                                // Nombre de périodes écoulées: 
                                $nb_periods_until_today = floor($nb_month / $periodicity);
                            }
                        }
                        $nb_periods_until_today += 1;
                    }
                } else {
                    $nb_periods_until_today = 1;
                }

                // Ajustement sur le nombre max de périodes facturables: 
                if ($nb_periods_until_today > $nb_total_periods) {
                    $nb_periods_until_today = $nb_total_periods;
                }

                // Retrait du nombre de périodes déjà facturées: 
                $qty_shipped = (float) $this->getShippedQty();
                $shipment_qty = null;

                if (!is_null($id_shipment) && (int) $id_shipment) {
                    $shipment_qty = (float) $this->getShippedQty($id_shipment);
                    $qty_shipped -= $shipment_qty;
                }

                // Calcul du nombre de périodes déjà expédiées: 
                $nb_periods_shipped = null;
                $product = $this->getProduct();
                if (BimpObject::objectLoaded($product) && $product->isTypeProduct()) {
                    $base_unit = $fullQty / $nb_total_periods;
                    $round_unit = round($base_unit);
                    if ($round_unit < 1)
                        $round_unit = 1;
                    $nb_periods_shipped = $qty_shipped / $round_unit;

                    if ($nb_periods_shipped != round($nb_periods_shipped)) {
                        if ($base_unit > $round_unit) {
                            $nb_periods_shipped = floor($nb_periods_shipped);
                        } else {
                            $nb_periods_shipped = ceil($nb_periods_shipped);
                        }
                    }

                    if ($base_unit != $round_unit) {
                        $data['nb_units_per_period'] = $round_unit;
                        $data['nb_units_last_period'] = $fullQty - ($round_unit * ($nb_total_periods - 1));
                    }
                }

                if (is_null($nb_periods_shipped)) {
                    $nb_periods_shipped = ($qty_shipped / $fullQty) * $nb_total_periods;
                }

                if ($nb_periods_shipped > $nb_total_periods) {
                    $nb_periods_shipped = $nb_total_periods;
                }

                $data['nb_total_periods'] = $nb_total_periods;
                $data['nb_periods_until_today'] = $nb_periods_until_today;
                $data['nb_periods_toship_today'] = $nb_periods_until_today - $nb_periods_shipped;
                $data['nb_periods_max'] = $nb_total_periods - $nb_periods_shipped;
                $data['nb_periods_shipped'] = $nb_periods_shipped;
                $data['qty_for_1_period'] = $this->getExpQtyFor1Periode();

                if ($data['nb_periods_toship_today'] < 0) {
                    $data['nb_periods_toship_today'] = 0;
                }

                if ($data['nb_periods_toship_today'] > $nb_total_periods) {
                    $data['nb_periods_toship_today'] = $nb_total_periods;
                }

                if ($data['nb_periods_max'] < 0) {
                    $data['nb_periods_max'] = 0;
                }

                if ($data['nb_periods_max'] > $nb_total_periods) {
                    $data['nb_periods_max'] = $nb_total_periods;
                }

                if (!is_null($shipment_qty)) {
//                    $data['nb_periods'] = ($shipment_qty / $fullQty) * $nb_total_periods;
                    $data['shipment_nb_periods'] = $this->getExpNbPeriodsFromQty($shipment_qty, $id_shipment);

                    if ($data['shipment_nb_periods'] < 0) {
                        $data['shipment_nb_periods'] = 0;
                    }

                    if ($data['shipment_nb_periods'] > $nb_total_periods) {
                        $data['shipment_nb_periods'] = $nb_total_periods;
                    }
                } else {
                    $data['shipment_nb_periods'] = $data['nb_periods_toship_today'];
                }

                if ((int) $this->getData('id_contrat_line_export')) {
                    $data['nb_periods_toship_today'] = 0;

                    if ($shipment_qty) {
                        $data['nb_periods_max'] = $data['shipment_nb_periods'];
                        $data['nb_total_periods'] = $data['nb_periods_shipped'] + $data['shipment_nb_periods'];
                        $data['nb_periods_toship_today'] = $data['shipment_nb_periods'];
                    } else {
                        $data['nb_total_periods'] = $data['nb_periods_shipped'];
                        $data['nb_periods_max'] = 0;
                        $data['shipment_nb_periods'] = 0;
                    }
                }
            }
        }

        return $data;
    }

    public function getNbPeriodsToBillData($id_facture = null, $check_qties = true)
    {
        $data = array(
            'nb_total_periods'        => 0, // Nombre total de périodes
            'nb_periods_billed'       => 0, // Nombre de périodes déjà facturées. 
            'nb_periods_max'          => 0, // Nombre total de périodes restant à facturer. 
            'nb_periods_until_today'  => 0, // Nombre total de périodes écoulées jusqu'à aujourd'hui. 
            'nb_periods_tobill_today' => 0, // Nombre de périodes à facturer à date.  
            'fac_nb_periods'          => 0, // Nombre de période à facturer pour la facture indiquée (nouvelle facture si null)
            'qty_for_1_period'        => $this->getFacQtyFor1Periode(),
            'start_date'              => ''
        );

        $fullQty = (float) $this->getFullQty();

        if ((float) $fullQty <= 0) {
            return $data;
        }

        if ($this->isLoaded()) {
            if ((int) $this->getData('fac_periodicity') && (int) $this->getData('fac_nb_periods')) {
                if ($check_qties) {
                    $this->checkQties();
                }

                $periodicity = (int) $this->getData('fac_periodicity');
                $nb_total_periods = (int) $this->getData('fac_nb_periods');
                $nb_periods_until_today = 0;

                // Calcul du nombre total de périodes écoulées à la date du jour: 
                $start = $this->getFacStartPeriode();
                if ($this->getData('fact_echue')) {
                    $start = date('Y-m-d', strtotime('+' . $this->getData('fac_periodicity') . ' month', strtotime($start)));
                }

                if ($start && $start !== '0000-00-00') {
                    $data['start_date'] = $start;
                    $dt_now = new DateTime();

                    if ($start < $dt_now->format('Y-m-d')) {
                        $dt_start = new DateTime($start);

                        $interval = $dt_now->diff($dt_start, true);

                        if ($interval !== false) {
                            // Nombre de mois écoulés: 
                            $nb_month = (($interval->y * 12) + $interval->m);

                            if ((int) $nb_month) {
                                // Nombre de périodes écoulées: 
                                $nb_periods_until_today = (int) floor($nb_month / $periodicity);
                            }
                        }
                        $nb_periods_until_today += 1;
                    }
                } else {
                    $nb_periods_until_today = 1;
                }

                // Ajustement sur le nombre max de périodes facturables: 
                if ($nb_periods_until_today > $nb_total_periods) {
                    $nb_periods_until_today = $nb_total_periods;
                }

                // Retrait du nombre de périodes déjà facturées: 
                $qty_billed = (float) $this->getData('qty_billed');
                $facture_qty = null;

                if (!is_null($id_facture) && (int) $id_facture) {
                    $facture_qty = (float) $this->getBilledQty($id_facture);
                    $qty_billed -= $facture_qty;
                }

                $nb_periods_billed = round(($qty_billed / $fullQty) * $nb_total_periods, 8);

                $data['nb_total_periods'] = $nb_total_periods;
                $data['nb_periods_until_today'] = $nb_periods_until_today;
                $data['nb_periods_tobill_today'] = round($nb_periods_until_today - $nb_periods_billed, 8);
                $data['nb_periods_max'] = $nb_total_periods - $nb_periods_billed;
                $data['nb_periods_billed'] = $nb_periods_billed;

                if ($data['nb_periods_tobill_today'] < 0) {
                    $data['nb_periods_tobill_today'] = 0;
                }

                if ($data['nb_periods_tobill_today'] > $nb_total_periods) {
                    $data['nb_periods_tobill_today'] = $nb_total_periods;
                }

                if ($data['nb_periods_max'] < 0) {
                    $data['nb_periods_max'] = 0;
                }

                if ($data['nb_periods_max'] > $nb_total_periods) {
                    $data['nb_periods_max'] = $nb_total_periods;
                }

                if (!is_null($facture_qty)) {
                    $data['fac_nb_periods'] = ($facture_qty / $fullQty) * $nb_total_periods;
                    if ($data['fac_nb_periods'] < 0) {
                        $data['fac_nb_periods'] = 0;
                    }

                    if ($data['fac_nb_periods'] > $nb_total_periods) {
                        $data['fac_nb_periods'] = $nb_total_periods;
                    }
                } else {
                    $data['fac_nb_periods'] = $data['nb_periods_tobill_today'];
                }

                if ((int) $this->getData('id_contrat_line_export')) {
                    $data['nb_periods_max'] = 0;
                    $data['nb_periods_tobill_today'] = 0;
                    $data['nb_total_periods'] = $data['nb_periods_billed'];

                    if (is_null($facture_qty)) {
                        $data['fac_nb_periods'] = 0;
                    }
                }
            }
        }

        return $data;
    }

    public function getNbPeriodesToBuyData($id_commande_fourn = null)
    {
        $data = array(
            'nb_total_periods'          => 0, // Nombre total de périodes
            'nb_periods_bought'         => 0, // Nombre de périodes déjà achetées. 
            'nb_periods_max'            => 0, // Nombre total de périodes restant à acheter. 
            'nb_periods_until_today'    => 0, // Nombre total de périodes écoulées jusqu'à aujourd'hui. 
            'nb_periods_tobuy_today'    => 0, // Nombre de périodes à acheter à date.  
            'commande_fourn_nb_periods' => 0, // Nombre de période à acheter pour la commande fourn indiquée (nouvelle commande si null)
            'qty_for_1_period'          => $this->getAchatQtyFor1Periode(),
            'start_date'                => ''
        );

        $fullQty = (float) $this->getFullQty();

        if ((float) $fullQty <= 0) {
            return $data;
        }

        $id_commande_fourn = (int) $id_commande_fourn;

        if ($this->isLoaded()) {
            if ((int) $this->getData('achat_periodicity') && (int) $this->getData('achat_nb_periods')) {
                $periodicity = (int) $this->getData('achat_periodicity');
                $nb_total_periods = (int) $this->getData('achat_nb_periods');
                $nb_periods_until_today = 0;

                // Calcul du nombre total de périodes écoulées à la date du jour: 
                $start = $this->getAchatStartPeriode();

                if ($start && $start !== '0000-00-00') {
                    $data['start_date'] = $start;
                    $dt_now = new DateTime();

                    if ($start <= $dt_now->format('Y-m-d')) {
                        $dt_start = new DateTime($start);

                        $interval = $dt_now->diff($dt_start, true);

                        if ($interval !== false) {
                            // Nombre de mois écoulés: 
                            $nb_month = (($interval->y * 12) + $interval->m);

                            if ((int) $nb_month) {
                                // Nombre de périodes écoulées: 
                                $nb_periods_until_today = (int) floor($nb_month / $periodicity);
                            }
                        }

                        $nb_periods_until_today += 1;
                    }
                } else {
                    $nb_periods_until_today = 1;
                }

                // Ajustement sur le nombre max de périodes facturables: 
                if ($nb_periods_until_today > $nb_total_periods) {
                    $nb_periods_until_today = $nb_total_periods;
                }

                // Retrait du nombre de périodes déjà facturées: 
                $qty_bought = (float) $this->getBoughtQty();
                $commande_qty = 0;

                if ($id_commande_fourn) {
                    $commande_qty = (float) $this->getBoughtQty($id_commande_fourn);
                    $qty_bought -= $commande_qty;
                }

                $nb_periods_bought = ($qty_bought / $fullQty) * $nb_total_periods;

                $data['nb_total_periods'] = $nb_total_periods;
                $data['nb_periods_until_today'] = $nb_periods_until_today;
                $data['nb_periods_tobuy_today'] = $nb_periods_until_today - $nb_periods_bought;
                $data['nb_periods_max'] = $nb_total_periods - $nb_periods_bought;
                $data['nb_periods_bought'] = $nb_periods_bought;

                if ($data['nb_periods_tobuy_today'] < 0) {
                    $data['nb_periods_tobuy_today'] = 0;
                }

                if ($data['nb_periods_tobuy_today'] > $nb_total_periods) {
                    $data['nb_periods_tobuy_today'] = $nb_total_periods;
                }

                if ($data['nb_periods_max'] < 0) {
                    $data['nb_periods_max'] = 0;
                }

                if ($data['nb_periods_max'] > $nb_total_periods) {
                    $data['nb_periods_max'] = $nb_total_periods;
                }

                if ($commande_qty) {
                    $data['commande_fourn_nb_periods'] = ($commande_qty / $fullQty) * $nb_total_periods;
                    if ($data['commande_fourn_nb_periods'] < 0) {
                        $data['commande_fourn_nb_periods'] = 0;
                    }

                    if ($data['commande_fourn_nb_periods'] > $nb_total_periods) {
                        $data['commande_fourn_nb_periods'] = $nb_total_periods;
                    }
                } else {
                    $data['commande_fourn_nb_periods'] = $data['nb_periods_tobuy_today'];
                }

                if ((int) $this->getData('id_contrat_line_export')) {
                    $data['nb_periods_max'] = 0;
                    $data['nb_periods_tobuy_today'] = 0;
                    $data['nb_total_periods'] = $data['nb_periods_bought'];

                    if (!$commande_qty) {
                        $data['commande_fourn_nb_periods'] = 0;
                    }
                }
            }
        }

        return $data;
    }

    public function getExpQtyFor1Periode()
    {
        if ((int) $this->getData('exp_nb_periods')) {
            return $this->getFullQty() / $this->getData('exp_nb_periods');
        }

        return $this->getFullQty();
    }

    public function getFacQtyFor1Periode()
    {
        if ((int) $this->getData('fac_nb_periods')) {
            return $this->getFullQty() / $this->getData('fac_nb_periods');
        }

        return $this->getFullQty();
    }

    public function getAchatQtyFor1Periode()
    {
        if ((int) $this->getData('achat_nb_periods')) {
            return $this->getFullQty() / $this->getData('achat_nb_periods');
        }

        return $this->getFullQty();
    }

    public function getExpQtyFromNbPeriods($nb_periods, $id_shipment = 0)
    {
        if ((float) $nb_periods && (int) $this->getData('exp_periodicity') && (int) $this->getData('exp_nb_periods')) {
            $fullQty = (float) $this->getFullQty();
            $nb_total_periods = (int) $this->getData('exp_nb_periods');
            $base_unit = ($fullQty / $nb_total_periods);

            $product = $this->getProduct();

            if (BimpObject::objectLoaded($product) && $product->isTypeProduct()) {
                $round_unit = round($base_unit);

                if ($round_unit != $base_unit) {
                    $shipped_qty = $this->getShippedQty();
                    if ((int) $id_shipment) {
                        $shipped_qty -= $this->getShippedQty($id_shipment);
                    }

                    $qty = $round_unit * $nb_periods;

                    if (($shipped_qty + $qty) > ($round_unit * ($nb_total_periods - 1))) {
                        $qty = $fullQty - $shipped_qty;
                    }

                    return $qty;
                }
            }

            $qty = $base_unit * $nb_periods;

            return $qty;
        }

        return $nb_periods;
    }

    public function getExpNbPeriodsFromQty($qty, $id_shipment = 0)
    {
        $fullQty = (float) $this->getFullQty();

        if ((float) $qty && $fullQty && (int) $this->getData('exp_periodicity') && (int) $this->getData('exp_nb_periods')) {
            $nb_total_periods = (int) $this->getData('exp_nb_periods');
            $base_unit = ($fullQty / $nb_total_periods);

            $product = $this->getProduct();

            if (BimpObject::objectLoaded($product) && $product->isTypeProduct()) {
                $round_unit = round($base_unit);

                if (!$round_unit) {
                    $round_unit = 1;
                }

                if ($round_unit != $base_unit) {
                    $shipped_qty = 0;

                    if ($id_shipment) {
                        $shipped_qty = $this->getShippedQty() - $this->getShippedQty($id_shipment);
                    }

                    if (($shipped_qty + $qty) > ($round_unit * ($nb_total_periods - 1))) {
                        if ($shipped_qty && ($shipped_qty > ($round_unit * ($nb_total_periods - 1)))) {
                            if (($shipped_qty + $qty) <= $fullQty) {
                                $nP = 1;
                            } else {
                                $nP = 0;
                            }
                        } else {
                            $np_shipped = 0;

                            if ($shipped_qty) {
                                $np_shipped = $shipped_qty / $round_unit;

                                if ($np_shipped != round($np_shipped)) {
                                    if ($base_unit > $round_unit) {
                                        $np_shipped = floor($np_shipped);
                                    } else {
                                        $np_shipped = ceil($np_shipped);
                                    }
                                }
                            }

                            $nP = $nb_total_periods - $np_shipped;
                        }

                        if ($nP < 0) {
                            $nP = 0;
                        }
                    } else {
                        $nP = $qty / $round_unit;
                    }

                    if ($nP > $nb_total_periods) {
                        $nP = $nb_total_periods;
                    }

                    return $nP;
                }
            }

            $nP = $qty / $base_unit;

            if ($nP > $nb_total_periods) {
                $nP = $nb_total_periods;
            }
        }

        return $nP;
    }

    public function getFacQtyFromNbPeriods($nb_periods)
    {
        if ((float) $nb_periods && (int) $this->getData('fac_periodicity') && (int) $this->getData('fac_nb_periods')) {
            return ($this->getFullQty() / (int) $this->getData('fac_nb_periods')) * (float) $nb_periods;
        }

        return $nb_periods;
    }

    public function getFacNbPeriodsFromQty($qty)
    {
        $fullQty = (float) $this->getFullQty();

        if ((float) $qty && $fullQty && (int) $this->getData('fac_periodicity') && (int) $this->getData('fac_nb_periods')) {
            $qty = $qty / ($fullQty / (int) $this->getData('fac_nb_periods'));
        }

        return $qty;
    }

    public function getAchatQtyFromNbPeriodes($nb_periods)
    {
        if ((float) $nb_periods && (int) $this->getData('achat_periodicity') && (int) $this->getData('achat_nb_periods')) {
            return ($this->getFullQty() / (int) $this->getData('achat_nb_periods')) * (float) $nb_periods;
        }

        return $nb_periods;
    }

    public function getAchatNbPeriodsFromQty($qty)
    {
        $fullQty = (float) $this->getFullQty();

        if ((float) $qty && $fullQty && (int) $this->getData('achat_periodicity') && (int) $this->getData('achat_nb_periods')) {
            $qty = $qty / ($fullQty / (int) $this->getData('achat_nb_periods'));
        }

        return $qty;
    }

    public function getPeriodicityExtraParam($param_name)
    {
        $params = $this->getData('periodicity_extra_params');

        if (isset($params[$param_name])) {
            return $params[$param_name];
        }

        switch ($param_name) {
            case 'exp_id_contact':
                return 0;

            case 'achat_type_pa':
                return 1;

            case 'achat_id_fourn_price':
                return $this->getCommandeFournIdPrice();

            case 'achat_id_fourn':
                return 0;

            case 'achat_pa_ht':
                $product = $this->getProduct();
                if (BimpObject::objectLoaded($product)) {
                    return $product->getCurrentPaHt(BimpTools::getPostFieldValue('achat_id_fourn', null, 'int'));
                }
                return 0;

            case 'achat_tva_tx':
                return 20;
        }

        return '';
    }

    public static function getNbPeriodsToProcess($type, $params = array())
    {
        if (!in_array($type, array('exp', 'fac', 'achat'))) {
            return 0;
        }

        $params = BimpTools::overrideArray(array(
                    'id_client'  => 0,
                    'id_fourn'   => 0,
                    'id_product' => 0
                        ), $params);

        $staticComm = BimpObject::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande');

        $sql = 'SELECT COUNT(DISTINCT l.id) as nb FROM ' . MAIN_DB_PREFIX . 'bimp_commande_line l';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'commande c ON c.rowid = l.id_obj';

        if ((int) $params['id_product']) {
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'commandedet cdet ON l.id_line = cdet.rowid';
        }

        $sql .= ' WHERE l.' . $type . '_periodicity > 0 AND ';
        $sql .= '(l.next_date_' . $type . ' IS NULL OR l.next_date_' . $type . ' <= \'' . date('Y-m-d') . '\')';
        $sql .= ' AND c.fk_statut = 1 AND c.logistique_status IN (' . implode(',', Bimp_Commande::$logistique_active_status) . ')';

        if ((int) $params['id_client']) {
            $sql .= ' AND c.fk_soc = ' . $params['id_client'];
        }

        if ((int) $params['id_product']) {
            $sql .= ' AND cdet.fk_product = ' . $params['id_product'];
        }

        if ($type == 'achat' && (int) $params['id_fourn']) {
            $sql .= ' AND l.achat_id_fourn = ' . $params['id_fourn'];
        }


        if (BimpTools::isModuleDoliActif('MULTICOMPANY')) {
            if ($staticComm->getEntity_name()) {
                $sql .= ' AND c.entity IN (' . getEntity($staticComm->getEntity_name()) . ')';
            }
        }

        $sql .= ' AND l.id_contrat_line_export = 0';

        $result = self::getBdb()->executeS($sql, 'array');

        if (isset($result[0]['nb'])) {
            return (int) $result[0]['nb'];
        }

        return 0;
    }

    public static function getPeriodicExpLinesToProcess(&$errors = array(), $id_client = null, $id_product = null)
    {
        $lines = array();

        BimpObject::loadClass('bimpcommercial', 'Bimp_Commande');

        $filters = array(
            'c.fk_statut'              => 1,
            'c.logistique_status'      => Bimp_Commande::$logistique_active_status,
            'a.id_contrat_line_export' => 0,
            'a.exp_periodicity'        => array(
                'operator' => '>',
                'value'    => 0
            ),
            'a.next_date_exp'          => array(
                'or_field' => array(
                    'IS_NULL',
                    '0000-00-00',
                    array(
                        'operator' => '<=',
                        'value'    => date('Y-m-d')
                    )
                )
            )
        );

        $id_lines = BimpTools::getPostFieldValue('id_objects', array(), 'array');

        if (!empty($id_lines)) {
            $filters['a.id'] = $id_lines;
        }

        BimpTools::addSqlFilterEntity($filters, BimpObject::getInstance('bimpcommercial', 'Bimp_Commande'), 'c');

        $joins = array(
            'c' => array(
                'table' => 'commande',
                'on'    => 'c.rowid = a.id_obj'
            )
        );

        if ($id_client) {
            $filters['c.fk_soc'] = $id_client;
        }

        if ($id_product) {
            $joins['cdet'] = array(
                'table' => 'commandedet',
                'on'    => 'cdet.rowid = a.id_line'
            );
            $filters['cdet.fk_product'] = $id_product;
        }

        $fields = array('DISTINCT a.id');

        $sql = BimpTools::getSqlFullSelectQuery('bimp_commande_line', $fields, $filters, $joins, array(
                    'order_by'  => 'id',
                    'order_way' => 'desc'
        ));

        $bdb = BimpCache::getBdb();

        $rows = $bdb->executeS($sql, 'array');

        if (is_null($rows)) {
            $errors[] = 'Echec de la récupération des facturations périodiques à traiter - ' . $bdb->err();
        } else {
            foreach ($rows as $r) {
                if (!in_array((int) $r['id'], $lines)) {
                    $lines[] = (int) $r['id'];
                }
            }
        }

        return $lines;
    }

    public static function getPeriodicFacLinesToProcess(&$errors = array(), $id_client = null, $id_product = null, $return_list = false)
    {
        $lines = array();

        BimpObject::loadClass('bimpcommercial', 'Bimp_Commande');

        $filters = array(
            'c.fk_statut'              => 1,
            'c.logistique_status'      => Bimp_Commande::$logistique_active_status,
            'c.invoice_status'         => array(0, 1, 3),
            'a.id_contrat_line_export' => 0,
            'a.fac_periodicity'        => array(
                'operator' => '>',
                'value'    => 0
            ),
            'a.next_date_fac'          => array(
                'or_field' => array(
                    'IS_NULL',
                    '0000-00-00',
                    array(
                        'operator' => '<=',
                        'value'    => date('Y-m-d')
                    )
                )
            )
        );

        $id_lines = BimpTools::getPostFieldValue('id_objects', array(), 'array');

        if (!empty($id_lines)) {
            $filters['a.id'] = $id_lines;
        }

        BimpTools::addSqlFilterEntity($filters, BimpObject::getInstance('bimpcommercial', 'Bimp_Commande'), 'c');

        $joins = array(
            'c'   => array(
                'table' => 'commande',
                'on'    => 'c.rowid = a.id_obj'
            ),
            'cef' => array(
                'table' => 'commande_extrafields',
                'on'    => 'c.rowid = cef.fk_object'
            )
        );

        if ($id_client) {
            $filters['c.fk_soc'] = $id_client;
        }

        if ($id_product) {
            $joins['cdet'] = array(
                'table' => 'commandedet',
                'on'    => 'cdet.rowid = a.id_line'
            );
            $filters['cdet.fk_product'] = $id_product;
        }

        $fields = array('DISTINCT a.id as id_line', 'c.fk_soc as id_client', 'c.id_client_facture', 'cef.entrepot as id_entrepot', 'cef.type as secteur', 'c.fk_mode_reglement as id_mode_reglement', 'c.fk_cond_reglement as id_cond_reglement');

        $sql = BimpTools::getSqlFullSelectQuery('bimp_commande_line', $fields, $filters, $joins, array(
                    'order_by'  => 'id',
                    'order_way' => 'desc'
        ));

        $bdb = BimpCache::getBdb();

        $rows = $bdb->executeS($sql, 'array');

        if (is_null($rows)) {
            $errors[] = 'Echec de la récupération des achats périodiques à traiter - ' . $bdb->err();
        } else {
            if ($return_list) {
                foreach ($rows as $r) {
                    $lines[] = (int) $r['id_line'];
                }
            } else {
                foreach ($rows as $r) {
                    $id_client = (int) $r['id_client_facture'];
                    if (!$id_client) {
                        $id_client = (int) $r['id_client'];
                    }
                    if (!isset($lines[$id_client])) {
                        $lines[$id_client] = array();
                    }

                    $lines[$id_client][(int) $r['id_line']] = array(
                        'id_entrepot'       => (int) $r['id_entrepot'],
                        'secteur'           => $r['secteur'],
                        'id_mode_reglement' => $r['id_mode_reglement'],
                        'id_cond_reglement' => $r['id_cond_reglement']
                    );
                }
            }
        }

        return $lines;
    }

    public static function getPeriodicAchatLinesToProcess(&$errors = array(), $id_fourn = null, $id_client = null, $id_product = null, $return_list = false)
    {
        $lines = array();

        BimpObject::loadClass('bimpcommercial', 'Bimp_Commande');

        $filters = array(
            'c.fk_statut'              => 1,
            'c.logistique_status'      => Bimp_Commande::$logistique_active_status,
            'a.id_contrat_line_export' => 0,
            'a.achat_periodicity'      => array(
                'operator' => '>',
                'value'    => 0
            ),
            'a.next_date_achat'        => array(
                'or_field' => array(
                    'IS_NULL',
                    '0000-00-00',
                    array(
                        'operator' => '<=',
                        'value'    => date('Y-m-d')
                    )
                )
            )
        );

        $id_lines = BimpTools::getPostFieldValue('id_objects', array(), 'array');

        if (!empty($id_lines)) {
            $filters['a.id'] = $id_lines;
        }

        BimpTools::addSqlFilterEntity($filters, BimpObject::getInstance('bimpcommercial', 'Bimp_Commande'), 'c');

        $joins = array(
            'c'   => array(
                'table' => 'commande',
                'on'    => 'c.rowid = a.id_obj'
            ),
            'cef' => array(
                'table' => 'commande_extrafields',
                'on'    => 'c.rowid = cef.fk_object'
            )
        );

        if ($id_client) {
            $filters['c.fk_soc'] = $id_client;
        }

        if ($id_product) {
            $joins['cdet'] = array(
                'table' => 'commandedet',
                'on'    => 'cdet.rowid = a.id_line'
            );
            $filters['cdet.fk_product'] = $id_product;
        }

        $fields = array('DISTINCT a.id', 'cef.entrepot');

        if ($id_fourn) {
            $filters['a.achat_id_fourn'] = $id_fourn;
        }


        $sql = BimpTools::getSqlFullSelectQuery('bimp_commande_line', $fields, $filters, $joins, array(
                    'order_by'  => 'id',
                    'order_way' => 'desc'
        ));

        $bdb = BimpCache::getBdb();

        $rows = $bdb->executeS($sql, 'array');

        if (is_null($rows)) {
            $errors[] = 'Echec de la récupération des achats périodiques à traiter - ' . $bdb->err();
        } else {
            foreach ($rows as $r) {
                if ($return_list) {
                    if (!in_array((int) $r['id'], $lines)) {
                        $lines[] = (int) $r['id'];
                    }
                } else {
                    if (!isset($lines[(int) $r['id']])) {
                        $lines[(int) $r['id']] = array('id_entrepot' => (int) $r['entrepot']);
                    }
                }
            }
        }

        return $lines;
    }

    // Getters Array:

    public function getSelectShipmentsArray($include_empty = false, $empty_label = '', $id_contact = null)
    {
        $shipments = array();

        if ($include_empty) {
            $shipments[0] = $empty_label;
        }

        $commande = $this->getParentInstance();

        if (BimpObject::objectLoaded($commande)) {
            $cs = BimpObject::getInstance('bimplogistique', 'BL_CommandeShipment');

            $filters = array(
                'id_commande_client' => (int) $commande->id,
                'status'             => 1
            );

            if (!is_null($id_contact)) {
                $filters['id_contact'] = (int) $id_contact;
            }

            foreach ($cs->getList($filters) as $row) {
                $shipments[(int) $row['id']] = 'Expédition n°' . $row['num_livraison'];
            }
        }

        return $shipments;
    }

    public function getCommandesFournisseursArray($id_fourn = 0, $id_entrepot = 0)
    {
        $commandes = array(
            'new' => 'Nouvelle commande'
        );

        if (!(int) $id_entrepot) {
            if (BimpTools::isPostFieldSubmit('id_entrepot')) {
                $id_entrepot = (int) BimpTools::getPostFieldValue('id_entrepot', 0, 'int');
            } elseif ($this->isLoaded()) {
                $commande = $this->getParentInstance();

                if (BimpObject::objectLoaded($commande)) {
                    $id_entrepot = (int) $commande->getData('entrepot');
                }
            }
        }

        if (!(int) $id_fourn) {
            $type_price = (int) BimpTools::getPostFieldValue('type_price', 1, 'int');
            switch ($type_price) {
                case 1:
                    $id_price = (int) BimpTools::getPostFieldValue('id_fourn_price', 0, 'int');
                    if (!$id_price && $this->isLoaded()) {
                        $id_price = (int) $this->getCommandeFournIdPrice();
                    }
                    if ($id_price) {
                        $fournPrice = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ProductFournisseurPrice', $id_price);
                        if ($fournPrice->isLoaded()) {
                            $id_fourn = (int) $fournPrice->getData('fk_soc');
                        }
                    }
                    break;

                case 2:
                    $id_fourn = (int) BimpTools::getPostFieldValue('id_fourn', 0, 'int');
                    break;
            }
        }

        if ($id_fourn /*&& $id_entrepot*/) {
            $sql = 'SELECT cf.rowid as id, cf.ref, cf.date_creation as date, s.nom FROM ' . MAIN_DB_PREFIX . 'commande_fournisseur cf';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'commande_fournisseur_extrafields cfe ON cf.rowid = cfe.fk_object';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'societe s ON s.rowid = cf.fk_soc';
            $sql .= ' WHERE cf.fk_soc = ' . (int) $id_fourn . ' AND cf.fk_statut = 0';
            if($id_entrepot)
                $sql .= ' AND cfe.entrepot = ' . (int) $id_entrepot;
            $sql .= ' ORDER BY cf.rowid DESC';

            $rows = $this->db->executeS($sql);
            if (!is_null($rows) && count($rows)) {
                foreach ($rows as $obj) {
                    $DT = new DateTime($obj->date);
                    $commandes[(int) $obj->id] = $obj->nom . ' ' . $obj->ref . ' - Créée le ' . $DT->format('d / m / Y à H:i');
                }
            }
        }
        return $commandes;
    }

    public function getClientEquipmentsArray($return_available_only = true)
    {
        $commande = $this->getParentInstance();

        if (BimpObject::objectLoaded($commande) && (int) $this->id_product) {
            $items = BimpCache::getSocieteProductEquipmentsArray((int) $commande->getData('fk_soc'), (int) $this->id_product, true);

            if ($return_available_only) {
                foreach ($items as $id_equipment => $label) {
                    $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                    if (!BimpObject::objectLoaded($equipment)) {
                        unset($items[$id_equipment]);
                        continue;
                    }

                    $err = array();
                    if (!$equipment->isAvailable(0, $err, array(), array('sav'))) {
                        unset($items[$id_equipment]);
                    }
                }
            }

            return $items;
        }

        return array();
    }

    public static function getReservationsStatusArray()
    {
        BimpObject::loadClass('bimpreservation', 'BR_Reservation');
        return BR_Reservation::getStatusListArrayStatic(BR_Reservation::BR_RESERVATION_COMMANDE);
    }

    // Affichages:

    public function displayQty($qty_type = 'total')
    {
        if ($this->field_exists('qty_' . $qty_type)) {
            $qty = (float) $this->getData('qty_' . $qty_type);
            $class = '';

            $full_qty = abs((float) $this->getFullQty());
            switch ($qty_type) {
                case 'shipped':
                case 'billed':
                    if (abs($qty) === $full_qty) {
                        $class = 'success';
                    } elseif (abs($qty) <= 0) {
                        $class = 'danger';
                    } elseif (abs($qty) < $full_qty) {
                        $class = 'warning';
                    }
                    break;

                case 'to_ship';
                case 'to_bill':
                case 'billed_not_shipped':
                case 'shipped_not_billed':
                    if ($qty == 0) {
                        $class = 'success';
                    } elseif (abs($qty) < $full_qty) {
                        $class = 'warning';
                    } else {
                        $class = 'danger';
                    }
                    break;

                case 'total':
                    if ($qty < 0) {
                        $class = 'important';
                    }
            }

            return '<span class="badge badge-' . ($class ? $class : 'default') . '">' . round($qty, 4) . '</span>';
        }

        return '';
    }

    public function displayQties()
    {
        $html = '';
        $total_qty = (float) $this->getFullQty();
        $modif_qty = (float) $this->getData('qty_modif');
        $is_return = ($total_qty < 0);

        $has_exp_periods = ((int) $this->getData('exp_periodicity') ? true : false);
        $has_fac_periods = ((int) $this->getData('fac_periodicity') ? true : false);

        // Qté totale
        if ($modif_qty) {
            $popover = 'Qtés totales';
            if ($is_return) {
                $popover .= ' (Qtés retournées +/- qtés modifiées)';
            } else {
                $popover .= ' (Qtés commandées +/- qtés modifiées)';
            }
        } else {
            if ($is_return) {
                $popover = 'Qtés retournées';
            } else {
                $popover = 'Qtés commandées';
            }
        }

        $html .= '<div style="display: inline-block;">';
        $html .= '<span class="bold bs-popover"' . BimpRender::renderPopoverData($popover) . ' style="margin-right: 15px; padding: 3px 0;">';

        $html .= '<span' . ($is_return ? ' class="important"' : '') . '>';
        $html .= BimpRender::renderIcon($is_return ? 'fas_arrow-circle-left' : 'fas_dolly', 'iconLeft');
        $html .= $total_qty;
        $html .= '</span>';

        if ($modif_qty) {
            $html .= '&nbsp;<span class="important">';
            $html .= '(' . ($total_qty - $modif_qty) . ($modif_qty > 0 ? '+' : '-') . abs($modif_qty) . ')';
            $html .= '</span>';
        }
        $html .= '</span>';

        // Qté dispo
        if ($total_qty > 0) {
            $commande = $this->getParentInstance();
            if (BimpObject::objectLoaded($commande)) {
                if ((int) $this->id_product) {
                    $product = $this->getProduct();
                    if (BimpObject::objectLoaded($product)) {
                        if ($product->getData('fk_product_type') === 0) {
                            $qties_reserved = $this->getReservedQties();
                            $qty_wanted = ($qties_reserved['not_reserved'] - $qties_reserved['ordered']);
                            $qty_available = 0;
                            $stocks = $product->getStocksForEntrepot((int) $commande->getData('entrepot'), 'dispo');
                            if (isset($stocks['dispo'])) {
                                $qty_available = $stocks['dispo'];
                            }
                            if ($qty_wanted > 0 && $qty_available < $qty_wanted) {
                                $class = 'danger';
                            } elseif ($qty_wanted > 0 && $qty_available === $qty_wanted) {
                                $class = 'warning';
                            } else {
                                $class = 'success';
                            }

                            $popover = '<span style="font-weight: bold">Qtés disponibles / nécessaires</span><br/>';
                            $popover .= 'Déjà traitées: ' . $qties_reserved['reserved'] . '<br/>';
                            $popover .= 'Commandées au fournisseur: ' . $qties_reserved['ordered'] . '<br/>';
                            $popover .= 'A traiter: ' . $qty_wanted . '<br/>';

                            $html .= '<span style="display: inline-block; margin-right: 15px; padding: 3px 0;"';
                            $html .= BimpRender::renderPopoverData($popover, 'top', 'true');
                            $html .= ' class="bs-popover ' . $class . '">';
                            $html .= BimpRender::renderIcon('fas_box-open', 'iconLeft');
                            $html .= $qty_available . ' / ' . $qty_wanted . '</span>';
                        }
                    }
                }
            }
        }
        $html .= '</div>';

        $html .= '<div style="display: inline-block;">';

        // Qté expédiée:
        $shipments_qty = (float) $this->getShipmentsQty();

        if ($shipments_qty) {
            $qty_shipped = round((float) $this->getShippedQty(), 6);
            $qty_shipped_valid = round((float) $this->getShippedQty(null, true), 6);

            if ((int) $this->getData('id_contrat_line_export') && $qty_shipped == $qty_shipped_valid) {
                $class = 'success';
            } elseif (!abs($qty_shipped_valid)) {
                $class = 'danger';
            } elseif (abs($qty_shipped) < abs($shipments_qty)) {
                $class = 'warning';
            } else {
                $class = 'success';
            }


            $html .= '<span class="bs-popover ' . $class . '" style="display: inline-block; margin-right: 15px; padding: 3px 0;"';
            $html .= BimpRender::renderPopoverData('Qtés ajoutées à une expédition ' . ($has_exp_periods ? '(n périodes) ' : '') . '/ Qtés expédiées' . ($has_exp_periods ? ' (n périodes)' : ''));
            $html .= '>';
            $html .= BimpRender::renderIcon('fas_shipping-fast', 'iconLeft');

            if ((int) $this->getData('id_contrat_line_export') && $qty_shipped == $qty_shipped_valid) {
                $class = 'success';
            } elseif (!abs($qty_shipped)) {
                $class = 'danger';
            } elseif (abs($qty_shipped) < abs($shipments_qty)) {
                $class = 'warning';
            } else {
                $class = 'success';
            }

            $nP = 0;
            if ($has_exp_periods) {
                $nP = $this->getExpNbPeriodsFromQty($qty_shipped);
                $html .= '<span class="' . $class . '">' . round($qty_shipped, 4) . ($nP ? ' (' . BimpTools::displayFloatValue(round($nP, 4), 2, ',', 0, 0, 0, 1, 1, 1) . 'p)' : '') . '</span>';
            } else {
                $html .= '<span class="' . $class . '">' . $qty_shipped . '</span>';
            }


            $html .= ' / ';

            if ((int) $this->getData('id_contrat_line_export') && $qty_shipped == $qty_shipped_valid) {
                $class = 'success';
            } elseif (!abs($qty_shipped_valid)) {
                $class = 'danger';
            } elseif (abs($qty_shipped_valid) < abs($shipments_qty)) {
                $class = 'warning';
            } else {
                $class = 'success';
            }

            $nP = 0;
            if ($has_exp_periods) {
                $nP = $this->getExpNbPeriodsFromQty($qty_shipped_valid);
                $html .= '<span class="' . $class . '">' . round($qty_shipped_valid, 4) . ($nP ? ' (' . BimpTools::displayFloatValue(round($nP, 4), 2, ',', 0, 0, 0, 1, 1, 1) . 'p)' : '') . '</span>';
            } else {
                $html .= '<span class="' . $class . '">' . $qty_shipped_valid . '</span>';
            }

            $html .= '</span>';
        }

        // Qté facturée: 
        $qty_billed = round((float) $this->getBilledQty(), 6);
        $qty_billed_valid = round((float) $this->getBilledQty(null, true), 6);

        if ((int) $this->getData('id_contrat_line_export') && $qty_billed_valid === $qty_billed) {
            $class = 'success';
        } elseif (!abs($qty_billed_valid)) {
            $class = 'danger';
        } elseif (abs($qty_billed_valid) < abs($total_qty)) {
            $class = 'warning';
        } else {
            $class = 'success';
        }


        $html .= '<span class="bs-popover ' . $class . '" style="display: inline-block; padding: 3px 0;"';
        $html .= BimpRender::renderPopoverData('Qtés ajoutées à une facture ' . ($has_fac_periods ? '(n périodes) ' : '') . '/ Qtés facturées' . ($has_fac_periods ? ' (n périodes)' : ''));
        $html .= '>';
        $html .= BimpRender::renderIcon('fas_file-invoice-dollar', 'iconLeft');

        if ((int) $this->getData('id_contrat_line_export') && $qty_billed_valid === $qty_billed) {
            $class = 'success';
        } elseif (!abs($qty_billed)) {
            $class = 'danger';
        } elseif (abs($qty_billed) < abs($total_qty)) {
            $class = 'warning';
        } else {
            $class = 'success';
        }

        $nP = 0;
        if ($has_fac_periods) {
            $nP = $qty_billed;

            if ($total_qty) {
                $nP /= $total_qty;
            }

            $nP *= $this->getData('fac_nb_periods');
            $html .= '<span class="' . $class . '">' . round($qty_billed, 4) . ($nP ? ' (' . BimpTools::displayFloatValue(round($nP, 4), 2, ',', 0, 0, 0, 1, 1, 1) . 'p)' : '') . '</span>';
        } else {
            $html .= '<span class="' . $class . '">' . $qty_billed . '</span>';
        }

        $html .= ' / ';

        if ((int) $this->getData('id_contrat_line_export') && $qty_billed_valid === $qty_billed) {
            $class = 'success';
        } elseif (!abs($qty_billed_valid)) {
            $class = 'danger';
        } elseif (abs($qty_billed_valid) < abs($total_qty)) {
            $class = 'warning';
        } else {
            $class = 'success';
        }

        $nP = 0;
        if ($has_fac_periods) {
            $nP = $qty_billed_valid;

            if ($total_qty) {
                $nP /= $total_qty;
            }

            $nP *= $this->getData('fac_nb_periods');
            $html .= '<span class="' . $class . '">' . round($qty_billed_valid, 4) . ($nP ? ' (' . BimpTools::displayFloatValue(round($nP, 4), 2, ',', 0, 0, 0, 1, 1, 1) . 'p)' : '') . '</span>';
        } else {
            $html .= '<span class="' . $class . '">' . $qty_billed_valid . '</span>';
        }

        $html .= '</span>';
        $html .= '</div>';

        return $html;
    }

    public function displayReservationsStatus($hideMore = true)
    {
        $html = '<div id="resa_ln_' . $this->id . '">';

        $qty = (float) $this->getFullQty();

        if ($qty >= 0) {
            $product = $this->getProduct();
            if (!BimpObject::objectLoaded($product)) {
                return '';
            }

            if ($product->isTypeProduct()) {
                $res_by_status = $this->getReservations('status', 'asc', null, null, array(
                    'ids_only'  => true,
                    'by_status' => true
                ));
                $serialisable = 0;
                if (BimpObject::objectLoaded($product)) {
                    $serialisable = $product->isSerialisable();
                }

                $nReservations = 0;

                foreach ($res_by_status as $status => $res_list) {
                    if (is_array($res_list)) {
                        $nReservations += count($res_list);
                    }
                }

                if ($nReservations > 0) {
                    $use_cache = ($nReservations < 250 ? true : false);
                    if ($nReservations > 1) {
                        $html .= '<div class="smallActionsContainer">';
                        $html .= '<span class="small-action" onclick="checkAll($(this).parent().parent(), \'.reservation_check\');">';
                        $html .= BimpRender::renderIcon('fas_check-square', 'iconLeft') . 'Tout sélectionner';
                        $html .= '</span>';
                        $html .= '<span class="small-action" onclick="uncheckAll($(this).parent().parent(), \'.reservation_check\');">';
                        $html .= BimpRender::renderIcon('far_square', 'iconLeft') . 'Tout désélectionner';
                        $html .= '</span>';
                        $html .= '</div>';
                    }

                    $html .= '<table class="bimp_list_table Bimp_Commande_line_reservations_table">';
                    $html .= '<tbody class="reservations_rows">';

                    foreach ($res_by_status as $status => $res_list) {
                        $hidable = (count($res_list) > 10);

                        if ($hidable) {
                            $html .= '<tr>';
                            $html .= '<td style="width: 45px"></td>';
                            $html .= '<td style="width: 250px">';
                            $html .= '<span class="' . BR_Reservation::$status_list[$status]['classes'][0] . '">';
                            $html .= BimpRender::renderIcon(BR_Reservation::$status_list[$status]['icon'], 'iconLeft');
                            $html .= BR_Reservation::$status_list[$status]['label'];
                            $html .= '</span>';
                            $html .= '</td>';
                            $html .= '<td style="80px;">';
                            $html .= 'Nombre de lignes: ' . count($res_list);
                            $html .= '</td>';

                            if ($serialisable) {
                                $html .= '<td></td>';
                            }

                            $html .= '<td>';
                            if ($hideMore) {
                                $onClick = $this->getJsLoadCustomContent('displayReservationsStatus', '$(\'#resa_ln_' . $this->id . '\')', array(0), array('button' => ''));
                                $html .= '<span class="bold hover showLines" onclick="' . $onClick . '">';
                                $html .= BimpRender::renderIcon('fas_bars', 'iconLeft') . 'Afficher les lignes' . BimpRender::renderIcon('fas_caret-down', 'iconRight');
                                $html .= '</span>';
                            } else {
                                $html .= '<span class="bold hover showLines" onclick="$(this).findParentByClass(\'reservations_rows\').find(\'.Bimp_CommandeLine_reservation_row.status_' . $status . '\').show();$(this).hide().parent(\'td\').find(\'span.hideLines\').show();" style="display: none">';
                                $html .= BimpRender::renderIcon('fas_bars', 'iconLeft') . 'Afficher les lignes' . BimpRender::renderIcon('fas_caret-down', 'iconRight');
                                $html .= '</span>';
                                $html .= '<span class="bold hover hideLines" onclick="$(this).findParentByClass(\'reservations_rows\').find(\'.Bimp_CommandeLine_reservation_row.status_' . $status . '\').hide();$(this).hide().parent(\'td\').find(\'span.showLines\').show();">';
                                $html .= BimpRender::renderIcon('fas_bars', 'iconLeft') . 'Masquer les lignes' . BimpRender::renderIcon('fas_caret-up', 'iconRight');
                                $html .= '</span>';
                                $html .= '</td>';
                                $html .= '</tr>';
                            }
                        }

                        if (!$hidable || !$hideMore) {
                            foreach ($res_list as $id_res) {
                                if ($use_cache) {
                                    $reservation = BimpCache::getBimpObjectInstance('bimpreservation', 'BR_Reservation', (int) $id_res);
                                } else {
                                    $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation', (int) $id_res);
                                }

                                if (!BimpObject::objectLoaded($reservation)) {
                                    unset($reservation);
                                    continue;
                                }

                                $buttons = $reservation->getListExtraBtn();
                                $buttons[] = array(
                                    'label'   => 'Vue rapide',
                                    'icon'    => 'fas_eye',
                                    'onclick' => $reservation->getJsLoadModalView('default')
                                );

                                $html .= '<tr class="Bimp_CommandeLine_reservation_row status_' . $status . '"' . /* ($hidable ? ' style="display: none"' : '') . */ '>';
                                $html .= '<td style="text-align: center; width: 45px">';
                                $html .= '<input type="checkbox" name="reservation_check[]" value="' . $reservation->id . '" class="reservation_check"';
                                $html .= ' data-id_commande_line="' . $this->id . '"';
                                $html .= ' data-id_reservation="' . $reservation->id . '"';
                                $html .= '/>';
                                $html .= '</td>';
                                //                $html .= '<td>' . $reservation->getData('ref') . '</td>';
                                $html .= '<td style="width: 250px;">';
                                $html .= $reservation->displayData('status');
                                if ($serialisable && (int) $reservation->getData('status') >= 200) {
                                    if ($nReservations > 500) {
                                        $html .= '<br/>';
                                        if ((int) $reservation->getData('id_equipment')) {
                                            $html .= BimpRender::renderIcon('fas_desktop', 'iconLeft') . ' ' . $this->db->getValue('be_equipment', 'serial', 'id = ' . (int) $reservation->getData('id_equipment'));
                                        } else {
                                            $html .= '<span class="warning">Non attribué</span>';
                                        }
                                    } else {
                                        $html .= '<br/>' . $reservation->displayEquipment();
                                    }
                                }
                                $html .= '</td>';
                                $html .= '<td style="width: 80px;">Qté: ' . $reservation->getData('qty') . '</td>';
                                if ($serialisable) {
                                    $id_equipment = (int) $reservation->getData('id_equipment');
                                    if ((int) $id_equipment) {
                                        $html .= '<td>';
                                        $id_shipment = (int) $this->getEquipmentIdShipment($id_equipment);
                                        if ($id_shipment) {
                                            $shipment = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeShipment', $id_shipment);
                                            if (BimpObject::objectLoaded($shipment)) {
                                                $html .= '<span class="' . BL_CommandeShipment::$status_list[(int) $shipment->getData('status')]['classes'][0] . '">';
                                                $html .= 'Exp: n°' . $shipment->getData('num_livraison');
                                                $html .= '</span>';
                                            }
                                        } else {
                                            $commande = $this->getParentInstance();
                                            if (BimpObject::objectLoaded($commande)) {
                                                $onclick = $commande->getJsActionOnclick('addEquipmentsToShipment', array(
                                                    'reservations' => $reservation->id
                                                        ), array(
                                                    'form_name'      => 'shipment_equipments',
                                                    'on_form_submit' => 'function($form, extra_data) {return onShipmentEquipmentsFormSubmit($form, extra_data);}'
                                                ));
                                                $html .= 'Exp: ';
                                                $html .= '<button class="btn btn-default btn-small" onclick="' . $onclick . '">';
                                                $html .= 'Attribuer' . BimpRender::renderIcon('fas_arrow-circle-right', 'iconRight');
                                                $html .= '</button>';
                                            }
                                        }

                                        $id_facture = (int) $this->getEquipmentIdFacture($id_equipment);
                                        if ($id_facture) {
                                            $html .= '<br/>Fac: ';
                                            if ($id_facture === -1) {
                                                $html .= '<span class="warning">Facturé hors BIMP-ERP</span>';
                                            } else {
                                                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_facture);
                                                if (BimpObject::objectLoaded($facture)) {
                                                    $html .= $facture->getLink();
                                                } else {
                                                    $html .= BimpRender::renderAlerts('La facture d\'ID ' . $id_facture . ' n\'existe plus');
                                                }
                                            }
                                        }
                                        $html .= '</td>';
                                    }
                                }
                                $html .= '<td>';
                                foreach ($buttons as $button) {
                                    $html .= BimpRender::renderRowButton($button['label'], $button['icon'], $button['onclick'], isset($button['class']) ? $button['class'] : '', isset($button['attrs']) ? $button['attrs'] : array());
                                }
                                $html .= '</td>';
                                $html .= '</tr>';

                                if (!$use_cache) {
                                    unset($reservation);
                                    $reservation = null;
                                }
                            }
                        }
                    }
                    $html .= '</tbody>';
                    $html .= '</table>';
                }
            } else {
                // Recherche des unités commandées: 
                $qty_ordered = 0;
                $qty_received = 0;

                $fournLines = BimpCache::getBimpObjectObjects('bimpcommercial', 'Bimp_CommandeFournLine', array(
                            'linked_object_name' => 'commande_line',
                            'linked_id_object'   => (int) $this->id
                ));

                foreach ($fournLines as $line) {
                    $line_qty_received = (float) $line->getReceivedQty(null, true);
                    $qty_ordered += (float) $line->getFullQty() - $line_qty_received;
                    $qty_received += $line_qty_received;
                }

                if ($qty_ordered) {
                    $html .= '<span class="warning">' . BimpRender::renderIcon('fas_cart-arrow-down', 'iconLeft') . $qty_ordered . ' unité(s) en attente de réception</span>';
                }
                if ($qty_received) {
                    $html .= ($html ? '<br/>' : '') . '<span class="success">' . BimpRender::renderIcon('fas_cart-arrow-down', 'iconLeft') . $qty_received . ' unité(s) reçues</span>';
                }
            }
        } else {
            if ($this->isProductSerialisable()) {
                // Liste des équipements retournés:

                $equipments = $this->getData('equipments_returned');

                $html .= '<table class="bimp_list_table">';
                $html .= '<tbody>';
                $html .= '<tr>';
                $html .= '<td><strong>Equipements retournés: </strong></td>';
                $html .= '<td colspan="3" style="text-align: right">';

                if ((abs($this->getFullQty()) - count($equipments)) > 0) {
                    $onclick = $this->getJsActionOnclick('addReturnedEquipments', array(), array(
                        'form_name' => 'equipments_return'
                    ));

                    $html .= '<span class="btn btn-default btn-small" onclick="' . $onclick . '">';
                    $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Ajouter des équipements';
                    $html .= '</span>';
                }

                $html .= '</td>';
                $html .= '</tr>';

                if (empty($equipments)) {
                    $html .= '<tr>';
                    $html .= '<td colspan="4">';
                    $html .= '<div style="text-align: center">';
                    $html .= BimpRender::renderAlerts('Aucun équipement retourné enregistré', 'info');
                    $html .= '</div>';
                    $html .= '</td>';
                    $html .= '</tr>';
                } else {
                    BimpTools::loadDolClass('product/stock', 'entrepot');
                    foreach ($equipments as $id_equipment => $id_entrepot) {
                        $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                        if (BimpObject::objectLoaded($equipment)) {
                            $id_shipment = (int) $this->getEquipmentIdShipment($id_equipment);
                            if ($id_shipment) {
                                $shipment = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeShipment', $id_shipment);
                            } else {
                                $shipment = null;
                            }

                            $id_facture = (int) $this->getEquipmentIdFacture($id_equipment);
                            if ($id_facture > 0) {
                                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_facture);
                            } else {
                                $facture = null;
                            }

                            $html .= '<tr>';
                            $html .= '<td>';
                            $html .= $equipment->getLink();
                            $html .= '</td>';
                            $html .= '<td>';

                            if (!BimpObject::objectLoaded($shipment) || (int) $shipment->getData('status') === BL_CommandeShipment::BLCS_BROUILLON) {
                                if (!(int) $id_entrepot) {
                                    if (BimpObject::objectLoaded($shipment)) {
                                        $id_entrepot = (int) $shipment->getData('id_entrepot');
                                    } else {
                                        $id_entrepot = (int) $commande->getData('entrepot');
                                    }
                                }

                                $html .= '<p class="smallInfo">Entrepôt de destination: </p>';
                                $html .= BimpInput::renderInput('search_entrepot', 'line_' . $this->id . '_equipment_' . $id_equipment . '_id_entrepot', (int) $id_entrepot, array(
                                            'extra_class' => 'equipment_returned_id_entrepot'
                                ));
                            } elseif ((int) $id_entrepot) {
                                $entrepot = BimpCache::getDolObjectInstance((int) $id_entrepot, 'product/stock', 'entrepot');
                                if (BimpObject::objectLoaded($entrepot)) {
                                    $html .= 'Dest: ' . $entrepot->getNomUrl(1);
                                }
                            }
                            $html .= '</td>';
                            $html .= '<td>';

                            if ($id_shipment) {
                                if (BimpObject::objectLoaded($shipment)) {
                                    $html .= '<span class="' . BL_CommandeShipment::$status_list[(int) $shipment->getData('status')]['classes'][0] . '">';
                                    $html .= 'Exp: n°' . $shipment->getData('num_livraison');
                                    $html .= '</span>';
                                } else {
                                    $html .= BimpRender::renderAlerts('L\'expédition d\'ID ' . $id_shipment . ' n\'existe plus');
                                }
                            }

                            if ($id_facture) {
                                if ($id_shipment) {
                                    $html .= '<br/>';
                                }
                                $html .= 'Fac: ';
                                if ($id_facture === -1) {
                                    $html .= '<span class="warning">Facturé hors BIMP-ERP</span>';
                                } else {
                                    if (BimpObject::objectLoaded($facture)) {
                                        $html .= $facture->getLink();
                                    } else {
                                        $html .= BimpRender::renderAlerts('La facture d\'ID ' . $id_facture . ' n\'existe plus');
                                    }
                                }
                            }
                            $html .= '</td>';
                            $html .= '<td style="text-align: right">';
                            if ((int) $id_facture !== -1 && ((!BimpObject::objectLoaded($facture) || !(int) $facture->getData('fk_statut')) &&
                                    (!BimpObject::objectLoaded($shipment) || (int) $shipment->getData('status') === BL_CommandeShipment::BLCS_BROUILLON))) {
                                $onclick = $this->getJsActionOnclick('removeReturnedEquipments', array(
                                    'equipments' => $id_equipment
                                        ), array(
                                    'confirm_msg' => 'Veuillez confirmer le retrait de l\\\'équipement ' . $equipment->getData('serial')
                                ));
                                $html .= BimpRender::renderRowButton('Retirer', 'fas_trash-alt', $onclick);
                            }
                            $html .= '</td>';
                            $html .= '</tr>';
                        }
                    }
                }

                $html .= '</tbody>';
                $html .= '</table>';
            }
        }

        return $html;
    }

    public function displayLogistiqueQties()
    {
        $html = '';

        if ($this->isLoaded()) {
            $qties = $this->getReservedQties();

            foreach ($qties['status'] as $status => $qty) {
                if (!$qty) {
                    continue;
                }
                if ($html) {
                    $html .= '<br/>';
                }

                $html .= '<span class="' . BR_Reservation::$status_list[(int) $status]['classes'][0] . '">';
                $html .= BimpRender::renderIcon(BR_Reservation::$status_list[(int) $status]['icon'], 'iconLeft');
                $html .= BR_Reservation::$status_list[(int) $status]['label'] . ': ' . $qty;
                $html .= '</span>';
            }
        }

        return $html;
    }

    public function displayServiceFin()
    {
        $product = $this->getProduct();
        if (BimpObject::objectLoaded($product) && $product->getData('2hotline') > 0) {
            $commande = $this->getParentInstance();
            if (BimpObject::objectLoaded($commande)) {
                $date = new DateTime($commande->getData('date_valid'));
                $date->add(new DateInterval('P' . $product->getData('2hotline') . 'M'));
                return $date->format('d-m-Y');
            }
        }
        return 'ff';
    }

    public function displayPeriodicity($no_html = false, $types = array())
    {
        $html = '';

        if ((int) $this->getData('id_contrat_line_export')) {
            $html .= '<br/><span class="important">' . BimpRender::renderIcon('fas_exclamation-circle', 'iconLeft') . 'Gestion des opérations périodiques transférées vers ';
            $id_contrat = (int) $this->db->getValue('contratdet', 'fk_contrat', 'rowid = ' . (int) $this->getData('id_contrat_line_export'));
            if ($id_contrat) {
                $contrat = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_Contrat', $id_contrat);
                $html .= 'le contrat d\'abonnement ' . (BimpObject::objectLoaded($contrat) ? $contrat->getLink() : '#' . $id_contrat);
            } else {
                $html .= 'la ligne de contrat d\'abonnement #' . (int) $this->getData('id_contrat_line_export');
            }
            $html .= '</span><br/>';
        }

        $extra_params = $this->getData('periodicity_extra_params');
        $shipment_same_values = false;
        $achat_same_values = false;

        $display_fac = true;
        $display_exp = true;
        $display_achat = true;

        if (!empty($types) && is_array($types)) {
            $display_fac = in_array('fac', $types);
            $display_exp = in_array('exp', $types);
            $display_achat = in_array('achat', $types);
        }

        if ((int) $this->getData('fac_periodicity') && (int) $this->getData('fac_nb_periods')) {
            $periodicity = (int) $this->getData('fac_periodicity');
            $nb_periods = (int) $this->getData('fac_nb_periods');
            $nb_months = $periodicity * $nb_periods;

            if ($periodicity === (int) $this->getData('exp_periodicity') &&
                    ($nb_periods === (int) $this->getData('exp_nb_periods'))) {
                $shipment_same_values = true;
            }

            if ($periodicity === (int) $this->getData('achat_periodicity') &&
                    ($nb_periods === (int) $this->getData('achat_nb_periods'))) {
                $achat_same_values = true;
            }

            if ($display_fac ||
                    ($shipment_same_values && $display_exp) ||
                    ($achat_same_values && $display_achat)) {
                $label = ($display_fac ? 'Facturation' : '');
                $label .= ($shipment_same_values && $display_exp ? ($label ? ' /' : '') . 'Livraison' : '');
                $label .= ($achat_same_values && $display_achat ? ($label ? ' /' : '') . 'Achat' : '');

                if ($no_html) {
                    $html .= "\n" . $label . ' ';

                    if (isset(self::$periodicities[(int) $periodicity])) {
                        if ($achat_same_values) {
                            $html .= lcfirst(self::$periodicities_masc[(int) $periodicity]);
                        } else {
                            $html .= lcfirst(self::$periodicities[(int) $periodicity]);
                        }
                    } else {
                        $html .= 'tous les ' . $periodicity . ' mois';
                    }
                    $html .= ' sur ' . $nb_months . ' mois (' . $nb_periods . ' période' . ($nb_periods > 1 ? 's' : '') . ')';
                } else {
                    $html .= '<br/><b>' . $label . ' ';
                    if (isset(self::$periodicities[(int) $periodicity])) {
                        if ($achat_same_values) {
                            $html .= lcfirst(self::$periodicities_masc[(int) $periodicity]);
                        } else {
                            $html .= lcfirst(self::$periodicities[(int) $periodicity]);
                        }
                    } else {
                        $html .= 'tous les ' . $periodicity . ' mois';
                    }
                    $html .= ' sur ' . $nb_months . ' mois<br/>(' . $nb_periods . ' période' . ($nb_periods > 1 ? 's' : '') . ')</b>';
                }
            }
        }

        if (!$shipment_same_values && (int) $this->getData('exp_periodicity') && (int) $this->getData('exp_nb_periods')) {
            $periodicity = (int) $this->getData('exp_periodicity');
            $nb_periods = (int) $this->getData('exp_nb_periods');
            $nb_months = $periodicity * $nb_periods;

            $achat_same_values_as_exp = false;

            if (!$achat_same_values && $periodicity === (int) $this->getData('achat_periodicity') &&
                    $nb_periods === (int) $this->getData('achat_nb_periods')) {
                $achat_same_values_as_exp = true;
                $achat_same_values = true;
            }

            if ($display_exp || ($achat_same_values && $display_achat)) {
                $label = ($display_exp ? 'Livraison' : '');
                $label .= ($achat_same_values && $display_achat ? ($label ? ' / ' : '') . 'Achat' : '');

                if ($no_html) {
                    $html .= "\n" . $label . ' ';
                    if (isset(self::$periodicities[(int) $periodicity])) {
                        if ($achat_same_values_as_exp) {
                            $html .= lcfirst(self::$periodicities_masc[(int) $periodicity]);
                        } else {
                            $html .= lcfirst(self::$periodicities[(int) $periodicity]);
                        }
                    } else {
                        $html .= 'tous les ' . $periodicity . ' mois';
                    }
                    $html .= ' sur ' . $nb_months . ' mois (' . $nb_periods . ' période' . ($nb_periods > 1 ? 's' : '') . ')';
                } else {
                    $html .= '<br/><b>' . $label . ' ';
                    if (isset(self::$periodicities[(int) $periodicity])) {
                        if ($achat_same_values_as_exp) {
                            $html .= lcfirst(self::$periodicities_masc[(int) $periodicity]);
                        } else {
                            $html .= lcfirst(self::$periodicities[(int) $periodicity]);
                        }
                    } else {
                        $html .= 'tous les ' . $periodicity . ' mois';
                    }
                    $html .= ' sur ' . $nb_months . ' mois<br/>(' . $nb_periods . ' période' . ($nb_periods > 1 ? 's' : '') . ')</b>';
                }
            }
        }

        if ($display_achat && !$achat_same_values && (int) $this->getData('achat_periodicity') && (int) $this->getData('achat_nb_periods')) {
            $periodicity = (int) $this->getData('achat_periodicity');
            $nb_periods = (int) $this->getData('achat_nb_periods');
            $nb_months = $periodicity * $nb_periods;

            if ($no_html) {
                $html .= "\n" . 'Achat ';
                if (isset(self::$periodicities_masc[(int) $periodicity])) {
                    $html .= lcfirst(self::$periodicities_masc[(int) $periodicity]);
                } else {
                    $html .= 'tous les ' . $periodicity . ' mois';
                }
                $html .= ' sur ' . $nb_months . ' mois (' . $nb_periods . ' période' . ($nb_periods > 1 ? 's' : '') . ')';
            } else {
                $html .= '<br/><strong>Achat ';
                if (isset(self::$periodicities_masc[(int) $periodicity])) {
                    $html .= lcfirst(self::$periodicities_masc[(int) $periodicity]);
                } else {
                    $html .= 'tous les ' . $periodicity . ' mois';
                }
                $html .= ' sur ' . $nb_months . ' mois<br/>(' . $nb_periods . ' période' . ($nb_periods > 1 ? 's' : '') . ')</strong>';
            }
        }

        if ($display_exp) {
            $id_contact = (int) BimpTools::getArrayValueFromPath($extra_params, 'exp_id_contact', 0);

            if ($id_contact) {
                $contact = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $id_contact);
                if (BimpObject::objectLoaded($contact)) {
                    $html .= '<br/><b>Contact livraison : </b>' . $contact->getName();
                }
            }
        }

        if ($display_achat) {
            $id_fourn = (int) BimpTools::getArrayValueFromPath($extra_params, 'achat_id_fourn', 0);
            if (!$id_fourn) {
                $id_pfp = (int) BimpTools::getArrayValueFromPath($extra_params, 'achat_id_fourn_price', 0);
                if ($id_pfp) {
                    $id_fourn = (int) $this->db->getValue('product_fournisseur_price', 'fk_soc', 'rowid = ' . $id_pfp);
                }
            }

            if ($id_fourn) {
                $fourn = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Fournisseur', $id_fourn);
                if (BimpObject::objectLoaded($fourn)) {
                    $html .= '<br/><b>Fournisseur : </b>' . ($no_html ? $fourn - getRef() . ' - ' . $fourn->getName() : $fourn->getLink());
                }
            }
        }

        return $html;
    }

    public function displayLineData($field, $edit = 0, $display_name = 'default', $no_html = false)
    {
        $html = parent::displayLineData($field, $edit, $display_name, $no_html);

        switch ($field) {
            case 'desc':
            case 'desc_light':
                $html .= '<br/>' . $this->displayPeriodicity($no_html);
                break;
        }

        return $html;
    }

    public function displayBilledPeriods($single_line = false, $data = null)
    {
        $html = '';

        if ($this->isLoaded() && (int) $this->getData('fac_periodicity')) {
            if (is_null($data)) {
                $data = $this->getNbPeriodsToBillData(null, true);
            }

            $total = (int) $this->getData('fac_nb_periods');
            $billed = (isset($data['nb_periods_billed']) ? (int) $data['nb_periods_billed'] : 0);
            $qty_for_1_period = BimpTools::getArrayValueFromPath($data, 'qty_for_1_period', 0);
            $s = ($billed > 1 ? 's' : '');

            if (!$single_line) {
                $html .= '<div style="text-align: center">';
            }

            $html .= '<span class="' . ($total == $billed ? 'success' : (!$billed ? 'danger' : 'warning')) . '">' . $billed . ' / ' . $total . '</span>&nbsp;période' . $s;
            $html .= ($single_line ? '&nbsp;' : '<br/>');
            $html .= '<span class="small">(' . BimpTools::displayFloatValue($billed * $qty_for_1_period, 4, ',', 0, 0, 0, 1, 1, 1) . '&nbsp;unité' . $s . ')</span>';

            if (!$single_line) {
                $html .= '</div>';
            }
        }

        return $html;
    }

    public function displayNbPeriodsToBill()
    {
        if ($this->isLoaded() && (int) $this->getData('fac_periodicity')) {
            if ((int) $this->getData('id_contrat_line_export')) {
                return '<span class="success">0 (déplacé vers contrat)</span>';
            }
            $data = $this->getNbPeriodsToBillData(null, true);

            if (isset($data['nb_periods_tobill_today'])) {
                return '<div style="text-align: center"><span class="badge badge-' . ($data['nb_periods_tobill_today'] > 0 ? ($data['nb_periods_tobill_today'] > 1 ? 'important' : 'warning') : 'success') . '">' . $data['nb_periods_tobill_today'] . '</span></div>';
            }
        }

        return '';
    }

    public function displayShippedPeriods($single_line = false, $data = null)
    {
        $html .= '';

        if ($this->isLoaded() && (int) $this->getData('exp_periodicity')) {
            if (is_null($data)) {
                $data = $this->getNbPeriodsToShipData(null, true);
            }

            $total = (int) $this->getData('exp_nb_periods');
            $shipped = (isset($data['nb_periods_shipped']) ? (int) $data['nb_periods_shipped'] : 0);
            $qty_for_1_period = BimpTools::getArrayValueFromPath($data, 'qty_for_1_period', 0);
            $s = ($shipped > 1 ? 's' : '');

            if (!$single_line) {
                $html .= '<div style="text-align: center">';
            }

            $html .= '<span class="' . ($total == $shipped ? 'success' : (!$shipped ? 'danger' : 'warning')) . '">' . $shipped . ' / ' . $total . '</span>&nbsp;période' . $s;
            $html .= ($single_line ? '&nbsp;' : '<br/>');
            $html .= '<span class="small">(' . BimpTools::displayFloatValue($shipped * $qty_for_1_period, 4, ',', 0, 0, 0, 1, 1, 1) . '&nbsp;unité' . $s . ')</span>';

            if (!$single_line) {
                $html .= '</div>';
            }
        }

        return $html;
    }

    public function displayNbPeriodsToShip()
    {
        if ($this->isLoaded() && (int) $this->getData('exp_periodicity')) {
            if ((int) $this->getData('id_contrat_line_export')) {
                return '<span class="success">0 (déplacé vers contrat)</span>';
            }

            $data = $this->getNbPeriodsToShipData(null, true);

            if (isset($data['nb_periods_toship_today'])) {
                return '<div style="text-align: center"><span class="badge badge-' . ($data['nb_periods_toship_today'] > 0 ? ($data['nb_periods_toship_today'] > 1 ? 'important' : 'warning') : 'success') . '">' . $data['nb_periods_toship_today'] . '</span></div>';
            }
        }

        return '';
    }

    public function displayBoughtPeriods($single_line = false, $data = null)
    {
        $html = '';

        if ($this->isLoaded() && (int) $this->getData('achat_periodicity')) {
            if (is_null($data)) {
                $data = $this->getNbPeriodesToBuyData(null);
            }

            $total = (int) $this->getData('achat_nb_periods');
            $bought = BimpTools::getArrayValueFromPath($data, 'nb_periods_bought', 0);
            $qty_for_1_period = BimpTools::getArrayValueFromPath($data, 'qty_for_1_period', 0);

            $s = ($bought > 1 ? 's' : '');

            $html .= '<span class="' . ($total == $bought ? 'success' : (!$bought ? 'danger' : 'warning')) . '">' . $bought . ' / ' . $total . '</span>&nbsp;période' . $s;
            $html .= ($single_line ? '&nbsp;' : '<br/>');
            $html .= '<span class="small">(' . ($bought * $qty_for_1_period) . '&nbsp;unité' . $s . ')</span>';
        }

        return $html;
    }

    public function displayNbPeriodsToBuy()
    {
        if ($this->isLoaded() && (int) $this->getData('achat_periodicity')) {
            if ((int) $this->getData('id_contrat_line_export')) {
                return '<span class="success">0 (déplacé vers contrat)</span>';
            }

            $data = $this->getNbPeriodesToBuyData(null);

            if (isset($data['nb_periods_tobuy_today'])) {
                return '<div style="text-align: center"><span class="badge badge-' . ($data['nb_periods_tobuy_today'] > 0 ? ($data['nb_periods_tobuy_today'] > 1 ? 'important' : 'warning') : 'success') . '">' . $data['nb_periods_tobuy_today'] . '</span></div>';
            }
        }

        return '';
    }

    public function displayNextPeriodDate($type, $with_color = false)
    {
        if (!in_array($type, array('exp', 'fac', 'achat'))) {
            return '';
        }

        if ((int) $this->getData('id_contrat_line_export')) {
            return '<span class="warning">Déplacé vers contrat</span>';
        }
        if ($this->getData('next_date_' . $type) == '9999-12-31') {
            return 'aucune';
        }

        $date = $this->displayData('next_date_' . $type);

        if ($with_color) {
            if ($this->getData('next_date_' . $type) <= date('Y-m-d')) {
                return '<span class="success">' . $date . '</span>';
            } else {
                return '<span class="danger">' . $date . '</span>';
            }
        }

        return $date;
    }

    public function displayAchatFourn()
    {
        $html = '';

        $fourn = $this->getChildObject('achat_fourn');
        if (BimpObject::objectLoaded($fourn)) {
            $html .= $fourn->getLink();
        } else {
            $html .= '<span class="danger">' . BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft') . 'Non défini</span>';
        }

        return $html;
    }

    public function displayClientNameInput()
    {
        $id_client = (int) BimpTools::getPostFieldValue('id_client', 0, 'int');
        if ($id_client) {
            $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $id_client);
            if (BimpObject::objectLoaded($client)) {
                return $client->getLink();
            }
        }

        return '';
    }

    public function displayFournNameInput()
    {
        $id_fourn = (int) BimpTools::getPostFieldValue('id_fourn', 0, 'int');
        if ($id_fourn) {
            $fourn = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Fournisseur', $id_fourn);
            if (BimpObject::objectLoaded($fourn)) {
                return $fourn->getLink();
            }
        }

        return '';
    }

    public function displayProductNameInput()
    {
        $id_product = (int) BimpTools::getPostFieldValue('id_product', 0, 'int');
        if ($id_product) {
            $prod = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $id_product);
            if (BimpObject::objectLoaded($prod)) {
                return $prod->getLink();
            }
        }

        return '';
    }

    // Rendus HTML:

    public function renderShipmentQtyInput($id_shipment, $with_total_max = false, $value = null, $qty_input_name = null, $include_input_name = null)
    {
        $html = '';

        $id_shipment = (int) $id_shipment;

        if (is_null($qty_input_name)) {
            $qty_input_name = 'line_' . $this->id . '_shipment_' . $id_shipment . '_qty';
        }

        if (is_null($include_input_name)) {
            $include_input_name = 'line_' . $this->id . '_shipment_' . $id_shipment . '_include';
        }

        if ((int) $this->getData('exp_periodicity') && (int) $this->getData('exp_nb_periods')) {
            return $this->renderShipmentNbPeriodsInput($id_shipment, $with_total_max, $qty_input_name);
        }

        $shipments = $this->getData('shipments');
        $is_return = ((float) $this->getFullQty() < 0);

        $shipment_qty = 0;
        if ($id_shipment && isset($shipments[(int) $id_shipment]['qty'])) {
            $shipment_qty = (float) $shipments[(int) $id_shipment]['qty'];
        }

        $decimals = 6;

        if ((int) $this->getData('type') === self::LINE_PRODUCT) {
            $product = $this->getProduct();
            if ($product && (int) $product->getData('fk_product_type') === 0) {
                $decimals = 0;
            }
        }

        $shipments_qty = (float) $this->getShipmentsQty();
        $shipped_qty = (float) $this->getShippedQty();

        $min = 0;
        $max = 0;
        $min_label = 0;
        $max_label = 0;

        if (!$is_return) {
            $max = $shipments_qty - $shipped_qty + $shipment_qty;
            $max_label = 1;
            if (is_null($value)) {
                $value = (!$with_total_max && !(float) $shipment_qty && !(int) $id_shipment ? $max : $shipment_qty);
            }
        } else {
            $min = $shipments_qty - $shipped_qty + $shipment_qty;
            $min_label = 1;
            if (is_null($value)) {
                $value = (!$with_total_max && !(float) $shipment_qty && !(int) $id_shipment ? $min : $shipment_qty);
            }
        }

        if (!$decimals) {
            $max = (int) floor($max);
            $min = (int) ceil($min);
        }

        $options = array(
            'data'        => array(
                'id_line'   => (int) $this->id,
                'data_type' => 'number',
                'decimals'  => $decimals,
                'unsigned'  => 0,
                'min'       => $min,
                'max'       => $max
            ),
            'extra_class' => 'line_shipment_qty',
            'min_label'   => $min_label,
            'max_label'   => $max_label
        );

        if ($with_total_max) {
            if (!$is_return) {
                $options['data']['total_max_value'] = (float) $this->getShipmentsQty();
                $options['data']['total_max_inputs_class'] = 'line_shipment_qty';
                $options['extra_class'] .= ' total_max';
            } else {
                $options['data']['total_min_value'] = (float) $this->getShipmentsQty();
                $options['data']['total_min_inputs_class'] = 'line_shipment_qty';
                $options['extra_class'] .= ' total_min';
            }
        }

        if ($this->field_exists('force_qty_1') && (int) $this->getData('force_qty_1')) {
            if ($is_return) {
                $max_value = $min;
            } else {
                $max_value = $max;
            }

            $html .= '<div class="line_qty_forced_to_1">';
            $html .= '<input type="hidden" name="' . $qty_input_name . '" value="' . $value . '"';
            $html .= BimpRender::displayTagData($options['data']);
            if (isset($options['extra_class'])) {
                $html .= ' class="' . $options['extra_class'] . '"';
            }
            $html .= '/>';

            $html .= '<div class="qty_label"' . ((float) $value == 0 && (float) $max_value ? ' style="display: none"' : '') . '>';
            $html .= $max_value . ' ';
            $msg = 'L\'option "Forcer les qtés à 1" est activée pour cette ligne de commande. Il n\'est donc pas possible de répartir les unités de cette ligne en plusieurs expéditions';
            $html .= '<span class="warning bs-popover"' . BimpRender::renderPopoverData($msg) . '>(Forcée à 1)</span>';
            $html .= '</div>';

            if ((float) $max_value) {
                $html .= '<div>' . BimpInput::renderInput('toggle', $include_input_name, ((float) $value == 0 ? 0 : 1)) . '</div>';
                $html .= '<script type="text/javascript">';
                $html .= '$(\'[name="' . $include_input_name . '"]\').change(function() {';
                $html .= 'var parent = $(this).findParentByClass(\'line_qty_forced_to_1\');';
                $html .= 'if (parseInt($(this).val())){';
                $html .= 'parent.find(\'.qty_label\').slideDown(250);';
                $html .= 'parent.find(\'[name="' . $qty_input_name . '"]\').val(' . $max_value . ');';
                $html .= '} else {';
                $html .= 'parent.find(\'.qty_label\').slideUp(250);';
                $html .= 'parent.find(\'[name="' . $qty_input_name . '"]\').val(0);';
                $html .= '}});';
                $html .= '</script>';
            }
            $html .= '</div>';
        } else {
            $html .= BimpInput::renderInput('qty', $qty_input_name, $value, $options);

            if ($id_shipment && abs($shipment_qty) > 0) {
                if ($shipment_qty === 1) {
                    $msg = $shipment_qty . ' unité a déjà été assignée à cette expédition.';
                } else {
                    $msg = $shipment_qty . ' unités ont déjà été assignées à cette expédition.';
                }

                $msg .= '<br/>Indiquez ici le nombre total d\'unités à assigner.';
                $html .= BimpRender::renderAlerts($msg, 'info');
            }
        }

        return $html;
    }

    public function renderShipmentNbPeriodsInput($id_shipment = 0, $with_total_max = false, $input_name = null, $value = null)
    {
        $html = '';

        if (!(int) $this->getData('exp_periodicity') && (int) $this->getData('exp_nb_periods')) {
            return BimpRender::renderAlerts('Périodicité absente');
        }

        if (!(int) $this->getData('exp_nb_periods')) {
            return BimpRender::renderAlerts('Nombre total de périodes à expédier absent');
        }

        $fullQty = (float) $this->getFullQty();

        if ($fullQty < 0) {
            return BimpRender::renderAlerts('L\'expédition par période n\'est pas possible pour les quantités négatives');
        }

        if (is_null($id_shipment)) {
            $id_shipment = 0;
        }

        $periods_data = $this->getNbPeriodsToShipData($id_shipment, true);
        $max = $periods_data['nb_periods_max'];

        if (is_null($value)) {
            $value = (int) $periods_data['shipment_nb_periods'];
        } else {
            if ($fullQty > 1) {
                $value /= $fullQty;
            }

            $value *= (float) $this->getData('exp_nb_periods');
        }

        $options = array(
            'data'        => array(
                'id_line'   => (int) $this->id,
                'data_type' => 'number',
                'decimals'  => 0,
                'unsigned'  => 0,
                'min'       => 0,
                'max'       => $max
            ),
            'extra_class' => 'line_shipment_qty',
            'max_label'   => 1
        );

        if ($with_total_max) {
            $options['data']['total_max_value'] = (int) $this->getData('exp_nb_periods');
            $options['data']['total_max_inputs_class'] = 'line_shipment_qty';
            $options['extra_class'] .= ' total_max';
        }

        if ($max > 0) {
            $html .= '<div class="bold">Nombre de livraisons périodiques:' . '</div>';
            $html .= BimpInput::renderInput('qty', $input_name, $value, $options);
        }
        $msg = 'Livraison tous les <b>' . $this->getData('exp_periodicity') . ' mois</b><br/>';

        if ($periods_data['start_date']) {
            $dt = new DateTime($periods_data['start_date']);
            $msg .= '<b>1ère livraison: </b>' . $dt->format('d / m / Y') . '<br/>';
        }
        $msg .= '<b>Livraisons déjà effectuées: </b>' . $periods_data['nb_periods_shipped'] . '<br/>';
        $msg .= '<b>Livraisons à effectuer à date: </b>' . $periods_data['nb_periods_toship_today'] . '<br/>';
        $html .= BimpRender::renderAlerts($msg, 'info');

        if (!is_null($periods_data['nb_units_last_period'])) {
            $msg = '<b>' . ((int) $this->getData('exp_nb_periods') - 1) . '</b> périodes de <b>' . $periods_data['nb_units_per_period'] . '</b> unité(s)<br/>';
            $msg .= 'puis <b>1</b> période de <b>' . $periods_data['nb_units_last_period'] . '</b> unité(s)';
            $html .= BimpRender::renderAlerts($msg, 'warning');
        }

        return $html;
    }

    public function renderFactureQtyInput($id_facture = 0, $with_total_max = false, $value = null, $max = null, $canEdit = true, $from_shipment = false)
    {
        $html = '';

        if (is_null($id_facture)) {
            $id_facture = 0;
        }

        $input_name = 'line_' . $this->id . '_facture_' . $id_facture . '_qty';

        $facture_data = $this->getFactureData($id_facture);

        if (isset($facture_data['qty'])) {
            $facture_qty = (float) $facture_data['qty'];
        } else {
            $facture_qty = 0;
        }

        if ((int) $id_facture === -1) {
            $html = '<input type="hidden" class="line_facture_qty" name="' . $input_name . '" value="' . $facture_qty . '"/>';
            $html .= $facture_qty;
            if ($id_facture === -1) {
                $html .= ' <span class="warning">(Non modifiable)</span>';
            }
            return $html;
        }

        if ((int) $this->getData('fac_periodicity') && (int) $this->getData('fac_nb_periods')) {
            return $this->renderFactureNbPeriodsInput($id_facture, $with_total_max, $value, $max, $canEdit);
        }

        $decimals = 6;

        if ((int) $this->getData('type') === self::LINE_PRODUCT) {
            $product = $this->getProduct();
            if (BimpObject::objectLoaded($product) && (int) $product->getData('fk_product_type') === 0) {
                $decimals = 0;
            }
        }

        $qty = (float) $this->getFullQty();

        if ($qty >= 0) {
            $max = $qty - (float) $this->getBilledQty() + $facture_qty;
            $min = 0;
            if (is_null($value)) {
                $value = (!$with_total_max && !(float) $facture_qty && !(int) $id_facture ? $max : $facture_qty);
            }
        } else {
            $min = $qty - (float) $this->getBilledQty() + $facture_qty;
            $max = 0;
            if (is_null($value)) {
                $value = (!$with_total_max && !(float) $facture_qty && !(int) $id_facture ? $min : $facture_qty);
            }
        }

        $force_qty_1 = ($this->field_exists('force_qty_1') && (int) $this->getData('force_qty_1'));

        if (!$canEdit) {
            $html = '<input type="hidden" class="line_facture_qty" name="line_' . $this->id . '_facture_' . $id_facture . '_qty" value="' . $value . '"/>' . $value;
            if ($force_qty_1) {
                $msg = 'L\'option "Forcer les qtés à 1" est activée pour cette ligne de commande. Il n\'est donc pas possible de répartir les unités de cette ligne en plusieurs factures';
                $html .= '<span class="warning bs-popover"' . BimpRender::renderPopoverData($msg) . '>(Forcée à 1)</span>';
            }
            return $html;
        }

        if (!$decimals) {
            $max = (int) floor($max);
            $min = (int) ceil($min);
        }

        $options = array(
            'data'        => array(
                'id_line'   => (int) $this->id,
                'data_type' => 'number',
                'decimals'  => $decimals,
                'unsigned'  => 0,
                'min'       => $min,
                'max'       => $max
            ),
            'extra_class' => 'line_facture_qty',
        );

        if ($qty >= 0) {
            $options['max_label'] = 1;
        } else {
            $options['min_label'] = 1;
        }

        if ($with_total_max) {
            if ($qty >= 0) {
                $options['data']['total_max_value'] = $qty;
                $options['data']['total_max_inputs_class'] = 'line_facture_qty';
                $options['extra_class'] .= ' total_max';
            } else {
                $options['data']['total_min_value'] = $qty;
                $options['data']['total_min_inputs_class'] = 'line_facture_qty';
                $options['extra_class'] .= ' total_min';
            }
        }

        if ($force_qty_1) {
            if ($qty >= 0) {
                $max_value = $max;
            } else {
                $max_value = $min;
            }
            $html .= '<div class="line_qty_forced_to_1">';
            $html .= '<input type="hidden" name="' . $input_name . '" value="' . $value . '"';
            $html .= BimpRender::displayTagData($options['data']);
            if (isset($options['extra_class'])) {
                $html .= ' class="' . $options['extra_class'] . '"';
            }
            $html .= '/>';

            $html .= '<div class="qty_label"' . ((float) $value == 0 && (float) $max_value ? ' style="display: none"' : '') . '>';
            $html .= $max_value . ' ';
            $msg = 'L\'option "Forcer les qtés à 1" est activée pour cette ligne de commande. Il n\'est donc pas possible de répartir les unités de cette ligne en plusieurs expéditions';
            $html .= '<span class="warning bs-popover"' . BimpRender::renderPopoverData($msg) . '>(Forcée à 1)</span>';
            $html .= '</div>';

            if ((float) $max_value) {
                $include_input_name = 'line_' . $this->id . '_facture_' . $id_facture . '_include';
                $html .= '<div>' . BimpInput::renderInput('toggle', $include_input_name, ((float) $value == 0 ? 0 : 1)) . '</div>';
                $html .= '<script type="text/javascript">';
                $html .= '$(\'[name="' . $include_input_name . '"]\').change(function() {';
                $html .= 'var parent = $(this).findParentByClass(\'line_qty_forced_to_1\');';
                $html .= 'if (parseInt($(this).val())){';
                $html .= 'parent.find(\'.qty_label\').slideDown(250);';
                $html .= 'parent.find(\'[name="' . $input_name . '"]\').val(' . $max_value . ');';
                $html .= '} else {';
                $html .= 'parent.find(\'.qty_label\').slideUp(250);';
                $html .= 'parent.find(\'[name="' . $input_name . '"]\').val(0);';
                $html .= '}});';
                $html .= '</script>';
            }

            $html .= '</div>';
        } else {
            $html .= BimpInput::renderInput('qty', $input_name, $value, $options);

            if ($facture_qty > 0) {
                if ($facture_qty == 1) {
                    $msg = $facture_qty . ' unité a déjà été assignée à cette facture.';
                } else {
                    $msg = $facture_qty . ' unités ont déjà été assignées à cette facture.';
                }

                $msg .= '<br/>Indiquez ici le nombre total d\'unités à assigner pour cette ligne de commande';
                if ($from_shipment) {
                    $msg .= ' <b>(toutes expéditions confondues)</b>';
                }
                $msg .= '.';
                $html .= BimpRender::renderAlerts($msg, ($from_shipment ? 'warning' : 'info'));
            }
        }

        return $html;
    }

    public function renderFactureNbPeriodsInput($id_facture = 0, $with_total_max = false, $value = null, $max = null, $canEdit = true)
    {
        $html = '';

        if (!(int) $this->getData('fac_periodicity') && (int) $this->getData('fac_nb_periods')) {
            return BimpRender::renderAlerts('Périodicité absente');
        }

        if (!(int) $this->getData('fac_nb_periods')) {
            return BimpRender::renderAlerts('Nombre total de périodes à facturer absent');
        }

        $fullQty = (float) $this->getFullQty();

        if ($fullQty < 0) {
            return BimpRender::renderAlerts('La facturation par période n\'est pas possible pour les quantités négatives');
        }

        if (is_null($id_facture)) {
            $id_facture = 0;
        }

        $input_name = 'line_' . $this->id . '_facture_' . $id_facture . '_periods';

        $decimals = 0;

        $periods_data = $this->getNbPeriodsToBillData($id_facture, true);

        $max = $periods_data['nb_periods_max'];
        $min = 0;

        if (is_null($value)) {
            $value = (int) $periods_data['fac_nb_periods'];
        } else {
            if ($fullQty > 1) {
                $value /= $fullQty;
            }

            $value *= (float) $this->getData('fac_nb_periods');
        }

        if (!$canEdit) {
            return '<input type="hidden" class="line_facture_periods" name="' . $input_name . '" value="' . $value . '"/>' . $value;
        }

        $options = array(
            'data'        => array(
                'id_line'   => (int) $this->id,
                'data_type' => 'number',
                'decimals'  => $decimals,
                'unsigned'  => 0,
                'min'       => $min,
                'max'       => $max
            ),
            'extra_class' => 'line_facture_periods',
            'max_label'   => 1
        );

        if ($with_total_max) {
            $options['data']['total_max_value'] = (int) $this->getData('fac_nb_periods');
            $options['data']['total_max_inputs_class'] = 'line_facture_periods';
            $options['extra_class'] .= ' total_max';
        }

        $html .= '<div class="bold">Nombre de périodes à facturer:' . '</div>';
        $html .= BimpInput::renderInput('qty', $input_name, $value, $options);
        $msg = '<b>1 période = ' . $this->getData('fac_periodicity') . ' mois</b><br/>';
        if ($periods_data['start_date']) {
            $dt = new DateTime($periods_data['start_date']);
            $msg .= '<b>1ère facturation: </b>' . $dt->format('d / m / Y') . '<br/>';
        }
        $msg .= '<b>Périodes déjà facturées: </b>' . $periods_data['nb_periods_billed'] . '<br/>';
        $msg .= '<b>Périodes à facturer à date: </b>' . $periods_data['nb_periods_tobill_today'] . '<br/>';
        $html .= BimpRender::renderAlerts($msg, 'info');
        return $html;
    }

    public function renderShipmentEquipmentsInput($id_shipment, $input_name = null, $qty_input_name = null)
    {
        $html = '';

        if (is_null($input_name)) {
            $input_name = 'line_' . $this->id . '_shipment_' . $id_shipment . '_equipments';
        }

        if (is_null($qty_input_name)) {
            $qty_input_name = 'line_' . $this->id . '_shipment_' . $id_shipment . '_qty';
        }

        $is_return = ((float) $this->getFullQty() < 0);
        $items = array();
        $values = array();

        $shipment_data = $this->getShipmentData($id_shipment);
        if (isset($shipment_data['equipments'])) {
            foreach ($shipment_data['equipments'] as $id_equipment) {
                $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $id_equipment);
                if (BimpObject::objectLoaded($equipment)) {
                    $items[$id_equipment] = $equipment->getData('serial');
                    $values[] = $id_equipment;
                }
            }
        }

        $equipments = $this->getEquipementsToAttributeToShipment();
        $equipments_returned = $this->getData('equipments_returned');

        if (count($equipments)) {
            foreach ($equipments as $id_equipment) {
                if (array_key_exists((int) $id_equipment, $items)) {
                    continue;
                }
                $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $id_equipment);
                if (BimpObject::objectLoaded($equipment)) {
                    $items[$id_equipment] = $equipment->displaySerialImei();
                    if ($is_return) {
                        if (array_key_exists((int) $id_equipment, $equipments_returned)) {
                            $entrepôt = BimpCache::getDolObjectInstance((int) $equipments_returned[(int) $id_equipment], 'product/stock', 'entrepot');
                            if (BimpObject::objectLoaded($entrepôt)) {
                                $items[$id_equipment] .= ' (Entrepôt: ' . $entrepôt->getNomUrl(1) . ')';
                            }
                        }
                    }
                }
            }
        }

        if (count($items)) {
            $html .= '<span style="font-weight: bold; font-size: 14px">Equipements: </span><br/>';

            $options = array(
                'items' => $items
            );

            if ($qty_input_name) {
                $options['max_input_name'] = $qty_input_name;
                $options['max_input_abs'] = 1;
            }

            $html .= BimpInput::renderInput('check_list', $input_name, $values, $options);
        }

        return $html;
    }

    public function renderFactureEquipmentsInput($id_fature, $input_name = null, $qty_input_name = null)
    {
        $html = '';

        if (is_null($input_name)) {
            $input_name = 'line_' . $this->id . '_facture_' . $id_fature . '_equipments';
        }

        $items = array();
        $values = array();

        $factureData = $this->getFactureData($id_fature);

        if (isset($factureData['equipments']) && is_array($factureData['equipments'])) {
            $nEquipments = count($factureData['equipments']);

            if ($nEquipments > 500) {
                foreach ($factureData['equipments'] as $id_equipment) {
                    $serial = $this->db->getValue('be_equipment', 'serial', 'id = ' . (int) $id_equipment);
                    if ($serial) {
                        $items[$id_equipment] = $serial;
                        $values[] = $id_equipment;
                    }
                }
            } else {
                foreach ($factureData['equipments'] as $id_equipment) {
                    $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $id_equipment);
                    if (BimpObject::objectLoaded($equipment)) {
                        $items[$id_equipment] = $equipment->getData('serial');
                        $values[] = $id_equipment;
                    }
                }
            }
        }

        $equipments = $this->getEquipementsToAttributeToFacture();
        $nEquipments = (is_array($equipments) ? count($equipments) : 0);
        if ($nEquipments) {
            foreach ($equipments as $id_equipment) {
                if (array_key_exists((int) $id_equipment, $items)) {
                    continue;
                }

                if ($nEquipments > 500) {
                    $serial = $this->db->getValue('be_equipment', 'serial', 'id = ' . (int) $id_equipment);
                    if ($serial) {
                        $items[$id_equipment] = $serial;
                    }
                } else {
                    $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $id_equipment);
                    if (BimpObject::objectLoaded($equipment)) {
                        $items[$id_equipment] = $equipment->getData('serial');
                    }
                }
            }
        }

        if (count($items)) {
            $html .= '<span style="font-weight: bold; font-size: 14px">Equipements: </span><br/>';

            $options = array(
                'items' => $items
            );

            if (!is_null($qty_input_name)) {
                $options['max_input_name'] = $qty_input_name;
                $options['max_input_abs'] = 1;
            }

            $html .= BimpInput::renderInput('check_list', $input_name, $values, $options);
        }

        return $html;
    }

    public function renderShipmentsView()
    {
        $html = '';

        if (!$this->isShippable()) {
            return BimpRender::renderAlerts('Cette ligne de commande n\'est pas expédiable');
        }

        $commande = $this->getParentInstance();
        $product = null;
        $option_type = '';
        $is_return = ((float) $this->getFullQty() < 0);

        if ((int) $this->getData('type') === ObjectLine::LINE_PRODUCT) {
            $product = $this->getProduct();
            if (BimpObject::objectLoaded($product) && (int) $product->getData('fk_product_type') === 0) {
                if (!$product->isSerialisable()) {
                    if (!$is_return) {
                        $option_type = 'group';
                    } else {
                        $option_type = 'entrepot';
                    }
                } else {
                    $option_type = 'equipments';
                }
            }
        }

        if (!BimpObject::objectLoaded($commande)) {
            $html .= BimpRender::renderAlerts('ID de la commande associée absent');
        } else {
            $shipments = $commande->getChildrenObjects('shipments');
            $line_shipments = $this->getData('shipments');

            if (count($shipments)) {
                $html .= '<div id="commande_line_' . $this->id . '_shipments_form' . '" class="object_form commande_shipments_form line_shipment_qty_container">';
                $html .= '<table class="bimp_list_table">';
                $html .= '<thead>';
                $html .= '<tr>';
                $html .= '<th style="width: 400px;">Expédition</th>';
                $html .= '<th>Qté</th>';

                if ($option_type) {
                    $html .= '<th>';
                    switch ($option_type) {
                        case 'group':
                            $html .= 'Grouper les articles';
                            break;

                        case 'entrepot':
                            $html .= 'Entrepôt de destination';
                            break;

                        case 'equipments':
                            $html .= 'Equipements';
                            break;
                    }
                    $html .= '</th>';
                }

                $html .= '</tr>';
                $html .= '</thead>';
                $html .= '<tbody>';

                foreach ($shipments as $shipment) {
                    $edit = ((int) $shipment->getData('status') === BL_CommandeShipment::BLCS_BROUILLON);
                    $html .= '<tr id="commande_line_shipment_' . $shipment->id . '_row" class="shipment_row" data-id_shipment="' . $shipment->id . '">';
                    $html .= '<td style="width: 400px;">';
                    $card = new BC_Card($shipment, null, 'default');
                    $html .= $card->renderHtml();
                    $html .= '</td>';

                    $qty = isset($line_shipments[(int) $shipment->id]['qty']) ? (float) $line_shipments[(int) $shipment->id]['qty'] : 0;
                    $group = isset($line_shipments[(int) $shipment->id]['group']) ? (float) $line_shipments[(int) $shipment->id]['group'] : 0;

                    $html .= '<td>';
                    if ($edit) {
                        $html .= $this->renderShipmentQtyInput((int) $shipment->id, true, null, 'shipment_' . $shipment->id . '_qty');
                    } else {
                        if ($this->hasExpPeriodicity()) {
                            $nb_periods = $this->getExpNbPeriodsFromQty($qty, $shipment->id);
                            $html .= '<input type="hidden" name="shipment_' . $shipment->id . '_qty" value="' . $nb_periods . '" class="line_shipment_qty total_max"/>';
                            $html .= '<b>' . round($nb_periods, 4) . ' livraison(s) périodique(s)</b><br/><span class="smallInfo">' . round($qty, 4) . ' unité(s)</span>';
                        } else {
                            $html .= '<input type="hidden" name="shipment_' . $shipment->id . '_qty" value="' . $qty . '" class="line_shipment_qty total_max"/>';
                            $html .= $qty;
                        }
                    }
                    $html .= '</td>';

                    if ($option_type) {
                        $html .= '<td>';
                        switch ($option_type) {
                            case 'group':
                                if ($edit) {
                                    $html .= BimpInput::renderInput('toggle', 'shipment_' . $shipment->id . '_group', $group, array(
                                                'extra_class' => 'line_shipment_group'
                                    ));
                                } else {
                                    if ($group) {
                                        $html .= '<span class="success">OUI</span>';
                                    } else {
                                        $html .= '<span class="danger">NON</span>';
                                    }
                                }
                                break;

                            case 'entrepot':
                                $id_entrepot = isset($line_shipments[(int) $shipment->id]['id_entrepot']) ? (float) $line_shipments[(int) $shipment->id]['id_entrepot'] : (int) $shipment->getData('id_entrepot');
                                if ($edit) {
                                    $html .= BimpInput::renderInput('search_entrepot', 'shipment_' . $shipment->id . '_id_entrepot', $id_entrepot, array(
                                                'extra_class' => 'line_shipment_entrepot'
                                    ));
                                } else {
                                    if ($id_entrepot) {
                                        BimpTools::loadDolClass('product/stock', 'entrepot');
                                        $entrepot = new Entrepot($this->db->db);
                                        $entrepot->fetch($id_entrepot);
                                        if (BimpObject::objectLoaded($entrepot)) {
                                            $html .= $entrepot->getNomUrl(1);
                                        } else {
                                            $html .= BimpRender::renderAlerts('L\'entrepôt d\'ID ' . $id_entrepot . ' n\'existe pas');
                                        }
                                    } else {
                                        $html .= '<span class="warning">Aucun entrepôt sélectionné</span>';
                                    }
                                }
                                break;

                            case 'equipments':
                                if ($edit) {
//                                    $html .= $this->renderShipmentEquipmentsInput($shipment->id, 'shipment_' . $shipment->id . '_equipments', 'shipment_' . $shipment->id . '_qty');
                                } else {
                                    $equipments = isset($line_shipments[(int) $shipment->id]['equipments']) ? $line_shipments[(int) $shipment->id]['equipments'] : array();
                                    $equipments_returned = $this->getData('equipments_returned');
                                    if (count($equipments)) {
                                        foreach ($equipments as $id_equipment) {
                                            $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                                            if (BimpObject::objectLoaded($equipment)) {
                                                $html .= $equipment->getLink();
                                                if ($is_return) {
                                                    if (array_key_exists((int) $id_equipment, $equipments_returned)) {
                                                        $entrepot = BimpCache::getDolObjectInstance((int) $equipments_returned[(int) $id_equipment], 'product/stock', 'entrepot');
                                                        if (BimpObject::objectLoaded($entrepot)) {
                                                            $html .= ' (Entrepôt: ' . $entrepot->getNomUrl(1) . ')';
                                                        }
                                                    }
                                                }
                                                $html .= '<br/>';
                                            } else {
                                                $html .= '<span class="danger">L\'équipement d\'ID ' . $id_equipment . ' n\'existe plus</span>';
                                            }
                                        }
                                    } else {
                                        $html .= BimpRender::renderAlerts('Aucun équipement attribué', 'warning');
                                    }
                                }
                                break;
                        }
                        $html .= '</td>';
                    }
                    $html .= '</tr>';
                }

                $html .= '</tbody>';
                $html .= '</table>';

                $html .= '<div class="ajaxResultContainer" style="display: none"></div>';

                $html .= '<div style="display: none" class="buttonsContainer align-right">';
                $html .= '<button class="btn btn-primary" onclick="saveCommandeLineShipments($(this), ' . $this->id . ')">';
                $html .= BimpRender::renderIcon('fas_save', 'iconLeft') . 'Enregistrer';
                $html .= '</button>';
                $html .= '</div>';

                $html .= '</div>';
            } else {
                $html .= BimpRender::renderAlerts('Aucune expédition créée');
                $html .= '<div class="buttonsContainer align-center">';

                $shipment = BimpObject::getInstance('bimplogistique', 'BL_CommandeShipment');
                $onclick = $shipment->getJsLoadModalForm('default', 'Nouvelle expédition', array(
                    'fields' => array(
                        'id_commande_client' => (int) $commande->id
                    )
                        ), addslashes('function() {' . $this->getJsLoadModalView('shipments', 'Gestion des expéditions') . '}'));
                $html .= '<button class="btn btn-default btn-large" onclick="' . $onclick . '">';
                $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Créer une expédition';
                $html .= '</button>';
                $html .= '</div>';
            }
        }

        return $html;
    }

    public function renderLogistiqueListFooterExtraContent()
    {
        $html = '';

        $items = array();

        $commande = $this->getParentInstance();

        if (BimpObject::objectLoaded($commande)) {
            $items[] = '<button class="btn btn-light-default" onclick="setSelectedCommandeLinesReservationsStatus($(this), ' . $commande->id . ', 2);">' . BimpRender::renderIcon('fas_exclamation-circle', 'iconLeft') . 'A réserver</button>';
            $items[] = '<button class="btn btn-light-default" onclick="setSelectedCommandeLinesReservationsStatus($(this), ' . $commande->id . ', 200);">' . BimpRender::renderIcon('fas_lock', 'iconLeft') . 'Réserver</button>';
            $items[] = '<button class="btn btn-light-default" onclick="setSelectedCommandeLinesReservationsStatus($(this), ' . $commande->id . ', 0);">' . BimpRender::renderIcon('fas_undo', 'iconLeft') . 'Réinitialiser</button>';
            $items[] = '<button class="btn btn-light-default" onclick="setSelectedCommandeLinesReservationsEquipmentsToShipment($(this), ' . $commande->id . ');">' . BimpRender::renderIcon('fas_shipping-fast', 'iconLeft') . 'Attribuer les équipements</button>';
            $items[] = '<button class="btn btn-light-default" onclick="setSelectedCommandeLinesReservationsAction($(this), ' . $commande->id . ', \'moveToEntrepotCommande\', \'Veuillez confirmer (action irréversible)\');">' . BimpRender::renderIcon('fas_arrow-right', 'iconLeft') . 'Déplacer vers entrepôt commande</button>';
        }
        $html .= BimpRender::renderDropDownButton('Status sélectionnés', $items, array(
                    'icon' => 'far_check-square'
        ));

        return $html;
    }

    public function renderCommandeFournQtyInput()
    {
        $html = '';

        $product = $this->getProduct();

        if (BimpObject::objectLoaded($product)) {
            $ordered_qty = $this->getBoughtQty();

            if ($product->isSerialisable() || (!(int) $this->getData('achat_periodicity') && $product->isTypeProduct())) {
                $max = $this->getReservationsQties(0);
                $min = 1;
                $decimals = 0;
            } else {
                $max = $this->getFullQty() - $ordered_qty;
                $min = 0.000001;
                $decimals = 6;
            }


            $periods_data = null;
            $qty_per_period = null;

            if ((int) $this->getData('achat_periodicity')) {
                $periods_data = $this->getNbPeriodesToBuyData();
                $qty_per_period = $this->getAchatQtyFor1Periode();
                $value = $qty_per_period * $periods_data['nb_periods_tobuy_today'];

                if ($min === 1) {
                    if ($value < 1 && $value > 0) {
                        $value = 1;
                    } else {
                        $value = floor($value);
                    }
                }

                if ($value > $max) {
                    $value = $max;
                }
            } else {
                $value = $max;
            }

            if ($max > 0) {
                $html .= BimpInput::renderInput('qty', 'qty', $value, array(
                            'data'      => array(
                                'data_type' => 'number',
                                'min'       => $min,
                                'max'       => $max,
                                'decimals'  => $decimals
                            ),
                            'max_label' => 1
                ));

                $msg = '';
                if ((int) $this->getData('achat_periodicity')) {
                    $msg = 'Achat périodique tous les <b>' . $this->getData('achat_periodicity') . ' mois</b><br/>';
                    $msg .= '<b>' . $qty_per_period . ' unité' . ($qty_per_period > 1 ? 's' : '') . '</b> par achat périodique<br/>';

                    if ($periods_data['start_date']) {
                        $dt = new DateTime($periods_data['start_date']);
                        $msg .= '<b>1er achat: </b>' . $dt->format('d / m / Y') . '<br/>';
                    }

                    $msg .= '<b>Nombre d\'achat  déjà effectués: </b>' . $periods_data['nb_periods_bought'] . '<br/>';
                    $msg .= '<b>Achats à effectuer à date: </b>' . $periods_data['nb_periods_tobuy_today'] . ' (<b>' . ($qty_per_period * $periods_data['nb_periods_tobuy_today']) . ' unité(s)</b>)<br/>';
                } elseif ($ordered_qty > 0) {
                    if (($ordered_qty > 1)) {
                        $msg = $ordered_qty . ' unités ont déjà été commandées';
                    } else {
                        $msg = $ordered_qty . ' unité a déjà été commandée';
                    }
                }

                if ($msg) {
                    $html .= BimpRender::renderAlerts($msg, 'info');
                }
            } else {
                $html .= '<input type="hidden" name="qty" value="0"/>';
                $html .= BimpRender::renderAlerts('Il ne reste aucune unité à commander');
            }
        } else {
            $html .= '<input type="hidden" name="qty" value="0"/>';
            $html .= BimpRender::renderAlerts('Aucun produit associé à cette ligne de commande');
        }

        return $html;
    }

    public function renderInvoicesView()
    {
        $html = '';

        $commande = $this->getParentInstance();
        $product = null;
        $isSerialisable = false;
        $dispatcher = null;

        if ((int) $this->getData('type') === ObjectLine::LINE_PRODUCT) {
            $product = $this->getProduct();
            if (BimpObject::objectLoaded($product) && $product->isSerialisable()) {
                $isSerialisable = true;
                $equipment_instance = BimpObject::getInstance('bimpequipment', 'Equipment');
                $dispatcher = new BC_Dispatcher($equipment_instance, '', '');
                $dispatcher->setItems($this->getEquipementsToAttributeToFacture());
            }
        }

        if (!BimpObject::objectLoaded($commande)) {
            $html .= BimpRender::renderAlerts('ID de la commande associée absent');
        } else {
            $line_factures = $this->getData('factures');
            $asso = new BimpAssociation($commande, 'factures');
            $factures_list = $asso->getAssociatesList();

            if (!empty($factures_list)) {
                $html .= '<div id="commande_line_' . $this->id . '_factures_form' . '" class="commande_factures_form line_facture_qty_container line_facture_periods_container">';

//                if ($isSerialisable) {
//                    $html .= '<div class="row">';
//                    $html .= '<div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">';
//
//                    $html .= $dispatcher->renderHtml();
//
//                    $html .= '</div>';
//                    $html .= '<div class="col-lg-8 col-md-8 col-sm-6 col-xs-12">';
//                }

                $html .= '<form>';
                $html .= '<table class="bimp_list_table">';
                $html .= '<thead>';
                $html .= '<tr>';
                $html .= '<th style="width: 250px;">Facture</th>';
                $html .= '<th>Qté</th>';
                $html .= '<th>Correction auto du PA</th>';
                if ($isSerialisable) {
                    $html .= '<th>Equipements</th>';
                }
                $html .= '</tr>';
                $html .= '</thead>';
                $html .= '<tbody>';

                $fullQty = (float) $this->getFullQty();

                foreach ($line_factures as $id_facture => $facture_data) {
                    if ((int) $id_facture !== -1) {
                        $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_facture);
                        if (!BimpObject::ObjectLoaded($facture)) {
                            $facture = 0;
                        }
                    } else {
                        $facture = 0;
                    }

                    $html .= '<tr id="commande_line_facture_' . $id_facture . '_row" class="facture_row" data-id_facture="' . $id_facture . '" data-facture_ref="' . ($facture ? $facture->getData('ref') : '') . '">';
                    $html .= '<td style="width: 250px;">';
                    if ($facture) {
                        $card = new BC_Card($facture, null, 'light');
                        $card->params['view_btn'] = 0;
                        $html .= $card->renderHtml();
                    } else {
                        if ((int) $id_facture === -1) {
                            $html .= '<span class="warning">Facturé hors BIMP-ERP</span>';
                        } else {
                            $html .= BimpRender::renderAlerts('La facture d\'ID ' . $id_facture . ' n\'existe plus');
                        }
                    }
                    $html .= '</td>';

                    $html .= '<td>';
                    if ($facture && (int) $facture->getData('fk_statut') === (int) Facture::STATUS_DRAFT) {
                        $html .= $this->renderFactureQtyInput($id_facture, true);
                    } else {
                        $html .= '<input type="hidden" name="line_' . $this->id . '_facture_' . $id_facture . '_qty" value="' . $facture_data['qty'] . '" class="line_facture_qty total_max"/>';
                        $html .= $facture_data['qty'];

                        if ($this->getData('fac_periodicity')) {
                            $nb_periods = $facture_data['qty'];
                            if ($fullQty) {
                                $nb_periods /= $fullQty;
                            }
                            $nb_periods *= (int) $this->getData('fac_nb_periods');

                            $html .= '<br/>(' . $nb_periods . ' période' . ($nb_periods > 1 ? 's' : '') . ')';
                        }
                    }
                    $html .= '</td>';

                    $html .= '<td>';
                    if ($facture) {
                        $pa_editable = 1;
                        $fac_line = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_FactureLine', array(
                                    'id_obj'             => $id_facture,
                                    'linked_object_name' => 'commande_line',
                                    'linked_id_object'   => $this->id
                        ));
                        if (BimpObject::objectLoaded($fac_line)) {
                            $pa_editable = (int) $fac_line->getData('pa_editable');
                        }
                        if ((int) $facture->getData('fk_statut') === (int) Facture::STATUS_DRAFT) {
                            $html .= BimpInput::renderInput('toggle', 'line_' . $this->id . '_facture_' . $id_facture . '_pa_editable', $pa_editable, array(
                                        'extra_class' => 'line_facture_pa_editable'
                            ));
                        } else {
                            $html .= '<span class="' . ($pa_editable ? 'success' : 'danger') . '">' . ($pa_editable ? 'OUI' : 'NON') . '</span>';
                        }
                    }
                    $html .= '</td>';

                    if ($isSerialisable) {
                        $html .= '<td>';
                        if (!isset($facture_data['equipments']) || empty($facture_data['equipments'])) {
                            $html .= '<span class="warning">Aucun équipement attribué à cette facture</span>';
                        } else {
                            if ($facture && (int) $facture->getData('fk_statut') === (int) Facture::STATUS_DRAFT) {
                                $items = array();
                                $values = array();
                                foreach ($facture_data['equipments'] as $id_equipment) {
                                    $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                                    if (BimpObject::objectLoaded($equipment)) {
                                        $items[(int) $id_equipment] = $equipment->getData('serial');
                                        $values[] = (int) $id_equipment;
                                    }
                                }

                                $html .= BimpInput::renderInput('check_list', 'line_' . $this->id . '_facture_' . $facture->id . '_equipments', $values, array(
                                            'items'          => $items,
                                            'max_input_name' => 'line_' . $this->id . '_facture_' . $facture->id . '_qty',
                                            'max_input_abs'  => ($this->getFullQty() < 0 ? 1 : 0)
                                ));
                            } else {
                                foreach ($facture_data['equipments'] as $id_equipment) {
                                    $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                                    if (BimpObject::objectLoaded($equipment)) {
                                        $html .= ' - ' . $equipment->getLink();
                                    } else {
                                        $html .= '<span class="danger">L\'équipement d\'ID ' . $id_equipment . ' n\'existe pas</span>';
                                    }
                                    $html .= '<br/>';
                                }
                            }
                        }
                        $html .= '</td>';
                    }

                    $html .= '</tr>';
                }

                $html .= '</tbody>';
                $html .= '</table>';
                $html .= '</form>';

                $html .= '<div class="ajaxResultContainer" style="display: none"></div>';

                $html .= '<div style="display: none" class="buttonsContainer align-right">';
                $html .= '<button class="btn btn-primary" onclick="saveCommandeLineFactures($(this), ' . $this->id . ')">';
                $html .= BimpRender::renderIcon('fas_save', 'iconLeft') . 'Enregistrer';
                $html .= '</button>';
                $html .= '</div>';

//                if ($isSerialisable) {
//                    $html .= '</div>';
//                    $html .= '</div>';
//                }

                $html .= '</div>';
            } else {
                $html .= BimpRender::renderAlerts('Aucune facture créée pour cette commande');
                if ($commande->isActionAllowed('linesFactureQties') && $commande->canSetAction('linesFactureQties')) {
                    $html .= '<div class="buttonsContainer align-center">';
                    $onclick = $commande->getJsActionOnclick('linesFactureQties', array(
                        'new_facture'       => 1,
                        'id_client_facture' => (int) $commande->getData('fk_soc'),
                        'note_public'       => htmlentities($commande->getData('note_public')),
                        'note_private'      => htmlentities($commande->getData('note_private')),
                        'id_contact'        => (int) $commande->dol_object->contact_id,
                        'id_cond_reglement' => (int) $commande->getData('fk_cond_reglement')
                            ), array(
                        'form_name'      => 'invoice',
                        'on_form_submit' => 'function ($form, extra_data) { return onFactureFormSubmit($form, extra_data); }'
                    ));

                    $html .= '<button class="btn btn-default btn-large" onclick="' . $onclick . '">';
                    $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Nouvelle facture';
                    $html .= '</button>';
                    $html .= '</div>';
                }
            }
        }

        return $html;
    }

    public function renderQtyModifiedInput()
    {
        if (!$this->isLoaded()) {
            return BimpRender::renderAlerts('ID de la ligne de commande absent', 'danger');
        }
        $decimals = $this->getQtyDecimals();

        if ((float) $this->getFullQty() >= 0) {
            $min = (float) $this->getMinQty();
            $max = 'none';
            $min_label = 1;
            $max_label = 0;
        } else {
            $max = (float) $this->getMinQty();
            $min = 'none';
            $min_label = 0;
            $max_label = 1;
        }

        return BimpInput::renderInput('qty', 'qty_modified', (float) $this->getFullQty(), array(
                    'data'      => array(
                        'data_type' => 'number',
                        'min'       => $min,
                        'max'       => $max,
                        'decimals'  => $decimals,
                        'unsigned'  => 0
                    ),
                    'min_label' => $min_label,
                    'max_label' => $max_label
        ));
    }

    public function renderEquipmentsReturnInput()
    {
        $html = '';

        if ((float) $this->getFullQty() >= 0) {
            $html .= BimpRender::renderAlerts('Aucun équipement en attente de retour', 'warning');
        } else {
            $input = BimpInput::renderInput('select', 'equipments_add_value', '', array(
                        'options' => $this->getClientEquipmentsArray()
            ));

            $equipments = $this->getData('equipments_returned');

            $max = abs((float) $this->getFullQty()) - count($equipments);

            $values = array();

            if (BimpTools::isSubmit('fields/equipments')) {
                foreach (BimpTools::getValue('fields/equipments', array(), 'array') as $id_equipment) {
                    if (count($values) >= $max) {
                        break;
                    }

                    $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);

                    if (BimpObject::objectLoaded($equipment)) {
                        $values[(int) $id_equipment] = $equipment->getData('serial');
                    }
                }
            }

            $content = BimpInput::renderMultipleValuesInput($this, 'equipments', $input, $values, '', 0, 1, 1, $max);

            $html = BimpInput::renderInputContainer('equipments', '', $content, '', 1, 1, '', array(
                        'values_field' => 'equipments'
            ));
        }

        return $html;
    }

    public function renderReturnsFromLinesInputs()
    {
        $html = '';

        $id_lines = BimpTools::getPostFieldValue('id_objects', array(), 'array');
        $warnings = array();

        if (empty($id_lines) && $this->isLoaded()) {
            $id_lines = array($this->id);
        }

        if (!is_array($id_lines) || empty($id_lines)) {
            $html .= BimpRender::renderAlerts('Aucune ligne de commande sélectionnée');
        } else {
            $lines_data = array();
            BimpObject::loadClass('bimplogistique', 'BL_CommandeShipment');
            foreach ($id_lines as $id_line) {
                $line = BimpCache::getBimpObjectInstance($this->module, $this->object_name, (int) $id_line);

                if (!BimpObject::ObjectLoaded($line)) {
                    $warnings[] = 'La ligne de commande d\'ID ' . $id_line . ' n\'existe pas';
                    continue;
                }

                if ((float) $line->getFullQty() < 0) {
                    $warnings[] = 'La ligne n°' . $line->getData('position') . ' est déjà un retour produits';
                    continue;
                }

                $line_warnings = array();
                $line_data = array();
                $isSerialisable = (int) $line->isProductSerialisable();

                if ((float) $line->getFullQty()) {
                    $commande = $line->getParentInstance();
                    if (!BimpObject::ObjectLoaded($commande)) {
                        $line_warnings[] = 'ID de la commande absent';
                        continue;
                    }

                    $shipments = $commande->getChildrenObjects('shipments', array(
                        'status' => BL_CommandeShipment::BLCS_EXPEDIEE
                    ));

                    if ($isSerialisable) {
                        $line_data['equipments'] = array();
                    } else {
                        $line_data['qty'] = 0;
                    }

                    foreach ($shipments as $shipment) {
                        $line_shipment_data = $line->getShipmentData((int) $shipment->id);
                        if ((float) $line_shipment_data['qty']) {
                            if ($isSerialisable && isset($line_shipment_data['equipments']) && !empty($line_shipment_data['equipments'])) {
                                foreach ($line_shipment_data['equipments'] as $id_equipment) {
                                    if (!array_key_exists((int) $id_equipment, $line_data['equipments'])) {
                                        $eq_errors = $line->checkReturnedEquipment($id_equipment);
                                        if (!count($eq_errors)) {
                                            $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                                            if (BimpObject::ObjectLoaded($equipment)) {
                                                $line_data['equipments'][$id_equipment] = $equipment->getData('serial');
                                            }
                                        } else {
                                            $line_warnings[] = BimpTools::getMsgFromArray($eq_errors);
                                        }
                                    }
                                }
                            } else {
                                $line_data['qty'] += (float) $line_shipment_data['qty'];
                            }
                        }
                    }
                }

                if (empty($line_data) ||
                        ($isSerialisable && empty($line_data['equipments'])) ||
                        (!$isSerialisable && !$line_data['qty'])) {
                    $line_warnings[] = 'Aucune unité elligible pour un retour';
                } else {
                    $lines_data[(int) $line->id] = $line_data;
                }

                if (count($line_warnings)) {
                    $warnings[] = BimpTools::getMsgFromArray($line_warnings, 'Ligne n°' . $line->getData('position'));
                }
            }

            if (count($warnings)) {
                $html .= BimpRender::renderAlerts($warnings, 'warning');
            }

            if (!empty($lines_data)) {
                $html .= '<table class="bimp_list_table">';
                $html .= '<thead>';
                $html .= '<tr>';
                $html .= '<th>N° ligne</th>';
                $html .= '<th>Description</th>';
                $html .= '<th>Qté / équipements</th>';
                $html .= '<th></th>';
                $html .= '</tr>';
                $html .= '</thead>';

                $html .= '<tbody>';
                foreach ($lines_data as $id_line => $line_data) {
                    $line = BimpCache::getBimpObjectInstance($this->module, $this->object_name, (int) $id_line);

                    $html .= '<tr class="line_row" data-id_line="' . $id_line . '">';
                    $html .= '<td>' . $line->getData('position') . '</td>';
                    $html .= '<td>' . $line->displayLineData('desc_light') . '</td>';
                    $html .= '<td>';
                    $return_line = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', array(
                                'linked_object_name' => 'return_of_commande_line',
                                'linked_id_object'   => (int) $line->id
                                    ), true);

                    if (BimpObject::ObjectLoaded($return_line)) {
                        $returned_qty = abs($return_line->getFullQty());
                        if ($returned_qty > 0) {
                            if ($returned_qty > 1) {
                                $msg = $returned_qty . ' unités ont déjà été retournées pour cette ligne de commande';
                            } else {
                                $msg = $returned_qty . ' unité a déjà été retournée pour cette ligne de commande';
                            }
                        }
                        $html .= BimpRender::renderAlerts($msg, 'warning');
                    }

                    if (isset($line_data['equipments'])) {
                        $html .= BimpInput::renderInput('check_list', 'line_' . $line->id . '_equipments', array(), array(
                                    'items' => $line_data['equipments']
                        ));
                    } elseif (isset($line_data['qty'])) {
                        $decimals = 6;
                        $product = $line->getProduct();
                        if (BimpObject::ObjectLoaded($product) && !(int) $product->getData('fk_product_type')) {
                            $decimals = 0;
                        }
                        $max = (float) $line_data['qty'];
                        if (BimpObject::ObjectLoaded($return_line)) {
                            $max -= abs($return_line->getFullQty());
                            $line_data['qty'] = $max;
                        }
                        $html .= BimpInput::renderInput('qty', 'line_' . $line->id . '_qty', $line_data['qty'], array(
                                    'data'      => array(
                                        'data_type' => 'number',
                                        'min'       => 0,
                                        'max'       => $max,
                                        'decimals'  => $decimals,
                                        'unsigned'  => 0
                                    ),
                                    'max_label' => 1,
                        ));
                    }
                    $html .= '</td>';
                    $html .= '<td>';
                    if (isset($line_data['equipments'])) {
                        $commande = $line->getParentInstance();
                        if (BimpObject::ObjectLoaded($commande)) {
                            $id_entrepot = (int) $commande->getData('entrepot');
                        } else {
                            $id_entrepot = 0;
                        }
                        $html .= '<p class="smallInfo">Entrepôt de destination: </p>';
                        $html .= BimpInput::renderInput('search_entrepot', 'line_' . $line->id . '_id_entrepot', $id_entrepot);
                    }
                    $html .= '</td>';
                    $html .= '</tr>';
                }
                $html .= '</tbody>';
                $html .= '</table>';
            }
        }

        return $html;
    }

    public function renderOriginLink()
    {
        $html = '';
        if ($this->isLoaded()) {
            $commande = $this->getParentInstance();

            if (BimpObject::objectLoaded($commande)) {
                $html .= '<span class="bold">Commande client d\'origine: </span>';
                $html .= $commande->getLink() . '<br/>Ligne n°' . $this->getData('position');
                $link = $commande->renderLogistiqueLink();

                if ($link) {
                    $html .= ' - ' . $link;
                }
            }
        }

        return $html;
    }

    public function renderFournPriceButtons($input_name = 'id_fourn_price')
    {
        $html = '';

        if ($this->canEditPrixAchat() && (int) $this->id_product) {
            $html .= '<div class="buttonsContainer" style="margin: 15px 15px 5px 15px; text-align: right">';
            $url = DOL_URL_ROOT . '/bimpcore/index.php?fc=product&id=' . $this->id_product . '&navtab=prix';
            $html .= '<span class="btn btn-default" onclick="window.open(\'' . $url . '\')">';
            $html .= BimpRender::renderIcon('fas_pencil-alt', 'iconLeft') . 'Editer les prix d\'achat';
            $html .= '</span>';
            $html .= '<span class="btn btn-default" onclick="reloadParentInput($(this), \'' . $input_name . '\');">';
            $html .= BimpRender::renderIcon('fas_redo', 'iconLeft') . 'Actualiser';
            $html .= '</span>';
            $html .= '</div>';
        }

        return $html;
    }

    public function renderHasExpPeriodicityInput()
    {
        $html = '';

        $value = $this->hasExpPeriodicity();

        if ($this->isFieldEditable('exp_periodicity')) {
            $html .= BimpInput::renderInput('toggle', 'has_exp_periodicity', $value);
        } else {
            $html .= '<input type="hidden" value="' . $value . '" name="has_exp_periodicity"/>';
            if ($value) {
                $html .= '<span class="success">OUI</span>';
            } else {
                $html .= '<span class="danger">NON</span>';
            }
        }

        return $html;
    }

    public function renderHasFacPeriodicityInput()
    {
        $html = '';

        $value = $this->hasFacPeriodicity();

        if ($this->isFieldEditable('fac_periodicity')) {
            $html .= BimpInput::renderInput('toggle', 'has_fac_periodicity', $value);
        } else {
            $html .= '<input type="hidden" value="' . $value . '" name="has_fac_periodicity"/>';
            if ($value) {
                $html .= '<span class="success">OUI</span>';
            } else {
                $html .= '<span class="danger">NON</span>';
            }
        }

        return $html;
    }

    public function renderHasAchatPeriodicityInput()
    {
        $html = '';

        $value = $this->hasAchatPeriodicity();

        if ($this->isFieldEditable('achat_periodicity')) {
            $html .= BimpInput::renderInput('toggle', 'has_achat_periodicity', $value);
        } else {
            $html .= '<input type="hidden" value="' . $value . '" name="has_achat_periodicity"/>';
            if ($value) {
                $html .= '<span class="success">OUI</span>';
            } else {
                $html .= '<span class="danger">NON</span>';
            }
        }

        return $html;
    }

    public function renderPeriodsToProcessOverview($params = array())
    {
        $params = BimpTools::overrideArray(array(
                    'id_client'  => 0,
                    'id_fourn'   => 0,
                    'id_product' => 0
                        ), $params);

        self::checkPeriodsDataAll('all', true, $params);

        $nb_exp = self::getNbPeriodsToProcess('exp', $params);
        $nb_facs = self::getNbPeriodsToProcess('fac', $params);
        $nb_achats = self::getNbPeriodsToProcess('achat', $params);

        $html .= '<table class="bimp_list_table">';
        $html .= '<tbody class="headers_col">';

        if (!(int) $params['id_fourn']) {
            // Livraisons : 
            $html .= '<tr>';
            $html .= '<th>' . BimpRender::renderIcon('fas_shipping-fast', 'iconLeft') . 'Livraisons</th>';
            $html .= '<td><span class="badge badge-' . ($nb_exp > 0 ? 'warning' : 'success') . '">' . $nb_exp . '</span></td>';
            $html .= '<td style="text-align: right">';
            if ($nb_exp > 0 && $this->canSetAction('periodicityMassprocess')) {
                $onclick = $this->getJsActionOnclick('periodicityMassProcess', array(
                    'operation_type' => 'exp',
                    'id_client'      => $params['id_client'],
                    'id_fourn'       => $params['id_fourn'],
                    'id_product'     => $params['id_product']
                        ), array(
                    'form_name'        => 'periodicity_mass_process',
                    'on_form_submit'   => 'function($form, extra_data) { return onPeriodicExpMassProcessFormSubmit($form, extra_data); }',
                    'use_bimpdatasync' => true,
                    'use_report'       => true
                ));
                $html .= '<span class="btn btn-default" onclick="' . $onclick . '">';
                $html .= 'Tout traiter' . BimpRender::renderIcon('fas_arrow-circle-right', 'iconRight');
                $html .= '</span>';
            }
            $html .= '</td>';
            $html .= '</tr>';

            // Facturation : 

            $html .= '<tr>';
            $html .= '<th>' . BimpRender::renderIcon('fas_file-invoice-dollar', 'iconLeft') . 'Facturations</th>';
            $html .= '<td><span class="badge badge-' . ($nb_facs > 0 ? 'warning' : 'success') . '">' . $nb_facs . '</span></td>';
            $html .= '<td style="text-align: right">';
            if ($nb_facs > 0 && $this->canSetAction('periodicityMassprocess')) {
                $onclick = $this->getJsActionOnclick('periodicityMassProcess', array(
                    'operation_type' => 'fac',
                    'id_client'      => $params['id_client'],
                    'id_fourn'       => $params['id_fourn'],
                    'id_product'     => $params['id_product']
                        ), array(
                    'form_name'        => 'periodicity_mass_process',
                    'on_form_submit'   => 'function($form, extra_data) { return onPeriodicFacMassProcessFormSubmit($form, extra_data); }',
                    'use_bimpdatasync' => true,
                    'use_report'       => true
                ));

                $html .= '<span class="btn btn-default" onclick="' . $onclick . '">';
                $html .= 'Tout traiter' . BimpRender::renderIcon('fas_arrow-circle-right', 'iconRight');
                $html .= '</span>';
            }
            $html .= '</tr>';
        }

        // Achats : 
        $html .= '<tr>';
        $html .= '<th>' . BimpRender::renderIcon('fas_cart-arrow-down', 'iconLeft') . 'Achats</th>';
        $html .= '<td><span class="badge badge-' . ($nb_achats > 0 ? 'warning' : 'success') . '">' . $nb_achats . '</span></td>';
        $html .= '<td style="text-align: right">';
        if ($nb_achats > 0 && $this->canSetAction('periodicityMassprocess')) {
            $onclick = $this->getJsActionOnclick('periodicityMassProcess', array(
                'operation_type' => 'achat',
                'id_client'      => $params['id_client'],
                'id_fourn'       => $params['id_fourn'],
                'id_product'     => $params['id_product']
                    ), array(
                'form_name'        => 'periodicity_mass_process',
                'on_form_submit'   => 'function($form, extra_data) { return onPeriodicAchatMassProcessFormSubmit($form, extra_data); }',
                'use_bimpdatasync' => true,
                'use_report'       => true
            ));

            $html .= '<span class="btn btn-default" onclick="' . $onclick . '">';
            $html .= 'Tout traiter' . BimpRender::renderIcon('fas_arrow-circle-right', 'iconRight');
            $html .= '</span>';
        }
        $html .= '</tr>';

        $html .= '</tag>';
        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }

    public function renderPeriodsList($type, $id_client = 0, $id_product = 0, $id_fourn = 0)
    {
        $title = '';
        $list_name = '';

        switch ($type) {
            case 'exp':
                $title = 'Produits en commande à livraison périodique';
                $list_name = 'general_periodes_exp';
                break;

            case 'fac':
                $title = 'Produits en commande à facturation périodique';
                $list_name = 'general_periodes_fac';
                break;

            case 'achat':
                $title = 'Produits en commande à achat périodique';
                $list_name = 'general_periodes_achat';
                break;

            default:
                return BimpRender::renderAlerts('Type de liste invalide');
        }

        if ($id_client) {
            $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $id_client);
            if (!BimpObject::objectLoaded($client)) {
                return BimpRender::renderAlerts('Le client #' . $id_client . ' n\'existe pas');
            }
            $title .= ' du client ' . $client->getRef() . ' - ' . $client->getName();
        } elseif ($id_product) {
            $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $id_product);
            if (!BimpObject::objectLoaded($product)) {
                return BimpRender::renderAlerts('Le produit #' . $id_product . ' n\'existe pas');
            }
            $title .= ' pour le produit ' . $product->getRef() . ' - ' . $product->getName();
        } elseif ($type == 'achat' && $id_fourn) {
            $fourn = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Fournisseur', $id_fourn);
            if (!BimpObject::objectLoaded($fourn)) {
                return BimpRender::renderAlerts('Le fournisseur #' . $id_fourn . ' n\'existe pas');
            }
            $title .= ' auprès du fournisseur ' . $fourn->getRef() . ' - ' . $fourn->getName();
        }

        $this->periods_list_id_client = $id_client;
        $this->periods_list_id_fourn = $id_fourn;
        $this->periods_list_id_product = $id_product;

        $bc_list = new BC_ListTable($this, $list_name, 1, null, $title, 'fas_calendar-alt');
        $bc_list->addJoin('commande', 'a.id_obj = parent.rowid', 'parent');
        $bc_list->addFieldFilterValue('parent.fk_statut', array(
            'operator' => '>',
            'value'    => 0
        ));
        $bc_list->addFieldFilterValue('a.' . $type . '_periodicity', array(
            'operator' => '>',
            'value'    => 0
        ));

        if ($id_client) {
            $bc_list->addFieldFilterValue('parent.fk_soc', (int) $id_client);
        } elseif ($id_product) {
            $bc_list->addJoin('commandedet', 'a.id_line = cdet.rowid', 'cdet');
            $bc_list->addFieldFilterValue('cdet.fk_product', (int) $id_product);
        } elseif ($type == 'achat' && $id_fourn) {
            $bc_list->addFieldFilterValue('a.achat_id_fourn', (int) $id_fourn);
        }

        $list_html = $bc_list->renderHtml();

        if ($id_client || $id_fourn || $id_product) {
            return $list_html;
        }

        $tabs = array();

        $tabs[] = array(
            'id'      => $type . '_periods_list_tab',
            'title'   => BimpRender::renderIcon('fas_bars', 'iconLeft') . 'Liste',
            'content' => $list_html
        );

        $tabs[] = array(
            'id'            => $type . '_periods_report_tab',
            'title'         => BimpRender::renderIcon('fas_file-alt', 'iconLeft') . 'Rapports',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderPeriodsReportsList', '$(\'#' . $type . '_periods_report_tab .nav_tab_ajax_result\')', array($type), array('button' => ''))
        );

        return BimpRender::renderNavTabs($tabs);
    }

    public function renderPeriodsReportsList($type)
    {
        if (!in_array($type, array('exp', 'fac', 'achat'))) {
            return BimpRender::renderAlerts('Type d\'opération invalide');
        }

        $report = BimpObject::getInstance('bimpdatasync', 'BDS_Report');

        $title = 'Rapports des ';

        switch ($type) {
            case 'exp':
                $title .= 'livraisons';
                break;

            case 'fac':
                $title .= 'facturations';
                break;

            case 'achat':
                $title .= 'achats';
                break;
        }

        $title .= ' périodiques en masse';

        $list = new BC_ListTable($report, 'operation', 1, null, $title);
        $list->addIdentifierSuffix($type);
        $list->addFieldFilterValue('code', 'PERIODIC_MASS_PROCESS_' . strtoupper($type));

        return $list->renderHtml();
    }

    public function renderAchatPeriodicitySameValuesInput()
    {
        if ($this->isFieldEditable('achat_periodicity')) {
            return BimpInput::renderInput('select', 'achat_periodicity_same_values', 'custom', array(
                        'options' => array(
                            'custom' => 'Personnalisés',
                            'as_fac' => 'Identiques à la facturation périodique',
                            'as_exp' => 'Identiques à la livraison périodique'
                        )
            ));
        }
        return '<input type="hidden" value="custom" name="achat_periodicity_same_values"/>Personnalisés';
    }

    public function renderPeriodicityMassProcessInputs()
    {
        $html = '';
        $errors = array();

        $operation_type = BimpTools::getPostFieldValue('operation_type', '', 'aZ09');

        if (!$operation_type) {
            $errors[] = 'Type d\'opération périodique non spécifiée';
        } else {
            $method = 'renderPeriodic' . ucfirst($operation_type) . 'MassProcessInputs';
            if (!method_exists($this, $method)) {
                $errors[] = 'Type d\'opération invalide - ' . $operation_type;
            } else {
                $html = $this->{$method}($errors);
            }
        }

        if (count($errors)) {
            $html .= BimpRender::renderAlerts($errors);
        }


        return $html;
    }

    public function renderPeriodicExpMassProcessInputs(&$errors = array())
    {
        $html = '';

        $id_client = (int) BimpTools::getPostFieldValue('id_client', 0, 'int');
        $id_product = (int) BimpTools::getPostFieldValue('id_product', 0, 'int');

        $lines = self::getPeriodicExpLinesToProcess($errors, $id_client, $id_product);

        // Trie par commande client / contact: 
        $lines_by_commandes = array();

        foreach ($lines as $id_line) {
            $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', $id_line);
            if (!BimpObject::objectLoaded($line)) {
                continue;
            }

            $commande = $line->getParentInstance();
            if (!BimpObject::objectLoaded($commande)) {
                $errors[] = 'Ligne #' . $id_line . ' : commande client absente';
                continue;
            }

            $params = $line->getData('periodicity_extra_params');

            $id_contact = (int) BimpTools::getArrayValueFromPath($params, 'exp_id_contact', 0);

            if (!isset($lines_by_commandes[$commande->id])) {
                $lines_by_commandes[$commande->id] = array();
            }

            if (!isset($lines_by_commandes[$commande->id][$id_contact])) {
                $lines_by_commandes[$commande->id][$id_contact] = array();
            }

            $lines_by_commandes[$commande->id][$id_contact][] = $id_line;
        }

        if (empty($lines_by_commandes)) {
            $html .= BimpRender::renderIcon('fas_check', 'iconLeft') . '<span class = "success">Aucune livraison périodique à effectuer à date</span>';
        } else {
            $html .= '<div class="buttonsContainer align-right" style="margin:  0 0 5px 0; padding: 0">';
            $html .= '<span class="btn btn-default check_all_lines">';
            $html .= BimpRender::renderIcon('far_check-square', 'iconLeft') . 'Tout cocher';
            $html .= '</span>';
            $html .= '<span class="btn btn-default uncheck_all_lines">';
            $html .= BimpRender::renderIcon('far_square', 'iconLeft') . 'Tout décocher';
            $html .= '</span>';
            $html .= '</div>';

            $html .= '<table class="bimp_list_table">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th style="min-width: 30px; max-width: 30px; text-align: center"></th>';
            $html .= '<th>Produit / service</th>';
            $html .= '<th>Quantités</th>';
            $html .= '<th>Date prochaine livraison</th>';
            $html .= '</tr>';
            $html .= '</thead>';

            $html .= '<tbody>';

            foreach ($lines_by_commandes as $id_commande => $contacts) {
                $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $id_commande);
                if (!BimpObject::objectLoaded($commande)) {
                    $html .= '<tr>';
                    $html .= '<td colspan="99">' . BimpRender::renderAlerts('La commande client #' . $id_commande . ' n\'existe plus') . '</td>';
                    $html .= '</tr>';
                    continue;
                }
                $html .= '<tr class="commande_row" data-id_commande="' . $id_commande . '">';
                $html .= '<td colspan="99" style="font-weight: bold; font-size: 14px; padding: 30px 10px 10px 10px; background-color: #FAFAFA!important; border-left: 3px solid #' . BimpCore::getParam('colors/primary', '000000') . '">';
                $html .= 'Commande client : ' . $commande->getLink();

                $client = $commande->getChildObject('client');
                if (BimpObject::objectLoaded($client)) {
                    $html .= '<br/>Client : ' . $client->getLink();
                }
                $html .= '</td>';
                $html .= '</tr>';

                foreach ($contacts as $id_contact => $contact_lines) {
                    $html .= '<tr class="commande_contact_row" data-id_commande="' . $id_commande . '" data-id_contact="' . $id_contact . '">';
                    $html .= '<td colspan="99" style="font-size: 12px; padding: 10px; background-color: #DCDCDC">';

                    $html .= '<div style="display: inline-block">';
                    $html .= '<b>Livraison à : </b><br/>';

                    if ($id_contact) {
                        $contact = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $id_contact);
                        if (BimpObject::objectLoaded($contact)) {
                            $html .= $contact->getLink() . ' - <b>' . $contact->displayFullAddress(true) . '</b>';
                        } else {
                            $html .= '<span class="danger">Le contact #' . $id_contact . ' n\'existe plus</span>';
                        }
                    } else {
                        $html .= '<b>Adresse de livraison de la commande</b>';
                    }
                    $html .= '</div>';

                    $html .= '<div style="display: inline-block; max-width: 400px; margin-left: 30px; font-size: 12px; font-weight: normal">';
                    $html .= '<span class="small bold">Expédition : </span>';
                    $shipments = $line->getSelectShipmentsArray(true, 'Nouvelle expédition', $id_contact);

                    if (count($shipments) > 1) {
                        $html .= BimpInput::renderInput('select', 'commande_' . $id_commande . '_contact_' . $id_contact . '_shipment', 0, array(
                                    'options'     => $shipments,
                                    'extra_class' => 'commande_shipment_select'
                        ));
                    } else {
                        $html .= 'Nouvelle expédition';
                        $html .= '<input type="hidden" name="commande_' . $id_commande . '_contact_' . $id_contact . '_shipment" value="0"/>';
                    }


                    $html .= '<div class="exp_ref_container" style="margin-top: 10px">';
                    $html .= '<span class="small bold">Réf. expédition : </span>';
                    $html .= BimpInput::renderInput('text', 'commande_' . $id_commande . '_contact_' . $id_contact . '_shipment_ref', '');
                    $html .= '</div>';

                    $html .= '</div>';

                    $html .= '</td>';
                    $html .= '</tr>';

                    foreach ($contact_lines as $id_line) {
                        $tr_class = '';
                        $row_html = '';

                        $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', $id_line);
                        if (BimpObject::objectLoaded($line)) {
                            $periods_data = $line->getNbPeriodsToShipData(null);

                            $row_html .= '<td style="min-width: 30px; max-width: 30px; text-align: center">';
                            if ($periods_data['nb_periods_toship_today'] > 0) {
                                $tr_class = 'selected';
                                $row_html .= '<input type="checkbox" name="line_' . $line->id . '_check" class="line_check" checked="1"/>';
                            } else {
                                $tr_class = 'deactivated';
                            }
                            $row_html .= '</td>';

                            $row_html .= '<td>';
                            $product = $line->getProduct();
                            if (BimpObject::objectLoaded($product)) {
                                $row_html .= $product->getLink() . '<br/>';
                                $row_html .= $product->getName() . '<br/>';
                                $row_html .= $line->displayPeriodicity(false, array('exp'));
                            }
                            $row_html .= '</td>';

                            $row_html .= '<td style="min-width: 250px">';
//                            $row_html .= '<pre>';
//                            $row_html .= print_r($periods_data, 1);
//                            $row_html .= '</pre>';

                            $row_html .= 'Déjà traité : ' . $line->displayShippedPeriods(true, $periods_data) . '<br/>';

                            $class = ($periods_data['nb_periods_toship_today'] > 0 ? ($periods_data['nb_periods_toship_today'] > 1 ? 'important' : 'warning') : 'danger');
                            $s = ($periods_data['nb_periods_toship_today'] > 1 ? 's' : '');
                            $row_html .= 'A traiter aujoud\'hui : <span class="' . $class . '">' . $periods_data['nb_periods_toship_today'] . ' période' . $s . '</span>';
                            $row_html .= '&nbsp;(';
                            $row_html .= BimpTools::displayFloatValue($periods_data['nb_periods_toship_today'] * $periods_data['qty_for_1_period'], 4, ',', 0, 1, 0, 1, 1, 1);
                            $row_html .= ' unité' . $s . ')<br/>';

                            if ($periods_data['nb_periods_toship_today'] > 0) {
                                $row_html .= '<br/>';
                                $row_html .= '<b>Nb périodes à livrer: </b><br/>';
                                $row_html .= BimpInput::renderInput('qty', 'line_' . $line->id . '_qty', $periods_data['nb_periods_toship_today'], array(
                                            'extra_class' => 'line_qty',
                                            'max_label'   => true,
                                            'data'        => array(
                                                'max'      => $periods_data['nb_periods_toship_today'],
                                                'min'      => 0,
                                                'decimals' => 0
                                            )
                                ));
                            }

                            $row_html .= '</td>';
                            $row_html .= '<td>';
                            $row_html .= $line->displayNextPeriodDate('exp', true);
                            $row_html .= '</td>';
                        } else {
                            $row_html .= '<td colspan="99">';
                            $row_html .= BimpRender::renderAlerts('La ligne de commande #' . $id_line . ' n\'existe plus');
                            $row_html .= '</td>';
                        }

                        $html .= '<tr class="commande_line_row' . ($tr_class ? ' ' . $tr_class : '') . '" data-id_commande="' . $id_commande . '" data-id_contact="' . $id_contact . '" data-id_line="' . $id_line . '">';
                        $html .= $row_html;
                        $html .= '</tr>';
                    }
                }
            }

            $html .= '</tbody>';
            $html .= '</table>';
        }

        return $html;
    }

    public function renderPeriodicFacMassProcessInputs(&$errors = array())
    {
        $html = '';

        $id_client = (int) BimpTools::getPostFieldValue('id_client', 0, 'int');
        $id_product = (int) BimpTools::getPostFieldValue('id_product', 0, 'int');

        $lines_by_clients = self::getPeriodicFacLinesToProcess($errors, $id_client, $id_product);

        if (empty($lines_by_clients)) {
            $msg = 'Aucune facturation périodique à effectuer à date';

            if ($id_client) {
                $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $id_client);
                if (BimpObject::objectLoaded($client)) {
                    $msg .= ' pour le client ' . $client->getRef() . ' - ' . $client->getName();
                }
            } elseif ($id_product) {
                $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $id_product);
                if (BimpObject::objectLoaded($product)) {
                    $msg .= ' pour le produit ' . $product->getRef() . ' - ' . $product->getName();
                }
            }

            $html .= BimpRender::renderIcon('fas_check', 'iconLeft') . '<span class = "success">' . $msg . '</span>';
        } else {
            // Répartition par entrepôt / secteur / mode et conditions de réglement : 

            $clients_factures = array();

            foreach ($lines_by_clients as $id_client => $lines) {
                $clients_factures[$id_client] = array();

                foreach ($lines as $id_line => $line_data) {
                    $check = false;

                    foreach ($clients_factures[$id_client] as $idx => $cf_data) {
                        if ($cf_data['id_entrepot'] == $line_data['id_entrepot'] && $cf_data['secteur'] == $line_data['secteur'] &&
                                $cf_data['id_mode_reglement'] == $line_data['id_mode_reglement'] && $cf_data['id_cond_reglement'] == $line_data['id_cond_reglement']) {
                            $clients_factures[$id_client][$idx]['lines'][] = $id_line;
                            $check = true;
                            break;
                        }
                    }

                    if (!$check) {
                        $clients_factures[$id_client][] = array(
                            'id_entrepot'       => $line_data['id_entrepot'],
                            'secteur'           => $line_data['secteur'],
                            'id_mode_reglement' => $line_data['id_mode_reglement'],
                            'id_cond_reglement' => $line_data['id_cond_reglement'],
                            'lines'             => array($id_line)
                        );
                    }
                }
            }

            $html .= '<div class="buttonsContainer align-right" style="margin:  0 0 5px 0; padding: 0">';
            $html .= '<span class="btn btn-default check_all_lines">';
            $html .= BimpRender::renderIcon('far_check-square', 'iconLeft') . 'Tout cocher';
            $html .= '</span>';
            $html .= '<span class="btn btn-default uncheck_all_lines">';
            $html .= BimpRender::renderIcon('far_square', 'iconLeft') . 'Tout décocher';
            $html .= '</span>';
            $html .= '</div>';

            $html .= '<table class = "bimp_list_table">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th style="min-width: 30px; max-width: 30px; text-align: center"></th>';
            $html .= '<th>Commande client</th>';
            $html .= '<th>Produit / service</th>';
            $html .= '<th>Quantités</th>';
            $html .= '<th>Date prochaine facturation</th>';
            $html .= '</tr>';
            $html .= '</thead>';

            $html .= '<tbody>';

            $secteurs = BimpCache::getSecteursArray(false);
            $modes_reglement = BimpCache::getModeReglements();
            $conds_reglement = BimpCache::getCondReglementsArray(false);

            foreach ($clients_factures as $id_client => $client_factures) {
                $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $id_client);
                if (!BimpObject::objectLoaded($client)) {
                    $html .= '<tr>';
                    $html .= '<td colspan="99">' . BimpRender::renderAlerts('Le client #' . $id_client . ' n\'existe plus') . '</td>';
                    $html .= '</tr>';
                    continue;
                }

                $html .= '<tr class="client_row" data-id_client="' . $id_client . '">';
                $html .= '<td colspan="99" style="font-weight: bold; font-size: 14px; padding: 10px; background-color: #DCDCDC">';
                $html .= 'Client : ' . $client->getLink();
                $html .= '</td>';
                $html .= '</tr>';

                foreach ($client_factures as $fac_idx => $facture_data) {
                    $entrepot = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Entrepot', $facture_data['id_entrepot']);
                    $html .= '<tr class="client_fac_row" data-id_client="' . $id_client . '" data-fac_idx="' . $fac_idx . '"';
                    $html .= ' data-id_entrepot="' . $facture_data['id_entrepot'] . '"';
                    $html .= ' data-secteur="' . $facture_data['secteur'] . '"';
                    $html .= ' data-id_mode_reglement="' . $facture_data['id_mode_reglement'] . '"';
                    $html .= ' data-id_cond_reglement="' . $facture_data['id_cond_reglement'] . '"';
                    $html .= '>';
                    $html .= '<td colspan="99" style="font-size: 12px; padding: 10px; background-color: #DCDCDC">';
                    $html .= '<div style="display: inline-block">';
                    $html .= 'Entrepôt : ' . $entrepot->getLink() . '<br/>';
                    $html .= 'Secteur : <b>' . (isset($secteurs[$facture_data['secteur']]) ? $secteurs[$facture_data['secteur']] : '<span class="danger">' . ($facture_data['secteur'] ? 'inconnu (' . $facture_data['secteur'] . ')' : 'non spécifié') . '</span>') . '</b><br/>';
                    $html .= 'Mode de réglement : <b>' . (isset($modes_reglement[$facture_data['id_mode_reglement']]) ? $modes_reglement[$facture_data['id_mode_reglement']] : '<span class="danger">' . ($facture_data['id_mode_reglement'] ? 'inconnu (' . $facture_data['id_mode_reglement'] . ')' : 'non spécifié') . '</span>') . '</b><br/>';
                    $html .= 'Conditions de réglement : <b>' . (isset($conds_reglement[$facture_data['id_cond_reglement']]) ? $conds_reglement[$facture_data['id_cond_reglement']] : '<span class="danger">' . ($facture_data['id_cond_reglement'] ? 'inconnu (' . $facture_data['id_cond_reglement'] . ')' : 'non spécifié') . '</span>') . '</b><br/>';
                    $html .= '</div>';

                    $factures = array('0' => 'Nouvelle facture');

                    foreach (BimpCache::getBimpObjectObjects('bimpcommercial', 'Bimp_Facture', array(
                        'a.fk_soc'            => $id_client,
                        'a.fk_statut'         => 0,
                        'a.type'              => 0,
                        'a.fk_mode_reglement' => (int) $facture_data['id_mode_reglement'],
                        'a.fk_cond_reglement' => (int) $facture_data['id_cond_reglement'],
                        'fef.entrepot'        => $facture_data['id_entrepot'],
                        'fef.type'            => $facture_data['secteur']
                            ), 'rowid', 'asc', array(
                        'fef' => array(
                            'table' => 'facture_extrafields',
                            'on'    => 'fef.fk_object = a.rowid'
                        )
                    )) as $fac) {
                        $factures[$fac->id] = $fac->getRef() . ' (créée le ' . date('d / m / Y', strtotime($fac->getData('datec'))) . ')';
                    }

                    $html .= '<div style="display: inline-block; max-width: 400px; margin-left: 30px; font-size: 12px; font-weight: normal">';
                    $html .= '<span class="small bold">Facture : </span>';

                    if (count($factures) > 1) {
                        $html .= BimpInput::renderInput('select', 'client_' . $id_client . '_fac_' . $fac_idx, 0, array(
                                    'options'     => $factures,
                                    'extra_class' => 'client_facture_select'
                        ));
                    } else {
                        $html .= 'Nouvelle facture';
                        $html .= '<input type="hidden" name="client_' . $id_client . '_fac_' . $fac_idx . '" value="0"/>';
                    }

                    $fac_commandes = array();

                    foreach ($facture_data['lines'] as $id_line) {
                        $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', $id_line);
                        if (BimpObject::objectLoaded($line)) {
                            $id_commande = (int) $line->getData('id_obj');
                            if (!in_array($id_commande, $fac_commandes)) {
                                $fac_commandes[] = $id_commande;
                            }
                        }
                    }

                    $fac_libelle = 'Facturation périodique';
                    if (count($fac_commandes) == 1) {
                        $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $fac_commandes[0]);

                        if (BimpObject::objectLoaded($commande)) {
                            $fac_libelle .= ' - ' . $commande->getData('libelle');
                        }
                    }

                    $html .= '<div class="fac_libelle_container" style="margin-top: 10px">';
                    $html .= '<span class="small bold">Libellé facture : </span>';
                    $html .= BimpInput::renderInput('text', 'client_' . $id_client . '_fac_' . $fac_idx . '_libelle', $fac_libelle);
                    $html .= '</div>';

                    $html .= '</div>';

                    $html .= '</td>';
                    $html .= '</tr>';

                    foreach ($facture_data['lines'] as $id_line) {
                        $tr_class = '';
                        $row_html = '';

                        $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', $id_line);
                        if (BimpObject::objectLoaded($line)) {
                            $periods_data = $line->getNbPeriodsToBillData(null);

                            $row_html .= '<td style="min-width: 30px; max-width: 30px; text-align: center">';
                            if ($periods_data['nb_periods_tobill_today'] > 0) {
                                $tr_class = 'selected';
                                $row_html .= '<input type="checkbox" name="line_' . $line->id . '_check" class="line_check" checked="1"/>';
                            } else {
                                $tr_class = 'deactivated';
                            }
                            $row_html .= '</td>';

                            $row_html .= '<td>';
                            $commande = $line->getParentInstance();
                            if (BimpObject::objectLoaded($commande)) {
                                $row_html .= $commande->getLink();
                            }
                            $row_html .= '</td>';

                            $row_html .= '<td>';
                            $product = $line->getProduct();
                            if (BimpObject::objectLoaded($product)) {
                                $row_html .= $product->getLink() . '<br/>';
                                $row_html .= $product->getName() . '<br/>';
                                $row_html .= $line->displayPeriodicity(false, array('fac'));
                            }
                            $row_html .= '</td>';

                            $row_html .= '<td style="min-width: 250px">';
//                                $row_html .= '<pre>';
//                                $row_html .= print_r($periods_data, 1);
//                                $row_html .= '</pre>';

                            $row_html .= 'Déjà traité : ' . $line->displayBilledPeriods(true, $periods_data) . '<br/>';

                            $class = ($periods_data['nb_periods_tobill_today'] > 0 ? ($periods_data['nb_periods_tobill_today'] > 1 ? 'important' : 'warning') : 'danger');
                            $s = ($periods_data['nb_periods_tobill_today'] > 1 ? 's' : '');

                            $row_html .= 'A traiter aujoud\'hui : <span class="' . $class . '">' . $periods_data['nb_periods_tobill_today'] . ' période' . $s . '</span>';
                            $row_html .= '&nbsp;(';
                            $row_html .= BimpTools::displayFloatValue($periods_data['nb_periods_tobill_today'] * $periods_data['qty_for_1_period'], 4, ',', 0, 1, 0, 1, 1, 1);
                            $row_html .= ' unité' . $s . ')<br/>';

                            if ($periods_data['nb_periods_tobill_today'] > 0) {
                                $row_html .= '<br/>';
                                $row_html .= '<b>Nb périodes à facturer: </b><br/>';
                                $row_html .= BimpInput::renderInput('qty', 'line_' . $line->id . '_qty', $periods_data['nb_periods_tobill_today'], array(
                                            'extra_class' => 'line_qty',
                                            'max_label'   => true,
                                            'data'        => array(
                                                'max'      => $periods_data['nb_periods_tobill_today'],
                                                'min'      => 0,
                                                'decimals' => 0
                                            )
                                ));
                            }

                            $row_html .= '</td>';
                            $row_html .= '<td>';
                            $row_html .= $line->displayNextPeriodDate('fac', true);
                            $row_html .= '</td>';
                        } else {
                            $row_html .= '<td colspan="99">';
                            $row_html .= BimpRender::renderAlerts('La ligne de commande #' . $id_line . ' n\'existe plus');
                            $row_html .= '</td>';
                        }

                        $html .= '<tr class="commande_line_row' . ($tr_class ? ' ' . $tr_class : '') . '"';
                        $html .= ' data-id_client="' . $id_client . '"';
                        $html .= ' data-fac_idx="' . $fac_idx . '"';
                        $html .= ' data-id_line="' . $id_line . '"';
                        $html .= '>';
                        $html .= $row_html;
                        $html .= '</tr>';
                    }
                }
            }

            $html .= '</tbody>';
            $html .= '</table>';
        }

        return $html;
    }

    public function renderPeriodicAchatMassProcessInputs(&$errors = array())
    {
        $html = '';

        $id_fourn_filter = (int) BimpTools::getPostFieldValue('id_fourn', 0, 'int');
        $id_client = (int) BimpTools::getPostFieldValue('id_client', 0, 'int');
        $id_product = (int) BimpTools::getPostFieldValue('id_product', 0, 'int');

        $lines = self::getPeriodicAchatLinesToProcess($errors, $id_fourn_filter, $id_client, $id_product);

        // Trie par fournisseur et entrepot: 
        $lines_by_fourns = array();

        foreach ($lines as $id_line => $line_data) {
            $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', $id_line);
            if (!BimpObject::objectLoaded($line)) {
                continue;
            }

            $id_entrepot = (int) BimpTools::getArrayValueFromPath($line_data, 'id_entrepot', 0);
            if (!$id_entrepot) {
                $errors[] = 'Ligne #' . $id_line . ' : entrepôt absent';
                continue;
            }

            $line_errors = array();
            $params = $line->getData('periodicity_extra_params');

            $id_fourn = 0;
            $pa_ht = 0;
            $type_pa = (int) BimpTools::getArrayValueFromPath($params, 'achat_type_pa', 0);
            $type_pa_label = '';
            switch ($type_pa) {
                case 1:
                    $type_pa_label = 'PA fournisseur configuré';
                    $id_pfp = (int) BimpTools::getArrayValueFromPath($params, 'achat_id_fourn_price', 0);
                    if ($id_pfp) {
                        $pfp = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ProductFournisseurPrice', $id_pfp);
                        if (BimpObject::objectLoaded($pfp)) {
                            $type_pa_label = 'Dernier PA fournisseur enregistré pour ce produit';
                            $id_fourn = $pfp->getData('fk_soc');
                            $pa_ht = $pfp->getData('price');
                        } else {
                            $line_errors[] = 'PA fournisseur #' . $id_pfp . ' n\'existe plus';
                        }
                    } else {
                        $line_errors[] = 'PA fournisseur non défini';
                    }
                    break;

                case 2:
                    $type_pa_label = 'PA personnalisé';
                    $id_fourn = (int) BimpTools::getArrayValueFromPath($params, 'achat_id_fourn', 0);
                    $pa_ht = (float) BimpTools::getArrayValueFromPath($params, 'achat_pa_ht', 0);
                    break;

                default:
                    $product = $line->getProduct();
                    if (BimpObject::objectLoaded($product)) {
                        $pfp = $product->getLastFournPrice();
                        if (BimpObject::objectLoaded($pfp)) {
                            $type_pa_label = 'Dernier PA fournisseur enregistré pour ce produit';
                            $id_fourn = (int) $pfp->getData('fk_soc');
                            $pa_ht = (float) $pfp->getData('price');
                            break;
                        }
                    }
                    $line_errors[] = 'Aucun prix d\'achat configuré';
                    break;
            }

            if ($id_fourn_filter && $id_fourn != $id_fourn_filter) {
                continue;
            }

            if (!isset($lines_by_fourns[$id_fourn])) {
                $lines_by_fourns[$id_fourn] = array();
            }

            if (!isset($lines_by_fourns[$id_fourn][$id_entrepot])) {
                $lines_by_fourns[$id_fourn][$id_entrepot] = array();
            }

            $lines_by_fourns[$id_fourn][$id_entrepot][$id_line] = array(
                'pa_ht'         => $pa_ht,
                'type_pa_label' => $type_pa_label,
                'errors'        => $line_errors
            );
        }

        if (empty($lines_by_fourns)) {
            $html .= BimpRender::renderIcon('fas_check', 'iconLeft') . '<span class = "success">Aucun achat périodique à effectuer à date</span>';
        } else {
            $html .= '<div class="buttonsContainer align-right" style="margin:  0 0 5px 0; padding: 0">';
            $html .= '<span class="btn btn-default check_all_lines">';
            $html .= BimpRender::renderIcon('far_check-square', 'iconLeft') . 'Tout cocher';
            $html .= '</span>';
            $html .= '<span class="btn btn-default uncheck_all_lines">';
            $html .= BimpRender::renderIcon('far_square', 'iconLeft') . 'Tout décocher';
            $html .= '</span>';
            $html .= '</div>';

            $html .= '<table class = "bimp_list_table">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th style="min-width: 30px; max-width: 30px; text-align: center"></th>';
            $html .= '<th>Commande client</th>';
            $html .= '<th>Produit / service</th>';
            $html .= '<th>Quantités</th>';
            $html .= '<th>Date prochain achat</th>';
            $html .= '<th>Prix d\'achat</th>';
            $html .= '</tr>';
            $html .= '</thead>';

            $html .= '<tbody>';

            foreach ($lines_by_fourns as $id_fourn => $entrepots) {
                if ($id_fourn) {
                    $fourn = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Fournisseur', $id_fourn);
                    if (!BimpObject::objectLoaded($fourn)) {
                        $html .= '<tr>';
                        $html .= '<td colspan="99">' . BimpRender::renderAlerts('Le fournisseur #' . $id_fourn . ' n\'existe plus') . '</td>';
                        $html .= '</tr>';
                        continue;
                    }
                }

                $html .= '<tr class="fourn_row" data-id_fourn="' . $id_fourn . '">';
                $html .= '<td colspan="99" style="font-weight: bold; font-size: 14px; padding: 10px; background-color: #DCDCDC">';
                if ($id_fourn) {
                    $html .= 'Fournisseur : ' . $fourn->getLink();
                } else {
                    $html .= '<span class="danger">';
                    $html .= BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft') . 'Lignes de commandes sans prix d\'achat fournisseur défini';
                    $html .= '</span>';
                }

                $html .= '</td>';
                $html .= '</tr>';

                foreach ($entrepots as $id_entrepot => $entrepot_lines) {
                    if ($id_entrepot) {
                        $entrepot = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Entrepot', $id_entrepot);
                        $html .= '<tr class="fourn_entrepot_row" data-id_fourn="' . $id_fourn . '" data-id_entrepot="' . $id_entrepot . '">';
                        $html .= '<td colspan="99" style="font-weight: bold; font-size: 12px; padding: 10px; background-color: #DCDCDC">';
                        $html .= 'Entrepôt : ' . $entrepot->getLink();

                        $commandes = $this->getCommandesFournisseursArray($id_fourn, $id_entrepot);

                        $html .= '<div style="display: inline-block; max-width: 400px; margin-left: 30px; font-size: 12px; font-weight: normal">';
                        $html .= '<span class="small">Commande fournisseur : </span>';

                        if (count($commandes) > 1) {
                            $html .= BimpInput::renderInput('select', 'fourn_' . $id_fourn . '_entrepot_' . $id_entrepot . '_commande_fourn', 'new', array(
                                        'options' => $commandes
                            ));
                        } else {
                            $html .= 'Nouvelle commande fournisseur';
                            $html .= '<input type="hidden" name="fourn_' . $id_fourn . '_entrepot_' . $id_entrepot . '_commande_fourn" value="new"/>';
                        }

                        $html .= '</div>';

                        $html .= '</td>';
                        $html .= '</tr>';
                    }

                    foreach ($entrepot_lines as $id_line => $line_data) {
                        $tr_class = '';
                        $row_html = '';

                        $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', $id_line);
                        if (BimpObject::objectLoaded($line)) {
                            $periods_data = $line->getNbPeriodesToBuyData(null);

                            $row_html .= '<td style="min-width: 30px; max-width: 30px; text-align: center">';
                            if ($id_fourn) {
                                if ($periods_data['nb_periods_tobuy_today'] > 0) {
                                    $tr_class = 'selected';
                                    $row_html .= '<input type="checkbox" name="line_' . $line->id . '_check" class="line_check" checked="1"/>';
                                } else {
                                    $tr_class = 'deactivated';
                                }
                            } else {
                                $tr_class = 'deactivated';
                            }

                            $row_html .= '</td>';

                            $row_html .= '<td>';
                            $commande = $line->getParentInstance();
                            if (BimpObject::objectLoaded($commande)) {
                                $row_html .= $commande->getLink();
                            }
                            $row_html .= '</td>';

                            $row_html .= '<td>';
                            $product = $line->getProduct();
                            if (BimpObject::objectLoaded($product)) {
                                $row_html .= $product->getLink() . '<br/>';
                                $row_html .= $product->getName() . '<br/>';
                                $row_html .= $line->displayPeriodicity(false, array('exp'));
                            }
                            $row_html .= '</td>';

                            $line_errors = BimpTools::getArrayValueFromPath($line_data, 'errors', array());

                            if (count($line_errors)) {
                                $row_html .= '<td colspan="4">';
                                $row_html .= BimpRender::renderAlerts($line_errors);
                                $row_html .= '</td>';
                            } else {
                                $row_html .= '<td style="min-width: 250px">';
                                $row_html .= 'Déjà traité : ' . $line->displayBoughtPeriods(true, $periods_data) . '<br/>';

                                $class = ($periods_data['nb_periods_tobuy_today'] > 0 ? ($periods_data['nb_periods_tobuy_today'] > 1 ? 'important' : 'warning') : 'danger');
                                $s = ($periods_data['nb_periods_tobuy_today'] > 1 ? 's' : '');
                                $row_html .= 'A traiter aujoud\'hui : <span class="' . $class . '">' . $periods_data['nb_periods_tobuy_today'] . ' période' . $s . '</span>';
                                $row_html .= '&nbsp;(' . ($periods_data['nb_periods_tobuy_today'] * $periods_data['qty_for_1_period']) . ' unité' . $s . ')<br/>';

                                if ($id_fourn && $periods_data['nb_periods_tobuy_today'] > 0) {
                                    $row_html .= '<br/>';
                                    $row_html .= '<b>Nb périodes à acheter: </b><br/>';
                                    $row_html .= BimpInput::renderInput('qty', 'line_' . $line->id . '_qty', $periods_data['nb_periods_tobuy_today'], array(
                                                'extra_class' => 'line_qty',
                                                'max_label'   => true,
                                                'data'        => array(
                                                    'max'      => $periods_data['nb_periods_tobuy_today'],
                                                    'min'      => 0,
                                                    'decimals' => 0
                                                )
                                    ));
                                }

                                $row_html .= '</td>';
                                $row_html .= '<td>';
                                $row_html .= $line->displayNextPeriodDate('achat', true);
                                $row_html .= '</td>';

                                $row_html .= '<td>';
                                $pa_ht = (float) BimpTools::getArrayValueFromPath($line_data, 'pa_ht', 0);

                                if ($id_fourn && $periods_data['nb_periods_tobuy_today'] > 0) {
                                    $row_html .= BimpInput::renderInput('text', 'line_' . $line->id . '_pa_ht', $pa_ht, array(
                                                'extra_class' => 'line_pa_ht',
                                                'addon_right' => BimpRender::renderIcon('fas_euro-sign'),
                                                'data'        => array(
                                                    'data_type' => 'number',
                                                    'decimals'  => 2
                                                )
                                    ));
                                } else {
                                    $row_html .= '<b>' . BimpTools::displayMoneyValue($pa_ht) . '</b>';
                                }


                                $pa_label = BimpTools::getArrayValueFromPath($line_data, 'type_pa_label', '');
                                if ($pa_label) {
                                    $row_html .= '<br/><span class="small">' . $pa_label . '</span>';
                                }

                                $row_html .= '</td>';
                            }
                        } else {
                            $row_html .= '<td colspan="99">';
                            $row_html .= BimpRender::renderAlerts('La ligne de commande #' . $id_line . ' n\'existe plus');
                            $row_html .= '</td>';
                        }

                        $html .= '<tr class="commande_line_row' . ($tr_class ? ' ' . $tr_class : '') . '" data-id_fourn="' . $id_fourn . '" data-id_entrepot="' . $id_entrepot . '" data-id_line="' . $id_line . '">';
                        $html .= $row_html;
                        $html .= '</tr>';
                    }
                }
            }

            $html .= '</tbody>';
            $html .= '</table>';
        }

        return $html;
    }

    public function renderAchatsList()
    {
        $html = '';

        if ($this->isLoaded()) {
            $cf_lines = BimpCache::getBimpObjectObjects('bimpcommercial', 'Bimp_CommandeFournLine', array(
                        'a.linked_object_name' => 'commande_line',
                        'a.linked_id_object'   => $this->id,
                        'cf.fk_statut'         => array(
                            'not_in' => array(6, 7, 9)
                        )
                            ), 'cf.rowid', 'ASC', array(
                        'cf' => array(
                            'table' => 'commande_fournisseur',
                            'on'    => 'cf.rowid = a.id_obj'
                        )
            ));

            if (!empty($cf_lines)) {
                $html .= '<table class="bimp_sub_list_table">';
                $html .= '<thead>';
                $html .= '<th>Commande fourn.</th>';
                $html .= '<th>Qté</th>';
                $html .= '<th>PA HT</th>';
                $html .= '<th>Total HT</th>';
                $html .= '</thead>';

                $html .= '<tbody>';

                $total_qty = 0;
                $total_ht = 0;
                foreach ($cf_lines as $line) {
                    $cf = $line->getParentInstance();
                    $html .= '<tr>';
                    $html .= '<td>' . (BimpObject::objectLoaded($cf) ? $cf->getLink() : '#' . $line->getData('fk_commande')) . '</td>';
                    $html .= '<td>' . $line->getFullQty() . '</td>';
                    $html .= '<td>' . $line->displayLineData('pu_ht') . '</td>';
                    $html .= '<td>' . $line->displayLineData('total_ht') . '</td>';
                    $html .= '</tr>';

                    $total_qty += (float) $line->getFullQty();
                    $total_ht += (float) $line->getTotalHt();
                }

                $html .= '<tr>';
                $html .= '<td style="font-weight: bold; border-top: 1px solid #777">Total : </td>';
                $html .= '<td style="font-weight: bold; border-top: 1px solid #777">' . $total_qty . '</td>';
                $html .= '<td style="font-weight: bold; border-top: 1px solid #777"></td>';
                $html .= '<td style="font-weight: bold; border-top: 1px solid #777">' . BimpTools::displayMoneyValue($total_ht) . '</td>';
                $html .= '</tr>';
                $html .= '</tbody>';
                $html .= '</table>';
            } else {
                $html .= '<span class="danger">Aucun achat effectué</span>';
            }
        }

        return $html;
    }

    // Traitements réservations:

    public function checkReservations()
    {
        $errors = array();

        if (!BimpCore::isModuleActive('bimpreservation')) {
            return $errors;
        }

        if ((float) $this->getFullQty() < 0) {
            return array();
        }

        if (!$this->isLoaded()) {
            $errors[] = 'ID de la ligne de commande absent';
        } else {
            $commande = $this->getParentInstance();

            if (!BimpObject::objectLoaded($commande)) {
                $errors[] = 'ID de la commande absent';
            } else {
                if (isset($commande::$no_check_reservations) && $commande::$no_check_reservations) {
                    return array();
                }

                if ((int) $commande->getData('fk_statut') > 1) {
                    return array();
                }

                if ((int) $this->getData('type') === self::LINE_PRODUCT) {
                    $product = $this->getProduct();
                    if (!BimpObject::objectLoaded($product)) {
                        $errors[] = 'ID du produit absent';
                    } else {
                        $reserved_qties = $this->getReservedQties();
                        if ((int) $product->getData('fk_product_type') === 0) {
                            $qty = (int) ceil($this->getFullQty() - (float) $reserved_qties['total']);
                            if ($qty > 0) {
                                // On Vérifie l'existence d'une réservation au statut "à traiter" pour cette ligne de commande: 
                                $reservation = BimpCache::findBimpObjectInstance('bimpreservation', 'BR_Reservation', array(
                                            'type'                    => BR_Reservation::BR_RESERVATION_COMMANDE,
                                            'id_commande_client'      => (int) $commande->id,
                                            'id_commande_client_line' => (int) $this->id,
                                            'status'                  => 0
                                                ), true);

                                if (BimpObject::objectLoaded($reservation)) {
                                    // Mise à jour des quantités de la réservation: 
                                    $qty += (int) $reservation->getData('qty');
                                    $reservation->set('qty', $qty);
                                    $res_warnings = array();
                                    $res_errors = $reservation->update($res_warnings, true);
                                    $res_errors = BimpTools::merge_array($res_errors, $res_warnings);

                                    if (count($res_errors)) {
                                        $errors[] = BimpTools::getMsgFromArray($res_errors, 'Des erreurs sont survenues lors de la mise des quantités de la réservation pour la ligne n° ' . $this->getData('position'));
                                    }
                                } else {
                                    // Création de la réservation
                                    $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');

                                    $ref = (string) $this->getData('ref_reservations');

                                    $res_errors = $reservation->validateArray(array(
                                        'ref'                     => $ref,
                                        'type'                    => BR_Reservation::BR_RESERVATION_COMMANDE,
                                        'id_commande_client'      => (int) $commande->id,
                                        'id_commande_client_line' => (int) $this->id,
                                        'id_entrepot'             => (int) $commande->getData('entrepot'),
                                        'id_client'               => (int) $commande->getData('fk_soc'),
                                        'id_commercial'           => (int) $commande->getData('fk_user_author'),
                                        'id_product'              => (int) $product->id,
                                        'id_equipment'            => 0,
                                        'status'                  => 0,
                                        'qty'                     => $qty,
                                        'date_from'               => date('Y-m-d H:i:s')
                                    ));

                                    $res_warnings = array();
                                    if (!count($res_errors)) {
                                        $res_errors = $reservation->create($res_warnings, true);
                                        $res_errors = BimpTools::merge_array($res_errors, $res_warnings);
                                    }

                                    if (count($res_errors)) {
                                        $errors[] = BimpTools::getMsgFromArray($res_errors, 'Des erreurs sont survenues lors de la création de la réservation pour la ligne n° ' . $this->getData('position'));
                                    } else {
                                        if (!$ref) {
                                            $this->updateField('ref_reservations', $reservation->getData('ref'));
                                        }
                                    }
                                }
                            } elseif ($qty < 0) {
                                $remain_qty = abs($qty);
                                $reservation = BimpCache::findBimpObjectInstance('bimpreservation', 'BR_Reservation', array(
                                            'type'                    => BR_Reservation::BR_RESERVATION_COMMANDE,
                                            'id_commande_client'      => (int) $commande->id,
                                            'id_commande_client_line' => (int) $this->id,
                                            'status'                  => 0
                                                ), true);

                                if (BimpObject::objectLoaded($reservation)) {
                                    $new_qty = $reservation->getData('qty') - (int) $remain_qty;
                                    if ($new_qty < 0) {
                                        $remain_qty = abs($new_qty);
                                        $new_qty = 0;
                                    }

                                    $res_warnings = array();
                                    if ($new_qty > 0) {
                                        // mise à jour de la réservation: 
                                        $qty += (int) $reservation->getData('qty');
                                        $reservation->set('qty', $qty);
                                        $res_errors = $reservation->update($res_warnings, true);
                                    } else {
                                        // Suppression de la réservation: 
                                        $res_errors = $reservation->delete($res_warnings, true);
                                    }
                                }

                                if ($remain_qty > 0) {
                                    $reservation = BimpCache::findBimpObjectInstance('bimpreservation', 'BR_Reservation', array(
                                                'type'                    => BR_Reservation::BR_RESERVATION_COMMANDE,
                                                'id_commande_client'      => (int) $commande->id,
                                                'id_commande_client_line' => (int) $this->id,
                                                'status'                  => 2
                                                    ), true);

                                    if (BimpObject::objectLoaded($reservation)) {
                                        $new_qty = $reservation->getData('qty') - (int) $remain_qty;
                                        if ($new_qty < 0) {
                                            $remain_qty = abs($new_qty);
                                            $new_qty = 0;
                                        }

                                        $res_warnings = array();
                                        if ($new_qty > 0) {
                                            // mise à jour de la réservation: 
                                            $qty += (int) $reservation->getData('qty');
                                            $reservation->set('qty', $qty);
                                            $reservation->update($res_warnings, true);
                                        } else {
                                            // Suppression de la réservation: 
                                            $reservation->delete($res_warnings, true);
                                        }
                                    }
                                }
                            }
                        } else {
                            // Service : on supprime toutes les réservations éventuelles: 
                            if ((float) $reserved_qties['total'] > 0) {
//                                mailSyn2("Suppression reservations", BimpCore::getConf('devs_email'), null, "Attention une reservation a été supprimé " . $product->getData('ref') . " comm: " . $this->getData("id_obj"));
                                foreach (BimpCache::getBimpObjectObjects('bimpreservation', 'BR_Reservation', array(
                                    'type'                    => BR_Reservation::BR_RESERVATION_COMMANDE,
                                    'id_commande_client'      => (int) $commande->id,
                                    'id_commande_client_line' => (int) $this->id,
                                )) as $res) {
                                    $res->delete($res_warnings, true);
                                }
                            }
                        }
                    }
                }
            }
        }
        return $errors;
    }

    public function addEquipments($equipments)
    {
        $errors = array();

        if (!$this->isLoaded()) {
            $errors[] = 'ID de la ligne de commande fournisseur absent';
        }

        $reservations = $this->getReservations('status', 'asc', 100);

        foreach ($reservations as $reservation) {
            if (!count($equipments)) {
                break;
            }

            $res_qty = (int) $reservation->getData('qty');

            $id_equipment = 0;
            while ($res_qty > 0) {
                if (!count($equipments)) {
                    break;
                }

                $id_equipment = array_shift($equipments);
                if (is_null($id_equipment) || !(int) $id_equipment) {
                    continue;
                }
                $res_errors = $reservation->setNewStatus(200, array(
                    'qty'          => 1,
                    'id_equipment' => (int) $id_equipment
                ));
                if (count($res_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($res_errors, 'Echec de l\'attribution de l\'équipement ' . $id_equipment . ' à la réservation ' . $reservation->id);
                } else {
                    $res_qty--;
                }
            }
        }

        if (count($equipments)) {
            foreach ($equipments as $id_equipment) {
                $msg = 'L\'équipement d\'ID ' . $id_equipment;
                $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                if (BimpObject::objectLoaded($equipment)) {
                    $msg .= ' (NS: ' . $equipment->getData('serial') . ')';
                }

                $msg .= ' n\'a pas pu être attribué à une réservation de la commande client';
                $errors[] = $msg;
            }
        }

        return $errors;
    }

    public function addReceivedQty($qty, $new_status = 200)
    {
        $errors = array();

        if (!$this->isLoaded($errors)) {
            return $errors;
        }

        $reservations = $this->getReservations('status', 'asc', 100);

        $remain_qty = $qty;

        if ($this->isProductSerialisable()) {
            $new_status = 101;
        }

        foreach ($reservations as $reservation) {
            if (!$remain_qty) {
                break;
            }

            if ($remain_qty > $reservation->getData('qty')) {
                $res_qty = $reservation->getData('qty');
            } else {
                $res_qty = $remain_qty;
            }
            $remain_qty -= $res_qty;

            $res_errors = $reservation->setNewStatus($new_status, array('qty' => $res_qty));
            if (count($res_errors)) {
                $errors[] = BimpTools::getMsgFromArray($res_errors, 'Des erreurs sont survenues lors de la mise à jour du statut de la réservation d\'ID ' . $reservation->id);
            }
        }

        return $errors;
    }

    public function autoReserveUnits($qty, &$errors = array())
    {
        if (!$this->isLoaded($errors)) {
            return 0;
        }

        if ($this->isProductSerialisable()) {
            $errors[] = 'Réservation automatique impossible pour les produits sérialisés';
            return 0;
        }

        $reservations = $this->getReservations('status', 'asc', '< 200');
        $remain_qty = $qty;

        foreach ($reservations as $reservation) {
            if (!$remain_qty) {
                break;
            }

            if ($remain_qty > $reservation->getData('qty')) {
                $res_qty = $reservation->getData('qty');
            } else {
                $res_qty = $remain_qty;
            }
            $remain_qty -= $res_qty;

            $res_errors = $reservation->setNewStatus(200, array('qty' => $res_qty));
            if (count($res_errors)) {
                $errors[] = BimpTools::getMsgFromArray($res_errors, 'Des erreurs sont survenues lors de la mise à jour du statut de la réservation d\'ID ' . $reservation->id);
            }
        }

        if ($remain_qty) {
            if ($remain_qty > 1) {
                $errors[] = $remain_qty . ' unités n\'ont pas pu être réservées automatiquement';
            } else {
                $errors[] = $remain_qty . ' unité n\'a pas pu être réservée automatiquement';
            }
            return 0;
        }

        return 1;
    }

    public function addToReceiveQty($qty, $new_status = 100)
    {
        $errors = array();

        $reservations = $this->getReservations('status', 'asc', 0);

        $remain_qty = $qty;

        foreach ($reservations as $reservation) {
            if (!$remain_qty) {
                break;
            }

            if ($remain_qty > $reservation->getData('qty')) {
                $res_qty = $reservation->getData('qty');
            } else {
                $res_qty = $remain_qty;
            }
            $remain_qty -= $res_qty;

            $res_errors = $reservation->setNewStatus($new_status, array('qty' => $res_qty));
            if (count($res_errors)) {
                $errors[] = BimpTools::getMsgFromArray($res_errors, 'Des erreurs sont survenues lors de la mise à jour du statut de la réservation d\'ID ' . $reservation->id);
            }
        }

        return $errors;
    }

    public function removeToReceiveQty($qty, $new_status = 0)
    {
        $errors = array();

        $reservations = $this->getReservations('status', 'asc', 100);

        $remain_qty = $qty;

        foreach ($reservations as $reservation) {
            if (!$remain_qty) {
                break;
            }

            if ($remain_qty > $reservation->getData('qty')) {
                $res_qty = $reservation->getData('qty');
            } else {
                $res_qty = $remain_qty;
            }
            $remain_qty -= $res_qty;

            $res_errors = $reservation->setNewStatus($new_status, array('qty' => $res_qty));
            if (count($res_errors)) {
                $errors[] = BimpTools::getMsgFromArray($res_errors, 'Des erreurs sont survenues lors de la mise à jour du statut de la réservation d\'ID ' . $reservation->id);
            }
        }

        return $errors;
    }

    // Traitements expéditions:

    public function checkShipmentEquipments($id_shipment, $equipments)
    {
        $errors = array();

        if (is_array($equipments)) {
            $shipment_data = $this->getShipmentData($id_shipment);
            $current_equipments = isset($shipment_data['equipments']) ? $shipment_data['equipments'] : array();
            $available_equipments = $this->getEquipementsToAttributeToShipment();

            foreach ($equipments as $id_equipment) {
                $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                if (!BimpObject::objectLoaded($equipment)) {
                    $errors[] = 'L\'équipement d\'ID ' . $id_equipment . ' n\'existe pas';
                } elseif (!in_array((int) $id_equipment, $current_equipments) && !in_array((int) $id_equipment, $available_equipments)) {
                    $errors[] = 'L\'équipement ' . $equipment->getData('serial') . ' (ID ' . $id_equipment . ') n\'est plus disponible pour cette expédition';
                }
            }
        }

        return $errors;
    }

    public function setShipmentData(BL_CommandeShipment $shipment, $data, &$warnings = array(), $convert_periods_qty = false)
    {
        $errors = array();

        if ($convert_periods_qty) {
            $qty = (float) BimpTools::getArrayValueFromPath($data, 'qty', 0);
            $data['qty'] = $this->getExpQtyFromNbPeriods($qty, (int) $shipment->id);
        }

        if (BimpObject::objectLoaded($shipment)) {
            $shipments = $this->getData('shipments');

            if (!is_array($shipments)) {
                $shipments = array();
            }

            $shipment_data = $this->getShipmentData((int) $shipment->id);
            $shipment_data['qty'] = (float) $data['qty'];

            // Grouper les articles: 
            $group = null;

            $product = $this->getProduct();
            $isSerialisable = 0;

            if (BimpObject::objectLoaded($product)) {
                if ($product->isSerialisable()) {
                    $isSerialisable = 1;
                }
                if ($shipment_data['qty'] > 0 && (int) $product->getData('fk_product_type') === 0 && !$isSerialisable) {
                    $group = isset($data['group_articles']) ? (int) $data['group_articles'] : (isset($data['group']) ? (int) $data['group'] : 0);
                }
            }

            if (!is_null($group)) {
                $shipment_data['group'] = $group;
            } elseif (isset($shipment_data['group'])) {
                unset($shipment_data['group']);
            }

            if (isset($data['id_entrepot'])) {
                $shipment_data['id_entrepot'] = (int) $data['id_entrepot'];
            } elseif (isset($shipment_data['id_entrepot'])) {
                unset($shipment_data['id_entrepot']);
            }

            // Vérification des quantités: 
            $total_qty_shipped = 0;
            foreach ($shipments as $id_shipment => $s_data) {
                if ((int) $id_shipment === $shipment->id) {
                    $total_qty_shipped += $data['qty'];
                } else {
                    $total_qty_shipped += (isset($s_data['qty']) ? (float) $s_data['qty'] : 0);
                }
            }

            if (abs($total_qty_shipped) > abs((float) $this->getShipmentsQty())) {
                $errors[] = 'Le nombre total d\'unités ajoutées à des expéditions (' . $total_qty_shipped . ') dépasse le nombre total d\'unités expédiables pour cette ligne de commande (' . $this->getShipmentsQty() . ')';
            }

            // Equipements:
            $equipments = array();
            if ($isSerialisable) {
                if (!isset($data['equipments'])) {
                    $data['equipments'] = array();
                }
                if (is_array($data['equipments'])) {
                    if (count($data['equipments']) > abs((int) $data['qty'])) {
                        $errors[] = 'Veuillez retirer ' . (count($data['equipments']) - abs((int) $data['qty'])) . ' équipement(s)';
                    } else {
                        $eq_errors = $this->checkShipmentEquipments($id_shipment, $data['equipments']);
                        if (count($eq_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($eq_errors);
                        } else {
                            $equipments = $data['equipments'];
                        }
                    }
                }
            }
            $shipment_data['equipments'] = $equipments;

            // Mise à jour: 
            if (!count($errors)) {
                $shipments[(int) $shipment->id] = $shipment_data;
                $errors = $this->updateField('shipments', $shipments);

                $ship_errors = $shipment->onLinesChange();
                if (count($ship_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($ship_errors, 'Expédition n°' . $shipment->getData('num_livraison'));
                }
            }
        } else {
            $errors[] = 'Expédition invalide';
        }

        $this->checkQties();

        if ((int) $this->getData('exp_periodicity') > 0) {
            $this->checkPeriodicityData('exp');
        }

        return $errors;
    }

    public function setShipmentsData($shipments_data, &$warnings = array(), $convert_periods_qty = false)
    {
        $errors = array();

        $shipments = $this->getData('shipments');
        $updated_shipments = array();

        $is_return = ((float) $this->getFullQty() < 0);
        $is_serialisable = $this->isProductSerialisable();

        foreach ($shipments_data as $data) {
            $id_shipment = isset($data['id_shipment']) ? (int) $data['id_shipment'] : 0;
            if (!$id_shipment) {
                continue;
            }

            $shipment = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeShipment', $id_shipment);
            if (!BimpObject::objectLoaded($shipment)) {
                $errors[] = 'L\'expédition d\'ID ' . $id_shipment . ' n\'existe plus';
                continue;
            }

            if ($convert_periods_qty) {
                $qty = (float) BimpTools::getArrayValueFromPath($data, 'qty', 0);
                $data['qty'] = $this->getExpQtyFromNbPeriods($qty, $id_shipment);
            }

            $shipment_editable = ((int) $shipment->getData('status') === Bl_CommandeShipment::BLCS_BROUILLON);

            if ($shipment_editable) {
                $shipment_data = $this->getShipmentData((int) $id_shipment);
                $shipment_data['qty'] = isset($data['qty']) ? (float) $data['qty'] : 0;
                $shipment_data['group'] = isset($data['group']) ? (int) $data['group'] : 0;

                if ($is_return) {
                    $shipment_data['id_entrepot'] = isset($data['id_entrepot']) ? (int) $data['id_entrepot'] : (int) $shipment->getData('id_entrepot');
                }

                if ($is_serialisable) {
                    $equipments = isset($data['equipments']) ? $data['equipments'] : array();
                    $eq_errors = $this->checkShipmentEquipments($id_shipment, $equipments);
                    if (count($eq_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($eq_errors, 'Expédition n°' . $shipment->getData('num_livraison'));
                    } else {
                        $shipment_data['equipments'] = $equipments;
                    }
                }

                $shipments[(int) $id_shipment] = $shipment_data;
            }

            $updated_shipments[] = $shipment;
        }

        $total_qty_shipped = 0;
        foreach ($shipments as $data) {
            $total_qty_shipped += (float) $data['qty'];
        }

        if (abs($total_qty_shipped) > abs((float) $this->getShipmentsQty())) {
            $errors[] = 'Les quantités totales ajoutées à des expéditions dépasse le nombre d\'unités expédiables pour cette ligne de commande. Veuillez corriger';
        }

        if (!count($errors)) {
            $this->set('shipments', $shipments);
            $errors = $this->updateField('shipments', $shipments);
        }

        if (!count($errors)) {
            foreach ($updated_shipments as $shipment) {
                $ship_errors = $shipment->onLinesChange();
                if (count($ship_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($ship_errors, 'Expédition n°' . $shipment->getData('num_livraison'));
                }
            }
        }

        $this->checkQties();

        return $errors;
    }

    public function setShipmentShipped(BL_CommandeShipment $shipment)
    {
        $errors = array();

        if (!$this->isReadyToShip($shipment->id, $errors)) {
            return $errors;
        }

        $shipment_data = $this->getShipmentData($shipment->id);
        $commande = $this->getParentInstance();

        $id_client = (int) $commande->getData('fk_soc');
        $id_contact = (int) $shipment->getcontact();
        $id_entrepot = (int) $shipment->getData('id_entrepot');

        if (!BimpObject::objectLoaded($commande)) {
            return array('ID de la commande client absent ou invalide');
        }

        if (!$id_entrepot && (int) BimpCore::getConf('USE_ENTREPOT', null, 'bimpcore')) {
            return array('ID de l\'entrepôt absent pour cette expédition');
        }

        if ((int) $this->getData('type') === self::LINE_PRODUCT) {
            $product = $this->getProduct();

            if (!BimpObject::objectLoaded($product)) {
                if ((int) $this->id_product) {
                    $errors[] = 'Le produit d\'ID ' . $this->id_product . ' n\'existe plus';
                } else {
                    $errors[] = 'ID du produit absent';
                }
                return $errors;
            }
            if ((float) $shipment_data['qty'] >= 0) {
                // Cas des produits vendus: 
                // traitement des réservations: 
                $stock_label = 'Expédition n°' . $shipment->getData('num_livraison') . ' pour la commande client "' . $commande->getRef() . '"';
                $codemove = 'CO' . $commande->id . '_EXP' . $shipment->id;

                if ($this->isProductSerialisable()) {
                    $reservations = $this->getReservations('status', 'asc', '200');

                    foreach ($reservations as $reservation) {
                        $id_equipment = (int) $reservation->getData('id_equipment');
                        if ($id_equipment) {
                            if (in_array($id_equipment, $shipment_data['equipments'])) {
                                $equipment = $reservation->getChildObject('equipment');
                                if (!BimpObject::objectLoaded($equipment)) {
                                    $errors[] = 'L\'équipement d\'ID ' . $id_equipment . ' n\'existe pas';
                                } else {
                                    $res_errors = $reservation->setNewStatus(300, array('qty' => 1, 'id_equipment' => $id_equipment));
                                    if (count($res_errors)) {
                                        $errors[] = BimpTools::getMsgFromArray($res_errors, 'Echec de la mise à jour du statut pour l\'équipement ' . $equipment->getData('serial'));
                                    } else {
                                        $reservation->set('origin', 'commande_shipment');
                                        $reservation->set('id_origin', (int) $shipment->id);
                                        $reservation->update();

                                        // Mise à jour de l'emplacement de l'équipement: 
                                        $place_errors = $equipment->moveToPlace(BE_Place::BE_PLACE_CLIENT, $id_client, $codemove, $stock_label, 1, '', 'commande', (int) $commande->id, $id_contact);

                                        if (count($place_errors)) {
                                            $errors[] = BimpTools::getMsgFromArray($place_errors, 'Echec de la création du nouvel emplacement pour l\'équipement ' . $equipment->getData('serial') . ' (ID ' . $id_equipment . ')');
                                        }
                                    }
                                }
                            }
                        }
                    }
                } else {
                    $reservations = $this->getReservations('status', 'asc', '200');

                    $remain_qty = $shipment_data['qty'];

                    foreach ($reservations as $reservation) {
                        if ($remain_qty <= 0) {
                            break;
                        }
                        $qty = $remain_qty;
                        if ($qty > (int) $reservation->getData('qty')) {
                            $qty = (int) $reservation->getData(('qty'));
                            $remain_qty -= $qty;
                        }
                        $res_errors = $reservation->setNewStatus(300, array('qty' => $qty));
                        if (count($res_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($res_errors, 'Echec de la mise à jour du statut de la réservation d\'ID ' . $reservation->id);
                        } else {
                            // Retrait des stocks
                            $stock_errors = $product->correctStocks($id_entrepot, $shipment_data['qty'], Bimp_Product::STOCK_OUT, $codemove, $stock_label, 'commande', (int) $commande->id);
                            if (count($stock_errors)) {
                                $errors[] = BimpTools::getMsgFromArray($stock_errors);
                            }
                        }
                    }
                }

                // Traitement durée limitée: 
                if (BimpObject::objectLoaded($product) && (int) $product->getData('duree') > 0) {
                    if (empty($this->date_from)) {
                        $date_from = $shipment->getData('date_shipped');
                        if (!empty($date_from)) {
                            $this->date_from = $date_from;
                        }
                        $dt = new DateTime($date_from);
                        $dt->add(new DateInterval('P' . (int) $product->getData('duree') . 'M'));
                        $this->date_to = $dt->format('Y-m-d');
                        $w = array();
                        $this->update($w, true);
                    }
                }
            } else {
                // Cas des produits retournés: 

                if (BimpObject::ObjectLoaded($product) && !(int) $product->getData('fk_product_type')) {
                    $stock_label = 'Retour - Expédition n°' . $shipment->getData('num_livraison') . ' - Commande client "' . $commande->getRef() . '"';
                    $codemove = 'CO' . $commande->id . '_EXP' . $shipment->id;
                    if ($this->isProductSerialisable()) {
                        $equipments_returned = $this->getData('equipments_returned');

                        if (!isset($shipment_data['equipments']) || count($shipment_data['equipments']) !== abs($shipment_data['qty'])) {
                            $errors[] = 'Le nombre d\'équipements sélectionnés est incorrect';
                        } else {
                            //  Vérifications: 
                            foreach ($shipment_data['equipments'] as $id_equipment) {
                                $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                                if (!BimpObject::objectLoaded($equipment)) {
                                    $errors[] = 'L\'équipement d\'ID ' . $id_equipment . ' n\'existe plus';
                                    continue;
                                }

                                if (!array_key_exists((int) $id_equipment, $equipments_returned)) {
                                    $errors[] = 'L\'équipement "' . $equipment->getData('serial') . '" ne fait pas partie de la liste des équipement retournés enregistrés pour cette ligne de commande';
                                    continue;
                                } elseif (!(int) $equipments_returned[(int) $id_equipment]) {
                                    $errors[] = 'Aucun entrepôt de destination sélectionné pour l\'équipement "' . $equipment->getData('serial') . '"';
                                    continue;
                                }

                                $id_s = (int) $this->getEquipmentIdShipment($id_equipment);
                                if ($id_s && ($id_s !== (int) $shipment->id)) {
                                    $errors[] = 'L\'équipement "' . $equipment->getData('serial') . '" est déjà attribué à une autre expédition';
                                    continue;
                                }
                            }

                            if (!count($errors)) {
                                // Mise à jour des emplacements:             

                                foreach ($shipment_data['equipments'] as $id_equipment) {
                                    $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                                    $place = BimpObject::getInstance('bimpequipment', 'BE_Place');
                                    $place_errors = $place->validateArray(array(
                                        'id_equipment' => (int) $id_equipment,
                                        'type'         => BE_Place::BE_PLACE_ENTREPOT,
                                        'id_entrepot'  => (int) $equipments_returned[(int) $id_equipment],
                                        'infos'        => $stock_label,
                                        'date'         => date('Y-m-d H:i:s'),
                                        'code_mvt'     => $codemove,
                                        'origin'       => 'commande',
                                        'id_origin'    => (int) $commande->id
                                    ));

                                    if (!count($place_errors)) {
                                        $warnings = array();
                                        $place_errors = $place->create($warnings, true);
                                    }

                                    if (count($place_errors)) {
                                        $msg = BimpTools::getMsgFromArray($place_errors, 'Echec de la création du nouvel emplacement pour l\'équipement ' . $equipment->getData('serial') . ' (ID ' . $id_equipment . ')');
                                        $errors[] = $msg;
                                    }

                                    $equipment->updateField('id_commande_line_return', 0);
                                }
                            }
                        }
                    } else {
                        // Mise en stock: 
                        if (!isset($shipment_data['id_entrepot']) || !(int) $shipment_data['id_entrepot']) {
                            $errors[] = 'Entrepôt de destination absent';
                        } else {
                            $stock_errors = $product->correctStocks((int) $shipment_data['id_entrepot'], abs((float) $shipment_data['qty']), Bimp_Product::STOCK_IN, $codemove, $stock_label, 'commande', (int) $commande->id);
                            if (count($stock_errors)) {
                                $errors[] = BimpTools::getMsgFromArray($stock_errors);
                            }
                        }
                    }
                }
            }
        }

        // Mise à jour des données de l'expédition: 

        if (!count($errors)) {
            $shipments = $this->getData('shipments');
            $shipments[(int) $shipment->id]['shipped'] = 1;
            $up_errors = $this->updateField('shipments', $shipments);
            if (count($up_errors)) {
                $errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec de la mise à jour de la ligne de commande client');
            }
        }

        $this->checkQties();

        return $errors;
    }

    public function cancelShipmentShipped(BL_CommandeShipment $shipment)
    {
        $errors = array();
        global $user;

        if (!$this->isLoaded()) {
            $errors[] = 'ID de la ligne de commande absent';
            return $errors;
        }

        if (!BimpObject::ObjectLoaded($shipment)) {
            $errors[] = 'ID de l\'expédition absent';
            return $errors;
        }

        $shipment_data = $this->getShipmentData((int) $shipment->id);

        if (!isset($shipment_data['qty']) || !(float) $shipment_data['qty']) {
            return array();
        }

        if (!isset($shipment_data['shipped']) || !$shipment_data['shipped']) {
            $errors[] = 'L\'expédition n\'est pas marquée comme validée pour cette ligne de commande';
            return $errors;
        }

        $commande = $this->getParentInstance();

        if (!BimpObject::objectLoaded($commande)) {
            $errors[] = 'ID de la commande client absent';
            return $errors;
        }

        $id_entrepot = (int) $shipment->getData('id_entrepot');

        $product = $this->getProduct();
        $stock_label = 'Annulation de l\'expédition n°' . $shipment->getData('num_livraison') . ' pour la commande client "' . $commande->getRef() . '"';
        $codemove = 'CO' . $commande->id . '_EXP' . $shipment->id . '_ANNUL';

        // Cas des produits vendus: 
        if ((float) $shipment_data['qty'] > 0) {
            if (BimpObject::objectLoaded($product) && $product->isSerialisable()) {

                // Traitement des réservations: 
                $reservations = $this->getReservations('status', 'asc', '300', (int) $shipment->id);

                foreach ($reservations as $reservation) {
                    $id_equipment = (int) $reservation->getData('id_equipment');
                    if ($id_equipment) {
                        if (in_array($id_equipment, $shipment_data['equipments'])) {
                            $equipment = $reservation->getChildObject('equipment');
                            if (!BimpObject::objectLoaded($equipment)) {
                                $errors[] = 'L\'équipement d\'ID ' . $id_equipment . ' n\'existe pas';
                            } else {
                                $res_errors = $reservation->setNewStatus(200, array('qty' => 1, 'id_equipment' => $id_equipment));
                                if (count($res_errors)) {
                                    $errors[] = BimpTools::getMsgFromArray($res_errors, 'Echec de la mise à jour du statut pour l\'équipement ' . $equipment->getData('serial'));
                                } else {
                                    // Mise à jour de l'emplacement de l'équipement:  
                                    $place = BimpObject::getInstance('bimpequipment', 'BE_Place');
                                    $place_errors = $place->validateArray(array(
                                        'id_equipment' => $id_equipment,
                                        'type'         => BE_Place::BE_PLACE_ENTREPOT,
                                        'id_entrepot'  => $id_entrepot,
                                        'infos'        => $stock_label,
                                        'date'         => date('Y-m-d H:i:s'),
                                        'code_mvt'     => $codemove,
                                        'origin'       => 'commande',
                                        'id_origin'    => (int) $commande->id
                                    ));

                                    if (!count($place_errors)) {
                                        $warnings = array();
                                        $place_errors = $place->create($warnings, true);
                                    }

                                    if (count($place_errors)) {
                                        $msg = BimpTools::getMsgFromArray($place_errors, 'Echec de la création du nouvel emplacement pour l\'équipement ' . $equipment->getData('serial') . ' (ID ' . $id_equipment . ')');
                                        $errors[] = $msg;
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                $reservations = $this->getReservations('status', 'asc', '300');

                $remain_qty = $shipment_data['qty'];

                foreach ($reservations as $reservation) {
                    if ($remain_qty <= 0) {
                        break;
                    }
                    $qty = $remain_qty;
                    if ($qty > (int) $reservation->getData('qty')) {
                        $qty = (int) $reservation->getData(('qty'));
                        $remain_qty -= $qty;
                    }
                    $res_errors = $reservation->setNewStatus(200, array('qty' => $qty));
                    if (count($res_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($res_errors, 'Echec de la mise à jour du statut de la réservation d\'ID ' . $reservation->id);
                    } else {
                        // Remise des stocks
                        $stock_errors = $product->correctStocks($id_entrepot, $shipment_data['qty'], Bimp_Product::STOCK_IN, $codemove, $stock_label, 'commande', (int) $commande->id);
                        if (count($stock_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($stock_errors);
                        }
                    }
                }
            }

            // Traitement durée limitée: 
            if (BimpObject::objectLoaded($product) && (int) $product->getData('duree') > 0) {
                $date_shipped = $shipment->getData('date_shipped');
                if (!empty($date_shipped)) {
                    $dt_shipped = new DateTime($date_shipped);
                    $date_shipped = $dt_shipped->format('Y-m-d');

                    if ($this->date_from == $date_shipped) {
                        $this->date_from = null;
                        $this->date_to = null;
                        $w = array();
                        $this->update($w, true);
                    }
                }
            }
        } else {
            // Cas des produits retournés: 
            $stock_label = 'Retour - ' . $stock_label;

            if (BimpObject::ObjectLoaded($product) && !$product->getData('fk_product_type')) {
                if ($product->isSerialisable()) {
                    // Création des emplacements: 
                    $id_client = (int) $commande->getData('fk_soc');
                    foreach ($shipment_data['equipments'] as $id_equipment) {
                        $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                        if (BimpObject::ObjectLoaded($equipment)) {
                            $place = BimpObject::getInstance('bimpequipment', 'BE_Place');
                            $place_errors = $place->validateArray(array(
                                'id_equipment' => (int) $id_equipment,
                                'type'         => BE_Place::BE_PLACE_CLIENT,
                                'id_client'    => $id_client,
                                'infos'        => $stock_label,
                                'date'         => date('Y-m-d H:i:s'),
                                'code_mvt'     => $codemove,
                                'origin'       => 'commande',
                                'id_origin'    => (int) $commande->id
                            ));

                            if (!count($place_errors)) {
                                $warnings = array();
                                $place_errors = $place->create($warnings, true);
                            }

                            if (count($place_errors)) {
                                $msg = BimpTools::getMsgFromArray($place_errors, 'Echec de la création du nouvel emplacement pour l\'équipement ' . $equipment->getData('serial') . ' (ID ' . $id_equipment . ')');
                                $errors[] = $msg;
                            }

                            $equipment->updateField('id_commande_line_return', (int) $this->id);
                        }
                    }
                } else {
                    // Retraits des stocks: 
                    if (isset($shipment_data['id_entrepot']) && (int) $shipment_data['id_entrepot']) {
                        $stock_errors = $product->correctStocks((int) $shipment_data['id_entrepot'], abs($shipment_data['qty']), Bimp_Product::STOCK_OUT, $codemove, $stock_label, 'commande', (int) $commande->id);
                        if (count($stock_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($stock_errors);
                        }
                    }
                }
            }
        }

        // Mise à jour des données de l'expédition: 

        if (!count($errors)) {
            $shipments = $this->getData('shipments');
            if (isset($shipments[(int) $shipment->id])) {
                unset($shipments[(int) $shipment->id]);
            }
            $up_errors = $this->updateField('shipments', $shipments);
            if (count($up_errors)) {
                $errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec de la mise à jour de la ligne de commande client');
            }
        }

        $this->checkQties();

        return $errors;
    }

    public function addEquipmentsToShipment($id_shipment, $equipments, $new_qty = null)
    {
        $errors = array();

        if (!count($equipments)) {
            $errors[] = 'Aucun équipement sélectionné';
            return $errors;
        }

        $shipment_data = $this->getShipmentData($id_shipment);

        $shipments = $this->getData('shipments');
        if (!is_null($new_qty)) {
            if ((int) $new_qty !== (int) $shipment_data['qty']) {
                $remain = (int) $this->getShipmentsQty();
                foreach ($shipments as $id_s => $s_data) {
                    if ((int) $id_s !== (int) $id_shipment) {
                        $remain -= (int) $s_data['qty'];
                    }
                }

                if (abs((int) $new_qty) > abs((int) $remain)) {
                    $errors[] = 'Nouvelles quantités d\'unités assignées à l\'expédition invalides.<br/>Veuillez retirer ' . ($new_qty - $remain) . ' unité(s)';
                } else {
                    $shipment_data['qty'] = $new_qty;
                }
            }
        }

        if (count($errors)) {
            return $errors;
        }

        // Vérification concernant les équipements à assigner:
        foreach ($equipments as $id_equipment) {
            $eq_id_shipment = (int) $this->getEquipmentIdShipment($id_equipment);
            if ($eq_id_shipment) {
                $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $id_equipment);
                if (BimpObject::objectLoaded($equipment)) {
                    $serial = ' - ns: ' . $equipment->getData('serial');
                } else {
                    $serial = '';
                }
                $errors[] = 'L\'équipement ' . $id_equipment . $serial . ' est déjà assigné à une expédition';
            } else {
                if ((float) $this->getFullQty() > 0) {
                    $reservation = BimpCache::findBimpObjectInstance('bimpreservation', 'BR_Reservation', array(
                                'id_commande_client_line' => (int) $this->id,
                                'id_equipment'            => (int) $id_equipment
                                    ), false, false);

                    if (!BimpObject::objectLoaded($reservation)) {
                        $errors[] = 'L\'équipement ' . $id_equipment . ' - ns: ' . $equipment->getData('serial') . ' n\'est pas associé à cette ligne de commande';
                    }
                } else {
                    $equipments_returned = $this->getData('equipments_returned');
                    if (!array_key_exists((int) $id_equipment, $equipments_returned)) {
                        $errors[] = 'L\'équipement retourné  "' . $equipment->getData('serial') . '" n\'est pas associé à cette ligne de commande';
                    }
                }
            }
        }

        if (count($errors)) {
            return $errors;
        }

        if (!isset($shipment_data['equipments'])) {
            $shipment_data['equipments'] = array();
        }

        $remain_qty = abs((int) $shipment_data['qty']) - count($shipment_data['equipments']);

        if (!$remain_qty) {
            $errors[] = 'Il n\'y a plus d\'équipements à assigner à cette expédition pour cette ligne de commande';
        } elseif (count($equipments) > (int) $remain_qty) {
            $errors[] = 'Il ne reste que ' . $remain_qty . ' équipements à assigner à cette expédition.<br/>Veuillez retirer ' . (abs($remain_qty) - count($equipments)) . ' unité(s).';
        } else {
            $shipment_data['equipments'] = BimpTools::merge_array($shipment_data['equipments'], $equipments);

            $shipments[(int) $id_shipment] = $shipment_data;

            $errors = $this->updateField('shipments', $shipments);
        }

        return $errors;
    }

    public function removeEquipmentFromShipment($id_equipment)
    {
        $errors = array();

        $shipments = $this->getData('shipments');

        foreach ($shipments as $id_shipment => $shipment_data) {
            if (isset($shipment_data['equipments']) && in_array((int) $id_equipment, $shipment_data['equipments'])) {
                $shipment = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeShipment', (int) $id_shipment);
                if (in_array((int) $shipment->getData('status'), array(BL_CommandeShipment::BLCS_BROUILLON, BL_CommandeShipment::BLCS_ANNULEE))) {
                    $shipments[(int) $id_shipment]['equipments'] = BimpTools::unsetArrayValue($shipment_data['equipments'], (int) $id_equipment);
                    $this->updateField('shipments', $shipments);
                } else {
                    $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                    $msg = 'L\'équipement "' . $equipment->getData('serial') . '" ne peut pas être retiré de l\'expédition n°';
                    $msg .= $shipment->getData('num_livraison') . ' car celle-ci a le statut "' . BL_CommandeShipment::$status_list[$shipment->getData('status')]['label'] . '"';
                    $errors[] = $msg;
                }
            }
        }

        return $errors;
    }

    // Traitements factures:

    public function checkFactureData($qty, $equipments, $id_facture = 0)
    {
        $errors = array();

        if (is_null($id_facture)) {
            $id_facture = 0;
        }

        $factures = $this->getData('factures');

        // Vérification des quantités: 
        $total_qty_billed = 0;
        foreach ($factures as $id_fac => $facture_data) {
            if ((int) $id_facture && ((int) $id_fac === (int) $id_facture)) {
                $total_qty_billed += $qty;
            } else {
                $total_qty_billed += (float) $facture_data['qty'];
            }
        }

        if (abs($total_qty_billed) > abs((float) $this->getFullQty())) {
            $errors[] = 'Le nombre total d\'unités ajoutées à des factures (' . $total_qty_billed . ') dépasse le nombre d\'unité enregistrées pour cette ligne de commande (' . $this->getFullQty() . ')';
        }

        if (!count($errors) && is_array($equipments) && !empty($equipments)) {
            if (count($equipments) > abs($qty)) {
                $errors[] = 'Le nombre d\'équipements (' . count($equipments) . ') dépasse le nombre d\'unités assignées à cette facture (' . abs($qty) . ')';
            } else {
                foreach ($equipments as $id_equipment) {
                    $eq_id_facture = (int) $this->getEquipmentIdFacture($id_equipment);
                    if ($eq_id_facture && (!$id_facture || (int) $id_facture !== $eq_id_facture)) {
                        $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $id_equipment);
                        if (BimpObject::objectLoaded($equipment)) {
                            $label = '"' . $equipment->getData('serial') . '" (ID: ' . $id_equipment . ')';
                        } else {
                            $label = 'd\'ID ' . $id_equipment;
                        }
                        $errors[] = 'L\'équipement ' . $label . ' est déjà assigné à une facture';
                    } else {
                        $check = true;
                        if ($this->getFullQty() >= 0) {
                            $reservation = BimpCache::findBimpObjectInstance('bimpreservation', 'BR_Reservation', array(
                                        'id_commande_client_line' => (int) $this->id,
                                        'id_equipment'            => (int) $id_equipment
                                            ), false, false);

                            if (!BimpObject::objectLoaded($reservation)) {
                                $check = false;
                            }
                        } else {
                            $returned_equipments = $this->getData('equipments_returned');
                            if (!is_array($returned_equipments) || !array_key_exists((int) $id_equipment, $returned_equipments)) {
                                $check = false;
                            }
                        }

                        if (!$check) {
                            $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                            if (BimpObject::objectLoaded($equipment)) {
                                $label = '"' . $equipment->getData('serial') . '" (ID: ' . $id_equipment . ')';
                            } else {
                                $label = 'd\'ID ' . $id_equipment;
                            }
                            $errors[] = 'L\'équipement ' . $label . ' n\'est pas associé à cette ligne de commande';
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function setFacturesData($factures_data, &$warnings = array())
    {
        $errors = array();

        if (!$this->isLoaded()) {
            $errors[] = 'ID de la ligne de commande client absent';
            return $errors;
        }

        $commande = $this->getParentInstance();
        if (!BimpObject::objectLoaded($commande)) {
            $errors[] = 'ID de la commande client absent';
            return $errors;
        }

        // Vérifications: 

        $fullQty = (float) $this->getFullQty();
        $total_qty = 0;

        foreach ($factures_data as $idx => $facture_data) {
            if (isset($facture_data['id_facture'])) {
                if ((int) $facture_data['id_facture'] === -1) {
                    continue;
                }

                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $facture_data['id_facture']);
                if (!BimpObject::objectLoaded($facture)) {
                    $errors[] = 'La facture d\'ID ' . $facture_data['id_facture'] . ' n\'existe pas';
                    continue;
                }

                if (isset($facture_data['periods']) && (int) $this->getData('fac_periodicity') && (int) $this->getData('fac_nb_periods')) {
                    $factures_data[$idx]['qty'] = ($fullQty / (int) $this->getData('fac_nb_periods')) * $facture_data['periods'];
                }

                if (!isset($factures_data[$idx]['qty'])) {
                    $factures_data[$idx]['qty'] = 0;
                }

                $total_qty += (float) $facture_data['qty'];
            }
        }

        $fullQty = (float) $this->getFullQty();
        if ($fullQty >= 0) {
            if ($total_qty > $fullQty) {
                $errors[] = 'Les quantités totales ajoutées à des factures (' . $total_qty . ') dépasse le nombre d\'unités enregistrées pour cette ligne de commande (' . $this->getFullQty() . '). Veuillez corriger';
            }
        } else {
            if ($total_qty < $fullQty) {
                $errors[] = 'Les quantités totales d\'unités retournées ajoutées à des factures (' . $total_qty . ') dépasse le nombre d\'unités à retourner enregistrées pour cette ligne de commande (' . $this->getFullQty() . '). Veuillez corriger';
            }
        }

        if (count($errors)) {
            return $errors;
        }

        foreach ($factures_data as $facture_data) {
            if ((int) $facture_data['id_facture'] === -1) {
                continue;
            }

            $qty = (float) $facture_data['qty'];
            $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $facture_data['id_facture']);
            $equipments = isset($facture_data['equipments']) ? $facture_data['equipments'] : array();

            // Vérification qté et équipements facture: 
            $check_errors = $this->checkFactureData($qty, $equipments, (int) $facture_data['id_facture']);

            if (count($check_errors)) {
                $errors[] = BimpTools::getMsgFromArray($check_errors, 'Facture "' . $facture->getData('ref') . '"');
                continue;
            }

            // Mise à jour de la ligne de facture: 

            if ((int) $facture->getData('fk_statut') == 0) {
                $data = array(
                    'qty' => (float) $facture_data['qty']
                );

                if (isset($facture_data['equipments'])) {
                    $data['equipments'] = $facture_data['equipments'];
                }
                if (isset($facture_data['pa_editable'])) {
                    $data['pa_editable'] = $facture_data['pa_editable'];
                }

                $fac_errors = $commande->addLinesToFacture((int) $facture->id, array(
                    $this->id => $data
                ));

                if (count($fac_errors)) {
                    $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $facture->id);
                    $errors[] = BimpTools::getMsgFromArray($fac_errors, 'Erreurs lors de la mise à jour de la facture "' . $facture->getRef() . '" (ID: ' . $facture->id . ')');
                    continue;
                }

                // Mise à jour de la ligne de commande: 
                $line_warnings = array();
                $line_errors = $this->setFactureData((int) $facture->id, $qty, $equipments, $line_warnings, false);
                if (count($line_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($line_errors, 'Erreurs lors de l\'enregistrement des données pour la facture "' . $facture->getData('ref') . '"');
                }
                if (count($line_warnings)) {
                    $warnings[] = BimpTools::getMsgFromArray($line_warnings, 'Erreurs lors de l\'enregistrement des données pour la facture "' . $facture->getData('ref') . '"');
                }
            }
        }

        $this->checkQties();

        return $errors;
    }

    public function setFactureData($id_facture, $qty, $equipments = array(), &$warnings = array(), $check_data = true)
    {
        if ($check_data) {
            $errors = $this->checkFactureData($qty, $equipments, $id_facture);
            if (count($errors)) {
                return $errors;
            }
        }

        $factures = $this->getData('factures');

        if (!is_array($factures)) {
            $factures = array();
        }

        if (!isset($factures[(int) $id_facture])) {
            $factures[(int) $id_facture] = array(
                'qty' => (float) $qty
            );
        } else {
            $factures[(int) $id_facture]['qty'] = (float) $qty;
        }

        if (is_array($equipments)) {
            $factures[(int) $id_facture]['equipments'] = $equipments;
        }

        $errors = $this->updateField('factures', $factures);

        $this->checkQties();

        return $errors;
    }

    public function onFactureDelete($id_facture)
    {
        if ($this->isLoaded()) {
            $factures = $this->getData('factures');

            if (isset($factures[(int) $id_facture])) {
                unset($factures[(int) $id_facture]);

                $this->updateField('factures', $factures);
            }

            $this->checkQties();

            if ((int) $this->getData('fac_periodicity') > 0) {
                $this->checkPeriodicityData('fac');
            }
        }
    }

    public function changeIdFacture($old_id_facture, $new_id_facture)
    {
        if (!$this->isLoaded()) {
            return array('ID de la ligne de commande absent');
        }

        $factures = $this->getData('factures');
        if (isset($factures[(int) $old_id_facture])) {
            $facture_data = $factures[(int) $old_id_facture];
            unset($factures[(int) $old_id_facture]);
            $factures[(int) $new_id_facture] = $facture_data;

            return $this->updateField('factures', $factures);
        }

        return array();
    }

    // Traitements achats:

    public function addToCommandeFourn($id_commande_fourn, $qty, $price_data, &$warnings = array())
    {
        $errors = array();
        $product = $this->getProduct();
        if (!BimpObject::objectLoaded($product)) {
            $errors[] = 'ID du produit absent';
        } else {
            $line = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFournLine');

            $line->validateArray(array(
                'id_obj'             => $id_commande_fourn,
                'type'               => ObjectLine::LINE_PRODUCT,
                'deletable'          => 0,
                'editable'           => 1,
                'remisable'          => 1,
                'linked_id_object'   => (int) $this->id,
                'linked_object_name' => 'commande_line'
            ));

            $pu_ht = (float) BimpTools::getArrayValueFromPath($price_data, 'pu_ht', 0);
            $tva_tx = (float) BimpTools::getArrayValueFromPath($price_data, 'tva_tx', 0);
            $id_fourn_price = (int) BimpTools::getArrayValueFromPath($price_data, 'id_fourn_price', 0);
            $ref_supplier = BimpTools::getArrayValueFromPath($price_data, 'ref_supplier', '');
            $id_fourn = (int) BimpTools::getArrayValueFromPath($price_data, 'id_fourn', 0);

            if (!$id_fourn_price && $id_fourn && $pu_ht) {
                $pfp = $product->getLastFournPrice($id_fourn);
                if (BimpObject::objectLoaded($pfp) && (round($pfp->getData('price'), 2) == round($pu_ht, 2))) {
                    $id_fourn_price = $pfp->id;

                    if (!$tva_tx) {
                        $tva_tx = $pfp->getData('tva_tx');
                    }

                    if (!$ref_supplier) {
                        $ref_supplier = $pfp->getData('ref_fourn');
                    }
                }
            }

            $line->desc = $this->desc;
            $line->id_product = (int) $product->id;
            $line->qty = (float) $qty;
            $line->id_fourn_price = $id_fourn_price;
            $line->ref_supplier = $ref_supplier;
            $line->pu_ht = $pu_ht;
            $line->tva_tx = $tva_tx;
            $line->date_from = $this->date_from;
            $line->date_to = $this->date_to;

            $errors = $line->create($warnings, true);

            if (!count($errors)) {
                if ($product->isTypeProduct()) {
                    $remain_qty = $qty;

                    $reservations = $this->getReservations('status', 'asc', 0);

                    foreach ($reservations as $reservation) {
                        if ($remain_qty <= 0) {
                            break;
                        }
                        $res_qty = (int) $reservation->getData('qty');
                        if ($remain_qty > $res_qty) {
                            $remain_qty -= $res_qty;
                        } else {
                            $res_qty = $remain_qty;
                            $remain_qty = 0;
                        }

                        $res_errors = $reservation->setNewStatus(100, array('qty' => $res_qty));

                        if (count($res_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($res_errors, 'Echec de la mise à jour du statut des produits pour ' . $res_qty . ' unité(s)');
                        }
                    }
                }
            }
        }

        return $errors;
    }

    // Traitements opé périodiques:

    public function checkPeriodicityData($type = 'all', &$infos = array())
    {
        if (!in_array($type, array('all', 'exp', 'fac', 'achat'))) {
            return array('Type invalide');
        }

        $errors = array();

        $fullQty = $this->getFullQty();

        if (in_array($type, array('all', 'exp'))) {
            $dateNextExp = '';
            if ($this->getData('exp_periodicity') > 0 && (int) $this->getData('exp_nb_periods') > 0) {
                $start = $this->getExpStartPeriode(true, $infos);
                if ($start) {
                    $data = $this->getNbPeriodsToShipData(null, false);

                    if ((int) $data['nb_periods_shipped'] >= (int) $data['nb_total_periods']) {
                        $dateNextExp = '9999-12-31';
                    } else {
                        $nbMoisOk = floor($data['nb_periods_shipped'] * (int) $this->getData('exp_periodicity'));
                        if ($nbMoisOk) {
                            $dateNextExp = date('Y-m-d', strtotime('+' . ($nbMoisOk) . ' month', strtotime($start)));
                        } else {
                            $dateNextExp = $start;
                        }
                    }
                }
            }

            $old_date = (string) $this->getData('next_date_exp');
            if ($old_date == '0000-00-00') {
                $old_date = '';
            }
            if ($old_date != (string) $dateNextExp) {
                $old_date = $this->getData('next_date_exp');
                $up_errors = $this->updateField('next_date_exp', $dateNextExp, null, true, false, true);

                if (!count($up_errors)) {
                    $infos[] = 'Mise à jour de la date de prochaine livraison (Ancienne : "' . $old_date . '" - Nouvelle : "' . $dateNextExp . '")';
                } else {
                    $errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec de la mise à jour de la date de prochaine livraison');
                }
            }
        }

        if (in_array($type, array('all', 'fac'))) {
            $dateNextFact = null;
            if ($this->getData('fac_periodicity') > 0 && (int) $this->getData('fac_nb_periods') > 0) {
                $start = $this->getFacStartPeriode(true, $infos);
                if ($start) {
                    $data = $this->getNbPeriodsToBillData(null, false);

                    if ((int) $data['nb_periods_billed'] >= (int) $data['nb_total_periods']) {
                        $dateNextFact = '9999-12-31';
                    } else {
                        $nbMoisOk = floor($data['nb_periods_billed'] * (int) $this->getData('fac_periodicity'));
                        if ($nbMoisOk) {
                            $dateNextFact = date('Y-m-d', strtotime('+' . ($nbMoisOk) . ' month', strtotime($start)));
                        } else {
                            $dateNextFact = $start;
                        }
                    }

                    if ($this->getData('fact_echue')) {
                        $dateNextFact = date('Y-m-d', strtotime('+' . $this->getData('fac_periodicity') . ' month', strtotime($dateNextFact)));
                    }
                }
            }

            $old_date = (string) $this->getData('next_date_fac');
            if ($old_date == '0000-00-00') {
                $old_date = '';
            }
            if ($old_date != (string) $dateNextFact) {
                $old_date = $this->getData('next_date_fac');
                $up_errors = $this->updateField('next_date_fac', $dateNextFact, null, true, false, true);

                if (!count($up_errors)) {
                    $infos[] = 'Mise à jour de la date de prochaine facturation (Ancienne : "' . $old_date . '" - Nouvelle : "' . $dateNextFact . '")';
                } else {
                    $errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec de la mise à jour de la date de prochaine facturation');
                }
            }
        }

        if (in_array($type, array('all', 'achat'))) {
            $dateNextAchat = null;
            if ($this->getData('achat_periodicity') > 0 && (int) $this->getData('achat_nb_periods') > 0) {
                $start = $this->getAchatStartPeriode(true, $infos);
                if ($start) {
                    $data = $this->getNbPeriodesToBuyData(null);

                    if ((int) $data['nb_periods_bought'] >= (int) $data['nb_total_periods']) {
                        $dateNextAchat = '9999-12-31';
                    } else {
                        $nbMoisOk = floor($data['nb_periods_bought'] * (int) $this->getData('achat_periodicity'));
                        if ($nbMoisOk) {
                            $dateNextAchat = date('Y-m-d', strtotime('+' . ($nbMoisOk) . ' month', strtotime($start)));
                        } else {
                            $dateNextAchat = $start;
                        }
                    }
                }
            }

            $old_date = (string) $this->getData('next_date_achat');
            if ($old_date == '0000-00-00') {
                $old_date = '';
            }

            if ($old_date != (string) $dateNextAchat) {
                $old_date = $this->getData('next_date_achat');
                $up_errors = $this->updateField('next_date_achat', $dateNextAchat, null, true, false, true);

                if (!count($up_errors)) {
                    $infos[] = 'Mise à jour de la date de prochain achat (Ancienne : "' . $old_date . '" - Nouvelle : "' . $dateNextAchat . '")';
                } else {
                    $errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec de la mise à jour de la date de prochain achat - ' . $nbMoisOk);
                }
            }
        }
        return $errors;
    }

    // Traitements divers:

    public function onLinkedShipmentChange($shipment)
    {
        $this->checkPeriodicityData('exp');
    }

    public function onLinkedFactureLineChange($fac_line)
    {
        $this->checkPeriodicityData('fac');
    }

    public function onLinkedCommandeFournLineChange($cf_line)
    {
        $this->checkPeriodicityData('achat');
    }

    public function checkReturnedEquipment($id_equipment)
    {
        $errors = array();

        $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
        $commande = $this->getParentInstance();

        if (!BimpObject::objectLoaded($commande)) {
            $errors[] = 'ID de la commande absent';
            return $errors;
        }

        if (!BimpObject::objectLoaded($equipment)) {
            $errors[] = 'L\'équipement d\'ID ' . $id_equipment . ' n\'existe pas';
        } else {
            $place = $equipment->getCurrentPlace();

            // Si Aucun emplacement : on considère que c'est OK
            if (BimpObject::objectLoaded($place)) {
                if ((int) $place->getData('type') !== BE_Place::BE_PLACE_SAV) {
                    if ((int) $place->getData('type') !== BE_Place::BE_PLACE_CLIENT ||
                            (int) $place->getData('id_client') !== (int) $commande->getData('fk_soc')) {
                        $errors[] = 'Emplacement actuel de l\'équipement "' . $equipment->getData('serial') . '" invalide';
                    }
                }
            }

            $equipment->isAvailable(0, $errors, array(), array('sav'));
        }

        return $errors;
    }

    public function addReturnedEquipments(&$warnings, $equipments)
    {
        $errors = array();

        if (!$this->isLoaded()) {
            $errors[] = 'ID de la ligne de commande absent';
            return $errors;
        }

        $commande = $this->getParentInstance();

        if (!BimpObject::objectLoaded($commande)) {
            $errors[] = 'ID de la commande client absent';
        } elseif (!isset($equipments) || empty($equipments)) {
            $errors[] = 'Aucun équipement sélectionné';
        } else {
            $equipments_returned = $this->getData('equipments_returned');

            if (is_null($equipments_returned) || !is_array($equipments_returned)) {
                $equipments_returned = array();
            }

            $max = abs($this->getFullQty()) - count($equipments_returned);

            if (!$max) {
                $errors[] = 'Tous les équipement à retournés ont déjà été ajoutés';
            } elseif (count($equipments) > $max) {
                $errors[] = 'Vous ne pouvez ajouter que ' . $max . ' équipement(s) à retourner';
            } else {
                foreach ($equipments as $equipment_data) {
                    if (!isset($equipment_data['id_equipment']) || !(int) $equipment_data['id_equipment']) {
                        continue;
                    }

                    if (!isset($equipment_data['id_entrepot']) || !(int) $equipment_data['id_entrepot']) {
                        $equipment_data['id_entrepot'] = (int) $commande->getData('entrepot');
                    }

                    $errors = $this->checkReturnedEquipment((int) $equipment_data['id_equipment']);

                    if (!count($errors)) {
                        $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $equipment_data['id_equipment']);

                        $eq_errors = $equipment->updateField('id_commande_line_return', (int) $this->id);
                        if (count($eq_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($eq_errors, 'Echec de la mise à jour de l\'équipement "' . $equipment->getData('serial') . '" - Cet équipement n\'a donc pas été ajouté à la liste des équipement retournés');
                        } else {
                            $equipments_returned[(int) $equipment->id] = (int) $equipment_data['id_entrepot'];
                        }
                    }
                }

                if (!count($errors)) {
                    $errors = $this->updateField('equipments_returned', $equipments_returned);
                }
            }
        }

        return $errors;
    }

    public function checkQties()
    {
        if ($this->isLoaded()) {
            $commande = $this->getParentInstance();

            if (!BimpObject::objectLoaded($commande) || (int) $commande->getData('logistique_status') === 6) {
                return;
            }

            $fullQty = (float) $this->getFullQty();
            if ($fullQty !== (float) $this->getData('qty_total')) {
                $this->updateField('qty_total', $fullQty, null, true);
            }

            if ((int) $this->getData('type') !== self::LINE_TEXT) {
                if ($commande->isLogistiqueActive()) {
                    $status_forced = $commande->getData('status_forced');

//                    $fullQty = abs($fullQty);
                    $shipments_qty = (float) $this->getShipmentsQty();

                    // Expéditions: 
                    if (isset($status_forced['shipment']) && (int) $status_forced['shipment'] && (int) $commande->getData('shipment_status') === 2) {
                        $shipped_qty = $shipments_qty;
                        $to_ship_qty = 0;
                    } else {
                        $shipped_qty = (float) $this->getShippedQty(null, true);
                        $to_ship_qty = $shipments_qty - $shipped_qty;
                    }

                    // Facturation: 
                    if (isset($status_forced['invoice']) && (int) $status_forced['invoice'] && (int) $commande->getData('invoice_status') === 2) {
                        $billed_qty = $fullQty;
                        $to_bill_qty = 0;
                    } else {
                        $billed_qty = (float) $this->getBilledQty();
                        $to_bill_qty = $fullQty - $billed_qty;
                    }

                    // Diff:
                    $qty_billed_not_shipped = $billed_qty - $shipped_qty;
                    $qty_shipped_not_billed = $shipped_qty - $billed_qty;
                    if ($fullQty == 0) {
                        $qty_billed_not_shipped = 0;
                        $qty_shipped_not_billed = 0;
                    } elseif ($fullQty < 0) {
                        if ($qty_billed_not_shipped > 0)
                            $qty_billed_not_shipped = 0;
                        else
                            $qty_shipped_not_billed = 0;
                    } else {
                        if ($qty_billed_not_shipped < 0)
                            $qty_billed_not_shipped = 0;
                        else
                            $qty_shipped_not_billed = 0;
                    }

                    if ($shipped_qty !== (float) $this->getData('qty_shipped')) {
                        $this->updateField('qty_shipped', $shipped_qty, null, true);
                    }

                    if ($to_ship_qty !== (float) $this->getData('qty_to_ship')) {
                        $this->updateField('qty_to_ship', $to_ship_qty, null, true);
                    }

                    if ($billed_qty !== (float) $this->getData('qty_billed')) {
                        $this->updateField('qty_billed', $billed_qty, null, true);
                    }

                    if ($to_bill_qty !== (float) $this->getData('qty_to_bill')) {
                        $this->updateField('qty_to_bill', $to_bill_qty, null, true);
                    }

                    if ((float) $qty_billed_not_shipped !== (float) $this->getData('qty_billed_not_shipped')) {
                        $this->updateField('qty_billed_not_shipped', $qty_billed_not_shipped, null, true);
                    }

                    if ((float) $qty_shipped_not_billed !== (float) $this->getData('qty_shipped_not_billed')) {
                        $this->updateField('qty_shipped_not_billed', (float) $qty_shipped_not_billed, null, true);
                    }
                }
            } else {
                if ((float) $this->qty && !(float) $this->pu_ht) {
                    $this->qty = 0;
                    $this->db->update('commandedet', array(
                        'qty' => 0
                            ), '`rowid` = ' . (int) $this->id);

                    if ((float) $this->getData('qty_total')) {
                        $this->updateField('qty_total', 0, null, true);
                    }

                    if ((float) $this->getData('qty_shipped')) {
                        $this->updateField('qty_shipped', 0, null, true);
                    }

                    if ((float) $this->getData('qty_to_ship')) {
                        $this->updateField('qty_to_ship', 0, null, true);
                    }

                    if ((float) $this->getData('qty_billed')) {
                        $this->updateField('qty_billed', 0, null, true);
                    }

                    if ((float) $this->getData('qty_to_bill')) {
                        $this->updateField('qty_to_bill', 0, null, true);
                    }
                }
            }
        }
    }

    // Actions:

    public function actionSaveShipments($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Enregistrement effectué avec succès';

        if (!isset($data['shipments']) || empty($data['shipments'])) {
            $errors[] = 'Aucune expédition à traiter';
        } else {
            $errors = $this->setShipmentsData($data['shipments'], $warnings, true);
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionSaveFactures($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Mise à jour effectuée avec succès';

        if (!isset($data['factures']) || empty($data['factures'])) {
            $errors[] = 'Aucune facture à traiter';
        } else {
            $errors = $this->setFacturesData($data['factures'], $warnings);
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionAddToCommandeFourn($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Ajouts à la commande fournisseur effectué avec succès';

        if (!$this->isLoaded()) {
            $errors[] = 'ID de la ligne de commande absent';
        } else {
            if ((int) $this->getData('type') !== self::LINE_PRODUCT) {
                $errors[] = 'Cette opération n\'est possible que pour les lignes de type "Produit"';
            } else {
                $product = $this->getProduct();
                if (!BimpObject::objectLoaded($product)) {
                    $errors[] = 'ID du produit absent';
                } else {
                    if ($product->isTypeProduct()) {
                        $max = $this->getReservationsQties(0);
                    } else {
                        $max = (float) $this->getFullQty();

                        // Recheche des qtés déjà commandées: 
                        $fournLines = BimpCache::getBimpObjectObjects('bimpcommercial', 'Bimp_CommandeFournLine', array(
                                    'linked_object_name' => 'commande_line',
                                    'linked_id_object'   => (int) $this->id
                        ));

                        foreach ($fournLines as $line) {
                            $max -= (float) $line->qty;
                        }
                    }
                }
            }


            $qty = isset($data['qty']) ? (float) $data['qty'] : 0;
            $id_commande_fourn = isset($data['id_commande_fourn']) ? $data['id_commande_fourn'] : 0;
            $commande = $this->getParentInstance();

            if ($id_commande_fourn !== 'new') {
                $id_commande_fourn = (int) $id_commande_fourn;
            }

            if (!$qty) {
                $errors[] = 'Veuillez saisir une quantité supérieure à 0';
            } elseif ($qty > $max) {
                $errors[] = 'Il ne reste que ' . $max . ' unité(s) à commander. Veuillez corriger les quantités';
            } elseif (!$id_commande_fourn) {
                $errors[] = 'Veuillez sélectionner une commande fournisseur';
            } elseif (!BimpObject::objectLoaded($commande)) {
                $errors[] = 'ID de la commande client absent';
            } else {
                $type_price = isset($data['type_price']) ? (int) $data['type_price'] : 1;
                $id_entrepot = isset($data['id_entrepot']) ? (int) $data['id_entrepot'] : (int) $commande->getData('entrepot');
                $pa_ht = 0;
                $tva_tx = 0;
                $ref_supplier = 0;
                $id_fourn = 0;
                $id_fourn_price = 0;

                switch ($type_price) {
                    case 1:
                        $id_fourn_price = isset($data['id_fourn_price']) ? (int) $data['id_fourn_price'] : 0;
                        if (!$id_fourn_price) {
                            $errors[] = 'Aucun prix fournisseur sélectionné';
                        } else {
                            $fourn_price = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ProductFournisseurPrice', $id_fourn_price);
                            if (!$fourn_price->isLoaded()) {
                                $errors[] = 'Le prix fournisseur d\'ID ' . $id_fourn_price . ' n\'existe pas';
                            } else {
                                $id_fourn = (int) $fourn_price->getData('fk_soc');
                                if (!$id_fourn) {
                                    $errors[] = 'Aucun fournisseur associé au prix d\'achat sélectionné';
                                } else {
                                    $pa_ht = (float) $fourn_price->getData('price');
                                    $tva_tx = (float) $fourn_price->getData('tva_tx');
                                    $ref_supplier = $fourn_price->getData('ref_fourn');
                                }
                            }
                        }
                        break;

                    case 2:
                        $id_fourn = isset($data['id_fourn']) ? (int) $data['id_fourn'] : 0;
                        if (!$id_fourn) {
                            $errors[] = 'Veuillez sélectionner un fournisseur';
                        } else {
                            $pa_ht = isset($data['pa_ht']) ? (float) $data['pa_ht'] : 0;
                            $tva_tx = isset($data['tva_tx']) ? (float) $data['tva_tx'] : 0;

                            if (!$pa_ht) {
                                $errors[] = 'Veuillez saisir un prix d\'achat supérieur à 0';
                            }
                            if (!$tva_tx) {
                                $errors[] = 'Veuillez saisir un taux de TVA supérieur à 0';
                            }
                        }
                        break;
                }

                // Création de la commande fourn : 
                $commande_fourn = null;

                if ($id_commande_fourn == 'new' || !(int) $id_commande_fourn) {
                    $commande_fourn = BimpObject::createBimpObject('bimpcommercial', 'Bimp_CommandeFourn', array(
                                'entrepot' => $id_entrepot,
                                'ef_type'  => $commande->getData('ef_type'),
                                'fk_soc'   => $id_fourn
                                    ), true, $errors, $warnings);
                } else {
                    $commande_fourn = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', (int) $id_commande_fourn);
                    if (!BimpObject::objectLoaded($commande_fourn)) {
                        $errors[] = 'La commande fournisseur d\'ID ' . $id_commande_fourn . ' n\'existe pas';
                    } else {
                        if ((int) $commande_fourn->getData('fk_statut') !== 0) {
                            $errors[] = 'La commande fournisseur sélectionnée n\'est plus modifiable';
                        }
                    }
                }

                if (!count($errors)) {
                    // Ajout de la ligne à la CF: 
                    $line_warnings = array();
                    $line_errors = $this->addToCommandeFourn($commande_fourn->id, $qty, array(
                        'id_fourn_price' => $id_fourn_price,
                        'ref_supplier'   => $ref_supplier,
                        'pu_ht'          => $pa_ht,
                        'tva_tx'         => $tva_tx
                            ), $line_warnings);

                    if (count($line_warnings)) {
                        $warnings[] = BimpTools::getMsgFromArray($line_warnings, 'Erreur suite à l\'ajout de la ligne à la commande fournisseur');
                    }

                    if (count($line_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de l\'ajout de la ligne à la commande fournisseur');
                    }
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionCancelCommandeFourn($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Suppression de la ligne de commande fournisseur effectuée avec succès';

        if (!isset($data['id_commande_fourn_line']) || !(int) $data['id_commande_fourn_line']) {
            $errors[] = 'ID de la commande fournisseur absent';
        } else {
            $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFournLine', (int) $data['id_commande_fourn_line']);
            if (!BimpObject::objectLoaded($line)) {
                $errors[] = 'La ligne de commande fournisseur d\'ID ' . $data['id_commande_fourn_line'] . ' n\'existe pas';
            } else {
                $commande_fourn = $line->getParentInstance();
                if (!BimpObject::objectLoaded($commande_fourn)) {
                    $errors[] = 'Commande fournisseur non trouvée';
                } elseif ((int) $commande_fourn->getData('fk_statut') !== 0) {
                    $errors[] = 'La commande fournisseur "' . $commande_fourn->getRef() . '" n\'a plus le statut "brouillon"';
                } else {
                    $qty = (int) $line->qty;
                    $errors = $line->delete($warnings, true);

                    if (!count($errors)) {
                        $remain_qty = $qty;

                        $reservations = $this->getReservations('status', 'asc', 100);
                        foreach ($reservations as $reservation) {
                            $res_qty = (int) $reservation->getData('qty');
                            if ($remain_qty > $res_qty) {
                                $remain_qty -= $res_qty;
                            } else {
                                $res_qty = $remain_qty;
                                $remain_qty = 0;
                            }

                            $res_errors = $reservation->setNewStatus(0, array('qty' => $res_qty));

                            if (count($res_errors)) {
                                $warnings[] = BimpTools::getMsgFromArray($res_errors, 'Echec de la mise à jour du statut des produits pour ' . $res_qty . ' unité(s)');
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

    public function actionModifyQty($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Mise à jour des quantités effectuée avec succès';

        $commande = $this->getParentInstance();

        if (!isset($data['qty_modified'])) {
            $errors[] = 'Nouvelles quantités de la ligne de commande absentes';
        } else {
            if ((float) $this->getFullQty() >= 0) {
                $min = (float) $this->getMinQty();
                if ((float) $data['qty_modified'] < $min) {
                    $msg = '';
                    if ($min > 1) {
                        $msg .= $min . ' unités ont déjà été attribuées à une expédition ou une facture ';
                    } else {
                        $msg .= $min . ' unité a déjà ajoutée à une expédition ou une facture';
                    }

                    $msg .= '<br/>Veuillez indiquer une quantité supérieure ou égale à ' . $min;
                    $errors[] = $msg;
                } else {
                    $product = $this->getProduct();
                    $isProduct = (BimpObject::objectLoaded($product) && (int) $product->getData('fk_product_type') === 0);
                    $diff = (float) $data['qty_modified'] - ((float) $this->qty + (float) $this->getInitData('qty_modif'));

                    if ($isProduct) {
                        if ($diff < 0) {
                            $res_qties = $this->getReservationsQties(0);
                            $res_qties += $this->getReservationsQties(303);

                            if ($res_qties < abs($diff)) {
                                if ($res_qties > 1) {
                                    $msg = 'Seules ' . $res_qties . ' unité sont';
                                } elseif ($res_qties) {
                                    $msg = 'Seule ' . $res_qties . ' unité est';
                                } else {
                                    $msg = 'Il n\'y a aucune unité';
                                }

                                $msg .= ' au statut "A traiter".';

                                if ($res_qties) {
                                    $msg .= '<br/>Vous ne pouvez retirer que ' . $res_qties . ' maximum';
                                }

                                $errors[] = $msg;
                            }
                        }
                    }

                    if (!count($errors)) {
                        $qty_modified = (float) $data['qty_modified'] - (float) $this->qty;
                        $this->set('qty_modif', $qty_modified);
                        $errors = $this->validate();

                        if (!count($errors)) {
                            $errors = $this->updateField('qty_modif', $qty_modified);

                            if (!count($errors)) {
                                if ((float) $this->qty == 0) {
                                    $this->calcRemise();
                                }

                                if ($isProduct) {
                                    $this->checkReservations();
                                }

                                if (BimpObject::objectLoaded($commande)) {
                                    $log = ($diff >= 0 ? 'Ajout' : 'Retrait') . ' de ' . abs($diff) . ' unité(s) en logistique (Ligne n°' . $this->getData('position') . ')';
                                    $commande->addLog($log);
                                }
                            }
                        }
                    }
                }
            } else {
                $max = $this->getMinQty();
                if ((float) $data['qty_modified'] > $max) {
                    $errors[] = 'Veuillez indiquer une quantité inférieure ou égale à ' . $max;
                } else {
                    $diff = (float) $data['qty_modified'] - ((float) $this->qty + (float) $this->getInitData('qty_modif'));
                    $qty_modified = (float) $data['qty_modified'] - (float) $this->qty;

                    $this->set('qty_modif', $qty_modified);
                    $errors = $this->validate();

                    if (!count($errors)) {
                        $errors = $this->updateField('qty_modif', $qty_modified);

                        if (!count($errors)) {
                            if (BimpObject::objectLoaded($commande)) {
                                $log = ($diff >= 0 ? 'Ajout' : 'Retrait') . ' de ' . abs($diff) . ' unité(s) en logistique (Ligne n°' . $this->getData('position') . ')';
                                $commande->addLog($log);
                            }
                        }
                    }
                }
            }

            if (BimpObject::objectLoaded($commande)) {
                $commande->checkShipmentStatus();
                $commande->checkInvoiceStatus();
                $commande->checkLogistiqueStatus();
            }
        }

        $this->checkQties();

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionAddReturnedEquipments($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Equipements enregistrés avec succès';

        if (!isset($data['equipments']) || empty($data['equipments'])) {
            $errors[] = 'Aucun équipement sélectionné';
        } else {
            $equipments = array();
            foreach ($data['equipments'] as $id_equipment) {
                $equipments[] = array(
                    'id_equipment' => (int) $id_equipment,
                    'id_entrepot'  => 0
                );
            }
            $errors = $this->addReturnedEquipments($warnings, $equipments);
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionRemoveReturnedEquipments($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        if (!$this->isLoaded()) {
            $errors[] = 'ID de la ligne de commande absent';
        } elseif (!isset($data['equipments'])) {
            $errors[] = 'Aucun équipement spécifié';
        } else {
            $equipments = $this->getData('equipments_returned');
            $removed = array();
            foreach (explode(',', $data['equipments']) as $id_equipment) {
                $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                if (!BimpObject::objectLoaded($equipment)) {
                    $warnings[] = 'L\'équipement d\'ID ' . $id_equipment . ' n\'existe pas';
                    $removed[] = $id_equipment;
                    continue;
                }
                if (!in_array($id_equipment, $equipments)) {
                    $warnings[] = 'L\'équipement "' . $equipment->getData('serial') . '" ne fait pas partie de la liste des équipement retournés';
                    $removed[] = $id_equipment;
                    continue;
                }

                $id_shipment = (int) $this->getEquipmentIdShipment($id_equipment);
                $shipment = null;
                if ($id_shipment) {
                    $shipment = BimpCache::getBimpObjectFullListArray('bimplogistique', 'BL_CommandeShipment', $id_shipment);
                    if (BimpObject::objectLoaded($shipment)) {
                        if ((int) $shipment->getData('status') > 0) {
                            $warnings[] = 'L\'équipement "' . $equipment->getData('serial') . '" ne peux pas être retiré car il a été attribué à une expédition validée';
                            continue;
                        }
                    }
                }

                $id_facture = (int) $this->getEquipmentIdFacture($id_equipment);
                $facture = null;
                if ($id_facture) {
                    if ((int) $id_facture === -1) {
                        $warnings[] = 'L\'équipement "' . $equipment->getData('serial') . '" ne peux pas être retiré car il a été facturé (hors BIMP-ERP)';
                    } else {
                        $facture = BimpCache::getBimpObjectFullListArray('bimpcommercial', 'Bimp_Facture', (int) $id_facture);
                        if (BimpObject::objectLoaded($facture)) {
                            if ((int) $facture->getData('fk_statut') > 0) {
                                $warnings[] = 'L\'équipement "' . $equipment->getData('serial') . '" ne peux pas être retiré car il a été attribué à une facture validée';
                                continue;
                            }
                        }
                    }
                }

                $eq_errors = $equipment->updateField('id_commande_line_return', 0);
                if (count($eq_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($eq_errors, 'Equipement "' . $equipment->getData('serial') . '"');
                } else {
                    $removed[] = $id_equipment;

                    if (BimpObject::ObjectLoaded($facture)) {
                        $fac_line = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_FactureLine', array(
                                    'id_obj'             => (int) $facture->id,
                                    'linked_object_name' => 'commande_line',
                                    'linked_id_object'   => (int) $this->id
                                        ), true);

                        if (BimpObject::objectLoaded($fac_line)) {
                            $fac_errors = $fac_line->removeEquipment($id_equipment);
                            if (count($fac_errors)) {
                                $warnings[] = BimpTools::getMsgFromArray($fac_errors, 'Echec du retrait de l\'équipement "' . $equipment->getData('serial') . '" de la facture "' . $facture->getRef() . '"');
                            }
                        }
                    }
                }
            }

            if (!empty($removed)) {
                if (count($removed) > 1) {
                    $success = count($equipment) . ' retirés avec succès';
                } else {
                    $success = 'Un équipement retiré avec succès';
                }

                // Retrait des expéditions: 
                $shipments = $this->getData('shipments');
                foreach ($shipments as $id_shipment => $shipment_data) {
                    if (isset($shipment_data['equipments']) && !empty($shipment_data['equipments'])) {
                        $shipment_equipments = array();
                        foreach ($shipment_data['equipments'] as $id_equipment) {
                            if (!in_array((int) $id_equipment, $removed)) {
                                $shipment_equipments[] = (int) $id_equipment;
                            }
                        }
                        $shipments[(int) $id_shipment]['equipments'] = $shipment_equipments;
                    }
                }

                $up_errors = $this->updateField('shipments', $shipments);
                if (count($up_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($up_errors);
                }

                // Retrait des factures: 
                $factures = $this->getData('factures');

                foreach ($factures as $id_facture => $facture_data) {
                    if (isset($facture_data['equipments']) && !empty($facture_data['equipments'])) {
                        $fac_equipments = array();
                        foreach ($facture_data['equipments'] as $id_equipment) {
                            if (!in_array((int) $id_equipment, $removed)) {
                                $fac_equipments[] = (int) $id_equipment;
                            }
                        }
                        $factures[(int) $id_facture]['equipments'] = $fac_equipments;
                    }
                }

                $up_errors = $this->updateField('factures', $factures);
                if (count($up_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($up_errors);
                }

                $new_equipments = array();
                foreach ($equipments as $id_equipment => $id_entrepot) {
                    if (!in_array((int) $id_equipment, $removed)) {
                        $new_equipments[(int) $id_equipment] = (int) $id_entrepot;
                    }
                }

                $up_errors = $this->updateField('equipments_returned', $new_equipments);
                if (count($up_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($up_errors);
                }
            } elseif (empty($errors)) {
                $errors[] = 'Aucun équipement retiré';
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionSaveReturnedEquipmentEntrepot($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Entrepôt enregistré';

        $id_entrepot = (isset($data['id_entrepot']) ? (int) $data['id_entrepot'] : 0);
        $id_equipment = (isset($data['id_equipment']) ? (int) $data['id_equipment'] : 0);

        if (!$id_entrepot) {
            $errors[] = 'Aucun entrepôt sélectionné';
        }

        if (!$id_equipment) {
            $errors[] = 'ID de l\'équipement absent';
        }

        if (!count($errors)) {
            $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $id_equipment);
            if (!BimpObject::objectLoaded($equipment)) {
                $errors[] = 'L\'équipement d\'ID ' . $id_equipment . ' n\'existe plus';
            } else {
                $equipments_returned = $this->getData('equipments_returned');
                if (!array_key_exists($id_equipment, $equipments_returned)) {
                    $errors[] = 'L\'équipement "' . $equipment->getData('serial') . '" ne fait pas partie de la liste des équipements à retourner pour cette ligne de commande';
                }
            }

            if (!count($errors)) {
                $id_shipment = (int) $this->getEquipmentIdShipment($id_equipment);
                if ($id_shipment) {
                    $shipment = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeShipment', $id_shipment);
                    if (BimpObject::objectLoaded($shipment)) {
                        if ((int) $shipment->getData('status') !== BL_CommandeShipment::BLCS_BROUILLON) {
                            $errors[] = 'Impossible de modifier l\'entrepôt de destination: cet équipement a été ajouté à une expédition qui n\'est plus au statut "brouillon"';
                        }
                    }
                }

                $id_facture = (int) $this->getEquipmentIdFacture($id_equipment);
                if ($id_facture) {
                    if ((int) $id_facture === -1) {
                        $errors[] = 'Impossible de modifier l\'entrepôt de destination: cet équipement a été facturé (hors BIMP-ERP)';
                    } else {
                        $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_facture);
                        if (BimpObject::objectLoaded($facture)) {
                            if ((int) $facture->getData('fk_statut') > 0) {
                                $errors[] = 'Impossible de modifier l\'entrepôt de destination: cet équipement a été ajouté à une facture qui n\'est plus au statut "brouillon"';
                            }
                        }
                    }
                }
            }

            if (!count($errors)) {
                $equipments_returned[$id_equipment] = $id_entrepot;
                $errors = $this->updateField('equipments_returned', $equipments_returned);
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionAddReturnsFromLines($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $lines = isset($data['lines']) ? $data['lines'] : array();

        if (!is_array($lines) || empty($lines)) {
            $errors[] = 'Aucune ligne de commande sélectionnée';
        }

        if (!count($errors)) {
            foreach ($lines as $id_line => $line_data) {
                $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', $id_line);
                if (!BimpObject::ObjectLoaded($line)) {
                    $warnings[] = 'La ligne de commande client d\'ID ' . $id_line . ' n\'existe pas';
                    continue;
                }

                if ($line->isProductSerialisable()) {
                    if (!isset($line_data['equipments']) || empty($line_data['equipments'])) {
                        continue;
                    }
                } else {
                    if (!isset($line_data['qty']) || !(float) $line_data['qty']) {
                        continue;
                    }
                }

                $commande = $line->getParentInstance();

                if (!BimpObject::ObjectLoaded($commande)) {
                    $warnings[] = 'ID de la commande absent pour la ligne n°' . $line->getData('position');
                    continue;
                }

                $line_warnings = array();
                $line_errors = $commande->addReturnFromLine($line_warnings, $id_line, $line_data);
                if (count($line_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($line_errors, 'Ligne n°' . $line->getData('position'));
                }
                if (count($line_warnings)) {
                    $warnings[] = BimpTools::getMsgFromArray($line_warnings, 'Ligne n°' . $line->getData('position'));
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionRemoveAcompte($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Acompte retiré avec succès';

        $id_line = (int) $this->getData('id_line');

        if (!$id_line) {
            $errors[] = 'ID de la ligne de commande absent';
        } else {
            $qty_modif = (float) $this->getData('qty_modif');
            $qty_modif--;

            if (!(float) $this->qty && !$qty_modif && (int) $this->getData('id_line') && $this->isLoaded()) {
                $this->db->delete('commandedet', 'rowid = ' . (int) $this->getData('id_line'));
                $this->db->delete('bimp_commande_line', 'id = ' . (int) $this->id);
            } else {
                $this->db->update('commandedet', array(
                    'fk_remise_except' => 0
                        ), '`rowid` = ' . (int) $id_line);

                $this->set('qty_modif', $qty_modif);
                if ($this->update($warnings, true) <= 0) {
                    $msg = 'Echec du retrait de l\'acompte';
                    $sqlError = $this->db->db->lasterror();
                    if ($sqlError) {
                        $msg .= ' - ' . $sqlError;
                    }

                    $errors[] = $msg;
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionSetPeriodicity($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $ids_lines = BimpTools::getArrayValueFromPath($data, 'id_objects', array());

        if (empty($ids_lines)) {
            $errors[] = 'Aucune ligne de commande sélectionnée';
        } else {
            $fac_periodicity = 0;
            $fac_nb_periods = 0;
            $fac_periods_start = null;

            $exp_periodicity = 0;
            $exp_nb_periods = 0;
            $exp_periods_start = null;

            $achat_periodicity = 0;
            $achat_nb_periods = 0;
            $achat_periods_start = null;

            $has_fac_periodicity = BimpTools::getArrayValueFromPath($data, 'has_fac_periodicity', 0);

            if ($has_fac_periodicity) {
                $fac_periodicity = (int) BimpTools::getArrayValueFromPath($data, 'fac_periodicity', 0);
                $fac_nb_periods = (int) BimpTools::getArrayValueFromPath($data, 'fac_nb_periods', 0);
                $fac_periods_start = BimpTools::getArrayValueFromPath($data, 'fac_periods_start', null);
                $fact_echue = BimpTools::getArrayValueFromPath($data, 'fact_echue', 0);

                if (!$fac_periodicity) {
                    $errors[] = 'Veuillez sélectionner une périodicité de facturation';
                }

                if (!$fac_nb_periods) {
                    $errors[] = 'Veuillez sélectionner un nombre de périodes à facturer supérieur à 0';
                }
            }

            $has_exp_periodicity = BimpTools::getArrayValueFromPath($data, 'has_exp_periodicity', 0);

            if ($has_exp_periodicity) {
                if ((int) BimpTools::getArrayValueFromPath($data, 'exp_periodicity_same_values', 0)) {
                    if (!count($errors)) {
                        $exp_periodicity = $fac_periodicity;
                        $exp_nb_periods = $fac_nb_periods;
                        $exp_periods_start = $fac_periods_start;
                    }
                } else {
                    $exp_periodicity = BimpTools::getArrayValueFromPath($data, 'exp_periodicity', 0);
                    $exp_nb_periods = BimpTools::getArrayValueFromPath($data, 'exp_nb_periods', 0);
                    $exp_periods_start = BimpTools::getArrayValueFromPath($data, 'exp_periods_start', null);

                    if (!$exp_periodicity) {
                        $errors[] = 'Veuillez sélectionner une périodicité de livraison';
                    }

                    if (!$exp_nb_periods) {
                        $errors[] = 'Veuillez sélectionner un nombre de périodes de livraison supérieur à 0';
                    }
                }
            }

            $has_achat_periodicity = BimpTools::getArrayValueFromPath($data, 'has_achat_periodicity', 0);

            if ($has_achat_periodicity) {
                $params_mode = BimpTools::getArrayValueFromPath($data, 'achat_periodicity_same_values', 'custom');

                switch ($params_mode) {
                    case 'custom':
                        $achat_periodicity = BimpTools::getArrayValueFromPath($data, 'achat_periodicity', 0);
                        $achat_nb_periods = BimpTools::getArrayValueFromPath($data, 'achat_nb_periods', 0);
                        $achat_periods_start = BimpTools::getArrayValueFromPath($data, 'achat_periods_start', null);
                        break;

                    case 'as_fac':
                        $achat_periodicity = $fac_periodicity;
                        $achat_nb_periods = $fac_nb_periods;
                        $achat_periods_start = $fac_periods_start;
                        break;

                    case 'as_exp':
                        $achat_periodicity = $exp_periodicity;
                        $achat_nb_periods = $exp_nb_periods;
                        $achat_periods_start = $exp_periods_start;
                        break;
                }

                if (!$achat_periodicity) {
                    $errors[] = 'Veuillez sélectionner une périodicité d\'achat';
                }

                if (!$achat_nb_periods) {
                    $errors[] = 'Veuillez sélectionner un nombre de périodes d\'achat supérieur à 0';
                }
            }
        }

        $nOk = 0;

        if (!count($errors)) {
            foreach ($ids_lines as $id_line) {
                $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $id_line);

                if (BimpObject::objectLoaded($line)) {
                    $line_err = array();
                    $line_warnings = array();
                    if ($line->isPeriodicityAllowed($line_err)) {
                        $line_data = array();

                        if (!(float) $this->getBilledQty()) {
                            $line_data['fac_periodicity'] = $fac_periodicity;
                            $line_data['fac_nb_periods'] = $fac_nb_periods;
                            $line_data['fac_periods_start'] = $fac_periods_start;
                            $line_data['fact_echue'] = $fact_echue;
                        } else {
                            $line_err[] = 'Une facturation a déjà eut lieu. Les paramètres de facturation périodique ne peuvent pas être modifiés';
                        }

                        if (!(float) $line->getShippedQty()) {
                            $line_data['exp_periodicity'] = $exp_periodicity;
                            $line_data['exp_nb_periods'] = $exp_nb_periods;
                            $line_data['exp_periods_start'] = $exp_periods_start;

                            $line_product = $line->getProduct();

                            if (BimpObject::objectLoaded($line_product) && $line_product->isTypeProduct()) {
                                $line_qty = $line->getFullQty();

                                if ($line_qty < $exp_nb_periods) {
                                    // Ajustement du nombres de périodes: 
                                    $line_data['exp_nb_periods'] = $line_qty;
                                    $line_warnings[] = 'Nombre de périodes réduites à ' . $line_qty;
                                } elseif ($exp_nb_periods > $line_qty) {
                                    $period_unit = $line_qty / $exp_nb_periods;
                                    if (round($period_unit) != $period_unit) {
                                        // Tentative d'ajustement de la périodicité: 
                                        if ($exp_periodicity > 1) {
                                            $nb_month = $exp_periodicity * $exp_nb_periods;

                                            $min_periodicity = $nb_month / $line_qty;

                                            // Si la nouvelle périodicité tombe juste: 
                                            if (round($min_periodicity) == $min_periodicity) {
                                                $new_nb_periods = $nb_month / $min_periodicity;

                                                // Si le nouveau nombre de périodes tombe juste: 
                                                if (round($new_nb_periods) == $new_nb_periods) {
                                                    $line_data['exp_periodicity'] = $min_periodicity;
                                                    $line_data['exp_nb_periods'] = $new_nb_periods;
                                                    $line_warnings[] = 'Paramètres de livraison périodique ajustés';
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            $line_err[] = 'Une facturation a déjà eut lieu. Les paramètres de livraison périodique ne peuvent pas être modifiés';
                        }

                        if (!(float) $line->getBoughtQty()) {
                            $line_data['achat_periodicity'] = $achat_periodicity;
                            $line_data['achat_nb_periods'] = $achat_nb_periods;
                            $line_data['achat_periods_start'] = $achat_periods_start;
                        } else {
                            $line_err[] = 'Un achat a déjà eut lieu. Les paramètres d\'achat périodique ne peuvent pas être modifiés';
                        }

                        if (count($line_warnings)) {
                            $warnings[] = BimpTools::getMsgFromArray($line_warnings, 'Ligne n°' . $line->getData('position'));
                        }

                        if (empty($line_err) && !empty($line_data)) {
                            $line_err = $line->validateArray($line_data);
                            if (empty($line_err)) {
                                $line_warnings = array();
                                $line_err = $line->update($line_warnings, true);
                            }
                        }
                    }

                    if (!empty($line_err)) {
                        $warnings[] = BimpTools::getMsgFromArray($line_err, 'Ligne n° ' . $line->getData('position'));
                    } else {
                        $nOk++;
                    }
                } else {
                    $warnings[] = 'La ligne d\'ID ' . $id_line . ' n\'existe pas';
                }
            }
        }

        if ($nOk) {
            $success = $nOk . ' ligne(s) mise(s) à jour avec succès';
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionCheckPeriodicityData($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Vérification effectuée';

        if ($this->isLoaded($errors)) {
            $infos = array();
            $errors = $this->checkPeriodicityData('all', $infos);
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings,
            'infos'    => $infos
        );
    }

    // Actions BDS : 

    public function initBdsActionPeriodicityMassProcess($process, &$action_data = array(), &$errors = array(), $extra_data = array())
    {
        $use_db_transactions = (int) BimpCore::getConf('use_db_transactions');
        if ($use_db_transactions) {
            $this->db->db->begin();
        }

        $operation_type = BimpTools::getArrayValueFromPath($extra_data, 'operation_type', '');

        if (!in_array($operation_type, array('exp', 'fac', 'achat'))) {
            $errors[] = 'Type d\'opération périodique invalide';
        } else {
            switch ($operation_type) {
                case 'exp':
                    $action_data['operation_title'] = 'Traitement en masse des livraisons périodiques';
                    $action_data['report_code'] = 'PERIODIC_MASS_PROCESS_EXP';

                    // Check des expés:
                    $commandes = BimpTools::getArrayValueFromPath($extra_data, 'commandes', array());
                    if (empty($commandes)) {
                        $errors[] = 'Aucune commande client spécifiée';
                    } else {
                        $process->setCurrentObjectData('bimplogistique', 'BL_CommandeShipment');

                        foreach ($commandes as $id_commande => $contacts) {
                            if (!$id_commande || empty($contacts)) {
                                continue;
                            }

                            $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $id_commande);
                            if (!BimpObject::objectLoaded($commande)) {
                                $errors[] = 'Le commande client #' . $id_commande . ' n\'existe plus';
                            } else {
                                foreach ($contacts as $id_contact => $contact_data) {
                                    $lines = BimpTools::getArrayValueFromPath($contact_data, 'lines', array());
                                    if (empty($lines)) {
                                        continue;
                                    }

                                    $id_shipment = (int) BimpTools::getArrayValueFromPath($contact_data, 'id_shipment', 0);
                                    if ((int) $id_shipment > 0) {
                                        $shipment = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeShipment', $id_shipment);
                                        if (!BimpObject::objectLoaded($shipment)) {
                                            $errors[] = 'L\'expédition #' . $id_shipment . ' n\'existe plus';
                                        } else {
                                            if ((int) $shipment->getData('status') != 1) {
                                                $errors[] = 'L\'expédition n° ' . $shipment->getData('num_livraison') . ' pour la commande ' . $commande->getRef() . ' n\'est plus au statut brouillon';
                                            }
                                        }
                                    } else {
                                        // Création de l'expédition : 
                                        $shipment_errors = array();
                                        $shipment = BimpObject::createBimpObject('bimplogistique', 'BL_CommandeShipment', array(
                                                    'id_commande_client' => $id_commande,
                                                    'id_contact'         => $id_contact,
                                                    'id_entrepot'        => $commande->getData('entrepot'),
                                                    'ref'                => BimpTools::getArrayValueFromPath($contact_data, 'ref_shipment', '')
                                                        ), true, $shipment_errors);

                                        if (count($shipment_errors)) {
                                            $msg = BimpTools::getMsgFromArray($shipment_errors, 'Echec de la création de l\'expédition pour la commande "' . $commande->getRef() . '"');
                                            $errors[] = $msg;
                                            $process->Error($msg, $commande);
                                        } else {
                                            $commandes[$id_commande][$id_contact]['id_shipment'] = (int) $shipment->id;
                                            $contact = null;
                                            if ($id_contact) {
                                                $contact = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $id_contact);
                                            }
                                            $process->Success('Création de l\'expédition pour la commande "' . $commande->getRef() . '" ' . (BimpObject::objectLoaded($contact) ? ' (Contact : ' . $contact->getName() . ')' : '') . 'OK', $commande);
                                            $process->incCreated();
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if (!count($errors)) {
                        // Ajout des lignes:

                        $action_data['steps'] = array();
                        $shipments = array();
                        foreach ($commandes as $id_commande => $contacts) {
                            foreach ($contacts as $id_contact => $contact_data) {
                                $lines = BimpTools::getArrayValueFromPath($contact_data, 'lines', array());
                                $id_shipment = (int) BimpTools::getArrayValueFromPath($contact_data, 'id_shipment', 0);

                                if (!$id_commande || !$id_shipment || empty($lines)) {
                                    continue;
                                }

                                $elements = array();

                                foreach ($lines as $line_data) {
                                    $elements[] = json_encode(array(
                                        'id_line' => (int) BimpTools::getArrayValueFromPath($line_data, 'id_line', 0),
                                        'qty'     => (int) BimpTools::getArrayValueFromPath($line_data, 'qty', 0)
                                    ));
                                }

                                if (!empty($elements)) {
                                    if (!in_array($id_shipment, $shipments)) {
                                        $shipments[] = $id_shipment;
                                    }

                                    $action_data['steps']['process_shipment_' . $id_shipment . '_lines'] = array(
                                        'label'                  => 'Ajout des lignes à la l\'expédition #' . $id_shipment,
                                        'on_error'               => 'continue',
                                        'elements'               => $elements,
                                        'nbElementsPerIteration' => 10
                                    );
                                }
                            }
                        }

                        if (!empty($shipments)) {
                            $action_data['steps']['finalize_exps'] = array(
                                'label'                  => 'Vérifications et finalisation',
                                'on_error'               => 'stop',
                                'elements'               => $shipments,
                                'nbElementsPerIteration' => 100
                            );
                        }
                    }
                    break;

                case 'fac':
                    $action_data['operation_title'] = 'Traitement en masse des facturations périodiques';
                    $action_data['report_code'] = 'PERIODIC_MASS_PROCESS_FAC';
                    $facs_lines = array();

                    // Check des factures:
                    $clients = BimpTools::getArrayValueFromPath($extra_data, 'clients', array());
                    if (empty($clients)) {
                        $errors[] = 'Aucun client spécifié';
                    } else {
                        $process->setCurrentObjectData('bimpcommercial', 'Bimp_Facture');

                        foreach ($clients as $id_client => $client_facs) {
                            if (!$id_client || empty($client_facs)) {
                                continue;
                            }

                            $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $id_client);
                            if (!BimpObject::objectLoaded($client)) {
                                $errors[] = 'Le client #' . $id_client . ' n\'existe plus';
                            } else {
                                foreach ($client_facs as $fac_idx => $fac_data) {
                                    $lines = BimpTools::getArrayValueFromPath($fac_data, 'lines', array());
                                    if (empty($lines)) {
                                        continue;
                                    }

                                    $id_facture = (int) BimpTools::getArrayValueFromPath($fac_data, 'id_facture', 0);
                                    if ((int) $id_facture > 0) {
                                        $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_facture);
                                        if (!BimpObject::objectLoaded($facture)) {
                                            $errors[] = 'La facture #' . $id_facture . ' n\'existe plus';
                                            continue;
                                        } else {
                                            if ((int) $facture->getData('fk_statut') != 0) {
                                                $errors[] = 'La facture n° ' . $facture->getRef() . ' pour le client ' . $client->getRef() . ' ' . $client->getName() . ' n\'est plus au statut brouillon';
                                                continue;
                                            }
                                        }
                                    } else {
                                        // Création de la facture :
                                        $fac_errors = array();
                                        $id_entrepot = (int) BimpTools::getArrayValueFromPath($fac_data, 'id_entrepot', 0, $fac_errors, true, 'Facture n° ' . $fac_idx + 1 . ' pour le client ' . $client->getName() . ': entrepôt absent');
                                        $secteur = BimpTools::getArrayValueFromPath($fac_data, 'secteur', '', $fac_errors, true, 'Facture n° ' . $fac_idx + 1 . ' pour le client ' . $client->getName() . ': secteur absent');
                                        $id_mode_reglement = (int) BimpTools::getArrayValueFromPath($fac_data, 'id_mode_reglement', 0, $fac_errors, true, 'Facture n° ' . $fac_idx + 1 . ' pour le client ' . $client->getName() . ': mode de réglement absent');
                                        $id_cond_reglement = (int) BimpTools::getArrayValueFromPath($fac_data, 'id_cond_reglement', 0, $fac_errors, true, 'Facture n° ' . $fac_idx + 1 . ' pour le client ' . $client->getName() . ': conditions de réglement absentes');
                                        $libelle = BimpTools::getArrayValueFromPath($fac_data, 'libelle', 'Facturation périodique');

                                        if (!count($fac_errors)) {
                                            $fac = BimpObject::createBimpObject('bimpcommercial', 'Bimp_Facture', array(
                                                        'fk_soc'            => $id_client,
                                                        'entrepot'          => $id_entrepot,
                                                        'ef_type'           => $secteur,
                                                        'libelle'           => $libelle,
                                                        'fk_mode_reglement' => $id_mode_reglement,
                                                        'fk_cond_reglement' => $id_cond_reglement,
                                                        'datef'             => date('Y-m-d')
                                                            ), true, $fac_errors);
                                        }

                                        if (count($fac_errors)) {
                                            $msg = BimpTools::getMsgFromArray($fac_errors, 'Echec de la création de la facture n° ' . ($fac_idx + 1) . ' pour le client "' . $client->getRef() . ' - ' . $client->getName() . '"');
                                            $errors[] = $msg;
                                            $process->Error($msg, $client);
                                            continue;
                                        } else {
                                            $process->Success('Création de la facture n° ' . ($fac_idx + 1) . ' pour le client "' . $client->getRef() . ' - ' . $client->getName() . '" OK (Facture : ' . $fac->getLink() . ')', $client);
                                            $process->incCreated();
                                            $id_facture = $fac->id;
                                        }
                                    }

                                    if ($id_facture) {
                                        $facs_lines[$id_facture] = $fac_data['lines'];
                                    }
                                }
                            }
                        }
                    }

                    if (!count($errors)) {
                        // Ajout des lignes:

                        $action_data['steps'] = array();
                        $factures = array();
                        foreach ($facs_lines as $id_facture => $fac_lines) {
                            if (empty($fac_lines)) {
                                continue;
                            }

                            $elements = array();

                            foreach ($fac_lines as $line_data) {
                                $elements[] = json_encode(array(
                                    'id_line' => (int) BimpTools::getArrayValueFromPath($line_data, 'id_line', 0),
                                    'qty'     => (int) BimpTools::getArrayValueFromPath($line_data, 'qty', 0)
                                ));
                            }

                            if (!empty($elements)) {
                                $factures[] = $id_facture;
                                $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_facture);
                                $action_data['steps']['process_facture_' . $id_facture . '_lines'] = array(
                                    'label'                  => 'Ajout des lignes à la la facture ' . $fac->getRef(),
                                    'on_error'               => 'continue',
                                    'elements'               => $elements,
                                    'nbElementsPerIteration' => 10
                                );
                            }
                        }

                        if (!empty($factures)) {
                            $action_data['steps']['finalize_facs'] = array(
                                'label'                  => 'Vérifications et finalisation',
                                'on_error'               => 'stop',
                                'elements'               => $factures,
                                'nbElementsPerIteration' => 100
                            );
                        }
                    }
                    break;

                case 'achat':
                    $action_data['operation_title'] = 'Traitement en masse des achats périodiques';
                    $action_data['report_code'] = 'PERIODIC_MASS_PROCESS_ACHAT';

                    // Check des commandes fourns:
                    $fourns = BimpTools::getArrayValueFromPath($extra_data, 'fourns', array());
                    if (empty($fourns)) {
                        $errors[] = 'Aucune commande fournisseur spécifiée';
                    } else {
                        $process->setCurrentObjectData('bimpcommercial', 'Bimp_CommandeFourn');

                        foreach ($fourns as $id_fourn => $entrepots) {
                            if (!$id_fourn || empty($entrepots)) {
                                continue;
                            }

                            $fourn = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Fournisseur', $id_fourn);
                            if (!BimpObject::objectLoaded($fourn)) {
                                $errors[] = 'Le fournisseur #' . $id_fourn . ' n\'existe plus';
                            } else {
                                foreach ($entrepots as $id_entrepot => $entrepot_data) {
                                    $lines = BimpTools::getArrayValueFromPath($entrepot_data, 'lines', array());
                                    if (empty($lines)) {
                                        continue;
                                    }

                                    $id_cf = (int) BimpTools::getArrayValueFromPath($entrepot_data, 'id_commande_fourn', 0);
                                    if ($id_cf != 'new' && (int) $id_cf > 0) {
                                        $cf = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', $id_cf);
                                        if (!BimpObject::objectLoaded($cf)) {
                                            $errors[] = 'La commande fournisseur #' . $id_cf . ' n\'existe plus';
                                        } else {
                                            if ((int) $cf->getData('fk_statut') != 0) {
                                                $errors[] = 'La commande fournisseur ' . $cf->getLink() . ' n\'est plus au statut brouillon';
                                            }
                                        }
                                    } else {
                                        // Création de la CF : 
                                        $cf_errors = array();
                                        $cf = BimpObject::createBimpObject('bimpcommercial', 'Bimp_CommandeFourn', array(
                                                    'fk_soc'   => $id_fourn,
                                                    'entrepot' => $id_entrepot,
                                                    'ef_type'  => 'C',
                                                    'libelle'  => 'Achats périodiques'
                                                        ), true, $cf_errors);

                                        if (count($cf_errors)) {
                                            $msg = BimpTools::getMsgFromArray($cf_errors, 'Echec de la création de la commande pour le fournisseur "' . $fourn->getName() . '"');
                                            $errors[] = $msg;
                                            $process->Error($msg, $fourn);
                                        } else {
                                            $fourns[$id_fourn][$id_entrepot]['id_commande_fourn'] = (int) $cf->id;
                                            $process->Success('Création de la CF pour le fournisseur "' . $fourn->getName() . '" OK', $fourn);
                                            $process->incCreated();
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if (!count($errors)) {
                        // Ajout des lignes:

                        $action_data['steps'] = array();
                        $commandes = array();

                        foreach ($fourns as $id_fourn => $entrepots) {
                            foreach ($entrepots as $id_entrepot => $entrepot_data) {
                                $lines = BimpTools::getArrayValueFromPath($entrepot_data, 'lines', array());
                                $id_cf = (int) BimpTools::getArrayValueFromPath($entrepot_data, 'id_commande_fourn', 0);

                                if (!$id_fourn || !$id_cf || empty($lines)) {
                                    continue;
                                }
                            }

                            $elements = array();

                            foreach ($lines as $line_data) {
                                $elements[] = json_encode(array(
                                    'id_line' => (int) BimpTools::getArrayValueFromPath($line_data, 'id_line', 0),
                                    'qty'     => (int) BimpTools::getArrayValueFromPath($line_data, 'qty', 0),
                                    'pa_ht'   => (float) BimpTools::getArrayValueFromPath($line_data, 'pa_ht', 0),
                                ));
                            }

                            if (!empty($elements)) {
                                if (!in_array($id_cf, $commandes)) {
                                    $commandes[] = $id_cf;
                                }

                                $fourn = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Fournisseur', $id_fourn);
                                $action_data['steps']['process_cf_' . $id_cf . '_lines'] = array(
                                    'label'                  => 'Ajout des lignes à la CF pour le fournisseur "' . $fourn->getName() . '"',
                                    'on_error'               => 'continue',
                                    'elements'               => $elements,
                                    'nbElementsPerIteration' => 10
                                );
                            }
                        }

                        if (!empty($commandes)) {
                            $action_data['steps']['finalize_achats'] = array(
                                'label'                  => 'Vérifications et finalisation',
                                'on_error'               => 'stop',
                                'elements'               => $commandes,
                                'nbElementsPerIteration' => 100
                            );
                        }
                    }
                    break;
            }
        }

        if ($use_db_transactions) {
            if (count($errors)) {
                $this->db->db->rollback();
            } else {
                $this->db->db->commit();
            }
        }
    }

    public function executeBdsActionPeriodicityMassProcess($process, $step_name, $elements = array(), &$errors = array(), $operation_extra_data = array(), $action_extra_data = array())
    {
        if (empty($elements)) {
            $errors[] = 'Aucune ligne de commande client à traiter';
            return;
        }

        $use_db_transaction = (int) BimpCore::getConf('use_db_transactions');
        if ($use_db_transaction) {
            $this->db->db->commitAll();
        }

        switch ($step_name) {
            default:
                if (preg_match('/^process_shipment_(\d+)_lines$/', $step_name, $matches)) {
                    $id_shipment = (int) $matches[1];

                    if (!$id_shipment) {
                        $errors[] = 'ID de l\'expédition à traiter absent';
                    } else {
                        $shipment = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeShipment', $id_shipment);
                        if (!BimpObject::objectLoaded($shipment)) {
                            $errors[] = 'L\'expédition #' . $id_shipment . ' n\'existe plus';
                        } else {
                            foreach ($elements as $element) {
                                $process->setCurrentObjectData('bimpcommercial', 'Bimp_CommandeLine');
                                $line_data = json_decode($element, 1);

                                $id_line = BimpTools::getArrayValueFromPath($line_data, 'id_line', 0);
                                $periods_qty = (int) BimpTools::getArrayValueFromPath($line_data, 'qty', 0);

                                if (!$id_line) {
                                    $process->incIgnored();
                                    $process->Alert('Une ligne ignorée (ID de la ligne de commande client absent)', $shipment);
                                    continue;
                                }

                                $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', $id_line);
                                if (!BimpObject::objectLoaded($line)) {
                                    $process->incIgnored();
                                    $process->Error('La ligne de commande client #' . $id_line . ' n\'existe plus', $shipment);
                                    continue;
                                }

                                $commande_client = $line->getParentInstance();
                                $line_ref = 'Expédition n° ' . $shipment->getData('num_livraison') . ' - Ligne n° ' . $line->getData('position');
                                $product = $line->getProduct();

                                if (!BimpObject::objectLoaded($product)) {
                                    $process->incIgnored();
                                    if ((int) $line->id_product) {
                                        $process->Error('Le produit #' . $line->id_product . ' n\'existe plus', $commande_client, $line_ref);
                                    } else {
                                        $process->Error('Aucun produit', $commande_client, $line_ref);
                                    }
                                    continue;
                                }

                                $line_ref .= ' - Produit ' . $product->getRef();

                                if (!$periods_qty) {
                                    $process->incIgnored();
                                    $process->Alert('Ligne ignorée (Aucune unité à livrer)', $commande_client, $line_ref);
                                    continue;
                                }

                                // Check des qty:
                                $line_periods_data = $line->getNbPeriodsToShipData();

                                if (!(int) $line_periods_data['nb_periods_toship_today']) {
                                    $process->incIgnored();
                                    $msg = 'Il ne reste plus de période à livrer à date pour cette ligne de commande client';
                                    $process->Error($msg, $commande_client, $line_ref);
                                    continue;
                                } elseif ($periods_qty > $line_periods_data['nb_periods_toship_today']) {
                                    $msg = 'Il ne reste que ' . $line_periods_data['nb_periods_toship_today'] . ' période(s) à acheter (' . ($line_periods_data['nb_periods_toship_today'] * $line_periods_data['qty_for_1_period']) . ' unité(s))';
                                    $process->Alert($msg, $commande_client, $line_ref);
                                    $periods_qty = $line_periods_data['nb_periods_toship_today'];
                                }

                                $qty = $periods_qty * $line_periods_data['qty_for_1_period'];

                                if (!$qty) {
                                    $process->incIgnored();
                                    $process->Alert('Aucune unité à livrer', $commande_client, $line_ref);
                                    continue;
                                }

                                // Maj des quantités expédiées pour la ligne de commande:
                                if ($use_db_transaction) {
                                    $this->db->db->begin();
                                }

                                $line_warnings = array();
                                $line_errors = $line->setShipmentData($shipment, array(
                                    'qty' => $qty
                                        ), $line_warnings);

                                if (count($line_warnings)) {
                                    $process->Alert($line_warnings, $commande_client, $line_ref);
                                }

                                if (count($line_errors)) {
                                    $process->Error($line_errors, $commande_client, $line_ref);
                                    $process->incIgnored();
                                } else {
                                    $s = ($qty > 1 ? 's' : '');
                                    $process->Success($qty . ' unité' . $s . ' livrée' . $s . ' (' . $periods_qty . ' période' . ($periods_qty > 1 ? 's' : '') . ')', $commande_client, $line_ref);
                                    $process->incUpdated();
                                }

                                if ($use_db_transaction) {
                                    if (count($line_errors)) {
                                        $this->db->db->rollback();
                                    } else {
                                        $this->db->db->commit();
                                    }
                                }
                            }
                        }
                    }
                } elseif (preg_match('/^process_facture_(\d+)_lines$/', $step_name, $matches)) {
                    $id_facture = (int) $matches[1];

                    if (!$id_facture) {
                        $errors[] = 'ID de la facture à traiter absent';
                    } else {
                        $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_facture);
                        if (!BimpObject::objectLoaded($facture)) {
                            $errors[] = 'La facture #' . $id_facture . ' n\'existe plus';
                        } else {
                            $lines_data = array();

                            foreach ($elements as $element) {
                                $process->setCurrentObjectData('bimpcommercial', 'Bimp_FactureLine');
                                $line_data = json_decode($element, 1);

                                $id_line = BimpTools::getArrayValueFromPath($line_data, 'id_line', 0);
                                $periods_qty = (int) BimpTools::getArrayValueFromPath($line_data, 'qty', 0);

                                if (!$id_line) {
                                    $process->incIgnored();
                                    $process->Alert('Une ligne ignorée (ID de la ligne de commande client absent)', $facture);
                                    continue;
                                }

                                $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', $id_line);
                                if (!BimpObject::objectLoaded($line)) {
                                    $process->incIgnored();
                                    $process->Error('La ligne de commande client #' . $id_line . ' n\'existe plus', $facture);
                                    continue;
                                }

                                $commande_client = $line->getParentInstance();
                                $line_ref = (BimpObject::objectLoaded($commande_client) ? 'Commande ' . $commande_client->getRef() . ' - Ligne n° ' . $line->getData('position') : 'Ligne #' . $line->id);
                                $product = $line->getProduct();

                                if (!BimpObject::objectLoaded($product)) {
                                    $process->incIgnored();
                                    if ((int) $line->id_product) {
                                        $process->Error('Le produit #' . $line->id_product . ' n\'existe plus', $facture, $line_ref);
                                    } else {
                                        $process->Error('Aucun produit', $facture, $line_ref);
                                    }
                                    continue;
                                }

                                $line_ref .= ' - Produit ' . $product->getRef();

                                if (!$periods_qty) {
                                    $process->incIgnored();
                                    $process->Alert('Ligne ignorée (Aucune unité à facturer)', $facture, $line_ref);
                                    continue;
                                }

                                // Check des qty:
                                $line_periods_data = $line->getNbPeriodsToBillData();

                                if (!(int) $line_periods_data['nb_periods_tobill_today']) {
                                    $process->incIgnored();
                                    $msg = 'Il ne reste plus de période à facturer pour cette ligne de commande client';
                                    $msg .= '<pre>' . print_r($line_periods_data, 1) . '</pre>';
                                    $process->Error($msg, $facture, $line_ref);
                                    continue;
                                } elseif ($periods_qty > $line_periods_data['nb_periods_tobill_today']) {
                                    $msg = 'Il ne reste que ' . $line_periods_data['nb_periods_tobill_today'] . ' période(s) à facturer (' . ($line_periods_data['nb_periods_tobill_today'] * $line_periods_data['qty_for_1_period']) . ' unité(s))';
                                    $process->Alert($msg, $facture, $line_ref);
                                    $periods_qty = $line_periods_data['nb_periods_tobill_today'];
                                }

                                $qty = $periods_qty * $line_periods_data['qty_for_1_period'];

                                if (!$qty) {
                                    $process->incIgnored();
                                    $process->Alert('Aucune unité à facturer', $facture, $line_ref);
                                    continue;
                                }

                                $lines_data[$id_line] = array(
                                    'qty' => $qty
                                );
                            }

                            if (!empty($lines_data)) {
                                // Ajout des lignes à la facture :
                                $commande_instance = BimpObject::getInstance('bimpcommercial', 'Bimp_Commande');
                                $nOk = 0;
                                $lines_errors = $commande_instance->addLinesToFacture($id_facture, $lines_data, true, true, true, $nOk);

                                if (count($lines_errors)) {
                                    $process->incIgnored('current', count($lines_errors));
                                    $process->Error(BimpTools::getMsgFromArray($lines_errors), $facture);
                                } elseif ($nOk > 0) {
                                    $process->incCreated('current', $nOk);
                                    $process->Success($nOk . ' ligne(s) traitée(s) avec succès', $facture);
                                }
                            }
                        }
                    }
                } elseif (preg_match('/^process_cf_(\d+)_lines$/', $step_name, $matches)) {
                    $id_cf = (int) $matches[1];

                    if (!$id_cf) {
                        $errors[] = 'ID de la commande fournisseur à traiter absent';
                    } else {
                        $id_fourn = (int) $this->db->getValue('commande_fournisseur', 'fk_soc', 'rowid = ' . $id_cf);
                        $cf = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', $id_cf);
                        if (!BimpObject::objectLoaded($cf)) {
                            $errors[] = 'La commande fournisseur #' . $id_cf . ' n\'existe plus';
                        } else {
                            foreach ($elements as $element) {
                                $process->setCurrentObjectData('bimpcommercial', 'Bimp_CommandeFournLine');
                                $line_data = json_decode($element, 1);

                                $id_line = BimpTools::getArrayValueFromPath($line_data, 'id_line', 0);
                                $periods_qty = (int) BimpTools::getArrayValueFromPath($line_data, 'qty', 0);
                                $pa_ht = (float) BimpTools::getArrayValueFromPath($line_data, 'pa_ht', 0);

                                if (!$id_line) {
                                    $process->incIgnored();
                                    $process->Alert('Une ligne ignorée (ID de la ligne de commande client absent)', $cf);
                                    continue;
                                }

                                $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', $id_line);
                                if (!BimpObject::objectLoaded($line)) {
                                    $process->incIgnored();
                                    $process->Error('La ligne de commande client #' . $id_line . ' n\'existe plus', $cf);
                                    continue;
                                }

                                $commande_client = $line->getParentInstance();
                                $line_ref = (BimpObject::objectLoaded($commande_client) ? 'Commande ' . $commande_client->getRef() . ' - Ligne n° ' . $line->getData('position') : 'Ligne #' . $line->id);
                                $product = $line->getProduct();

                                if (!BimpObject::objectLoaded($product)) {
                                    $process->incIgnored();
                                    if ((int) $line->id_product) {
                                        $process->Error('Le produit #' . $line->id_product . ' n\'existe plus', $cf, $line_ref);
                                    } else {
                                        $process->Error('Aucun produit', $cf, $line_ref);
                                    }
                                    continue;
                                }

                                $line_ref .= ' - Produit ' . $product->getRef();

                                if (!$periods_qty) {
                                    $process->incIgnored();
                                    $process->Alert('Ligne ignorée (Aucune unité à commander)', $cf, $line_ref);
                                    continue;
                                }

                                // Check des qty:
                                $line_periods_data = $line->getNbPeriodesToBuyData();

                                if (!(int) $line_periods_data['nb_periods_tobuy_today']) {
                                    $process->incIgnored();
                                    $msg = 'Il ne reste plus de période à acheter pour cette ligne de commande client';
                                    $process->Error($msg, $cf, $line_ref);
                                    continue;
                                } elseif ($periods_qty > $line_periods_data['nb_periods_tobuy_today']) {
                                    $msg = 'Il ne reste que ' . $line_periods_data['nb_periods_tobuy_today'] . ' période(s) à acheter (' . ($line_periods_data['nb_periods_tobuy_today'] * $line_periods_data['qty_for_1_period']) . ' unité(s))';
                                    $process->Alert($msg, $cf, $line_ref);
                                    $periods_qty = $line_periods_data['nb_periods_tobuy_today'];
                                }

                                $qty = $periods_qty * $line_periods_data['qty_for_1_period'];

                                if (!$qty) {
                                    $process->incIgnored();
                                    $process->Alert('Aucune unité à commander', $cf, $line_ref);
                                    continue;
                                }

                                // Création de la ligne de cf:
                                if ($use_db_transaction) {
                                    $this->db->db->begin();
                                }


                                $line_warnings = array();
                                $line_errors = $line->addToCommandeFourn($id_cf, $qty, array(
                                    'id_fourn' => $id_fourn,
                                    'pu_ht'    => $pa_ht,
                                    'tva_tx'   => $product->getData('tva_tx')
                                        ), $line_warnings);

                                if (count($line_warnings)) {
                                    $process->Alert($line_warnings, $cf, 'Ligne n° ' . $line->getData('position') . (BimpObject::objectLoaded($product) ? ' - ' . $product->getRef() : ''));
                                }

                                if (count($line_errors)) {
                                    $process->Error($line_errors, $cf, 'Ligne n° ' . $line->getData('position') . (BimpObject::objectLoaded($product) ? ' - ' . $product->getRef() : ''));
                                    $process->incIgnored();
                                } else {
                                    $process->Success($qty . ' unité(s) commandée(s) (' . $periods_qty . ' période(s))', $cf, 'Ligne n° ' . $line->getData('position') . (BimpObject::objectLoaded($product) ? ' - ' . $product->getRef() : ''));
                                    $process->incCreated();
                                }

                                if ($use_db_transaction) {
                                    if (count($line_errors)) {
                                        $this->db->db->rollback();
                                    } else {
                                        $this->db->db->commit();
                                    }
                                }
                            }
                        }
                    }
                } else {
                    $errors[] = 'Etape invalide';
                }
                break;

            case 'finalize_exps':
                $exps_ok = array();
                $process->setCurrentObjectData('bimplogistique', 'BL_CommandeShipment');

                foreach ($elements as $id_shipment) {
                    $shipment = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeShipment', $id_shipment);
                    if (BimpObject::objectLoaded($shipment) && (int) $shipment->getData('status') == 1) {
                        $commande = $shipment->getParentInstance();

                        $qty = 0;
                        if (BimpObject::objectLoaded($commande)) {
                            foreach ($commande->getLines('not_text') as $line) {
                                $qty += (float) $line->getShippedQty($id_shipment);
                            }
                        }

                        if ($use_db_transaction) {
                            $this->db->db->begin();
                        }

                        $exp_warnings = array();
                        $exp_errors = array();
                        if (!$qty) {
                            $exp_errors = $shipment->delete($exp_warnings, true);

                            if (!count($exp_errors)) {
                                $process->Alert('Aucune ligne ajoutée à l\'expédition #' . $id_shipment . '. Celle-ci a été supprimée', $commande);
                                $process->incDeleted();
                            } else {
                                $process->Alert(BimpTools::getMsgFromArray($exp_errors, 'Aucune ligne ajoutée à l\'expédition #' . $id_shipment . ' mais échec de sa suppression'), $commande, 'Expédition ' . $shipment->getRef());
                            }
                        } else {
                            $exps_ok[] = $id_shipment;

                            $exp_errors = $shipment->validateShipment($exp_warnings, date('Y-m-d H:i:s'), 1, 1, 0, 1);

                            if (count($exp_errors)) {
                                $process->Error(BimpTools::getMsgFromArray($exp_errors, 'Echec de la validation de l\'expédition'), $commande, 'Expédition ' . $shipment->getRef());
                            } else {
                                $process->Success('Validation de l\'expédition effectuée', $commande, 'Expédition ' . $shipment->getRef());
                            }

                            if (count($exp_warnings)) {
                                $process->Alert(BimpTools::getMsgFromArray($exp_warnings, 'Erreurs lors de la validation de l\'expédition'), $commande, 'Expédition ' . $shipment->getRef());
                            }
                        }

                        if (!count($exp_errors)) {
                            if ($use_db_transaction) {
                                $this->db->db->commit();
                            }
                        } else {
                            if ($use_db_transaction) {
                                $this->db->db->rollback();
                            }
                        }
                    }
                }

                if (!empty($exps_ok)) {
                    $s = (count($exps_ok) > 1 ? 's' : '');
                    $msg = count($exps_ok) . ' expédition' . $s . ' traitée' . $s . ' avec succès.<br/>';

                    foreach ($exps_ok as $id_shipment) {
                        $shipment = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeShipment', $id_shipment);

                        $commande = $shipment->getParentInstance();
                        if (BimpObject::objectLoaded($commande)) {
                            $url = DOL_URL_ROOT . '/bimplogistique/index.php?fc=commande&id=' . $commande->id . '&navtab-maintabs=shipments';
                            $msg .= '<br/><a href="' . $url . '" target="_blank">Commande ' . $commande->getRef() . ' - expédition n° ' . $shipment->getData('num_livraison');
                            $msg .= BimpRender::renderIcon('fas_external-link-alt', 'iconRight') . '</a>';
                        } else {
                            $msg .= '<br/>' . $shipment->getLink();
                        }
                    }

                    $process->Success($msg);
                }
                break;

            case 'finalize_facs':
                $facs_ok = array();
                $process->setCurrentObjectData('bimpcommercial', 'Bimp_Facture');
                foreach ($elements as $id_facture) {
                    $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_facture);
                    if (BimpObject::objectLoaded($fac) && (int) $fac->getData('fk_statut') == 0) {
                        if ($this->db->getCount('facturedet', 'fk_facture = ' . $id_facture, 'rowid') == 0) {
                            if ($use_db_transaction) {
                                $this->db->db->begin();
                            }

                            $fac_warnings = array();
                            $fac_errors = $fac->delete($fac_warnings, true);

                            if (!count($fac_errors)) {
                                $process->Alert('Aucune ligne ajoutée à la facture #' . $id_facture . '. Celle-ci a été supprimée');
                                $process->incDeleted();

                                if ($use_db_transaction) {
                                    $this->db->db->commit();
                                }

                                continue;
                            } else {
                                if ($use_db_transaction) {
                                    $this->db->db->rollback();
                                }
                            }
                        }
                    }

                    $facs_ok[] = $id_facture;
                }

                if (!empty($facs_ok)) {
                    $s = (count($facs_ok) > 1 ? 's' : '');
                    $msg = count($facs_ok) . ' facture' . $s . ' traitée' . $s . ' avec succès.<br/>';

                    foreach ($facs_ok as $id_fac) {
                        $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_fac);

                        $sql = BimpTools::getSqlFullSelectQuery('bimp_facture_line', array('DISTINCT cl.id_obj as id_commande'), array(
                                    'a.id_obj'             => $id_fac,
                                    'a.linked_object_name' => 'commande_line'
                                        ), array(
                                    'cl' => array(
                                        'table' => 'bimp_commande_line',
                                        'on'    => 'cl.id = a.linked_id_object'
                                    )
                        ));

                        echo $sql . '<br/><br/>';
                        echo $this->db->err() . '<br/><br/>';

                        $commandes = $this->db->executeS($sql, 'array');

                        // Liens commandes: 
                        if (!empty($commandes)) {
                            foreach ($commandes as $c) {
                                $fac->dol_object->add_object_linked('commande', (int) $c['id_commande']);
                            }
                        }

                        // Contacts: 
                        $contacts = array();
                        $users = array();
                        if (!empty($commandes)) {
                            $client = $fac->getChildObject('client');
                            foreach ($commandes as $c) {
                                $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $c['id_commande']);
                                if (BimpObject::objectLoaded($commande)) {
                                    $cmde_contacts = $commande->getContactsByCodes('external');
                                    $cmde_users = $commande->getContactsByCodes('internal');

                                    foreach ($cmde_contacts as $contact) {
                                        if (!isset($contacts[$contact['code']])) {
                                            $contacts[$contact['code']] = array();
                                        }

                                        $contacts[$contact['code']][] = $contact['id'];
                                    }

                                    foreach ($cmde_users as $u) {
                                        if (!isset($users[$u['code']])) {
                                            $users[$u['code']] = array();
                                        }

                                        $users[$u['code']][] = $u['id'];
                                    }
                                }
                            }

                            if (BimpObject::objectLoaded($client)) {
                                if (!isset($contacts['BILLING2']) || empty($contacts['BILLING2']) || count($contacts['BILLING2']) > 1) {
                                    // On récupère le contact e-mail facturation par défaut de la fiche client : 
                                    $id_def_contact = (int) $client->getData('contact_default');
                                    if ($id_def_contact) {
                                        $contacts['BILLING2'] = array($id_def_contact);
                                    }
                                }

                                if (!isset($users['SALESREPFOLL']) || empty($users['SALESREPFOLL'])) {
                                    // On récupère le commerical du client : 
                                    $id_def_commercial = (int) $client->getCommercial(false);
                                    if ($id_def_commercial) {
                                        $users['SALESREPFOLL'] = array($id_def_commercial);
                                    }
                                }
                            }

                            $fac_contacts = $fac->getContactsByCodes('external');
                            $fac_users = $fac->getContactsByCodes('internal');

                            foreach ($contacts as $code => $contacts_ids) {
                                foreach ($contacts_ids as $id_contact) {
                                    if (!isset($fac_contacts[$code]) || !in_array($id_contact, $fac_contacts[$code])) {
                                        $fac->dol_object->add_contact($id_contact, $code, 'external');
                                        $fac_contacts[$code][] = $id_contact;
                                    }
                                }
                            }

                            foreach ($users as $code => $users_ids) {
                                foreach ($users_ids as $id_user) {
                                    if (!isset($fac_users[$code]) || !in_array($id_user, $fac_users[$code])) {
                                        $fac->dol_object->add_contact($id_user, $code, 'internal');
                                        $fac_users[$code][] = $id_user;
                                    }
                                }
                            }
                        }

                        $msg .= '<br/>' . $fac->getLink();
                    }

                    $process->Success($msg);
                }
                break;

            case 'finalize_achats':
                $commandes_ok = array();
                $process->setCurrentObjectData('bimpcommercial', 'Bimp_CommandeFourn');
                foreach ($elements as $id_cf) {
                    $cf = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', $id_cf);
                    if (BimpObject::objectLoaded($cf) && (int) $cf->getData('fk_statut') == 0) {
                        if ($this->db->getCount('commande_fournisseurdet', 'fk_commande = ' . $id_cf, 'rowid') == 0) {
                            if ($use_db_transaction) {
                                $this->db->db->begin();
                            }

                            $cf_warnings = array();
                            $cf_errors = $cf->delete($cf_warnings, true);

                            if (!count($cf_errors)) {
                                $process->Alert('Aucune ligne ajoutée à la commande fournisseur #' . $id_cf . '. Celle-ci a été supprimée');
                                $process->incDeleted();

                                if ($use_db_transaction) {
                                    $this->db->db->commit();
                                }

                                continue;
                            } else {
                                if ($use_db_transaction) {
                                    $this->db->db->rollback();
                                }
                            }
                        }
                    }

                    $commandes_ok[] = $id_cf;
                }

                if (!empty($commandes_ok)) {
                    $s = (count($commandes_ok) > 1 ? 's' : '');
                    $msg = count($commandes_ok) . ' commande' . $s . ' fournisseur' . $s . ' traitée' . $s . ' avec succès.<br/>';

                    foreach ($commandes_ok as $id_cf) {
                        $cf = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', $id_cf);
                        $msg .= '<br/>' . $cf->getLink();
                    }

                    $process->Success($msg);
                }
                break;
        }
    }

    public function initBdsActionCheckPeriodicityData($process, &$action_data = array(), &$errors = array(), $extra_data = array())
    {
        $action_data['operation_title'] = 'Vérification des données des opérations périodiques';

        $id_client = (int) BimpTools::getArrayValueFromPath($extra_data, 'id_client', $id_client);
        $id_fourn = (int) BimpTools::getArrayValueFromPath($extra_data, 'id_fourn', $id_fourn);
        $id_product = (int) BimpTools::getArrayValueFromPath($extra_data, 'id_product', $id_product);

        if ($id_client) {
            $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $id_client);
            $action_data['operation_title'] .= ' du client ' . $client->getRef() . ' - ' . $client->getName();
        } elseif ($id_fourn) {
            $fourn = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_fournisseur', $id_fourn);
            $action_data['operation_title'] .= ' auprès du fournisseur ' . $fourn->getRef() . ' - ' . $fourn->getName();
        } elseif ($id_product) {
            $prod = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $id_product);
            $action_data['operation_title'] .= ' pour le produit ' . $prod->getRef() . ' - ' . $prod->getName();
        }

        $null_dates_only = (int) BimpTools::getArrayValueFromPath($extra_data, 'null_dates_only', 1);
        $types = BimpTools::getArrayValueFromPath($extra_data, 'types', array());

        if (empty($types)) {
            $errors[] = 'Aucun type d\'opération périodique à vérifier sélectionné';
        } else {
            foreach (array('exp' => 'livraisons périodiques', 'fac' => 'Facturations périodiques', 'achat' => 'achats périodiques') as $type => $type_label) {
                if (!in_array($type, $types)) {
                    continue;
                }

                $joins = array(
                    'c' => array(
                        'table' => 'commande',
                        'on'    => 'c.rowid = a.id_obj'
                    )
                );

                $filters = array(
                    'c.fk_statut'                 => array(1, 2),
                    'a.' . $type . '_periodicity' => '> 0'
                );

                if ($null_dates_only) {
                    $filters[$type . '_periods_start'] = array(
                        'or_field' => array(
                            'IS_NULL',
                            '',
                            '0000-00-00',
                            '1970-01-01'
                        )
                    );
                    $filters['next_date_' . $type] = array(
                        'or_field' => array(
                            'IS_NULL',
                            '',
                            '0000-00-00',
                            '1970-01-01'
                        )
                    );
                }

                if ($id_client) {
                    $filters['c.fk_soc'] = $id_client;
                }

                if ($id_product) {
                    $joins['cdet'] = array(
                        'table' => 'commandedet',
                        'on'    => 'cdet.rowid = a.id_line'
                    );
                    $filters['cdet.fk_product'] = $id_product;
                }

                if ($type == 'achat' && $id_fourn) {
                    $filters['a.achat_id_fourn'] = $id_fourn;
                }

                $sql = BimpTools::getSqlFullSelectQuery('bimp_commande_line', array('a.id'), $filters, $joins);
                $rows = $this->db->executeS($sql, 'array');

                if (is_array($rows)) {
                    $elements = array();
                    foreach ($rows as $r) {
                        $elements[] = (int) $r['id'];
                    }

                    if (!empty($elements)) {
                        $action_data['steps']['check_' . $type . '_lines'] = array(
                            'label'                  => 'Vérification des ' . $type_label,
                            'on_error'               => 'continue',
                            'elements'               => $elements,
                            'nbElementsPerIteration' => 25
                        );
                    }
                } else {
                    $errors[] = 'Erreur SQL pour les ' . $type_label . ' - ' . $this->db->err();
                }
            }
        }
    }

    public function executeBdsActionCheckPeriodicityData($process, $step_name, $elements = array(), &$errors = array(), $operation_extra_data = array(), $action_extra_data = array())
    {
        if (empty($elements)) {
            $errors[] = 'Aucune ligne de commande client à traiter';
            return;
        }

        if (preg_match('/^check_([a-z]+)_lines/', $step_name, $matches)) {
            $type = $matches[1];

            $process->setCurrentObjectData('bimpcommercial', 'Bimp_CommandeLine');

            foreach ($elements as $id_line) {
                $process->incProcessed();
                $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', $id_line);
                $line->useNoTransactionsDb();

                if (BimpObject::objectLoaded($line)) {
                    $infos = array();
                    $line_errors = $line->checkPeriodicityData($type, $infos);

                    if (count($line_errors)) {
                        $process->incIgnored();
                        $process->Error(BimpTools::getMsgFromArray($line_errors), $line);
                    } elseif (count($infos)) {
                        $process->incUpdated();
                        $process->Info(BimpTools::getMsgFromArray($infos), $line);
                    }
                }
            }
        }
    }

    // Overrides:

    public function getDbData($fields = null)
    {
        $data = parent::getDbData($fields);

        if ((is_null($fields) || in_array('exp_periods_start', $fields)) && !(string) $this->data['exp_periods_start']) {
            $data['exp_periods_start'] = null;
        }
        if ((is_null($fields) || in_array('fac_periods_start', $fields)) && !(string) $this->data['fac_periods_start']) {
            $data['fac_periods_start'] = null;
        }
        if ((is_null($fields) || in_array('achat_periods_start', $fields)) && !(string) $this->data['achat_periods_start']) {
            $data['achat_periods_start'] = null;
        }

        return $data;
    }

    public function validate()
    {
        $errors = array();

        $periodicity_extra_params = $this->getData('periodicity_extra_params');
        if (BimpTools::isPostFieldSubmit('has_fac_periodicity')) {
            if (!(int) BimpTools::getValue('has_fac_periodicity', 0, 'int')) {
                $this->set('fac_periodicity', 0, 'int');
                $this->set('fac_nb_periods', 0, 'int');
                $this->set('fac_periods_start', null);
                $this->set('next_date_fac', null);
            }
        }

        if (BimpTools::isPostFieldSubmit('has_exp_periodicity')) {
            if (!(int) BimpTools::getValue('has_exp_periodicity', 0, 'int')) {
                $this->set('exp_periodicity', 0);
                $this->set('exp_nb_periods', 0);
                $this->set('exp_periods_start', null);

                if (isset($periodicity_extra_params['exp_id_contact'])) {
                    unset($periodicity_extra_params['exp_id_contact']);
                }
            } else {
                if ((int) $this->getData('fac_periodicity') > 0 && (int) BimpTools::getPostFieldValue('exp_periodicity_same_values', 0, 'int')) {
                    $this->set('exp_periodicity', $this->getData('fac_periodicity'));
                    $this->set('exp_nb_periods', $this->getData('fac_nb_periods'));
                    $this->set('exp_periods_start', $this->getData('fac_periods_start'));
                }

                if (BimpTools::isPostFieldSubmit('exp_id_contact')) {
                    $periodicity_extra_params['exp_id_contact'] = (int) BimpTools::getPostFieldValue('exp_id_contact', 0, 'int');
                }
            }
        }

        if (BimpTools::isPostFieldSubmit('has_achat_periodicity')) {
            if (!(int) BimpTools::getValue('has_achat_periodicity', 0, 'int')) {
                $this->set('achat_periodicity', 0);
                $this->set('achat_nb_periods', 0);
                $this->set('achat_periods_start', null);
                $this->set('achat_id_fourn', 0);

                if (isset($periodicity_extra_params['achat_id_fourn'])) {
                    unset($periodicity_extra_params['achat_id_fourn']);
                }
                if (isset($periodicity_extra_params['achat_pa_ht'])) {
                    unset($periodicity_extra_params['achat_pa_ht']);
                }
                if (isset($periodicity_extra_params['achat_tva_tx'])) {
                    unset($periodicity_extra_params['achat_tva_tx']);
                }
                if (isset($periodicity_extra_params['achat_id_fourn_price'])) {
                    unset($periodicity_extra_params['achat_id_fourn_price']);
                }
            } else {
                $params_mode = BimpTools::getPostFieldValue('achat_periodicity_same_values', '', 'aZ09');
                switch ($params_mode) {
                    case 'as_fac':
                        $this->set('achat_periodicity', $this->getData('fac_periodicity'));
                        $this->set('achat_nb_periods', $this->getData('fac_nb_periods'));
                        $this->set('achat_periods_start', $this->getData('fac_periods_start'));
                        break;

                    case 'as_exp':
                        $this->set('achat_periodicity', $this->getData('exp_periodicity'));
                        $this->set('achat_nb_periods', $this->getData('exp_nb_periods'));
                        $this->set('achat_periods_start', $this->getData('exp_periods_start'));
                        break;
                }

                if (BimpTools::isPostFieldSubmit('achat_type_pa')) {
                    $periodicity_extra_params['achat_type_pa'] = (int) BimpTools::getPostFieldValue('achat_type_pa', 0, 'int');
                    switch ($periodicity_extra_params['achat_type_pa']) {
                        case 1: // PA fourn
                            if (isset($periodicity_extra_params['achat_id_fourn'])) {
                                unset($periodicity_extra_params['achat_id_fourn']);
                            }
                            if (isset($periodicity_extra_params['achat_pa_ht'])) {
                                unset($periodicity_extra_params['achat_pa_ht']);
                            }
                            if (isset($periodicity_extra_params['achat_tva_tx'])) {
                                unset($periodicity_extra_params['achat_tva_tx']);
                            }
                            $periodicity_extra_params['achat_id_fourn_price'] = (int) BimpTools::getPostFieldValue('achat_id_fourn_price', 0, 'int');
                            $pfp = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ProductFournisseurPrice', $periodicity_extra_params['achat_id_fourn_price']);
                            if (BimpObject::objectLoaded($pfp)) {
                                $this->set('achat_id_fourn', $pfp->getData('fk_soc'));
                            } else {
                                $this->set('achat_id_fourn', 0);
                            }
                            break;
                        case 2: // PA custom
                            if (isset($periodicity_extra_params['achat_id_fourn_price'])) {
                                unset($periodicity_extra_params['achat_id_fourn_price']);
                            }
                            $periodicity_extra_params['achat_id_fourn'] = (int) BimpTools::getPostFieldValue('achat_id_fourn', 0, 'int');
                            $periodicity_extra_params['achat_pa_ht'] = (float) BimpTools::getPostFieldValue('achat_pa_ht', 0, 'float');
                            $periodicity_extra_params['achat_tva_tx'] = (float) BimpTools::getPostFieldValue('achat_tva_tx', 0, 'float');
                            $this->set('achat_id_fourn', $periodicity_extra_params['achat_id_fourn']);
                            break;
                    }
                }
            }
        }

        $this->set('periodicity_extra_params', $periodicity_extra_params);

        if ((int) $this->getData('fac_periodicity') !== (int) $this->getInitData('fac_periodicity') ||
                (int) $this->getData('fac_nb_periods') !== (int) $this->getInitData('fac_nb_periods')) {
            if ((float) $this->getBilledQty() > 0) {
                $errors[] = 'Les paramètres de facturation périodique ne peuvent plus être modifiés car une facturation a déjà eut lieu';
            }

            if ((int) $this->getData('fac_periodicity') && (int) $this->getData('fac_nb_periods') <= 0) {
                $errors[] = 'Veuillez saisir un nombre de périodes à facturer supérieur à 0';
            }
        }

        if ((int) $this->getData('exp_periodicity') !== (int) $this->getInitData('exp_periodicity') ||
                (int) $this->getData('exp_nb_periods') !== (int) $this->getInitData('exp_nb_periods')) {
            if ((float) $this->getShippedQty() > 0) {
                $errors[] = 'Les paramètres de livraison périodique ne peuvent plus être modifiés car une expéidition a déjà été créée';
            }

            if ((int) $this->getData('exp_periodicity') && (int) $this->getData('exp_nb_periods') <= 0) {
                $errors[] = 'Veuillez saisir un nombre de livraison périodique supérieur à 0';
            }
        }

        if (count($errors)) {
            return $errors;
        }

        // Check fac periodicity
        if ((int) $this->getData('fac_periodicity') && $this->isPeriodicityAllowed($errors)) {
            $this->set('force_qty_1', 0);

            if (!(string) $this->getData('fac_periods_start')) {
                $this->set('fac_periods_start', null);
                $this->set('next_date_fac', '0000-00-00');
            }
        } else {
            $this->set('fac_nb_periods', 0);
            $this->set('fac_periods_start', null);
            $this->set('fact_echue', 0);
            $this->set('next_date_fac', null);
        }

        // Check exp periodicity
        if ((int) $this->getData('exp_periodicity') && $this->isPeriodicityAllowed($errors)) {
            $product = $this->getProduct();

//            if (BimpObject::objectLoaded($product) && $product->isTypeProduct()) {
//                $period_unit = $this->getExpQtyFor1Periode();
//
//                if (round($period_unit) != $period_unit) {
//                    $errors[] = 'Nombre de livraisons périodiques invalides (les quantités totales de cette ligne doivent être un multiple du nombre de livraisons)';
//                }
//            }

            if (empty($errors)) {
                $this->set('force_qty_1', 0);
                if (!(string) $this->getData('exp_periods_start')) {
                    $this->set('exp_periods_start', null);
                    $this->set('next_date_exp', '0000-00-00');
                }
            }
        } else {
            $this->set('exp_nb_periods', 0);
            $this->set('exp_periods_start', null);
            $this->set('next_date_exp', null);
        }

        // Check achat periodicity
        if ((int) $this->getData('achat_periodicity') && $this->isPeriodicityAllowed($errors)) {
            $product = $this->getProduct();

//            if (BimpObject::objectLoaded($product) && $product->isTypeProduct()) {
//                $period_unit = $this->getAchatQtyFor1Periode();
//
//                if (round($period_unit) != $period_unit) {
//                    $errors[] = 'Nombre d\'achats périodiques invalides (les quantités totales de cette ligne doivent être un multiple du nombre d\'achats)';
//                }
//            }

            if (!(string) $this->getData('achat_periods_start')) {
                $this->set('achat_periods_start', null);
                $this->set('next_date_achat', '0000-00-00');
            }
        } else {
            $this->set('achat_nb_periods', 0);
            $this->set('achat_periods_start', null);
            $this->set('next_date_achat', null);
        }

        if (count($errors)) {
            return $errors;
        }

        return parent::validate();
    }

    public function onSave(&$errors = array(), &$warnings = array())
    {
        if ($this->isLoaded()) {
            $commande = $this->getParentInstance();

            if (BimpObject::objectLoaded($commande) && is_a($commande, 'Bimp_Commande')) {
                $commande->processFacturesRemisesGlobales();
            }

            $this->checkPeriodicityData();
        }
        parent::onSave($errors, $warnings);
    }

    public function checkObject($context = '', $field = '')
    {
        if ($context === 'fetch') {
            $this->checkQties();
            $commande = $this->getParentInstance();

            if (BimpObject::objectLoaded($commande) && ((int) $commande->getData('fk_statut') === 1)) {
                // Vérification des réservations: 
                $this->checkReservations(); // les quantités des réservations sont vérifiées dans cette méthode.
            }
        }

        parent::checkObject();
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = array();
        $commande = $this->getParentInstance();

        if (!BimpObject::objectLoaded($commande)) {
            $errors[] = 'ID de la commande absent';
            return $errors;
        }

        $is_extra_line = false;
        $current_commande_status = (int) $commande->getData('fk_statut');

        if ($current_commande_status !== 0) {
            $is_extra_line = true;

            if (!(int) $this->id_remise_except) {
                $this->set('qty_modif', (float) $this->qty);
                $this->qty = 0;
            }

            $commande->set('fk_statut', 0);
            $commande->dol_object->statut = 0;
        }

        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            if ($is_extra_line) {
                if (in_array((int) $current_commande_status, Bimp_Commande::$logistique_active_status)) {
                    $res_errors = $this->checkReservations();
                    if (count($res_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($res_errors);
                    }
                }

                $commande->set('fk_statut', $current_commande_status);
                $commande->dol_object->statut = $current_commande_status;

                $commande->checkShipmentStatus();
                $commande->checkInvoiceStatus();
                $commande->addLog('Ajout en logistique de la ligne n° ' . $this->getData('position') . ' (' . $this->getData('qty_modif') . ' unité(s))');
            }

            $this->checkQties();
        }

        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $prev_commande_status = null;
        $commande = $this->getParentInstance();

        if (BimpObject::objectLoaded($commande)) {
            if ((int) $commande->getData('fk_statut') === 1) {
                $prev_commande_status = 1;
                $commande->dol_object->statut = 0;
                $commande->dol_object->brouillon = 1;
            }
        }

        // Forçage si on est dans le cas d'une ligne ajouté en logistique: 
        if ((int) $this->qty === 0 && (int) $this->getData('qty_modif')) {
            $force_update = true;
        }

        $errors = parent::update($warnings, $force_update);

        if (!is_null($prev_commande_status)) {
            $commande->dol_object->statut = $prev_commande_status;
            $commande->dol_object->brouillon = 0;
        }

        if (BimpObject::objectLoaded($commande) && (int) $commande->getData('fk_statut') === 1) {
            $res_errors = $this->checkReservations();
            if (count($res_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($res_errors);
            }
        }

        if (!count($errors)) {
            $this->checkQties();
        }

        return $errors;
    }

    // Méthodes statiques:

    public static function checkAllQties()
    {
        ignore_user_abort(0);
        set_time_limit(600);
        $instance = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeLine');
        $rows = $instance->getList(array('qty_billed_not_shipped' => array('custom' => 'qty_billed_not_shipped = -qty_shipped_not_billed AND qty_shipped_not_billed != 0')), null, null, 'id', 'asc', 'array', array('id'));

        foreach ($rows as $r) {
            $line = BimpCache::getBimpObjectInstance($instance->module, $instance->object_name, (int) $r['id']);

            if (BimpObject::objectLoaded($line)) {
                $line->checkQties();
            }
        }
    }

    public static function checkPeriodsDataAll($type_to_check, $null_dates_only = true, $params = array())
    {
        $params = BimpTools::overrideArray(array(
                    'id_client'  => 0,
                    'id_fourn'   => 0,
                    'id_product' => 0
                        ), $params);

        if (!in_array($type_to_check, array('all', 'exp', 'fac', 'achat'))) {
            return;
        }

        if ($type_to_check === 'all') {
            $types = array('exp', 'fac', 'achat');
        } else {
            $types = array($type_to_check);
        }
        foreach ($types as $type) {
            $joins = array(
                'c' => array(
                    'table' => 'commande',
                    'on'    => 'c.rowid = a.id_obj'
                )
            );

            if ($params['id_product']) {
                $joins['cdet'] = array(
                    'table' => 'commandedet',
                    'on'    => 'cdet.rowid = a.id_line'
                );
            }

            $sql = BimpTools::getSqlSelect(array('a.id'));
            $sql .= BimpTools::getSqlFrom('bimp_commande_line', $joins, 'a');

            $sql .= ' WHERE ';
            $sql .= ' c.fk_statut > 0 AND c.fk_statut < 3';
            $sql .= ' AND a.' . $type . '_periodicity > 0';

            if ($null_dates_only) {
                $sql .= ' AND (';
                $sql .= 'a.' . $type . '_periods_start IS NULL OR a.' . $type . '_periods_start = \'\' OR a.' . $type . '_periods_start = \'0000-00-00\' OR a.' . $type . '_periods_start = \'1970-01-01\'';
                $sql .= ' OR a.next_date_' . $type . ' IS NULL OR a.next_date_' . $type . ' = \'\' OR a.next_date_' . $type . ' = \'0000-00-00\' OR a.next_date_' . $type . ' = \'1970-01-01\'';
                $sql .= ')';
            }

            if ($params['id_client']) {
                $sql .= ' AND c.fk_soc = ' . $params['id_client'];
            }

            if ($params['id_product']) {
                $sql .= ' AND cdet.fk_product = ' . $params['id_product'];
            }

            if ($type == 'achat' && $params['id_fourn']) {
                $sql .= ' AND a.achat_id_fourn = ' . $params['id_fourn'];
            }

//            die('SQL : '. $sql);
            $rows = self::getBdb()->executeS($sql, 'array');

            if (is_array($rows)) {
//                echo '<pre>';
//                print_r($rows);
//                exit;
                foreach ($rows as $r) {
                    $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $r['id']);
                    if (BimpObject::objectLoaded($line)) {
                        $line->checkPeriodicityData($type);
                    }
                }
            }
        }
    }
}
