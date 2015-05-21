<?php

/*
 * * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.2
 * Created on : 18 nov. 2010
 *
 * Infos on http://www.finapro.fr
 *
 */
/**
 *
 * Name : getResults-html_response.php
 * GLE-1.2
 */
if (!isset($conf)) {
    require_once('../../main.inc.php');
    $id = $_REQUEST['id'];
} elseif (isset($idCommande) && $idCommande > 0) {
    global $idCommande;
    $id = $idCommande;
}
    require_once(DOL_DOCUMENT_ROOT . "/commande/class/commande.class.php");
    $com = new Synopsis_Commande($db);
    $com->fetch($id);
if ($com->id > 0) {
    print "<div>";
    print "<div class='titre'>R&eacute;sum&eacute;</div>";
    print "<br/>";


    print "<table><tr><td valign=top colspan=2>";
    print "<table cellpadding=10 width=764>";
    print "<tr><th class='ui-widget-header ui-state-hover' colspan=4>Interventions";
//Prevu dans la commande
//$arrGrp = array();
//$arrGrpCom = array($com->id => $com->id);
//$arrGrp = $com->listGroupMember(true);
//if (count($arrGrp) > 0 && $arrGrp) {
//    foreach ($arrGrp as $key => $commandeMember) {
//        $arrGrpCom[$commandeMember->id] = $commandeMember->id;
//    }
//}
    $arrGrpCom = $com->listIdGroupMember();
    if (!$arrGrpCom)
        $arrGrpCom = array($com->id);

//Vendu en euro
    $requete = "SELECT SUM(total_ht) as tht
              FROM " . MAIN_DB_PREFIX . "commandedet cdet,
                   " . MAIN_DB_PREFIX . "product p,
                   " . MAIN_DB_PREFIX . "categorie_product cp,
                   " . MAIN_DB_PREFIX . "categorie c
             WHERE p.rowid = cdet.fk_product
               AND c.rowid = cp.fk_categorie
               AND cp.fk_product = p.rowid
               AND c.rowid IN (SELECT catId FROM " . MAIN_DB_PREFIX . "Synopsis_PrepaCom_c_cat_total)
               AND cdet.fk_commande IN (" . join(',', $arrGrpCom) . ") ";
    $sql = $db->query($requete);
    $res = $db->fetch_object($sql);

    $vendu = $res->tht;

//Prevu en euro et en temps
    $requete = "SELECT SUM(interdet.duree) as durTot ,
                   SUM(interdet.total_ht) as totHT
              FROM " . MAIN_DB_PREFIX . "synopsisdemandeinterv inter,
                   " . MAIN_DB_PREFIX . "synopsisdemandeintervdet interdet,
                   " . MAIN_DB_PREFIX . "synopsisfichinter_c_typeInterv t
             WHERE t.id=interdet.fk_typeinterv
               AND interdet.fk_synopsisdemandeinterv = inter.rowid
               AND t.inTotalRecap=1
               AND fk_commande  IN (" . join(',', $arrGrpCom) . ") ";
    $sql = $db->query($requete);
    $res = $db->fetch_object($sql);
    $prevuEuro = $res->totHT;
    $prevuTemp = $res->durTot;
    $arr1 = convDur($res->durTot);
//Realise en euros et en temps
    $requete = "SELECT SUM(interdet.duree) as durTot ,
                   SUM(interdet.total_ht) as totHT
              FROM " . MAIN_DB_PREFIX . "Synopsis_fichinter inter,
                   " . MAIN_DB_PREFIX . "Synopsis_fichinterdet interdet,
                   " . MAIN_DB_PREFIX . "synopsisfichinter_c_typeInterv t
             WHERE t.id=interdet.fk_typeinterv
               AND interdet.fk_fichinter = inter.rowid
               AND t.inTotalRecap=1
               AND inter.fk_commande  IN (" . join(',', $arrGrpCom) . ") ";
//print $requete;
    $sql = $db->query($requete);
    $res = $db->fetch_object($sql);
    $realEuro = $res->totHT;
    $realTemp = $res->durTot;
    $arr2 = convDur($res->durTot);



    print "<tr><th width=150 class='ui-widget-header ui-state-default' >Vendu";
    print "    <td width=150 class='ui-widget-content' align=right>" . price($vendu) . " &euro;";

    print "<tr><th class='ui-widget-header ui-state-default' >Programm&eacute;";
    print "    <td class='ui-widget-content' align=right>" . price($prevuEuro) . " &euro; / " . $arr1['hours']['abs'] . "h " . ($arr1['minutes']['rel'] > 0 ? $arr1['minutes']['rel'] . "m" : "");

    print "<tr><th class='ui-widget-header ui-state-default' >R&eacute;alis&eacute;";
    print "    <td class='ui-widget-content' align=right>" . price($realEuro) . " &euro; / " . $arr2['hours']['abs'] . "h " . ($arr2['minutes']['rel'] > 0 ? $arr2['minutes']['rel'] . "m" : "");

    $diff_real_prevuEuro = $prevuEuro - $realEuro;
    $diff_real_prevuTemp = $prevuTemp - $realTemp;

    $diff_vendu_prevuEuro = $vendu - $prevuEuro;
    $diff_vendu_realEuro = $vendu - $realEuro;

    $arr3 = convDur($diff_real_prevuTemp);

    $textExtra = "<td>Pr&eacute;vu superviseur/ Vendu par com.<td align=right>" . price($diff_vendu_prevuEuro) . "&euro;<td>
          <tr><td>R&eacute;al. total / Vendu par com.<td align=right>" . price($diff_vendu_realEuro) . "&euro;<td>
          <tr><td>R&eacute;al. total / Pr&eacute;vu superviseur<td align=right>" . price($diff_real_prevuEuro) . " &euro; <td align=center>(" . $arr3['hours']['abs'] . "h " . ($arr3['minutes']['rel'] > 0 ? $arr3['minutes']['rel'] . "m" : "") . ")";


//Boite ok, sous vendu, vendu Ok
    print "<tr><td class='ui-widget-content' align=center colspan=2 >";
    if ($prevuTemp > 0 || $realTemp > 0) {
        if ($prevuEuro <= $vendu && $realEuro <= $vendu) {
            print "<table cellpadding=3><tr><td rowspan=3 colspan=2 width=90>" . /* Babel_img_crystal('Vendu OK','actions/agt_games',24,' ALIGN="middle" style="margin-bottom:8px;" '). */" Vendu OK &nbsp;&nbsp;&nbsp;" . $textExtra . "</table>";
        } else if ($prevuEuro > $vendu) {
            print "<table cellpadding=3><tr><td rowspan=3><span style='border: 0' class='ui-state-highlight'><span class='ui-icon ui-icon-alert'></span><td width=90 rowspan=3><span style='padding:3px 10px;' class='ui-state-highlight'>Sous vendu</span>" . $textExtra . "</span></table>";
        } else {
            print "<table cellpadding=3><tr><td rowspan=3><span style='border: 0' class='ui-state-error'><span class='ui-icon ui-icon-alert'></span><td rowspan=3 width=90><span style='padding:3px 10px;' class='ui-state-error'>Sous vendu</span>" . $textExtra . "</span></table>";
        }
    }

    print "</table>";
    print "<tr><td valign=top>";
    print "<table width=380 cellpadding=10>";
    print "<tr><th class='ui-widget-header ui-state-hover' colspan=2>Par cat&eacute;gorie (Pr&eacute;vu)";

//table avec le dispatch
    $requete = "SELECT SUM(dt.total_ht) as tht, t.label
              FROM " . MAIN_DB_PREFIX . "synopsisdemandeinterv as d,
                   " . MAIN_DB_PREFIX . "synopsisdemandeintervdet as dt,
                   " . MAIN_DB_PREFIX . "synopsisfichinter_c_typeInterv as t
             WHERE d.fk_commande  IN (" . join(',', $arrGrpCom) . ") " .
            " AND t.id = dt.fk_typeinterv
               AND dt.fk_synopsisdemandeinterv = d.rowid
          GROUP BY t.id";
    $sql = $db->query($requete);
    while ($res = $db->fetch_object($sql)) {
        if ($res->tht > 0)
            print "<tr><th class='ui-widget-header ui-state-default' width=150>" . traiteStr($res->label) . "<td class='ui-widget-content' align=right>" . price($res->tht) . ' &euro;';
    }
    print "</table>";
    print "<td valign=top>";
    print "<table width=380 cellpadding=10>";
    print "<tr><th class='ui-widget-header ui-state-hover' colspan=2>Par cat&eacute;gorie (R&eacute;alis&eacute;)";

//table avec le dispatch
    $requete = "SELECT SUM(dt.total_ht) as tht, t.label
              FROM " . MAIN_DB_PREFIX . "Synopsis_fichinter as d,
                  " . MAIN_DB_PREFIX . "Synopsis_fichinterdet as dt,
                  " . MAIN_DB_PREFIX . "synopsisfichinter_c_typeInterv as t
             WHERE d.fk_commande  IN (" . join(',', $arrGrpCom) . ") " .
            " AND t.id = dt.fk_typeinterv
               AND dt.`fk_fichinter` = d.rowid
          GROUP BY dt.fk_typeinterv";
    $sql = $db->query($requete);
//die($requete);
//print $requete;
    while ($res = $db->fetch_object($sql)) {
        if ($res->tht > 0)
            print "<tr><th class='ui-widget-header ui-state-default' width=150>" . traiteStr($res->label) . "<td class='ui-widget-content' align=right>" . price($res->tht) . ' &euro;';
    }
    print "</table>";

//Lignes
//$com->fetch_group_lines();
//$lines = $com->lines;
    $lines = array();
    $sql = $db->query("SELECT `fk_product`, `total_ht` FROM `" . MAIN_DB_PREFIX . "commandedet` WHERE fk_product > 0 AND `fk_commande` IN (" . implode(",", $com->listIdGroupMember()) . ")");
    $i = 0;
    while ($val = $db->fetch_object($sql)) {
        $lines[$i] = $val;
        $i++;
    }

//Categorie
    $requete = "SELECT l.catId, c.label FROM " . MAIN_DB_PREFIX . "Synopsis_PrepaCom_c_cat_listContent as l, " . MAIN_DB_PREFIX . "categorie as c WHERE l.catId = c.rowid";
    $sqlContent = $db->query($requete);
    $iter = 0;
    $replacePosition = 0;
    require_once(DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php');
    while ($resContent = $db->fetch_object($sqlContent)) {
        $return = "";
//    $cat = new Categorie($db, $resContent->catId);
//    $cat->fetch($resContent->catId);
//    $catsTmp = $cat->get_filles();
//    if (!sizeof($catsTmp) > 0) {
//        continue;
//    }
        if ($iter == 0)
            $return .="<tr><td valign='top' colspan=1>";
        else
            $return .="    <td valign='top' colspan=2>";

        $return .="<table width=380 cellpadding=10>";
        $return .="<tr><th class='ui-widget-header ui-state-hover' colspan=4>Vendu par " . strtolower($resContent->label);
        $arrCat = array();
        $arrPos = array();
        $arrLabelSort = array();
        $ArrCat = array();

//        $sql = $db->query("SELECT `fk_product`, `total_ht` FROM `" . MAIN_DB_PREFIX . "commandedet` WHERE fk_product > 0 AND `fk_commande` IN (" . implode(",", $com->listIdGroupMember()) . ")");
        foreach ($lines as $key => $val) {
//    while ($val = $db->fetch_object($sql)) {
            if ($val->fk_product > 0) {
                $replacePosition++;
                if (!isset($ArrCat[$val->fk_product])) {
                    $ArrCat[$val->fk_product] = array();
                    $requete = "SELECT ct.fk_categorie, c.label, c.rowid, '" . $replacePosition . "' as position, fk_parent
                      FROM " . MAIN_DB_PREFIX . "categorie_product as ct
                        LEFT JOIN " . MAIN_DB_PREFIX . "categorie as c ON ct.fk_categorie = c.rowid
                     WHERE  ct.fk_product = " . $val->fk_product . "
                         AND c.rowid IN (SELECT catId FROM " . MAIN_DB_PREFIX . "Synopsis_PrepaCom_c_cat_listContent)";
                    $sql = $db->query($requete);
                    if (!$sql)
                        die("Erreur SQL " . $requete);
                    while ($res1 = $db->fetch_object($sql)) {
                        $ArrCat[$val->fk_product][] = $res1;
                    }
                }


//            $return .=$requete."<br/>";
                if ($sql) {
//                while ($res1 = $db->fetch_object($sql)) {
                    foreach ($ArrCat[$val->fk_product] as $res1) {
                        $result = asPosition($res1->label);
                        if ($result) {
                            $res1->label = $result[1];
                            $res1->position = $result[0];
                        }



//                    $requete = "SELECT fk_parent as rowid,
//                                       ".MAIN_DB_PREFIX."categorie.label
//                                  FROM ".MAIN_DB_PREFIX."categorie
//                                 WHERE rowid = " . $res1->fk_categorie . "
//                              ORDER BY label ASC";
//                    $sql2 = $db->query($requete);
////                    $return .=$requete."<br/>";
//                    while ($res2 = $db->fetch_object($sql2)) {
////                    die($requete);
                        if ($res1->fk_parent == $resContent->catId) {
                            $arrLabelSort[$res1->rowid] = $res1->label;
                            if (isset($arrCat[$res1->rowid]))
                                $arrCat[$res1->rowid] += $val->total_ht;
                            else
                                $arrCat[$res1->rowid] = $val->total_ht;
                            $arrPos[$res1->rowid] = $res1->rowid;
                        }
//                    }
                        //var_dump($arrLabelSort);
                    }
                }
            }
        }
        array_multisort($arrPos);
        foreach ($arrPos as $key => $val) {
            //NOTA ATTTENTION La position doit etre mise !!!
            $return .="<tr><th colspan=2 class='ui-wdiget-header ui-state-default'>" . traiteStr($arrLabelSort[$val]) . '<td class="ui-widget-content" colspan=2 align=right >' . price($arrCat[$val]) . '&euro;';
        }

        $return .="</table>";


        $iter++;
        if ($iter == 2)
            $iter = 0;
        
        if(count($arrPos) > 0)
            echo $return;
    }


    echo "</table>";

    echo "</div>";


}
    function traiteStr($str) {
        return $str;
    }
?>
