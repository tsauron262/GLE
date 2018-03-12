<?php

class reservationController extends BimpController
{

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
            $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation', $id_reservation);
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
            $id_reservation = BR_Reservation::findEquipmentToReceive($id_commande_client, $serial, $errors);

            if ($id_reservation) {
                $success = 'Attribution d\'un équipement à la réservation ' . $id_reservation . ' et changement de statut effectué avec succès';
            }
        }

        die(json_encode(array(
            'errors'         => $errors,
            'success'        => $success,
            'id_reservation' => $id_reservation,
            'request_id'     => BimpTools::getValue('request_id', 0)
        )));
    }
}
