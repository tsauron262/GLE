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
$holiday = new Holiday($db);


llxHeader(array(), $langs->trans('CPTitreMenu'));

print_fiche_titre($langs->trans('MenuConfCP'));

$holiday->updateSold(); // Create users into table holiday if they don't exists. TODO Remove if we use field into table user.

$listUsers = $holiday->fetchUsers(false, false);

// Si il y a une action de mise à jour
if ($action == 'update' && isset($_POST['update_cp'])) {
    $userID = array_keys($_POST['update_cp']);
    $userID = $userID[0];

    $userValueCurrent = (int) $_POST['nb_holiday_current'][$userID];
    $userValueNext = (int) $_POST['nb_holiday_next'][$userID];

    $nowValueCurrent = null;
    $nowValueNext = null;

    $sql = 'SELECT `nb_holiday` as current, `nb_holiday_next` as next FROM ' . MAIN_DB_PREFIX . 'holiday_users WHERE `fk_user` = ' . $userID;
    $result = $db->query($sql);
    if ($result) {
        if ($db->num_rows($result)) {
            $obj = $db->fetch_object($result);
            $nowValueCurrent = (int) $obj->current;
            $nowValueNext = (int) $obj->next;
        }
    }
    $db->free($result);
    if (!empty($userValueCurrent)) {
        $userValueCurrent = price2num($userValueCurrent, 2);
    } else {
        $userValueCurrent = 0;
    }

    if (!empty($userValueNext)) {
        $userValueNext = price2num($userValueNext, 2);
    } else {
        $userValueNext = 0;
    }

    //If the user set a comment, we add it to the log comment
    $comment = ((isset($_POST['note_holiday'][$userID]) && !empty($_POST['note_holiday'][$userID])) ? ' (' . $_POST['note_holiday'][$userID] . ')' : '');

    // Log
    $updateDone = false;
    if ((isset($nowValueCurrent) && $nowValueCurrent != $userValueCurrent) || !isset($nowValueCurrent)) {
        // Maj solde année n
        $holiday->addLogCP($user->id, $userID, 'Mise à jour manuelle du solde (année en cours)' . $comment, $userValueCurrent, false, true);
        $holiday->updateSoldeCP($userID, $userValueCurrent, true);
        $updateDone = true;
    }
    if ((isset($nowValueNext) && $nowValueNext != $userValueNext) || !isset($nowValueNext)) {
        // Maj solde année n+1
        echo 'maj : ' . $userValueNext . '<br/>';
        $holiday->addLogCP($user->id, $userID, 'Mise à jour manuelle du solde (année suivante)' . $comment, $userValueNext, false, false);
        $holiday->updateSoldeCP($userID, $userValueNext, false);

        // If it's first update of sold, we set date to avoid to have sold incremented by new month
        $now = dol_now();
        $sql = "UPDATE " . MAIN_DB_PREFIX . "holiday_config SET";
        $sql.= " value = '" . dol_print_date($now, '%Y%m%d%H%M%S') . "'";
        $sql.= " WHERE name = 'lastUpdate' and value IS NULL"; // Add value IS NULL to be sure to update only at init.
        dol_syslog('define_holiday update lastUpdate entry sql=' . $sql);
        $db->query($sql);
        $updateDone = true;
    }
    if ($updateDone) {
        $mesg = '<div class="ok">' . $langs->trans('UpdateConfCPOK') . '</div>';
        dol_htmloutput_mesg($mesg);
    }
} else if ($action == 'add_event') {
    $error = 0;

    if (!empty($_POST['list_event']) && $_POST['list_event'] > 0) {
        $event = $_POST['list_event'];
    } else {
        $error++;
    }
    if (!empty($_POST['userCP']) && $_POST['userCP'] > 0) {
        $userCP = $_POST['userCP'];
    } else {
        $error++;
    }
    if (!empty($_POST['soldeType'])) {
        $soldType = $_POST['soldeType'];
    } else {
        $error++;
    }
    if ($error) {
        $message = '<div class="error">' . $langs->trans('ErrorAddEventToUserCP') . '</div>';
    } else {
        $add_holiday = $holiday->getValueEventCp($event);
        $check = 0;
        switch ($soldType) {
            case 'currentYearSolde':
                $nb_holiday = $holiday->getCurrentYearCPforUser($userCP);
                $new_holiday = $nb_holiday + $add_holiday;
                $holiday->addLogCP($user->id, $userCP, $holiday->getNameEventCp($event).' (année en cours)', $new_holiday, false, true);
                $check = $holiday->updateSoldeCP($userCP, $new_holiday, true);
                break;
                
            case 'nextYearSolde':
                $nb_holiday = $holiday->getNextYearCPforUser($userCP);
                $new_holiday = $nb_holiday + $add_holiday;
                $holiday->addLogCP($user->id, $userCP, $holiday->getNameEventCp($event).' (année suivante)', $new_holiday, false, false);
                $check = $holiday->updateSoldeCP($userCP, $new_holiday, false);
                break;
        }
        if ($check > 0)
            $message = $langs->trans('AddEventToUserOkCP');
        else {
            $message = '<div class="error">' . $langs->trans('ErrorAddEventToUserCP') . '</div>';
        }
    }

    dol_htmloutput_mesg($message);
}

$langs->load('users');
$var = true;
$i = 0;

$cp_events = $holiday->fetchEventsCP();

if ($cp_events == 1) {
    print '<br><form method="POST" action="' . $_SERVER["PHP_SELF"] . '">' . "\n";
    print '<input type="hidden" name="action" value="add_event" />';

    print_fiche_titre($langs->trans('DefineEventUserCP'), '', '');

    print $langs->trans('MotifCP') . ' : ';
    print $holiday->selectEventCP();
    print ' &nbsp; ' . $langs->trans('UserCP') . ' : ';
    print $form->select_dolusers('', "userCP", 1, "", 0, '');
    print '&nbsp; Solde à incrémenter&nbsp;:&nbsp;';
    print '<select id="soldeType" name="soldeType" class="flat">';
    print '<option value="currentYearSolde">Année en cours</option>';
    print '<option value="nextYearSolde">Année n+1</option>';
    print '</select>';
    print ' <input type="submit" value="' . $langs->trans("addEventToUserCP") . '" name="bouton" class="button"/>';

    print '</form><br>';
}

dol_fiche_head();

print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">' . "\n";
print '<input type="hidden" name="action" value="update" />';
print '<table class="noborder" width="100%;">';
print "<tr class=\"liste_titre\">";
print '<td width="5%">' . $langs->trans('ID') . '</td>';
print '<td width="50%">' . $langs->trans('Employee') . '</td>';
print '<td width="20%" style="text-align:center">Solde CP Année en cours</td>';
print '<td width="20%" style="text-align:center">Solde CP Année n+1</td>';
print '<td width="20%" style="text-align:center">' . $langs->trans('Note') . '</td>';
print '<td style="text-align:center">' . $langs->trans('UpdateButtonCP') . '</td>';
print '</tr>';

foreach ($listUsers as $users) {

    $var = !$var;
    $nb_holiday_current = $holiday->getCurrentYearCPforUser($users['rowid']);
    $nb_holiday_next = $holiday->getNextYearCPforUser($users['rowid']);

    print '<tr ' . $bc[$var] . ' style="height: 20px;">';
    print '<td>' . $users['rowid'] . '</td>';
    print '<td>';
    $userstatic->id = $users['rowid'];
    $userstatic->lastname = $users['name'];
    $userstatic->firstname = $users['firstname'];
    print $userstatic->getNomUrl(1);
    print '</td>';
    print '<td style="text-align:center">';
    print '<input type="text" value="' . $nb_holiday_current . '" name="nb_holiday_current[' . $users['rowid'] . ']" size="5" style="text-align: center;"/>';
    print ' ' . $langs->trans('days') . '</td>' . "\n";
    print '<td style="text-align:center">';
    print '<input type="text" value="' . $nb_holiday_next . '" name="nb_holiday_next[' . $users['rowid'] . ']" size="5" style="text-align: center;"/>';
    print ' ' . $langs->trans('days') . '</td>' . "\n";
    print '<td style="text-align:center"><input type="text" value="" name="note_holiday[' . $users['rowid'] . ']" size="30"/></td>';
    print '<td><input type="submit" name="update_cp[' . $users['rowid'] . ']" value="' . dol_escape_htmltag($langs->trans("Update")) . '" class="button"/></td>' . "\n";
    print '</tr>';

    $i++;
}

print '</table>';
print '</form>';

dol_fiche_end();

llxFooter();

$db->close();
?>
