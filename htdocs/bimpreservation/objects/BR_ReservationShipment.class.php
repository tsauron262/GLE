<?php

class BR_reservationShipment extends BimpObject
{

    public $commande = null;

    // Getters: 

    public function isQtyEditable()
    {
        $shipment = $this->getParentInstance();
        if (BimpObject::objectLoaded($shipment)) {
            if ((int) $shipment->getData('status') < 2) {
                return 1;
            }
        }

        return 0;
    }

    public function isEditable($force_edit = false)
    {
        if (!$this->isLoaded()) {
            return 1;
        }
        
        $shipment = $this->getParentInstance();
        if (BimpObject::objectLoaded($shipment)) {
            if (in_array((int) $shipment->getData('status'), array(1, 4))) {
                return 1;
            }
        }

        return 0;
    }

    public function isShipmentInvoiced()
    {
        $shipment = $this->getParentInstance();
        if (BimpObject::objectLoaded($shipment)) {
            if ((int) $shipment->getData('id_facture')) {
                return 1;
            }
        }

        return 0;
    }

    public function isOrderInvoiced()
    {
        $commande = $this->getCommande();
        if (BimpObject::objectLoaded($commande)) {
            if ((int) $commande->getData('id_facture')) {
                return 1;
            }
        }

        return 0;
    }

    public function doAddToCreditNoteOnRemove($remove_from_order = null)
    {
        $commande = $this->getCommande();
        if (BimpObject::objectLoaded($commande)) {
            if ((int) $commande->getData('id_facture')) {
                if (is_null($remove_from_order)) {
                    $remove_from_order = (int) BimpTools::getValue('fields/remove_from_order', 0);
                }

                if ((int) $remove_from_order) {
                    return 1;
                }
                
                return 0;
            }
        }
        
        $shipment = $this->getParentInstance();
        if (BimpObject::objectLoaded($shipment)) {
            if ((int) $shipment->getData('id_facture')) {
                $status = $this->db->getValue('facture', 'fk_statut', '`rowid` = ' . (int) $shipment->getData('id_facture'));
                if (is_null($status) || (int) $status === 0) {
                    return 0;
                }
                return 1;
            }
        }

        return 0;
    }

    public function getCommande()
    {
        if (is_null($this->commande)) {
            $this->commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $this->getData('id_commande_client'));
        }

        return $this->commande;
    }

    public function getShipmentsArray()
    {
        $shipments = array();

        $id_commande_client = (int) $this->getData('id_commande_client');

        if (!$id_commande_client) {
            $ref = $this->getData('ref_reservation');
            if ($ref) {
                $reservation = BimpObject::getInstance($this->module, 'BR_Reservation');
                if ($reservation->find(array(
                            'ref'    => $ref,
                            'status' => 200
                                ), true)) {
                    $id_commande_client = $reservation->getData('id_commande_client');
                }
            }
        }
        if ($id_commande_client) {
            $cs = BimpObject::getInstance($this->module, 'BR_CommandeShipment');
            foreach ($cs->getList(array(
                'id_commande_client' => $id_commande_client,
                'status'             => 1
            )) as $row) {
                $shipments[(int) $row['id']] = 'Expédition n°' . $row['num_livraison'];
            }
        }

        return $shipments;
    }

    public function getBrOrderLine()
    {
        $id_commande = (int) $this->getData('id_commande_client');
        $id_commande_line = (int) $this->getData('id_commande_client_line');
//        echo $id_commande.', '.$id_commande_line; exit;
        if ($id_commande && $id_commande_line) {
            $orderLine = BimpObject::getInstance($this->module, 'BR_OrderLine');
            if ($orderLine->find(array(
                        'id_commande'   => $id_commande,
                        'id_order_line' => $id_commande_line,
                        'type'          => BR_OrderLine::PRODUIT
                    ))) {
                if ($orderLine->isLoaded()) {
                    return $orderLine;
                }
            }
        }

        return null;
    }

    public function getAvailableQty()
    {
        $qty = (int) $this->getData('qty');

        $product = $this->getChildObject('product');
        if (BimpObject::objectLoaded($product)) {
            if (!$product->isSerialisable()) {
                $reservation = BimpObject::getInstance($this->module, 'BR_Reservation');
                $list = $reservation->getList(array(
                    'ref'    => $this->getData('ref_reservation'),
                    'status' => 200
                        ), null, null, 'id', 'desc', 'array', array(
                    'qty'
                ));
                if (!is_null($list)) {
                    foreach ($list as $item) {
                        $qty += (int) $item['qty'];
                    }
                }
            }
        }

        return $qty;
    }

    public function getListExtraBtn()
    {
        $buttons = array();

        if ((int) $this->getData('qty') > 0) {
            $shipment = $this->getParentInstance();
            if (BimpObject::objectLoaded($shipment)) {
                if (in_array($shipment->getData('status'), array(2, 4))) {
                    $buttons[] = array(
                        'label'   => 'Retirer',
                        'icon'    => 'times',
                        'onclick' => $this->getJsActionOnclick('removeFromShipment', array(), array(
                            'form_name' => 'remove'
                        ))
                    );
                }
            }
        }

        return $buttons;
    }

    public function getAvoirsArray()
    {
        $avoirs = array(
            0 => 'Créer un nouvel avoir'
        );

        $commande = $this->getCommande();

        if (BimpObject::objectLoaded($commande)) {
            $asso = new BimpAssociation($commande, 'avoirs');
            foreach ($asso->getAssociatesList() as $id_avoir) {
                $avoir = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_avoir);
                if ($avoir->isLoaded()) {
                    if ((int) $avoir->dol_object->statut === (int) Facture::STATUS_DRAFT) {
                        $DT = new DateTime($this->db->db->iDate($avoir->dol_object->date_creation));
                        $avoirs[(int) $id_avoir] = $avoir->dol_object->ref . ' (créé le ' . $DT->format('d / m / Y à H:i') . ')';
                    }
                }
            }
        }

        krsort($avoirs);

        return $avoirs;
    }

    // Traitements:

    public function removeFromShipment($qty, $removeFromOrder = false, $defective = false, $id_avoir = 0)
    {
        $qty = (int) $qty;

        if ($qty > (int) $this->getData('qty')) {
            $qty = (int) $this->getData('qty');
        }

        $errors = array();

        $shipment = $this->getParentInstance();
        $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $this->getData('id_commande_client'));

        if (!BimpObject::objectLoaded($shipment)) {
            $errors[] = 'ID de l\'expédition absent';
        }

        if (!BimpObject::objectLoaded($commande)) {
            $errors[] = 'ID de la commande client absent';
        }

        if (count($errors)) {
            return $errors;
        }

        // Ajout à l'avoir: 
        $rebuild_facture = false;
        
        if (!$this->isOrderInvoiced() && $this->isShipmentInvoiced()) {
            $facture_status = (int) $this->db->getValue('facture', 'fk_statut', '`rowid` = ' . (int) $shipment->getData('id_facture'));
            if ($facture_status > 0) {
                $avoir_errors = array();
                $orderLine = BimpObject::getInstance($this->module, 'BR_OrderLine');
                $orderLine->find(array('id_order_line' => (int) $this->getData('id_commande_client_line')));

                if (BimpObject::objectLoaded($orderLine)) {
                    $avoir_errors = $orderLine->addToCreditNote($qty, $id_avoir, (int) $this->getData('id_equipment'));
                } else {
                    $avoir_errors[] = 'ID de la ligne de commande absent ou invalide';
                }

                if (count($avoir_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($avoir_errors, 'Echec de l\'ajout à l\'avoir');
                }
            } else {
                $rebuild_facture = true;
            }
        }

        // Remise en stock:
        if ((int) $shipment->getData('status') === 2) {
            $ref_commande = $commande->dol_object->ref;

            // Traitement des stocks et emplacement: 
            if ((int) $this->getData('id_equipment')) {
                $place = BimpObject::getInstance('bimpequipment', 'BE_Place');
                $place_errors = $place->validateArray(array(
                    'id_equipment' => (int) $this->getData('id_equipment'),
                    'type'         => BE_Place::BE_PLACE_ENTREPOT,
                    'id_entrepot'  => (int) $shipment->getData('id_entrepot'),
                    'infos'        => 'Retrait de l\'expédition n°' . $shipment->getData('num_livraison') . ' pour la commande "' . $ref_commande . '"',
                    'date'         => date('Y-m-d H:i:s'),
                    'code_mvt'     => dol_print_date(dol_now(), '%y%m%d%H%M%S')
                ));

                if (!count($place_errors)) {
                    $place_errors = $place->create();
                }

                if (count($place_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($place_errors, 'Echec de la création du nouvel emplacement pour l\'équipement d\'ID ' . $this->getData('id_equipment'));
                }
            } else {
                $product = $this->getChildObject('product');
                if (!BimpObject::objectLoaded($product)) {
                    $errors[] = 'Aucun produit trouvé pour la ligne d\'expédition d\'ID ' . $this->id;
                } else {
                    if ($product->isSerialisable()) {
                        $errors[] = 'Numéro de série obligatoire pour le produit "' . $product->dol_object->label . '" (ID ' . $product->id . ')';
                    } else {
                        global $user;
                        $stock_label = 'Retrait de l\'expédition n°' . $shipment->getData('num_livraison') . ' pour la commande "' . $ref_commande . '"';
                        if ($product->dol_object->correct_stock($user, (int) $shipment->getData('id_entrepot'), $qty, 0, $stock_label, 0, dol_print_date(dol_now(), '%y%m%d%H%M%S'), 'commande', (int) $this->getData('id_commande_client')) <= 0) {
                            $errors[] = 'Echec de la mise à jour des stocks pour le produit "' . $product->dol_object->label . '" (ID ' . $product->id . ', quantités à ajouter: ' . $qty . ')';
                        }
                    }
                }
            }
        }

        if (!$removeFromOrder) {
            // Mise à jour des réservations: 
            $up_qty_errors = $this->updateReservationsQty(-$qty);
            if (count($up_qty_errors)) {
                $errors[] = BimpTools::getMsgFromArray($up_qty_errors, 'Des erreurs sont survenues lors de la mise à jour des statuts des réservations correspondantes');
            }
        } else {
            // Retrait des commandes: - L'ajout à l'avoir sera fait par Bimp_Commande::removeOrderLine() si le produit a été facturé hors expéditions. 
            $reservation = BimpObject::getInstance($this->module, 'BR_Reservation');
            if (in_array($shipment->getData('status'), array(1, 4))) {
                $status = 250;
            } else {
                $status = 300;
            }
            if ($reservation->find(array(
                        'ref'          => $this->getData('ref_reservation'),
                        'id_equipment' => (int) $this->getData('id_equipment'),
                        'status'       => $status
                    ))) {
                $remove_errors = $reservation->removeFromOrder($qty, $defective, $id_avoir);
                if (count($remove_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($remove_errors, 'Des erreurs sont survenues lors du retrait de la commande client');
                }
            } else {
                $errors[] = 'Réservation correspondante non trouvée';
            }
        }

        // mise à jour des qtés livrées: 
        $up_qty_errors = $this->updateOrderLineShippedQty(-$qty);
        if (count($up_qty_errors)) {
            $errors[] = BimpTools::getMsgFromArray($up_qty_errors, 'Des erreurs sont survenues lors de la mise à jour des quantités livrées pour la ligne de commande');
        }

        $new_qty = ((int) $this->getData('qty')) - $qty;

        if ($new_qty <= 0) {
            $del_warnings = array();
            $del_errors = $this->delete($del_warnings, true);
            if (count($del_errors)) {
                $errors[] = BimpTools::getMsgFromArray($del_errors, 'Echec de la suppression de la ligne d\'expédition');
            }
        } else {
            $up_errors = $this->updateField('qty', $new_qty);
            if (count($up_errors)) {
                $errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec de la mise à jour des quantités de la ligne d\'expédition');
            }
        }
        
        if ($rebuild_facture) {
            $fac_errors = $shipment->rebuildFacture();
            if (count($fac_errors)) {
                $errors[] = BimpTools::getMsgFromArray($fac_errors, 'Echec de la reconstruction de la facture pour cette expédition');
            }
        }

        $commande->checkIsFullyShipped();
        $commande->checkIsFullyInvoiced();

        return $errors;
    }

    public function updateOrderLineShippedQty($modif_qty)
    {
        $errors = array();

        $orderLine = $this->getBrOrderLine();

        if (BimpObject::objectLoaded($orderLine)) {
            $shipped_qty = (int) $orderLine->getData('qty_shipped');
            $new_qty = (int) $shipped_qty + (int) $modif_qty;
            $orderLine->set('qty_shipped', $new_qty);
            $errors = $orderLine->update();
        } else {
            $errors[] = 'ID de la ligne de commande absent ou invalide';
        }

        return $errors;
    }

    public function updateReservationsQty($modif_qty)
    {
        $errors = array();

        $modif_qty = (int) $modif_qty;

        if ($modif_qty === 0) {
            return $errors;
        }

        $reservation = BimpObject::getInstance($this->module, 'BR_Reservation');

        if ($modif_qty > 0) {
            if ($reservation->find(array(
                        'ref'          => $this->getData('ref_reservation'),
                        'status'       => 200,
                        'id_equipment' => (int) $this->getData('id_equipment')
                    ))) {
                $available_qty = (int) $reservation->getData('qty');
                if ($modif_qty > $available_qty) {
                    $diff = $modif_qty - $available_qty;
                    $this->updateField('qty', (int) $this->getData('qty') - (int) $diff);
                    if ($diff > 1) {
                        $errors[] = $diff . ' unités n\'ont pas pu être ajoutées car non disponibles';
                    } else {
                        $errors[] = $diff . ' unité n\'a pas pu être ajoutée car non disponible';
                    }
                    $modif_qty = $available_qty;
                    if ($modif_qty <= 0) {
                        return $errors;
                    }
                }
                $id_equipment = (int) $this->getData('id_equipment');
                $errors = array_merge($errors, $reservation->setNewStatus(250, $modif_qty, ($id_equipment ? $id_equipment : null)));
            } else {
                $errors[] = 'Aucun produit disponible trouvé';
                $this->updateField('qty', (int) $this->getData('qty') - (int) $modif_qty);
            }
        } else {
            $shipment = $this->getParentInstance();
            if (BimpObject::objectLoaded($shipment)) {
                if ((int) $shipment->getData('status') === 2) {
                    $status = 300;
                } else {
                    $status = 250;
                }
                $modif_qty *= -1;
                if ($reservation->find(array(
                            'ref'          => $this->getData('ref_reservation'),
                            'status'       => $status,
                            'id_equipment' => (int) $this->getData('id_equipment')
                        ))) {
                    $available_qty = (int) $reservation->getData('qty');
                    if ($modif_qty > $available_qty) {
                        $diff = $modif_qty - $available_qty;
                        $this->updateField('qty', (int) $this->getData('qty') + (int) $diff);
                        if ($diff > 1) {
                            $errors[] = $diff . ' unités n\'ont pas pu être retirées car non disponibles';
                        } else {
                            $errors[] = $diff . ' unité n\'a pas pu être retirée car non disponible';
                        }
                        $modif_qty = $available_qty;
                        if ($modif_qty <= 0) {
                            return $errors;
                        }
                    }
                    $errors = array_merge($errors, $reservation->setNewStatus(200, $modif_qty, null));
                } else {
                    $errors[] = 'Aucun produit pouvant être retiré de l\'expédition trouvé';
                    $this->updateField('qty', (int) $this->getData('qty') + (int) $modif_qty);
                }
            } else {
                $errors[] = 'Erreur technique: Expédition absente ou invalide';
            }
        }

        return $errors;
    }

    // Rendus:

    public function renderRemoveQtyInput()
    {
        return BimpInput::renderInput('qty', 'qty', (int) $this->getData('qty'), array(
                    'data' => array(
                        'data_type' => 'number',
                        'decimals'  => 0,
                        'unsigned'  => 1,
                        'min'       => 1,
                        'max'       => (int) $this->getData('qty')
                    )
        ));
    }

    // Actions:

    public function actionRemoveFromShipment($data, &$success)
    {
        $errors = array();
        $warnings = array();

        if (!isset($data['remove_from_order'])) {
            $data['remove_from_order'] = 0;
        }

        if (!isset($data['defective'])) {
            $data['defective'] = 0;
        }

        if (!isset($data['id_avoir'])) {
            $data['id_avoir'] = 0;
        }

        if (isset($data['qty']) && (int) $data['qty'] > 0) {
            $errors = $this->removeFromShipment((int) $data['qty'], (int) $data['remove_from_order'], (int) $data['defective'], (int) $data['id_avoir']);
        } else {
            $errors[] = 'Aucune quantité à retirer spécifiée';
        }

        $success = 'Produit retiré de l\'expédition ';
        if ($data['remove_from_order']) {
            $success .= ' et de la commande ';
        }
        $success .= 'avec succès';

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrides:

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = array();

        $ref_reservation = $this->getData('ref_reservation');
        if (!$ref_reservation) {
            $errors[] = 'Référence de la réservation absente';
        }

        $id_shipment = (int) $this->getData('id_shipment');
        if (!$id_shipment) {
            $errors[] = 'ID de l\'expédition absent';
        }

        if ((int) $this->getData('qty') <= 0) {
            $errors[] = 'Veuillez indiquer une quantité supérieure à 0';
        }

        if (!count($errors)) {
            $reservation = BimpObject::getInstance($this->module, 'BR_Reservation');
            if ($reservation->find(array(
                        'ref'          => $ref_reservation,
                        'status'       => 200,
                        'id_equipment' => (int) $this->getData('id_equipment')
                    ))) {
                if ((int) $this->getData('qty') > (int) $reservation->getData('qty')) {
                    $this->set('qty', (int) $reservation->getData('qty'));
                }
                $id_commande_client = (int) $reservation->getData('id_commande_client');
                $id_commande_client_line = (int) $reservation->getData('id_commande_client_line');
                $id_product = (int) $reservation->getData('id_product');
                $id_equipment = (int) $reservation->getData('id_equipment');

                if (!$id_commande_client) {
                    $errors[] = 'ID de la commande client absent';
                }
                if (!$id_commande_client_line) {
                    $errors[] = 'ID de la ligne de commande client absent';
                }
                if (!$id_product) {
                    $errors[] = 'ID du produit absent';
                } elseif ($reservation->isProductSerialisable()) {
                    if (!$id_equipment) {
                        $errors[] = 'ID de l\'équipement absent';
                    }
                }

                if (count($errors)) {
                    return $errors;
                }

                $this->set('id_commande_client', $id_commande_client);
                $this->set('id_commande_client_line', $id_commande_client_line);
                $this->set('id_product', $id_product);
                $this->set('id_equipment', $id_equipment);

                $errors = parent::create($warnings, $force_create);

                if ($this->isLoaded()) {
                    $res_errors = $this->updateReservationsQty((int) $this->getData('qty'));
                    if (count($res_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($res_errors, 'Des erreurs sont survenues lors de la mise à jour des statuts des réservations correspondantes');
                    }

                    $qty = (int) $this->getData('qty');
                    if ($qty > 0) {
                        $orderLineErrors = $this->updateOrderLineShippedQty($qty);
                        if (count($orderLineErrors)) {
                            $warnings[] = BimpTools::getMsgFromArray($orderLineErrors, 'Echec de la mise à jour des quantités livrées pour la ligne de commande correspondante');
                        }
                    }
                }
            } else {
                $errors[] = 'Aucune réservation au statut "' . BR_Reservation::$status_list[200]['label'] . '" trouvée pour la référence "' . $ref_reservation . '"';
            }
        }

        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $current_qty = (int) $this->getSavedData('qty');

        $errors = parent::update($warnings, $force_update);

        if (!count($errors)) {
            $new_qty = (int) $this->getData('qty');
            $diff = $new_qty - $current_qty;
            if ($diff !== 0) {
                $res_errors = $this->updateReservationsQty($diff);
                if (count($res_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($res_errors, 'Des erreurs sont survenues lors de la mise à jour des statuts des réservations correspondantes');
                }

                $new_qty = (int) $this->getData('qty');
                $diff = $new_qty - $current_qty;
                $orderLineErrors = $this->updateOrderLineShippedQty($diff);
                if (count($orderLineErrors)) {
                    $warnings[] = BimpTools::getMsgFromArray($orderLineErrors, 'Echec de la mise à jour des quantités livrées pour la ligne de commande correspondante');
                }
            }

            if ((int) $this->getData('qty') === 0) {
                $del_warnings = array();
                $this->delete($del_warnings, true);
            }
        }

        return $errors;
    }

//    public function delete()
//    {
////        $errors = array();
//
////        if ((int) $this->getData('qty') > 0) {
////            $errors = $this->removeFromShipment((int) $this->getData('qty'));
////        }
//
////        if (!count($errors)) {
//            $errors = parent::delete();
////        }
//
//        return $errors;
//    }
        }
