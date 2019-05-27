<?php

//class Bimp_Commande {
//    public function isFullyShipped()
//    {
//        if ($this->isLoaded()) {
//            $total_qty = 0;
//
//            $orderLines = $this->getChildrenObjects('order_lines');
//
//            foreach ($orderLines as $line) {
//                $total_qty += (int) $line->getData('qty');
//            }
//
//            $shipment_instance = BimpObject::getInstance('bimpreservation', 'BR_CommandeShipment');
//            $rs = BimpObject::getInstance('bimpreservation', 'BR_ReservationShipment');
//            $ss = BimpObject::getInstance('bimpreservation', 'BR_ServiceShipment');
//            foreach ($shipment_instance->getList(array(
//                'id_commande_client' => (int) $this->id,
//                'status'             => 2
//                    ), null, null, 'id', 'asc', 'object', array('id')) as $shipment) {
//                foreach ($rs->getList(array(
//                    'id_commande_client' => (int) $this->id,
//                    'id_shipment'        => (int) $shipment->id
//                        ), null, null, 'id', 'asc', 'object', array('qty')) as $item) {
//                    $total_qty -= $item->qty;
//                }
//                foreach ($ss->getList(array(
//                    'id_commande_client' => (int) $this->id,
//                    'id_shipment'        => (int) $shipment->id
//                        ), null, null, 'id', 'asc', 'object', array('qty')) as $item) {
//                    $total_qty -= $item->qty;
//                }
//            }
//
//            if ($total_qty <= 0) {
//                return 1;
//            }
//        }
//
//        return 0;
//    }
//
//    public function isFullyInvoiced()
//    {
//        if ($this->isLoaded()) {
//            if ((int) $this->getData('id_facture')) {
//                return 1;
//            }
//
//            $total_qty = 0;
//
//            $orderLines = $this->getChildrenObjects('order_lines');
//
//            foreach ($orderLines as $line) {
//                $total_qty += (int) $line->getData('qty');
//            }
//
//            $shipment_instance = BimpObject::getInstance('bimplogistique', 'BL_CommandeShipment');
//            $rs = BimpObject::getInstance('bimpreservation', 'BR_ReservationShipment');
//            $ss = BimpObject::getInstance('bimpreservation', 'BR_ServiceShipment');
//
//            foreach ($shipment_instance->getList(array(
//                'id_commande_client' => (int) $this->id,
//                'id_facture'         => array(
//                    'operator' => '>',
//                    'value'    => 0
//                )
//                    ), null, null, 'id', 'asc', 'object', array('id')) as $shipment) {
//                foreach ($rs->getList(array(
//                    'id_commande_client' => (int) $this->id,
//                    'id_shipment'        => (int) $shipment->id
//                        ), null, null, 'id', 'asc', 'object', array('qty')) as $item) {
//                    $total_qty -= $item->qty;
//                }
//                foreach ($ss->getList(array(
//                    'id_commande_client' => (int) $this->id,
//                    'id_shipment'        => (int) $shipment->id
//                        ), null, null, 'id', 'asc', 'object', array('qty')) as $item) {
//                    $total_qty -= $item->qty;
//                }
//            }
//
//            if ($total_qty <= 0) {
//                return 1;
//            }
//        }
//
//        return 0;
//    }
//    
//    // Traitements divers (obsolètes):
//
//    public function addOrderLine($id_product, $qty = 1, $desc = '', $id_fournisseur_price = 0, $remise_percent = 0, $date_start = '', $date_end = '')
//    {
//        $errors = array();
//
//        if (!$this->isLoaded()) {
//            $errors[] = 'ID de la commande client absent';
//        }
//
//        if (!(int) $id_product) {
//            $errors[] = 'Produit absent';
//        }
//
//        if (count($errors)) {
//            return $errors;
//        } else {
//            global $db;
//            $product = new Product($db);
//            if ($product->fetch((int) $id_product) <= 0) {
//                $errors[] = 'ID du produit invalide';
//            } else {
//                $pu_ht = $product->price;
//                $txtva = (float) $product->tva_tx;
//                $txlocaltax1 = 0;
//                $txlocaltax2 = 0;
//                $fk_product = (int) $id_product;
//                $info_bits = 0;
//                $fk_remise_except = 0;
//                $price_base_type = 'HT';
//                $pu_ttc = $product->price_ttc;
//                $pa_ht = 0;
//                if ($id_fournisseur_price) {
//                    $fournPrice = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ProductFournisseurPrice', (int) $id_fournisseur_price);
//                    if (BimpObject::objectLoaded($fournPrice)) {
//                        $pa_ht = (float) $fournPrice->getData('price');
//                    } else {
//                        $errors[] = 'Prix fournisseur d\'ID ' . $id_fournisseur_price . ' inexistant';
//                        return $errors;
//                    }
//                }
//
//                $current_status = $this->dol_object->statut;
//                $this->dol_object->statut = Commande::STATUS_DRAFT;
//
//                $id_line = $this->dol_object->addline($desc, $pu_ht, (int) $qty, $txtva, $txlocaltax1, $txlocaltax2, $fk_product, (float) $remise_percent, $info_bits, $fk_remise_except, $price_base_type, $pu_ttc, $date_start, $date_end, 0, -1, 0, 0, null, $pa_ht);
//
//                $this->dol_object->statut = $current_status;
//                $this->update();
//
//                if ($id_line <= 0) {
//                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Des erreurs sont survenues lors de l\'ajout de la ligne de commande');
//                } else {
//                    global $db;
//                    $line = new OrderLine($db);
//                    $line->fetch((int) $id_line);
//                    $line->id = $line->rowid;
//
//                    $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');
//                    $br_order_line = BimpObject::getInstance('bimpreservation', 'BR_OrderLine');
//                    $id_entrepot = (int) $this->dol_object->array_options['options_entrepot'];
//
//                    $res_errors = $reservation->createFromCommandeClientLine($id_entrepot, $this->dol_object, $line, $br_order_line);
//                    if (count($res_errors)) {
//                        $errors[] = BimpTools::getMsgFromArray($res_errors, 'Des erreurs sont survenues lors de la création des éléments de la logistique');
//                    }
//                }
//
//                $this->checkIsFullyShipped();
//                $this->checkIsFullyInvoiced();
//            }
//        }
//
//        return $errors;
//    }
//
//    public function removeOrderLine($id_line, $qty, $id_avoir = 0, $id_equipment = 0)
//    {
//        $errors = array();
//
//        if ($this->isLoaded()) {
//            $orderLine = BimpObject::getInstance('bimpreservation', 'BR_OrderLine');
//            if ($orderLine->find(array('id_order_line' => (int) $id_line))) {
//                $current_qty = (int) $orderLine->getData('qty');
//                $new_qty = $current_qty - $qty;
//                if ($new_qty < 0) {
//                    $errors[] = 'Quantité à retirer invalide (nouvelles quantités négatives)';
//                    return $errors;
//                }
//
//                if ((int) $this->getData('id_facture')) {
//                    // Ajout à l'avoir:
//                    $avoir_errors = $this->addLineToCreditNote($id_line, $qty, $id_avoir, null, $id_equipment);
//                    if (count($avoir_errors)) {
//                        $errors[] = BimpTools::getMsgFromArray($avoir_errors, 'Echec de l\'ajout à l\'avoir');
//                    }
//                }
//                global $user;
//                $current_status = $this->dol_object->statut;
//                $this->dol_object->statut = Commande::STATUS_DRAFT;
//                $this->dol_object->update($user);
//                $this->dol_object->fetch($this->id);
//
//                if ($new_qty > 0) {
//                    // Mise à jour des quantités de la ligne de commande: 
//
//                    global $db;
//                    $line = new OrderLine($db);
//                    if ($line->fetch((int) $id_line) <= 0) {
//                        $errors[] = 'Ligne de commande d\'ID ' . $id_line . ' non trouvée';
//                    } else {
//                        if ($this->dol_object->updateline((int) $id_line, $line->desc, (float) $line->subprice, $new_qty, $line->remise_percent, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 'HT') <= 0) {
//                            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la mise à jour des quantités pour la ligne de commande d\'ID ' . $id_line);
//                        } else {
//                            $up_errors = $orderLine->updateField('qty', $new_qty);
//                            if (count($up_errors)) {
//                                $errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec de la mise à jour des quantités pour la ligne de commande d\'ID ' . $id_line);
//                            }
//                        }
//                    }
//                } else {
//                    // Suppression de la ligne de commande (quantités = 0) 
//                    if ($this->dol_object->deleteline($user, $id_line) <= 0) {
//                        $errors = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la suppression de la ligne de commande');
//                    } else {
//                        $del_warnings = array();
//                        $del_errors = $orderLine->delete($del_warnings, true);
//                        if (count($del_errors)) {
//                            $errors[] = BimpTools::getMsgFromArray($del_errors, 'Echec de la suppression de la ligne de commande');
//                        }
//                    }
//                }
//
//                $this->dol_object->statut = $current_status;
//                $this->dol_object->update($user);
//                $this->dol_object->fetch($this->id);
//            } else {
//                $errors[] = 'ID de la ligne de commande absent ou invalide';
//            }
//        } else {
//            $errors[] = 'ID de ma commande absent';
//        }
//
//        return $errors;
//    }
//
//    public function checkIsFullyShipped()
//    {
//        global $user;
//        $errors = array();
//        if ($this->isFullyShipped()) {
//            if ((int) $this->dol_object->statut !== Commande::STATUS_CLOSED) {
//                if ($this->dol_object->cloture($user) <= 0) {
//                    $errors[] = 'Echec de la fermeture de la commande';
//                }
//            }
//        } else {
//            if ((int) $this->dol_object->statut === Commande::STATUS_CLOSED) {
//                if ($this->dol_object->set_reopen($user) <= 0) {
//                    $errors[] = 'Echec de la réouverture de la commande';
//                }
//            }
//        }
//        return $errors;
//    }
//
//    public function checkIntegrity()
//    {
//        return array();
//
//        $errors = array();
//        if ($this->isLoaded() && $this->dol_object->statut > 0) {
//            $nCommandeProducts = 0;
//            $nCommandeServices = 0;
//            $nBrOrderProducts = 0;
//            $nBrOrderServices = 0;
//            $nToShipProducts = 0;
//            $nToShipServices = 0;
//            $nShippedProducts = 0;
//            $nShippedServices = 0;
//            $nOrderLineProductsShipped = 0;
//            $nOrderLineServicesShipped = 0;
//
//            $product = BimpObject::getInstance('bimpcore', 'Bimp_Product');
//
//            foreach ($this->dol_object->lines as $line) {
//                if (isset($line->fk_product) && (int) $line->fk_product) {
//                    $type = (int) $product->getSavedData('fk_product_type', (int) $line->fk_product);
//                    if ((int) $line->qty > 0) {
//                        if ($type === Product::TYPE_PRODUCT) {
//                            $nCommandeProducts += (int) $line->qty;
//                        } else {
//                            $nCommandeServices += (int) $line->qty;
//                        }
//                    }
//                }
//            }
//
//            foreach ($this->getChildrenObjects('order_lines') as $brOrderLine) {
//                $qty = (int) $brOrderLine->getData('qty');
//                $qtyShipped = (int) $brOrderLine->getData('qty_shipped');
//                switch ((int) $brOrderLine->getData('type')) {
//                    case BR_OrderLine::PRODUIT:
//                        $nBrOrderProducts += $qty;
//                        $nOrderLineProductsShipped += $qtyShipped;
//                        break;
//
//                    case BR_OrderLine::SERVICE:
//                        $nBrOrderServices += $qty;
//                        $nOrderLineServicesShipped += $qtyShipped;
//                        break;
//                }
//            }
//
//            foreach ($this->getChildrenObjects('shipments') as $shipment) {
//                if ((int) $shipment->getData('status') === 2) {
//                    $nShippedProducts += (int) $shipment->getNbArticles();
//                    $nShippedServices += (int) $shipment->getNbServices();
//                } else {
//                    $nToShipProducts += (int) $shipment->getNbArticles();
//                    $nToShipServices += (int) $shipment->getNbServices();
//                }
//            }
//
//            if ((int) $nCommandeProducts !== (int) $nBrOrderProducts) {
//                $errors[] = 'Le nombre de produits enregistrés pour la commande (' . $nCommandeProducts . ') ne correspond pas au nombre de produits enregistrés pour la logistique (' . $nBrOrderProducts . ')';
//            }
//
//            if ((int) $nCommandeServices !== (int) $nBrOrderServices) {
//                $errors[] = 'Le nombre de services enregistrés pour la commande ne correspond pas au nombre de services enregistrés pour la logistique';
//            }
//            if (((int) $nToShipProducts + (int) $nShippedProducts) !== $nOrderLineProductsShipped) {
//                $errors[] = 'Le nombre de produits expédiés ne correspond pas à la quantité enregistrée';
//            }
//            if (((int) $nToShipServices + (int) $nShippedServices) !== $nOrderLineServicesShipped) {
//                $errors[] = 'Le nombre de services expédiés ne correspond pas à la quantité enregistrée';
//            }
//
//            BimpObject::loadClass('bimpreservation', 'BR_Reservation');
//
//            $sql = 'SELECT SUM(`qty`) as qty FROM ' . MAIN_DB_PREFIX . 'br_reservation WHERE `id_commande_client` = ' . (int) $this->id;
//            $result = $this->db->executeS($sql . ' AND `status` = 250');
//            if ((int) $result[0]->qty !== (int) $nToShipProducts) {
//                $errors[] = 'Le nombre de réservations au statut "' . BR_Reservation::$status_list[250]['label'] . '" est incorrect';
//            }
//            $result = $this->db->executeS($sql . ' AND `status` = 300');
//            if ((int) $result[0]->qty !== (int) $nShippedProducts) {
//                $errors[] = 'Le nombre de réservations au statut "' . BR_Reservation::$status_list[300]['label'] . '" est incorrect';
//            }
//            $result = $this->db->executeS($sql . ' AND `status` < 250');
//            if ((int) $result[0]->qty !== (int) ($nCommandeProducts - $nToShipProducts - $nShippedProducts)) {
//                $errors[] = 'Le nombre de réservations non expédiées ou en attente d\'expédition est incorrect: ' . $result[0]->qty . ' => ' . $nCommandeProducts . ', ' . $nToShipProducts . ', ' . $nShippedProducts;
//            }
//        }
//
//        return $errors;
//    }
//    
//    // Traitements factures old: 
//
//    public function createFactureOld($shipments_ids = null, $cond_reglement = null, $id_account = null, $remises = array(), $public_note = '', $private_note = '')
//    {
//        $errors = array();
//
//        if (!$this->isLoaded()) {
//            $errors[] = 'ID de la commande client absent ou invalide';
//            return $errors;
//        }
//
//        if ((int) $this->getData('id_facture')) {
//            $errors[] = 'Tous les éléments de cette commande ont déjà été facturés';
//            return $errors;
//        }
//
//        $id_client = (int) $this->dol_object->socid;
//
//        if (!$id_client) {
//            $errors[] = 'Aucun client enregistré pour cette commande';
//        }
//
//        $shipments_objects = array();
//
//        if (!is_null($shipments_ids)) {
//            if (!is_array($shipments_ids)) {
//                $shipments_ids = array($shipments_ids);
//            }
//            foreach ($shipments_ids as $id_shipment) {
//                $shipment = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeShipment', (int) $id_shipment);
//                if (!BimpObject::objectLoaded($shipment)) {
//                    $errors[] = 'Expédition d\'ID ' . $id_shipment . ' non trouvée';
//                } elseif ((int) $shipment->getData('id_facture')) {
//                    $errors[] = 'L\'expédition d\'ID ' . $id_shipment . ' a déjà été facturée';
//                } else {
//                    $shipments_objects[] = $shipment;
//                }
//            }
//        }
//
//        if (count($errors)) {
//            return $errors;
//        }
//
//        global $user, $langs;
//
//        $commande = $this->dol_object;
//
//        $langs->load('errors');
//        $langs->load('bills');
//        $langs->load('companies');
//        $langs->load('compta');
//        $langs->load('products');
//        $langs->load('banks');
//        $langs->load('main');
//
//        $facture = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture');
//
//        $rs = BimpObject::getInstance('bimpreservation', 'BR_ReservationShipment');
//        $ss = BimpObject::getInstance('bimpreservation', 'BR_ServiceShipment');
//
//        if (!is_null($shipments_objects) && count($shipments_objects)) {
//            $shipments_list = implode(',', $shipments_ids);
//
//            foreach ($commande->lines as $i => $line) {
//                if (!isset($line->fk_product) || !$line->fk_product) {
//                    unset($commande->lines[$i]);
//                    continue;
//                }
//
//                $qty = 0;
//
//                $list = $rs->getList(array(
//                    'id_shipment'             => array('in' => $shipments_list),
//                    'id_commande_client'      => (int) $this->id,
//                    'id_commande_client_line' => $line->id
//                        ), null, null, 'id', 'asc', 'array', array('qty'));
//                if (count($list)) {
//                    foreach ($list as $item) {
//                        $qty += (int) $item['qty'];
//                    }
//                } else {
//                    foreach ($ss->getList(array(
//                        'id_shipment'             => array('in' => $shipments_list),
//                        'id_commande_client'      => (int) $this->id,
//                        'id_commande_client_line' => $line->id
//                            ), null, null, 'id', 'asc', 'array', array('qty')) as $item) {
//                        $qty += (int) $item['qty'];
//                    }
//                }
//
//                if ($qty === 0) {
//                    unset($commande->lines[$i]);
//                } else {
//                    $commande->lines[$i]->qty = $qty;
//                }
//            }
//
//            if (!count($commande->lines)) {
//                $errors[] = 'Aucun produit ou service à facturer trouvé';
//            }
//            if (count($errors)) {
//                return $errors;
//            }
//        } else {
//            $lines_billed_qties = array();
//            $shipments = $this->getChildrenObjects('shipments', array(
//                'id_facture' => array(
//                    'operator' => '>',
//                    'value'    => 0
//                )
//            ));
//            $filters = array(
//                'id_commande_client' => (int) $this->id
//            );
//            foreach ($shipments as $shipment) {
//                $filters['id_shipment'] = (int) $shipment->id;
//                foreach ($rs->getList($filters, null, null, 'id', 'asc', 'array', array('id_commande_client_line', 'qty')) as $item) {
//                    if (!isset($lines_billed_qties[(int) $item['id_commande_client_line']])) {
//                        $lines_billed_qties[(int) $item['id_commande_client_line']] = 0;
//                    }
//                    $lines_billed_qties[(int) $item['id_commande_client_line']] += (int) $item['qty'];
//                }
//                foreach ($ss->getList($filters, null, null, 'id', 'asc', 'array', array('id_commande_client_line', 'qty')) as $item) {
//                    if (!isset($lines_billed_qties[(int) $item['id_commande_client_line']])) {
//                        $lines_billed_qties[(int) $item['id_commande_client_line']] = 0;
//                    }
//                    $lines_billed_qties[(int) $item['id_commande_client_line']] += (int) $item['qty'];
//                }
//            }
//
//            foreach ($commande->lines as $i => $line) {
//                if (isset($lines_billed_qties[(int) $line->id])) {
//                    $new_qties = (int) $line->qty - $lines_billed_qties[(int) $line->id];
//
//                    if ($new_qties === 0) {
//                        unset($commande->lines[$i]);
//                    } else {
//                        $commande->lines[$i]->qty = $new_qties;
//                    }
//                }
//            }
//        }
//
//        if (!is_null($cond_reglement) && $cond_reglement) {
//            $commande->cond_reglement_id = (int) $cond_reglement;
//        }
//
//        $commande->array_options['options_type'] = 'C';
//        if ($facture->createFromCommande($commande, (int) $id_account, $public_note, $private_note) <= 0) {
//            $msg = 'Echec de la création de la facture';
//            if ($facture->dol_object->error) {
//                $msg .= ' - "' . $langs->trans($facture->dol_object->error) . '"';
//            }
//            $errors[] = $msg;
//            return $errors;
//        }
//
//        unset($commande);
//        $commande = null;
//
//        if (count($remises)) {
//            foreach ($remises as $id_remise) {
//                $facture->dol_object->error = '';
//                $facture->dol_object->errors = array();
//
//                if ($facture->dol_object->insert_discount((int) $id_remise) <= 0) {
//                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($facture->dol_object), 'Echec de l\'insertion de la remise client d\'ID ' . $id_remise);
//                }
//            }
//        }
//
//        // Validation de la facture: 
////        if ($facture->dol_object->validate($user) <= 0) {
////            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($facture->dol_object), 'Echec de la validation de la facture');
////        }
////
////        $facture->dol_object->generateDocument('bimpfact', $langs);
//
//        $this->fetch($this->id);
//
//        if (count($shipments_objects)) {
//            foreach ($shipments_objects as $shipment) {
//                $shipment->set('id_facture', (int) $facture->id);
//                if ((int) $shipment->getData('status') !== 2) {
//                    $shipment->set('status', 4);
//                }
//                $up_errors = $shipment->update();
//                if (count($up_errors)) {
//                    $label = 'Expédition n° ' . $shipment->getData('num_livraison');
//                    $errors[] = BimpTools::getMsgFromArray($up_errors, $label . ': facture créée avec succès mais échec de l\'enregistrement de l\'ID facture (' . $facture->id . ')');
//                }
//            }
//        } else {
//            $up_errors = $this->updateField('id_facture', (int) $facture->id);
//            if (count($up_errors)) {
//                $errors[] = BimpTools::getMsgFromArray($up_errors, 'Des erreurs sont survenues durant l\'enregistrement de l\'ID de la facture');
//            }
//        }
//
//        $this->checkIsFullyInvoiced();
//
//        return $errors;
//    }
//
//    public function checkIsFullyInvoiced()
//    {
//        global $user;
//        $errors = array();
//        if ($this->isFullyInvoiced()) {
//            if (!(int) $this->dol_object->billed) {
//                if ($this->dol_object->classifyBilled($user) <= 0) {
//                    $errors[] = 'Echec de la mise à jour du statut de la commande à "Facturée"';
//                }
//            }
//        } else {
//            if ((int) $this->dol_object->billed) {
//                if ($this->dol_object->classifyUnBilled() <= 0) {
//                    $errors[] = 'Echec de la mise à jour du statut de la commande à "Non Facturée"';
//                }
//            }
//        }
//        return $errors;
//    }
//
//    public function addLineToCreditNote($id_line, $qty, $id_avoir = null, $id_facture_source = null, $id_equipment = null)
//    {
//        $errors = array();
//
//        $avoir = null;
//
//        global $db, $user, $langs;
//
//        $langs->load('errors');
//        $langs->load('bills');
//        $langs->load('companies');
//        $langs->load('compta');
//        $langs->load('products');
//        $langs->load('banks');
//        $langs->load('main');
//
//        if (is_null($id_avoir) || !(int) $id_avoir) {
//            // Création d'un nouvel avoir: 
//            BimpTools::loadDolClass('compta/facture', 'facture');
//            $avoir = new Facture($db);
//            $avoir->date = dol_now();
//            $avoir->socid = $this->dol_object->socid;
//            $avoir->type = Facture::TYPE_CREDIT_NOTE;
//            $avoir->origin = $this->dol_object->element;
//            $avoir->origin_id = $this->dol_object->id;
//            $avoir->array_options['options_type'] = 'R';
//            $avoir->array_options['options_entrepot'] = $this->dol_object->array_options['options_entrepot'];
//
//            if (!is_null($id_facture_source)) {
//                $avoir->fk_facture_source = $id_facture_source;
//            }
//
//            $avoir->linked_objects[$avoir->origin] = $avoir->origin_id;
//
//            if ($avoir->create($user) <= 0) {
//                $avoir_errors = BimpTools::getErrorsFromDolObject($avoir, null, $langs);
//                $errors[] = BimpTools::getMsgFromArray($avoir_errors, 'Des erreurs sont survenues lors de la création de l\'avoir');
//            } else {
//                $asso = new BimpAssociation($this, 'avoirs');
//                $asso->addObjectAssociation($avoir->id);
//            }
//        } else {
//            $bimp_avoir = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_avoir);
//            if (!$bimp_avoir->isLoaded()) {
//                $errors[] = 'Avoir d\'ID ' . $id_avoir . ' inexistant';
//            } else {
//                $avoir = $bimp_avoir->dol_object;
//            }
//        }
//
//        if (!count($errors) && BimpObject::objectLoaded($avoir)) {
//            $order_line = new OrderLine($db);
//            if ($order_line->fetch((int) $id_line) <= 0) {
//                $errors[] = 'Ligne de commande d\'ID ' . $id_line . ' inexistante';
//            } else {
//                $serial = '';
//                if (!is_null($id_equipment) && (int) $id_equipment) {
//                    $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
//                    if ($equipment->isLoaded()) {
//                        $serial = $equipment->getData('serial');
//                    }
//                }
//
//                $fk_product = $order_line->fk_product;
//                $desc = $order_line->desc . ($serial ? ' - N° de série: ' . $serial : '');
//                $qty = (int) $qty;
//                $pu_ht = $order_line->subprice;
//                $txtva = $order_line->tva_tx;
//                $remise_percent = $order_line->remise_percent;
//
//                $txlocaltax1 = $order_line->localtax1_tx;
//                $txlocaltax2 = $order_line->localtax2_tx;
//                $price_base_type = 'HT';
//                $date_start = '';
//                $date_end = '';
//                $ventil = 0;
//                $info_bits = 0;
//                $fk_remise_except = $order_line->fk_remise_except;
//
//                if ($avoir->addline($desc, $pu_ht, $qty, $txtva, $txlocaltax1, $txlocaltax2, $fk_product, $remise_percent, $date_start, $date_end, $ventil, $info_bits, $fk_remise_except, $price_base_type) <= 0) {
//                    $msg = 'Des erreurs sont survenues lors de l\'ajout à l\'avoir';
//                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($avoir, null, $langs), $msg);
//                }
//            }
//        }
//
//        return $errors;
//    }
//    
//    // Actions obsolètes: 
//
//    public function actionRemoveOrderLines($data, &$success)
//    {
//        $success = 'Produits retirés de la commande avec succès';
//        $errors = array();
//        $warnings = array();
//
//        $errors[] = 'Action obsolète';
//
//        return array(
//            'errors'   => $errors,
//            'warnings' => $warnings
//        );
//    }
//
//    public function actionCreateFacture($data, &$success)
//    {
//        $errors = array();
//        $warnings = array();
//
//        $errors[] = 'Action obsolète';
////        $success = 'Création de la facture effectuée avec succès';
////        $success_callback = '';
////
////        $id_shipment = (isset($data['id_shipment']) ? (int) $data['id_shipment'] : null);
////        $cond_reglement = (isset($data['cond_reglement']) ? (int) $data['cond_reglement'] : 0);
////        $id_account = (isset($data['id_account']) ? (int) $data['id_account'] : 0);
////        $remises = (isset($data['id_remises_list']) ? $data['id_remises_list'] : array());
////        $public_note = (isset($data['note_public']) ? $data['note_public'] : '');
////        $private_note = (isset($data['note_private']) ? $data['note_private'] : '');
////
////        if ((is_null($id_account) || !$id_account)) {
////            $errors[] = 'Compte financier absent';
////        }
////
////        if ((is_null($id_shipment) || !$id_shipment) && !(int) $this->getData('id_facture')) {
////            $success_callback = 'bimp_reloadPage();';
////        }
////
////        $errors = $this->createFactureOld($id_shipment, $cond_reglement, $id_account, $remises, $public_note, $private_note);
//
//        return array(
//            'errors'           => $errors,
//            'warnings'         => $warnings,
//            'success_callback' => $success_callback
//        );
//    }
//
//    public function actionAddLine($data, &$success)
//    {
//        $errors = array();
//        $warnings = array();
//        $success = '';
//        $success_callback = 'bimp_reloadPage();';
//        $success = 'Produit / service ajouté à la commande avec succès - ' . $data['qty'];
//
//        if (!isset($data['id_product']) || !$data['id_product']) {
//            $errors[] = 'Produit absent';
//        } else {
//            $id_product = (int) $data['id_product'];
//        }
//
//        if (!isset($data['id_fournisseur_price']) || !$data['id_fournisseur_price']) {
//            $errors[] = 'Prix fournisseur absent';
//        } else {
//            $id_fournisseur_price = (int) $data['id_fournisseur_price'];
//        }
//
//        if (isset($data['qty'])) {
//            $qty = (int) $data['qty'];
//        } else {
//            $qty = 1;
//        }
//
//        if (isset($data['desc'])) {
//            $desc = $data['desc'];
//        } else {
//            $desc = '';
//        }
//
//        if (isset($data['reduc'])) {
//            $remise_percent = $data['reduc'];
//        } else {
//            $remise_percent = 0;
//        }
//
//        if (!isset($data['limited']) || !(int) $data['limited']) {
//            $data['date_start'] = '';
//            $data['date_end'] = '';
//        }
//
//        $errors = $this->addOrderLine($id_product, $qty, $desc, $id_fournisseur_price, $remise_percent, $data['date_start'], $data['date_end']);
//
//        return array(
//            'errors'           => $errors,
//            'warnings'         => $warnings,
//            'success_callback' => $success_callback
//        );
//    }
//
//    public function actionValidateFacture($data, &$success)
//    {
//        $errors = array();
//        $warnings = array();
//        $success = 'Facture validée avec succès';
//        $success_callback = 'bimp_reloadPage();';
//
//        if (!$this->isLoaded()) {
//            $errors[] = 'ID de la commande absent';
//        } elseif (!(int) $this->getData('id_facture')) {
//            $errors[] = 'Aucune facture enregistrée pour cette commande';
//        } else {
//            $facture = $this->getChildObject('facture');
//            if (!BimpObject::objectLoaded($facture)) {
//                $errors[] = 'Facture d\'ID ' . $this->getData('id_facture') . ' non trouvée';
//            } elseif ((int) $facture->getData('fk_statut') > 0) {
//                $errors[] = 'Cette facture a déjà été validée';
//            } else {
//                global $user, $langs;
//
//                if ($facture->dol_object->validate($user) <= 0) {
//                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($facture->dol_object), 'Echec de la validation de la facture');
//                }
//
//                $facture->dol_object->generateDocument('bimpfact', $langs);
//            }
//        }
//        return array(
//            'errors'           => $errors,
//            'warnings'         => $warnings,
//            'success_callback' => $success_callback
//        );
//    }
//}