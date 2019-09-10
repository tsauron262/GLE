<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);

global $db, $langs;

//require_once '../pdf/classes/LoyerPDF.php';
//$obj = new Propal($db);
//$obj->fetch(92608);
//$pdf = new PropalPDF($db);
//$pdf = new LoyerPDF($db);
//$pdf->init($obj);
//$pdf->render(DOL_DATA_ROOT . '/propal.pdf', true);
//require_once '../pdf/classes/InvoicePDF.php';
//$obj = new Facture($db);
//$obj->fetch(86570);
//$pdf = new InvoicePDF($db);
//$pdf->init($obj);
//$pdf->render(__DIR__. '/testFacture.pdf', true);

require_once '../pdf/classes/OrderFournPDF.php';

$pdf = new OrderFournPDF($db);

$obj = new CommandeFournisseur($db);
$obj->fetch(500);
$pdf = new OrderFournPDF($db);
$pdf->init($obj);
$pdf->render(DOL_DATA_ROOT . '/testCommandeFourn.pdf', true);




