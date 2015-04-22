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
require_once (DOL_DOCUMENT_ROOT . "/synopsisfinanc/class/synopsisfinancement.class.php");
require_once(DOL_DOCUMENT_ROOT . '/core/lib/contract.lib.php');

$langs->load("propal");

$id = $_REQUEST['id'];
restrictedArea($user, 'propal', $id, '');

$js = '<link rel="stylesheet" href="css/stylefinance.css">'
        . '<script>$(document).ready(function(){'
//            . '$("#radfinancier").attr("checked","checked");'
//            . '$(".pr").hide();'
//            . '$(".vr").hide();'
            . '$("#banque").change(function(e){'
                . 'if($("#banque").val()!=""){'
                    . '$("#taux").val($("#banque").val());'
                . '}'
                . '$("#Bcache").val($("#banque option:selected").html());'
            . '});'
            . '$("#bouton").click(function(e){'
                . 'e.preventDefault();'
                . 'calc();'
            . '});'
            . '$("#pretAP").change(function(e){'
                . 'calc();'
            . '});'
            . '$(".rad").change(function(e){'
                . 'init_location(true);'
            . '});'
            . 'init_location(false);'
        . '});'
        . 'function init_location(valdef){'
            . 'var radio=$(".rad:checked");'
            . 'if($(radio).val()=="financier"){'
                    . '$(".pr").fadeOut();'
                    . 'if(valdef)'
                    . '$("#preter").val(0);'
                    . '$(".vr").fadeOut();'
                    . 'if(valdef)'
                    . '$("#VR").val(0);'
                    . 'if(valdef)'
                    . '$("#montant").val((parseFloat($("#tot").html().replace(" ",""))));'
                . '}'
                . 'if($(radio).val()=="operationnel"){'
                    . '$(".pr").fadeOut();'
                    . 'if(valdef)'
                    . '$("#preter").val(0);'
                    . '$(".vr").fadeIn();'
                    . 'if(valdef)'
                    . '$("#VR").val(parseFloat($("#matos").html().replace(" ", ""))*0.15);'
                    . 'if(valdef)'
                    . '$("#montant").val(parseFloat($("#tot").html().replace(" ",""))-$("#VR").val());'
                . '}'
                . 'if($(radio).val()=="evol+"){'
                    . '$(".pr").fadeIn();'
                    . '$(".vr").fadeOut();'
                    . 'if(valdef){'
                    . '$("#VR").val(0);'
                    . '$("#preter").val(parseFloat($("#matos").html().replace(" ", "")));'
                    . '$("#montant").val(parseFloat($("#tot").html().replace(" ",""))-$("#preter").val());'
                    . '}'
                . '}'
        . '}'
        . 'function calc(){'
            . 'var fric_dispo = parseFloat($("#pretAP").val());'
            . 'var mois = parseFloat($("#mensuel").val());'
            . 'var dure = parseFloat($("#duree").val());'
            . 'var cC=parseFloat($("#commC").val());'
            . 'var cF=parseFloat($("#commF").val());'
            . 'var mensualite=fric_dispo/mois;'
            . 'var interet = parseFloat($("#taux").val());'
            . 'interet=interet/100/12;'
            . 'var emprunt = mensualite / (interet / (1 - Math.pow(1+interet, -dure)));'
            . 'var res=emprunt/((100+cC)/100*(100+cF)/100);'
            . 'res=Math.round(res*100)/100;'
            . '$("#montant").val(res);'
        . '}'
        . '</script>';

llxHeader($js, 'Finanacement');

require_once(DOL_DOCUMENT_ROOT . "/core/lib/propal.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/comm/propal/class/propal.class.php");
$object = new Propal($db);
$object->fetch($id);

$head = propal_prepare_head($object);


dol_fiche_head($head, "financ", $langs->trans("Propal"));



$totG = $object->total_ht;
$totService = 50;
$totLogiciel = 100;
$totMateriel = $totG - $totService - $totLogiciel;

echo '<table cellspacing=0>'
        . '<tr>'
            . '<th class="HG">Total propal:</th>'
            . '<th>Total service:</th>'
            . '<th>Total logiciel:</th>'
            . '<th class="HD">Total Materiel:</th>'
        . '</tr>'
        . '<tr>'
            . '<td class="BG" id="tot">'.price($totG).'</td>'
            . '<td id="serv">'.price($totService).'</td>'
            . '<td id="log">'.price($totLogiciel).'</td>'
            . '<td class="BD" id="matos">'.price($totMateriel).'</td>'
        . '</tr>'
    . '</table>';

$valfinance = new Synopsisfinancement($db);
$valfinance->fetch(null, $object->id);



if ($valfinance->id && !isset($_POST['form1'])) {
    $montantAF=$valfinance->montantAF;
    $commC =$valfinance->commC;
    $commF = $valfinance->commF;
    $duree = $valfinance->duree;
    $tauxInteret = $valfinance->taux;
    $banque = $valfinance->banque;
    $periode = $valfinance->periode;
    $pret=$valfinance->pret;
    $VR=$valfinance->VR;
    $location=$valfinance->location;
    
} else {

    $montantAF = (isset($_POST['montantAF'])) ? $_POST['montantAF'] : $totG;
    $commC = (isset($_POST['commC'])) ? $_POST['commC'] : 2;
    $commF = (isset($_POST['commF'])) ? $_POST['commF'] : 8;
    $duree = (isset($_POST['duree'])) ? $_POST['duree'] : 24;
    $tauxInteret = (isset($_POST['taux'])) ? $_POST['taux'] : 2.4;
    $banque = (isset($_POST['Bcache'])) ? $_POST['Bcache'] : "";
    $periode = (isset($_POST['periode'])) ? $_POST['periode'] : 1;
    $VR=(isset($_POST["VR"])) ? $_POST["VR"] : 0;
    $pret=(isset($_POST["preter"])) ? $_POST["preter"] : 0;
    $location=(isset($_POST["rad"])) ? $_POST["rad"] : "financier";
}



if (isset($_POST['form1'])) {



    $valfinance->taux = $tauxInteret;
    $valfinance->montantAF = $montantAF;
    $valfinance->periode = $periode;
    $valfinance->duree = $duree;
    $valfinance->commC = $commC;
    $valfinance->commF = $commF;
    $valfinance->banque = $banque;
    $valfinance->VR=$VR;
    $valfinance->pret=$pret;
    $valfinance->propal_id = $object->id;
    $valfinance->location=$location;
    
    $valfinance->calcul();
    
    if($valfinance->id > 0)
        $valfinance->update($user);
    else
        $valfinance->insert($user);
}



echo "<br/><hr/><br/>";
echo "<form method='POST'>";

echo "<input type='hidden' name='form1'/>";

echo '<table cellspacing=0>';
    echo '<tr><th colspan="2" class="HH">';
        foreach ($valfinance::$rad as $name => $value){
            echo "<input type='radio' class='rad' name='rad' id='rad".$name."' value='".$name."' ".(($name==$location)?"checked='checked'":"")."/><label for='rad".$name."'>".$value."</label>";
        }
    echo '</th></tr>';
    echo'<tr>';
        echo "<th><div class='pr'>Somme préter au client: <hr/></div><div class='vr'>VR: <hr/></div>Somme financée au client: <hr/>Argent disponible du client: </th>";
        echo "<td>"
        . "<div class='pr'><input type='text' id='preter' name='preter' value='".$pret."' /><hr/></div>"
        . "<div class='vr'><input type='text' id='VR' name='VR' value='".$VR."' /><hr/></div>";
        echo "<input type='text' id='montant' name='montantAF' value='" . $montantAF . "'/><hr/>";
        echo '<input type="text" id="pretAP" name="pretAP" value=""/>€<br/><button class="butAction" id="bouton">calculer le pret</button></td>';
    echo'</tr>';

    echo '<tr>';
        $tabM=array(1=>"Mensuel",3=>"Trimestriel",4=>"Quadrimestriel",6=>"Semestriel");
        echo "<th>Type de période: </th><td><select id='mensuel' name='periode'>";
        foreach ($tabM as $val=>$mensualite){
            echo "<option value='".$val."'".(($val==$periode)?'selected="selected"':"").">".$mensualite."</option>";
        }
        echo "</select></td>";
    echo '</tr>';

    echo '<tr>';
        $tabD=array(24=>"24 mois",36=>"36 mois",48=>"48 mois",240=>"240 mois");
        echo "<th>Durée du financement: </th><td><select id='duree' name='duree'>";
        foreach ($tabD as $dure=>$mois){
            echo "<option value='".$dure."'".(($dure==$duree)?'selected="selected"' :"").">".$mois."</option>";
        }
        echo "</select></td>";
    echo '</tr>';

    echo '<tr>';
        echo '<th>Commissions: </th>';
        echo "<td>Commerciale:<br/><input type='text' id='commC' name='commC' value='" . $commC . "'/>%<hr/>";
        echo "Financière:<br/><input type='text' name='commF' id='commF' value='" . $commF . "'/>%</td>";
    echo '</tr>';
    
    echo '<tr>';
        $tabB = array("" => "", "Axa" => 2, "NBP" => 4);
        echo '<th>Banque:<hr/>Taux</th><td><select id="banque">';
        foreach ($tabB as $nomB => $tauxT) {
           echo '<option value="' . $tauxT . '"'.(($nomB==$banque)?'selected="selected"' :"" ).'>' . $nomB . '</option>';
        }
        echo '</select><hr/>';
        
        echo "<input id='taux' type='text' name='taux' value='" . $tauxInteret . "'/>%</td>";
    echo '</tr>';

    echo '<input type="hidden" id="Bcache" name="Bcache" value=""/>';
    echo '<tr>';
        echo "<td class='BB' colspan='2'><input type='submit' value='Valider'/></td>";
    echo '</tr>';
echo '</table>';
echo '</form>';


if($valfinance->id > 0){

    echo "<br/><hr/><br/>";

    echo "Montant Total a emprunter sur la periode : " . price($valfinance->emprunt);

    echo"<br/><br/>";

    
    
    echo $tabM[$valfinance->periode] .": " . price(($valfinance->loyer)+0.005) . " €   X   " . $valfinance->nb_periode . " periodes soit " . price($valfinance->prix_final) . " €";
    
    if($valfinance->VR>0){
        echo " avec un VR de: ".price($valfinance->VR)." €";
    }
}

llxFooter();