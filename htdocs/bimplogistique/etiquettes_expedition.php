<?php

require_once("../main.inc.php");

ini_set('display_errors', 1);

require_once './pdf/classes/ExpeditionPDF.php';


global $db, $langs;
$errors = array();

$id_shipment = (int) BimpTools::getValue('id_shipment', 0);

if (!$id_shipment) {
    die('Erreur: ID de l\'expédition absent');
}

$shipment = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeShipment', $id_shipment);

if (!BimpObject::objectLoaded($shipment)) {
    die('Erreur: L\'expédition d\'ID ' . $id_shipment . ' n\'existe pas');
}

$qty = (int) BimpTools::getValue('qty', 1);

$pdf = new ExpeditionPDF($db);

$pdf->qty_etiquettes = $qty;
$pdf->init($shipment);

if ($pdf->render('etiquette_exp_' . $id_shipment . '.pdf', true, '123')) {
    exit;
}

if (count($pdf->errors)) {
    $pdf->displayErrors();
}