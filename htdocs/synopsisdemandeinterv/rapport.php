<?php

/* Copyright (C) 2003 Xavier DUTOIT        <doli@sydesy.com>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id: rapport.php,v 1.12 2007/06/22 08:44:46 hregis Exp $
 * $Source: /cvsroot/dolibarr/dolibarr/htdocs/synopsisdemandeinterv/rapport.php,v $
 *
 */
/*
 * BIMP-ERP by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.1
 * Create on : 4-1-2009
 *
 * Infos on http://www.finapro.fr
 *
 */
require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT . "/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/class/html.form.class.php");
require_once(DOL_DOCUMENT_ROOT . "/synopsisdemandeinterv/class/synopsisdemandeinterv.class.php");


if (! $user->rights->synopsisdemandeinterv->rapportTous) accessforbidden();


$html = new Form($db);
if ($user->societe_id > 0) {
    $socidUser = $user->societe_id;
}
$socid = 0;
if (isset($_SESSION['socid'])) {
    $socid = $_SESSION['socid'];
}
if (isset($_REQUEST['socid'])) {
    $socid = $_REQUEST['socid'];
    $_SESSION['socid'] = $socid;
}
llxHeader();

/*
 * Liste
 *
 */

$filterUser = $user->id;
if ($user->rights->synopsisdemandeinterv->rapportTous) {
    $filterUser = false;

    if (isset($_SESSION['filterUser'])) {
        $filterUser = $_SESSION['filterUser'];
    }

    if (isset($_REQUEST['filterUser']) && $_REQUEST['filterUser'] == -1)
        $filterUser = false;
    elseif (isset($_REQUEST['filterUser'])) {

        $filterUser = $_REQUEST['filterUser'];
    }
    $_SESSION['filterUser'] = $filterUser;
}

//if ($sortorder == "") {
    $sortorder = "ASC";
//}
//if ($sortfield == "") {
    $sortfield = "f.datei";
//}

//if ($page == -1) {
    $page = 0;
//}
//
//$limit = $conf->liste_limit;
//$offset = $limit * $page;
//$pageprev = $page - 1;
//$pagenext = $page + 1;

$sql = "SELECT s.nom,
               s.rowid as socid,
               f.description,
               f.ref,
               f.datei as dp,
               f.rowid as fichid,
               f.fk_statut,
               f.duree";
$sql .= " FROM " . MAIN_DB_PREFIX . "societe as s,
               " . MAIN_DB_PREFIX . "synopsisdemandeinterv as f ";
$sql .= "WHERE f.fk_soc = s.rowid";

if ($filterUser) {
    $sql .= " AND (fk_user_prisencharge = " . $filterUser . " OR fk_user_target =" . $filterUser . ")";
}


$MM = isset($_REQUEST['MM'])? $_REQUEST['MM'] : '';
$YY = isset($_REQUEST['YY'])? $_REQUEST['YY'] : '';

if ($socid > 0) {
    $sql .= " AND s.rowid = " . $socid;
}

if (empty($MM))
    $MM = utf8_decode(strftime("%m", time()));
if (empty($YY))
    $YY = strftime("%Y", time());;


$start = "$YY-$MM-01 00:00:00";
if ($MM == 12) {
    $y = $YY + 1;
    $end = "$y-01-01 00:00:00";
} else {
    $m = $MM + 1;
    $end = "$YY-$m-01 00:00:00";
}
$sql .= " AND datei >= '$start' AND datei < '$end'";
if ($socid > 0) {
    $sql .= " AND fk_soc = " . $socid;
}

if(isset($_REQUEST['statut']) && $_REQUEST['statut']> 0)
    $sql .= " AND f.fk_statut = ".($_REQUEST['statut']-1);


$sql .= " ORDER BY $sortfield $sortorder ";

//$requete = "SELECT DISTINCT s.nom,s.rowid as socid ";
//$requete .= " FROM " . MAIN_DB_PREFIX . "societe as s, " . MAIN_DB_PREFIX . "synopsisdemandeinterv as f ";
//$requete .= " WHERE (f.fk_soc = s.rowid";
//
//$requete .= " AND datei >= '$start' AND datei < '$end'";
//if ($filterUser) {
//    $requete .= " AND (fk_user_prisencharge = " . $filterUser . " OR fk_user_target =" . $filterUser . ")";
//}
//$requete .= " ) OR s.rowid = '".$socid."'";
//
//$requete .= " ORDER BY $sortfield $sortorder ";
////die($requete);
//$sqlpre1 = $db->query($requete);
//$selSoc = "<select name='socid'>";
//$selSoc .= "<option value=''>S&eacute;lectioner -></option>";
//while ($respre1 = $db->fetch_object($sqlpre1)) {
//    if ($socid > 0 && $socid == $respre1->socid) {
//        $selSoc .= "<option SELECTED value='" . $respre1->socid . "'>" . $respre1->nom . "</option>";
//    } else {
//        $selSoc .= "<option value='" . $respre1->socid . "'>" . $respre1->nom . "</option>";
//    }
//}
//$selSoc .= "</select>";
$form = new Form($db);
$selSoc .= $form->select_company($socid, 'socid');

//TODO si selection société filtrer
//print $sql;
$resql = $db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql);
    $title = "Rapport d'activit&eacute; de " . strftime("%B %Y", strtotime($start));
    print_barre_liste($title, $page, "rapport.php", "&socid=$socid", $sortfield, $sortorder, '', $num);
    print "<br/><br/>";
    echo "<div style='float: right;  margin-top:-18px; margin-right: 20%; padding: 10px;' class='noprint ui-widget-content ui-state-default'>";
    echo "\n<form action='rapport.php'>";
    echo "<input type='hidden' name='socid' value='$socid'>";
    $prevMM = $MM - 1;
    $prevYY = $YY;
    if ($prevMM < 1) {
        $prevMM = 12;
        $prevYY--;
    }
    $nxtMM = $MM + 1;
    $nxtYY = $YY;
    if ($nxtMM > 12) {
        $nxtMM = 1;
        $nxtYY++;
    }
    echo "<table><tr><td><a href='rapport.php?MM=" . $prevMM . "&YY=" . $prevYY . "&g=Afficher'><span class='ui-icon ui-icon-circle-triangle-w' ></span></a>";
    echo '           <td>' . $langs->trans("Month") . " <input style='text-align:center' name='MM' size='2' value='$MM'>";
    echo " Ann&eacute;e <input size='4' name='YY' style='text-align:center' value='$YY'></td>";
    echo "<td><a href='rapport.php?MM=" . $nxtMM . "&YY=" . $nxtYY . "&g=Afficher'><span class='ui-icon ui-icon-circle-triangle-e' ></span></a>";
    echo "<td>&nbsp;";
    echo "<td align=center>Soci&eacute;t&eacute;";

    print $selSoc;
    if ($user->rights->synopsisdemandeinterv->rapportTous) {
        echo "<td align=center>Intervenant";
        $html->select_users(($filterUser > 0 ? $filterUser : 0), 'filterUser', 1, '', 0, 1);
    }
    $tabStat = array("Sélectionner", "Brouillon", "Validée", "En cours", "Clôturée");
    echo "<td> Statut <select name='statut'>";
    foreach($tabStat as $id => $stat){
            echo "<option value='".$id."'";
        if(isset($_REQUEST["statut"]) && $_REQUEST["statut"] == $id)
            echo " selected='selected'";
        echo ">".$stat."</option>";
    }
    echo "</select>";
    echo "<tr><td colspan=6 align=center><input class='button ui-state-default' style='padding: 3px;' type='submit' name='g' value='Afficher le rapport'></td>";
    echo "</table><form>";
    echo "</div><br/><br/><br/>";
    $i = 0;
    print '<table class="noborder" width="100%" cellspacing="0" cellpadding="3">';
    $var = true;
    $DureeTotal = 0;
    $totTReel = 0;
    $totReel = 0;
    $totTPrev = 0;
    $totPrev = 0;
    $totVendu = 0;
    $htmlTab = '';
    while ($objp = $db->fetch_object($resql)) {

        $var = !$var;
        $htmlTab .= "<tr $bc[$var]>";
        $di = new Synopsisdemandeinterv($db);
        $di->fetch($objp->fichid);


        $htmlTab .= "<td>" . $di->getNomUrl(1) . "</td>\n";

        if (empty($socid)) {
            if (!empty($MM))
                $filter = "&MM=$MM&YY=$YY";
            $tmpSoc = new Societe($db);
            $tmpSoc->fetch($objp->socid);
            $htmlTab .= '<td align="center"><a href="rapport.php?socid=' . $objp->socid . $filter . '"><img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/filter.png" border="0"></a>&nbsp;';
            $htmlTab .= $tmpSoc->getNomUrl(1) . "</td>\n";
        }
        $htmlTab .= '<td align="center">' . nl2br($objp->description) . '</td>';
        $htmlTab .= '<td align="center">' . strftime("%d %B %Y", strtotime($objp->dp)). "</td>\n";
        $durStr = convDur($objp->duree);
        $htmlTab .= '<td align="center">' . ($durStr['days']['abs'] > 0 ? $durStr['days']['abs'] . 'j ' : "") . $durStr['hours']['rel'] . 'h ' . $durStr['minutes']['rel'] . 'm</td>';








        $synopsisdemandeinterv = new Synopsisdemandeinterv($db);
        $synopsisdemandeinterv->id = $objp->fichid;
        $synopsisdemandeinterv->fetch($objp->fichid);
        if ($synopsisdemandeinterv->fk_commande) {
            require_once(DOL_DOCUMENT_ROOT . "/commande/class/commande.class.php");
            $com = new Commande($db);
            $com->fetch($synopsisdemandeinterv->fk_commande);
//        $com->fetch_group_lines(0, 0, 0, 0, 1);//Groupe commande
            if($com->id > 0){
            $arrGrpCom = array($com->id => $com->id);
//        $arrGrp = $com->listGroupMember(true);
//        foreach ($arrGrp as $key => $commandeMember) {
//            $arrGrpCom[$commandeMember->id] = $commandeMember->id;
//        }
//Vendu en euro
            $requete = "SELECT SUM(total_ht) as tht
              FROM " . MAIN_DB_PREFIX . "commandedet as commdet,
                   " . MAIN_DB_PREFIX . "product as prod,
                   " . MAIN_DB_PREFIX . "categorie_product as catprod,
                   " . MAIN_DB_PREFIX . "categorie as cat
             WHERE prod.rowid = commdet.fk_product
               AND cat.rowid = catprod.fk_categorie
               AND catprod.fk_product = prod.rowid
               AND cat.rowid IN (SELECT catId FROM " . MAIN_DB_PREFIX . "Synopsis_PrepaCom_c_cat_total)
               AND commdet.fk_commande IN (" . join(',', $arrGrpCom) . ")  ";
//        die($requete);

            $sql = $db->query($requete);
            $res = $db->fetch_object($sql);
            if (!$res)
                die($requete . "|" . $objp->fichid);
            $vendu = $res->tht;
            $totVendu += $vendu;


//Prevu en euro et en temps
            $requete = "SELECT SUM(det.duree) as durTot ,
                   SUM(det.total_ht) as totHT
              FROM " . MAIN_DB_PREFIX . "synopsisdemandeinterv di,
                   " . MAIN_DB_PREFIX . "synopsisdemandeintervdet as det,
                   " . MAIN_DB_PREFIX . "synopsisfichinter_c_typeInterv as t
             WHERE t.id=det.fk_typeinterv
               AND det.fk_synopsisdemandeinterv = di.rowid
               AND t.inTotalRecap=1
               AND fk_commande  IN (" . join(',', $arrGrpCom) . ") ";
            $sql = $db->query($requete);
            $res = $db->fetch_object($sql);
            $prevuEuro = $res->totHT;
            $prevuTemp = $res->durTot;
            $arr1 = convDur($res->durTot);
            $totPrev += $res->totHT;
            $totTPrev += $res->durTot;
//Realise en euros et en temps
            $requete = "SELECT SUM(det.duree) as durTot ,
                   SUM(det.total_ht) as totHT
              FROM " . MAIN_DB_PREFIX . "synopsis_fichinter as inter,
                   " . MAIN_DB_PREFIX . "synopsis_fichinterdet as det,
                   " . MAIN_DB_PREFIX . "synopsisfichinter_c_typeInterv as t
             WHERE t.id=det.fk_typeinterv
               AND det.fk_fichinter = inter.rowid
               AND t.inTotalRecap=1
               AND inter.fk_commande  IN (" . join(',', $arrGrpCom) . ") ";
//$htmlTab .= $requete;
            $sql = $db->query($requete);
            $res = $db->fetch_object($sql);
            $realEuro = $res->totHT;
//            $realTemp = $res->durTot;
            $arr2 = convDur($res->durTot);
            $totReel += $res->totHT;
            $totTReel += $res->durTot;
        }
    }
        $htmlTab .= '<td align="center">' . price($prevuEuro) . " &euro; / " . $arr1['hours']['abs'] . "h " . ($arr1['minutes']['rel'] > 0 ? $arr1['minutes']['rel'] . "m" : "") . '</td>';
        $htmlTab .= '<td align="center">' . price($vendu) . " &euro; </td>";
        $htmlTab .= '<td align="center">' . price($realEuro) . " &euro; / " . $arr2['hours']['abs'] . "h " . ($arr2['minutes']['rel'] > 0 ? $arr2['minutes']['rel'] . "m" : "") . '</td>';
        $htmlTab .= '<td align="center">' . ($prevuEuro == 0 ? 0 : price(($prevuEuro - $realEuro) / $prevuEuro * 100)) . " %" . '</td>';
        $htmlTab .= '<td align="center">' . ($vendu == 0 ? 0 : price(($vendu - $realEuro) / $vendu * 100)) . " %" . '</td>';

        $htmlTab .=  '<td align="center">' . $di->getLibStatut(2) . ' </td>';








        $DureeTotal += $objp->duree;
        $htmlTab .= "</tr>\n";
    }
    $arr1 = convDur($totTPrev);
    $arr2 = convDur($totTReel);
    $htmlTot = "<tr class=\"liste_titre\">";
    $htmlTot .= '<td align="center">Ref.</td>';
    if (empty($socid))
        $htmlTot .= '<td align="center">Soci&eacute;t&eacute;</td>';
    $htmlTot .= '<td align="center">' . $langs->trans("Description") . '</td>';

    $htmlTot .= '<td align="center">Date</td>';
    $htmlTot .= '<td align="center">' . $langs->trans("Duration") . '</td>';
    $htmlTot .= '<td align="center">Total prevue</td>';
    $htmlTot .= '<td align="center">Total vendue</td>';
    $htmlTot .= '<td align="center">Total réalisé</td>';
    $htmlTot .= '<td align="center">Dif réalisé prévue</td>';
    $htmlTot .= '<td align="center">Dif réalisé vendue</td>';
    $htmlTot .= '<td align="center">Statut</td>';
    $htmlTot .= "</tr><tr class=\"liste_titre\"><td></td><td></td><td></td><td></td>";
    if (empty($socid))
    $htmlTot .= "<td></td>";
    $htmlTot .= '<td align="center">' . price($totPrev) . " &euro; / " . $arr1['hours']['abs'] . "h " . ($arr1['minutes']['rel'] > 0 ? $arr1['minutes']['rel'] . "m" : "") . '</td>';
    $htmlTot .= '<td align="center">' . price($totVendu) . '</td>';
    $htmlTot .= '<td align="center">' . price($totReel) . " &euro; / " . $arr2['hours']['abs'] . "h " . ($arr2['minutes']['rel'] > 0 ? $arr2['minutes']['rel'] . "m" : "") . '</td>';
    $htmlTot .= '<td align="center">' . ($totPrev == 0 ? 0 : price(($totPrev - $totReel) / $totPrev * 100)) . " %" . '</td>';
    $htmlTot .= '<td align="center">' . ($totVendu == 0 ? 0 : price(($totVendu - $totReel) / $totVendu * 100)) . " %" . '</td>';
    $htmlTot .= '<td align="center"></td>';
    $htmlTot .= '</tr>';


    print $htmlTot . $htmlTab . $htmlTot;

    print "</table>";
    $db->free();
    $durStr = convDur($DureeTotal);
    print "<br />" . $langs->trans("Total") . ": " . ($durStr['days']['abs'] > 0 ? $durStr['days']['abs'] . 'j ' : "") . $durStr['hours']['rel'] . 'h ' . $durStr['minutes']['rel'] . 'm';
} else {
    dol_print_error($db);
}
$db->close();

llxFooter("<em>Derni&egrave;re modification : 2007/06/22 08:44:46 $ r&eacute;vision  1.12 $</em>");
?>
