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

if (!$shipment->isActionAllowed('fetchFullDoc', $errors)) {
    foreach ($errors as $error) {
        echo $error . "\n";
    }
    exit;
}

$dir = $shipment->getFilesDir();
if (!$dir) {
    echo 'Dossier absent';
    exit;
}

if (!preg_match('/^.+\/$/', $dir)) {
    $dir .= '/';
}

$i = 1;
$files = array();
$files_labels = array();

// Borderau: 
$file_path = $shipment->getPackingListFilePath();
if (!file_exists($file_path)) {
    $file_errors = $shipment->fetchPackingList();
    if (count($file_errors)) {
        $errors[] = BimpTools::getMsgFromArray($file_errors, 'Echec obtention du bordereau d\'expédition');
    }
}
if (file_exists($file_path)) {
    $files[$i] = $file_path;
    $i++;
    $files_labels[] = '1 bordereau d\'expédition (Packing List)';
}


// Etiquette retour: 
//$file_path = $shipment->getBulkReturnLabelFilePath();
//if (!file_exists($file_path)) {
//    $file_errors = $shipment->fetchBulkReturnLabel();
//    if (count($file_errors)) {
//        $errors[] = BimpTools::getMsgFromArray($file_errors, 'Echec obtention de l\'étiquette de retour');
//    }
//}
//$files[$i] = $file_path;
//$i++;
//
//
// Etiquette UPS: 

$file_path = $dir . 'Etiquette_ups_retour_groupe_' . $shipment->id . '.pdf';
if (file_exists($file_path)) {
    $files[$i] = $file_path;
    $i++;
    $files_labels[] = '1 Etiquette d\'expédition UPS';
}

// Parts: 

$parts = $shipment->getChildrenObjects('parts');
$nParts = 0;
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
        $files[$i] = $filePath;
        $i++;
        $nParts++;
    } else {
        $errors[] = BimpTools::getMsgFromArray($errors, 'Composant #' . $part->id . ' - ' . $part->getData('part_number'), true);
    }
}

if (count($errors)) {
    echo BimpTools::getMsgFromArray($errors, 'Erreur(s)', true);
    exit;
}
$files_labels[] = '1 Etiquette pour chaque composant (' . $nParts . ')';


// En-tête:

$header_file_path = $dir . 'en_tete.pdf';
unlink($header_file_path);
if (!file_exists($header_file_path)) {
    require_once DOL_DOCUMENT_ROOT . '/bimpapple/pdf/ShipmentHeaderPDF.php';
    global $db;
    $pdf = new ShipmentHeaderPDF($db, $shipment);
    $pdf->files_list_labels = $files_labels;

    if (!$pdf->render($header_file_path, 'F')) {
        $errors[] = 'Echec de la création  du fichier d\'en-tête';
    }
}

if (count($errors)) {
    echo BimpTools::getMsgFromArray($errors, 'Erreur(s)', true);
    exit;
}

if (file_exists($header_file_path)) {
    $files[0] = $header_file_path;
}

ksort($files);

require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/BimpPDF.php';

$pdf = new BimpConcatPdf();
$pdf->concatFiles('retour_groupe_' . $shipment->id . '.pdf', $files, 'I');
