<?php
require_once('../../main.inc.php');
require_once DOL_DOCUMENT_ROOT . '/synopsisapple/ups/GsxUps.class.php';
llxHeader();
?>
<link type="text/css" rel="stylesheet" href="./GsxUps.css"/>
<script type="text/javascript" src="./appleUps.js"></script>
<?php
error_reporting(E_ALL);
//error_reporting(E_ERROR);
ini_set('display_errors', 1);

$GU = new GsxUps('');
echo $GU->getShipToForm();



//$GU->ref = "gfgdfg";
//$object = $GU;


$object = new shipment($db,1);
$upload_dir = $conf->synopsisapple->dir_output . "/" . $object->ref;

if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'generatePdf' || $_REQUEST['action'] == 'builddoc')) {
    require_once(DOL_DOCUMENT_ROOT . "/synopsisapple/core/modules/synopsisapple/modules_synopsisapple.php");
    $model = (isset($_REQUEST['model']) ? $_REQUEST['model'] : 'appleretour');
    synopsisapple_pdf_create($db, $object, $model);
}

$filename = sanitize_string($object->ref);
$urlsource = $_SERVER["PHP_SELF"] . "?";
$genallowed = 1; //$user->rights->synopsischrono->Global->read;
require_once(DOL_DOCUMENT_ROOT . "/core/class/html.formfile.class.php");
$html = new Form($db);
$formfile = new FormFile($db);
$somethingshown = $formfile->show_documents('synopsisapple', $filename, $upload_dir, $urlsource, $genallowed, $genallowed, "Chrono", 0); //, $object->modelPdf);


echo $GU->getCurrentShipmentsHtml();

//$r = $GU->loadBulkReturnProforma(1);
//echo '<pre>';
//print_r($r);
//global $db;
//$ship = new shipment($db, 1);
//echo $ship->getInfosHtml();
?>