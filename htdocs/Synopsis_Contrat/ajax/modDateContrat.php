<?php
require_once('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/Synopsis_Contrat/class/contrat.class.php");
$contrat = new Synopsis_Contrat($db);
$contrat->fetch($_REQUEST['id']);
$date = $_REQUEST['date_contrat'];
$tabT = explode("/", $date);
if(isset($tabT[2]))
    $date = $tabT[2]."/".$tabT[1]."/".$tabT[0];
$contrat->setDateContrat($date);
header("location: ".DOL_URL_ROOT."/contrat/fiche.php?id=".$_REQUEST['id']);
?>