<?php

if (!isset($user))
    require('../main.inc.php');
require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';

function getGroupUsersCheckboxes($group_id) {
    global $db;
    $html = '';
    $group = new UserGroup($db);
    if ($group->fetch($group_id) > 0) {
        $users = $group->listUsersForGroup();
        $html .= '<div style="margin-bottom: 5px; padding-bottom: 5px; border-bottom: 1px solid #DCDCDC">';
        $html .= '<input id="checkAllUsers" type="checkbox" checked onchange="toggleAllUserCheck()" style="margin-right: 10px"/>';
        $html .= '<label id="checkAllUsersLabel" for="checkAllUsers">Tout décocher</label>';
        $html .= '</div>';
        $html .= '<div id="usersCheckboxes">';
        if (!count($users)) {
            $html .= '<p style="color: #A00000">Aucun utisateur dans ce groupe.</p>';
        } else {
            foreach ($users as $user) {
                $html .= '<input style="margin-right: 10px" type="checkbox" checked name="groupUsers[]" id="userCheck_' . $user->id . '" value="' . $user->id . '"/>';
                $html .= '<label for="userCheck_' . $user->id . '">' . $user->lastname . '&nbsp;' . $user->firstname . '</label><br/>';
            }
        }
        $html .= '</div>';
    } else {
        $html .= '<p style="color: #A00000">Erreur: la liste des utilisateurs n\'a pas pu être chargée. (id groupe invalide)</p>';
    }
    return $html;
}

if (isset($_GET['groupId'])) {
    print getGroupUsersCheckboxes($_GET['groupId']);
}