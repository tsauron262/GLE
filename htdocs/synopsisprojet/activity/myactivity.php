<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.*//*
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
  *//*
 */

/**
        \file       htdocs/synopsisprojet/activity/myactivity.php
        \ingroup    projet
        \brief      Page activite perso du module projet
        \version    $Id: myactivity.php,v 1.12 2008/04/28 22:34:41 eldy Exp $
*/

require("./pre.inc.php");

if (!$user->rights->synopsisprojet->lire) accessforbidden();

/*
 * Securite acces client
 */
if ($user->societe_id > 0)
{
  $socid = $user->societe_id;
}

$langs->load("synopsisproject@synopsisprojet");
$head = '<script type="text/javascript" src="'.DOL_URL_ROOT.'/Synopsis_Common/js/swfobject.js"></script>';


$head .=  '<script type="text/javascript">';


$head .=  'swfobject.embedSWF( "'.DOL_URL_ROOT.'/Synopsis_Common/open-flash-chart/open-flash-chart.swf", "chart2", "750", "400", "9.0.0", "expressInstall.swf",';
$head .= '{"data-file":"'.DOL_URL_ROOT.'/synopsisprojet/activity/myactivity-pie-chart.php?dur=effective&userId=59"} );';

$head .=  'swfobject.embedSWF( "'.DOL_URL_ROOT.'/Synopsis_Common/open-flash-chart/open-flash-chart.swf", "chart3", "750", "400", "9.0.0", "expressInstall.swf",';
$head .= '{"data-file":"'.DOL_URL_ROOT.'/synopsisprojet/activity/myactivity-pie-chart.php?dur=planed&userId=59"} );';


$head .= '</script>';


llxHeader($head,$langs->trans("MyActivity"),"","1");

$now = time();

print_fiche_titre($langs->trans("MyActivity"));

print '<table border="0" width="100%" class="notopnoleftnoright">';
print '<tr><td width="30%" valign="top" class="notopnoleft">';

/*
 *
 * Affichage de la liste des projets
 *
 */
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans("Project"),"index.php","s.nom","","","",$sortfield,$sortorder);
print '<td align="center">'.$langs->trans("NbOpenTasks").'</td>';
print "</tr>\n";

$sql = "SELECT p.title, p.rowid, count(t.rowid)";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
$sql .= " FROM ".MAIN_DB_PREFIX."Synopsis_projet as p";
$sql .= " , ".MAIN_DB_PREFIX."Synopsis_projet_task as t";
$sql .= " , ".MAIN_DB_PREFIX."Synopsis_projet_task_actors as a";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql .= " WHERE t.fk_projet = p.rowid";
if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND p.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid)
{
  $sql .= " AND p.fk_soc = ".$socid;
}
$sql .= " AND a.fk_user = ".$user->id;
$sql .= " AND a.fk_projet_task = t.rowid ";
$sql .= " AND t.statut = 'open'";

$sql .= " GROUP BY p.rowid";


$var=true;
$resql = $db->query($sql);
if ( $resql )
{
  $num = $db->num_rows($resql);
  $i = 0;

  while ($i < $num)
    {
      $row = $db->fetch_row( $resql);
      $var=!$var;
      print "<tr $bc[$var]>";
      print '<td><a href="'.DOL_URL_ROOT.'/synopsisprojet/tasks/card.php?id='.$row[1].'">'.$row[0].'</a></td>';
      print '<td align="center">'.$row[2].'</td>';
      print "</tr>\n";

      $i++;
    }

  $db->free($resql);
}
else
{
  dol_print_error($db);
}
print "</table>";

/* Affichage de la liste des projets d'aujourd'hui */
print '<br /><table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="50%">'.$langs->trans('Today').'</td>';
print '<td width="50%" align="center">Temps</td>';
print "</tr>\n";

$sql = "SELECT p.title, p.rowid, sum(tt.task_duration)";
$sql .= " FROM ".MAIN_DB_PREFIX."Synopsis_projet as p";
$sql .= " , ".MAIN_DB_PREFIX."Synopsis_projet_task as t";
$sql .= " , ".MAIN_DB_PREFIX."Synopsis_projet_task_time as tt";
$sql .= " WHERE t.fk_projet = p.rowid";
$sql .= " AND tt.fk_task = t.rowid";
$sql .= " AND tt.fk_user = ".$user->id;
$sql .= " AND date_format(task_date,'%d%m%y') = ".strftime("%d%m%y",time());
$sql .= " GROUP BY p.rowid";

$var=true;
$total=0;
$resql = $db->query($sql);
if ( $resql )
{
  while ($row = $db->fetch_row($resql))
    {
      $var=!$var;
      print "<tr $bc[$var]>";
      print '<td><a href="'.DOL_URL_ROOT.'/synopsisprojet/tasks/card.php?id='.$row[1].'">'.$row[0].'</a></td>';
      print '<td align="center">'.sec2hour($row[2]).'</td>';
      print "</tr>\n";
      $total += $row[2];
    }

  $db->free($resql);
}
else
{
  dol_print_error($db);
}
print '<tr class="liste_total">';
print '<td>'.$langs->trans('Total').'</td>';
print '<td align="center">'.sec2hour($total).'</td>';
print "</tr>\n";
print "</table>";

/* Affichage de la liste des projets d'hier */
print '<br /><table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="50%">'.$langs->trans('Yesterday').'</td>';
print '<td width="50%" align="center">Temps</td>';
print "</tr>\n";

$sql = "SELECT p.title, p.rowid, sum(tt.task_duration)";
$sql .= " FROM ".MAIN_DB_PREFIX."Synopsis_projet as p";
$sql .= " , ".MAIN_DB_PREFIX."Synopsis_projet_task as t";
$sql .= " , ".MAIN_DB_PREFIX."Synopsis_projet_task_time as tt";
$sql .= " WHERE t.fk_projet = p.rowid";
$sql .= " AND tt.fk_task = t.rowid";
$sql .= " AND tt.fk_user = ".$user->id;
$sql .= " AND date_format(date_add(task_date, INTERVAL 1 DAY),'%d%m%y') = ".strftime("%d%m%y",time());
$sql .= " GROUP BY p.rowid";

$var=true;
$total=0;
$resql = $db->query($sql);
if ( $resql )
{
  while ($row = $db->fetch_row($resql))
    {
      $var=!$var;
        print "<tr $bc[$var]>";
      print '<td><a href="'.DOL_URL_ROOT.'/synopsisprojet/tasks/card.php?id='.$row[1].'">'.$row[0].'</a></td>';
      print '<td align="center">'.sec2hour($row[2]).'</td>';
      print "</tr>\n";
      $total += $row[2];
    }

  $db->free($resql);
}
else
{
  dol_print_error($db);
}
print '<tr class="liste_total">';
print '<td>'.$langs->trans('Total').'</td>';
print '<td align="center">'.sec2hour($total).'</td>';
print "</tr>\n";
print "</table>";

print '</td><td width="70%" valign="top" class="notopnoleft">';

/* Affichage de la liste des projets de la semaine */
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="50%">Activit&eacute; sur les projets cette semaine</td>';
print '<td width="50%" align="center">Temps</td>';
print "</tr>\n";

$sql = "SELECT p.title, p.rowid, sum(tt.task_duration)";
$sql .= " FROM ".MAIN_DB_PREFIX."Synopsis_projet as p";
$sql .= " , ".MAIN_DB_PREFIX."Synopsis_projet_task as t";
$sql .= " , ".MAIN_DB_PREFIX."Synopsis_projet_task_time as tt";
$sql .= " WHERE t.fk_projet = p.rowid";
$sql .= " AND tt.fk_task = t.rowid";
$sql .= " AND tt.fk_user = ".$user->id;
$sql .= " AND week(task_date) = ".strftime("%W",time());
$sql .= " GROUP BY p.rowid";
$total = 0;
$var=true;
$resql = $db->query($sql);
if ( $resql )
{
  while ($row = $db->fetch_row( $resql))
    {
      $var=!$var;
        print "<tr ".$bc[$var].">";
      print '<td><a href="'.DOL_URL_ROOT.'/synopsisprojet/tasks/card.php?id='.$row[1].'">'.$row[0].'</a></td>';
      print '<td align="center">'.sec2hour($row[2]).'</td>';
      print "</tr>\n";
      $total += $row[2];
    }

  $db->free($resql);
}
else
{
  dol_print_error($db);
}
print '<tr class="liste_total">';
print '<td>'.$langs->trans('Total').'</td>';
print '<td align="center">'.sec2hour($total).'</td>';
print "</tr>\n";
print "</table><br />";

/* Affichage de la liste des projets du mois */
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="50%">'.$langs->trans("Project").' ce mois : '.strftime("%B %Y", $now).'</td>';
print '<td width="50%" align="center">Nb heures</td>';
print "</tr>\n";

$sql = "SELECT p.title, p.rowid, sum(tt.task_duration)";
$sql .= " FROM ".MAIN_DB_PREFIX."Synopsis_projet as p";
$sql .= " , ".MAIN_DB_PREFIX."Synopsis_projet_task as t";
$sql .= " , ".MAIN_DB_PREFIX."Synopsis_projet_task_time as tt";
$sql .= " WHERE t.fk_projet = p.rowid";
$sql .= " AND tt.fk_task = t.rowid";
$sql .= " AND tt.fk_user = ".$user->id;
$sql .= " AND month(task_date) = ".strftime("%m",$now);
$sql .= " GROUP BY p.rowid";

$total = 0;
$var=true;
$resql = $db->query($sql);
if ( $resql )
{
  while ($row = $db->fetch_row($resql))
    {
      print "<tr $bc[$var]>";
      print '<td><a href="'.DOL_URL_ROOT.'/synopsisprojet/tasks/card.php?id='.$row[1].'">'.$row[0].'</a></td>';
      print '<td align="center">'.sec2hour($row[2]).'</td>';
      print "</tr>\n";
      $var=!$var;
      $total+=$row[2];
    }
  $db->free($resql);
} else {
  dol_print_error($db);
}print '<tr class="liste_total">';
print '<td>'.$langs->trans('Total').'</td>';
print '<td align="center">'.sec2hour($total).'</td>';
print "</tr>\n";

print "</table>";

/* Affichage de la liste des projets de l'annee */
print '<br /><table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="50%">'.$langs->trans("Project").' cette ann&eacute;e : '.strftime("%Y", $now).'</td>';
print '<td width="50%" align="center">Nb heures</td>';
print "</tr>\n";

$sql = "SELECT p.title, p.rowid, sum(tt.task_duration)";
$sql .= " FROM ".MAIN_DB_PREFIX."Synopsis_projet as p";
$sql .= " , ".MAIN_DB_PREFIX."Synopsis_projet_task as t";
$sql .= " , ".MAIN_DB_PREFIX."Synopsis_projet_task_time as tt";
$sql .= " WHERE t.fk_projet = p.rowid";
$sql .= " AND tt.fk_task = t.rowid";
$sql .= " AND tt.fk_user = ".$user->id;
$sql .= " AND YEAR(task_date) = ".strftime("%Y",$now);
$sql .= " GROUP BY p.rowid";

$total = 0;
$var=true;
$resql = $db->query($sql);
if ( $resql )
{
  while ($row = $db->fetch_row($resql))
  {
      print "<tr $bc[$var]>";
      print '<td><a href="'.DOL_URL_ROOT.'/synopsisprojet/tasks/card.php?id='.$row[1].'">'.$row[0].'</a></td>';
      print '<td align="center">'.sec2hour($row[2]).'</td>';
      print "</tr>\n";
      $var=!$var;
      $total+=$row[2];
  }
  $db->free($resql);
} else {
    dol_print_error($db);
}
print '<tr class="liste_total">';
print '<td>'.$langs->trans('Total').'</td>';
print '<td align="center">'.sec2hour($total).'</td>';
print "</tr>\n";

print "</table>";

print '</td></tr></table>';


print "<table><tr><td>";
print "<div id='chart3'></div>";
print "<td>";
print "<div id='chart2'></div>";
print "</td></tr></table>";
//
//print '<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000"';
//print '        codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0"';
//print '        width="500"';
//print '        height="250" id="graph-2" align="middle">';
//
//print '    <param name="allowScriptAccess" value="sameDomain" />';
//print '    <param name="movie" value="open-flash-chart.swf" />';
//print '    <param name="quality" value="high" />';
//print '    <embed src="'.DOL_URL_ROOT.'/Synopsis_Common/open-flash-chart/open-flash-chart.swf"';
//print '           quality="high"';
//print '           bgcolor="#FFFFFF"';
//print '           width="500"';
//print '           height="250"';
//print '           name="open-flash-chart"';
//print '           align="middle"';
//print '           allowScriptAccess="sameDomain"';
//print '           type="application/x-shockwave-flash"';
//print '           pluginspage="http://www.macromedia.com/go/getflashplayer" />';
//print '</object>';

//
//
//print '<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000"';
//print '        codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0"';
//print '        width="500"';
//print '        height="250" id="graph-2" align="middle">';
//
//print '    <param name="allowScriptAccess" value="sameDomain" />';
//print '    <param name="movie" value="open-flash-chart.swf" />';
//print '    <param name="quality" value="high" />';
//print '    <embed src="'.DOL_URL_ROOT.'/Synopsis_Common/open-flash-chart/open-flash-chart.swf"';
//print '           quality="high"';
//print '           bgcolor="#FFFFFF"';
//print '           width="500"';
//print '           height="250"';
//print '           name="open-flash-chart"';
//print '           align="middle"';
//print '           allowScriptAccess="sameDomain"';
//print '           type="application/x-shockwave-flash"';
//print '           pluginspage="http://www.macromedia.com/go/getflashplayer" />';
//print '</object>';



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date: 2008/04/28 22:34:41 $ r&eacute;vision $Revision: 1.12 $</em>");
function sec2time($sec){
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
