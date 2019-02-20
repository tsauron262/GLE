<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/ObjectLine.class.php';

class Bimp_CommandeLine extends ObjectLine
{

    public static $parent_comm_type = 'commande';
    public static $dol_line_table = 'commandedet';
    public static $reservations_ordered_status = array(3, 100);

    // Getters:

    public function isCreatable()
    {
        $commande = $this->getParentInstance();
        if (!BimpObject::objectLoaded($commande)) {
            return 0;
        }

        if (in_array((int) $commande->getData('fk_statut'), array(0, 1))) {
            return 1;
        }

        return 0;
    }

    public function isEditable($force_edit = false)
    {
        if (!$force_edit && !(int) $this->getData('editable')) {
            return 0;
        }

        $parent = $this->getParentInstance();
        if (!BimpObject::objectLoaded($parent)) {
            return 0;
        }

        if ($parent->field_exists('fk_statut') && in_array((int) $parent->getData('fk_statut'), array(0, 1))) {
            return 1;
        }

        return 0;
    }

    public function isRemiseEditable()
    {
        return (int) $this->isParentEditable();
    }

    public function getMinQty()
    {
        if ($this->isParentEditable()) {
            return 'none';
        }

        return $this->qty;
    }

    public function getReservations($order_by = 'status', $order_way = 'asc')
    {
        $reservations = array();

        if ($this->isLoaded()) {
            $commande = $this->getParentInstance();
            if (BimpObject::objectLoaded($commande)) {
                $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');
                $rows = $reservation->getList(array(
                    'type'                    => BR_Reservation::BR_RESERVATION_COMMANDE,
                    'id_commande_client'      => (int) $commande->id,
                    'id_commande_client_line' => $this->id
                        ), null, null, $order_by, $order_way, 'array', array('id'));

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

    public function getLogistiqueBulkActions()
    {
        $commande = $this->getParentInstance();

        if (BimpObject::objectLoaded($commande)) {
            return array(
                array(
                    'label'   => 'Ajouter à une expédition',
                    'icon'    => 'fas_shipping-fast',
                    'onclick' => 'addSelectedCommandeLinesToShipment($(this), \'list_id\', ' . $commande->id . ')'
                ),
                array(
                    'label'   => 'Ajouter à une facture',
                    'icon'    => 'fas_file-invoice-dollar',
                    'onclick' => 'addSelectedCommandeLinesToInvoice($(this), \'list_id\', ' . $commande->id . ')'
                )
            );
        }

        return array();
    }

    public function getLogistiqueExtraButtons()
    {
        $buttons = array();

        if ($this->isLoaded()) {
            $commande = $this->getParentInstance();
            if (BimpObject::objectLoaded($commande)) {
                $buttons[] = array(
                    'label'   => 'Gérer les expéditions',
                    'icon'    => 'fas_shipping-fast',
                    'onclick' => $this->getJsLoadModalView('shipments', 'Gestion des expéditions')
                );
            }

            if ((float) $this->getBilledQty() < (float) $this->qty) {
                $buttons[] = array(
                    'label'   => 'Ajouter à une facture',
                    'icon'    => 'fas_file-invoice-dollar',
                    'onclick' => ''
                );
            }
        }

        return $buttons;
    }

    public function getShippedQty()
    {
        $shipments = $this->getData('shipments');

        $qty = 0;

        if (is_array($shipments)) {
            foreach ($shipments as $shipment) {
                if (isset($shipment['qty'])) {
                    $qty += (float) $shipment['qty'];
                }
            }
        }

        return $qty;
    }

    public function getBilledQty()
    {
        $factures = $this->getData('factues');

        $qty = 0;

        if (is_array($factures)) {
            foreach ($factures as $facture) {
                if (isset($facture['qty'])) {
                    $qty += (float) $facture['qty'];
                }
            }
        }

        return $qty;
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

    // Affichages: 

    public function displayQties()
    {
        $html = '';
        $total_qty = (float) $this->qty;

        // Qté totale
        $html .= '<span class="bold bs-popover"' . BimpRender::renderPopoverData('Qtés totales') . '>';
        $html .= BimpRender::renderIcon('fas_dolly', 'iconLeft');
        $html .= $total_qty;
        $html .= '</span>';

        // Qté dispo
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
                        if ($qty_available < $qty_wanted) {
                            $class = 'danger';
                        } elseif ($qty_available === $qty_wanted) {
                            $class = 'warning';
                        } else {
                            $class = 'success';
                        }

                        $popover = '<span style="font-weight: bold">Qtés disponibles / nécessaires</span><br/>';
                        $popover .= 'Déjà traitées: ' . $qties_reserved['reserved'] . '<br/>';
                        $popover .= 'Commandées au fournisseur: ' . $qties_reserved['ordered'] . '<br/>';
                        $popover .= 'A traiter: ' . $qty_wanted . '<br/>';

                        $html .= '<span style="display: inline-block; margin-left: 15px"';
                        $html .= BimpRender::renderPopoverData($popover, 'top', 'true');
                        $html .= ' class="bs-popover ' . $class . '">';
                        $html .= BimpRender::renderIcon('fas_box-open', 'iconLeft');
                        $html .= $qty_available . ' / ' . $qty_wanted . '</span>';
                    }
                }
            }
        }

        // Qté expédiée:
        $qty_shipped = (float) $this->getShippedQty();

        if ($qty_shipped <= 0) {
            $class = 'danger';
        } elseif ($qty_shipped < $total_qty) {
            $class = 'warning';
        } else {
            $class = 'success';
        }

        $html .= '<span class="bs-popover ' . $class . '" style="display: inline-block; margin-left: 15px"';
        $html .= BimpRender::renderPopoverData('Qtés ajoutées à une expédition');
        $html .= '>';
        $html .= BimpRender::renderIcon('fas_shipping-fast', 'iconLeft');
        $html .= $qty_shipped;
        $html .= '</span>';

        // Qté facturée: 
        $qty_billed = (float) $this->getBilledQty();
        if ($qty_billed <= 0) {
            $class = 'danger';
        } elseif ($qty_billed < $total_qty) {
            $class = 'warning';
        } else {
            $class = 'success';
        }

        $html .= '<span class="bs-popover ' . $class . '" style="display: inline-block; margin-left: 15px"';
        $html .= BimpRender::renderPopoverData('Qtés ajoutées à une facture');
        $html .= '>';
        $html .= BimpRender::renderIcon('fas_file-invoice-dollar', 'iconLeft');
        $html .= $qty_billed;
        $html .= '</span>';

        return $html;
    }

    public function displayReservationsStatus()
    {
        $html = '';

        $reservations = $this->getReservations();

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
                $html .= '<td style="text-align: center">';
                $html .= '<input type="checkbox" name="reservation_check[]" value="' . $reservation->id . '" class="reservation_check"';
                $html .= ' data-id_commande_line="' . $this->id . '"';
                $html .= ' data-id_reservation="' . $reservation->id . '"';
                $html .= '/>';
                $html .= '</td>';
                $html .= '<td>' . $reservation->getData('ref') . '</td>';
                $html .= '<td>' . $reservation->displayData('status') . '</td>';
                $html .= '<td>Qté: ' . $reservation->getData('qty') . '</td>';
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

        $max = (float) $this->qty - (float) $this->getShippedQty() + $shipment_qty;

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
            $options['data']['total_max_value'] = (float) $this->qty;
            $options['data']['total_max_inputs_class'] = 'line_shipment_qty';
            $options['extra_class'] .= ' total_max';
        }

        $value = (!$with_total_max && !(float) $shipment_qty ? $max : $shipment_qty);

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

    public function renderShipmentEquipmentsInput($id_shipment)
    {
        return 'TEST';
    }

    public function renderShipmentsView()
    {
        $html = '';

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
        }
        $html .= BimpRender::renderDropDownButton('Status sélectionnés', $items, array(
                    'icon' => 'far_check-square'
        ));

        return $html;
    }

    // Traitements: 

    public function createReservation()
    {
        $errors = array();

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

                        $qty = (int) ceil($this->qty - (float) $reserved_qties['total']);

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
                        }
                    }
                }
            }
        }
        return $errors;
    }

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

            if ($total_qty_shipped > (float) $this->qty) {
                $errors[] = 'Le nombre total d\'unités ajoutées à des expéditions (' . $total_qty_shipped . ') dépasse le nombre d\'unité enregistrées pour cette ligne de commande (' . $this->qty . ')';
            }

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

        if ($total_qty_shipped > (float) $this->qty) {
            $errors[] = 'Les quantités totales ajoutée à des expéditions dépasse le nombre d\'unités enregistrées pour cette ligne de commande. Veuillez corriger';
        }

        if (!count($errors)) {
            $this->set('shipments', $shipments);
            $errors = $this->update($warnings, true);
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

    // Overrides: 

    public function create(&$warnings = array(), $force_create = false)
    {
        $prev_commande_status = null;
        $commande = $this->getParentInstance();
        if (BimpObject::objectLoaded($commande)) {
            if ((int) $commande->getData('fk_statut') === 1) {
                $prev_commande_status = $commande->dol_object->statut;
                $commande->dol_object->statut = 0;
            }
        }

        $errors = parent::create($warnings, $force_create);

        if (!is_null($prev_commande_status)) {
            $commande->dol_object->statut = $prev_commande_status;
        }

        if (BimpObject::objectLoaded($commande) && (int) $commande->getData('fk_statut') > 0) {
            $res_errors = $this->createReservation();
            if (count($res_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($res_errors);
            }
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
            $res_errors = $this->createReservation();
            if (count($res_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($res_errors);
            }
        }

        return $errors;
    }
}
