<?php

define('NOLOGIN', '1');

require_once("../main.inc.php");

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/TransferPDF.php';

global $db, $langs;

$id_transfer = (int) BimpTools::getValue('id_transfer');

$errors = array();

ini_set('display_errors', 1);

if (!$id_transfer) {
    $errors[] = 'ID du transfer absent';
} else {
    $transfer = BimpCache::getBimpObjectInstance('bimptransfer', 'Transfer', $id_transfer);

    if (!BimpObject::objectLoaded($transfer)) {
        $errors[] = 'Le transfer d\'ID ' . $id_transfer . ' n\'existe pas';
    } else {
        $pdf = new TransferPDF($db);

        $pdf->init($transfer);

        $file = $pdf->getFilePath() . $pdf->getFileName();
        ini_set('display_errors', 1);
        $pdf->render($file, true);
        exit;
    }
}

if (count($errors)) {
    echo BimpRender::renderAlerts($errors);
    exit;
}


