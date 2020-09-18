<?php

class BR_ServiceShipment extends BimpObject
{

    public $commande = null;

    // Getters: 

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

    public function getCommande()
    {
        if (is_null($this->commande)) {
            $this->commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $this->getData('id_commande_client'));
        }

        return $this->commande;
    }

    public function getProductId()
    {
        $order_line = $this->getChildObject('service_order_line');
        if (BimpObject::objectLoaded($order_line)) {
            return (int) $order_line->getData('id_product');
        }

        return 0;
    }

    public function getAvailableQty()
    {
        $orderLine = $this->getChildObject('service_order_line');
        return (int) $orderLine->getShipmentAvailableQty() + (int) $this->getSavedData('qty');
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
                        $avoirs[(int) $id_avoir] = $avoir->dol_object->ref . ' - créé le ' . $DT->format('d / m / Y à H:i');
                    }
                }
            }
        }

        krsort($avoirs);

        return $avoirs;
    }

    // Affichages: 

    public function displayService($display_name = 'default')
    {
        $order_line = $this->getChildObject('service_order_line');
        if (BimpObject::objectLoaded($order_line)) {
            return $order_line->displayData('id_product', $display_name);
        }

        return '';
    }

    // Traitements: 

    public function removeFromShipment($qty, $removeFromOrder = false, $id_avoir = 0)
    {
        $errors = array();

        $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $this->getData('id_commande_client'));
        $id_order_line = (int) $this->getData('id_commande_client_line');
        $shipment = $this->getParentInstance();

        if (!BimpObject::objectLoaded($commande)) {
            $errors[] = 'ID de la commande client absent';
        }

        if (!$id_order_line) {
            $errors[] = 'ID de la ligne de commande client absent';
        }


        if (!BimpObject::objectLoaded($shipment)) {
            $errors[] = 'ID de l\'expédition absent';
        }

        if (count($errors)) {
            return $errors;
        }

        $qty = (int) $qty;

        if ($qty > (int) $this->getData('qty')) {
            $qty = (int) $this->getData('qty');
        }

        // Mise à jour des qtés livrées: 
        $errors = $this->updateOrderLineShippedQty(-$qty);

        // Ajout à un avoir: 
        $rebuild_facture = false;

        if (!$this->isOrderInvoiced() && $this->isShipmentInvoiced()) {
            $facture_status = (int) $this->db->getValue('facture', 'fk_statut', '`rowid` = ' . (int) $shipment->getData('id_facture'));
            if ($facture_status > 0) {
                $avoir_errors = array();
                $orderLine = BimpObject::getInstance($this->module, 'BR_OrderLine');
                $orderLine->find(array('id_order_line' => $id_order_line));
                if (BimpObject::objectLoaded($orderLine)) {
                    $avoir_errors = $orderLine->addToCreditNote($qty, $id_avoir);
                } else {
                    $avoir_errors[] = 'ID de la ligne de commande absent ou invalide';
                }

                if (count($avoir_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($avoir_errors, 'Echec de l\'ajout à l\'avoir');
                }

                unset($orderLine);
                $orderLine = null;
            } else {
                $rebuild_facture = true;
            }
        }

        if (!count($errors)) {
            // Retrait de l'expédition: 
            $new_qty = (int) $this->getData('qty') - $qty;
            if ($removeFromOrder && ($new_qty === 0)) {
                $del_warnings = array();
                $del_errors = $this->delete($del_warnings, true);
                if (count($del_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($del_errors, 'Echec de la suppression de la ligne d\'expédition');
                }
            } else {
                $up_errors = $this->updateField('qty', $new_qty);
                if (count($up_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec de la mise à jour des nouvelles quantités pour la ligne d\'expédition');
                }
            }

            // Retrait de la commande - L'ajout à l'avoir sera fait par Bimp_Commande::removeOrderLine() si le service a été facturée hors expéditions. 
            $orderLine = null;
            if (!count($errors) && $removeFromOrder) {
                $orderLine = BimpObject::getInstance($this->module, 'BR_OrderLine');
                if ($orderLine->find(array('id_order_line' => $id_order_line))) {
                    $remove_errors = $orderLine->removeServiceFromOrder($qty, $id_avoir);
                    if (count($remove_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($remove_errors, 'Echec du retrait de la commande');
                    }
                } else {
                    $errors[] = 'ID de la ligne de commande absent ou invalide';
                }
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

        $orderLine = $this->getChildObject('service_order_line');
        if (BimpObject::objectLoaded($orderLine)) {
            $shipped_qty = (int) $orderLine->getData('qty_shipped');
            $new_qty = $shipped_qty + (int) $modif_qty;
            $orderLine->set('qty_shipped', $new_qty);
            $errors = $orderLine->update();
        } else {
            $errors[] = 'ID de la ligne de commande absent ou invalide';
        }

        return $errors;
    }

    // Actions: 

    public function actionRemoveFromShipment($data, &$success)
    {
        $errors = array();
        $warnings = array();

        if (!isset($data['remove_from_order'])) {
            $data['remove_from_order'] = 0;
        }

        if (!isset($data['id_avoir'])) {
            $data['id_avoir'] = 0;
        }

        if (isset($data['qty']) && (int) $data['qty'] > 0) {
            $errors = $this->removeFromShipment((int) $data['qty'], (int) $data['remove_from_order'], (int) $data['id_avoir']);
        } else {
            $errors[] = 'Aucune quantité spécifiée ou quantité à retirer nulle';
        }

        $success = 'Service retiré de l\'expédition';
        if ((int) $data['remove_from_order']) {
            $success .= ' et de la commande';
        }
        $success .= ' avec succès';

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
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

    // Overrides: 

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            $qty = (int) $this->getData('qty');
            if ($qty !== 0) {
                $orderLineErrors = $this->updateOrderLineShippedQty($qty);
                if (count($orderLineErrors)) {
                    $warnings[] = BimpTools::getMsgFromArray($orderLineErrors, 'Echec de la mise à jour des quantités livrées pour la ligne de commande ' . $this->getData('id_br_order_line'));
                }
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
            $diff = (int) ($new_qty - $current_qty);
            if ($diff !== 0) {
                $orderLineErrors = $this->updateOrderLineShippedQty($diff);
                if (count($orderLineErrors)) {
                    $warnings[] = BimpTools::getMsgFromArray($orderLineErrors, 'Echec de la mise à jour des quantités livrées pour la ligne de commande ' . $this->getData('id_br_order_line'));
                }
            }
        }

        return $errors;
    }

    public function delete(&$warnings = array(), $force_delete = false)
    {
        $errors = $this->removeFromShipment((int) $this->getData('qty'));

        if (!count($errors)) {
            $errors = parent::delete($warnings, $force_delete);
        }

        return $errors;
    }
}
