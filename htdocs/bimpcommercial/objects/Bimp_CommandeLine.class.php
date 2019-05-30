<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/ObjectLine.class.php';

class Bimp_CommandeLine extends ObjectLine
{

    public static $parent_comm_type = 'commande';
    public static $dol_line_table = 'commandedet';
    public static $reservations_ordered_status = array(3, 100);
    public static $notShippableLines = array();
    public static $notEditableInLogistiqueLines = array('discount');

    // Getters booléens:

    public function isRemiseEditable()
    {
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

    public function isReadyToShip($id_shipment, &$errors)
    {
        $shipments = $this->getData('shipments');

        if (!isset($shipments[(int) $id_shipment]['qty']) || (float) $shipments[(int) $id_shipment]['qty'] <= 0) {
            $errors[] = 'Il n\'y a aucune unité ajouté à cette expédition';
            return 0;
        }

        $ready_qty = (float) $this->getReadyToShipQty($id_shipment);
        if ($ready_qty < (float) $shipments[(int) $id_shipment]['qty']) {
            $diff = (float) $shipments[(int) $id_shipment]['qty'] - $ready_qty;
            $msg = 'Il manque ';
            if ($diff > 1) {
                $msg .= $diff . ' unités prêtes à être expédiées ';
            } else {
                $msg .= '1 unité prête à être expédiée ';
            }
            $errors[] = $msg;
            return 0;
        }
        return 1;
    }

    public function isActionAllowed($action, &$errors = array())
    {
        if (in_array($action, array('modifyQty'))) {
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
                return 1;
        }

        return parent::isActionAllowed($action, $errors);
    }

    public function isShippable()
    {
        if (in_array((string) $this->getData('linked_object_name'), self::$notShippableLines)) {
            return 0;
        }

        if ((float) $this->getFullQty() < 0) {
            return 0;
        }

        return 1;
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

            $onclick = 'addSelectedCommandeLinesToFacture($(this), \'list_id\', ';
            $onclick .= $commande->id . ', ' . (int) $id_client_facture . ', ';
            $onclick .= (($id_client_facture === (int) $commande->getData('fk_soc')) ? (int) $commande->dol_object->contactid : 0) . ', ';
            $onclick .= (int) $commande->getData('fk_cond_reglement') . ')';

            $actions[] = array(
                'label'   => 'Quantités facture',
                'icon'    => 'fas_file-invoice-dollar',
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

                if ($type === self::LINE_PRODUCT) {
                    $product = $this->getProduct();
                    if (BimpObject::objectLoaded($product) && (int) $product->getData('fk_product_type') === 0) {
                        if (isset($reserved_qties['status'][0]) && $reserved_qties['status'][0] > 0)
                            $buttons[] = array(
                                'label'   => 'Commander',
                                'icon'    => 'fas_cart-arrow-down',
                                'onclick' => $this->getJsActionOnclick('addToCommandeFourn', array(
                                    'remise_pa'       => (float) $this->getData('remise_pa'),
                                    'remise_pa_label' => ((int) $this->getData('remise_crt') ? 'Remise CRT' : '')
                                        ), array(
                                    'form_name' => 'commande_fourn'
                                ))
                            );
                    }
                }

                if ($type !== self::LINE_TEXT) {
                    $shipments_qty = (float) $this->getShipmentsQty();
                    if ($shipments_qty > 0) {
                        $buttons[] = array(
                            'label'   => 'Gérer les expéditions',
                            'icon'    => 'fas_shipping-fast',
                            'onclick' => $this->getJsLoadModalView('shipments', 'Gestion des expéditions')
                        );
                    }
                    $buttons[] = array(
                        'label'   => 'Gérer les factures',
                        'icon'    => 'fas_file-invoice-dollar',
                        'onclick' => $this->getJsLoadModalView('invoices', 'Gestion des factures')
                    );

                    if ($this->isActionAllowed('modifyQty')) {
                        $buttons[] = array(
                            'label'   => 'Modifier les quantités',
                            'icon'    => 'fas_edit',
                            'onclick' => $this->getJsActionOnclick('modifyQty', array(), array(
                                'form_name' => 'qty_modified'
                            ))
                        );
                    }
                }
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
                        'id_product' => (int) $this->id_product,
                        'available'  => 0
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
                $billed_qty = (float) $this->getBilledQty();
                $equipments = array();

                if ($this->isProductSerialisable()) {
                    $equipments = $this->getData('equipments_returned');
                }

                $min = $billed_qty;

                if ((count($equipments) * -1) < $min) {
                    $min = count($equipments) * -1;
                }

                return $min;
            }
        }

//        return $this->qty;
        return 'none';
    }

    public function getReservations($order_by = 'status', $order_way = 'asc', $status = null)
    {
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
                $rows = $reservation->getList($filters, null, null, $order_by, $order_way, 'array', array('id'));

                if (!is_null($rows)) {
                    foreach ($rows as $r) {
                        $res = BimpCache::getBimpObjectInstance('bimpreservation', 'BR_Reservation', (int) $r['id']);
                        if (BimpObject::objectLoaded($res)) {
                            $reservations[] = $res;
                        }
                    }
                }
            }
        }

        return $reservations;
    }

    public function getReservedQties()
    {
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
                        if (in_array((int) $r['status'], BR_Reservation::$unavailable_status)) {
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
        if (in_array($this->getData('linked_object_name'), self::$notShippableLines)) {
            return 0;
        }

        $qty = (float) $this->getFullQty();

        if ($qty < 0) {
            return 0;
        }

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
                if ($shipments_validated_only) {
                    $shipment = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeShipment', (int) $id_s);
                    if (!BimpObject::objectLoaded($shipment) || (int) $shipment->getData('status') !== BL_CommandeShipment::BLCS_EXPEDIEE) {
                        continue;
                    }
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
                if ($invoices_validated_only) {
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

    public function getShipmentData($id_shipment)
    {
        // *** Shipment Data *** 
        // qty: float
        // group: bool
        // shipped: bool
        // equipments: array (liste id_equipment) 

        $shipments = $this->getData('shipments');

        if (isset($shipments[(int) $id_shipment])) {
            return array(
                'qty'        => (isset($shipments[(int) $id_shipment]['qty']) ? $shipments[(int) $id_shipment]['qty'] : 0),
                'group'      => (isset($shipments[(int) $id_shipment]['group']) ? $shipments[(int) $id_shipment]['group'] : 0),
                'shipped'    => (isset($shipments[(int) $id_shipment]['shipped']) ? $shipments[(int) $id_shipment]['shipped'] : 0),
                'equipments' => (isset($shipments[(int) $id_shipment]['equipments']) ? $shipments[(int) $id_shipment]['equipments'] : array()),
            );
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

            if ($this->isProductSerialisable()) {
                $shipments = $this->getData('shipments');
                if (is_array($shipments) && isset($shipments[(int) $id_shipment]['equipments']) && is_array($shipments[(int) $id_shipment]['equipments'])) {

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
                }
            } else {
                $product = $this->getProduct();
                if ($product->getData('fk_product_type') === 0) {
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
                    $qty += (float) $this->getShippedQty($id_shipment);
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
                foreach ($returned_equipments as $id_equipment) {
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

    // Getters Array:

    public function getSelectShipmentsArray()
    {
        $shipments = array();

        $commande = $this->getParentInstance();

        if (BimpObject::objectLoaded($commande)) {
            $cs = BimpObject::getInstance('bimplogistique', 'BL_CommandeShipment');
            foreach ($cs->getList(array(
                'id_commande_client' => (int) $commande->id,
                'status'             => 1
            )) as $row) {
                $shipments[(int) $row['id']] = 'Expédition n°' . $row['num_livraison'];
            }
        }

        return $shipments;
    }

    public function getCommandesFournisseursArray()
    {
        $commandes = array(
            'new' => 'Nouvelle commande'
        );

        if ($this->isLoaded()) {
            $commande = $this->getParentInstance();

            if (BimpObject::objectLoaded($commande)) {
                $id_entrepot = (int) BimpTools::getPostFieldValue('id_entrepot', (int) $commande->getData('entrepot'));

                if ($id_entrepot) {
                    $type_price = BimpTools::getPostFieldValue('type_price', 1);
                    $id_fourn = 0;
                    switch ($type_price) {
                        case 1:
                            $id_price = (int) BimpTools::getPostFieldValue('id_fourn_price', 0);
                            if ($id_price) {
                                $fournPrice = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ProductFournisseurPrice', $id_price);
                                if ($fournPrice->isLoaded()) {
                                    $id_fourn = (int) $fournPrice->getData('fk_soc');
                                }
                            }
                            break;

                        case 2:
                            $id_fourn = (int) BimpTools::getPostFieldValue('id_fourn', 0);
                            break;
                    }

                    if ($id_fourn) {
                        $sql = 'SELECT cf.rowid as id, cf.ref, cf.date_creation as date, s.nom FROM ' . MAIN_DB_PREFIX . 'commande_fournisseur cf';
                        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'commande_fournisseur_extrafields cfe ON cf.rowid = cfe.fk_object';
                        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'societe s ON s.rowid = cf.fk_soc';
                        $sql .= ' WHERE cf.fk_soc = ' . (int) $id_fourn . ' AND cf.fk_statut = 0 AND cfe.entrepot = ' . (int) $id_entrepot;
                        $sql .= ' ORDER BY cf.rowid DESC';

                        $rows = $this->db->executeS($sql);
                        if (!is_null($rows) && count($rows)) {
                            foreach ($rows as $obj) {
                                $DT = new DateTime($obj->date);
                                $commandes[(int) $obj->id] = $obj->nom . ' ' . $obj->ref . ' - Créée le ' . $DT->format('d / m / Y à H:i');
                            }
                        }
                    }
                }
            }
        }

        return $commandes;
    }

    public function getClientEquipmentsArray($return_available_only = true)
    {
        $commande = $this->getParentInstance();

        if (BimpObject::objectLoaded($commande) && (int) $this->id_product) {
            $items = BimpCache::getSocieteProductEquipmentsArray((int) $commande->getData('fk_soc'), (int) $this->id_product);

            if ($return_available_only) {
                foreach ($items as $id_equipment => $label) {
                    $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                    if (!BimpObject::objectLoaded($equipment)) {
                        unset($items[$id_equipment]);
                        continue;
                    }

                    if (!(int) $equipment->getData('return_available')) {
                        unset($items[$id_equipment]);
                    }
                }
            }

            return $items;
        }

        return array();
    }

    // Affichages:

    public function displayQties()
    {
        $html = '';
        $total_qty = (float) $this->getFullQty();
        $modif_qty = (float) $this->getData('qty_modif');

        // Qté totale
        $popover = 'Qtés totales';
        if ($modif_qty) {
            $popover .= ' (Qtés commandées +/- qtés modifiées)';
        }
        $html .= '<div style="display: inline-block;">';
        $html .= '<span class="bold bs-popover"' . BimpRender::renderPopoverData($popover) . ' style="margin-right: 15px; padding: 3px 0;">';
        $html .= BimpRender::renderIcon('fas_dolly', 'iconLeft');
        $html .= $total_qty;

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
                            $stocks = $product->getStocksForEntrepot((int) $commande->getData('entrepot'));
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

        if ($shipments_qty > 0) {
            $qty_shipped = (float) $this->getShippedQty();
            $qty_shipped_valid = (float) $this->getShippedQty(null, true);

            if ($qty_shipped_valid <= 0) {
                $class = 'danger';
            } elseif ($qty_shipped < $shipments_qty) {
                $class = 'warning';
            } else {
                $class = 'success';
            }

            $html .= '<span class="bs-popover ' . $class . '" style="display: inline-block; margin-right: 15px; padding: 3px 0;"';
            $html .= BimpRender::renderPopoverData('Qtés ajoutées à une expédition / Qtés expédiées');
            $html .= '>';
            $html .= BimpRender::renderIcon('fas_shipping-fast', 'iconLeft');

            if ($qty_shipped <= 0) {
                $class = 'danger';
            } elseif ($qty_shipped < $shipments_qty) {
                $class = 'warning';
            } else {
                $class = 'success';
            }

            $html .= '<span class="' . $class . '">' . $qty_shipped . '</span>';

            $html .= ' / ';

            if ($qty_shipped_valid <= 0) {
                $class = 'danger';
            } elseif ($qty_shipped_valid < $shipments_qty) {
                $class = 'warning';
            } else {
                $class = 'success';
            }
            $html .= '<span class="' . $class . '">' . $qty_shipped_valid . '</span>';

            $html .= '</span>';
        }

        // Qté facturée: 
        $qty_billed = abs((float) $this->getBilledQty());
        $qty_billed_valid = abs((float) $this->getBilledQty(null, true));
        if ($qty_billed_valid <= 0) {
            $class = 'danger';
        } elseif ($qty_billed_valid < $total_qty) {
            $class = 'warning';
        } else {
            $class = 'success';
        }

        $html .= '<span class="bs-popover ' . $class . '" style="display: inline-block; padding: 3px 0;"';
        $html .= BimpRender::renderPopoverData('Qtés ajoutées à une facture / Qtés facturées');
        $html .= '>';
        $html .= BimpRender::renderIcon('fas_file-invoice-dollar', 'iconLeft');

        if ($qty_billed <= 0) {
            $class = 'danger';
        } elseif ($qty_billed < $total_qty) {
            $class = 'warning';
        } else {
            $class = 'success';
        }
        $html .= '<span class="' . $class . '">' . $qty_billed . '</span>';

        $html .= ' / ';

        if ($qty_billed_valid <= 0) {
            $class = 'danger';
        } elseif ($qty_billed_valid < $total_qty) {
            $class = 'warning';
        } else {
            $class = 'success';
        }
        $html .= '<span class="' . $class . '">' . $qty_billed_valid . '</span>';
        $html .= '</span>';
        $html .= '</div>';

        return $html;
    }

    public function displayReservationsStatus()
    {
        $html = '';

        $qty = (float) $this->getFullQty();

        if ($qty >= 0) {
            $reservations = $this->getReservations();
            $serialisable = 0;
            $product = $this->getProduct();
            if (BimpObject::objectLoaded($product)) {
                $serialisable = $product->isSerialisable();
            }

            if (!empty($reservations)) {
                $html .= '<div class="smallActionsContainer">';
                $html .= '<span class="small-action" onclick="checkAll($(this).parent().parent(), \'.reservation_check\');">';
                $html .= BimpRender::renderIcon('fas_check-square', 'iconLeft') . 'Tout sélectionner';
                $html .= '</span>';
                $html .= '<span class="small-action" onclick="uncheckAll($(this).parent().parent(), \'.reservation_check\');">';
                $html .= BimpRender::renderIcon('far_square', 'iconLeft') . 'Tout désélectionner';
                $html .= '</span>';
                $html .= '</div>';
                $html .= '<table class="bimp_list_table Bimp_Commande_line_reservations_table">';
                $html .= '<tbody>';
                foreach ($reservations as $reservation) {
                    $buttons = $reservation->getListExtraBtn();
                    $html .= '<tr class="Bimp_CommandeLine_reservation_row">';
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
                        $html .= '<br/>' . $reservation->displayEquipment();
                    }
                    $html .= '</td>';
                    $html .= '<td style="width: 80px;">Qté: ' . $reservation->getData('qty') . '</td>';
                    if ($serialisable) {
                        $id_equipment = (int) $reservation->getData('id_equipment');
                        if ((int) $id_equipment) {
                            $html .= '<td>';
                            $html .= 'Exp: ';

                            $id_shipment = (int) $this->getEquipmentIdShipment($id_equipment);
                            if ($id_shipment) {
                                $shipment = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeShipment', $id_shipment);
                                if (BimpObject::objectLoaded($shipment)) {
                                    $html .= 'n°' . $shipment->getData('num_livraison');
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
                                    $html .= '<button class="btn btn-default btn-small" onclick="' . $onclick . '">';
                                    $html .= 'Attribuer' . BimpRender::renderIcon('fas_arrow-circle-right', 'iconRight');
                                    $html .= '</button>';
                                }
                            }

                            $id_facture = (int) $this->getEquipmentIdFacture($id_equipment);
                            if ($id_facture) {
                                $html .= '<br/>Fac: ';
                                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_facture);
                                if (BimpObject::objectLoaded($facture)) {
                                    $html .= $facture->getNomUrl(1, 1, 1, 'full');
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
                }
                $html .= '</tbody>';
                $html .= '</table>';
            }
        } else {
            if ($this->isProductSerialisable()) {
                $equipments = $this->getData('equipments_returned');

                $html .= '<table class="bimp_list_table">';
                $html .= '<tbody>';
                $html .= '<tr>';
                $html .= '<td><strong>Equipements retournés: </strong></td>';
                $html .= '<td colspan="2" style="text-align: right">';

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
                    $html .= '<td colspan="3">';
                    $html .= '<div style="text-align: center">';
                    $html .= BimpRender::renderAlerts('Aucun équipement retourné enregistré', 'info');
                    $html .= '</div>';
                    $html .= '</td>';
                    $html .= '</tr>';
                } else {
                    foreach ($equipments as $id_equipment) {
                        $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                        if (BimpObject::objectLoaded($equipment)) {
                            $html .= '<tr>';
                            $html .= '<td>';
                            $html .= $equipment->getNomUrl(1, 1, 1, 'default');
                            $html .= '</td>';
                            $html .= '<td>';
                            $id_facture = $this->getEquipmentIdFacture($id_equipment);
                            $facture = null;
                            if ($id_facture) {
                                $html .= 'Fac.: ';
                                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_facture);
                                if (BimpObject::objectLoaded($facture)) {
                                    $html .= $facture->getNomUrl(1, 1, 1, 'full');
                                } else {
                                    $html .= BimpRender::renderAlerts('La facture d\'ID ' . $id_facture . ' n\'existe plus');
                                }
                            }
                            $html .= '</td>';
                            $html .= '<td style="text-align: right">';
                            if (!BimpObject::objectLoaded($facture) || !(int) $facture->getData('fk_statut')) {
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

    // Rendus HTML:

    public function renderShipmentQtyInput($id_shipment, $with_total_max = false)
    {
        $html = '';

        $shipments = $this->getData('shipments');

        $shipment_qty = 0;
        if (isset($shipments[(int) $id_shipment]['qty'])) {
            $shipment_qty = (float) $shipments[(int) $id_shipment]['qty'];
        }

        $decimals = 3;

        if ((int) $this->getData('type') === self::LINE_PRODUCT) {
            $product = $this->getProduct();
            if ((int) $product->getData('fk_product_type') === 0) {
                $decimals = 0;
            }
        }

        $max = (float) $this->getShipmentsQty() - (float) $this->getShippedQty() + $shipment_qty;

        if (!$decimals) {
            $max = (int) floor($max);
        }

        $options = array(
            'data'        => array(
                'id_line'   => (int) $this->id,
                'data_type' => 'number',
                'decimals'  => $decimals,
                'unsigned'  => 0,
                'min'       => 0,
                'max'       => $max
            ),
            'extra_class' => 'line_shipment_qty',
            'max_label'   => 1
        );

        if ($with_total_max) {
            $options['data']['total_max_value'] = (float) $this->getShipmentsQty();
            $options['data']['total_max_inputs_class'] = 'line_shipment_qty';
            $options['extra_class'] .= ' total_max';
        }

        $value = (!$with_total_max && !(float) $shipment_qty && !(int) $id_shipment ? $max : $shipment_qty);

        $html .= BimpInput::renderInput('qty', 'line_' . $this->id . '_shipment_' . $id_shipment . '_qty', $value, $options);

        if ($shipment_qty > 0) {
            if ($shipment_qty === 1) {
                $msg = $shipment_qty . ' unité a déjà été assignée à cette expédition.';
            } else {
                $msg = $shipment_qty . ' unités ont déjà été assignées à cette expédition.';
            }

            $msg .= '<br/>Indiquez ici le nombre total d\'unités à assigner.';
            $html .= BimpRender::renderAlerts($msg, 'info');
        }

        return $html;
    }

    public function renderFactureQtyInput($id_facture = 0, $with_total_max = false, $value = null, $max = null)
    {
        $html = '';

        if (is_null($id_facture)) {
            $id_facture = 0;
        }

        $facture_data = $this->getFactureData($id_facture);

        if (isset($facture_data['qty'])) {
            $facture_qty = (float) $facture_data['qty'];
        } else {
            $facture_qty = 0;
        }

        $decimals = 3;

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

        $html .= BimpInput::renderInput('qty', 'line_' . $this->id . '_facture_' . $id_facture . '_qty', $value, $options);

        if ($facture_qty > 0) {
            if ($facture_qty == 1) {
                $msg = $facture_qty . ' unité a déjà été assignée à cette facture.';
            } else {
                $msg = $facture_qty . ' unités ont déjà été assignées à cette facture.';
            }

            $msg .= '<br/>Indiquez ici le nombre total d\'unités à assigner.';
            $html .= BimpRender::renderAlerts($msg, 'info');
        }

        return $html;
    }

    public function renderShipmentEquipmentsInput($id_shipment, $input_name = null, $qty_input_name = null)
    {
        $html = '';

        if (is_null($input_name)) {
            $input_name = 'line_' . $this->id . '_shipment_' . $id_shipment . '_equipments';
        }

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

        if (count($equipments)) {
            foreach ($equipments as $id_equipment) {
                if (array_key_exists((int) $id_equipment, $items)) {
                    continue;
                }
                $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $id_equipment);
                if (BimpObject::objectLoaded($equipment)) {
                    $items[$id_equipment] = $equipment->getData('serial');
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

        if (isset($factureData['equipments'])) {
            foreach ($factureData['equipments'] as $id_equipment) {
                $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $id_equipment);
                if (BimpObject::objectLoaded($equipment)) {
                    $items[$id_equipment] = $equipment->getData('serial');
                    $values[] = $id_equipment;
                }
            }
        }

        $equipments = $this->getEquipementsToAttributeToFacture();

        if (count($equipments)) {
            foreach ($equipments as $id_equipment) {
                if (array_key_exists((int) $id_equipment, $items)) {
                    continue;
                }
                $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $id_equipment);
                if (BimpObject::objectLoaded($equipment)) {
                    $items[$id_equipment] = $equipment->getData('serial');
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
                if ((float) $this->getFullQty() < 0) {
                    $options['max_input_abs'] = 1;
                }
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
        $use_group = false;

        if ((int) $this->getData('type') === ObjectLine::LINE_PRODUCT) {
            $product = $this->getProduct();
            if (BimpObject::objectLoaded($product)) {
                if ((int) $product->getData('fk_product_type') === 0 && !$product->isSerialisable()) {
                    $use_group = true;
                }
            }
        }

        if (!BimpObject::objectLoaded($commande)) {
            $html .= BimpRender::renderAlerts('ID de la commande associée absent');
        } else {
            $shipments = $commande->getChildrenObjects('shipments');
            $line_shipments = $this->getData('shipments');

            if (count($shipments)) {
                $html .= '<div id="commande_line_' . $this->id . '_shipments_form' . '" class="commande_shipments_form line_shipment_qty_container">';
                $html .= '<table class="bimp_list_table">';
                $html .= '<thead>';
                $html .= '<tr>';
                $html .= '<th style="width: 400px;">Expédition</th>';
                $html .= '<th>Qté</th>';

                if ($use_group) {
                    $html .= '<th>Grouper les articles</th>';
                }

                $html .= '</tr>';
                $html .= '</thead>';
                $html .= '<tbody>';

                foreach ($shipments as $shipment) {
                    $html .= '<tr id="commande_line_shipment_' . $shipment->id . '_row" class="shipment_row" data-id_shipment="' . $shipment->id . '">';
                    $html .= '<td style="width: 400px;">';
                    $card = new BC_Card($shipment, null, 'default');
                    $html .= $card->renderHtml();
                    $html .= '</td>';

                    $qty = isset($line_shipments[(int) $shipment->id]['qty']) ? (float) $line_shipments[(int) $shipment->id]['qty'] : 0;
                    $group = isset($line_shipments[(int) $shipment->id]['group']) ? (float) $line_shipments[(int) $shipment->id]['group'] : 0;

                    if ((int) $shipment->getData('status') === BL_CommandeShipment::BLCS_BROUILLON) {
                        $html .= '<td>';
                        $html .= $this->renderShipmentQtyInput((int) $shipment->id, true);
                        $html .= '</td>';
                        if ($use_group) {
                            $html .= '<td>';
                            $html .= BimpInput::renderInput('toggle', 'shipment_' . $shipment->id . '_group', $group, array(
                                        'extra_class' => 'line_shipment_group'
                            ));
                            $html .= '</td>';
                        }
                    } else {
                        $html .= '<td>';
                        $html .= '<input type="hidden" name="line_' . $this->id . '_shipment_' . $shipment->id . '_qty" value="' . $qty . '" class="line_shipment_qty total_max"/>';
                        $html .= $qty;
                        $html .= '</td>';
                        if ($use_group) {
                            $html .= '<td>';
                            if ($group) {
                                $html .= '<span class="success">OUI</span>';
                            } else {
                                $html .= '<span class="danger">NON</span>';
                            }
                            $html .= '</td>';
                        }
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
        }
        $html .= BimpRender::renderDropDownButton('Status sélectionnés', $items, array(
                    'icon' => 'far_check-square'
        ));

        return $html;
    }

    public function renderCommandeFournQtyInput()
    {
        $html = '';

        $max = $this->getReservationsQties(0);

        if ($max > 0) {
            $html .= BimpInput::renderInput('qty', 'qty', $max, array(
                        'data'      => array(
                            'data_type' => 'number',
                            'min'       => 1,
                            'max'       => $max,
                            'decimals'  => 0
                        ),
                        'max_label' => 1
            ));
        } else {
            $html .= '<input type="hidden" name="qty" value="0"/>';
            $html .= BimpRender::renderAlerts('Il n\'y a aucun produit au statut "A traiter"');
        }

        return $html;
    }

    public function renderInvoicesView()
    {
        $html = '';

        $commande = $this->getParentInstance();
        $product = null;
        $isSerialisable = false;

        if ((int) $this->getData('type') === ObjectLine::LINE_PRODUCT) {
            $product = $this->getProduct();
            if (BimpObject::objectLoaded($product) && $product->isSerialisable()) {
                $isSerialisable = true;
            }
        }

        if (!BimpObject::objectLoaded($commande)) {
            $html .= BimpRender::renderAlerts('ID de la commande associée absent');
        } else {
            $line_factures = $this->getData('factures');
            $asso = new BimpAssociation($commande, 'factures');
            $factures_list = $asso->getAssociatesList();

            if (!empty($factures_list)) {
                $html .= '<div id="commande_line_' . $this->id . '_factures_form' . '" class="commande_factures_form line_facture_qty_container">';
                $html .= '<form>';
                $html .= '<table class="bimp_list_table">';
                $html .= '<thead>';
                $html .= '<tr>';
                $html .= '<th style="width: 400px;">Facture</th>';
                $html .= '<th>Qté</th>';
                if ($isSerialisable) {
                    $html .= '<th>Equipements</th>';
                }
                $html .= '</tr>';
                $html .= '</thead>';
                $html .= '<tbody>';

                foreach ($line_factures as $id_facture => $facture_data) {
                    $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_facture);

                    $html .= '<tr id="commande_line_facture_' . $id_facture . '_row" class="facture_row" data-id_facture="' . $id_facture . '" data-facture_ref="' . $facture->getData('facnumber') . '">';
                    $html .= '<td style="width: 400px;">';
                    $card = new BC_Card($facture, null, 'light');
                    $card->params['view_btn'] = 0;
                    $html .= $card->renderHtml();
                    $html .= '</td>';

                    if ((int) $facture->getData('fk_statut') === (int) Facture::STATUS_DRAFT) {
                        $html .= '<td>';
                        $html .= $this->renderFactureQtyInput($id_facture, true);
                        $html .= '</td>';
                    } else {
                        $html .= '<td>';
                        $html .= '<input type="hidden" name="line_' . $this->id . '_facture_' . $facture->id . '_qty" value="' . $facture_data['qty'] . '" class="line_facture_qty total_max"/>';
                        $html .= $facture->getData('facnumber') . ': ' . $facture->getData('fk_statut') . ' - ' . $facture_data['qty'];
                        $html .= '</td>';
                    }


                    if ($isSerialisable) {
                        $html .= '<td>';
                        if (!isset($facture_data['equipments']) || empty($facture_data['equipments'])) {
                            $html .= '<span class="warning">Aucun équipement attribué à cette facture</span>';
                        } else {
                            if ((int) $facture->getData('fk_statut') === (int) Facture::STATUS_DRAFT) {
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
                                        $html .= ' - ' . $equipment->getNomUrl(1, 1, 1, 'default');
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

                $html .= '</div>';
            } else {
                $html .= BimpRender::renderAlerts('Aucune facture créée pour cette commande');
                $html .= '<div class="buttonsContainer align-center">';
                $onclick = $commande->getJsActionOnclick('linesFactureQties', array(
                    'new_facture'       => 1,
                    'id_client'         => (int) $commande->getData('fk_soc'),
                    'id_contact'        => (int) $commande->dol_object->contactid,
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
                foreach (BimpTools::getValue('fields/equipments', array()) as $id_equipment) {
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

    // Traitements réservations:

    public function checkReservations()
    {
        $errors = array();

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
                if ((int) $this->getData('type') === self::LINE_PRODUCT) {
                    $product = $this->getProduct();
                    if (!BimpObject::objectLoaded($product)) {
                        $errors[] = 'ID du produit absent';
                    } elseif ((int) $product->getData('fk_product_type') === 0) {

                        $reserved_qties = $this->getReservedQties();

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
                                $res_errors = array_merge($res_errors, $res_warnings);

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
                                    $res_errors = array_merge($res_errors, $res_warnings);
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
                $res_errors = $reservation->setNewStatus(200, 1, (int) $id_equipment);
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

        if (!$this->isLoaded()) {
            $errors[] = 'ID de la ligne de commande fournisseur absent';
        }

        $reservations = $this->getReservations('status', 'asc', 100);

        $remain_qty = (int) $qty;

        if ($this->isProductSerialisable()) {
            $new_status = 101;
        }

        foreach ($reservations as $reservation) {
            if (!$remain_qty) {
                break;
            }

            if ($remain_qty > (int) $reservation->getData('qty')) {
                $res_qty = (int) $reservation->getData('qty');
            } else {
                $res_qty = $remain_qty;
            }
            $remain_qty -= $res_qty;

            $res_errors = $reservation->setNewStatus($new_status, $res_qty);
            if (count($res_errors)) {
                $errors[] = BimpTools::getMsgFromArray($res_errors, 'Des erreurs sont survenues lors de la mise à jour du statut de la réservation d\'ID ' . $reservation->id);
            }
        }

        return $errors;
    }

    // Traitements expéditions: 

    public function setShipmentData(BL_CommandeShipment $shipment, $data, &$warnings = array())
    {
        $errors = array();

        if (BimpObject::objectLoaded($shipment)) {
            $shipments = $this->getData('shipments');

            if (!is_array($shipments)) {
                $shipments = array();
            }

            if (!isset($shipments[(int) $shipment->id])) {
                $shipments[(int) $shipment->id] = array(
                    'qty' => (float) $data['qty']
                );
            } else {
                $shipments[(int) $shipment->id]['qty'] = (float) $data['qty'];
            }

            // Grouper les articles: 
            $group = null;

            if ((int) $this->getData('type') === self::LINE_PRODUCT) {
                $product = $this->getProduct();

                if (BimpObject::objectLoaded($product)) {
                    if ((int) $product->getData('fk_product_type') === 0 && !$product->isSerialisable()) {
                        $group = isset($data['group_articles']) ? (int) $data['group_articles'] : 0;
                    }
                }
            }

            if (!is_null($group)) {
                $shipments[(int) $shipment->id]['group'] = $group;
            } elseif (isset($shipments[(int) $shipment->id]['group'])) {
                unset($shipments[(int) $shipment->id]['group']);
            }

            // Vérification des quantités: 
            $total_qty_shipped = 0;
            foreach ($shipments as $id_shipment => $shipment_data) {
                if ((int) $id_shipment === $shipment->id) {
                    $total_qty_shipped += $data['qty'];
                } else {
                    $total_qty_shipped += (isset($shipment_data['qty']) ? (float) $shipment_data['qty'] : 0);
                }
            }

            if ($total_qty_shipped > (float) $this->getShipmentsQty()) {
                $errors[] = 'Le nombre total d\'unités ajoutées à des expéditions (' . $total_qty_shipped . ') dépasse le nombre total d\'unités expédiables pour cette ligne de commande (' . $this->getShipmentsQty() . ')';
            }

            // Equipements:
            $equipments = array();
            $product = $this->getProduct();
            if (isset($data['equipments']) && BimpObject::objectLoaded($product) && $product->isSerialisable()) {
                if (is_array($data['equipments']) && count($data['equipments']) > (int) $data['qty']) {
                    $errors[] = 'Veuillez retirer ' . (count($data['equipments']) - (int) $data['qty']) . ' équipement(s)';
                } else {
                    $current_equipments = isset($shipments[(int) $id_shipment]['equipments']) ? $shipments[(int) $id_shipment]['equipments'] : array();
                    $available_equipments = $this->getEquipementsToAttributeToShipment();

                    foreach ($data['equipments'] as $id_equipment) {
                        $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                        if (!BimpObject::objectLoaded($equipment)) {
                            $errors[] = 'L\'équipement d\'ID ' . $id_equipment . ' n\'existe pas';
                        } elseif (!in_array((int) $id_equipment, $current_equipments) && !in_array((int) $id_equipment, $available_equipments)) {
                            $errors[] = 'L\'équipement ' . $equipment->getData('serial') . ' (ID ' . $id_equipment . ') n\'est plus disponible pour cette expédition';
                        } else {
                            $equipments[] = $id_equipment;
                        }
                    }
                }
            }

            $shipments[(int) $id_shipment]['equipments'] = $equipments;

            // Mise à jour: 
            if (!count($errors)) {
                $this->set('shipments', $shipments);
                $errors = $this->update($warnings, true);
            }
        } else {
            $errors[] = 'Expédition invalide';
        }

        return $errors;
    }

    public function setShipmentsData($shipments_data, &$warnings = array())
    {
        $errors = array();

        $shipments = $this->getData('shipments');

        foreach ($shipments_data as $data) {
            $id_shipment = isset($data['id_shipment']) ? (int) $data['id_shipment'] : 0;
            if (!$id_shipment) {
                continue;
            }

            $shipment = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeShipment', $id_shipment);
            $shipment_editable = ($shipment->getData('status') === Bl_CommandeShipment::BLCS_BROUILLON);
            if (!BimpObject::objectLoaded($shipment)) {
                $errors[] = 'L\'expédition d\'ID ' . $id_shipment . ' n\'existe plus';
                continue;
            }


            if (!isset($shipments[$id_shipment])) {
                $shipments[$id_shipment] = array(
                    'qty' => isset($data['qty']) ? (float) $data['qty'] : 0
                );
            } elseif ($shipment_editable) {
                $shipments[$id_shipment]['qty'] = isset($data['qty']) ? (float) $data['qty'] : 0;
            }

            if (isset($data['group']) && $shipment_editable) {
                $shipments[$id_shipment]['group'] = (int) $data['group'];
            }
        }

        $total_qty_shipped = 0;
        foreach ($shipments as $data) {
            $total_qty_shipped += (float) $data['qty'];
        }

        if ($total_qty_shipped > (float) $this->getShipmentsQty()) {
            $errors[] = 'Les quantités totales ajoutées à des expéditions dépasse le nombre d\'unités expédiables pour cette ligne de commande. Veuillez corriger';
        }

        if (!count($errors)) {
            $this->set('shipments', $shipments);
            $errors = $this->update($warnings, true);
        }

        return $errors;
    }

    public function setShipmentShipped(BL_CommandeShipment $shipment)
    {
        $errors = array();

        if (!$this->isReadyToShip($shipment->id, $errors)) {
            return $errors;
        }

        global $user;
        $shipment_data = $this->getShipmentData($shipment->id);
        $commande = $this->getParentInstance();

        $id_client = (int) $commande->getData('fk_soc');
        $id_contact = (int) $shipment->getcontact();
        $id_entrepot = (int) $shipment->getData('id_entrepot');


        if (!BimpObject::objectLoaded($commande)) {
            return array('ID de la commande client absent ou invalide');
        }

        if (!$id_entrepot) {
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

            // traitement des réservations: 

            $stock_label = 'Expédition n°' . $shipment->getData('num_livraison') . ' pour la commande client "' . $commande->getRef() . '"';
            $codemove = dol_print_date(dol_now(), '%y%m%d%H%M%S');

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
                                $res_errors = $reservation->setNewStatus(300, 1, $id_equipment);
                                if (count($res_errors)) {
                                    $errors[] = BimpTools::getMsgFromArray($res_errors, 'Echec de la mise à jour du statut pour l\'équipement ' . $equipment->getData('serial'));
                                } else {
                                    // Mise à jour de l'emplacement de l'équipement:  
                                    $place = BimpObject::getInstance('bimpequipment', 'BE_Place');
                                    $place_errors = $place->validateArray(array(
                                        'id_equipment' => $id_equipment,
                                        'type'         => BE_Place::BE_PLACE_CLIENT,
                                        'id_client'    => (int) $id_client,
                                        'id_contact'   => (int) $id_contact,
                                        'infos'        => $stock_label,
                                        'date'         => date('Y-m-d H:i:s'),
                                        'code_mvt'     => $codemove
                                    ));

                                    if (!count($place_errors)) {
                                        $place_errors = $place->create();
                                    }

                                    if (count($place_errors)) {
                                        $errors[] = BimpTools::getMsgFromArray($place_errors, 'Echec de la création du nouvel emplacement pour l\'équipement ' . $equipment->getData('serial') . ' (ID ' . $id_equipment . ')');
                                        dol_syslog('Echec de la création du nouvel emplacement pour l\'équipement ' . $equipment->getData('serial') . ' (ID ' . $id_equipment . ') - Commande client: ' . $commande->getRef() . '(ID ' . $commande->id . ')', LOG_ERR);
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
                    $res_errors = $reservation->setNewStatus(300, $qty);
                    if (count($res_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($res_errors, 'Echec de la mise à jour du statut de la réservation d\'ID ' . $reservation->id);
                    } else {
                        // traitement des stocks
                        // todo: Voir pour utiliser un code_mov spécifique (utile en cas d'annulation pour vérifier que le mvt de stock a été correctement effectué.
                        if ($product->dol_object->correct_stock($user, $id_entrepot, $shipment_data['qty'], 1, $stock_label, 0, $codemove, 'commande', $commande->id) <= 0) {
                            $msg = 'Echec de la mise à jour des stocks pour le produit "' . $product->dol_object->label . '" (ID ' . $product->id . ', quantités à retirer: ' . $shipment_data['qty'] . ')';
                            $errors[] = $msg;
                            dol_syslog($msg, LOG_ERR);
                        }
                    }
                }
            }
        }

        // Mise à jour des données de l'expédition: 

        if (!count($errors)) {
            $shipments = $this->getData('shipments');
            $shipments[(int) $shipment->id]['shipped'] = 1;
            $this->set('shipments', $shipments);
            $warnings = array();
            $up_errors = $this->update($warnings, true);
            if (count($up_errors)) {
                $errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec de la mise à jour de la ligne de commande client');
            }
        }

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
        $codemove = dol_print_date(dol_now(), '%y%m%d%H%M%S');

        if (BimpObject::objectLoaded($product) && $product->isSerialisable()) {

            // Traitement des réservations: 
            $reservations = $this->getReservations('status', 'asc', '300');

            foreach ($reservations as $reservation) {
                $id_equipment = (int) $reservation->getData('id_equipment');
                if ($id_equipment) {
                    if (in_array($id_equipment, $shipment_data['equipments'])) {
                        $equipment = $reservation->getChildObject('equipment');
                        if (!BimpObject::objectLoaded($equipment)) {
                            $errors[] = 'L\'équipement d\'ID ' . $id_equipment . ' n\'existe pas';
                        } else {
                            $res_errors = $reservation->setNewStatus(200, 1, $id_equipment);
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
                                    'code_mvt'     => $codemove
                                ));

                                if (!count($place_errors)) {
                                    $place_errors = $place->create();
                                }

                                if (count($place_errors)) {
                                    $errors[] = BimpTools::getMsgFromArray($place_errors, 'Echec de la création du nouvel emplacement pour l\'équipement ' . $equipment->getData('serial') . ' (ID ' . $id_equipment . ')');
                                    dol_syslog('Echec de la création du nouvel emplacement pour l\'équipement ' . $equipment->getData('serial') . ' (ID ' . $id_equipment . ') - Commande client: ' . $commande->getRef() . '(ID ' . $commande->id . ')', LOG_ERR);
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
                $res_errors = $reservation->setNewStatus(200, $qty);
                if (count($res_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($res_errors, 'Echec de la mise à jour du statut de la réservation d\'ID ' . $reservation->id);
                } else {
                    // traitement des stocks
                    // todo: Voir pour utiliser un code_mov spécifique (utile en cas d'annulation pour vérifier que le mvt de stock a été correctement effectué.
                    if ($product->dol_object->correct_stock($user, $id_entrepot, $shipment_data['qty'], 0, $stock_label, 0, $codemove, 'commande', $commande->id) <= 0) {
                        $msg = 'Echec de la mise à jour des stocks pour le produit "' . $product->dol_object->label . '" (ID ' . $product->id . ', quantités à ajouter: ' . $shipment_data['qty'] . ')';
                        $errors[] = $msg;
                        dol_syslog($msg, LOG_ERR);
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
            $this->set('shipments', $shipments);
            $warnings = array();
            $up_errors = $this->update($warnings, true);
            if (count($up_errors)) {
                $errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec de la mise à jour de la ligne de commande client');
            }
        }

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

                if ((int) $new_qty > (int) $remain) {
                    $errors[] = 'Nouvelles quantités d\'unités assignées à l\'expédition invalides.<br/>Veuillez retirer ' . $new_qty - $remain . ' unité(s)';
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
                $reservation = BimpCache::findBimpObjectInstance('bimpreservation', 'BR_Reservation', array(
                            'id_commande_client_line' => (int) $this->id,
                            'id_equipment'            => (int) $id_equipment
                                ), false, false);

                if (!BimpObject::objectLoaded($reservation)) {
                    $errors[] = 'L\'équipement ' . $id_equipment . ' - ns: ' . $equipments->getData('serial') . ' n\'est pas associé à cette ligne de commande';
                }
            }
        }

        if (count($errors)) {
            return $errors;
        }

        if (!isset($shipment_data['equipments'])) {
            $shipment_data['equipments'] = array();
        }

        $remain_qty = (int) $shipment_data['qty'] - count($shipment_data['equipments']);

        if (!$remain_qty) {
            $errors[] = 'Il n\'y a plus d\'équipements à assigner à cette expédition pour cette ligne de commande';
        } elseif (count($equipments) > (int) $remain_qty) {
            $errors[] = 'Il ne reste que ' . $remain_qty . ' équipements à assigner à cette expédition.<br/>Veuillez retirer ' . ($remain_qty - count($equipments)) . ' unité(s).';
        } else {
            $shipment_data['equipments'] = array_merge($shipment_data['equipments'], $equipments);

            $shipments[(int) $id_shipment] = $shipment_data;

            $this->set('shipments', $shipments);
            $warnings = array();
            $errors = $this->update($warnings, true);
            $errors = array_merge($errors, $warnings);
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
                            if (!is_array($returned_equipments) || !in_array((int) $id_equipment, $returned_equipments)) {
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

        $total_qty = 0;

        foreach ($factures_data as $facture_data) {
            if (isset($facture_data['id_facture'])) {
                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $facture_data['id_facture']);
                if (!BimpObject::objectLoaded($facture)) {
                    $errors[] = 'La facture d\'ID ' . $facture_data['id_facture'] . ' n\'existe pas';
                } else {
                    if (!isset($facture_data['qty'])) {
                        $facture_data['qty'] = 0;
                    }

                    $total_qty += (float) $facture_data['qty'];
                }
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
            $qty = (float) $facture_data['qty'];
            $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $facture_data['id_facture']);
            $equipments = isset($facture_data['equipments']) ? $facture_data['equipments'] : array();

            // Vérification qté et équipements facture: 
            $check_errors = $this->checkFactureData($qty, $equipments, $facture->id);

            if (count($check_errors)) {
                $errors[] = BimpTools::getMsgFromArray($check_errors, 'Facture "' . $facture->getData('facnumber') . '"');
                continue;
            }

            // Mise à jour de la ligne de facture: 
            $fac_errors = $commande->addLinesToFacture((int) $facture_data['id_facture'], array(
                $this->id => (float) $facture_data['qty']
                    ), array(
                $this->id => $equipments
            ));

            if (count($fac_errors)) {
                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $facture_data['id_facture']);
                $errors[] = BimpTools::getMsgFromArray($fac_errors, 'Erreurs lors de la mise à jour de la facture "' . $facture->getData('facnumber') . '" (ID: ' . $facture->id . ')');
                continue;
            }

            // Mise à jour de la ligne de commande: 
            $line_warnings = array();
            $line_errors = $this->setFactureData((int) $facture->id, $qty, $equipments, $line_warnings, false);
            if (count($line_errors)) {
                $errors[] = BimpTools::getMsgFromArray($line_errors, 'Erreurs lors de l\'enregistrement des données pour la facture "' . $facture->getData('facnumber') . '"');
            }
            if (count($line_warnings)) {
                $warnings[] = BimpTools::getMsgFromArray($line_warnings, 'Erreurs lors de l\'enregistrement des données pour la facture "' . $facture->getData('facnumber') . '"');
            }
        }

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

        $this->set('factures', $factures);
        return $this->update($warnings, true);
    }

    public function onFactureDelete($id_facture)
    {
        if ($this->isLoaded()) {
            $factures = $this->getData('factures');

            if (isset($factures[(int) $id_facture])) {
                unset($factures[(int) $id_facture]);

                $this->updateField('factures', $factures);
            }
        }
    }

    // Traitements divers: 

    public function setPrixAchat($pa_ht)
    {
        $errors = array();

        if ($this->isLoaded()) {
            if ((float) $this->pa_ht !== (float) $pa_ht) {
                $this->pa_ht = $pa_ht;
                $this->id_fourn_price = 0;

                $errors = $this->forceUpdateLine();

                $facture_line = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureLine');
                $factures_lines_list = $facture_line->getList(array(
                    'linked_object_name' => 'commande_line',
                    'linked_id_object'   => (int) $this->id
                        ), null, null, 'id', 'asc', 'array', array('id'));

                if (!is_null($factures_lines_list)) {
                    foreach ($factures_lines_list as $item) {
                        $facture_line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureLine', (int) $item['id']);
                        if (BimpObject::objectLoaded($facture_line)) {
                            if ($facture_line->isParentEditable()) {
                                // Màj de la ligne de facture: 
                                $facture_line->pa_ht = $pa_ht;
                                $facture_line->id_fourn_price = 0;
                                $w = array();
                                $fac_errors = $facture_line->update($w, true);
                                $fac_errors = array_merge($fac_errors, $w);

                                if (count($fac_errors)) {
                                    $facture = $facture_line->getParentInstance();
                                    $fac_label = '';
                                    if (BimpObject::objectLoaded($facture)) {
                                        $fac_label = ' (facture "' . $facture->getData('facnumber') . '")';
                                    }
                                    $errors[] = BimpTools::getMsgFromArray($fac_errors, 'Echec de la mise à jour du prix d\'achat pour la ligne de facture n°' . $facture_line->getData('position') . $fac_label);
                                }
                            } else {
                                // Ajout d'un correctif:     
                                BimpObject::loadClass('bimpcore', 'BimpCorrectif');

                                $diff = (float) $pa_ht - (float) $facture_line->pa_ht;
                                $fac_errors = BimpCorrectif::setValue($facture_line, 'pa_ht', $diff);

                                if (count($fac_errors)) {
                                    $facture = $facture_line->getParentInstance();
                                    $fac_label = '';
                                    if (BimpObject::objectLoaded($facture)) {
                                        $fac_label = ' (facture "' . $facture->getData('facnumber') . '")';
                                    }
                                    $errors[] = BimpTools::getMsgFromArray($fac_errors, 'Echec de l\'ajout d\'un correctif du prix d\'achat pour la ligne de facture n°' . $facture_line->getData('position') . $fac_label);
                                }
                            }
                        }
                    }
                }
            }
        } else {
            $errors[] = 'ID de la ligne de commande client absent. Mise à jour du prix d\'achat impossible';
        }

        return $errors;
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
                if ((int) $place->getData('type') !== BE_Place::BE_PLACE_CLIENT ||
                        (int) $place->getData('id_client') !== (int) $commande->getData('fk_soc')) {
                    $errors[] = 'Emplacement actuel de l\'équipement "' . $equipment->getData('serial') . '" invalide';
                }
            }

            if (!(int) $equipment->getData('return_available')) {
                $errors[] = 'L\'équipement "' . $equipment->getData('serial') . '" est marqué comme non disponible pour un retour (un retour est probablement déjà en cours)';
            }
        }

        return $errors;
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
            $errors = $this->setShipmentsData($data['shipments'], $warnings);
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
                } elseif ((int) $product->getData('fk_product_type') !== 0) {
                    $errors[] = 'Cette opération n\'est pas possible pour les services';
                }
            }

            $max = $this->getReservationsQties(0);
            $qty = isset($data['qty']) ? (int) $data['qty'] : 0;
            $id_commande_fourn = isset($data['id_commande_fourn']) ? $data['id_commande_fourn'] : 0;
            $commande = $this->getParentInstance();

            if ($id_commande_fourn !== 'new') {
                $id_commande_fourn = (int) $id_commande_fourn;
            }

            if (!$qty) {
                $errors[] = 'Veuillez saisir une quantité supérieure à 0';
            } elseif ($qty > $max) {
                $errors[] = 'Il n\'y a que ' . $max . ' unité(s) au statut "A traiter". Veuillez corriger les quantités';
            } elseif (!$id_commande_fourn) {
                $errors[] = 'Veuillez sélectionner une commande fournisseur (' . $id_commande_fourn . ')';
            } elseif (!BimpObject::objectLoaded($commande)) {
                $errors[] = 'ID de la commande client absent';
            } else {
                if ((int) $id_commande_fourn) {
                    $commande_fourn = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', (int) $id_commande_fourn);
                    if (!BimpObject::objectLoaded($commande_fourn)) {
                        $errors[] = 'La commande fournisseur d\'ID ' . $id_commande_fourn . ' n\'existe pas';
                    } else {
                        if ((int) $commande_fourn->getData('fk_statut') !== 0) {
                            $errors[] = 'La commande fournisseur sélectionnée n\'est plus modifiable';
                        }
                    }
                }

                if (!count($errors) && !BimpObject::objectLoaded($line)) {
                    $id_entrepot = isset($data['id_entrepot']) ? (int) $data['id_entrepot'] : (int) $commande->getData('entrepot');

                    $type_price = isset($data['type_price']) ? (int) $data['type_price'] : 1;
                    $id_fourn = 0;
                    $pa_ht = 0;
                    $tva_tx = 0;

                    $line = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFournLine');

                    $line->validateArray(array(
                        'type'               => ObjectLine::LINE_PRODUCT,
                        'deletable'          => 0,
                        'editable'           => 0,
                        'remisable'          => 1,
                        'linked_id_object'   => (int) $this->id,
                        'linked_object_name' => 'commande_line'
                    ));
                    $line->id_product = (int) $product->id;
                    $line->qty = (int) $qty;


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
                                        $line->id_fourn_price = $id_fourn_price;
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

                                $line->id_fourn_price = 0;
                                $line->tva_tx = $tva_tx;
                                $line->pa_ht = $pa_ht;
                            }
                            break;
                    }

                    if (!count($errors)) {
                        if ($id_commande_fourn === 'new') {
                            $commande_fourn = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFourn');
                            $commande_fourn->validateArray(array(
                                'entrepot' => $id_entrepot,
                                'fk_soc'   => $id_fourn
                            ));

                            $comm_warnings = array();
                            $comm_errors = $commande_fourn->create($warnings, true);

                            $comm_errors = array_merge($comm_errors, $comm_warnings);
                            if (count($comm_errors)) {
                                $errors[] = BimpTools::getMsgFromArray($comm_errors, 'Des erreurs sont survenues lors de la création de la commande fournisseur');
                            }
                        } else {
                            $commande_fourn = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFourn', $id_commande_fourn);
                            if (!$commande_fourn->isLoaded()) {
                                $errors[] = 'La commande fournisseur sélectionnée n\'existe plus (ID ' . $id_commande_fourn . ')';
                            }
                        }

                        if (!count($errors)) {
                            $line->set('id_obj', (int) $commande_fourn->id);
                            $line_errors = $line->create($line_warnings, true);

                            if (count($line_warnings)) {
                                $errors[] = BimpTools::getMsgFromArray($line_warnings, 'Des erreurs sont survenues suite à la création de la ligne de commande fournisseur');
                            }

                            if (count($line_errors)) {
                                $errors[] = BimpTools::getMsgFromArray($line_errors, 'Des erreurs sont survenues durant la création de la ligne de commande fournisseur');
                            } else {
                                if (isset($data['remise_pa']) && (float) $data['remise_pa']) {
                                    // Création de la remise sur le prix d'achat: 
                                    $remise = BimpObject::getInstance('bimpcommercial', 'ObjectLineRemise');
                                    $rem_errors = $remise->validateArray(array(
                                        'id_object_line' => (int) $line->id,
                                        'object_type'    => 'commande_fournisseur',
                                        'label'          => (isset($data['remise_pa_label']) ? $data['remise_pa_label'] : ''),
                                        'type'           => 1,
                                        'percent'        => (float) $data['remise_pa']
                                    ));

                                    if (!count($rem_errors)) {
                                        $rem_warnings = array();
                                        $rem_errors = $remise->create($rem_warnings, true);
                                        $rem_errors = array_merge($rem_errors, $rem_warnings);
                                    }

                                    if (count($rem_errors)) {
                                        $warnings[] = BimpTools::getMsgFromArray($rem_errors, 'Erreurs lors de la création de la remise');
                                    }
                                }

                                $remain_qty = $qty;

                                $reservations = $this->getReservations('status', 'asc', 0);

                                foreach ($reservations as $reservation) {
                                    $res_qty = (int) $reservation->getData('qty');
                                    if ($remain_qty > $res_qty) {
                                        $remain_qty -= $res_qty;
                                    } else {
                                        $res_qty = $remain_qty;
                                        $remain_qty = 0;
                                    }

                                    $res_errors = $reservation->setNewStatus(100, $res_qty);

                                    if (count($res_errors)) {
                                        $warnings[] = BimpTools::getMsgFromArray($res_errors, 'Echec de la mise à jour du statut des produits pour ' . $res_qty . ' unité(s)');
                                    }
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

                            $res_errors = $reservation->setNewStatus(0, $res_qty);

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
                        $this->updateField('qty_modif', $qty_modified);

                        if (!count($errors)) {
                            if ($isProduct) {
                                $this->checkReservations();
                            }
                        }
                    }
                }
            } else {
                $max = $this->getMinQty();
                if ((float) $data['qty_modified'] > $max) {
                    $errors[] = 'Veuillez indiquer une quantité inférieure ou égale à ' . $max;
                } else {
                    $qty_modified = (float) $data['qty_modified'] - (float) $this->qty;
                    $this->updateField('qty_modif', $qty_modified);
                }
            }

            $commande = $this->getParentInstance();
            if (BimpObject::objectLoaded($commande)) {
                $commande->checkShipmentStatus();
                $commande->checkInvoiceStatus();
                $commande->checkLogistiqueStatus();
            }
        }

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

        $commande = $this->getParentInstance();

        if (!BimpObject::objectLoaded($commande)) {
            $errors[] = 'ID de la commande client absent';
        } elseif (!isset($data['equipments']) || empty($data['equipments'])) {
            $errors[] = 'Aucun équipement sélectionné';
        } else {
            $equipments = $this->getData('equipments_returned');

            $max = abs($this->getFullQty()) - count($equipments);

            if (!$max) {
                $errors[] = 'Tous les équipement à retournés ont déjà été sélectionnés';
            } elseif (count($data['equipments']) > $max) {
                $errors[] = 'Vous ne pouvez sélectionner que ' . $max . ' équipement(s)';
            } else {
                foreach ($data['equipments'] as $id_equipment) {
                    $errors = $this->checkReturnedEquipment($id_equipment);

                    if (!count($errors)) {
                        $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);

                        $eq_errors = $equipment->updateField('return_available', 0);
                        if (count($eq_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($eq_errors, 'Echec de la mise à jour de l\'équipement "' . $equipment->getData('serial') . '" - Cet équipement n\'a donc pas été ajouté à la liste des équipement retournés');
                        } else {
                            $equipments[] = (int) $id_equipment;
                        }
                    }
                }

                if (!count($errors)) {
                    $errors = $this->updateField('equipments_returned', $equipments);
                }
            }
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

                $id_facture = $this->getEquipmentIdFacture($id_equipment);
                $facture = null;
                if ($id_facture) {
                    $facture = BimpCache::getBimpObjectFullListArray('bimpcommercial', 'Bimp_Facture', (int) $id_facture);
                    if (BimpObject::objectLoaded($facture)) {
                        if ((int) $facture->getData('fk_statut') > 0) {
                            $warnings[] = 'L\'équipement "' . $equipment->getData('serial') . '" ne peux pas être retiré car il a été attribué à une facture validée';
                            continue;
                        }
                    }
                }

                $eq_errors = $equipment->updateField('return_available', 1);
                if (count($eq_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($eq_errors, 'Equipement "' . $equipment->getData('serial') . '"');
                } else {
                    $removed[] = $id_equipment;

                    if (!is_null($facture)) {
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

                $factures = $this->getData('factures');

                foreach ($factures as $id_facture => $facture_data) {
                    if (isset($facture_data['equipments']) && !empty($facture_data['equipments'])) {
                        $fac_equipments = array();
                        foreach ($facture_data['equipments'] as $id_equipment) {
                            if (!in_array($id_equipment, $removed)) {
                                $fac_equipments[] = $id_equipment;
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
                foreach ($equipments as $id_equipment) {
                    if (!in_array((int) $id_equipment, $removed)) {
                        $new_equipments[] = $id_equipment;
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

    // Overrides:

    public function checkObject()
    {
        if ((int) $this->id_remise_except && (float) $this->qty) {
            BimpTools::loadDolClass('core', 'discount', 'DiscountAbsolute');
            $discount = new DiscountAbsolute($this->db->db);
            $discount->fetch((int) $this->id_remise_except);

            $id_facture = 0;
            if ((int) $discount->fk_facture) {
                $id_facture = (int) $discount->fk_facture;
            } elseif ((int) $discount->fk_facture_line) {
                $id_facture = (int) $this->db->getValue('facturedet', 'fk_facture', '`rowid` = ' . (int) $discount->fk_facture_line);
            }

            if ($id_facture) {
                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_facture);
                if (BimpObject::objectLoaded($facture)) {
                    $elements = BimpTools::getDolObjectLinkedObjectsList($facture->dol_object, $this->db);
                    $id_commande = (int) $this->getParentId();

                    $isOk = false;
                    foreach ($elements as $element) {
                        if ($element['type'] === 'commande' && (int) $element['id_object'] === $id_commande) {
                            $isOk = true;
                            break;
                        }
                    }
                    if (!$isOk) {
                        $this->qty = 0;
                        $this->desc .= ($this->desc ? '<br/>' : '') . '<span class="danger">Remise déjà consommée dans la facture ' . $facture->getNomUrl() . '</span>';
                        $warnings = array();
                        $this->update($warnings, true);
                    }
                }
            }
        }

        // Vérification des réservations: 
        $commande = $this->getParentInstance();

        if (BimpObject::objectLoaded($commande) && $commande->isLogistiqueActive()) {
            $this->checkReservations(); // les quantités sont vérifiées dans cette méthode.
        }
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

            $this->set('qty_modif', (float) $this->qty);
            $this->qty = 0;
            $commande->set('fk_statut', 0);
            $commande->dol_object->statut = 0;
        }

        $errors = parent::create($warnings, $force_create);

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
        }

        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $prev_commande_status = null;
        $commande = $this->getParentInstance();
//
        if (BimpObject::objectLoaded($commande)) {
            if ((int) $commande->getData('fk_statut') === 1) {
                $prev_commande_status = 1;
                $commande->dol_object->statut = 0;
                $commande->dol_object->brouillon = 1;
            }
        }

        $errors = parent::update($warnings, $force_update);

        if (!is_null($prev_commande_status)) {
            $commande->dol_object->statut = $prev_commande_status;
            $commande->dol_object->brouillon = 0;
        }

        if (BimpObject::objectLoaded($commande) && (int) $commande->getData('fk_statut') > 0) {
            $res_errors = $this->checkReservations();
            if (count($res_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($res_errors);
            }
        }

        return $errors;
    }
}
