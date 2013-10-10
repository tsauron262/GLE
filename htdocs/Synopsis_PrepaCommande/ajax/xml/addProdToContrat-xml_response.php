<?php

/*
 * * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.2
 * Created on : 19 nov. 2010
 *
 * Infos on http://www.finapro.fr
 *
 */
/**
 *
 * Name : addProdToContrat-xml_response.php
 * GLE-1.2
 */
$resXml = "";
require_once('../../../main.inc.php');
require_once DOL_DOCUMENT_ROOT."/Synopsis_Contrat/class/contrat.class.php";
//id="+comId+"&contratId="+jQuery('#fk_contrat').find(':selected').val()+"&prodId="+pId+"&comLigneId="+ligneId
$commId = $_REQUEST['id'];
$contratId = $_REQUEST['contratId'];
$comLigneId = $_REQUEST['comLigneId'];

$contrat = new Synopsis_Contrat($db);
$contrat->fetch($contratId);
$result = $contrat->addLigneCommande($commId, $comLigneId);





//print $requete;
//lier a contratProp normalement OK??
//".MAIN_DB_PREFIX."Synopsis_contrat_GMAO
//}
if ($result) {
    $resXml = "<OK>OK</OK>";
} else {
    $resXml = "<KO>KO</KO>";
}
if (stristr($_SERVER["HTTP_ACCEPT"], "application/xhtml+xml")) {
    header("Content-type: application/xhtml+xml;charset=utf-8");
} else {
    header("Content-type: text/xml;charset=utf-8");
}
$et = ">";
print "<?xml version='1.0' encoding='utf-8'?$et\n";
print "<ajax-response>";
print $resXml;
print "</ajax-response>";
?>