<?php

require_once("../main.inc.php");

ini_set('display_errors', 1);

require_once './pdf/classes/InvoicePDF.php';

$pdf = new InvoicePDF(41051);

$pdf->render(DOL_DATA_ROOT.'/invoice.pdf', true);

//echo DOL_DATA_ROOT.'/invoice.pdf';
