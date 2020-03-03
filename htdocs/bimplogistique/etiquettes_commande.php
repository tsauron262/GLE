<?php

require_once("../main.inc.php");

ini_set('display_errors', 1);

require_once './pdf/classes/ExpeditionPDF.php';


global $db, $langs;
$errors = array();

$id_commande = (int) BimpTools::getValue('id_commande', 0);

if (!$id_commande) {
    die('Erreur: ID de la commande client absent');
}

$commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $id_commande);

if (!BimpObject::objectLoaded($commande)) {
    die('Erreur: La commande client d\'ID ' . $id_commande . ' n\'existe pas');
}

$qty = (int) BimpTools::getValue('qty', 1);

$pdf = new ExpeditionPDF($db);

$pdf->qty_etiquettes = $qty;
$pdf->init($commande);

if ($pdf->render('etiquette_cmd_' . $id_commande . '.pdf', true, '123')) {
    exit;
}

if (count($pdf->errors)) {
    $pdf->displayErrors();
}