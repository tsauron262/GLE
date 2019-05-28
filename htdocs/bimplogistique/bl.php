<?php

define('NOLOGIN', '1');

require_once("../main.inc.php");

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/OrderPDF.php';

global $db, $langs;

$id_shipment = (int) BimpTools::getValue('id_shipment');

$errors = array();

ini_set('display_errors', 1);

if (!$id_shipment) {
    $errors[] = 'ID de la l\'expédition absent';
} else {
    $shipment = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeShipment', $id_shipment);

    if (!BimpObject::objectLoaded($shipment)) {
        $errors[] = 'L\'expédition d\'ID ' . $id_shipment . ' n\'existe pas';
    } else {
        $commande = $shipment->getParentInstance();

        if (!BimpObject::objectLoaded($commande)) {
            $errors[] = 'ID de la commande client absent';
        } else {
            $pdf = new BLPDF($db, $shipment);

            $pdf->init($commande->dol_object);
            $file = $pdf->getFilePath() . $pdf->getFileName();
            $pdf->render($file, true);
            exit;
        }
    }
}

if (count($errors)) {
    echo BimpRender::renderAlerts($errors);
    exit;
}


