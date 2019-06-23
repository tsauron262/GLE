<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/FournObjectLine.class.php';

class Bimp_CommandeFournLine extends FournObjectLine
{

    public static $parent_comm_type = 'commande_fournisseur';
    public static $dol_line_table = 'commande_fournisseurdet';
    public static $dol_line_parent_field = 'fk_commande';

    // Getters booléens: 

    public function isReceptionCancellable($id_reception, &$errors = array())
    {
        if (!$this->isLoaded()) {
            $errors[] = 'ID de la ligne de commande fournisseur absent';
            return 0;
        }

        $reception = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeFournReception', (int) $id_reception);

        if (BimpObject::objectLoaded($reception)) {
            $entrepot = $reception->getChildObject('entrepot');
        } else {
            $entrepot = null;
        }

        $reception_data = $this->getReceptionData($id_reception);

        if ((int) $reception_data['received']) {
            $fullQty = (float) $this->getFullQty();

            if ($fullQty >= 0) {
                if ($this->getData('linked_object_name') === 'commande_line' &&
                        isset($reception_data['assign_to_commande_client']) && (int) $reception_data['assign_to_commande_client']) {
                    $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $this->getData('linked_id_object'));
                }

                if ($this->isProductSerialisable()) {
                    $line_equipments_shipped = array();
                    $line_equipments_billed = array();

                    if (BimpObject::objectLoaded($line)) {
                        // Liste des équipements expédiés pour la ligne de commande client associée
                        $line_shipments = $line->getData('shipments');
                        $line_equipments_shipped = array();

                        foreach ($line_shipments as $id_shipment => $shipment_data) {
                            if (isset($shipment_data['equipments'])) {
                                foreach ($shipment_data['equipments'] as $id_equipment) {
                                    $line_equipments_shipped[] = (int) $id_equipment;
                                }
                            }
                        }

                        // Liste des équipements facturés pour la ligne de commande client associée
                        $line_factures = $line->getData('factures');
                        $line_equipments_billed = array();

                        foreach ($line_factures as $id_facture => $facture_data) {
                            if (isset($facture_data['equipments'])) {
                                foreach ($facture_data['equipments'] as $id_equipment) {
                                    $line_equipments_billed[] = (int) $id_equipment;
                                }
                            }
                        }
                    }

                    foreach ($reception_data['equipments'] as $id_equipment => $equipment_data) {
                        $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);

                        if (BimpObject::objectLoaded($equipment)) {

                            // Vérification de la disponibilité à la vente: 
                            $available_errors = array();
                            if (!(int) $equipment->isAvailable(0, $available_errors)) {
                                $errors[] = BimpTools::getMsgFromArray($available_errors);
                                continue;
                            }

                            // Vérification de l'emplacement: 
                            if (BimpObject::objectLoaded($entrepot)) {
                                $place = $equipment->getCurrentPlace();

                                if (BimpObject::objectLoaded($place)) {
                                    if (!(int) $place->getData('id_entrepot') || (int) $place->getData('id_entrepot') !== (int) $entrepot->id) {
                                        $errors[] = 'L\'équipement "' . $equipment->getData('serial') . '" n\'est plus dans l\'entrepot "' . $entrepot->libelle . '" (' . $entrepot->id . ', ' . $place->getData('id_entrepot') . ')';
                                        continue;
                                    }
                                }
                            }

                            if (BimpObject::objectLoaded($line)) {
                                // On vérifie la réservation de la ligne de commande client associée:  
                                $reservation = BimpObject::findBimpObjectInstance('bimpreservation', 'BR_Reservation', array(
                                            'id_equipment'            => (int) $id_equipment,
                                            'status'                  => 200,
                                            'id_commande_client_line' => (int) $line->id
                                                )
                                );
                                if (!BimpObject::objectLoaded($reservation)) {
                                    $commande = $line->getParentInstance();
                                    if (BimpObject::objectLoaded($commande)) {
                                        $commande_ref = '"' . $commande->getRef() . '"';
                                    } else {
                                        $commande_ref = 'd\'ID ' . $line->getData('id_obj');
                                    }
                                    $errors[] = 'L\'équipement "' . $equipment->getData('serial') . '" n\'est plus au statut "Réservé" pour la commande client ' . $commande_ref;
                                } else {
                                    // La réservation est ok : on vérifie l'ajout de l'équipement à une facture ou une expédition de la ligne de commande client:
                                    if (in_array((int) $id_equipment, $line_equipments_shipped)) {
                                        $errors[] = 'L\'équipement "' . $equipment->getData('serial') . '" a été ajouté à une expédition';
                                    }
                                    if (in_array((int) $id_equipment, $line_equipments_billed)) {
                                        $errors[] = 'L\'équipement "' . $equipment->getData('serial') . '" a été ajouté à une facture';
                                    }
                                }
                            } else {
                                $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');

                                // Liste des réservations de l'équipement: 
                                $list = $reservation->getList(array(
                                    'id_equipment' => (int) $id_equipment
                                        ), null, null, 'id', 'asc', 'array');

                                if (!is_null($list)) {
                                    foreach ($list as $item) {
                                        $status = (int) $item['status'];
                                        if ((int) $item['type'] === BR_Reservation::BR_RESERVATION_COMMANDE) {
                                            if ($status !== 200) {
                                                $errors[] = 'L\'équipement "' . $equipment->getData('serial') . '" est attributé à une commande client mais n\'est plus au statut "Réservé"';
                                            } else {
                                                // Une réservation de commande client au bon statut est trouvé, on vérifie l'ajout à une expédition ou une facture: 
                                                $commande_line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $item['id_commande_client_line']);
                                                if (BimpObject::objectLoaded($commande_line)) {
                                                    $line_shipments = $commande_line->getData('shipments');
                                                    foreach ($line_shipments as $id_shipment => $shipment_data) {
                                                        if (isset($shipment_data['equipments']) && in_array((int) $id_equipment, $shipment_data['equipments'])) {
                                                            $errors[] = 'L\'équipement "' . $equipment->getData('serial') . '" est attributé à une expédition';
                                                            break;
                                                        }
                                                    }

                                                    $line_factures = $commande_line->getData('factures');
                                                    foreach ($line_factures as $id_facture => $facture_data) {
                                                        if (isset($facture_data['equipments']) && in_array((int) $id_equipment, $facture_data['equipments'])) {
                                                            $errors[] = 'L\'équipement "' . $equipment->getData('serial') . '" est attribué à une facture';
                                                            break;
                                                        }
                                                    }
                                                }
                                            }
                                        } else {
                                            if (in_array($status, BR_Reservation::$unavailable_status)) {
                                                $errors[] = 'L\'équipement "' . $equipment->getData('serial') . '" est réservé en dehors d\'une commande client';
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                } elseif (BimpObject::objectLoaded($line)) {
                    $shipped_qty = (float) $line->getShippedQty();
                    $available_qty = (float) $line->getFullQty() - $shipped_qty;

                    if ((float) $reception_data['qty'] > $available_qty) {
                        $errors[] = 'Certaines unités ont été ajouté à une expédition';
                    }

                    $billed_qty = (float) $line->getBilledQty();
                    $available_qty = (float) $line->getFullQty() - $billed_qty;

                    if ((float) $reception_data['qty'] > $available_qty) {
                        $errors[] = 'Certaines unités ont été ajouté à une facture';
                    }
                }
            }
        }

        return (count($errors)) ? 0 : 1;
    }

    public function isActionAllowed($action, &$errors = array())
    {
        if (in_array($action, array('modifyQty'))) {
            if (!$this->isLoaded()) {
                $errors[] = 'ID de la ligne de commande fournisseur absent';
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
                    $errors[] = 'ID de la commande fournisseur absent';
                    return 0;
                }
                if (!in_array((int) $commande->getData('fk_statut'), array(1, 2, 3, 4, 5))) {
                    $errors[] = 'Le statut actuel de la commande fournisseur ne permet pas cette action';
                    return 0;
                }
                if ((int) $commande->isBilled()) {
                    $errors[] = 'Une facture a été créée pour cette commande fournisseur';
                    return 0;
                }
                return 1;
        }

        return parent::isActionAllowed($action, $errors);
    }

    // Getters données: 

    public function getMinQty()
    {
        $commande = $this->getParentInstance();

        if (BimpObject::objectLoaded($commande) && $commande->isLogistiqueActive()) {
            return $this->getReceivedQty();
        }

        return 'none';
    }

    public function getFullQty()
    {
        return (float) $this->qty + (float) $this->GetData('qty_modif');
    }

    public function getReceivedQty($id_reception = null, $validated_reception = false)
    {
        $receptions = $this->getData('receptions');

        $qty = 0;

        foreach ($receptions as $id_r => $reception_data) {
            if (!is_null($id_reception) && ((int) $id_r !== (int) $id_reception)) {
                continue;
            }

            if ($validated_reception) {
                $reception = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeFournReception', (int) $id_r);
                if (!BimpObject::objectLoaded($reception) || (int) $reception->getData('status') !== BL_CommandeFournReception::BLCFR_RECEPTIONNEE) {
                    continue;
                }
            }
            $qty += (float) $reception_data['qty'];
        }

        return $qty;
    }

    public function getReceptionData($id_reception)
    {
        $receptions = $this->getData('receptions');
        if (isset($receptions[$id_reception])) {
            return $receptions[$id_reception];
        }

        $reception = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeFournReception', (int) $id_reception);

        $assign_to_commande_client = 0;
        if (BimpObject::objectLoaded($reception)) {
            $assign_to_commande_client = (int) $reception->getData('assign_lines_to_commandes_client');
        }

        return array(
            'qty'                       => 0,
            'qties'                     => array(
                0 => array()
            ),
            'equipments'                => array(),
            'serials'                   => array(),
            'assign_to_commande_client' => $assign_to_commande_client,
            'received'                  => 0
        );
    }

    public function getReceptionAvailableQty($id_reception = 0)
    {
        if (is_null($id_reception)) {
            $id_reception = 0;
        }

        $qty = (float) $this->getFullQty() - (float) $this->getReceivedQty();

        if ($id_reception) {
            $qty += (float) $this->getReceivedQty($id_reception);
        }

        return $qty;
    }

    public function getLinesDataByUnitPriceAndTva()
    {
        $lines = array();
        $full_qty = $this->getFullQty();
        $is_serialisable = (int) $this->isProductSerialisable();

        $receptions = $this->getData('receptions');

        foreach ($receptions as $id_reception => $reception_data) {
            if ($is_serialisable) {
                if (isset($reception_data['equipments'])) {
                    foreach ($reception_data['equipments'] as $id_equiment => $equipment_data) {
                        $pu_ht = (string) (isset($equipment_data['pu_ht']) ? (float) $equipment_data['pu_ht'] : (float) $this->getUnitPriceHTWithRemises());
                        $tva_tx = (string) (isset($equipment_data['tva_tx']) ? (float) $equipment_data['tva_tx'] : (float) $this->tva_tx);

                        if (!isset($lines[$pu_ht])) {
                            $lines[$pu_ht] = array();
                        }

                        if (!isset($lines[$pu_ht][$tva_tx])) {
                            $lines[$pu_ht][$tva_tx] = array(
                                'equipments' => array()
                            );
                        }
                        $lines[$pu_ht][$tva_tx]['equipments'][] = $id_equiment;
                    }
                }
            } else {
                if (isset($reception_data['qties'])) {
                    foreach ($reception_data['qties'] as $qty_data) {
                        $qty = (float) (isset($qty_data['qty']) ? $qty_data['qty'] : 0);
                        $pu_ht = (string) (isset($qty_data['pu_ht']) ? $qty_data['pu_ht'] : $this->getUnitPriceHTWithRemises());
                        $tva_tx = (string) (isset($qty_data['tva_tx']) ? $qty_data['tva_tx'] : $this->tva_tx);

                        if (!isset($lines[$pu_ht])) {
                            $lines[$pu_ht] = array();
                        }

                        if (!isset($lines[$pu_ht][$tva_tx])) {
                            $lines[$pu_ht][$tva_tx] = 0;
                        }

                        $lines[$pu_ht][$tva_tx] += $qty;
                    }
                }
            }
        }

        return $lines;
    }

    public function getReceptionTotalHt($id_reception)
    {
        $data = $this->getReceptionData($id_reception);

        $total_ht = 0;

        if ($this->isProductSerialisable()) {
            foreach ($data['equipments'] as $id_equiment => $equipment_data) {
                $pu_ht = (float) (isset($equipment_data['pu_ht']) ? (float) $equipment_data['pu_ht'] : (float) $this->getUnitPriceHTWithRemises());
                $total_ht += $pu_ht;
            }
        } else {
            foreach ($data['qties'] as $qty_data) {
                $qty = (float) (isset($qty_data['qty']) ? $qty_data['qty'] : 0);
                $pu_ht = (float) (isset($qty_data['pu_ht']) ? $qty_data['pu_ht'] : $this->getUnitPriceHTWithRemises());

                $total_ht += ($qty * $pu_ht);
            }
        }

        return $total_ht;
    }

    public function getReceptionTotalTTC($id_reception)
    {
        $data = $this->getReceptionData($id_reception);

        $total_ttc = 0;

        if ($this->isProductSerialisable()) {
            foreach ($data['equipments'] as $id_equiment => $equipment_data) {
                $pu_ht = (float) (isset($equipment_data['pu_ht']) ? (float) $equipment_data['pu_ht'] : (float) $this->getUnitPriceHTWithRemises());
                $total_ttc += $pu_ht;
            }
        } else {
            foreach ($data['qties'] as $qty_data) {
                $qty = (float) (isset($qty_data['qty']) ? $qty_data['qty'] : 0);
                $pu_ht = (float) (isset($qty_data['pu_ht']) ? $qty_data['pu_ht'] : $this->getUnitPriceHTWithRemises());
                $tva_tx = (float) (isset($qty_data['tva_tx']) ? $qty_data['tva_tx'] : $this->tva_tx);

                $total_ttc += ($qty * BimpTools::calculatePriceTaxIn($pu_ht, $tva_tx));
            }
        }

        return $total_ttc;
    }

    public function getEquipmentIdReception($id_equipment)
    {
        $receptions = $this->getData('receptions');

        if (is_array($receptions)) {
            foreach ($receptions as $id_reception => $reception_data) {
                if (isset($reception_data['equipments']) && is_array($reception_data['equipments'])) {
                    if (array_key_exists((int) $id_equipment, $reception_data['equipments'])) {
                        return $id_reception;
                    }
                }
            }
        }

        return 0;
    }

    // Getters config: 

    public function getLogistiqueBulkActions()
    {
        return array();
    }

    public function getLogistiqueExtraButtons()
    {
        $buttons = array();

        if (abs((float) $this->getReceivedQty()) < abs((float) $this->getFullQty())) {
            $buttons[] = array(
                'label'   => 'Ajouter à une réception',
                'icon'    => 'fas_arrow-circle-down',
                'onclick' => $this->getJsLoadModalView('reception', 'Ligne n° ' . $this->getData('position') . ' - ajout à une réception')
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

        $product = $this->getProduct();

        if (BimpObject::objectLoaded($product)) {
            $buttons = array_merge($buttons, $product->getListsButtons((int) ceil($this->qty)));
        }

        return $buttons;
    }

    public function getGeneralListExtraButtons()
    {
        $buttons = array();
        if ($this->isLoaded()) {
            $commande = $this->getParentInstance();
            if (BimpObject::objectLoaded($commande)) {
                $url = DOL_URL_ROOT . '/bimpcommercial/index.php?fc=commandeFourn&id=' . $commande->id . '&navtab=content';
                $buttons[] = array(
                    'icon'    => 'fas_list',
                    'label'   => 'Contenu commande',
                    'onclick' => 'window.open(\'' . $url . '\')'
                );

                if ($commande->isLogistiqueActive()) {
                    $url = DOL_URL_ROOT . '/bimplogistique/index.php?fc=commandeFourn&id=' . $commande->id;
                    $buttons[] = array(
                        'icon'    => 'fas_truck-loading',
                        'label'   => 'Logistique commande',
                        'onclick' => 'window.open(\'' . $url . '\')'
                    );
                }
            }
        }

        return $buttons;
    }

    // Getters array: 

    public function getReturnableEquipmentsArray()
    {
        $items = array();

        if ($this->isLoaded() && $this->isProductSerialisable()) {
            $commande = $this->getParentInstance();

            if (BimpObject::objectLoaded($commande) && (int) $this->id_product) {
                BimpObject::loadClass('bimpequipment', 'BE_Place');
                $sql = BimpTools::getSqlSelect(array('a.id', 'a.serial'));
                $sql .= BimpTools::getSqlFrom('be_equipment', array(
                            'p' => array(
                                'table' => 'be_equipment_place',
                                'alias' => 'p',
                                'on'    => 'p.id_equipment = a.id'
                            )
                ));
                $sql .= BimpTools::getSqlWhere(array(
                            'a.id_product'  => (int) $this->id_product,
                            'p.position'    => 1,
                            'p.type'        => BE_Place::BE_PLACE_ENTREPOT,
                            'p.id_entrepot' => (int) $commande->getData('entrepot')
                ));

                $rows = self::getBdb()->executeS($sql, 'array');

                if (!is_null($rows)) {
                    foreach ($rows as $r) {
                        $items[(int) $r['id']] = $r['serial'];
                    }
                }

                foreach ($items as $id_equipment => $label) {
                    $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                    if (!BimpObject::objectLoaded($equipment)) {
                        unset($items[$id_equipment]);
                        continue;
                    }

                    if (!$equipment->isAvailable()) {
                        unset($items[$id_equipment]);
                    }
                }
            }
        }

        return $items;
    }

    // Affichages: 

    public function displayCommandeClient()
    {
        $html = '';

        if ($this->getData('linked_object_name') === 'commande_line') {
            $id_line = (int) $this->getData('linked_id_object');
            if ($id_line) {
                $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $id_line);
                if (!BimpObject::objectLoaded($line)) {
                    if (is_null($line)) {
                        $line = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeLine');
                    }
                    $html .= $this->renderChildUnfoundMsg('linked_object_name', $line, true);
                } else {
                    $commande = $line->getParentInstance();
                    if (BimpObject::objectLoaded($commande)) {
                        $url = DOL_MAIN_URL_ROOT . '/bimplogistique/index.php?fc=commande&id=' . $commande->id;
                        $html .= $commande->getNomUrl(1, 1, 1, 'full');
                        $html .= '&nbsp;&nbsp;&nbsp;<a href="' . $url . '" target="_blank">Logistique' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight') . '</a>';
                        $html .= '<br/>';
                        $html .= 'Ligne n°' . $line->getData('position');
                    } else {
                        $html .= BimpRender::renderAlerts('Erreur: Commande absente pour la ligne de commande d\'ID ' . $id_line);
                    }
                }
            }
        }

        return $html;
    }

    public function displayQty($qty_type = 'total')
    {
        if ($this->field_exists('qty_' . $qty_type)) {
            $qty = (float) $this->getData('qty_' . $qty_type);
            $class = '';

            switch ($qty_type) {
                case 'received':
                    if ($qty <= 0) {
                        $class = 'danger';
                    } elseif ($qty < abs((float) $this->getFullQty())) {
                        $class = 'warning';
                    } else {
                        $class = 'success';
                    }
                    break;

                case 'to_receive';
                    if ($qty <= 0) {
                        $class = 'success';
                    } elseif ($qty < abs((float) $this->getFullQty())) {
                        $class = 'warning';
                    } else {
                        $class = 'danger';
                    }
                    break;
            }

            return '<span class="badge ' . ($class ? 'badge-' . $class : 'default') . '">' . $qty . '</span>';
        }

        return '';
    }

    public function displayQties()
    {
        $html = '';

        $total_qty = (float) $this->getFullQty();
        $qty_modif = (float) $this->getData('qty_modif');

        // Qté totale

        if ($total_qty >= 0) {
            if ($qty_modif) {
                $popover .= 'Qtés totales (qtés commandées +/- qtés modifiées)';
            } else {
                $popover = 'Qtés commandées';
            }
        } else {
            if ($qty_modif) {
                $popover .= 'Qtés totales (qtés retournées +/- qtés modifiées)';
            } else {
                $popover = 'Qtés retournées';
            }
        }

        $html .= '<span class="bold bs-popover"';
        $html .= BimpRender::renderPopoverData($popover);
        $html .= ' style="display: inline-block; padding: 3px 0; margin-right: 15px">';

        if ($total_qty >= 0) {
            $html .= BimpRender::renderIcon('fas_cart-arrow-down', 'iconLeft');
        } else {
            $html .= '<span class="important">';
            $html .= BimpRender::renderIcon('fas_arrow-circle-left', 'iconLeft');
            $html .= '</span>';
        }
        $html .= $total_qty;

        if ($qty_modif) {
            $html .= '<span class="important"> (' . $this->qty . ($qty_modif > 0 ? '+' : '-') . abs($qty_modif) . ')</span>';
        }
        $html .= '</span>';

        // Qté reçues:
        $qty_received = (float) $this->getReceivedQty();
        $qty_received_valid = (float) $this->getReceivedQty(null, true);

        if (abs($qty_received_valid) <= 0) {
            $class = 'danger';
        } elseif (abs($qty_received_valid) < abs($total_qty)) {
            $class = 'warning';
        } else {
            $class = 'success';
        }

        $html .= '<span class="bs-popover ' . $class . '" style="display: inline-block; padding: 3px 0"';
        $html .= BimpRender::renderPopoverData('Qtés ajoutées à une réception / Qtés réceptionnées');
        $html .= '>';
        $html .= BimpRender::renderIcon('fas_arrow-circle-down', 'iconLeft');

        if (abs($qty_received) <= 0) {
            $class = 'danger';
        } elseif (abs($qty_received) < ($total_qty)) {
            $class = 'warning';
        } else {
            $class = 'success';
        }

        $html .= '<span class="' . $class . '">' . $qty_received . '</span>';

        $html .= ' / ';

        if (abs($qty_received_valid) <= 0) {
            $class = 'danger';
        } elseif (abs($qty_received_valid) < abs($total_qty)) {
            $class = 'warning';
        } else {
            $class = 'success';
        }

        $html .= '<span class="' . $class . '">' . $qty_received_valid . '</span>';

        $html .= '</span>';

//        // Qté facturée: 
//        $qty_billed = (float) $this->getBilledQty();
//        if ($qty_billed <= 0) {
//            $class = 'danger';
//        } elseif ($qty_billed < $total_qty) {
//            $class = 'warning';
//        } else {
//            $class = 'success';
//        }
//        $html .= '<span class="bs-popover ' . $class . '" style="display: inline-block; margin-left: 15px"';
//        $html .= BimpRender::renderPopoverData('Qtés ajoutées à une facture');
//        $html .= '>';
//        $html .= BimpRender::renderIcon('fas_file-invoice-dollar', 'iconLeft');
//        $html .= $qty_billed;
//        $html .= '</span>';

        return $html;
    }

    // Rendus Html: 

    public function renderReceptionForm()
    {
        $html = '';

        if (!$this->isLoaded()) {
            return BimpRender::renderAlerts('ID de la ligne de commande fournisseur absent');
        }

        $commande = $this->getParentInstance();

        if (!BimpObject::objectLoaded($commande)) {
            return BimpRender::renderAlerts('ID de la commande fournisseur absent');
        }

        if ((int) $this->getData('type') === self::LINE_PRODUCT) {
            $product = $this->getProduct();
            if (!BimpObject::objectLoaded($product)) {
                $html .= BimpRender::renderAlerts('Aucun produit associé à cette ligne');
            } else {
                $isSerialisable = $product->isSerialisable();
                $qty = (float) $this->getFullQty() - (float) $this->getReceivedQty();
                $isReturn = ($qty < 0);
                if (abs($qty) <= 0) {
                    $html .= BimpRender::renderAlerts('Toutes les unités de cette ligne de commande fournisseur ont déjà été réceptionnées', 'warning');
                } else {
                    $html .= BimpRender::renderAlerts('Ce formulaire est destiné à ajouter des unités à une ou plusieurs réceptions existantes.</br>La création de nouvelles réceptions se fait via le bouton "Nouvelle réception"', 'info');

                    $html .= '<div id="commande_fourn_line_' . $this->id . '_receptions_rows" class="line_reception_rows line_' . $this->id . '_reception_max_container" data-id_line="' . $this->id . '" data-id_commande="' . $commande->id . '">';
                    $html .= '<table class="bimp_list_table">';
                    $html .= '<thead>';
                    $html .= '<tr>';
                    $html .= '<th>Réception</th>';
                    $html .= '<th>' . ($isSerialisable ? ($isReturn ? 'Equipements retournés' : 'Numéros de série') : 'Qté') . '</th>';
                    $html .= '<th>Prix d\'achat (remises incluses)</th>';
                    $html .= '<th>Tx TVA</th>';
                    $html .= '<th></th>';
                    $html .= '</tr>';
                    $html .= '</thead>';

                    $html .= '<tbody class="receptions_rows">';

                    $tpl = $this->renderReceptionFormRowTpl(true);

                    $html .= '<tr class="line_' . $this->id . '_reception_row_tpl line_reception_row_tpl subObjectFormTemplate" style="display: none" data-next_idx="2">';
                    $html .= $tpl;
                    $html .= '</tr>';

                    $html .= '<tr class="line_' . $this->id . '_reception_row line_reception_row" data-idx="1">';
                    $tpl = str_replace('receptionidx', '1', $tpl);
                    $tpl = str_replace('linetotalmaxinputclass', 'line_' . $this->id . '_reception_max', $tpl);
                    $tpl = str_replace('linetotalmininputclass', 'line_' . $this->id . '_reception_min', $tpl);
                    $tpl .= '<td></td>';
                    $html .= $tpl;

                    $html .= '</tr>';

                    $html .= '</tbody>';
                    $html .= '</table>';

                    if (!$isReturn && $this->getData('linked_object_name') === 'commande_line') {
                        $commande_line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $this->getData('linked_id_object'));
                        if (BimpObject::objectLoaded($commande_line)) {
                            $commande_client = $commande_line->getParentInstance();
                            if (BimpObject::objectLoaded($commande)) {
                                $html .= '<div style="margin: 15px 0; padding: 10px 0;">';
                                $html .= 'Commande client associée: ' . $commande_client->getNomUrl(1, 1, 1) . '<br/>';
                                $product = $this->getProduct();
                                if (BimpObject::objectLoaded($product)) {
                                    $html .= '<div style="vertical-align: top">';
                                    $html .= '<span style="display: inline-block; padding-top: 6px; vertical-align: top">';
                                    $html .= 'Assigner les unités reçues à cette commande client: ';
                                    $html .= '</span>';
                                    $html .= BimpInput::renderInput('toggle', 'assign_to_commande_client', 1);
                                    $html .= '</div>';
                                }
                                $html .= '</div>';
                            }
                        }
                    }

                    $html .= '<div class="ajaxResultsContainer" style="display: none"></div>';

                    if (!$isReturn) {
                        $html .= '<div class="buttonsContainer align-right">';
                        $html .= '<span class="btn btn-default" onclick="addCommandeFournLineReceptionRow($(this), ' . $this->id . ');">';
                        $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Ajouter une réception';
                        $html .= '</span>';
                        $html .= '</div>';
                    }

                    $html .= '</div>';
                }
            }
        } else {
            $html .= BimpRender::renderAlerts('Aucun produit à réceptionner pour cette commande fournisseur', 'info');
        }

        return $html;
    }

    public function renderReceptionFormRowTpl($include_receptions = false)
    {
        $qty = (float) $this->getFullQty() - (float) $this->getReceivedQty();

        if (abs($qty) <= 0) {
            return '';
        }

        $isReturn = ($this->getFullQty() < 0);

        $product = $this->getProduct();
        if (!BimpObject::objectLoaded($product)) {
            return '';
        }

        $tpl = '';

        if ($include_receptions) {
            $commande = $this->getParentInstance();
            $receptions = array();
            if (BimpObject::objectLoaded($commande)) {
                $receptions = $commande->getReceptionsArray(false);
            }

            $id_reception = 0;
            foreach ($receptions as $id_r => $reception_label) {
                $id_reception = (int) $id_r;
                break;
            }

            $tpl .= '<td>';
            $tpl .= BimpInput::renderInput('select', 'line_' . $this->id . '_reception_receptionidx_id_reception', $id_reception, array(
                        'options' => $receptions
            ));
            $tpl .= '</td>';
        }

        $tpl .= '<td>';
        if ($product->isSerialisable()) {
            if (!$isReturn) {
                $tpl .= BimpInput::renderInput('textarea', 'line_' . $this->id . '_reception_receptionidx_equipments', '', array(
                            'auto_expand' => true
                ));
                $tpl .= '<p class="inputHelp">N° de série. ';
                $tpl .= 'Séparateurs possibles: sauts de ligne, espaces, virgules ou points-virgules.<br/>';
                $tpl .= 'Max: ' . $qty . ' numéro' . ($qty > 1 ? 's' : '') . ' de série.';
                $tpl .= '</p>';
            } else {
                $input_name = 'line_' . $this->id . '_reception_receptionidx_return_equipments';
                $equipments_to_return = $this->getReturnableEquipmentsArray();
                $input_content = BimpInput::renderInput('select', $input_name . '_add_value', array(), array(
                            'options' => $equipments_to_return
                ));
                $content = BimpInput::renderMultipleValuesInput($this, $input_name, $input_content, array(), '', 0, 0, 1, abs($qty));
                $tpl .= BimpInput::renderInputContainer($input_name, '', $content, '', 0, 1, '', array(
                            'values_field' => $input_name
                ));
            }
        } else {
            $decimals = $this->getQtyDecimals();
            $input_options = array(
                'data'        => array(
                    'data_type' => 'number',
                    'decimals'  => $decimals,
                ),
                'max_label'   => 1,
                'extra_class' => ''
            );

            if (!$isReturn) {
                $input_options['data']['min'] = 0;
                $input_options['data']['max'] = $qty;
                $input_options['data']['total_max_value'] = $qty;
                $input_options['data']['total_max_inputs_class'] = 'line_' . $this->id . '_reception_max';
                $input_options['max_label'] = 1;
                $input_options['extra_class'] = 'total_max linetotalmaxinputclass';
            } else {
                $input_options['data']['min'] = $qty;
                $input_options['data']['max'] = 0;
                $input_options['data']['total_min_value'] = $qty;
                $input_options['data']['total_min_inputs_class'] = 'line_' . $this->id . '_reception_min';
                $input_options['max_label'] = 1;
                $input_options['extra_class'] = 'total_min linetotalmininputclass';
            }
            $tpl .= BimpInput::renderInput('qty', 'line_' . $this->id . '_reception_receptionidx_qty', $qty, $input_options);
        }

        $tpl .= '</td>';

        $tpl .= '<td>';
        $tpl .= BimpInput::renderInput('text', 'line_' . $this->id . '_reception_receptionidx_pu_ht', $this->getUnitPriceHTWithRemises(), array(
                    'addon_right' => BimpRender::renderIcon('fas_euro-sign'),
                    'data'        => array(
                        'data_type' => 'number',
                        'min'       => 'none',
                        'max'       => 'none',
                        'decimals'  => 6,
                        'unsigned'  => 0
                    ),
                    'style'       => 'width: 90px'
        ));
        $tpl .= '</td>';

        $tpl .= '<td>';
        $tpl .= BimpInput::renderInput('text', 'line_' . $this->id . '_reception_receptionidx_tva_tx', $this->tva_tx, array(
                    'addon_right' => BimpRender::renderIcon('fas_percent'),
                    'data'        => array(
                        'data_type' => 'number',
                        'min'       => 0,
                        'max'       => 100,
                        'decimals'  => 6,
                        'unsigned'  => 1
                    ),
                    'style'       => 'width: 90px'
        ));
        $tpl .= '</td>';

        return $tpl;
    }

    public function renderQtyModifiedInput()
    {
        if (!$this->isLoaded()) {
            return BimpRender::renderAlerts('ID de la ligne de commande fournisseur absent', 'danger');
        }
        $decimals = $this->getQtyDecimals();

        $full_qty = (float) $this->getFullQty();

        $max = 'none';
        $min = 'none';

        $min_label = 0;
        $max_label = 0;

        if ($full_qty >= 0) {
            $min = (float) $this->getMinQty();
            $min_label = 1;
        } else {
            $max = $this->getMinQty();
            $max_label = 1;
        }


        return BimpInput::renderInput('qty', 'qty_modified', $full_qty, array(
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

    // Traitements: 

    public function explodeSerials($serials)
    {
        if (is_string($serials) && $serials) {
            $serials = str_replace("\n", ';', $serials);
            $serials = str_replace(" ", ';', $serials);
            $serials = str_replace(",", ';', $serials);
            $serials = explode(';', $serials);
        }

        if (is_array($serials)) {
            foreach ($serials as $idx => $serial) {
                if (!(string) $serial) {
                    unset($serials[$idx]);
                }
            }
            return $serials;
        }

        return array();
    }

    public function checkReceptionData($id_reception, $data)
    {
        $errors = array();

        if (!((int) $id_reception)) {
            $errors[] = 'ID de la réception non spécifié';
            return $errors;
        }

        $reception = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeFournReception', (int) $id_reception);
        if (!BimpObject::objectLoaded($reception)) {
            $errors[] = 'La réception d\'ID ' . $id_reception . ' n\'existe pas';
            return $errors;
        }

        if ($reception->getData('status') === BL_CommandeFournReception::BLCFR_BROUILLON) {
            if ($this->isProductSerialisable()) {
                if ($isReturn) {
                    if (isset($data['serials']) && !empty($data['serials'])) {
                        $serials = array();
                        foreach ($data['serials'] as $serial_data) {
                            if (isset($serial_data['serial']) && (string) $serial_data['serial']) {
                                $serials[] = $serial_data['serial'];
                            }
                        }
                        if (count($serials)) {
                            $errors = $this->checkReceptionSerials($serials, $id_reception);
                        }
                    }
                } else {
                    $equipments = $data['return_equipments'];
                }
            } else {
                $errors = $this->checkReceptionQty((float) $data['qty'], $id_reception);
            }
        } else {
            // todo
        }

        return $errors;
    }

    public function checkReceptionQty($qty, $id_reception = 0)
    {
        $errors = array();

        if ($qty > 0) {
            $remain_qty = (float) $this->getReceptionAvailableQty($id_reception);

            if ($qty > $remain_qty) {
                $msg = 'Il ne reste que ' . $remain_qty . ' unité(s) à réceptionner pour cette ligne de commande fournisseur.<br/>';
                $msg .= 'Veuillez retirer ' . ($qty - $remain_qty) . ' unité(s)';
                $errors[] = $msg;
            }
        }

        return $errors;
    }

    public function checkReceptionSerials($serials, $id_reception = 0)
    {
        $errors = array();

        $commande = $this->getParentInstance();
        if (!BimpObject::objectLoaded($commande)) {
            $errors[] = 'ID de la commande fournisseur absent';
            return $errors;
        }

        if (!empty($serials)) {
            $serials = $this->explodeSerials($serials);
        } else {
            return array();
        }

        // Vérification des serials en double: 
        $serials_checked = array();
        foreach ($serials as $serial) {
            if ((string) $serial) {
                if (in_array($serial, $serials_checked)) {
                    $errors[] = 'Le numéro de série "' . $serial . '" a été ajouté deux fois pour cette réception';
                } else {
                    $serials_checked[] = $serial;
                }
            }
        }

        if (count($errors)) {
            return $errors;
        }

        // Vérification qu'un serial n'a pas déjà été attributé à une autre réception: 
        $lines = $commande->getChildrenObjects('lines', array(
            'type' => array(
                'in' => array(self::LINE_FREE, self::LINE_PRODUCT)
            )
        ));

        foreach ($lines as $line) {
            $receptions = $line->getData('receptions');
            if (is_array($receptions)) {
                foreach ($receptions as $id_r => $reception_data) {
                    if ((int) $line->id === (int) $this->id && (int) $id_r === (int) $id_reception) {
                        continue;
                    }

                    if (isset($reception_data['serials']) && !empty($reception_data['serials'])) {
                        foreach ($reception_data['serials'] as $serial_data) {
                            if (in_array($serial_data['serial'], $serials_checked)) {
                                $reception = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeFournReception', (int) $id_r);
                                $errors[] = 'Le numéro de série "' . $serial_data['serial'] . '" a déjà été utilisé pour la réception n°' . $reception->getData('num_reception') . ' (' . $reception->getRef() . ') de la ligne n°' . $line->getData('position');
                            }
                        }
                    }
                }
            }
        }

        if (count($errors)) {
            return $errors;
        }

        // Vérification qu'un équipement n'existe pas déjà pour chaque numéro de série:
        $id_product = (int) $this->id_product;
        if ($id_product) {
            $equipment = BimpObject::getInstance('bimpequipment', 'Equipment');
            foreach ($serials_checked as $serial) {
                if ($equipment->find(array(
                            'id_product' => $id_product,
                            'serial'     => $serial
                                ), true)) {
                    $errors[] = 'Un équipement existe déjà pour le numéro de série "' . $serial . '": ' . $equipment->getNomUrl(1, 0, 1, 'default') . '';
                }
            }
        }

        if (count($errors)) {
            return $errors;
        }

        $qty = count($serials_checked);

        $remain_qty = (float) $this->getReceptionAvailableQty($id_reception);

        if ($qty > $remain_qty) {
            $msg = 'Il ne reste que ' . $remain_qty . ' équipement(s) à réceptionner pour cette ligne de commande fournisseur.<br/>';
            $msg .= 'Veuillez retirer ' . ($qty - $remain_qty) . ' numéro(s) de série';
            $errors[] = $msg;
        }

        return $errors;
    }

    public function checkReceptionReturnedEquipments($equipments, $id_reception = 0)
    {
        $errors = array();

        $commande = $this->getParentInstance();
        
        if (!BimpObject::objectLoaded($commande)) {
            $errors[] = 'ID de la commande absent';
            return $errors;
        }
        
        $id_entrepot = (int) $commande->getData('entrepot');

        foreach ($equipments as $id_equipment) {
            $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
            if (!BimpObject::objectLoaded($equipment)) {
                $errors[] = 'L\'équipement d\'ID ' . $id_equipment . ' n\'existe pas';
            } else {
                $id_r = (int) $this->getEquipmentIdReception((int) $id_equipment);
                if ($id_r && (!$id_reception || $id_reception !== $id_r)) {
                    $errors[] = 'L\'équipement "' . $equipment->getData('serial') . '" a déjà été ajouté à la réception #' . $id_equipment;
                } else {
                    $eq_errors = $equipment->isAvailable($id_entrepot, $eq_errors);
                    if (count($eq_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($eq_errors);
                    }
                }
            }
        }

        return $errors;
    }

    public function setReceptionData($id_reception, $data, $check_data = true, &$warnings = array())
    {
        $errors = array();

        $reception = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeFournReception', (int) $id_reception);

        if (!BimpObject::objectLoaded($reception)) {
            $errors[] = 'La réception d\'ID ' . $id_reception . ' n\'existe pas';
            return $errors;
        }

        $isSerialisable = $this->isProductSerialisable();

        if ($isSerialisable) {
            $edit = 1;
            if ((int) $reception->getData('status') !== BL_CommandeFournReception::BLCFR_BROUILLON) {
                $edit = 0;
            }

            if ($edit) {
                if (!isset($data['serials'])) {
                    $data['serials'] = array();
                    $data['qty'] = 0;
                } else {
                    $data['qty'] = count($data['serials']);
                }
            } else {
                if (!isset($data['equipments'])) {
                    $data['equipments'] = array();
                    $data['qty'] = 0;
                } else {
                    $data['qty'] = count($data['equipments']);
                }
            }
        } else {
            if (!isset($data['qty'])) {
                $data['qty'] = 0;
                if (isset($data['qties'])) {
                    foreach ($data['qties'] as $qty_data) {
                        $data['qty'] += (float) $qty_data['qty'];
                    }
                }
            }
        }

        if ($check_data) {
            $errors = $this->checkReceptionData($id_reception, $data);
        }

        $receptions = $this->getData('receptions');
        $receptions[(int) $id_reception] = $data;

        $this->set('receptions', $receptions);
        $errors = $this->update($warnings, true);

        if (!count($errors)) {
            $reception->onLinesChange();
        }

        $errors = array_merge($errors, $warnings);

        $this->checkQties();

        return $errors;
    }

    public function validateReception($id_reception, $check_data = true)
    {
        $errors = array();

        $reception_data = $this->getReceptionData((int) $id_reception);

        if (!isset($reception_data['qty']) || !(float) $reception_data['qty']) {
            return array();
        }

        if (!$this->isLoaded()) {
            $errors[] = 'ID de la ligne de commande fournisseur absent';
            return $errors;
        }

        $product = $this->getProduct();
        if (!BimpObject::objectLoaded($product)) {
            if ((int) $this->id_product) {
                $errors[] = 'Le produit d\'ID ' . $this->id_product . ' n\'existe pas';
            } else {
                $errors[] = 'Aucun produit associé à cette ligne de commande fournisseur';
            }
            return $errors;
        }

        $isSerialisable = $product->isSerialisable();

        $reception = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeFournReception', (int) $id_reception);
        if (!BimpObject::objectLoaded($reception)) {
            $errors[] = 'La réception d\'ID ' . $id_reception . ' n\'existe pas';
            return $errors;
        }

        $entrepot = $reception->getChildObject('entrepot');

        if (!BimpObject::objectLoaded($entrepot)) {
            $errors[] = 'Entrepot absent de la réception ou invalide';
            return $errors;
        }

        $commande_fourn = $this->getParentInstance();
        if (!BimpObject::objectLoaded($commande_fourn)) {
            $errors[] = 'ID de la commande fournisseur absent';
            return $errors;
        }

        if ($check_data) {
            $errors = $this->checkReceptionData($id_reception, $reception_data);
            if (count($errors)) {
                return $errors;
            }
        }

        $equipments = array();

        $stock_label = 'Réception n°' . $reception->getData('num_reception') . ' BR: ' . $reception->getData('ref') . ' - Commande fournisseur: ' . $commande_fourn->getData('ref');
        $code_mvt = 'CMDF_' . $commande_fourn->id . '_LN_' . $this->id . '_RECEP_' . $reception->id;

        if ($isSerialisable) {
            $reception_data['equipments'] = array();
            foreach ($reception_data['serials'] as $serial_data) {
                // Création de l'équipement: 
                $equipment = BimpObject::getInstance('bimpequipment', 'Equipment');

                $pu_ht = (isset($serial_data['pu_ht']) ? (float) $serial_data['pu_ht'] : (float) $this->getUnitPriceHTWithRemises());
                $tva_tx = (isset($serial_data['tva_tx']) ? (float) $serial_data['tva_tx'] : (float) $this->tva_tx);

                $eq_errors = $equipment->validateArray(array(
                    'id_product'    => $product->id,
                    'type'          => 1, // Dans produits? à sélectionner ? 
                    'serial'        => $serial_data['serial'],
                    'date_purchase' => $commande_fourn->getData('date_commande'),
                    'prix_achat'    => $pu_ht,
                    'achat_tva_tx'  => $tva_tx
                ));

                $eq_warnings = array();
                if (!count($eq_errors)) {
                    $eq_errors = $equipment->create($eq_warnings, true);
                }

                if (count($eq_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($eq_errors, 'Echec de la création de l\'équipement pour le numéro de série "' . $serial_data['serial'] . '"');
                }

                if (count($eq_warnings)) {
                    $errors[] = BimpTools::getMsgFromArray($eq_errors, 'Des erreurs sont survenues suite à la création de l\'équipement pour le numéro de série "' . $serial_data['serial'] . '"');
                }

                if (!count($eq_errors)) {
                    $equipments[] = $equipment->id;
                    $reception_data['equipments'][(int) $equipment->id] = array(
                        'pu_ht'  => $pu_ht,
                        'tva_tx' => $tva_tx
                    );

                    // Création de l'emplacement: 
                    $place = BimpObject::getInstance('bimpequipment', 'BE_Place');
                    $pl_errors = $place->validateArray(array(
                        'id_equipment' => (int) $equipment->id,
                        'type'         => BE_Place::BE_PLACE_ENTREPOT,
                        'date'         => date('Y-m-d H:i:s'),
                        'id_entrepot'  => (int) $entrepot->id,
                        'infos'        => $stock_label,
                        'code_mvt'     => $code_mvt
                    ));
                    if (!count($pl_errors)) {
                        $pl_warnings = array();
                        $pl_errors = $place->create($pl_warnings, true);
                    }

                    // Commenté car si erreurs, la validation de la réception sera annulée
//                    if (count($pl_errors)) {
//                        $errors[] = BimpTools::getMsgFromArray($pl_errors, 'Echec de la création de l\'emplacement pour le numéro de série "' . $serial . '"');
//                        $msg = 'ECHEC CREATION EMPLACEMENT EQUIPEMENT - A CORRIGER MANUELLEMENT' . "\n";
//                        $msg .= 'Plateforme: ' . DOL_URL_ROOT . ' - Equipement: ' . $equipment->id . ' - Entrepot: ' . $entrepot->id;
//                        $msg .= 'Erreurs:' . "\n";
//                        $msg .= print_r($pl_errors, 1);
//                        dol_syslog($msg, LOG_ERR);
//                        mailSyn2('[ERREUR]', 'debugerp@bimp.fr', 'BIMP<no-reply@bimp.fr>', $msg);
//                    }
                }
            }
        } else {
            // Incrémentation des stocks produit: 
            global $user;
            $product->dol_object->correct_stock($user, $entrepot->id, (int) $reception_data['qty'], 0, $stock_label, $code_mvt);
        }

        if ($this->getData('linked_object_name') === 'commande_line') {
            $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $this->getData('linked_id_object'));
            if (!BimpObject::objectLoaded($line)) {
                $errors[] = 'La ligne de commande client associée n\'existe plus';
            } else {
                if ($isSerialisable && isset($reception_data['assign_to_commande_client']) && (int) $reception_data['assign_to_commande_client']) {
                    // Attribution des équipements si serialisable.
                    $line->addEquipments($equipments);
                } else {
                    if (isset($reception_data['assign_to_commande_client']) && (int) $reception_data['assign_to_commande_client']) {
                        $new_status = 200;
                    } else {
                        $new_status = 101;
                    }

                    // Màj statuts.
                    $line->addReceivedQty((int) $reception_data['qty'], $new_status);
                }

                // Mise à jour du prix d'achat moyen pondéré pour la ligne de commande client si non sérialisable: 
                if (!$isSerialisable) {
                    $pa_total = 0;
                    $pa_qty = 0;

                    if (isset($reception_data['qties'])) {
                        foreach ($reception_data['qties'] as $qty_data) {
                            if (isset($qty_data['pu_ht']) && isset($qty_data['qty'])) {
                                $pa_total += ((float) $qty_data['pu_ht'] * (float) $qty_data['qty']);
                                $pa_qty += (float) $qty_data['qty'];
                            }
                        }
                    }

                    if ($pa_qty < (float) $reception_data['qty']) {
                        $pa_total += (((float) $reception_data['qty'] - $pa_qty) * (float) $this->getUnitPriceHTWithRemises());
                    }

                    if ((float) $reception_data['qty'] > 0) {
                        $pa_moyen = $pa_total / (float) $reception_data['qty'];
                    } else {
                        $pa_moyen = 0;
                    }

                    $line_qty = (float) $line->getFullQty();
                    if ($pa_moyen && $line_qty) {
                        $line_pa = (float) $line->pa_ht;
//                        $remise_pa = (float) $line->getData('remise_pa');
//                        if ($remise_pa) {
//                            $line_pa -= ((float) $line->pa_ht * ($remise_pa / 100));
//                        }

                        $new_line_pa = (float) ((((float) $line_pa * ($line_qty - (float) $reception_data['qty'])) + ($pa_moyen * (float) $reception_data['qty'])) / $line_qty);

//                        if ($remise_pa) {
//                            $new_line_pa = ($new_line_pa / (1 - ($remise_pa / 100)));
//                        }

                        if ($new_line_pa !== (float) $line->pa_ht) {
                            $line->setPrixAchat($new_line_pa);
                        }
                    }
                }
            }
        }

        $reception_data['received'] = 1;

        $receptions = $this->getData('receptions');

        $receptions[(int) $reception->id] = $reception_data;

        $this->set('receptions', $receptions);
        $up_warnings = array();
        $up_errors = $this->update($up_warnings, true);

        $up_errors = array_merge($up_errors, $up_warnings);

        if (count($up_errors)) {
            $errors[] = BimpTools::getMsgFromArray($up_errors, 'Erreurs lors de la mise à jour de la ligne de commande fournisseur');
        }

        $this->checkQties();

        return $errors;
    }

    public function cancelReceptionValidation($id_reception, &$warnings = array())
    {
        $errors = array();
        global $user;

        $reception_data = $this->getReceptionData($id_reception);

        if ((int) $reception_data['received']) {
            $reception = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeFournReception', (int) $id_reception);
            if (!BimpObject::objectLoaded($reception)) {
                $errors = 'La réception d\'ID ' . $id_reception . ' n\'existe pas';
                return $errors;
            }

            $commande_line = null;
            $id_commande_client_line = 0;

            if ($this->getData('linked_object_name') === 'commande_line') {
                $commande_line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $this->getData('linked_id_object'));
                if (BimpObject::objectLoaded($commande_line)) {
                    $id_commande_client_line = (int) $commande_line->id;
                }
            }

            $product = $this->getProduct();
            $commande_fourn = $this->getParentInstance();
            $id_entrepot = (int) $reception->getData('id_entrepot');

            if (($this->isProductSerialisable())) {
                foreach ($reception_data['equipments'] as $id_equipment => $equipment_data) {

                    $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                    if (BimpObject::objectLoaded($equipment)) {

                        $place = $equipment->getCurrentPlace();
                        if (BimpObject::objectLoaded($place)) {
                            if ((int) $place->getData('type') === BE_Place::BE_PLACE_ENTREPOT &&
                                    (int) $place->getData('id_entrepot') === $id_entrepot) {
                                // Remise au statut "en attente de réception" de la réservation correspondante: 
                                $reservation = BimpCache::findBimpObjectInstance('bimpreservation', 'BR_Reservation', array(
                                            'id_equipment'            => (int) $equipment->id,
                                            'status'                  => 200,
                                            'id_commande_client_line' => array(
                                                'operator' => '>',
                                                'value'    => 0
                                            )
                                                ), true);

                                if (BimpObject::objectLoaded($reservation)) {
                                    if ($id_commande_client_line && ((int) $reservation->getData('id_commande_client_line') === (int) $id_commande_client_line)) {
                                        $new_status = 100;
                                    } else {
                                        $new_status = 2;
                                    }
                                    $res_errors = $reservation->setNewStatus($new_status);

                                    if (count($res_errors)) {
                                        $warnings[] = BimpTools::getMsgFromArray($res_errors, 'Echec de la mise à jour de la réservation pour l\'équipement "' . $equipment->getData('serial') . '" (ID: ' . $equipment->id . ')');
                                    }
                                }

                                // Suppr de l'équipement 
                                $eq_warnings = array();
                                $eq_errors = $equipment->delete($eq_warnings, true);

                                if (count($eq_errors)) {
                                    $warnings[] = BimpTools::getMsgFromArray($eq_errors, 'Echec de la suppression de l\'équipement "' . $equipment->getData('serial') . '" (ID: ' . $equipment->id . ')');
                                }

                                if (count($eq_warnings)) {
                                    $warnings[] = BimpTools::getMsgFromArray($eq_warnings, 'Erreurs lors de la suppression de l\'équipement "' . $equipment->getData('serial') . '" (ID: ' . $equipment->id . ')');
                                }
                            }
                        }
                    }
                }
            } else {
                if ((float) $reception_data['qty'] > 0) {
                    // Traitement de la réservation correspondante: 
                    if ($id_commande_client_line) {
                        if (isset($reception_data['assign_to_commande_client']) && (int) $reception_data['assign_to_commande_client']) {
                            $status = 200;
                        } else {
                            $status = 101;
                        }

                        $reservation = BimpCache::findBimpObjectInstance('bimpreservation', 'BR_Reservation', array(
                                    'id_commande_client_line' => (int) $id_commande_client_line,
                                    'status'                  => $status
                                        ), true);
                        if (BimpObject::objectLoaded($reservation)) {
                            $res_errors = $reservation->setNewStatus(100, (int) $reception_data['qty']);

                            if (count($errors)) {
                                $errors[] = BimpTools::getMsgFromArray($res_errors, 'Erreurs lors de la mise à jour de la réservation correspondante');
                            }
                        }
                    }

                    if (!count($errors)) {
                        // Retrait du stock:
                        if (BimpObject::objectLoaded($product)) {
                            $stock_label = 'Annulation réception n°' . $reception->getData('num_reception') . ' BR: ' . $reception->getData('ref') . ' - Commande fournisseur: ' . $commande_fourn->getData('ref');
                            $code_mvt = 'ANNUL_CMDF_' . $commande_fourn->id . '_LN_' . $this->id . '_RECEP_' . $reception->id;

                            if ($product->dol_object->correct_stock($user, $id_entrepot, (int) $reception_data['qty'], 1, $stock_label, $code_mvt) <= 0) {
                                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($product->dol_object), 'Echec de la correction du stock');
                            }
                        }
                    }

                    // Mise à jour du prix d'achat moyen pondéré pour la ligne de commande client si non sérialisable: 

                    if ($id_commande_client_line) {
                        $pa_total = 0;
                        $pa_qty = 0;

                        if (isset($reception_data['qties'])) {
                            foreach ($reception_data['qties'] as $qty_data) {
                                if (isset($qty_data['pu_ht']) && isset($qty_data['qty'])) {
                                    $pa_total += ((float) $qty_data['pu_ht'] * (float) $qty_data['qty']);
                                    $pa_qty += (float) $qty_data['qty'];
                                }
                            }
                        }

                        if ($pa_qty < (float) $reception_data['qty']) {
                            $pa_total += (((float) $reception_data['qty'] - $pa_qty) * (float) $this->getUnitPriceHTWithRemises());
                        }

                        $line_qty = (float) $commande_line->getFullQty();
                        if ($pa_total && $line_qty) {
                            $line_pa = (float) $commande_line->pa_ht;
//                            $remise_pa = (float) $commande_line->getData('remise_pa');
//                            if ($remise_pa) {
//                                $line_pa -= ($line_pa * ($remise_pa / 100));
//                            }

                            $new_line_pa = (float) ((((float) $line_pa * $line_qty) - $pa_total) / ($line_qty - (float) $reception_data['qty']));


//                            if ($remise_pa) {
//                                $new_line_pa = ($new_line_pa / (1 - ($remise_pa / 100)));
//                            }

                            if ($new_line_pa !== (float) $commande_line->pa_ht) {
                                $commande_line->setPrixAchat($new_line_pa);
                            }
                        }
                    }
                }
            }

            $receptions = $this->getData('receptions');

            $reception_data['received'] = 0;
            $reception_data['equipments'] = array();
            $receptions[(int) $id_reception] = $reception_data;

            $up_errors = $this->updateField('receptions', $receptions);

            if (count($up_errors)) {
                $errors[] = BimpTools::getMsgFromArray($up_errors);
            }
        }

        $this->checkQties();

        return $errors;
    }

    public function unsetReception($id_reception)
    {
        $receptions = $this->getData('receptions');

        if (isset($receptions[(int) $id_reception])) {
            unset($receptions[(int) $id_reception]);

            $this->set('receptions', $receptions);
            $warnings = array();
            $errors = $this->update($warnings, true);
            $errors = array_merge($errors, $warnings);
        }


        $this->checkQties();

        return $errors;
    }

    public function checkQties()
    {
        if ($this->isLoaded()) {
            $fullQty = (float) $this->getFullQty();
            if ($fullQty !== (float) $this->getData('qty_total')) {
                $this->updateField('qty_total', $fullQty, null, true);
            }

            if ((int) $this->getData('type') !== self::LINE_TEXT) {
                $commande = $this->getParentInstance();

                if (BimpObject::objectLoaded($commande) && $commande->isLogistiqueActive()) {
                    $fullQty = abs($fullQty);
                    $received_qty = abs((float) $this->getReceivedQty(null, true));
                    $to_receive_qty = $fullQty - $received_qty;

                    if ($received_qty !== (float) $this->getData('qty_received')) {
                        $this->updateField('qty_received', $received_qty, null, true);
                    }

                    if ($to_receive_qty !== (float) $this->getData('qty_to_receive')) {
                        $this->updateField('qty_to_receive', $to_receive_qty, null, true);
                    }
                }
            }
        }
    }

    // Actions: 

    public function actionAddReceptions($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        if (!count($data)) {
            $errors[] = 'Aucunes données reçues';
        } else {
            $isSerialisable = $this->isProductSerialisable();
            $isReturn = ((float) $this->getFullQty() < 0);

            $commande = $this->getParentInstance();
            if (!BimpObject::objectLoaded($commande)) {
                $errors[] = 'ID de la commande fournisseur absent';
                return $errors;
            }

            $receptions = $this->getData('receptions');

            $i = 1;

            $new_receptions = array();

            // Trie des données: 
            foreach ($data as $reception_data) {
                if (!isset($reception_data['id_reception']) || !(int) $reception_data['id_reception']) {
                    $errors[] = 'Aucune réception sélectionnée pour l\'ajout n°' . $i;
                } else {
                    $reception = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeFournReception', (int) $reception_data['id_reception']);
                    if (!BimpObject::objectLoaded($reception)) {
                        $errors[] = 'La réception d\'ID ' . $reception_data['id_reception'] . ' n\'existe pas';
                    } elseif ((int) $reception->getData('status') !== BL_CommandeFournReception::BLCFR_BROUILLON) {
                        $errors[] = 'La réception n°' . $reception->getData('num_reception') . ' (' . $reception->getRef() . ') n\'est plus éditable';
                    } else {
                        if (!in_array($reception->id, $new_receptions)) {
                            $new_receptions[] = (int) $reception->id;
                        }
                        $pu_ht = (isset($reception_data['pu_ht']) ? (float) $reception_data['pu_ht'] : (float) $this->getUnitPriceHTWithRemises());
                        $tva_tx = (isset($reception_data['tva_tx']) ? (float) $reception_data['tva_tx'] : (float) $this->tva_tx);
                        if (!isset($receptions[(int) $reception->id])) {
                            $receptions[(int) $reception->id] = array(
                                'qty'                       => 0,
                                'qties'                     => array(),
                                'serials'                   => array(),
                                'equipments'                => array(),
                                'assign_to_commande_client' => (int) $reception->getData('assign_lines_to_commandes_client')
                            );
                        }
                        if (isset($reception_data['assign_to_commande_client'])) {
                            $receptions[(int) $reception->id]['assign_to_commande_client'] = (int) $reception_data['assign_to_commande_client'];
                        }
                        if ($isSerialisable) {
                            if (!$isReturn) {
                                $serials = $this->explodeSerials($reception_data['serials']);
                                if (!count($serials)) {
                                    $errors[] = 'Aucun numéro de série spécifié pour l\'ajout n°' . $i;
                                } else {
                                    foreach ($serials as $serial) {
                                        $receptions[(int) $reception->id]['serials'][] = array(
                                            'serial' => $serial,
                                            'pu_ht'  => $pu_ht,
                                            'tva_tx' => $tva_tx
                                        );
                                    }
                                }
                            } else {
                                
                            }
                        } else {
                            if (isset($reception_data['qty']) && (float) $reception_data['qty']) {
                                $receptions[(int) $reception->id]['qty'] += (float) $reception_data['qty'];
                                $receptions[(int) $reception->id]['qties'][] = array(
                                    'qty'    => (float) $reception_data['qty'],
                                    'pu_ht'  => $pu_ht,
                                    'tva_tx' => $tva_tx
                                );
                            }
                        }
                    }
                }
                $i++;
            }

            // Vérifications: 
            foreach ($new_receptions as $id_reception) {
                if (isset($receptions[(int) $id_reception])) {
                    $reception_errors = $this->checkReceptionData($id_reception, $receptions[(int) $id_reception]);
                    if (count($reception_errors)) {
                        $reception = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeFournReception', (int) $id_reception);
                        if (BimpObject::objectLoaded($reception)) {
                            $label = 'n°' . $reception->getData('num_reception') . ' (' . $reception->getRef() . ')';
                        } else {
                            $label = 'd\'ID ' . $id_reception;
                        }
                        $errors[] = BimpTools::getMsgFromArray($reception_errors, 'Réception ' . $label);
                    }
                }
            }

            if (count($errors)) {
                return $errors;
            }

            // Enregistrements: 
            foreach ($new_receptions as $id_reception) {
                if (isset($receptions[(int) $id_reception])) {
                    $reception = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeFournReception', (int) $id_reception);

                    $reception_warnings = array();
                    $reception_errors = $this->setReceptionData($id_reception, $receptions[(int) $id_reception], false, $reception_warnings);

                    $reception_errors = array_merge($reception_errors, $reception_warnings);

                    if (count($reception_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($reception_errors, 'Réception n°' . $reception->getData('num_reception') . ' (' . $reception->getData('ref') . ')');
                    } else {
                        $success .= ($success ? '<br/>' : '') . 'Mise à jour de la réception n°' . $reception->getData('num_reception') . ' (' . $reception->getData('ref') . ') effectuée avec succès';
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
            $errors[] = 'Nouvelles quantités de la ligne de commande fournisseur absentes';
        } else {
            $min = (float) $this->getMinQty();
            if ((float) $data['qty_modified'] < $min) {
                $msg = '';
                if ($min > 1) {
                    $msg .= $min . ' unités ont déjà été ajoutées à une réception';
                } else {
                    $msg .= $min . ' unité a déjà ajoutée à une réception';
                }

                $msg .= '<br/>Veuillez indiquer une quantité supérieure ou égale à ' . $min;
                $errors[] = $msg;
            } else {
                $qty_modified = (float) $data['qty_modified'] - (float) $this->qty;
                $errors = $this->updateField('qty_modif', $qty_modified);

                if (!count($errors)) {
                    $commande = $this->getParentInstance();

                    if (BimpObject::objectLoaded($commande)) {
                        $commande->checkReceptionStatus();
                    }
                }
            }
        }

        $this->checkQties();

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrides: 

    public function checkObject()
    {
        if ($this->isLoaded()) {
            $this->checkQties();
        }
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = array();
        $commande_fourn = $this->getParentInstance();

        if (!BimpObject::objectLoaded($commande_fourn)) {
            $errors[] = 'ID de la commande fournisseur absent';
            return $errors;
        }

//        if ((int) $commande_fourn->isBilled()) {
//            $errors[] = 'Une facture a été créée pour cette commande fournisseur';
//            return $errors;
//        }

        $is_extra_line = false;
        $current_commande_status = (int) $commande_fourn->getData('fk_statut');

        if ($current_commande_status !== 0) {
            $is_extra_line = true;

            $this->set('qty_modif', (float) $this->qty);
            $this->qty = 0;
            $commande_fourn->set('fk_statut', 0);
            $commande_fourn->dol_object->statut = 0;
        }

        $errors = parent::create($warnings, $force_create);

        if ($is_extra_line) {
            $commande_fourn->set('fk_statut', $current_commande_status);
            $commande_fourn->dol_object->statut = $current_commande_status;
            $commande_fourn->checkReceptionStatus();
        }

        if (!count($errors)) {
            $this->checkQties();
        }

        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $errors = parent::update($warnings, $force_update);

        if (count($errors)) {
            $this->checkQties();
        }

        return $errors;
    }

    // Méthodes statiques: 

    public static function checkAllQties()
    {

        set_time_limit(600);
        $instance = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFournLine');
        $rows = $instance->getList(array(), null, null, 'id', 'asc', 'array', array('id'));

        foreach ($rows as $r) {
            $line = BimpCache::getBimpObjectInstance($instance->module, $instance->object_name, (int) $r['id']);

            if (BimpObject::objectLoaded($line)) {
                $line->checkQties();
            }
        }
    }
}
