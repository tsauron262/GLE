<?php

/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011      Dimitri Mouillard <dmouillard@teclib.com>
 * Copyright (C) 2013      Marcos García <marcosgdf@gmail.com>
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
 * 		File that defines the balance of paid holiday of users.
 *
 *   	\file       htdocs/holiday/define_holiday.php
 * 		\ingroup    holiday
 * 		\brief      File that defines the balance of paid holiday of users.
 */

if (!isset($user))
    require('../main.inc.php');
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/synopsisholiday/common.inc.php';

// Protection if external user
if ($user->societe_id > 0)
    accessforbidden();

// If the user does not have perm to read the page
if (!$user->rights->holiday->define_holiday)
    accessforbidden();

$action = GETPOST('action');


/*
 * View
 */

$form = new Form($db);
$userstatic = new User($db);
$holiday = new SynopsisHoliday($db);


llxHeader(array(), $langs->trans('CPTitreMenu'));

print_fiche_titre($langs->trans('MenuConfRTT'));
$listUsers = $holiday->fetchUsers(false, false);

// Si il y a une action de mise à jour
if ($action == 'update' && isset($_POST['update_rtt'])) {
    $userID = array_keys($_POST['update_rtt']);
    $userID = $userID[0];

    $userValue = $_POST['nb_rtt'];
    $userValue = $userValue[$userID];

    if (!empty($userValue)) {
        $userValue = price2num($userValue, 2);
    } else {
        $userValue = 0;
    }

    //If the user set a comment, we add it to the log comment
    $comment = ((isset($_POST['note_rtt'][$userID]) && !empty($_POST['note_rtt'][$userID])) ? ' (' . $_POST['note_rtt'][$userID] . ')' : '');

    // We add the modification to the log
    $holiday->addLogCP($user->id, $userID, $langs->transnoentitiesnoconv('ManualUpdateRTT') . $comment, $userValue, true);

    // Update of the days of the employee
    $holiday->updateSoldeRTT($userID, $userValue);

    $mesg = '<div class="ok">' . $langs->trans('UpdateConfCPOK') . '</div>';

    dol_htmloutput_mesg($mesg);
}

$langs->load('users');
$var = true;
$i = 0;

dol_fiche_head();

print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">' . "\n";
print '<input type="hidden" name="action" value="update" />';
print '<table class="noborder" width="100%;">';
print "<tr class=\"liste_titre\">";
print '<td width="5%">' . $langs->trans('ID') . '</td>';
print '<td width="50%">' . $langs->trans('Employee') . '</td>';
print '<td width="20%" style="text-align:center">' . $langs->trans('Available') . '</td>';
print '<td width="20%" style="text-align:center">' . $langs->trans('Note') . '</td>';
print '<td style="text-align:center">' . $langs->trans('UpdateButtonCP') . '</td>';
print '</tr>';

foreach ($listUsers as $users) {

    $var = !$var;

    print '<tr ' . $bc[$var] . ' style="height: 20px;">';
    print '<td>' . $users['rowid'] . '</td>';
    print '<td>';
    $userstatic->id = $users['rowid'];
    $userstatic->lastname = $users['name'];
    $userstatic->firstname = $users['firstname'];
    print $userstatic->getNomUrl(1);
    print '</td>';
    print '<td style="text-align:center">';
    print '<input type="text" value="' . $holiday->getRTTforUser($users['rowid']) . '" name="nb_rtt[' . $users['rowid'] . ']" size="5" style="text-align: center;"/>';
    print ' ' . $langs->trans('days') . '</td>' . "\n";
    print '<td style="text-align:center"><input type="text" value="" name="note_rtt[' . $users['rowid'] . ']" size="30"/></td>';
    print '<td><input type="submit" name="update_rtt[' . $users['rowid'] . ']" value="' . dol_escape_htmltag($langs->trans("Update")) . '" class="button"/></td>' . "\n";
    print '</tr>';

    $i++;
}

print '</table>';
print '</form>';

dol_fiche_end();

llxFooter();

$db->close();
?>
