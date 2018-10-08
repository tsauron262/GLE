<?php
/*
 * * BIMP-ERP by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.2
 * Created on : 19 oct. 2010
 *
 * Infos on http://www.finapro.fr
 *
 */
/**
 *
 * Name : intervByContrat.php
 * BIMP-ERP-1.2
 */
require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT . '/core/lib/contract.lib.php');

$langs->load("contracts");

restrictedArea($user, 'contrat', $contratid, '');

$id = $_REQUEST['id'];

llxHeader($js, 'Prélèvement Automatique');

$contrat = getContratObj($_REQUEST["id"]);
$contrat->fetch($_GET["id"]);
$contrat->info($_GET["id"]);

$head = contract_prepare_head($contrat);
//$head = $contrat->getExtraHeadTab($head);


dol_fiche_head($head, "prelevAuto", $langs->trans("Contract"));

if(isset($_REQUEST['valider'])){
    $db->query("UPDATE ".MAIN_DB_PREFIX."synopsisprelevauto set dateDeb='".convertirDate($_REQUEST['dateDeb'], false)."', dateDern='".convertirDate($_REQUEST['dateDern'], false)."', "
            . "dateProch=".(isset($_REQUEST['dateProch']) && $_REQUEST['dateProch'] != "" ? "'".convertirDate($_REQUEST['dateProch'], false)."'" : 'null').", periode='".$_REQUEST['periode']."' WHERE type='contrat' AND referent=".$id);
}

if(isset($_REQUEST['prelevOk'])){
    $db->query("UPDATE ".MAIN_DB_PREFIX."synopsisprelevauto set dateDeb='".convertirDate($_REQUEST['dateDeb'], false)."', "
            . "dateDern=now(), dateProch ='".date('Y-m-d H:i:s', strtotime('+'.$_REQUEST['periode'].' month',strtotime(date('Y-m-d'))))."',"
            . "periode='".$_REQUEST['periode']."', nb=nb+1, note=concat(note, '<br/>Prélèvement fait le ". dol_print_date(date("Y-m-d H:i:s")) ." par ".$user->getFullName($langs)."') WHERE type='contrat' AND referent=".$id);
}



$sql = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."synopsisprelevauto WHERE type='contrat' AND referent=".$id);
if(!$db->num_rows($sql) > 0){
    $db->query("INSERT INTO ".MAIN_DB_PREFIX."synopsisprelevauto (type, referent, nb) VALUES ('contrat',".$id.", 0)");
}
    $result = $db->fetch_object($sql);

echo "<form method='POST'>";

echo "Date debut :";
echo "<input type='text' class='datePicker' name='dateDeb' value='".(isset($result)? dol_print_date($result->dateDeb, '%d/%m/%Y'): "")."'/></br>";

echo "Périodicité :";
echo "<input type='text' class='' name='periode' value='".(isset($result)? $result->periode: "")."'/> mois</br>";


echo "Date dernié prélèvement :";
echo "<input type='text' class='datePicker' name='dateDern' value='".(isset($result)? dol_print_date($result->dateDern, '%d/%m/%Y'): "")."'/></br>";

echo "Date prochain prélèvement :";
echo "<input type='text' class='datePicker' name='dateProch' value='".(isset($result)? dol_print_date($result->dateProch, '%d/%m/%Y'): "")."'/></br>";


echo "Nombre prélèvement effectué :";
echo (isset($result)? $result->nb: "0")."</br></br>";


echo "Note :";
echo (isset($result)? $result->note: "")."</br></br>";


echo "<input type='submit' class='butAction' name='valider' value='Valider'/>";

echo "<input type='submit' class='butAction' name='prelevOk' value='Prélèvement effectué'/>";