<?php
require_once('../../main.inc.php');
require_once DOL_DOCUMENT_ROOT . '/synopsisapple/ups/GsxUps.class.php';
llxHeader();
?>
<link type="text/css" rel="stylesheet" href="./GsxUps.css"/>
<script type="text/javascript" src="./appleUps.js"></script>
<?php
//error_reporting(E_ALL);
error_reporting(E_ERROR);
ini_set('display_errors', 1);

$GU = new GsxUps('');
echo $GU->getShipToForm();
echo $GU->getCurrentShipmentsHtml();

//$_REQUEST['shipId'] = 1;
//$_REQUEST['action'] = 'generatePdf';
//
//$GU->getPDFGenerationForm();

//$r = $GU->loadBulkReturnProforma(1);
//echo '<pre>';
//print_r($r);
//global $db;
//$ship = new shipment($db, 1);
//echo $ship->getInfosHtml();

if (isset($_REQUEST['shipId']) && !empty($_REQUEST['shipId'])) {
    echo '<input id="shipmentToLoad" type="hidden" value="' . $_REQUEST['shipId'] . '">';
}
?>