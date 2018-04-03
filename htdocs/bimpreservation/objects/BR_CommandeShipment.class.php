<?php

class BR_CommandeShipment extends BimpObject
{

    public static $status_list = array(
        1 => array('label' => 'Brouillon', 'icon' => 'file-text', 'classes' => array('warning')),
        2 => array('label' => 'Expédiée', 'icon' => 'check', 'classes' => array('success')),
        3 => array('label' => 'Annulée', 'icon' => 'times', 'classes' => array('danger'))
    );

    public function getNbArticles()
    {
        $qty = 0;

        foreach ($this->getChildrenObjects('reservation_shipments') as $rs) {
            $qty += (int) $rs->getData('qty');
        }

        return $qty;
    }

    public function displayBLButton()
    {
        if ($this->isLoaded() && (int) $this->getData('status') === 2) {
            $url = DOL_URL_ROOT . '/bimpreservation/bl.php?id_commande=' . $this->getData('id_commande_client') . '&num_bl=' . $this->getData('num_livraison');
            $onclick = 'window.open(\'' . $url . '\')';
            $html = '<button type="button" class="btn btn-default" onclick="' . htmlentities($onclick) . '">';
            $html .= '<i class="' . BimpRender::renderIconClass('fas_file-pdf') . ' iconLeft"></i>';
            $html .= 'Bon de livraison';
            $html .= '</button>';
        }

        return $html;
    }

    public function getContactsArray()
    {
        return array(
            0 => 'Addresse de livraison de la commande'
        );
    }

    public function getExtraBtn()
    {
        $buttons = array();

        if ($this->isLoaded()) {
            $onclick = 'loadModalView(\'' . $this->module . '\', \'' . $this->object_name . '\', ' . $this->id . ', \'lines\', $(this))';
            $buttons[] = array(
                'label'   => 'Produits inclus',
                'icon'    => 'bars',
                'onclick' => $onclick
            );

            if ((int) $this->getData('status') === 1) {
                $title = 'Finalisation d&apos;une expédition';
                $onclick = 'loadModalForm($(this), {module: \'bimpreservation\', object_name: \'BR_CommandeShipment\', id_object: ' . $this->id . ', ';
                $onclick .= 'form_name: \'validation\'}, \'' . addslashes($title) . '\');';
                $buttons[] = array(
                    'label'   => 'Expédier',
                    'icon'    => 'sign-out',
                    'onclick' => htmlentities($onclick)
                );
            }
        }

        return $buttons;
    }

    public function renderServicesQtiesInputs()
    {
        $id_commande = (int) $this->getData('id_commande_client');
        if (!$id_commande) {
            return '';
        }

        $commande = $this->getChildObject('commande_client');
        if (!is_null($commande) && isset($commande->id) && $commande->id) {
            $lines = $commande->lines;
            $html = '';
            $title_row = '';
            $service = BimpObject::getInstance($this->module, 'BR_Service');

            $html = '<table class="objectlistTable">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th style="width: 40%">Service</th>';
            $html .= '<th>PU HT</th>';
            $html .= '<th>Déjà livrés</th>';
            $html .= '<th>Qté restante</th>';
            $html .= '<th>Qté à inclure</th>';
            $html .= '</tr>';
            $html .= '</thead>';
            $html .= '<tbody>';
            foreach ($lines as $i => $line) {
                if (is_null($line->fk_product) || !$line->fk_product) {
                    if (!is_null($line->desc) && $line->desc) {
                        $title_row = '<tr style="font-weight: bold; background-color: #DCDCDC;"><td colspan="5">' . str_replace("\n", '<br/>', $line->desc) . '</td></tr>';
                    } else {
                        continue;
                    }
                } else {
                    if ($line->total_ht == 0) {
                        continue;
                    }
                    $product = new Product($this->db->db);
                    if ($product->fetch((int) $line->fk_product) <= 0) {
                        unset($product);
                        $product = null;
                    } elseif ($product->type === 0) {
                        continue;
                    }
                    $desc = '';
                    if (is_null($line->desc) || !$line->desc) {
                        if (!is_null($product)) {
                            $desc = $product->ref;
                            $desc.= ($desc ? ' - ' : '') . $product->label;
                        }
                    }
                    if (!$desc) {
                        $desc = $line->desc;
                    }
                    $desc = str_replace("\n", '<br/>', $desc);
                    if ($desc) {
                        if ($service->find(array(
                                    'id_commande_client'      => (int) $commande->id,
                                    'id_commande_client_line' => (int) $line->id
                                ))) {
                            $qty = (int) $service->getData('qty');
                            $qty_shipped = (int) $service->getData('shipped');
                            $qty_available = $qty - $qty_shipped;
                            if ($qty_available < 0) {
                                $qty_available = 0;
                            }

                            if ($title_row) {
                                $html .= $title_row;
                                $title_row = '';
                            }

                            $html .= '<tr>';
                            $html .= '<td>' . $desc . '</td>';
                            $html .= '<td>' . BimpTools::displayMoneyValue($line->subprice, 'EUR') . '</td>';
                            $html .= '<td>' . $qty_shipped . '</td>';
                            $html .= '<td>' . $qty_available . '</td>';
                            if ($qty_available > 0) {
                                $html .= '<td>';
                                $field_name = 'service_' . $line->id;
                                $options = array(
                                    'data'  => array(
                                        'data_type' => 'number',
                                        'decimals'  => 0,
                                        'min'       => 0,
                                        'max'       => $qty_available,
                                        'unsigned'  => 1
                                    ),
                                    'style' => 'width: auto;'
                                );

                                $html .= '<div class="inputContainer ' . $field_name . '_inputContainer"';
                                $html .= ' data-field_name="' . $field_name . '"';
                                $html .= ' data-initial_value="' . $qty_available . '"';
                                $html .= ' data-multiple="0"';
                                $html .= '>';
                                $html .= BimpInput::renderInput('text', $field_name, $qty_available, $options);
                                $html .= '</div>';
                                $html .= '</td>';
                            } else {
                                $html .= '<td></td>';
                            }
                            $html .= '</tr>';
                        }
                    }
                }
            }
            $html .= '</tbody></table>';
            return $html;
        }

        return BimpRender::renderAlerts('Commande invalide');
    }

    public function cancelShipment()
    {
        if ($this->isLoaded() && $this->getData('status') === 2) {
            $service = BimpObject::getInstance($this->module, 'BR_Service');
            $serviceShipment = BimpObject::getInstance($this->module, 'BR_ServiceShipment');

            $list = $serviceShipment->getList(array(
                'id_commande' => (int) $this->getData('id_commande_client'),
                'id_shipment' => (int) $this->id
                    ), null, null, 'id', 'asc', 'array', array('id', 'id_service'));

            if (!is_null($list) && count($list)) {
                foreach ($list as $item) {
                    if ($serviceShipment->fetch((int) $item['id'])) {
                        $qty = (int) $serviceShipment->getData('qty');
                        $serviceShipment->delete();
                        if ($qty > 0 && $serviceShipment->fetch((int) $item['id_service'])) {
                            $shipped = (int) $serviceShipment->getData('shipped');
                            $service->set('shipped', $shipped - $qty);
                            $service->update();
                        }
                    }
                }
            }

            $reservation = BimpObject::getInstance($this->module, 'BR_Reservation');
            $reservationShipment = BimpObject::getInstance($this->module, 'BR_ReservationShipment');

            $list = $reservationShipment->getList(array(
                'id_commande_client' => (int) $this->getData('id_commande_client'),
                'id_shipment'        => (int) $this->id
                    ), null, null, 'id', 'asc', 'array', array('id', 'ref_reservation', 'qty'));

            if (!is_null($list) && count($list)) {
                foreach ($list as $item) {
                    if ($reservation->find(array(
                                'id_commande_client' => (int) $this->getData('id_commande_client'),
                                'ref'                => $item['ref_reservation'],
                                'status'             => 300
                            ))) {
                        $reservation->setNewStatus(250, (int) $item['qty']);
                        $reservation->update();
                    }
                }
            }

            $this->set('status', 1);
            $this->update();
        }
    }

    public function create()
    {
        $errors = array();

        $id_commande = (int) $this->getData('id_commande_client');
        if (!$id_commande) {
            $errors[] = 'ID de la commande absent';
        } else {
            $commande = $this->getChildObject('commande_client');

//            $id_entrepot = (int) (isset($commande->array_options['options_entrepot'])?$commande->array_options['options_entrepot']:0);
            $id_entrepot = 1;
            if (!$id_entrepot) {
                $errors[] = 'ID de l\'entrepot absent';
            }

            $sql = 'SELECT MAX(num_livraison) as num FROM ' . MAIN_DB_PREFIX . 'br_commande_shipment ';
            $sql .= 'WHERE `id_commande_client` = ' . (int) $id_commande;

            $result = $this->db->execute($sql);
            $result = $this->db->db->fetch_object($result);

            if (is_null($result) || !isset($result->num)) {
                $num = 0;
            } else {
                $num = (int) $result->num;
            }

            $num++;

            $this->set('id_commande_client', $id_commande);
            $this->set('id_entrepot', $id_entrepot);
            $this->set('status', 1);
            $this->set('num_livraison', $num);
        }

        if (count($errors)) {
            return $errors;
        }

        $errors = parent::create();

        return $errors;
    }

    public function update()
    {
        $errors = array();

        if (BimpTools::getValue('validation', 0)) {
            if ((int) $this->getData('status') !== 1) {
                return array('Cette expédition doit avoir le statut "' . self::$status_list[1]['label'] . '" pour pouvoire être expédiée');
            }

            $id_entrepot = (int) $this->getData('id_entrepot');
            $commande = $this->getChildObject('commande_client');

            if (!$id_entrepot) {
                $errors[] = 'Entrepot absent';
            }

            if (is_null($commande) || !isset($commande->id) || !$commande->id) {
                $errors[] = 'Commande client absente ou invalide';
            }

            if (count($errors)) {
                return $errors;
            }

            $service = BimpObject::getInstance($this->module, 'BR_Service');
            $serviceShipment = BimpObject::getInstance($this->module, 'BR_ServiceShipment');
            $id_commande = (int) $this->getData('id_commande_client');

            // Récupération des quantités de services à inclure: 
            foreach ($_POST as $key => $value) {
                if (preg_match('/^service_(\d+)$/', $key, $matches)) {
                    if ((int) $value > 0) {
                        if ($service->find(array(
                                    'id_commande_client'      => (int) $id_commande,
                                    'id_commande_client_line' => (int) $matches[1]
                                ))) {
                            $shipped = (int) $service->getData('shipped');
                            $qty_available = (int) $service->getData('qty') - $shipped;
                            if ($value > $qty_available) {
                                $value = $qty_available;
                            }
                            $serviceShipment->reset();

                            $service_errors = $serviceShipment->validateArray(array(
                                'id_commande_client'      => (int) $id_commande,
                                'id_commande_client_line' => (int) $matches[1],
                                'id_service'              => (int) $service->id,
                                'id_shipment'             => (int) $this->id,
                                'qty'                     => (int) $value
                            ));

                            if (!count($service_errors)) {
                                $service_errors = $serviceShipment->create();
                                if (!count($service_errors)) {
                                    $service->set('shipped', $shipped + (int) $value);
                                    $service_errors = $service->update();

                                    if (count($service_errors)) {
                                        $serviceShipment->delete();
                                    }
                                }
                            }

                            if (count($service_errors)) {
                                $errors[] = 'Echec de l\'enregistrement des quantités à délivrer pour le service. (Ligne de commande d\'ID ' . $matches[1] . ')';
                                $errors = array_merge($errors, $service_errors);
                            }
                        } else {
                            $errors[] = 'Aucun service enregistré pour la ligne de commande d\'ID ' . $matches[1];
                        }
                    }
                }
            }

            if (count($errors)) {
                return $errors;
            }

            // Traitement des réservations: 
            $reservation = BimpObject::getInstance($this->module, 'BR_Reservation');
            $reservationShipment = BimpObject::getInstance($this->module, 'BR_ReservationShipment');
            $place = BimpObject::getInstance('bimpequipment', 'BE_Place');
            $id_client = $commande->socid;
            $id_contact = (int) $this->getData('id_contact');
            if (!$id_contact) {
                $contacts = $commande->getIdContact('external', 'SHIPPING');
                if (isset($contacts[0]) && $contacts[0]) {
                    $id_contact = $contacts[0];
                } else {
                    $contacts = $commande->getIdContact('external', 'CUSTOMER');
                    if (isset($contacts[0]) && $contacts[0]) {
                        $id_contact = $contacts[0];
                    }
                }
            }

            $list = $reservationShipment->getList(array(
                'id_commande_client' => (int) $this->getData('id_commande_client'),
                'id_shipment'        => (int) $this->id
                    ), null, null, 'id', 'asc', 'array', array('id', 'ref_reservation', 'qty'));

            if (!is_null($list) && count($list)) {
                global $user;
                $stock_label = 'Expédition n°' . $this->getData('num_livraison') . ' pour la commande client "' . $commande->ref . '"';
                $codemove = dol_print_date(dol_now(), '%y%m%d%H%M%S');
                foreach ($list as $item) {
                    // Mise à jour du statut de la réservation correspondante: 
                    if ($reservationShipment->fetch((int) $item['id'])) {
                        if ($reservation->find(array(
                                    'id_commande_client' => (int) $this->getData('id_commande_client'),
                                    'ref'                => $item['ref_reservation'],
                                    'status'             => 250
                                ))) {
                            $res_errors = $reservation->setNewStatus(300, (int) $item['qty']);
                            if (!count($res_errors)) {
                                $res_errors = $reservation->update();
                            }

                            if (count($res_errors)) {
                                $errors[] = 'Echec de la mise à jour du statut pour la réservation de référence "' . $item['ref_reservation'] . '"';
                                $errors = array_merge($errors, $res_errors);
                            }
                        } else {
                            $errors[] = 'Réservation de référence "'.$item['ref_reservation'].'" non trouvée pour la ligne d\'expédition d\'ID ' . $item['id'];
                        }

                        // Mise à jour des stocks et emplacement: 
                        $id_equipment = (int) $reservationShipment->getData('id_equipment');
                        if ($id_equipment) {
                            $place->reset();
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
                                $errors[] = 'Echec de la création du nouvel emplacement pour l\'équipement d\'ID ' . $id_equipment . ' (Réf. réservation: "' . $item['ref_reservation'] . '")';
                                $errors = array_merge($errors, $place_errors);
                            }
                        } else {
                            $product = $reservationShipment->getChildObject('product');
                            if (is_null($product) || !$product->isLoaded()) {
                                $errors[] = 'Aucun produit trouvé pour la ligne d\'expédition d\'ID ' . $reservationShipment->id . ' (Réf. réservation: "' . $item['ref_reservation'] . '"';
                            } else {
                                if ($product->isSerialisable()) {
                                    $errors[] = 'Numéro de série obligatoire pour le produit "' . $product->label . '" (ID ' . $product->id . ')';
                                } else {
                                    $qty = (int) $reservationShipment->getData('qty');
                                    if ($product->dol_object->correct_stock($user, $id_entrepot, $qty, 1, $stock_label, 0, $codemove, 'commande', $commande->id) <= 0) {
                                        $errors[] = 'Echec de la mise à jour des stocks pour le produit "' . $product->label . '" (ID ' . $product->id . ', quantités à retirer: ' . $qty . ')';
                                    }
                                }
                            }
                        }
                    } else {
                        $errors[] = 'Ligne d\'expédition non trouvée pour la réservation de référence "' . $item['ref_reservation'] . '"';
                    }
                }
            }

            $this->set('status', 2);
            $this->set('date_shipped', date('Y-m-d H:i:s'));
        }

        $errors = array_merge($errors, parent::update());

        return $errors;
    }
}
