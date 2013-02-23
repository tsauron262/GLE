<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2006 Regis Houssin        <regis.houssin@capnetworks.com>
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
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Create on : 4-1-2009
  *
  * Infos on http://www.finapro.fr
  *
  */
/*
 *
 * $Id: index.php,v 1.41 2008/06/18 20:01:02 hregis Exp $
 * $Source: /cvsroot/dolibarr/dolibarr/htdocs/projet/index.php,v $
 */

/**
        \file       htdocs/projet/index.php
        \ingroup    projet
        \brief      Page d'accueil du module projet
        \version    $Revision: 1.41 $
*/

require("./pre.inc.php");
$langs->load("projectsSyn@projet");

if (!$user->rights->synopsisprojet->lire) accessforbidden();

// Securite acces client
if ($user->societe_id > 0)
{
  $socid = $user->societe_id;
}/*
$jQueryDashBoardPath = DOL_URL_ROOT.'/Synopsis_Common/jquery/dashboard/';

$js = '
    <script>var DOL_URL_ROOT="'.DOL_URL_ROOT.'";</script>
    <script>var DOL_DOCUMENT_ROOT="'.DOL_DOCUMENT_ROOT.'";</script>
    <script type="text/javascript" src="'.$jQueryDashBoardPath.'jquery.dashboard.js"></script>
    <link rel="stylesheet" type="text/css" href="'.$jQueryDashBoardPath.'dashboard.css" />

    <script type="text/javascript" src="'.$jQueryDashBoardPath.'dashboard.js"></script>
    <link rel="stylesheet" type="text/css" href="'.$jQueryDashBoardPath.'demo.css" />
    <script type="text/javascript">var userid='.$user->id.';</script>
    <script type="text/javascript">var dashtype="36";</script>

';*/

    llxHeader($js,$langs->trans('Projects'),0);

/*
    print '<div class="titre">Mon tableau de bord - '.$langs->trans('ProjectsArea').'</div>';
    print "<br/>";
    print "<br/>";
    print "<div style='padding: 5px 10px; width: 270px;' class='butAction ui-state-default ui-widget-header ui-corner-all'><em><span style='float: left; margin: -1px 3px 0px 0px' class='ui-icon ui-icon-info'></span><a href='#' onClick='addWidget()'>Ajouter des widgets &agrave; votre tableau de bord.</a></em></div>";
    print "<br/>";
    print '<div id="dashboard">';
    print '  You need javascript to use the dashboard.';
    print '</div>';*/
//$csspath = DOL_URL_ROOT.'/Synopsis_Common/css/';
//$jspath = DOL_URL_ROOT.'/Synopsis_Common/jquery/';
//$jqueryuipath = DOL_URL_ROOT.'/Synopsis_Common/jquery/ui/';
//
//
//llxHeader($header,$langs->trans("Projects"),"Projet","1");
//
//print_fiche_titre($langs->trans("ProjectsArea"));
//
//print '<table border="0" width="100%" class="notopnoleftnoright">';
//print '<tr><td width="30%" valign="top" class="notopnoleft">';
//
///*
// *
// * Affichage de la liste des projets
// *
// */
//print '<table class="noborder" width="100%">';
//print '<tr class="liste_titre">';
//print_liste_field_titre($langs->trans("Project"),"index.php","s.nom","","","",$sortfield,$sortorder);
//print '<td align="right">'.$langs->trans("NbOpenTasks").'</td>';
//print '<td align="right">'.$langs->trans("Avancement").'</td>';
//print "</tr>\n";
//
//$sql = "SELECT p.title, p.rowid, count(t.rowid), avg(t.progress) as prg";
//if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
//$sql.= " FROM";
//if (!$user->rights->societe->client->voir && !$socid) $sql .= " ".MAIN_DB_PREFIX."societe_commerciaux as sc,";
//$sql.= " ".MAIN_DB_PREFIX."Synopsis_projet as p";
//$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."Synopsis_projet_task as t ON p.rowid = t.fk_projet";
//$sql.= " WHERE 1 = 1";
//if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND p.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
//if ($socid)
//{
//  $sql .= " AND p.fk_soc = ".$socid;
//}
//$sql.= " GROUP BY p.rowid";
//
//
//
//
//$var=true;
//$resql = $db->query($sql);
//if ( $resql )
//{
//  $num = $db->num_rows($resql);
//  $i = 0;
//
//  while ($i < $num)
//    {
//      $row = $db->fetch_row( $resql);
//      $var=!$var;
//      print "<tr $bc[$var]>";
//      print '<td nowrap="nowrap"><a href="'.DOL_URL_ROOT.'/projet/fiche.php?id='.$row[1].'">'.img_object($langs->trans("ShowProject"),"project")." ".$row[0].'</a></td>';
//      print '<td align="right">'.$row[2].'</td>';
//      print '<td align="right"><div style="height: 16px; border:1px solid #000000;" id="progBar'.$row[1].'"></div></td>';
//      print "</tr>\n";
//      print '<script type="text/javascript">$("#progBar'.$row[1].'").progressbar({ value:"'.$row[3].'"})</script>';
//
//      $i++;
//    }
//
//  $db->free($resql);
//}
//else
//{
//  dol_print_error($db);
//}
//print "</table>";
//
//print '</td><td width="70%" valign="top" class="notopnoleft">';
//
///*
// *
// * Affichage de la liste des projets
// *
// */
//print '<table class="noborder" width="100%">';
//print '<tr class="liste_titre">';
//print_liste_field_titre($langs->trans("Company"),"index.php","s.nom","","","",$sortfield,$sortorder);
//print '<td align="right">'.$langs->trans("Nb").'</td>';
//print "</tr>\n";
//
//$sql = "SELECT s.nom, s.rowid as socid, count(p.rowid)";
//if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
//$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."Synopsis_projet as p";
//if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
//$sql .= " WHERE p.fk_soc = s.rowid";
//if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
//if ($socid)
//{
//  $sql .= " AND s.rowid = ".$socid;
//}
//$sql .= " GROUP BY s.nom";
////$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit, $offset);
//
//$var=true;
//$resql = $db->query($sql);
//if ( $resql )
//{
//  $num = $db->num_rows($resql);
//  $i = 0;
//
//  while ($i < $num)
//    {
//      $row = $db->fetch_row( $resql);
//      $var=!$var;
//      print "<tr $bc[$var]>";
//      print '<td nowrap="nowrap"><a href="'.DOL_URL_ROOT.'/projet/liste.php?socid='.$row[1].'">'.img_object($langs->trans("ShowCompany"),"company")." ".$row[0].'</a></td>';
//      print '<td align="right">'.$row[2].'</td>';
//      print "</tr>\n";
//
//      $i++;
//    }
//
//  $db->free($resql);
//}
//else
//{
//  dol_print_error($db);
//}
//print "</table>";
//
//print '</td></tr></table>';
//
//
//
//$db->close();
//
llxFooter("<em>Derni&egrave;re modification $Date: 2008/06/18 20:01:02 $ r&eacute;vision $Revision: 1.41 $</em>");



function sec2time($sec){
    if (!is_numeric($sec))
    {
        $sec = 0;
    }
    $returnstring = " ";
    $days = intval($sec/86400);
    $hours = intval ( ($sec/3600) - ($days*24));
    $minutes = intval( ($sec - (($days*86400)+ ($hours*3600)))/60);
    $seconds = $sec - ( ($days*86400)+($hours*3600)+($minutes * 60));

    $returnstring .= ($days)?(($days == 1)? "1 j":$days."j"):"";
    $returnstring .= ($days && $hours && !$minutes && !$seconds)?"":"";
    $returnstring .= ($hours)?( ($hours == 1)?" 1h":" " .$hours."h"):"";
    $returnstring .= (($days || $hours) && ($minutes && !$seconds))?"  ":" ";
    $returnstring .= ($minutes)?( ($minutes == 1)?" 1 min":" ".$minutes."min"):"";
    //$returnstring .= (($days || $hours || $minutes) && $seconds)?" et ":" ";
    //$returnstring .= ($seconds)?( ($seconds == 1)?"1 second":"$seconds seconds"):"";
    return ($returnstring);
}
function sec2hour($sec){
    if (!is_numeric($sec))
    {
        $sec = 0;
    }
    $days=false;
    $returnstring = " ";
    $hours = intval ( ($sec/3600) );
    $minutes = intval( ($sec - ( ($hours*3600)))/60);
    $seconds = $sec - ( ($hours*3600)+($minutes * 60));

    $returnstring .= ($days)?(($days == 1)? "1 j":$days."j"):"";
    $returnstring .= ($days && $hours && !$minutes && !$seconds)?"":"";
    $returnstring .= ($hours)?( ($hours == 1)?" 1h":" " .$hours."h"):"";
    $returnstring .= (($days || $hours) && ($minutes && !$seconds))?"  ":" ";
    $returnstring .= ($minutes)?( ($minutes == 1)?" 1 min":" ".$minutes."min"):"";
    //$returnstring .= (($days || $hours || $minutes) && $seconds)?" et ":" ";
    //$returnstring .= ($seconds)?( ($seconds == 1)?"1 second":"$seconds seconds"):"";
    return ($returnstring);
}


?>
