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
 * $Id: index.php,v 1.9 2008/03/01 01:26:51 eldy Exp $
 * $Source: /cvsroot/dolibarr/dolibarr/htdocs/projet/tasks/index.php,v $
 */

/**
   \file       htdocs/projet/tasks/index.php
   \ingroup    projet
   \brief      Page des taches du module projet
   \version    $Revision: 1.9 $
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/projet/class/project.class.php');
if (!$user->rights->synopsisprojet->lire) accessforbidden();

// Securite acces client
if ($user->societe_id > 0)
{
  $action = '';
  $socid = $user->societe_id;
}

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, "", $num);

// Show description of content
if ($mine) print $langs->trans("MyProjectsDesc").'<br><br>';
else
{
	if ($user->rights->projet->all->lire && ! $socid) print $langs->trans("ProjectsDesc").'<br><br>';
	else print $langs->trans("ProjectsPublicDesc").'<br><br>';
}

// Get list of project id allowed to user (in a string list separated by coma)
$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,$mine,1,$socid);

// Get list of tasks in tasksarray and taskarrayfiltered
// We need all tasks (even not limited to a user because a task to user can have a parent that is not affected to him).
$tasksarray=$taskstatic->getTasksArray(0, 0, $projectstatic->id, $socid, 0, $search_project);
// We load also tasks limited to a particular user
$tasksrole=($mine ? $taskstatic->getUserRolesForProjectsOrTasks(0,$user,$projectstatic->id,0) : '');

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="mode" value="'.GETPOST('mode').'">';

print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Project").'</td>';
print '<td width="80">'.$langs->trans("RefTask").'</td>';
print '<td>'.$langs->trans("LabelTask").'</td>';
print '<td align="center">'.$langs->trans("DateStart").'</td>';
print '<td align="center">'.$langs->trans("DateEnd").'</td>';
print '<td align="center">'.$langs->trans("PlannedWorkload");
// TODO Replace 86400 and 7 to take account working hours per day and working day per weeks
//print '<br>('.$langs->trans("DelayWorkHour").')';
print '</td>';
print '<td align="right">'.$langs->trans("ProgressDeclared").'</td>';
print '<td align="right">'.$langs->trans("TimeSpent").'</td>';
print '<td align="right">'.$langs->trans("ProgressCalculated").'</td>';
print "</tr>\n";

print '<tr class="liste_titre">';
print '<td class="liste_titre">';
print '<input type="text" class="flat" name="search_project" value="'.$search_project.'" size="8">';
print '</td>';
print '<td class="liste_titre" colspan="7">';
print '&nbsp;';
print '</td>';
print '<td class="liste_titre" align="right"><input class="liste_titre" type="image" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'"></td>';
print "</tr>\n";

if (count($tasksarray) > 1000)
{
	print '<tr '.$bc[0].'>';
	print '<td colspan="9">';
	print $langs->trans("TooManyDataPleaseUseMoreFilters");
	print '</td></tr>';
}
else
{
	// Show all lines in taskarray (recursive function to go down on tree)
	$j=0; $level=0;
	$nboftaskshown=projectLinesa($j, 0, $tasksarray, $level, true, 1, $tasksrole, $projectsListId, 0);
}

print "</table>";

print '</form>';

print '</div>';

/*
 * Actions
 */
if ($user->rights->projet->creer)
{
	print '<div class="tabsAction">';
	print '<a class="butAction" href="'.DOL_URL_ROOT.'/projet/tasks.php?action=create">'.$langs->trans('AddTask').'</a>';
	print '</div>';
}


$jQueryDashBoardPath = DOL_URL_ROOT.'/Synopsis_Common/jquery/dashboard/';

$js = '
    <script>var DOL_URL_ROOT="'.DOL_URL_ROOT.'";</script>
    <script>var DOL_DOCUMENT_ROOT="'.DOL_DOCUMENT_ROOT.'";</script>
    <script type="text/javascript" src="'.$jQueryDashBoardPath.'jquery.dashboard.js"></script>
    <link rel="stylesheet" type="text/css" href="'.$jQueryDashBoardPath.'dashboard.css" />

    <script type="text/javascript" src="'.$jQueryDashBoardPath.'dashboard.js"></script>
    <link rel="stylesheet" type="text/css" href="'.$jQueryDashBoardPath.'demo.css" />
    <script type="text/javascript">var userid='.$user->id.';</script>
    <script type="text/javascript">var dashtype="37";</script>

';

    llxHeader($js,$langs->trans("Projects"),1);




    print '<div class="titre">Mon tableau de bord - '.$langs->trans('ProjectsArea').' - t&acirc;ches</div>';
    print "<br/>";
    print "<br/>";
    print "<div style='padding: 5px 10px; width: 270px;' class='butAction ui-state-default ui-widget-header ui-corner-all'><em><span style='float: left; margin: -1px 3px 0px 0px' class='ui-icon ui-icon-info'></span><a href='#' onClick='addWidget()'>Ajouter des widgets &agrave; votre tableau de bord.</a></em></div>";
    print "<br/>";
    print '<div id="dashboard">';
    print '  You need javascript to use the dashboard.';
    print '</div>';

$db->close();

llxFooter('$Date: 2008/06/19 08:50:59 $ - $Revision: 1.60 $');


//llxHeader("",$langs->trans("Projects"),"Projet");
//
//print_fiche_titre($langs->trans("ProjectsArea"));
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
//if (!$user->rights->societe->client->voir && !$socid) $sql .= ", llxsociete_commerciaux as sc";
//$sql .= " WHERE t.fk_projet = p.rowid";
//if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND p.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
//
//if ($socid)
//{
//  $sql .= " AND s.rowid = ".$socid;
//}
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
//      print '<td><a href="'.DOL_URL_ROOT.'/projet/tasks/card.php?id='.$row[1].'">'.$row[0].'</a></td>';
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
///*
// *
// * Affichage de la liste des projets
// *
// */
//print '<table class="noborder" width="100%">';
//print '<tr class="liste_titre">';
//print_liste_field_titre($langs->trans("Company"),"index.php","s.nom","","","",$sortfield,$sortorder);
//print '<td>Nb heures pr&eacute;vu</td>';
//print '<td>Nb heures effective</td>';
//print "</tr>\n";
//
//$sql = "SELECT p.title, p.rowid, sum(tt.task_duration), ifnull(sum(".MAIN_DB_PREFIX."Synopsis_projet_task_time_effective.task_duration_effective),0)";
//if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
//$sql .= " FROM ".MAIN_DB_PREFIX."Synopsis_projet_view as p";
//$sql .= " , ".MAIN_DB_PREFIX."projet_task as t";
//$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."Synopsis_projet_task_time_effective ON ".MAIN_DB_PREFIX."Synopsis_projet_task_time_effective.fk_task = t.rowid";
//$sql .= " , ".MAIN_DB_PREFIX."projet_task_time as tt";
//if (!$user->rights->societe->client->voir && !$socid) $sql .= ", llxsociete_commerciaux as sc";
//$sql .= " WHERE t.fk_projet = p.rowid";
//$sql .= " AND tt.fk_task = t.rowid";
//if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND p.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
//
//if ($socid)
//{
//  $sql .= " AND s.rowid = ".$socid;
//}
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
//      print '<td><a href="'.DOL_URL_ROOT.'/projet/tasks/card.php?id='.$row[1].'">'.$row[0].'</a></td>';
//      print '<td>'.sec2hour($row[2]).'</td>';
//      print '<td>'.sec2hour($row[3]).'</td>';
//      print "</tr>\n";
//
//      $i++;
//    }
//print '</table>';
//print '</table>';
//print "<br/>";
////
////require_once(DOL_DOCUMENT_ROOT.'/projet/class/project.class.php');
////$projet = new Project($db);
//
//$requete = "SELECT *
//              FROM ".MAIN_DB_PREFIX."Synopsis_projet_view
//             WHERE fk_statut = 0";
//$sql = $db->query($requete);
//print "<table border = 1 style='border-collapse: collapse; text-align: center; width: 100%;'>";
//print "<tr><th  style='padding: 8px;'>Nom du projet</th><th  style='padding: 8px;'>Message</th><th  style='padding: 8px;'>D&eacute;passement</th>";
//
//
//while ($res = $db->fetch_object($sql))
//{
//    $projet = new Project($db);
//    $projet->fetch($res->rowid);
//    $requete1 = "SELECT t.title,
//                        ifnull((SELECT SUM(task_duration_effective) FROM ".MAIN_DB_PREFIX."Synopsis_projet_task_time_effective WHERE fk_task = t.rowid),0) as sduration_effective,
//                        ifnull((SELECT SUM(task_duration) FROM ".MAIN_DB_PREFIX."projet_task_time WHERE fk_task = t.rowid),0) as sduration,
//                        t.progress, t.rowid
//                   FROM ".MAIN_DB_PREFIX."projet_task as t
//                  WHERE t.fk_projet = ".$res->rowid;
////1 affiche les taches warning :> débordement , diff % avancement et % de temps important
//    $sql1 = $db->query($requete1);
//    $html = "";
//    $cnt=0;
//    while ($res1 = $db->fetch_object($sql1))
//    {
//        if ($res1->sduration_effective > $res1->sduration)
//        {
//            $html .= "<tr style='background-color: #FF0000; style='padding: 4px; color: #FFFFFF; font-weight: 900;'>";
//            $html .= "    <td style='padding: 4px;'>D&eacute;bordement de la t&acirc;che ".$projet->getTaskNomUrl($res1->rowid,1)  . "</td>";
//            $html .= "    <td style='padding: 4px;'>". sec2time($res1->sduration_effective - $res1->sduration) . "</td>";
//            $html .= "</tr>" ;
//            $cnt ++;
//        }
//        //warning
//        $percAccompTime=0;
//        if ($res1->sduration != 0)
//        {
//            $percAccompTime = $res1->sduration_effective / $res1->sduration;
//        }
////        print '<tr><td>'.$percAccompTime.'<td>'.$res1->progress;
//        $delta = abs($percAccompTime*100 - $res1->progress);
//        if ($delta > 20)
//        {
//            $html .= "<tr style='background-color: #FFFF99; style='padding: 4px; color: #FFFFFF; font-weight: 900;'>";
//            $html .= "    <td style='padding: 4px;'>Warning ".$projet->getTaskNomUrl($res1->rowid,1) . "</td>";
//            $html .= "    <td style='padding: 4px;'>". $delta . "%</td>";
//            $html .= "</tr>" ;
//            $cnt ++;
//        }
//
//
//
//    }
//    $cnt++;
//    if ($cnt > 1)
//    {
//        print "<tr><td colspan=1 rowspan=$cnt>".$projet->getNomUrl(1);
//        print $html;
//    }
//
//}
//print "</table>";
//
////2                            :> tache en retard % rapport à la date prévu
//
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
//$db->close();
//
//llxFooter("<em>Derni&egrave;re modification $Date: 2008/03/01 01:26:51 $ r&eacute;vision $Revision: 1.9 $</em>");
//
//
//
//function sec2time($sec){
//    if (!is_numeric($sec))
//    {
//        $sec = 0;
//    }
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
//    if (!is_numeric($sec))
//    {
//        $sec = 0;
//    }
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

?>
