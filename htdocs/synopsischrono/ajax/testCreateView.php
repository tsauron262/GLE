<?php
die("Attention");
require_once('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT . "/synopsischrono/class/chrono.class.php");
require_once(DOL_DOCUMENT_ROOT . "/contact/class/contact.class.php");



$resultModel = $db->query("SELECT * FROM llx_synopsischrono_conf");
while ($ligneModel = $db->fetch_object($resultModel)) {
    $requetePre = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono_key WHERE model_refid =  " . $ligneModel->id;
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


    $nbVue = 1;
//    $view = "llx_synopsischrono_view_" . traiteCarac($ligneModel->id, "") . "";
//    if ($view == "llx_synopsischrono_view_105")
//        $nbVue = 2;
//    for ($cont = 1; $cont <= $nbVue; $cont++) {
//    $sqlPre = $db->query($requetePre);
//        $createView1 = $createView2 = $createView3 = "";
//        $i = 0;
//
//        if ($cont == 2)
//            $view .= "_simple";
//        $requeteView0 = "DROP VIEW IF EXISTS " . $view . ";";
//        while ($resPre = $db->fetch_object($sqlPre)) {
//            $i++;
//
//            if ($resPre->nom == "Note")
//                $resPre->nom = $resPre->nom . "2";
//            $resPre->nom = traiteCarac($resPre->nom);
//            
//            if ($cont == 2 && !in_array($resPre->nom, array("Centre", "Etat")))
//                continue;
//            $createView3 .= ' LEFT JOIN llx_synopsischrono_value tab' . $i . ' on tab' . $i . '.chrono_refid = chrono.id AND tab' . $i . '.key_id = ' . $resPre->id;
//            if ($resPre->type_valeur == 8) {//Liste
//                $createView3 .= ' LEFT JOIN llx_Synopsis_Process_form_list_members tab' . $i . 'list ON tab' . $i . 'list.list_refid = ' . $resPre->type_subvaleur . ' AND tab' . $i . 'list.valeur = tab' . $i . '.value';
//                $createView2 .= ',tab' . $i . 'list.label as ' . $resPre->nom . ', tab' . $i . 'list.valeur as ' . $resPre->nom . "Val";
//            } else {
//                $createView2 .= ',tab' . $i . '.value as ' . $resPre->nom;
//            }
//        }
//        $requeteView = "Create view " . $view . " as (SELECT chrono.* " . $createView2 . " FROM llx_synopsischrono chrono" . $createView3 . " WHERE model_refid = " . $ligneModel->id . ");";
//        $db->query($requeteView0);
//        $db->query($requeteView);
//        echo $requeteView0 . $requeteView;
//    }




    $view = "llx_synopsischrono_chrono_" . traiteCarac($ligneModel->id, "") . "";
    if ($view == "llx_synopsischrono_view_105")
        $nbVue = 2;
    for ($cont = 1; $cont <= $nbVue; $cont++) {
        $sqlPre = $db->query($requetePre);
        $createView1 = $createView2 = $createView3 = "";
        $i = 0;

        if ($cont == 2)
            $view .= "_simple";
        $requeteView0 = "DROP VIEW IF EXISTS " . $view . ";";
        $tabChamp = array("id int(10) PRIMARY KEY");
//        $res2 = $db->query("DESCRIBE llx_synopsischrono");
//        while ($ligne2 = $db->fetch_object($res2)) {
//            $tabChamp[] = $ligne2->Field . " " . $ligne2->Type;
//        }
        while ($resPre = $db->fetch_object($sqlPre)) {
            $i++;




            $champ = "";
            if ($resPre->nom == "Note")
                $resPre->nom = $resPre->nom . "2";
            $resPre->nom = traiteCarac($resPre->nom);

            if ($cont == 2 && !in_array($resPre->nom, array("Centre", "Etat")))
                continue;
            $createView3 .= ' LEFT JOIN llx_synopsischrono_valueSAUV tab' . $i . ' on tab' . $i . '.chrono_refid = chrono.id AND tab' . $i . '.key_id = ' . $resPre->id;
            $champ = $resPre->nom;
            $champ .= getTypeForSql($resPre->type_valeur);
            $tabChamp[] = $champ;
//            if ($resPre->type_valeur == 8) {//Liste
//                $champ = $resPre->nom . "Val";
//                $champ .= getTypeForSql(1);
//                $tabChamp[] = $champ;
//            }

            /*if ($resPre->type_valeur == 8) {//Liste
                $createView3 .= ' LEFT JOIN llx_Synopsis_Process_form_list_members tab' . $i . 'list ON tab' . $i . 'list.list_refid = ' . $resPre->type_subvaleur . ' AND tab' . $i . 'list.valeur = tab' . $i . '.value';
                $createView2 .= ',tab' . $i . 'list.label as ' . $resPre->nom . ', tab' . $i . 'list.valeur as ' . $resPre->nom . "Val";
            } else*/if($resPre->type_valeur == 2) {
                $createView2 .= ',STR_TO_DATE(tab' . $i . '.value, "%d/%m/%Y") as ' . $resPre->nom;
            }elseif($resPre->type_valeur == 3) {
                $createView2 .= ',STR_TO_DATE(tab' . $i . '.value, "%d/%m/%Y %H:%i:%s") as ' . $resPre->nom;
            }else {
                $createView2 .= ',tab' . $i . '.value as ' . $resPre->nom;
            }


//            print_r($resPre);die;
        }
        $requeteView = "Create table If not exists " . $view . " (" . implode(",", $tabChamp) . ");";
        $requeteView2 = "INSERT INTO " . $view . " (SELECT chrono.id " . $createView2 . " FROM llx_synopsischrono chrono" . $createView3 . " WHERE model_refid = " . $ligneModel->id . " GROUP BY chrono.id);";
        $db->query($requeteView0);
        $db->query($requeteView);
        $db->query($requeteView2);
        echo $requeteView0 . $requeteView . $requeteView2;
    }
}

function getTypeForSql($typeId) {
    $champ = "";
    if (in_array($typeId, array(1,9,8)))
        $champ .= " varchar(2000)";
    elseif ($typeId == 2)
        $champ .= " date";
    elseif ($typeId == 3)
        $champ .= " datetime";
    elseif ($typeId == 4)
        $champ .= " boolean";
    else
        $champ .= " int(15)";
    return $champ;
}
