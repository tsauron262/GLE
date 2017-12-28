<?php

require_once("../main.inc.php");

ini_set('display_errors', 1);

require_once './pdf/classes/InvoicePDF.php';

$pdf = new InvoicePDF();

$pdf->render('invoice.pdf', true);
