<?php

require("../main.inc.php");

llxHeader();


echo "Init menu header...";
echo "<br/>".synopsisHook::getTime(true)."<br/><br/>";


$tabObj = array();
$nb = (GETPOST("max") > 0)? GETPOST("max") : 300;
$tabObj[] = array("bimpsupport", "BS_SAV", $nb);
$tabObj[] = array("bimpsupport", "BS_Ticket", $nb);

$tabObj[] = array("bimpcommercial", "Bimp_Propal", $nb);
$tabObj[] = array("bimpcommercial", "Bimp_Commande", $nb);
$tabObj[] = array("bimpcommercial", "Bimp_Facture", $nb);
$tabObj[] = array("bimpcommercial", "Bimp_CommandeFourn", $nb);
$tabObj[] = array("bimpcommercial", "Bimp_FactureFourn", $nb);

$tabObj[] = array("bimpfichinter", "Bimp_Fichinter", $nb);
$tabObj[] = array("bimpfichinter", "Bimp_Demandinter", $nb);



foreach($tabObj as $infoObj){
    $max = $infoObj[2];
    $maxList = $max*25;
    $nomObj = $infoObj[1];
    $nomModule = $infoObj[0];
    $objs = BimpObject::getInstance($nomModule, $nomObj);
    $list = $objs->getList(null, $maxList);


    echo "Init list de ".$maxList." ".$nomObj;
    echo "<br/>".synopsisHook::getTime(true)."<br/><br/>";

    $i = 0;
    foreach($list as $el){
        if($i >= $max)
            break;
        $i++;
        $obj  = BimpObject::getInstance($nomModule, $nomObj, $el['id']);
//        echo $obj->getNomUrl();
    }


    echo "Init de ".$i." ".$nomObj;
    echo "<br/>".synopsisHook::getTime(true)."<br/><br/>"; 
}

llxFooter();