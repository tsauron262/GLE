<?php

define('NOLOGIN', '1');

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';

//llxHeader();

echo '<!DOCTYPE html>';
echo '<html lang="fr">';

echo '<head>';
//echo '<link rel="stylesheet" type="text/css" href="' . DOL_URL_ROOT . '/bimpcore/views/css/ticket.css' . '"/>';
echo '<script src="/test2/includes/jquery/js/jquery.min.js?version=6.0.4" type="text/javascript"></script>';
echo '</head>';

echo '<body>';

global $db;
$bdb = new BimpDb($db);

$id_commande = (int) BimpTools::getValue('id_commande', 0);
$id_entrepot = (int) BimpTools::getValue('id_entrepot', 0);


if (!$id_commande) {
    echo 'ID COMMANDE ABSENT';
} elseif (!$id_entrepot) {
    echo 'ID ENTREPOT ABSENT';
} else {
    $commande = BimpObject::getInstance('bimpcommercial', 'Bimp_Commande', $id_commande);

    if (!$commande->isLoaded()) {
        echo 'ID COMMANDE INVALIDE';
    } else {
        $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');
        $errors = $reservation->createReservationsFromCommandeClient($id_entrepot, $id_commande);

        if (count($errors)) {
            echo '<pre>';
            print_r($errors);
            echo '</pre>';
        } else {
            echo 'OK';
        }
    }
}

echo '</body></html>';

//llxFooter();
