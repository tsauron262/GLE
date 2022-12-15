<?php

require_once __DIR__ . "/../main.inc.php";

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
ini_set('display_errors', 1);

global $plpdf_errors;

if (is_null($plpdf_errors)) {
    $plpdf_errors = array();
}

require_once DOL_DOCUMENT_ROOT . '/bimpfinancement/pdf/PropositionLocationPDF.php';

$pdf = new PropositionLocationPDF();
$pdf->render('Proposition_Location', 'I', true);

if (count($pdf->errors)) {
    $plpdf_errors = $pdf->errors;
}