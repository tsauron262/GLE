<?php

$id_shipment = 0;

if (isset($_GET['id'])) {
    $id_shipment = (int) $_GET['id'];
}

if (!$id_shipment) {
    echo 'ID du retour groupé absent';
    exit;
}

require_once __DIR__ . "/../main.inc.php";

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
ini_set('display_errors', 1);

$shipment = BimpCache::getBimpObjectInstance('bimpapple', 'AppleShipment', $id_shipment);

if (!BimpObject::objectLoaded($shipment)) {
    echo 'Le retour groupé #' . $id_shipment . ' n\'existe pas';
    exit;
}

$errors = array();

if (!$shipment->isActionAllowed('fetchPartsReturnLabel', $errors)) {
    foreach ($errors as $error) {
        echo $error . "\n";
    }
    exit;
}

$filters = array();
$pack_number = BimpTools::getValue('pack_number', 0, 'int');

if ($pack_number) {
    $filters['pack_number'] = $pack_number;
}
$parts = $shipment->getChildrenObjects('parts', $filters);

if (!count($parts)) {
    echo 'Aucun composant enregistré pour ce retour groupé';
    exit;
}

$files = array();
$errors = array();

foreach ($parts as $part) {
    $part_errors = array();
    $filePath = $part->getReturnLabelFilePath();

    if ($filePath) {
        if (!file_exists($filePath)) {
            $part_errors = $part->fetchReturnLabel();
        }
    } else {
        $part_errors[] = 'N° de retour absent';
    }

    if (!count($part_errors)) {
        $files[] = $filePath;
    } else {
        $errors[] = BimpTools::getMsgFromArray($errors, 'Composant #' . $part->id . ' - ' . $part->getData('part_number'), true);
    }
}

if (count($errors)) {
    echo BimpTools::getMsgFromArray($errors, 'Erreur(s)', true);
    exit;
}

require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/BimpPDF.php';

$pdf = new BimpConcatPdf();
$pdf->concatFiles('retour_groupe_' . $shipment->id . '_etiquettes_composants' . ($pack_number ? '_colis_' . $pack_number : '') . '.pdf', $files, 'I');

