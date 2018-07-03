<?php

require_once("../main.inc.php");

ini_set('display_errors', 1);

//require_once './pdf/classes/InvoicePDF.php';
//$pdf = new InvoicePDF(41051);
//$pdf->render(DOL_DATA_ROOT.'/facture.pdf', true);
//echo DOL_DATA_ROOT.'/invoice.pdf';


//require_once './pdf/classes/LoyerPDF.php';
require_once './pdf/classes/PropalPDF.php';
//require_once './pdf/classes/PropalSavPDF.php';
//require_once './pdf/classes/InvoiceSavPDF.php';

global $db, $langs;

$pdf = new PropalPDF($db);
//$pdf = new LoyerPDF($db);
//$pdf = new PropalSavPDF($db);

$obj = new Propal($db);

//$pdf = new InvoiceSavPDF($db);

//$obj = new Facture($db);
$obj->fetch(92799);

$pdf->init($obj);
$pdf->render(__DIR__. '/testInvoice.pdf', true);

if (count($pdf->errors)) {
    $pdf->displayErrors();
}

//$pdf->write_file($propal, $langs);

//$pdf->render(DOL_DATA_ROOT . '/propal.pdf', true);


