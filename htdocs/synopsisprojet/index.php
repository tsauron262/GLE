<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2006 Regis Houssin        <regis@dolibarr.fr>
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
 *
 * $Id: index.php,v 1.41 2008/06/18 20:01:02 hregis Exp $
 * $Source: /cvsroot/dolibarr/dolibarr/htdocs/synopsisprojet/index.php,v $
 */

/**
        \file       htdocs/synopsisprojet/index.php
        \ingroup    projet
        \brief      Page d'accueil du module projet
        \version    $Revision: 1.41 $
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/project.lib.php");
$langs->load("synopsisproject@synopsisprojet");

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
//$sql.= " ".MAIN_DB_PREFIX."Synopsis_projet_view as p";
//$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task as t ON p.rowid = t.fk_projet";
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
//      print '<td nowrap="nowrap"><a href="'.DOL_URL_ROOT.'/synopsisprojet/card.php?id='.$row[1].'">'.img_object($langs->trans("ShowProject"),"project")." ".$row[0].'</a></td>';
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
//$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."Synopsis_projet_view as p";
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
//      print '<td nowrap="nowrap"><a href="'.DOL_URL_ROOT.'/synopsisprojet/liste.php?socid='.$row[1].'">'.img_object($langs->trans("ShowCompany"),"company")." ".$row[0].'</a></td>';
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



print '<div class="fichecenter"><div class="fichethirdleft">';


print_projecttasks_array($db, $form,$socid,$projectsListId);


print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans("ThirdParties"),$_SERVER["PHP_SELF"],"s.nom","","","",$sortfield,$sortorder);
print_liste_field_titre($langs->trans("NbOfProjects"),"","","","",'align="right"',$sortfield,$sortorder);
print "</tr>\n";

$sql = "SELECT count(p.rowid) as nb";
$sql.= ", s.nom, s.rowid as socid";
$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on p.fk_soc = s.rowid";
$sql.= " WHERE p.entity = ".$conf->entity;
if ($mine || empty($user->rights->projet->all->lire)) $sql.= " AND p.rowid IN (".$projectsListId.")";
if ($socid)	$sql.= "  AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".$socid.")";
$sql.= " GROUP BY s.nom, s.rowid";

$var=true;
$resql = $db->query($sql);
if ( $resql )
{
	$num = $db->num_rows($resql);
	$i = 0;

	while ($i < $num)
	{
		$obj = $db->fetch_object($resql);
		$var=!$var;
		print "<tr ".$bc[$var].">";
		print '<td class="nowrap">';
		if ($obj->socid)
		{
			$socstatic->id=$obj->socid;
			$socstatic->nom=$obj->nom;
			print $socstatic->getNomUrl(1);
		}
		else
		{
			print $langs->trans("OthersNotLinkedToThirdParty");
		}
		print '</td>';
		print '<td align="right"><a href="'.DOL_URL_ROOT.'/synopsisprojet/liste.php?socid='.$obj->socid.'">'.$obj->nb.'</a></td>';
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


print '</div></div></div>';


// Tasks for all resources of all opened projects and time spent for each task/resource
print '<div class="fichecenter">';

$sql = "SELECT p.title, p.rowid as projectid, t.label, t.rowid as taskid, u.rowid as userid, t.planned_workload, t.dateo, t.datee, SUM(tasktime.task_duration) as timespent";
$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on p.fk_soc = s.rowid";
$sql.= " INNER JOIN ".MAIN_DB_PREFIX."projet_task as t on t.fk_projet = p.rowid";
$sql.= " INNER JOIN ".MAIN_DB_PREFIX."projet_task_time as tasktime on tasktime.fk_task = t.rowid";
$sql.= " INNER JOIN ".MAIN_DB_PREFIX."user as u on tasktime.fk_user = u.rowid";
$sql.= " WHERE p.entity = ".$conf->entity;
if ($mine || ! $user->rights->projet->all->lire) $sql.= " AND p.rowid IN (".$projectsListId.")";
if ($socid)	$sql.= "  AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".$socid.")";
$sql.= " AND p.fk_statut=1";
$sql.= " GROUP BY p.title, p.rowid, t.label, t.rowid, u.rowid, t.planned_workload, t.dateo, t.datee";
$sql.= " ORDER BY u.rowid, t.dateo, t.datee";

$userstatic=new User($db);

dol_syslog('projet:index.php: affectationpercent sql='.$sql,LOG_DEBUG);
$resql = $db->query($sql);
if ( $resql )
{
	$num = $db->num_rows($resql);
	$i = 0;

	if ($num > (empty($conf->global->PROJECT_LIMIT_TASK_PROJECT_AREA)?1000:$conf->global->PROJECT_LIMIT_TASK_PROJECT_AREA))
	{
/*		print '<tr '.$bc[0].'>';
		print '<td colspan="9">';
		print $langs->trans("TooManyDataPleaseUseMoreFilters");
		print '</td></tr>';*/
	}
	else
	{
		print '<br>';

		print_fiche_titre($langs->trans("TimeSpent"),'','').'<br>';

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<th>'.$langs->trans('TaskRessourceLinks').'</th>';
		print '<th>'.$langs->trans('Projects').'</th>';
		print '<th>'.$langs->trans('Task').'</th>';
		print '<th>'.$langs->trans('DateStart').'</th>';
		print '<th>'.$langs->trans('DateEnd').'</th>';
		print '<th>'.$langs->trans('TimeSpent').'</th>';
		print '</tr>';

		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);
			$var=!$var;

			$username='';
			if ($obj->userid && $userstatic->id != $obj->userid)	// We have a user and it is not last loaded user
			{
				$result=$userstatic->fetch($obj->userid);
				if (! $result) $userstatic->id=0;
			}
			if ($userstatic->id) $username = $userstatic->getNomUrl(0,0);

			print "<tr ".$bc[$var].">";
			print '<td>'.$username.'</td>';
			print '<td><a href="'.DOL_URL_ROOT.'/synopsisprojet/card.php?id='.$obj->projectid.'">'.$obj->title.'</a></td>';
			print '<td><a href="'.DOL_URL_ROOT.'/synopsisprojet/tasks/task.php?id='.$obj->taskid.'&withproject=1">'.$obj->label.'</a></td>';
			print '<td>'.dol_print_date($db->jdate($obj->dateo)).'</td>';
			print '<td>'.dol_print_date($db->jdate($obj->datee)).'</td>';
			/* I disable this because information is wrong. This percent has no meaning for a particular resource. What do we want ?
			 * Percent of completion ?
			 * If we want to show completion, we must remove "user" into list,
			if (empty($obj->planned_workload)) {
				$percentcompletion = $langs->trans("Unknown");
			} else {
				$percentcompletion = intval($obj->task_duration*100/$obj->planned_workload);
			}*/
			print '<td><a href="'.DOL_URL_ROOT.'/synopsisprojet/tasks/time.php?id='.$obj->taskid.'&withproject=1">';
			//print $percentcompletion.' %';
			print convertSecondToTime($obj->timespent, 'all');
			print '</a></td>';
			print "</tr>\n";

			$i++;
		}

		print "</table>";
	}

	$db->free($resql);
}
else
{
	dol_print_error($db);
}

print '</div>';



llxFooter();

$db->close();
