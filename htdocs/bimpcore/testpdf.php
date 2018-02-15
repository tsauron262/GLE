<?php

require_once("../main.inc.php");

ini_set('display_errors', 1);

//require_once './pdf/classes/InvoicePDF.php';
require_once './pdf/classes/PropalPDF.php';

$pdf = new PropalPDF(3200);

$pdf->render(DOL_DATA_ROOT.'/propal.pdf', true);

//echo DOL_DATA_ROOT.'/invoice.pdf';
