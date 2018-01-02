<?php

require_once("../main.inc.php");

ini_set('display_errors', 1);

require_once './pdf/classes/PropalPDF.php';

$pdf = new PropalPDF($db);

require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
$propal = new Propal($db);
$propal->fetch(1);



$pdf->init($propal);

echo $pdf->render(DOL_DATA_ROOT.'/invoice.pdf', true);

//echo DOL_DATA_ROOT.'/invoice.pdf';
