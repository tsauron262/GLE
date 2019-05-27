<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011      François Legastelois <flegastelois@teclib.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *   	\file       htdocs/holiday/month_report.php
 *		\ingroup    holiday
 *		\brief      Monthly report of paid holiday.
 */

if (!isset($user))
    require('../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/synopsisholiday/common.inc.php';

// Protection if external user
if ($user->societe_id > 0) accessforbidden();


// Si l'utilisateur n'a pas le droit de lire cette page
if(!$user->rights->holiday->read_all) accessforbidden();



/*
 * View
 */

$html = new Form($db);
$htmlother = new FormOther($db);
$holidaystatic = new SynopsisHoliday($db);

llxHeader(array(),$langs->trans('CPTitreMenu'));

$cp = new SynopsisHoliday($db);

$mode = GETPOST('mode');
$month = GETPOST('month_start');
$year = GETPOST('year_start');

if(empty($month)) {
	$month = date('n');
}
if(empty($year)) {
	$year = date('Y');
}


if(isset($_REQUEST['dateDebut']))
    $dateDebut = str_replace('/', '-', $_REQUEST['dateDebut']);
else if($month==1)
    $dateDebut = "".($year-1) ."/12/25";
else
    $dateDebut = $year."/" . ($month-1) ."/" ."25";

if(isset($_REQUEST['dateFin']))
    $dateFin = str_replace('/', '-', $_REQUEST['dateFin']);
else
    $dateFin = $year."-". ($month ) ."-" ."24";

$sql = "SELECT cp.rowid, cp.description, cp.fk_user, cp.fk_group, cp.date_debut, cp.date_fin, cp.halfday, cp.type_conges";
$sql.= " FROM " . MAIN_DB_PREFIX . "holiday cp";
$sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "user u ON cp.fk_user = u.rowid";
$sql.= " WHERE cp.statut = 6";	// Approved
// TODO Use BETWEEN instead of date_format
$sql.= " AND (( cp.date_debut >= '".date('Y-m-d', strtotime($dateDebut))."' AND  cp.date_debut <= '".date('Y-m-d', strtotime($dateFin))."') OR ( cp.date_fin >= '".date('Y-m-d', strtotime($dateDebut))."' AND  cp.date_fin <= '".date('Y-m-d', strtotime($dateFin))."') OR ( cp.date_fin >= '".date('Y-m-d', strtotime($dateFin))."' AND  cp.date_debut <= '".date('Y-m-d', strtotime($dateDebut))."'))";
$sql.= " ORDER BY u.lastname,cp.date_debut";

$result  = $db->query($sql);
$num = $db->num_rows($result);

print_fiche_titre($langs->trans('MenuReportMonth'));

print '<div class="tabBar">';

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">'."\n";

print $langs->trans('Month').': ';
print $htmlother->select_month($month, 'month_start').' ';
print $htmlother->select_year($year,'year_start',1,10,3);
$form = new Form($db);




//print "<select name='mode'><option value='1'>1</option><option value='2' ".($mode == 2 ? "selected" : "").">2</option></select>";

print '<input type="submit" value="'.$langs->trans("Refresh").'" class="button" />';

print '</form>';


print '<form>';

$html->select_date(strtotime($dateDebut), "dateDebut"); 
$html->select_date(strtotime($dateFin), "dateFin"); 


print '<input type="submit" value="'.$langs->trans("Refresh").'" class="button" />';

print '</form>';


print '<br>';

$var=true;
print '<table class="noborder" width="40%;">';

print '<tr class="liste_titre">';
print '<td>'.$langs->trans('Ref').'</td>';
print '<td>'.$langs->trans('Employee').'</td>';
print '<td>Type</td>';
print '<td>Infos</td>';
print '<td>'.$langs->trans('DateDebCP').'</td>';
print '<td>'.$langs->trans('DateFinCP').'</td>';
print '<td align="right">'.$langs->trans('nbJours').'</td>';
print '</tr>';

if($num == '0') {

	print '<tr class="pair">';
	print '<td colspan="5">'.$langs->trans('None').'</td>';
	print '</tr>';

} else {

	$langs->load('users');

	while ($holiday = $db->fetch_array($result))
	{
            $newNom = "";
                if($holiday['fk_user'] > 0){
                    $user = new User($db);
                    $user->fetch($holiday['fk_user']);
                    $newNom = $user->getNomUrl(1);
                }
                elseif($holiday['fk_group'] > 0){
                    include_once(DOL_DOCUMENT_ROOT."/user/class/usergroup.class.php");
                    $userG = new UserGroup($db);
                    $userG->fetch($holiday['fk_group']);
                    $newNom = $userG->getNomUrl(1);
                }
		$var=!$var;

                if ($mode != 2){
                    $holidaystatic->id=$holiday['rowid'];
                    $holidaystatic->ref=$holiday['rowid'];
                    
                    
//die($holiday['date_debut']."   ".date('Y-m-d', $debut));
if(strtotime($holiday['date_debut']) < strtotime(date('Y-m-d', strtotime($dateDebut))))
    $holiday['date_debut'] = date('Y-m-d', strtotime($dateDebut));
if(strtotime($holiday['date_fin']) > strtotime(date('Y-m-d', strtotime($dateFin))))
    $holiday['date_fin'] = date('Y-m-d', strtotime($dateFin));

                    $start_date=$db->jdate($holiday['date_debut']);
                    $end_date=$db->jdate($holiday['date_fin']);
                    $start_date_gmt=$db->jdate($holiday['date_debut'],1);
                    $end_date_gmt=$db->jdate($holiday['date_fin'],1);
                    
                    
                    
                    
		$nbopenedday=num_open_dayUser($holiday['fk_user'], $start_date_gmt, $end_date_gmt, 0, 1, $holiday['halfday']);
                }

                $type = $holiday['type_conges'];
                if($nom != $newNom)
                    $nom = $nomUser = $newNom;
                else
                    $nomUser = "----";
		print '<tr '.$bc[$var].'>';
                if ($mode != 2)
		print '<td>'.$holidaystatic->getNomUrl(1).'</td>';
		print '<td>'.$nomUser.'</td>';
		print '<td>'.($type == 0? "congés" : ($type == 1? "absence exceptionnelle" : ($type == 2? "rtt" : ""))).'</td>';
		print '<td>'.$holiday['description'].'</td>';
                if ($mode != 2){
		print '<td>'.dol_print_date($start_date,'day');
		print '</td>';
		print '<td>'.dol_print_date($end_date,'day');
		print '</td>';
                }
		print '<td align="right">';
		print $nbopenedday;
		print '</td>';
		print '</tr>';
	}
}
print '</table>';
print '</div>';

// Fin de page
llxFooter();

$db->close();
