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
require_once DOL_DOCUMENT_ROOT . "/Synopsis_Contrat/class/contrat.class.php";
require_once DOL_DOCUMENT_ROOT . "/commande/class/commande.class.php";

global $user;

//id="+comId+"&contratId="+jQuery('#fk_contrat').find(':selected').val()+"&prodId="+pId+"&comLigneId="+ligneId
$commId = $_REQUEST['id'];

$contrat = new Synopsis_Contrat($db);
if (isset($_REQUEST['contratId']) && $_REQUEST['contratId'] > 0) {
    $contratId = $_REQUEST['contratId'];
    $contrat->fetch($contratId);
    if ($_REQUEST['renouveler'])
        $contrat->renouvellementPart1($user, $commId);
} else {
    $commande = new Commande($db);
    $commande->fetch($commId);
    $contrat->socid = $commande->socid;
    $contrat->commercial_signature_id = 2;
    $contrat->commercial_suivi_id = 5;
    $contrat->date_contrat = dol_now();
    $contrat->create($user);
    $contrat->setExtraParametersSimple(7);
}

if (isset($_REQUEST['tabElem']))
    $tabLigne = explode("-", $_REQUEST['tabElem']);
elseif (isset($_REQUEST['comLigneId']))
    $tabLigne = array($_REQUEST['comLigneId']);
else
    die("Rien a ajouter !!! ");
foreach ($tabLigne as $comLigneId)
    $result = $contrat->addLigneCommande($commId, $comLigneId);



if ($_REQUEST['renouveler'])
    $contrat->renouvellementPart2();
elseif (!isset($_REQUEST['contratId']) || $_REQUEST['contratId'] == 0) {
    $contrat->initRefPlus();
    addElementElement("commande", "contrat", $commId, $contrat->id);
}


//print $requete;
//lier a contratProp normalement OK??
//".MAIN_DB_PREFIX."Synopsis_contrat_GMAO
//}
if ($result) {
    $resXml = $contrat->id;
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