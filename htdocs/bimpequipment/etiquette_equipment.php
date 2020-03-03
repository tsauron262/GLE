<?php

require_once("../main.inc.php");
require_once ("../bimpcore/Bimp_Lib.php");
ini_set('display_errors', 1);

global $db, $langs;
$errors = array();

$equipments = array();

$id_equipment = (int) BimpTools::getValue('id_equipment', 0);

$filename = '';

if ($id_equipment) {
    $equipments[] = $id_equipment;
    $filename = 'etiquette_equipement_' . $id_equipment;
} else {
    $equipments = explode(',', BimpTools::getValue('equipments', array()));
    $filename = 'etiquettes_equipements_' . date('Y-m-d_H-i');
}

if (empty($equipments)) {
    die('Erreur: aucun équipement spécifié');
}

require_once './pdf/classes/EquipmentPDF.php';
$pdf = new EquipmentPDF($db);

$equipment = null;
foreach ($equipments as $id_equipment) {
    $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
    break;
}

$pdf->init($equipment);
$pdf->equipments = $equipments;

if ($pdf->render($equipments . '.pdf', true, true)) {
    exit;
}

if (count($pdf->errors)) {
    $pdf->displayErrors();
}