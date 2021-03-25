<?php

$ref = '';
$id_fac = 0;

if (isset($_GET['r'])) {
    $ref = trim(strip_tags(stripslashes($_GET['r'])));
}

if (isset($_GET['i'])) {
    $id_fac = (int) $_GET['i'];
}

if (!$ref || !$id_fac) {
    echo 'Référence de la facture absente';
    exit;
}

define('NOLOGIN', '1');
require_once __DIR__ . "/../main.inc.php";

global $db;

$sql = 'SELECT facnumber as ref FROM ' . MAIN_DB_PREFIX . 'facture WHERE rowid = ' . $id_fac . ' LIMIT 1';
$result = $db->query($sql);

$ref_check = '';
if ($result && $db->num_rows($result)) {
    $obj = $db->fetch_object($result);
    $db->free($result);
    $ref_check = $obj->ref;
}
$db->free($result);

if ($ref_check !== $ref) {
    echo 'Référence de la facture invalide';
    exit;
}

require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/BimpPDF.php';

$srcFile = DOL_DATA_ROOT . '/facture/' . $ref . '/' . $ref . '.pdf';

if (!file_exists($srcFile)) {
    echo 'Fichier absent. Veuillez contacter votre interlocuteur';
    exit;
}

$pdf = new BimpConcatPdf();
$pdf->generateDuplicata($srcFile, $ref . '_duplicata.pdf', 'DUPLICATA', 'I');

