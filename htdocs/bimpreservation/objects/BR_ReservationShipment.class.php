<?php

class BR_reservationShipment extends BimpObject
{

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
                        ))) {
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

    public function create()
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

        if (!count($errors)) {
            $reservation = BimpObject::getInstance($this->module, 'BR_Reservation');
            if ($reservation->find(array(
                        'ref'    => $ref_reservation,
                        'status' => 200
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

                $errors = parent::create();

                if ($this->isLoaded()) {
                    $res_errors = $reservation->setNewStatus(250, (int) $this->getData('qty'));
                    if (!count($res_errors)) {
                        $res_errors = $reservation->update();
                    }
                    if (count($res_errors)) {
                        $errors[] = 'Echec du changement de statut de la réservation correspondante';
                        $errors = array_merge($errors, $res_errors);
                        $this->delete();
                    }
                }
            } else {
                $errors[] = 'Aucune réservation au statut "' . BR_Reservation::$status_list[200]['label'] . '" trouvée pour la référence "' . $ref_reservation . '"';
            }
        }

        return $errors;
    }
}
