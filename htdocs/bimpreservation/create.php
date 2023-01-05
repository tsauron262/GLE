<?php

require_once '../bimpcore/main.php';

ini_set('display_errors', 1);

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
$id_commande = (int) BimpTools::getValue('id_commande');

if (!$id_commande) {
    echo 'ID de la commande client absent';
    exit;
}

$reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');

$id_entrepot = 1;
$errors = $reservation->createReservationsFromCommandeClient($id_entrepot, $id_commande);

if (count($errors)) {
    echo BimpRender::renderAlerts($errors);
    exit;
}

header("Location: " . DOL_URL_ROOT . '/bimpreservation/index.php?fc=commande&id=' . $id_commande);