<?php

/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@capnetworks.com>
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
/*
 */

/**
  \file       htdocs/synopsisdemandeinterv/index.php
  \brief      Page accueil espace fiches interventions
  \ingroup    synopsisdemandeinterv
  \version    $Id: index.php,v 1.40 2008/04/09 18:13:50 eldy Exp $
 */
require("./pre.inc.php");


require_once DOL_DOCUMENT_ROOT.'/bimpcore/Bimp_Lib.php';
require_once(DOL_DOCUMENT_ROOT."/bimpfichinter/objects/Bimp_Fichinter.class.php");
$htmlRedirect = Bimp_Fichinter::redirect(false, "list");


require_once(DOL_DOCUMENT_ROOT . "/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT . "/synopsisdemandeinterv/class/synopsisdemandeinterv.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/date.lib.php");

$langs->load("companies");
$langs->load("interventions");

$sortorder = isset($_GET["sortorder"]) ? $_GET["sortorder"] : "DESC";
$sortfield = isset($_GET["sortfield"]) ? $_GET["sortfield"] : "fk_statut asc, f.datei ";
$socid = isset($_GET["socid"]) ? $_GET["socid"] : "";
$page = isset($_GET["page"]) ? $_GET["page"] : "";

// Security check
$synopsisdemandeintervid = isset($_GET["id"]) ? $_GET["id"] : '';
if ($user->societe_id)
    $socid = $user->societe_id;
//$result = restrictedArea($user, 'synopsisdemandeinterv', $synopsisdemandeintervid,'');

//if (!$sortorder)
//    $sortorder = "DESC";
//if (!$sortfield)
//    $sortfield = "f.datei";
if ($page == -1) {
    $page = 0;
}

$limit = $conf->liste_limit;
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$filtreUser = (isset($_REQUEST['filtreUser']) && $_REQUEST['filtreUser']);

/*
 *    View
 */

llxHeader();

echo $htmlRedirect; 

$sql = "SELECT s.nom,s.rowid as socid, f.ref, f.datei as dp, f.rowid as fichid, f.fk_statut, f.description, f.duree";
if (!$user->rights->societe->client->voir && !$socid)
    $sql .= ", sc.fk_soc, sc.fk_user";
$sql.= " FROM " . MAIN_DB_PREFIX . "societe as s, " . MAIN_DB_PREFIX . "synopsisdemandeinterv as f ";
if (!$user->rights->societe->client->voir && !$socid)
    $sql .= ", " . MAIN_DB_PREFIX . "societe_commerciaux as sc";
$sql.= " WHERE f.fk_soc = s.rowid ";
if (!$user->rights->societe->client->voir && !$socid)
    $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " . $user->id;
if ($socid > 0) {
    $sql .= " AND s.rowid = " . $socid;
}
if ($filtreUser)
    $sql .= ' AND fk_user_prisencharge = '.$user->id;
$sql.= " ORDER BY $sortfield $sortorder ";
$sql.= $db->plimit($limit + 1, $offset);

$result = $db->query($sql);
if ($result) {
    $num = $db->num_rows($result);

    $synopsisdemandeinterv_static = new Synopsisdemandeinterv($db);

    $urlparam = "&amp;socid=$socid&filtreUser=$filtreUser";
    if ($filtreUser)
        print_barre_liste($langs->trans("ListOfMyDIs"), $page, "index.php", $urlparam, $sortfield, $sortorder, '', $num);
    else
        print_barre_liste($langs->trans("ListOfDIs"), $page, "index.php", $urlparam, $sortfield, $sortorder, '', $num);

    $i = 0;
    print '<table class="noborder" width="100%">';
    print "<tr class=\"liste_titre\">";
    print_liste_field_titre($langs->trans("Ref"), "index.php", "f.ref", "", $urlparam, 'width="15%"', $sortfield, $sortorder);
    print_liste_field_titre($langs->trans("Company"), "index.php", "s.nom", "", $urlparam, '', $sortfield, $sortorder);
    print '<td>' . $langs->trans("Description") . '</td>';
    print_liste_field_titre($langs->trans("Date"), "index.php", "f.datei", "", $urlparam, 'align="center"', $sortfield);
    print '<td align="right">' . $langs->trans("Duration") . '</td>';
    print '<td align="right">' . $langs->trans("Status") . '</td>';
    print "</tr>\n";
    $var = True;
    $total = 0;
    while ($i < min($num, $limit)) {
        $objp = $db->fetch_object($result);
        $var = !$var;
        print "<tr $bc[$var]>";
        print "<td><a href=\"card.php?id=" . $objp->fichid . "\">" . img_object($langs->trans("Show"), "synopsisdemandeinterv@synopsisdemandeinterv") . ' ' . $objp->ref . "</a></td>\n";
        print '<td><a href="' . DOL_URL_ROOT . '/comm/card.php?socid=' . $objp->socid . '">' . img_object($langs->trans("ShowCompany"), "company") . ' ' . dol_trunc($objp->nom, 44) . "</a></td>\n";
        print '<td>' . nl2br($objp->description) . '</td>';
        print '<td align="center">' . dol_print_date($db->jdate($objp->dp), 'day') . "</td>\n";
        print '<td align="right">' . ConvertSecondToTime($objp->duree) . '</td>';
        print '<td align="right">' . $synopsisdemandeinterv_static->LibStatut($objp->fk_statut, 5) . '</td>';

        print "</tr>\n";
        $total += $objp->duree;
        $i++;
    }
    print '<tr class="liste_total"><td colspan="3"></td><td>' . $langs->trans("Total") . '</td>';
    print '<td align="right" nowrap>' . ConvertSecondToTime($total) . '</td><td></td>';
    print '</tr>';

    print '</table>';
    $db->free($result);
} else {
    dol_print_error($db);
}

$db->close();

llxFooter('$Date: 2008/04/09 18:13:50 $ - $Revision: 1.40 $');
?>
