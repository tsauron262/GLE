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
 * $Source: /cvsroot/dolibarr/dolibarr/htdocs/fichinter/rapport.php,v $
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
require_once(DOL_DOCUMENT_ROOT . "/fichinter/class/fichinter.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/date.lib.php");


$enJour = false;



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
}

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
$page = 0;
//}

$limit = $conf->liste_limit;
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$sql = "SELECT s.nom,
            s.rowid as socid,
            f.description,
            f.ref,
            f.datei as dp,
            f.rowid as fichid,
            f.fk_statut,
            f.duree,
	    f.total_ht";
$sql .= " FROM " . MAIN_DB_PREFIX . "societe as s,
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
$sql .= " ORDER BY $sortfield $sortorder ";


$requete = "SELECT DISTINCT s.nom,s.rowid as socid ";
$requete .= " FROM " . MAIN_DB_PREFIX . "societe as s, " . MAIN_DB_PREFIX . "Synopsis_fichinter as f ";
$requete .= " WHERE f.fk_soc = s.rowid";

//    $requete .= " AND datei >= '$start' AND datei < '$end'" ;
if ($filterUser) {
    $requete .= " AND fk_user_author = " . $filterUser;
}


$requete .= " ORDER BY $sortfield $sortorder ";

$sqlpre1 = $db->query($requete);
$selSoc = "<select name='socid'>";
$selSoc .= "<option value=''>S&eacute;lectioner -></option>";
while ($respre1 = $db->fetch_object($sqlpre1)) {
    if ($socid > 0 && $socid == $respre1->socid) {
        $selSoc .= "<option SELECTED value='" . $respre1->socid . "'>" . $respre1->nom . "</option>";
    } else {
        $selSoc .= "<option value='" . $respre1->socid . "'>" . $respre1->nom . "</option>";
    }
}
$selSoc .= "</select>";

$req = "SELECT * FROM `" . MAIN_DB_PREFIX . "Synopsis_fichinter_c_typeInterv` WHERE `active` = 1";
$sqlpre11 = $db->query($req);
$selectHtml2 = '<select name="typeInter">';
$selectHtml2 .= "<option value=''>S&eacute;lectioner -></option>";
while ($respre11 = $db->fetch_object($sqlpre11)) {
    $i = $respre11->id;
    $option = $respre11->label;
    if (isset($_GET['typeInter']) && $i == $_GET['typeInter']) {
        $selectHtml2 .= "<OPTION SELECTED value='" . $i . "'>" . $option . "</OPTION>";
    } else {
        $selectHtml2 .= "<OPTION value='" . $i . "'>" . $option . "</OPTION>";
    }
}
$selectHtml2 .= "</select>";

//TODO si selection société filtrer
//print $sql;
$resql = $db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql);
    $title = "Rapport d'activit&eacute; de " . utf8_decode(strftime("%B %Y", strtotime($start)));
    print_barre_liste($title, $page, "rapport.php", "&socid=$socid", $sortfield, $sortorder, '', $num);
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


    echo "<td align=center>Soci&eacute;t&eacute;";
    print $selSoc;
    if ($user->rights->synopsisficheinter->rapportTous) {
        echo "<td align=center>Intervenant";
        $html->select_users($_REQUEST['filterUser'], 'filterUser', 1, '', 0, 1);
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
    print '<td align="center">' . $langs->trans("Description") . '</td>';

    print '<td align="center">Date</td>';
    print '<td align="center">' . $langs->trans("Duration") . '</td>';
    print '<td align="center">Prix HT</td>';
    print "</tr>\n";
    $var = true;
    $DureeTotal = 0;
    $total_ht = 0;
    while ($objp = $db->fetch_object($resql)) {
        $var = !$var;
        print "<tr $bc[$var]>";
        $di = new Fichinter($db);
        $di->fetch($objp->fichid);

        $total_ht += $objp->total_ht;

        print "<td>" . $di->getNomUrl(1) . "</td>\n";

        if (empty($socid)) {
            if (!empty($MM))
                $filter = "&MM=$MM&YY=$YY";
            $tmpSoc = new Societe($db);
            $tmpSoc->fetch($objp->socid);
            print '<td><a href="rapport.php?socid=' . $objp->socid . $filter . '"><img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/filter.png" border="0"></a>&nbsp;';
            print $tmpSoc->getNomUrl(1) . "</TD>\n";
        }
        print '<td>' . nl2br($objp->description) . '</td>';
        print "<td>" . dol_print_date($objp->dp) . "</td>\n";
        $durStr = convDur($objp->duree);
        if ($enJour)
            print '<td align="center">' . ($durStr['days']['abs'] > 0 ? $durStr['days']['abs'] . 'j ' : "") . $durStr['hours']['rel'] . 'h ' . $durStr['minutes']['rel'] . 'm</td>';
        else
            print '<td align="center">' . ($durStr['days']['abs'] > 0 ? $durStr['days']['abs'] * 24 + $durStr['hours']['rel'] : $durStr['hours']['rel']) . 'h ' . $durStr['minutes']['rel'] . 'm</td>';
        print '<td align="center">' . $objp->total_ht . ' &euro;</td>';

        $DureeTotal += $objp->duree;
        print "</tr>\n";
    }
    print "</table>";
//    $db->free();
    $durStr = convDur($DureeTotal);
    if ($enJour)
        print "<br />" . $langs->trans("Total") . ": " . ($durStr['days']['abs'] > 0 ? $durStr['days']['abs'] . 'j ' : "") . $durStr['hours']['rel'] . 'h ' . $durStr['minutes']['rel'] . 'm' . "  |  " . $total_ht . " &euro;";
    else
        print "<br />" . $langs->trans("Total") . ": " . ($durStr['days']['abs'] > 0 ? $durStr['days']['abs'] * 24 + $durStr['hours']['rel'] : $durStr['hours']['rel']) . 'h ' . $durStr['minutes']['rel'] . 'm' . "  |  " . $total_ht . " &euro;";
//  print "<br />".$langs->trans("Total")." $DureeTotal jour[s]";
} else {
    dol_print_error($db);
}
$db->close();

llxFooter("<em>Derni&egrave;re modification: 2007/06/22 08:44:46 $ r&eacute;vision: 1.12 $</em>");

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
?>
