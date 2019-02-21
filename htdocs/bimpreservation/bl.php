<?php

define('NOLOGIN', '1');

require_once("../main.inc.php");

ini_set('display_errors', 1);
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/OrderPDF.php';

global $db, $langs;

$id_commande = (int) BimpTools::getValue('id_commande');
$num_bl = (int) BimpTools::getValue('num_bl');
$id_contact = (int) BimpTools::getValue('id_contact_shipment', 0);

$errors = array();

if (!$id_commande) {
    $errors[] = 'ID de la commande absent';
}

if (!$num_bl) {
    $errors[] = 'N° du bon de livraison absent';
}

if (!count($errors)) {
    $pdf = new BLPDF($db, $num_bl, $id_contact);

    $obj = new Commande($db);
    if ($obj->fetch($id_commande) <= 0) {
        $errors[] = 'Commande invalide';
    } else {
        $pdf->init($obj);
        $file = $pdf->getFilePath() . $pdf->getFileName();
        $pdf->render($file, true);
        exit;
    }
}

if (count($errors)) {
    echo BimpRender::renderAlerts($errors);
    exit;
}


