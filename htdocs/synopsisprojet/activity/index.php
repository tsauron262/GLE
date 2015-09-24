<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2006 Regis Houssin        <regis@dolibarr.fr>
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
 */

/**
        \file       htdocs/synopsisprojet/activity/index.php
        \ingroup    projet
        \brief      Page activite du module projet
        \version    $Id: index.php,v 1.11 2008/04/28 22:34:41 eldy Exp $
*/

require("./pre.inc.php");
//$head = '<script type="text/javascript" src="'.DOL_URL_ROOT.'/Synopsis_Common/js/swfobject.js"></script>';


//$head .=  '<script type="text/javascript">';
//
//
//$head .=  'swfobject.embedSWF( "'.DOL_URL_ROOT.'/Synopsis_Common/open-flash-chart/open-flash-chart.swf", "chart2", "750", "400", "9.0.0", "expressInstall.swf",';
//$head .= '{"data-file":"'.DOL_URL_ROOT.'/synopsisprojet/activity/activity-pie-chart.php?dur=effective&userId=59"} );';
//
//$head .=  'swfobject.embedSWF( "'.DOL_URL_ROOT.'/Synopsis_Common/open-flash-chart/open-flash-chart.swf", "chart3", "750", "400", "9.0.0", "expressInstall.swf",';
//$head .= '{"data-file":"'.DOL_URL_ROOT.'/synopsisprojet/activity/activity-pie-chart.php?dur=planed&userId=59"} );';
//
//
//$head .= '</script>';

if (!$user->rights->synopsisprojet->lire) accessforbidden();

/*
 * Securite acces client
 */
if ($user->societe_id > 0)
{
  $action = '';
  $socid = $user->societe_id;
}
//

  $js="";

/*$jQueryDashBoardPath = DOL_URL_ROOT.'/Synopsis_Common/jquery/dashboard/';

$js = '
    <script>var DOL_URL_ROOT="'.DOL_URL_ROOT.'";</script>
    <script>var DOL_DOCUMENT_ROOT="'.DOL_DOCUMENT_ROOT.'";</script>
    <script type="text/javascript" src="'.$jQueryDashBoardPath.'jquery.dashboard.js"></script>
    <link rel="stylesheet" type="text/css" href="'.$jQueryDashBoardPath.'dashboard.css" />

    <script type="text/javascript" src="'.$jQueryDashBoardPath.'dashboard.js"></script>
    <link rel="stylesheet" type="text/css" href="'.$jQueryDashBoardPath.'demo.css" />
    <script type="text/javascript">var userid='.$user->id.';</script>
    <script type="text/javascript">var dashtype="38";</script>

';*/

    llxHeader($js,$langs->trans('Activity'),0);


  /*  print '<div class="titre">Mon tableau de bord - Projet - Activit&eacute;</div>';
    print "<br/>";
    print "<br/>";
    print "<div style='padding: 5px 10px; width: 270px;' class='butAction ui-state-default ui-widget-header ui-corner-all'><em><span style='float: left; margin: -1px 3px 0px 0px' class='ui-icon ui-icon-info'></span><a href='#' onClick='addWidget()'>Ajouter des widgets &agrave; votre tableau de bord.</a></em></div>";
    print "<br/>";
    print '<div id="dashboard">';
    print '  You need javascript to use the dashboard.';
    print '</div>';*/

//llxHeader($head,$langs->trans("Activity"),"1");
//
//$now = time();
//
//print_fiche_titre($langs->trans("Activity"));
//
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
//print '<td align="center">'.$langs->trans("NbOpenTasks").'</td>';
//print "</tr>\n";
//
//$sql = "SELECT p.title, p.rowid, count(t.rowid)";
//if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
//$sql .= " FROM ".MAIN_DB_PREFIX."Synopsis_projet_view as p";
//$sql .= " , ".MAIN_DB_PREFIX."projet_task as t";
//if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
//$sql .= " WHERE t.fk_projet = p.rowid";
//if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND p.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
//
//$sql .= " GROUP BY p.rowid";
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
//      print '<td><a href="'.DOL_URL_ROOT.'/synopsisprojet/tasks/card.php?id='.$row[1].'">'.$row[0].'</a></td>';
//      print '<td align="center">'.$row[2].'</td>';
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
//print '</td><td width="70%" valign="top" class="notopnoleft">';
//
//$var=true;
//
//// Defini les bornes date debut et fin de semaines, mois et annee pour le jour courant
//$now=time();
//$info=dol_getdate($now);
//$daystartw=$now-(($info['wday'] - 1)*24*3600);
//$dayendw  =$now+((7 - $info['wday'])*24*3600);
//$infostartw=dol_getdate($daystartw);
//$infoendw  =dol_getdate($dayendw);
//$datestartw=dol_mktime(0,0,0,$infostartw["mon"],$infostartw["mday"],$infostartw["year"]);
//$dateendw=dol_mktime(23,59,59,$infoendw["mon"],$infoendw["mday"],$infoendw["year"]);
//$datestartm=dol_mktime(0,0,0,$info["mon"],1,$info["year"]);
//$dateendm=dol_mktime(23,59,59,$info["mon"],30,$info["year"]);
//$datestarty=dol_mktime(0,0,0,1,1,$info["year"]);
//$dateendy=dol_mktime(23,59,59,12,31,$info["year"]);
////print time()." - ".gmtime().'<br>';
////print dol_print_date(mktime(0,0,0,1,1,1970),'dayhour')." - ".dol_print_date(gmmktime(0,0,0,1,1,1970),'dayhour').'<br>';
////print dol_print_date($datestartw,'dayhour')." - ".dol_print_date($now,'dayhour')." - ".dol_print_date($dateendw,'dayhour').'<br>';
////print dol_print_date($datestartm,'dayhour')." - ".dol_print_date($now,'dayhour')." - ".dol_print_date($dateendm,'dayhour').'<br>';
////print dol_print_date($datestarty,'dayhour')." - ".dol_print_date($now,'dayhour')." - ".dol_print_date($dateendy,'dayhour').'<br>';
////print 'xx '.dolibarr_date('Y-m-d H:i:s',$dateendy);
////print ' zz '.dol_print_date($dateendy,'dayhour');
//
///* Affichage de la liste des projets de la semaine */
//print '<table class="noborder" width="100%">';
//print '<tr class="liste_titre">';
//print '<td width="50%">Activit&eacute; sur les projets cette semaine</td>';
//print '<td width="50%" align="right">'.$langs->trans("Hours").'</td>';
//print "</tr>\n";
//
//$sql = "SELECT p.title, p.rowid, sum(tt.task_duration) as total";
//if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
//$sql .= " FROM ".MAIN_DB_PREFIX."Synopsis_projet_view as p";
//$sql .= " , ".MAIN_DB_PREFIX."projet_task as t";
//$sql .= " , ".MAIN_DB_PREFIX."projet_task_time as tt";
//if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
//$sql .= " WHERE t.fk_projet = p.rowid";
//if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND p.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
//$sql .= " AND tt.fk_task = t.rowid";
//$sql .= " AND task_date >= '".$db->idate($datestartw)."' AND task_date <= '".$db->idate($dateendw)."'";
//$sql .= " GROUP BY p.rowid";
//dol_syslog("Index: sql=".$sql);
//$resql = $db->query($sql);
//if ( $resql )
//{
//  $num = $db->num_rows($resql);
//  $i = 0;
//
//  while ($i < $num)
//    {
//      $obj = $db->fetch_object( $resql);
//      $var=!$var;
//      print "<tr $bc[$var]>";
//      print '<td><a href="'.DOL_URL_ROOT.'/synopsisprojet/tasks/card.php?id='.$obj->rowid.'">'.$obj->title.'</a></td>';
//      print '<td align="right">'.sec2hour($obj->total).'</td>';
//      print "</tr>\n";
//      $i++;
//    }
//
//  $db->free($resql);
//}
//else
//{
//  dol_print_error($db);
//}
//print "</table><br />";
//
///* Affichage de la liste des projets du mois */
//print '<table class="noborder" width="100%">';
//print '<tr class="liste_titre">';
//print '<td width="50%">'.$langs->trans("Project").' ce mois : '.strftime("%B %Y", $now).'</td>';
//print '<td width="50%" align="right">'.$langs->trans("Hours").'</td>';
//print "</tr>\n";
//
//$sql = "SELECT p.title, p.rowid, sum(tt.task_duration) as total";
//if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
//$sql .= " FROM ".MAIN_DB_PREFIX."Synopsis_projet_view as p";
//$sql .= " , ".MAIN_DB_PREFIX."projet_task as t";
//$sql .= " , ".MAIN_DB_PREFIX."projet_task_time as tt";
//if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
//$sql .= " WHERE t.fk_projet = p.rowid";
//if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND p.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
//$sql .= " AND tt.fk_task = t.rowid";
//$sql .= " AND task_date >= '".$db->idate($datestartm)."' AND task_date <= '".$db->idate($dateendm)."'";
//$sql .= " GROUP BY p.rowid";
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
//      $obj = $db->fetch_object($resql);
//      $var=!$var;
//      print "<tr $bc[$var]>";
//      print '<td><a href="'.DOL_URL_ROOT.'/synopsisprojet/tasks/card.php?id='.$obj->rowid.'">'.$obj->title.'</a></td>';
//      print '<td align="right">'.sec2hour($obj->total).'</td>';
//      print "</tr>\n";
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
///* Affichage de la liste des projets du mois */
//print '<br /><table class="noborder" width="100%">';
//print '<tr class="liste_titre">';
//print '<td width="50%">'.$langs->trans("Project").' cette ann&eacute;e : '.strftime("%Y", $now).'</td>';
//print '<td width="50%" align="right">'.$langs->trans("Hours").'</td>';
//print "</tr>\n";
//
//$sql = "SELECT p.title, p.rowid, sum(tt.task_duration) as total";
//if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
//$sql .= " FROM ".MAIN_DB_PREFIX."Synopsis_projet_view as p";
//$sql .= " , ".MAIN_DB_PREFIX."projet_task as t";
//$sql .= " , ".MAIN_DB_PREFIX."projet_task_time as tt";
//if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
//$sql .= " WHERE t.fk_projet = p.rowid";
//if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND p.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
//$sql .= " AND tt.fk_task = t.rowid";
//$sql .= " AND task_date >= '".$db->idate($datestarty)."' AND task_date <= '".$db->idate($dateendy)."'";
//$sql .= " GROUP BY p.rowid";
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
//      $obj = $db->fetch_object($resql);
//      $var=!$var;
//      print "<tr $bc[$var]>";
//      print '<td><a href="'.DOL_URL_ROOT.'/synopsisprojet/tasks/card.php?id='.$obj->rowid.'">'.$obj->title.'</a></td>';
//      print '<td align="right">'.sec2hour($obj->total).'</td>';
//      print "</tr>\n";
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
//print "<table><tr><td>";
//print "<div id='chart3'></div>";
//print "<td>";
//print "<div id='chart2'></div>";
//print "</td></tr></table>";
//
//$db->close();
llxFooter('$Date: 2008/04/28 22:34:41 $ - $Revision: 1.11 $');
//function sec2time($sec){
//    $returnstring = " ";
//    $days = intval($sec/86400);
//    $hours = intval ( ($sec/3600) - ($days*24));
//    $minutes = intval( ($sec - (($days*86400)+ ($hours*3600)))/60);
//    $seconds = $sec - ( ($days*86400)+($hours*3600)+($minutes * 60));
//
//    $returnstring .= ($days)?(($days == 1)? "1 j":$days."j"):"";
//    $returnstring .= ($days && $hours && !$minutes && !$seconds)?"":"";
//    $returnstring .= ($hours)?( ($hours == 1)?" 1h":" " .$hours."h"):"";
//    $returnstring .= (($days || $hours) && ($minutes && !$seconds))?"  ":" ";
//    $returnstring .= ($minutes)?( ($minutes == 1)?" 1 min":" ".$minutes."min"):"";
//    //$returnstring .= (($days || $hours || $minutes) && $seconds)?" et ":" ";
//    //$returnstring .= ($seconds)?( ($seconds == 1)?"1 second":"$seconds seconds"):"";
//    return ($returnstring);
//}
//
//function sec2hour($sec){
//    $days=false;
//    $returnstring = " ";
//    $hours = intval ( ($sec/3600) );
//    $minutes = intval( ($sec - ( ($hours*3600)))/60);
//    $seconds = $sec - ( ($hours*3600)+($minutes * 60));
//
//    $returnstring .= ($days)?(($days == 1)? "1 j":$days."j"):"";
//    $returnstring .= ($days && $hours && !$minutes && !$seconds)?"":"";
//    $returnstring .= ($hours)?( ($hours == 1)?" 1h":" " .$hours."h"):"";
//    $returnstring .= (($days || $hours) && ($minutes && !$seconds))?"  ":" ";
//    $returnstring .= ($minutes)?( ($minutes == 1)?" 1 min":" ".$minutes."min"):"";
//    //$returnstring .= (($days || $hours || $minutes) && $seconds)?" et ":" ";
//    //$returnstring .= ($seconds)?( ($seconds == 1)?"1 second":"$seconds seconds"):"";
//    return ($returnstring);
//}

print_projecttasks_array($db,$socid,$projectsListId,$mine);


/* Affichage de la liste des projets d'aujourd'hui */
print '<br><table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="50%">'.$langs->trans('Today').'</td>';
print '<td width="50%" align="right">'.$langs->trans("Time").'</td>';
print "</tr>\n";

$sql = "SELECT p.rowid, p.ref, p.title, SUM(tt.task_duration) as nb";
$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
$sql.= ", ".MAIN_DB_PREFIX."projet_task as t";
$sql.= ", ".MAIN_DB_PREFIX."projet_task_time as tt";
$sql.= " WHERE t.fk_projet = p.rowid";
$sql.= " AND p.entity = ".$conf->entity;
$sql.= " AND tt.fk_task = t.rowid";
$sql.= " AND tt.fk_user = ".$user->id;
$sql.= " AND date_format(task_date,'%y-%m-%d') = '".strftime("%y-%m-%d",$now)."'";
$sql.= " AND p.rowid in (".$projectsListId.")";
$sql.= " GROUP BY p.rowid, p.ref, p.title";

$resql = $db->query($sql);
if ( $resql )
{
	$var=true;
	$total=0;

	while ($row = $db->fetch_object($resql))
	{
		$var=!$var;
		print "<tr ".$bc[$var].">";
		print '<td>';
		$projectstatic->id=$row->rowid;
		$projectstatic->ref=$row->ref;
		print $projectstatic->getNomUrl(1);
		print '</td>';
		print '<td align="right">'.convertSecondToTime($row->nb).'</td>';
		print "</tr>\n";
		$total += $row->nb;
	}

	$db->free($resql);
}
else
{
	dol_print_error($db);
}
print '<tr class="liste_total">';
print '<td>'.$langs->trans('Total').'</td>';
print '<td align="right">'.convertSecondToTime($total).'</td>';
print "</tr>\n";
print "</table>";

// TODO Do not use date_add function to be compatible with all database
if ($db->type != 'pgsql')
{

/* Affichage de la liste des projets d'hier */
print '<br><table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans('Yesterday').'</td>';
print '<td align="right">'.$langs->trans("Time").'</td>';
print "</tr>\n";

$sql = "SELECT p.rowid, p.ref, p.title, sum(tt.task_duration) as nb";
$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
$sql.= ", ".MAIN_DB_PREFIX."projet_task as t";
$sql.= ", ".MAIN_DB_PREFIX."projet_task_time as tt";
$sql.= " WHERE t.fk_projet = p.rowid";
$sql.= " AND p.entity = ".$conf->entity;
$sql.= " AND tt.fk_task = t.rowid";
$sql.= " AND tt.fk_user = ".$user->id;
$sql.= " AND date_format(date_add(task_date, INTERVAL 1 DAY),'%y-%m-%d') = '".strftime("%y-%m-%d",$now)."'";
$sql.= " AND p.rowid in (".$projectsListId.")";
$sql.= " GROUP BY p.rowid, p.ref, p.title";

$resql = $db->query($sql);
if ( $resql )
{
	$var=true;
	$total=0;

	while ($row = $db->fetch_object($resql))
	{
		$var=!$var;
		print "<tr ".$bc[$var].">";
		print '<td>';
		$projectstatic->id=$row->rowid;
		$projectstatic->ref=$row->ref;
		print $projectstatic->getNomUrl(1);
		print '</td>';
		print '<td align="right">'.convertSecondToTime($row->nb).'</td>';
		print "</tr>\n";
		$total += $row->nb;
	}

	$db->free($resql);
}
else
{
	dol_print_error($db);
}
print '<tr class="liste_total">';
print '<td>'.$langs->trans('Total').'</td>';
print '<td align="right">'.convertSecondToTime($total).'</td>';
print "</tr>\n";
print "</table>";

}


print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


// TODO Do not use week function to be compatible with all database
if ($db->type != 'pgsql')
{

/* Affichage de la liste des projets de la semaine */
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("ActivityOnProjectThisWeek").'</td>';
print '<td align="right">'.$langs->trans("Time").'</td>';
print "</tr>\n";

$sql = "SELECT p.rowid, p.ref, p.title, SUM(tt.task_duration) as nb";
$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
$sql.= " , ".MAIN_DB_PREFIX."projet_task as t";
$sql.= " , ".MAIN_DB_PREFIX."projet_task_time as tt";
$sql.= " WHERE t.fk_projet = p.rowid";
$sql.= " AND p.entity = ".$conf->entity;
$sql.= " AND tt.fk_task = t.rowid";
$sql.= " AND tt.fk_user = ".$user->id;
$sql.= " AND week(task_date) = '".strftime("%W",time())."'";
$sql.= " AND p.rowid in (".$projectsListId.")";
$sql.= " GROUP BY p.rowid, p.ref, p.title";

$resql = $db->query($sql);
if ( $resql )
{
	$total = 0;
	$var=true;

	while ($row = $db->fetch_object($resql))
	{
		$var=!$var;
		print "<tr ".$bc[$var].">";
		print '<td>';
		$projectstatic->id=$row->rowid;
		$projectstatic->ref=$row->ref;
		print $projectstatic->getNomUrl(1);
		print '</td>';
		print '<td align="right">'.convertSecondToTime($row->nb).'</td>';
		print "</tr>\n";
		$total += $row->nb;
	}

	$db->free($resql);
}
else
{
	dol_print_error($db);
}
print '<tr class="liste_total">';
print '<td>'.$langs->trans('Total').'</td>';
print '<td align="right">'.convertSecondToTime($total).'</td>';
print "</tr>\n";
print "</table><br>";

}

/* Affichage de la liste des projets du mois */
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("ActivityOnProjectThisMonth").': '.dol_print_date($now,"%B %Y").'</td>';
print '<td align="right">'.$langs->trans("Time").'</td>';
print "</tr>\n";

$sql = "SELECT p.rowid, p.ref, p.title, SUM(tt.task_duration) as nb";
$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
$sql.= ", ".MAIN_DB_PREFIX."projet_task as t";
$sql.= ", ".MAIN_DB_PREFIX."projet_task_time as tt";
$sql.= " WHERE t.fk_projet = p.rowid";
$sql.= " AND p.entity = ".$conf->entity;
$sql.= " AND tt.fk_task = t.rowid";
$sql.= " AND tt.fk_user = ".$user->id;
$sql.= " AND date_format(task_date,'%y-%m') = '".strftime("%y-%m",$now)."'";
$sql.= " AND p.rowid in (".$projectsListId.")";
$sql.= " GROUP BY p.rowid, p.ref, p.title";

$resql = $db->query($sql);
if ( $resql )
{
	$var=false;

	while ($row = $db->fetch_object($resql))
	{
		print "<tr ".$bc[$var].">";
		print '<td>';
		$projectstatic->id=$row->rowid;
		$projectstatic->ref=$row->ref;
		print $projectstatic->getNomUrl(1);
		print '</td>';
		print '<td align="right">'.convertSecondToTime($row->nb).'</td>';
		print "</tr>\n";
		$var=!$var;
	}
	$db->free($resql);
}
else
{
	dol_print_error($db);
}
print '<tr class="liste_total">';
print '<td>'.$langs->trans('Total').'</td>';
print '<td align="right">'.convertSecondToTime($total).'</td>';
print "</tr>\n";
print "</table>";

/* Affichage de la liste des projets de l'annee */
print '<br><table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("ActivityOnProjectThisYear").': '.strftime("%Y", $now).'</td>';
print '<td align="right">'.$langs->trans("Time").'</td>';
print "</tr>\n";

$sql = "SELECT p.rowid, p.ref, p.title, SUM(tt.task_duration) as nb";
$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
$sql.= ", ".MAIN_DB_PREFIX."projet_task as t";
$sql.= ", ".MAIN_DB_PREFIX."projet_task_time as tt";
$sql.= " WHERE t.fk_projet = p.rowid";
$sql.= " AND p.entity = ".$conf->entity;
$sql.= " AND tt.fk_task = t.rowid";
$sql.= " AND tt.fk_user = ".$user->id;
$sql.= " AND YEAR(task_date) = '".strftime("%Y",$now)."'";
$sql.= " AND p.rowid in (".$projectsListId.")";
$sql.= " GROUP BY p.rowid, p.ref, p.title";

$var=false;
$resql = $db->query($sql);
if ( $resql )
{
	while ($row = $db->fetch_object($resql))
	{
		print "<tr ".$bc[$var].">";
		print '<td>';
		$projectstatic->id=$row->rowid;
		$projectstatic->ref=$row->ref;
		print $projectstatic->getNomUrl(1);
		print '</td>';
		print '<td align="right">'.convertSecondToTime($row->nb).'</td>';
		print "</tr>\n";
		$var=!$var;
	}
	$db->free($resql);
}
else
{
	dol_print_error($db);
}
print '<tr class="liste_total">';
print '<td>'.$langs->trans('Total').'</td>';
print '<td align="right">'.convertSecondToTime($total).'</td>';
print "</tr>\n";
print "</table>";


print '</div></div></div>';


llxFooter();

$db->close();
