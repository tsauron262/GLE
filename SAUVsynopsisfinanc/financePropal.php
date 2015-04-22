<?php
/*
 * * GLE by Synopsis et DRSI
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
 * GLE-1.2
 */
require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT . '/core/lib/contract.lib.php');

$langs->load("propal");

$id = $_REQUEST['id'];
restrictedArea($user, 'propal', $id, '');


llxHeader($js, 'Finanacement');

require_once(DOL_DOCUMENT_ROOT."/core/lib/propal.lib.php");
require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
$object = new Propal($db);
$object->fetch($id);

$head = propal_prepare_head($object);


dol_fiche_head($head, "financ", $langs->trans("Propal"));



$totG = $object->total_ht;
$totService = 0;
$totLogiciel = 0;
$totMateriel = $totG - $totService - $totLogiciel;

echo "<table><tr><td>Total propal : ".$totG."</td><td>Total service : ".$totService."</td><td>Total logiciel : ".$totLogiciel."</td><td>Tot Materiel : ".$totMateriel."</td></tr></table>";

$montantAF = (isset($_POST['montantAF']))? $_POST['montantAF'] : $totG;
$commC = (isset($_POST['commC']))? $_POST['commC'] : 2;
$commF = (isset($_POST['commF']))? $_POST['commF'] : 8;
$duree = (isset($_POST['duree']))? $_POST['duree'] : 24;
$tauxInteret = (isset($_POST['taux']))? $_POST['taux'] : 2.4;

echo "<form method='POST'>";

echo "<input type='hidden' name='form1'/>";

echo "<input type='text' name='montantAF' value='".$montantAF."'/>";

echo "<select name='periode'><option value='1'>Mensuel</option><option value='3'>Trimestrielle</option><option value='4'>Semestriel</option></select>";

echo "<select name='duree'><option value='24'>24 mois</option><option value='36'>36 mois</option><option value='48'>48 mois</option><option value='240'>240 mois</option></select>";

echo "Comm Comme<input type='text' name='commC' value='".$commC."'/>%";

echo "Comm Finan<input type='text' name='commF' value='".$commF."'/>%";

echo "Taux<input type='text' name='taux' value='".$tauxInteret."'/>%";

echo "<input type='submit' value='Valider'/>";


if(isset($_POST['form1'])){
    echo "<br/><br/>";
    $totalPostComm = $montantAF * (100 + $commC) / 100 * (100 + $commF) / 100;
//    $restant = $totG;
//    $totalInteret = 0;
//    for($i = 1; $i <= $duree; $i++){
//        $interet = $restant * $tauxInteret / 100;
//        $restant += $interet;
//        $restant
//        $totalInteret += $interet;
//    }
    $tauxInteret = $tauxInteret / 100 / 12;
    
    
    $mensualite = $totalPostComm * $tauxInteret / (1 - pow((1 + $tauxInteret), -$duree));
    
    echo "Montant Total a enprunter : ".$totalPostComm;
    
    echo"<br/><br/>";
    
    echo "Mensualite : ".price($mensualite)." €   X   ".$duree." mois soit ".price($duree*$mensualite)." €";
}