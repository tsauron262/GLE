<?php

class reservationController extends BimpController
{

    public function renderEquipmentForm($id_commande)
    {
        $html = '';

        $rows = array(
            array(
                'label' => 'Numéro de série d\'un équipement à attribuer',
                'input' => '<input type="text" class="large_input" name="serial" id="findEquipmentSerial" value="" autocomplete="off"/>'
            )
        );

        $buttons = array();

        $button = '<button id="hideEquipmentFormButton" type="button" class="btn btn-danger buttonLeft"';
        $button .= ' onclick="hideEquipmentForm();">';
        $button .= '<i class="fa fa-times iconLeft"></i>Fermer</button>';
        $buttons[] = $button;

        $button = '<button id="findEquipmentButton" type="button" class="btn btn-primary"';
        $button .= ' onclick="findEquipmentToReceive($(this), ' . (int) $id_commande . ');">';
        $button .= '<i class="fa fa-check iconLeft"></i>Valider</button>';
        $buttons[] = $button;

        $html .= '<div id="equipmentForm" style="display: none;">';
        $html .= '<div style="display: inline-block">';
        $html .= BimpRender::renderFreeForm($rows, $buttons, 'Attribution d\'équipement');
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    protected function ajaxProcessSetReservationStatus()
    {
        $errors = array();
        $success = '';

        $id_reservation = BimpTools::getValue('id_reservation');
        $status = BimpTools::getValue('status');

        if (is_null($id_reservation) || !$id_reservation) {
            $errors[] = 'ID de la réservation absent ou invalide';
        }

        if (is_null($status)) {
            $errors[] = 'Nouveau statut de la réservation non spécifié';
        }

        if (!count($errors)) {
            $reservation = BimpCache::getBimpObjectInstance('bimpreservation', 'BR_Reservation', $id_reservation);
            if (!$reservation->isLoaded()) {
                $errors[] = 'Réservation d\'ID ' . $id_reservation . ' non trouvée';
            } else {
                $success = 'Statut de la réservation ' . $id_reservation . ' mis à jour avec succès';
                $errors = $reservation->setNewStatus($status);
                if (!count($errors)) {
                    $errors = $reservation->update();
                }
            }
        }

        die(json_encode(array(
            'errors'     => $errors,
            'success'    => $success,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }

    protected function ajaxProcessFindEquipmentToReceive()
    {
        $errors = array();
        $success = '';
        $id_reservation = 0;

        $id_commande_client = (int) BimpTools::getValue('id_commande_client');
        $serial = BimpTools::getValue('serial', '');

        if (!$id_commande_client) {
            $errors[] = 'ID de la commande client absent';
        }

        if (!$serial) {
            $errors[] = 'Numéro de série absent';
        }

        if (!count($errors)) {
            BimpObject::loadClass('bimpreservation', 'BR_Reservation');
            $id_reservation = (int) BR_Reservation::findEquipmentToReceive($id_commande_client, $serial, $errors);

            if ($id_reservation) {
                $reservation = BimpCache::getBimpObjectInstance('bimpreservation', 'BR_Reservation', $id_reservation);
                if ($reservation->isLoaded()) {
                    $ref = $reservation->getData('ref') . '" (ID ' . $id_reservation . ')"';
                } else {
                    $ref = 'd\'ID ' . $id_reservation;
                }
                $success = 'Attribution d\'un équipement à la réservation ' . $ref . ' et changement de statut effectué avec succès';
            }
        }

        die(json_encode(array(
            'errors'         => $errors,
            'success'        => $success,
            'id_reservation' => $id_reservation,
            'request_id'     => BimpTools::getValue('request_id', 0)
        )));
    }

    protected function ajaxProcessRemoveFromCommandeFournisseur()
    {
        $errors = array();

        $id_reservation_cmd_fourn = (int) BimpTools::getValue('id_reservation_cmd_fourn', 0);
        $force_remove = (int) BimpTools::getValue('force_remove', 0);

        if (!$id_reservation_cmd_fourn) {
            $errors[] = 'ID de la la réservation absent';
        } else {
            $reservation = BimpCache::getBimpObjectInstance('bimpreservation', 'BR_ReservationCmdFourn', $id_reservation_cmd_fourn);
            if (!$reservation->isLoaded()) {
                $errors[] = 'ID de la réservation invalide';
            } else {
                $reservation->removeFromCommandeFournisseur($errors, $force_remove);
                if ($force_remove) {
                    $success = 'Produits désassociés de la commande fournisseur avec succès';
                } else {
                    $success = 'Produits retirés de la commande fournisseur avec succès';
                }
            }
        }

        die(json_encode(array(
            'errors'     => $errors,
            'success'    => $success,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }
}
