<?php

require_once('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT . "/synopsischrono/Chrono.class.php");
require_once(DOL_DOCUMENT_ROOT . "/contact/class/contact.class.php");



$resultModel = $db->query("SELECT * FROM llx_synopsischrono_conf");
while ($ligneModel = $db->fetch_object($resultModel)) {
    $requetePre = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono_key WHERE model_refid =  " . $ligneModel->id;
    $sqlPre = $db->query($requetePre);
    $arrPre = array();
    $arrKeyName = array();
    $arrhasTime = array();
    $arrCreateTable = array();
    $arrHasSubVal = array();
    $arrSourceIsOption = array();
    $arrphpClass = array();
    $arrvalueIsChecked = array();
    $arrvalueIsSelected = array();
    $tabLien = array();
    $tabGlobalVar = array();


    $createView1 = $createView2 = $createView3 = "";
    $i = 0;
    $view = "llx_synopsischrono_view_" . traiteCarac($ligneModel->id, "") . "";
    $requeteView0 = "DROP VIEW IF EXISTS ".$view.";";
    while ($resPre = $db->fetch_object($sqlPre)) {

        $i++;

        if($resPre->nom == "Note")
            $resPre->nom = $resPre->nom."2";
        $resPre->nom = traiteCarac($resPre->nom);
        $createView3 .= ' LEFT JOIN llx_synopsischrono_value tab' . $i . ' on tab' . $i . '.chrono_refid = chrono.id AND tab' . $i . '.key_id = ' . $resPre->id;
        if ($resPre->type_valeur == 8) {//Liste
            $createView3 .= ' LEFT JOIN llx_Synopsis_Process_form_list_members tab' . $i . 'list ON tab' . $i . 'list.list_refid = ' . $resPre->type_subvaleur . ' AND tab' . $i . 'list.valeur = tab' . $i . '.value';
            $createView2 .= ',tab' . $i . 'list.label as ' . $resPre->nom.', tab' . $i . 'list.valeur as ' . $resPre->nom."Val";
        } else {
            $createView2 .= ',tab' . $i . '.value as ' . $resPre->nom;
        }
    }
    $requeteView = "Create view ".$view." as (SELECT chrono.* " . $createView2 . " FROM llx_synopsischrono chrono" . $createView3 . " WHERE model_refid = " . $ligneModel->id . ");";
    $db->query($requeteView0);
    $db->query($requeteView);
//    echo $requeteView0 . $requeteView;
}

