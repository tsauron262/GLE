<?php

/* Copyright (C) 2003 Xavier DUTOIT        <doli@sydesy.com>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * $Source: /cvsroot/dolibarr/dolibarr/htdocs/synopsisfichinter/rapport.php,v $
 *
 */
/*
 * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.1
 * Create on : 4-1-2009
 *
 * Infos on http://www.synopsis-erp.com
 *
 */
require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT . "/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/class/html.form.class.php");
require_once(DOL_DOCUMENT_ROOT . "/synopsisfichinter/class/synopsisfichinter.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/date.lib.php");


$enJour = false;


if (!$user->rights->synopsisficheinter->lire)
    accessforbidden();


$tabIdFi = array();


$html = new Form($db);
if ($user->societe_id > 0) {
    $socidUser = $user->societe_id;
}

$socid = isset($_REQUEST['socid']) ? $_REQUEST['socid'] : 0;
llxHeader();

/*
 * Liste
 *
 */

//TODO filtre par utilisateur + droit de voir les fiche des autres

$filterUser = $user->id;
if ($user->rights->synopsisficheinter->rapportTous) {
    $filterUser = false;
} else
    $_REQUEST['filterUser'] = $filterUser;


if (isset($_REQUEST['filterUser']) && $_REQUEST['filterUser'] > 0) {
    $filterUser = $_REQUEST['filterUser'];
}

//if ($sortorder == "")
//{
$sortorder = "DESC";
//}
//if ($sortfield == "")
//{
$sortfield = "f.datei";
//}
//if ($page == -1) { 
$page = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 0);
//}
//die($conf->liste_limit);
$limit = $conf->liste_limit;
$offset = $limit * ($page - 0);
$pageprev = $page - 1;
$pagenext = $page + 1;

$sql1 = "SELECT s.nom,
            s.rowid as socid,
            f.fk_commande,
            f.fk_contrat,
            f.description,
            f.ref,
            f.datei as dp,
            f.rowid as fichid,
            f.fk_statut,
            f.duree,
	    f.total_ht";
$sql2 = "SELECT SUM(f.duree) as duree,
	    SUM(f.total_ht) as total,
            COUNT(f.rowid) as nbLignes";
$sql = " FROM " . MAIN_DB_PREFIX . "societe as s,
            " . MAIN_DB_PREFIX . "Synopsis_fichinter as f ";
$sql .= " WHERE f.fk_soc = s.rowid";

if ($filterUser) {
    $sql .= " AND fk_user_author = " . $filterUser;
}

if (isset($_GET['typeInter']) && $_GET['typeInter'] > 0)
    $sql .= " AND f.natureInter = " . $_GET['typeInter'];

if (isset($_GET['assocContrat']))
    $sql .= " AND fk_contrat > 0";

if (isset($_GET['statutInter'])) {
    if ($_GET['statutInter'] == 1)
        $sql .= " AND fk_statut = 0";
    elseif ($_GET['statutInter'] == 2)
        $sql .= " AND fk_statut = 1";
}

$MM = isset($_REQUEST['MM']) ? $_REQUEST['MM'] : '';
$YY = isset($_REQUEST['YY']) ? $_REQUEST['YY'] : '';

if ($socid > 0) {
    $sql .= " AND s.rowid = " . $socid;
}

//if ($typeInter > 0)
//{
//  $sql .= " AND f. = " . $socid;
//}

if (empty($MM))
    $MM = utf8_decode(strftime("%m", time()));
if (empty($YY))
    $YY = strftime("%Y", time());;
//echo "<div class='noprint'>";
//echo "\n<form action='rapport.php'>";
//echo "<input type='hidden' name='socid' value='$socid'>";
//echo $langs->trans("Month")." <input name='MM' size='2' value='$MM'>";
//echo " Ann&eacute;e <input size='4' name='YY' value='$YY'>";
//echo "<input type='submit' name='g' value='Gen&eacute;rer le rapport'>";
//echo "<form>";
//echo "</div>";

$start = "$YY-$MM-01 00:00:00";
if ($MM == 12) {
    $y = $YY + 1;
    $end = "$y-01-01 00:00:00";
} else {
    $m = $MM + 1;
    $end = "$YY-$m-01 00:00:00";
}
if (isset($_GET['dateDeb']) && isset($_GET['dateFin']) && $_GET['dateDeb'] != '' && $_GET['dateFin'] != '') {
    $tabStart = explode("/", $_GET['dateDeb']);
    $start = $tabStart[2] . "-" . $tabStart[1] . '-' . $tabStart[0];
    $tabFin = explode("/", $_GET['dateFin']);
    $end = $tabFin[2] . "-" . $tabFin[1] . '-' . $tabFin[0];
    $sql .= " AND datei >= '$start' AND datei <= '$end'";
}

if ($socid > 0) {
    $sql .= " AND fk_soc = " . $socid;
}

$sql2 = $sql2 . $sql;
$sql1 = $sql1 . $sql . " ORDER BY $sortfield $sortorder  LIMIT $offset, $limit ";

//$requete = "SELECT DISTINCT s.nom,s.rowid as socid ";
//$requete .= " FROM " . MAIN_DB_PREFIX . "societe as s, " . MAIN_DB_PREFIX . "fichinter as f ";
//$requete .= " WHERE f.fk_soc = s.rowid";
//
////    $requete .= " AND datei >= '$start' AND datei < '$end'" ;
//if ($filterUser) {
//    $requete .= " AND fk_user_author = " . $filterUser;
//}
//
//
//$requete .= " ORDER BY $sortfield $sortorder ";
//
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
$selSoc .= $form->select_thirdparty($socid, 'socid');


$req = "SELECT * FROM `" . MAIN_DB_PREFIX . "synopsisfichinter_c_typeInterv` WHERE `active` = 1";
$sqlpre11 = $db->query($req);
$selectHtml2 = '<select name="typeInter">';
global $tabSelectNatureIntrv, $total_ht;
foreach ($tabSelectNatureIntrv as $i => $option) {
    if (isset($_GET['typeInter']) && $i == $_GET['typeInter']) {
        $selectHtml2 .= "<OPTION SELECTED value='" . $i . "'>" . $option . "</OPTION>";
    } else {
        $selectHtml2 .= "<OPTION value='" . $i . "'>" . $option . "</OPTION>";
    }
}
//$selectHtml2 .= "<option value=''>S&eacute;lectioner -></option>";
//while ($respre11 = $db->fetch_object($sqlpre11)) {
//    $i = $respre11->id;
//    $option = $respre11->label;
//    if (isset($_GET['typeInter']) && $i == $_GET['typeInter']) {
//        $selectHtml2 .= "<OPTION SELECTED value='" . $i . "'>" . $option . "</OPTION>";
//    } else {
//        $selectHtml2 .= "<OPTION value='" . $i . "'>" . $option . "</OPTION>";
//    }
//}
$selectHtml2 .= "</select>";

//TODO si selection société filtrer
//print $sql1;
$resql = $db->query($sql1);
$resqlCount = $db->query($sql2);
if ($resql) {
    $resultCount = $db->fetch_object($resqlCount); // / $limit;
    $num = $resultCount->nbLignes;
//    $num = 10;
    $title = "Rapport d'activit&eacute; de " . strftime("%B %Y", strtotime($start));
    $param = "";
    foreach ($_GET as $valT => $valT2)
        if ($valT != "page")
            $param .= "&" . $valT . "=" . $valT2;
    print_barre_liste($title, $page, "rapport.php", "&" . $param, $sortfield, $sortorder, '', $offset + $limit + 1, $num);
    print "<br/><br/>";

    echo "<div style='float: left;  margin-top:-18px; padding: 10px;' class='noprint ui-widget-content ui-state-default'>";
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
        $nextYY++;
    }

    echo "<table><tr>";
    /* echo "<td><a href='rapport.php?MM=". $prevMM ."&YY=".$prevYY."&g=Afficher'><span class='ui-icon ui-icon-circle-triangle-w' ></span></a>";
      echo '           <td>'.$langs->trans("Month")." <input style='text-align:center' name='MM' size='2' value='$MM'>";
      echo " Ann&eacute;e <input size='4' name='YY' style='text-align:center' value='$YY'></td>";
      echo "<td><a href='rapport.php?MM=". $nxtMM ."&YY=".$nxtYY."&g=Afficher'><span class='ui-icon ui-icon-circle-triangle-e' ></span></a>";
      echo "<td>&nbsp;"; */

    print "<td>Date : <br/><input type='text' name='dateDeb' class='datePicker' value='" . (isset($_GET['dateDeb']) ? $_GET['dateDeb'] : '') . "'><br/><input type='text' name='dateFin' class='datePicker'  value='" . (isset($_GET['dateFin']) ? $_GET['dateFin'] : '') . "'>";


    echo "<td align=center>Type";
    print $selectHtml2;


    echo "<td align=center>Soci&eacute;t&eacute;    ";
    print $selSoc;
    if ($user->rights->synopsisficheinter->rapportTous) {
        echo "<td align=center>Intervenant";
//        $html->select_users($_REQUEST['filterUser'], 'filterUser', 1, '', 0, 1);
        print select_dolusersInGroup($html, 3, $_REQUEST['filterUser'], 'filterUser', 1, '', 0, true);
        $filterUser = false;
    }


    $selectHtml2 = '<select name="statutInter">';
    $tabSelect = array("Choix", "Non valider", "Valider");
    foreach ($tabSelect as $i => $option) {
        if (isset($_GET['typeInter']) && $i == $_GET['statutInter']) {
            $selectHtml2 .= "<OPTION SELECTED value='" . $i . "'>" . utf8_decode($option) . "</OPTION>";
        } else {
            $selectHtml2 .= "<OPTION value='" . $i . "'>" . utf8_decode($option) . "</OPTION>";
        }
    }
    $selectHtml2 .= "</select>";

    echo "<td align=center>Statut FI";
    print $selectHtml2;


    print '<td align="center">Associ&eacute; a un contrat<input type="checkbox" name="assocContrat" ' . (isset($_GET['assocContrat']) ? 'checked="checked"' : '') . '/>';

    echo "<tr><td colspan=6 align=center><input class='button ui-state-default' style='padding: 3px;' type='submit' name='g' value='Afficher le rapport'></td>";
    echo "</table><form>";
    echo "</div><br/><br/><br/>";

    $i = 0;
    print '<table class="noborder" width="100%" cellspacing="0" cellpadding="3">';
    print "<tr class=\"liste_titre\">";
    print '<td>Ref</td>';
    if (empty($socid))
        print '<td>Soci&eacute;t&eacute;</td>';
    print '<td align="center">' . $langs->trans("Commande / Contrat") . '</td>';
    print '<td align="center">' . $langs->trans("Description") . '</td>';

    print '<td align="center">Date</td>';
    print '<td align="center">' . $langs->trans("Duration") . '</td>';
    print '<td align="center">Prix HT</td>';
    print '<td align="center">Statut</td>';
    print "</tr>\n";
    $var = true;
    $DureeTotal = 0;
    $total_ht = 0;

    require_once(DOL_DOCUMENT_ROOT . "/commande/class/commande.class.php");
    require_once(DOL_DOCUMENT_ROOT . "/contrat/class/contrat.class.php");
    $commande = new Commande($db);
    $contrat = new Contrat($db);
    while ($objp = $db->fetch_object($resql)) {
        $var = !$var;
        print "<tr $bc[$var]>";
        $di = new Synopsisfichinter($db);
        $di->fetch($objp->fichid);

        $total_ht += $objp->total_ht;
        $tabIdFi[] = $di->id;
        print "<td>" . $di->getNomUrl(1) . "</td>\n";

        if (empty($socid)) {
            if (!empty($MM))
                $filter = "&MM=$MM&YY=$YY";
            $tmpSoc = new Societe($db);
            $tmpSoc->fetch($objp->socid);
            print '<td><a href="rapport.php?socid=' . $objp->socid . $filter . '"><img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/filter.png" border="0"></a>&nbsp;';
            print $tmpSoc->getNomUrl(1) . "</TD>\n";
        }


        print "<td>";
        if ($objp->fk_commande) {
            $commande->fetch($objp->fk_commande);
            print $commande->getNomUrl(1);
        }
        if ($objp->fk_contrat) {
            $contrat->fetch($objp->fk_contrat);
            print $contrat->getNomUrl(1);
        }
        print "</td>";



        print '<td>' . nl2br($objp->description) . '</td>';
        print "<td>" . dol_print_date($objp->dp) . "</td>\n";
        $durStr = convDur($objp->duree);
        if ($enJour)
            print '<td align="center">' . ($durStr['days']['abs'] > 0 ? $durStr['days']['abs'] . 'j ' : "") . $durStr['hours']['rel'] . 'h ' . $durStr['minutes']['rel'] . 'm</td>';
        else
            print '<td align="center">' . ($durStr['days']['abs'] > 0 ? $durStr['days']['abs'] * 24 + $durStr['hours']['rel'] : $durStr['hours']['rel']) . 'h ' . $durStr['minutes']['rel'] . 'm</td>';
        print '<td align="center">' . $objp->total_ht . ' &euro;</td>';
        print '<td align="center">' . $di->getLibStatut(2) . ' </td>';

        $DureeTotal += $objp->duree;
        print "</tr>\n";
    }
    print "</table>";
//    $db->free();
//    $durStr = convDur($DureeTotal);
    $durStr = convDur($resultCount->duree);
    $total_ht = $resultCount->total;
    if ($enJour)
        print "<div class='clear'></div>" . $langs->trans("Total") . ": " . ($durStr['days']['abs'] > 0 ? $durStr['days']['abs'] . 'j ' : "") . $durStr['hours']['rel'] . 'h ' . $durStr['minutes']['rel'] . 'm' . "  |  " . $total_ht . " &euro;";
    else
        print "<div class='clear'></div>" . $langs->trans("Total") . ": " . ($durStr['days']['abs'] > 0 ? $durStr['days']['abs'] * 24 + $durStr['hours']['rel'] : $durStr['hours']['rel']) . 'h ' . $durStr['minutes']['rel'] . 'm' . "  |  " . $total_ht . " &euro;";
//  print "<br />".$langs->trans("Total")." $DureeTotal jour[s]";
} else {
    dol_print_error($db);
}
print <<<EOF
	<script>
	jQuery(document).ready(function(){
		jQuery.datepicker.setDefaults(jQuery.extend({showMonthAfterYear: false,
			        dateFormat: 'dd/mm/yy',
			        changeMonth: true,
			        changeYear: true,
			        showButtonPanel: true,
			        buttonImage: 'cal.png',
			        buttonImageOnly: true,
			        showTime: false,
			        duration: '',
			        constrainInput: false,}, jQuery.datepicker.regional['fr']));
		jQuery('.datePicker').datepicker();
		jQuery('.dateTimePicker').datepicker({showTime:true});
	});
	</script>
EOF;


$result = $db->query("SELECT f.rowid " . $sql);
$tabIdFi = array();
while ($ligne = $db->fetch_object($result)) {
    $tabIdFi[$ligne->rowid] = $ligne->rowid;
}

if (count($tabIdFi) > 0) {
    $tabResult = afficheParType($tabIdFi);

    $tabIdErreur = testFi($tabIdFi, $tabResult);
//echo "<pre>";   
//print_r($tabIdErreur);

    if (count($tabIdErreur) > 0) {

        $newTabIdFi = array();
        foreach ($tabIdFi as $val) {
            if (!isset($tabIdErreur[$val]))
                $newTabIdFi[$val] = $val;
        }

        echo "<br/><br/>Ce qui donne sans comptabiliser ces interventions : <br/><br/>";

        if (count($newTabIdFi) > 0) {
            $tabResult = afficheParType($newTabIdFi, true);
            $tabIdErreur = testFi($newTabIdFi, $tabResult, false);
        }
    }
}

$db->close();

llxFooter("<em>Derni&egrave;re modification: 2007/06/22 08:44:46 $ r&eacute;vision: 1.12 $</em>");

function afficheParType($tabIdFi, $secondFois = false) {
    global $db, $additionP, $user;
    $requeteType2 = "SELECT SUM(fdet.`duree`) as dureeFI, SUM(fdet.total_ht) as prix, fk_typeinterv as ty  FROM " . MAIN_DB_PREFIX . "Synopsis_fichinterdet fdet WHERE fdet.fk_fichinter IN (" . implode(",", $tabIdFi) . ") GROUP BY  `fk_typeinterv` ;";
    $result2 = $db->query($requeteType2);
    $tabResult = array();
    while ($ligne = $db->fetch_object($result2)) {
        $tabResult[$ligne->ty][0] = $ligne->dureeFI;
        $tabResult[$ligne->ty][2] = $ligne->prix;
    }


    $tabDi = getElementElement("DI", "FI", null, $tabIdFi);
    foreach ($tabDi as $tabT)
        $tabIdDi[] = $tabT['s'];

//    print_r($tabIdDi);die;


    $requeteType3 = "SELECT SUM(ddet.`duree`) as dureeDI, SUM(ddet.total_ht) as prix, fk_typeinterv as ty  FROM " . MAIN_DB_PREFIX . "synopsisdemandeintervdet ddet WHERE ddet.fk_synopsisdemandeinterv IN (" . implode(",", $tabIdDi) . ") GROUP BY  `fk_typeinterv` ;";




    $result3 = $db->query($requeteType3);
    while ($ligne = $db->fetch_object($result3)) {
        $tabResult[$ligne->ty][1] = $ligne->dureeDI;
        $tabResult[$ligne->ty][3] = $ligne->prix;
    }

//
//    $requeteType3 = "SELECT SUM(ddet3.total_ht) as prixT, ddet3.fk_typeinterv as ty  FROM " . MAIN_DB_PREFIX . "synopsisdemandeintervdet ddet3 WHERE rowid IN (SELECT distinct(ddet2.rowid) FROM " . MAIN_DB_PREFIX . "synopsisdemandeintervdet ddet, " . MAIN_DB_PREFIX . "synopsisdemandeintervdet ddet2 WHERE (ddet2.fk_contratdet = ddet.fk_contratdet || ddet2.fk_commandedet = ddet.fk_commandedet)"
//            . " AND ddet.fk_synopsisdemandeinterv IN (" . implode(",", $tabIdDi) . ")) "
////            . " AND (ddet2.fk_contratdet = ddet.fk_contratdet) "
//            . " GROUP BY  ddet3.fk_typeinterv ;";
//    
//    
//    $requeteType3 = "SELECT SUM(ddet3.total_ht) as prixT, ddet3.fk_typeinterv as ty  FROM " . MAIN_DB_PREFIX . "synopsisdemandeintervdet ddet, " . MAIN_DB_PREFIX . "synopsisdemandeintervdet ddet3 WHERE (ddet3.fk_contratdet = ddet.fk_contratdet || ddet3.fk_commandedet = ddet.fk_commandedet)"
//            . " AND ddet.fk_synopsisdemandeinterv IN (" . implode(",", $tabIdDi) . ") "
////            . " AND (ddet2.fk_contratdet = ddet.fk_contratdet) "
//            . " GROUP BY  ddet3.fk_typeinterv ;";
//    
//    
//    $requeteType3 = "SELECT SUM(ddet3.total_ht) as prixT, ddet3.fk_typeinterv as ty  FROM " . MAIN_DB_PREFIX . "synopsisdemandeintervdet ddet3 WHERE "
//            . "fk_contratdet IN (SELECT distinct(ddet.fk_contratdet) FROM " . MAIN_DB_PREFIX . "synopsisdemandeintervdet ddet WHERE "
//            . " ddet.fk_synopsisdemandeinterv IN (" . implode(",", $tabIdDi) . ")) "
//           . " || fk_commandedet IN (SELECT distinct(ddet.fk_commandedet) FROM " . MAIN_DB_PREFIX . "synopsisdemandeintervdet ddet WHERE "
//            . " ddet.fk_synopsisdemandeinterv IN (" . implode(",", $tabIdDi) . ")) "
////            . " AND (ddet2.fk_contratdet = ddet.fk_contratdet) "
//            . " GROUP BY  ddet3.fk_typeinterv ;";
//
//
//    $tabResult[$ligne->ty][10] = 0;
//    $result3 = $db->query($requeteType3);
//    while ($ligne = $db->fetch_object($result3)) {
//        $tabResult[$ligne->ty][10] += $ligne->prixT;
//    }
//
//
//    $requeteType4 = "SELECT SUM(cdet.subprice) as prix, fk_typeinterv as ty FROM " . MAIN_DB_PREFIX . "commandedet cdet, " . MAIN_DB_PREFIX . "Synopsis_fichinterdet  fdet  WHERE fdet.fk_commandedet = cdet.rowid AND fdet.fk_fichinter IN (" . implode(",", $tabIdFi) . ") AND (fk_contratdet IS NULL || fk_contratdet = 0) GROUP BY  `fk_typeinterv`;";
//    $result4 = $db->query($requeteType4);
//    while ($ligne = $db->fetch_object($result4)) {
//        if ($tabResult[$ligne->ty][3] > 0 && $tabResult[$ligne->ty][10] > 0 && $tabResult[$ligne->ty][3] < $tabResult[$ligne->ty][10])
//            $coef = $tabResult[$ligne->ty][3] / $tabResult[$ligne->ty][10];
//        else
//            $coef = 1;
////        if($coef < 1)
////            echo "|||".$coef."|";
//        $tabResult[$ligne->ty][4] = $ligne->prix * $coef;
//    }
//    $requeteType5 = "SELECT SUM(codet.subprice) as prix, fk_typeinterv as ty FROM " . MAIN_DB_PREFIX . "contratdet codet, " . MAIN_DB_PREFIX . "Synopsis_fichinterdet  fdet  WHERE fdet.fk_contratdet = codet.rowid AND fdet.fk_fichinter IN (" . implode(",", $tabIdFi) . ") GROUP BY  `fk_typeinterv`;";
//    $result5 = $db->query($requeteType5);
//    while ($ligne = $db->fetch_object($result5)) {
//        if ($tabResult[$ligne->ty][3] > 0 && $tabResult[$ligne->ty][10] > 0 && $tabResult[$ligne->ty][3] < $tabResult[$ligne->ty][10])
//            $coef = $tabResult[$ligne->ty][3] / $tabResult[$ligne->ty][10];
//        else
//            $coef = 1;
////        if($coef < 1)
////            echo "|||".$coef."|";
//        $tabResult[$ligne->ty][5] = $ligne->prix * $coef;
//    }

    if (isset($tabResult[""]))
        foreach ($tabResult[""] as $clef => $val)
            $tabResult[0][$clef] += $val;

    echo "<div style='clear:both;'>";

    /* affichage */
    $requeteType6 = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsisfichinter_c_typeInterv";
    $result6 = $db->query($requeteType6);
    $additionP = 0;
    $tabTypeNonVendue = array();
    while ($ligne = $db->fetch_object($result6)) {
        if ($tabResult[$ligne->id][2] > 0 || ($tabResult[$ligne->id][0] / 3600) > 0) {
            $tabType[$ligne->id] = $ligne->label;
            if ($ligne->isVendue == 0)
                $tabTypeNonVendue[] = $ligne->id;
        }
    }

    if(!$secondFois){
        foreach ($tabType as $idT => $valT) {
            if (in_array($idT, $tabTypeNonVendue))
                $str2 .= $valT . ", ";
            else
                $str1 .= $valT . ", ";
        }

        echo "<br/>Rappel types considérés comme vendu : <br/>";
        echo $str1 . "<br/>";

        echo "<br/>Rappel types considérés comme non-vendu : <br/>";
        echo $str2 . "<br/>";
    }

    foreach ($tabType as $idType => $labelType) {
        if (in_array($idType, $tabTypeNonVendue))
            $newId = -1001;
        else
            $newId = -1000;
        foreach ($tabResult[$idType] as $idT => $valT) {
            $tabResult[-1111][$idT] += $valT;
            $tabResult[$newId][$idT] += $valT;
        }
    }
    $tabType[-1111] = "TOTAL";
    $tabType[-1000] = "TOTAL Vendu";
    $tabType[-1001] = "TOTAL Non-Vendu";



//hotline
    $req = "SELECT COUNT(c.id) as nb, SUM(TO_SECONDS(`Date_H_Fin`) - TO_SECONDS(`Date_H_Debut`))/3600 as sum FROM `" . MAIN_DB_PREFIX . "synopsischrono_chrono_100` ct, " . MAIN_DB_PREFIX . "synopsischrono c WHERE ct.id = c.id AND Date_H_Debut > 0 AND Date_H_Fin > 0 ";

    $reqF = "";
    if ($_REQUEST['filterUser'] > 0)
        $reqF .= " AND `Tech` = " . $_REQUEST['filterUser'];
    if (isset($_GET['dateDeb']) && isset($_GET['dateFin']) && $_GET['dateDeb'] != '' && $_GET['dateFin'] != '')
        $reqF .= " AND `Date_H_Debut` < STR_TO_DATE('" . $_GET['dateFin'] . "', '%d/%m/%Y') AND `Date_H_Debut` > STR_TO_DATE('" . $_GET['dateDeb'] . "', '%d/%m/%Y')";
    $req .= $reqF;
//    die($req);
    
    
    $strStat = "<div style='clear: both;'>";
    $strStat .= "<table class='tabBorder100'><tr><td>";
    if ($user->rights->synopsisficheinter->rapportTous){
        $sql = $db->query($req . " AND (Contrat IS NULL || Contrat = 0)");
        $result = $db->fetch_object($sql);
        $strStat .= "<br/>" . $result->nb . " appels durant " . price($result->sum) . " h soit " . ($result->sum * 50) . " € hors contrat<br/>";



        $sql = $db->query($req . " AND Contrat > 0");
    //    die($req." AND Contrat is not NULL");
        $result = $db->fetch_object($sql);
        $strStat .= "<br/>" . $result->nb . " appels durant " . price($result->sum) . " h soit " . ($result->sum * 50) . " € sous contrat<br/>";



        $sql = $db->query($req . " AND Contrat is not NULL");
        $result = $db->fetch_object($sql);
        $strStat .= "<br/>" . $result->nb . " appels durant " . price($result->sum) . " h soit " . ($result->sum * 50) . " € au Total<br/>";
    }
    $strStat .= "</td><td>";
    
    $tabResult[-1001][2] += ($result->sum * 50);


    $reqTropLong = $db->query("SELECT *, TIME_TO_SEC(TIMEDIFF(Date_H_Fin, Date_H_Debut)) as duree FROM `llx_synopsischrono_chrono_100` WHERE (TIME_TO_SEC(TIMEDIFF(Date_H_Fin, Date_H_Debut)) > 7200 || TIME_TO_SEC(TIMEDIFF(Date_H_Fin, Date_H_Debut)) < 0) ".$reqF);
    $probAppel = "";
    if ($db->num_rows($reqTropLong) > 0)
        $probAppel = "Attention ".$db->num_rows($reqTropLong)." appels pouvant posé problème dont les 30 premières sont listées ci dessous.<br/><br/>";
    $i=0;
    while ($result = $db->fetch_object($reqTropLong)) {
        $i++;
        require_once (DOL_DOCUMENT_ROOT . "/synopsischrono/class/chrono.class.php");
        $chr = new Chrono($db);
        $chr->fetch($result->id);
        $probAppel .= $chr->getNomUrl(1)."   |    ".  round($result->duree / 60,2)." m";
        $probAppel .= "<br/>";
        if($i > 29)
            break;
    }



    if ($user->rights->synopsisficheinter->rapportTous){
        $coef1 = 0.025;
        $coef2 = 0.10;
        $result = $tabResult[-1000][3] * $coef1 + ($tabResult[-1000][3] - $tabResult[-1000][2]) * $coef2;
        $strStat .= "<br/>Vendue<table><tr><td>Prévue</td><td>* " . $coef1 . "</td><td>+ Bonus</td><td>X " . $coef2 . "</td><td>= " . price($result) . " €</td></tr>";
        $strStat .= "<tr><td>" . price($tabResult[-1000][3]) . " €</td><td>* " . $coef1 . "</td><td>+ " . price($tabResult[-1000][3] - $tabResult[-1000][2]) . " €</td><td>X " . $coef2 . "</td><td>= " . price($result) . " €</td></tr></table>";

        $strStat .= "<br/>";


        $strStat .= "<br/>Non Vendue<table><tr><td>Réalisé</td><td>* " . $coef1 . "</td><td>= " . price($coef1 * $tabResult[-1001][2]) . " €</td></tr>";
        $strStat .= "<tr><td>" . price($tabResult[-1001][2]) . " €</td><td>* " . $coef1 . "</td><td>= " . price($coef1 * $tabResult[-1001][2]) . " €</td></tr></table>";

        $strStat .= "</td><td>  ";

        $strStat .= "<br/>Total<table><tr><td>" . price($result) . " €</td><td>+ " . price($coef1 * $tabResult[-1001][2]) . "</td><td>= " . price($result + $coef1 * $tabResult[-1001][2]) . " €</td></tr></table>";

        $strStat .= "<br/>";
    }
    $strStat .= "</td></tr></table>";


    ksort($tabType);

    foreach ($tabType as $idType => $labelType) {
        $texte = "<table class='tabResumeDeuxColl'><tr><th colspan = '2' class='ui-widget-header titre'>" . $labelType . "</th></tr>"
                . "<tr><th class='ui-widget-header'> Réalisé</th><td class='ui-widget-content'> " . price($tabResult[$idType][2]) . "€  (" . price($tabResult[$idType][0] / 3600) . "h) </td></tr>";
        $texte .= "<tr><th class='ui-widget-header'>  Prévu </th> <td class='ui-widget-content'>" . price($tabResult[$idType][3]) . "€ (" . price($tabResult[$idType][1] / 3600) . "h )</td></tr>";
//        $texte .=  "<tr><th class='ui-widget-header'> Vendu </th><td class='ui-widget-content'> " . price($tabResult[$idType][4] + $tabResult[$idType][5]) . " €</td></tr>";
        if ($tabResult[$idType][3] > 0)
            $pourcent1 = 100 - ($tabResult[$idType][2] * 100) / $tabResult[$idType][3];
        else
            $pourcent1 = "n/c";

        $precision = " (" . price($tabResult[$idType][3] - $tabResult[$idType][2]) . " € | " . (price($tabResult[$idType][1] / 3600 - $tabResult[$idType][0] / 3600)) . " h)";

        if (!is_numeric($pourcent1))
            $texte.= "<tr><th class='ui-widget-header'>Bonus (réalisé / vendu) </th><td class='ui-widget-content' style='color:orange;'> " . $pourcent1 . $precision . "</td></tr>";
        elseif ($pourcent1 >= 0)
            $texte.= "<tr><th class='ui-widget-header'>Bonus (réalisé / prévu) </th><td class='ui-widget-content' style='color:green;'> " . price2num($pourcent1, 2) . "%" . $precision . "</td></tr>";
        else
            $texte.= "<tr><th class='ui-widget-header'>Malus (réalisé / prévu) </th><td class='ui-widget-content' style='color:red;'> " . price2num(-$pourcent1, 2) . "%" . $precision . "</td></tr>";

        if (($tabResult[$idType][5] + $tabResult[$idType][4]) > 0)
            $pourcent2 = 100 - ($tabResult[$idType][2] * 100) / ($tabResult[$idType][5] + $tabResult[$idType][4]);
        else
            $pourcent2 = "n/c";
//        if (!is_numeric($pourcent2))
//            $texte.= "<tr><th class='ui-widget-header'>Bonus (réalisé / vendu) </th><td class='ui-widget-content' style='color:orange;'> " . $pourcent2 . "</td></tr>";
//        elseif ($pourcent2 >= 0)
//            $texte.= "<tr><th class='ui-widget-header'>Bonus (réalisé / vendu) </th><td class='ui-widget-content' style='color:green;'> " . price2num($pourcent2, 2) . "%</td></tr>";
//        else
//            $texte.= "<tr><th class='ui-widget-header'>Malus (réalisé / vendu) </th><td class='ui-widget-content' style='color:red;'> " . price2num(-$pourcent2, 2) . "%</td></tr>";
        $texte .= "</table>";
        if ($idType > 0)
            $additionP = $additionP + $tabResult[$idType][2];
        echo $texte;
    }
        echo $strStat;
        echo "<br/><table class='tabBorder100'><tr><td style='    min-width: 380px;'>".$probAppel;
    return $tabResult;
}

function testFi($tabIdFi, $tabResult, $alert = true) {
    global $total_ht, $additionP, $db;

    if (count($tabIdFi) == 0)
        return array();

    /*  test */
    $additionPT = price($total_ht - $additionP - $tabResult[0][2]);
    if ($additionPT != 0 && $alert)
        echo "Attention problême de calcul merci de contacter votre service technique au plus vite !!! ";


    if ($tabResult[0][2] != 0) {
        $requeteType7 = "SELECT fk_fichinter FROM " . MAIN_DB_PREFIX . "Synopsis_fichinterdet fdet WHERE fdet.fk_fichinter IN (" . implode(",", $tabIdFi) . ") AND (fk_typeinterv is NULL || fk_typeinterv= 0 ) Group BY fk_fichinter";
        $result7 = $db->query($requeteType7);
        echo "</td><td><div style='clear:both;'></div><br/>Attention marge d'erreur de " . price($tabResult[0][2]) . "€ (" . price($tabResult[0][0] / 3600) . "h ) sur le réalisé due aux " . $db->num_rows($result7) . " interventions réalisées sans type dont les 30 premières sont listées ci dessous<br/><br/>";
        $i = 0;
        while ($ligne = $db->fetch_object($result7)) {
            if ($i < 30) {
                $fi = new Fichinter($db);
                $fi->fetch($ligne->fk_fichinter);
                echo $fi->getNomUrl(1) . "<br/>";
            }
            $tabIdErreur[$ligne->fk_fichinter] = $ligne->fk_fichinter;
            $i ++;
        }
    }


    if ($tabResult[0][3] != 0) {
        $requeteType7 = "SELECT fk_synopsisdemandeinterv FROM " . MAIN_DB_PREFIX . "synopsisdemandeintervdet fdet WHERE fdet.fk_synopsisdemandeinterv IN (" . implode(",", $tabIdFi) . ") AND (fk_typeinterv is NULL || fk_typeinterv= 0 ) Group BY fk_synopsisdemandeinterv";
        $result7 = $db->query($requeteType7);
        echo "</td><td><div style='clear:both;'></div><br/>Attention marge d'erreur de " . price($tabResult[0][3]) . "€ (" . price($tabResult[0][1] / 3600) . "h ) sur le prévue due aux " . $db->num_rows($result7) . " demande d'interventions réalisées sans type dont les 30 premières sont listées ci dessous<br/><br/>";
        $i = 0;
        while ($ligne = $db->fetch_object($result7)) {
            if ($i < 30) {
                $fi = new Synopsisdemandeinterv($db);
                $fi->fetch($ligne->fk_synopsisdemandeinterv);
                echo $fi->getNomUrl(1) . "<br/>";
            }
            $tabIdErreurDI[$ligne->fk_synopsisdemandeinterv] = $ligne->fk_synopsisdemandeinterv;
            $i ++;
        }
    }

//
//
//    $requetePasDeDI = "SELECT fk_fichinter, SUM(total_ht) as tot FROM " . MAIN_DB_PREFIX . "Synopsis_fichinterdet fdet WHERE fdet.fk_fichinter IN (" . implode(",", $tabIdFi) . ") AND (fk_contratdet is NULL || fk_contratdet = 0 ) AND (fk_commandedet is NULL || fk_commandedet = 0 ) AND (fk_depProduct is NULL || fk_depProduct = 0 ) Group BY fk_fichinter";
//    $resultPasDeDI = $db->query($requetePasDeDI);
//    if ($db->num_rows($resultPasDeDI) > 0) {
//        echo "<div style='clear:both;'></div><br/>Attention marge d'erreur sur le vendue due aux " . $db->num_rows($resultPasDeDI) . " interventions réalisées sans référence à un contrat ou à une commande dont les dix premières sont listées ci dessous<br/>";
//        $i = 0;
//        while ($ligne = $db->fetch_object($resultPasDeDI)) {
//            if ($i < 10) {
//                $fi = new Fichinter($db);
//                $fi->fetch($ligne->fk_fichinter);
//                echo $fi->getNomUrl(1) . "<br/>";
//            }
//            $tabIdErreur[$ligne->fk_fichinter] = $ligne->fk_fichinter;
//            $i ++;
//        }
//    }


    $requetePasDeDI = "SELECT fk_fichinter, SUM(total_ht) as tot FROM " . MAIN_DB_PREFIX . "Synopsis_fichinterdet fdet WHERE fdet.fk_fichinter IN (" . implode(",", $tabIdFi) . ") AND fk_fichinter NOT IN (SELECT `fk_target`  FROM `" . MAIN_DB_PREFIX . "element_element` WHERE `sourcetype` LIKE 'DI' AND `targettype` LIKE 'FI') Group BY fk_fichinter";
    $resultPasDeDI = $db->query($requetePasDeDI);
    if ($db->num_rows($resultPasDeDI) > 0) {
        echo "</td><td><div style='clear:both;'></div><br/>Attention marge d'erreur sur le prevu due aux " . $db->num_rows($resultPasDeDI) . " interventions réalisées sans DI dont les 30 premières sont listées ci dessous<br/><br/>";
        $i = 0;
        while ($ligne = $db->fetch_object($resultPasDeDI)) {
            if ($i < 30) {
                $fi = new Fichinter($db);
                $fi->fetch($ligne->fk_fichinter);
                echo $fi->getNomUrl(1) . "<br/>";
            }
            $tabIdErreur[$ligne->fk_fichinter] = $ligne->fk_fichinter;
            $i ++;
        }
    }
    
    echo "</td></tr></table>";
    return $tabIdErreur;
}

?>
