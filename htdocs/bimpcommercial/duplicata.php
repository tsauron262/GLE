<?php

$ref = '';
$id = 0;
$type = '';

if (isset($_GET['r'])) {
    $ref = trim(strip_tags(stripslashes($_GET['r'])));
}

if (isset($_GET['i'])) {
    $id = (int) $_GET['i'];
}

if (isset($_GET['t'])) {
    $type = trim(strip_tags(stripslashes($_GET['t'])));
} else {
    $type = 'facture';
}

if (!in_array($type, array('facture', 'propale', 'commande'))) {
    echo 'Type de document invalide';
    exit;
}

if (!$ref || !$id) {
    echo 'Référence du document absente';
    exit;
}

define('NOLOGIN', '1');
require_once __DIR__ . "/../main.inc.php";

global $db;

switch ($type) {
    case 'propale':
        $sql = 'SELECT ref FROM ' . MAIN_DB_PREFIX . 'propal WHERE rowid = ' . $id . ' LIMIT 1';
        break;

    case 'commande':
        $sql = 'SELECT ref FROM ' . MAIN_DB_PREFIX . 'commande WHERE rowid = ' . $id . ' LIMIT 1';
        break;

    case 'facture':
        $sql = 'SELECT facnumber as ref FROM ' . MAIN_DB_PREFIX . 'facture WHERE rowid = ' . $id . ' LIMIT 1';
        break;
}

$result = $db->query($sql);

$ref_check = '';
if ($result && $db->num_rows($result)) {
    $obj = $db->fetch_object($result);
    $db->free($result);
    $ref_check = $obj->ref;
}
$db->free($result);

if ($ref_check !== $ref) {
    echo 'Référence du document invalide';
    exit;
}

require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/BimpPDF.php';

$ref = dol_sanitizeFileName($ref);

$srcFile = DOL_DATA_ROOT . '/' . $type . '/' . $ref . '/' . $ref . '.pdf';

if (!file_exists($srcFile)) {
    echo 'Fichier absent. Veuillez contacter votre interlocuteur';
    exit;
}

$pdf = new BimpConcatPdf();
$pdf->generateDuplicata($srcFile, $ref . '_duplicata.pdf', 'DUPLICATA', 'I');

