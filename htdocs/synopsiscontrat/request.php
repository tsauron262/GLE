<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require("../main.inc.php");




$id = $_REQUEST['id'];
$action =  $_REQUEST['action'];



if(!isset($id) || !isset($action))
    dol_syslog("Pas d'action ou pas d'id");
else{
    require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
    $contrat = new Contrat($db);
    $contrat->fetch($id);
    if($action == "activerAll" && $contrat->statut == 1){
        foreach ($contrat->lines as $ligne)
            $contrat->active_line($user, $ligne->id, $ligne->date_ouverture_prevue, $ligne->date_fin_validite);
    }
}


if(isset($id))
    header('Location: '.DOL_URL_ROOT.'/contrat/card.php?id='.$id);