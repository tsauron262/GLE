<?php

/* Copyright (C) 2011	Dimitri Mouillard	<dmouillard@teclib.com>
 * Copyright (C) 2013	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012	Regis Houssin		<regis.houssin@capnetworks.com>
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
 *   	\file       htdocs/holiday/index.php
 * 		\ingroup    holiday
 * 		\brief      List of holiday.
 */
if (!isset($user))
    require('../main.inc.php');
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT . '/synopsisholiday/common.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/usergroups.lib.php';

$langs->load('users');
$langs->load('holidays');

//echo '<pre>';
//print_r($user->rights->holiday);
//die();
// Protection if external user
if ($user->societe_id > 0)
    accessforbidden();

$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
$page = is_numeric($page) ? $page : 0;
$page = $page == -1 ? 0 : $page;

if (!$sortfield)
    $sortfield = "cp.rowid";
if (!$sortorder)
    $sortorder = "DESC";
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$id = GETPOST('id');
$holidaystatic = new SynopsisHoliday($db);

$search_ref = GETPOST('search_ref');
$month_create = GETPOST('month_create');
$year_create = GETPOST('year_create');
$type_conges = isset($_REQUEST['type_conges'])? $_REQUEST['type_conges'] : -1;
$month_start = GETPOST('month_start');
$year_start = GETPOST('year_start');
$month_end = GETPOST('month_end');
$year_end = GETPOST('year_end');
$search_employe = GETPOST('search_employe');
$search_valideur = GETPOST('search_valideur');
$search_statut = GETPOST('select_statut');

$droitAll = ((!empty($user->rights->holiday->write_all) && $user->rights->holiday->write_all) ||
        (!empty($user->rights->holiday->read_all) && $user->rights->holiday->read_all));
$isDrh = ($user->id == $holidaystatic->getConfCP('drhUserId'));
if ($isDrh || $droitAll) {
    $search_group = GETPOST('search_group');
    $showGroups = GETPOST('show_groups_cp');
}


$morefiltre = 'search_ref='.$search_ref.'&month_create='.$month_create.'&year_create='.$year_create.'&type_conges='.$type_conges.'&search_employe='.$search_employe.'&search_valideur='.$search_valideur.'&month_start='.$month_start.'&year_start='.$year_start.'&month_end='.$month_end.'&year_end='.$year_end.'&select_statut='.$search_statut.'';

/*
 * Actions
 */

// None

/*
 * View
 */

$holiday = new SynopsisHoliday($db);
$fuser = new User($db);

// Update sold
$holiday->updateSold();



if ((/*$search_valideur == $user->id || */isset($_REQUEST['myValid'])) && $holiday->getConfCP('drhUserId') == $user->id){
    $search_statut = 3;
    $search_valideur = null;
    $id2 = $user->id;
}
elseif (isset($_REQUEST['myValid'])){
    $search_valideur = $user->id;
    $search_statut = 2;
}

//deuxieme test pour le filtre
if($search_valideur > 0)
    $id2 = $search_valideur;



$max_year = 5;
$min_year = 10;
$filter = '';

llxHeader(array(), $langs->trans('CPTitreMenu'));

$order = $db->order($sortfield, $sortorder) . $db->plimit($conf->liste_limit + 1, $offset);

// WHERE
if (!empty($search_ref)) {
    $filter.= " AND cp.rowid LIKE '%" . $db->escape($search_ref) . "%'\n";
}


if ($type_conges > -1) {
    $filter.= " AND cp.type_conges = '" . $db->escape($type_conges) . "'\n";
}

// DATE START
if ($year_start > 0) {
    if ($month_start > 0) {
        $filter .= " AND (cp.date_debut BETWEEN '" . $db->idate(dol_get_first_day($year_start, $month_start, 1)) . "' AND '" . $db->idate(dol_get_last_day($year_start, $month_start, 1)) . "')";
        //$filter.= " AND date_format(cp.date_debut, '%Y-%m') = '$year_start-$month_start'";
    } else {
        $filter .= " AND (cp.date_debut BETWEEN '" . $db->idate(dol_get_first_day($year_start, 1, 1)) . "' AND '" . $db->idate(dol_get_last_day($year_start, 12, 1)) . "')";
        //$filter.= " AND date_format(cp.date_debut, '%Y') = '$year_start'";
    }
} else {
    if ($month_start > 0) {
        $filter.= " AND date_format(cp.date_debut, '%m') = '$month_start'";
    }
}

// DATE FIN
if ($year_end > 0) {
    if ($month_end > 0) {
        $filter .= " AND (cp.date_fin BETWEEN '" . $db->idate(dol_get_first_day($year_end, $month_end, 1)) . "' AND '" . $db->idate(dol_get_last_day($year_end, $month_end, 1)) . "')";
        //$filter.= " AND date_format(cp.date_fin, '%Y-%m') = '$year_end-$month_end'";
    } else {
        $filter .= " AND (cp.date_fin BETWEEN '" . $db->idate(dol_get_first_day($year_end, 1, 1)) . "' AND '" . $db->idate(dol_get_last_day($year_end, 12, 1)) . "')";
        //$filter.= " AND date_format(cp.date_fin, '%Y') = '$year_end'";
    }
} else {
    if ($month_end > 0) {
        $filter.= " AND date_format(cp.date_fin, '%m') = '$month_end'";
    }
}

// DATE CREATE
if ($year_create > 0) {
    if ($month_create > 0) {
        $filter .= " AND (cp.date_create BETWEEN '" . $db->idate(dol_get_first_day($year_create, $month_create, 1)) . "' AND '" . $db->idate(dol_get_last_day($year_create, $month_create, 1)) . "')";
        //$filter.= " AND date_format(cp.date_create, '%Y-%m') = '$year_create-$month_create'";
    } else {
        $filter .= " AND (cp.date_create BETWEEN '" . $db->idate(dol_get_first_day($year_create, 1, 1)) . "' AND '" . $db->idate(dol_get_last_day($year_create, 12, 1)) . "')";
        //$filter.= " AND date_format(cp.date_create, '%Y') = '$year_create'";
    }
} else {
    if ($month_create > 0) {
        $filter.= " AND date_format(cp.date_create, '%m') = '$month_create'";
    }
}

// EMPLOYE
if (empty($showGroups) && !$showGroups) {
    if (!empty($search_employe) && $search_employe != -1) {
        $filter.= " AND cp.fk_user = '" . $db->escape($search_employe) . "'\n";
    }
}

// GROUPE
else if (!empty($search_group) && $search_group != -1) {
    $filter.= " AND cp.fk_group = '" . $db->escape($search_group) . "'\n";
}

// VALIDEUR
if (!empty($search_valideur) && $search_valideur != -1) {
    $filter.= " AND cp.fk_validator = '" . $db->escape($search_valideur) . "'\n";
}

// STATUT
if (!empty($search_statut) && $search_statut != -1) {
    $filter.= " AND cp.statut = '" . $db->escape($search_statut) . "'\n";
}

/* * ***********************************
 * Fin des filtres de recherche
 * *********************************** */

// Récupération de l'ID de l'utilisateur
$user_id = $user->id;

if ($id > 0) {
    // Charge utilisateur edite
    $fuser->fetch($id);
    $fuser->getrights();
    $user_id = $fuser->id;
}
// Récupération des congés payés de l'utilisateur ou de tous les users
if ((!$droitAll && !$isDrh) || $id > 0) {
    $holiday_payes = $holiday->fetchByUser($user_id, $order, $filter);
} else {
    $holiday_payes = $holiday->fetchAll($order, $filter, $showGroups);
}
// Si erreur SQL
if ($holiday_payes == '-1') {
    print_fiche_titre($langs->trans('CPTitreMenu'));

    dol_print_error($db, $langs->trans('Error') . ' ' . $holiday->error);
    exit();
}

/* * ***********************************
 * Affichage du tableau des congés payés
 * *********************************** */

$var = true;
$num = count($holiday->holiday);
$form = new Form($db);
$formother = new FormOther($db);


if ($id2 > 0) {
    $fuser = new User($db);
    $fuser->fetch($id2);
}
if ($id > 0 || $id2 > 0) {
    $head = user_prepare_head($fuser);


    $title = $langs->trans("User");
    dol_fiche_head($head, 'paidholidaysRtt' . ($id2 > 0 ? "2" : ""), $title, 0, 'user');

    print '<table class="border" width="100%">';

    // Ref
    print '<tr><td width="25%" valign="top">' . $langs->trans("Ref") . '</td>';
    print '<td colspan="2">';
    print $form->showrefnav($fuser, 'id', '', $user->rights->user->user->lire || $user->admin);
    print '</td>';
    print '</tr>';

    // LastName
    print '<tr><td width="25%" valign="top">' . $langs->trans("LastName") . '</td>';
    print '<td colspan="2">' . $fuser->lastname . '</td>';
    print "</tr>\n";

    // FirstName
    print '<tr><td width="25%" valign="top">' . $langs->trans("FirstName") . '</td>';
    print '<td>' . $fuser->firstname . '</td>';
    print "</tr>\n";

    print '</table><br>';
} else {
    print_barre_liste($langs->trans("ListeCP"), $page, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, "", $num);

    dol_fiche_head('');
}

$nbaquis_current = $holiday->getCurrentYearCPforUser($user_id);
$nbaquis_next = $holiday->getNextYearCPforUser($user_id);
$nbdeduced = $holiday->getConfCP('nbHolidayDeducted');
$nb_holiday_current = $nbaquis_current / $nbdeduced;
$nb_holiday_next = $nbaquis_next / $nbdeduced;
$dateFinThisYear = date_create($holiday->getCPNextYearDate(false, false));
$dateDebNextYear = date_create($holiday->getCPNextYearDate(false, false));
$dateDebNextYear->add(new DateInterval('P1D'));
$dateFinNextYear = date_create($holiday->getCPNextYearDate(false, true));


$nbRtt = $holiday->getRTTforUser($user_id);

$nbAnneeFuturConge = getNbHolidays(new DateTime('NOW'), $dateFinThisYear, $user_id, 0);
$nbAnneePlus1FuturConge = getNbHolidays($dateDebNextYear, $dateFinNextYear, $user_id, 0);
print '<b>Année en cours : </b>';
print $langs->trans('SoldeCPUser', round($nb_holiday_current, 2)) . ($nbdeduced != 1 ? ' (' . $nbaquis_current . ' / ' . $nbdeduced . ')' : '');
if($nbAnneeFuturConge)
    print " plus ".$nbAnneeFuturConge." congé validé mais non passé";
print '&nbsp;(A utiliser avant le <b>' . date_format($dateFinThisYear, 'd / m / Y') . '</b>).';
print '<br/>';
print '<b>Année n+1 : </b>';
print $langs->trans('SoldeCPUser', round($nb_holiday_next, 2)) . ($nbdeduced != 1 ? ' (' . $nbaquis_next . ' / ' . $nbdeduced . ')' : '');
if($nbAnneePlus1FuturConge)
    print " plus ".$nbAnneePlus1FuturConge." congé validé mais non passé";
print '&nbsp;(A utiliser à partir du <b>' . date_format($dateDebNextYear, 'd / m / Y') . '</b> et avant le <b>' . date_format($dateFinNextYear, 'd / m / Y') . '</b>).';
print '<br/>';
print 'Solde RTT : <b>' . round($nbRtt, 2) . ' jours</b>';

if ($id > 0) {
    dol_fiche_end();
    print '</br>';
} else {
    dol_fiche_end();
}

print '<form id="searchForm" method="get" action="' . $_SERVER["PHP_SELF"] . '">' . "\n";
print '<div style="margin: 15px 0">';
if ($isDrh || $droitAll) {
    print '<input type="radio" id="showGroupsCp_no" name="show_groups_cp" value="0"'.(!$showGroups?' checked':'').'/>';
    print '<label style="margin: 0 30px 0 5px">Afficher les congés individuels</label>';
    print '<input type="radio" id="showGroupsCp_yes" name="show_groups_cp" value="1"'.($showGroups?' checked':'').'/>';
    print '<label style="margin-left: 5px">Afficher les congés collectifs</label>';
    print '</div>';
    print '<script type="text/javascript">';
    print '$(document).ready(function() {';
    print "$('input[name=show_groups_cp]').change(function() {";
    print "$('#searchForm').submit();";
    print "});";
    print 'setTimeout(function(){';
    print '$("#search_valideur").select2({ width: "200px" });';
    print '$("#search_employe").select2({ width: "200px" });';
    print '}, 0);';
    print '});';
    print '</script>';
}
print '<table class="noborder" width="100%;">';
print "<tr class=\"liste_titre\">";
print '<td></td>';
print_liste_field_titre($langs->trans("Ref"), $_SERVER["PHP_SELF"], "cp.rowid", "", $morefiltre, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("DateCreateCP"), $_SERVER["PHP_SELF"], "cp.date_create", "", $morefiltre, 'align="center"', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("Type"), $_SERVER["PHP_SELF"], "cp.type_conges", "", $morefiltre, 'align="center"', $sortfield, $sortorder);
if (!$showGroups)
    print_liste_field_titre($langs->trans("Employe"), $_SERVER["PHP_SELF"], "cp.fk_user", "", $morefiltre, '', $sortfield, $sortorder);
else
    print_liste_field_titre("Groupe", $_SERVER["PHP_SELF"], "cp.fk_group", "", $morefiltre, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("ValidatorCP"), $_SERVER["PHP_SELF"], "cp.fk_validator", "", $morefiltre, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("DateDebCP"), $_SERVER["PHP_SELF"], "cp.date_debut", "", $morefiltre, 'align="center"', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("DateFinCP"), $_SERVER["PHP_SELF"], "cp.date_fin", "", $morefiltre, 'align="center"', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("Duration"));
print_liste_field_titre($langs->trans("Status"), $_SERVER["PHP_SELF"], "cp.statut", "", $morefiltre, 'align="center"', $sortfield, $sortorder);
print "</tr>\n";

// ACTION
print '<tr class="liste_titre">';
print '<td align="right">';
print '<input type="image" class="liste_titre" name="button_search" src="' . img_picto($langs->trans("Search"), 'search.png', '', '', 1) . '" alt="' . $langs->trans('Search') . '">';
print '</td>';


// FILTRES
print '<td class="liste_titre" align="left" width="50">';
print '<input class="flat" size="4" type="text" name="search_ref" value="' . $search_ref . '">';
print '</td>';

// DATE CREATE
print '<td class="liste_titre" colspan="1" align="center">';
print '<input class="flat" type="text" size="1" maxlength="2" name="month_create" value="' . $month_create . '">';
$formother->select_year($year_create, 'year_create', 1, $min_year, 0);
print '</td>';

$tabType = array(-1 => "", 0 => "Congés", 1=>"Exceptionels", 2=> "Rtt");
// TYPE
print '<td class="liste_titre" colspan="1" align="center">';
print '<select name="type_conges">';
foreach($tabType as $idT => $type)
    print '<option value="'.$idT.'"'.($idT == $type_conges? "selected" : "").">".$type."</option>";
print '</select>';
print '</td>';

// UTILISATEUR
if ($droitAll || $isDrh) {
    print '<td class="liste_titre" align="left">';
    if (!$showGroups) {
        $form->select_users($search_employe, "search_employe", 1, "", 0, '');
    } else {
        $form->select_dolgroups($search_group, "search_group", 1);
    }
    print '</td>';
} else {
    print '<td class="liste_titre">&nbsp;</td>';
}

// VALIDEUR
if ($droitAll) {
    print '<td class="liste_titre" align="left">';

    $validator = new UserGroup($db);
//    $excludefilter = $user->admin ? '' : 'u.rowid <> ' . $user->id;
    $valideurobjects = $validator->listUsersForGroup($excludefilter,1);
    $form->select_users($search_valideur, "search_valideur", 1, "", 0, $valideurobjects, '');
    print '</td>';
} else {
    print '<td class="liste_titre">&nbsp;</td>';
}

// DATE DEBUT
print '<td class="liste_titre" colspan="1" align="center">';
print '<input class="flat" type="text" size="1" maxlength="2" name="month_start" value="' . $month_start . '">';
$formother->select_year($year_start, 'year_start', 1, $min_year, $max_year);
print '</td>';

// DATE FIN
print '<td class="liste_titre" colspan="1" align="center">';
print '<input class="flat" type="text" size="1" maxlength="2" name="month_end" value="' . $month_end . '">';
$formother->select_year($year_end, 'year_end', 1, $min_year, $max_year);
print '</td>';

// DUREE
print '<td>&nbsp;</td>';

// STATUT
print '<td class="liste_titre" width="70px;" align="center">';
$holiday->selectStatutCP($search_statut);
print '</td>';

print "</tr>\n";


// Lines
if (!empty($holiday->holiday)) {
    if (!$showGroups)
        $userstatic = new User($db);
    else
        $groupstatic = new UserGroup($db);
    $approbatorstatic = new User($db);

    foreach ($holiday->holiday as $infos_CP) {
        $var = !$var;

        // Utilisateur
        if (isset($userstatic)) {
            $userstatic->id = $infos_CP['fk_user'];
            $userstatic->lastname = $infos_CP['user_lastname'];
            $userstatic->firstname = $infos_CP['user_firstname'];
        } else if (isset($groupstatic)) {
            $groupstatic->fetch($infos_CP['fk_group']);
        }
        // Valideur
        $approbatorstatic->id = $infos_CP['fk_validator'];
        $approbatorstatic->lastname = $infos_CP['validator_lastname'];
        $approbatorstatic->firstname = $infos_CP['validator_firstname'];

        $date = $infos_CP['date_create'];

        $tabColor = array("16"=>"green", "17"=>"blue", "18"=>"orange", "19"=>"purple");
        print '<tr ' . $bc[$var] . '  style=" color:'.$tabColor[dol_print_date($infos_CP['date_debut'], '%y')].'">';
        print '<td colspan="2">';
        $holidaystatic->id = $infos_CP['rowid'];
        $holidaystatic->ref = $infos_CP['rowid'];
        print $holidaystatic->getNomUrl(1);

        print '</td>';
        print '<td style="text-align: center;">' . dol_print_date($date, 'day') . '</td>';
        print '<td>';
        
        
        print $tabType[$infos_CP["type_conges"]] . '</td>';
        print '<td>';
        
        if (!$showGroups)
            print $userstatic->getNomUrl('1');
        else
            print $groupstatic->name;
        print '</td>';
        print '<td>' . $approbatorstatic->getNomUrl('1') . '</td>';
        print '<td align="center">' . dol_print_date($infos_CP['date_debut'], 'day') . '</td>';
        print '<td align="center">' . dol_print_date($infos_CP['date_fin'], 'day') . '</td>';
        print '<td align="right">';
        $nbopenedday = num_open_dayUser($infos_CP['fk_user'], $infos_CP['date_debut_gmt'], $infos_CP['date_fin_gmt'], 0, 1, $infos_CP['halfday']);
        print $nbopenedday . ' ' . $langs->trans('DurationDays');
        print '<td align="right" colspan="1">' . $holidaystatic->LibStatut($infos_CP['statut'], 5) . '</td>';
        print '</tr>' . "\n";
    }
}

// Si il n'y a pas d'enregistrement suite à une recherche
if ($holiday_payes == '2') {
    print '<tr>';
    print '<td colspan="10" class="pair" style="text-align: center; padding: 5px;">' . $langs->trans('None') . '</td>';
    print '</tr>';
}

print '</table>';
print '</form>';

if ($user_id == $user->id) {
    print '<br>';
    print '<div style="float: right; margin-top: 8px;">';
    print '<a href="'.DOL_URL_ROOT.'/synopsisholiday/card.php?action=request" class="butAction">' . $langs->trans('AddCP') . '</a>';
    print '</div>';
}

llxFooter();

$db->close();
