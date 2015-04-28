<?php

/* Copyright (C) 2002-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2005      Lionel Cousteix      <etm_ltd@tiscali.co.uk>
 * Copyright (C) 2011      Herve Prot           <herve.prot@symeos.com>
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
 *       \file       htdocs/user/fiche.php
 *       \brief      Tab of user card
 */
require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT . "/user/class/user.class.php");
require_once(DOL_DOCUMENT_ROOT . "/user/class/usergroup.class.php");
require_once(DOL_DOCUMENT_ROOT . "/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/images.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/usergroups.lib.php");
if ($conf->ldap->enabled)
    require_once(DOL_DOCUMENT_ROOT . "/core/class/ldap.class.php");
if ($conf->adherent->enabled)
    require_once(DOL_DOCUMENT_ROOT . "/adherents/class/adherent.class.php");
if (!empty($conf->multicompany->enabled))
    dol_include_once("/multicompany/class/actions_multicompany.class.php");

$id = GETPOST('id', 'int');
$action = GETPOST("action");
$group = GETPOST("group", "int", 3);
$confirm = GETPOST("confirm");

// Define value to know what current user can do on users
$canadduser = ($user->admin || $user->rights->user->user->creer);
$canreaduser = ($user->admin || $user->rights->user->user->lire);
$canedituser = ($user->admin || $user->rights->user->user->creer);
$candisableuser = ($user->admin || $user->rights->user->user->supprimer);
$canreadgroup = $canreaduser;
$caneditgroup = $canedituser;
if (!empty($conf->global->MAIN_USE_ADVANCED_PERMS)) {
    $canreadgroup = ($user->admin || $user->rights->user->group_advance->read);
    $caneditgroup = ($user->admin || $user->rights->user->group_advance->write);
}
// Define value to know what current user can do on properties of edited user
if ($id) {
    // $user est le user qui edite, $_GET["id"] est l'id de l'utilisateur edite
    $caneditfield = ((($user->id == $id) && $user->rights->user->self->creer)
            || (($user->id != $id) && $user->rights->user->user->creer));
    $caneditpassword = ((($user->id == $id) && $user->rights->user->self->password)
            || (($user->id != $id) && $user->rights->user->user->password));
}

//Multicompany in mode transversal
if (!empty($conf->multicompany->enabled) && $conf->entity > 1 && $conf->multicompany->transverse_mode) {
    accessforbidden();
}

// Security check
$socid = 0;
if ($user->societe_id > 0)
    $socid = $user->societe_id;
$feature2 = 'user';
if ($user->id == $id) {
    $feature2 = '';
    $canreaduser = 1;
} // A user can always read its own card
$result = restrictedArea($user, 'user', $id, '&user', $feature2);
if ($user->id <> $id && !$canreaduser)
    accessforbidden();

$langs->load("users");

$fuser = new User($db);
$fuser->fetch($id);

$form = new Form($db);


$sql = $db->query("SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_li_User WHERE User_refid =" . $_REQUEST['id']);
$obj = $db->fetch_object($sql);


if ($action == 'update' && !$_POST["cancel"]) {
    if (isset($obj->User_refid))
        $sql = $db->query("UPDATE `" . MAIN_DB_PREFIX . "Synopsis_Zimbra_li_User` SET `ZimbraLogin`='" . $_REQUEST["zimbraLogin"] . "',`ZimbraPass`='" . $_REQUEST["zimbraPass"] . "',`ZimbraId`='" . $_REQUEST["zimbraId"] . "',`calFolderZimId`='" . (isset($_REQUEST["calFolderZimId"]) ? '1' : '0') . "' WHERE User_refId =" . $_REQUEST["id"]);
    else
        $sql = $db->query("INSERT INTO `" . MAIN_DB_PREFIX . "Synopsis_Zimbra_li_User` (`ZimbraLogin`, `ZimbraPass`, `ZimbraId`, `calFolderZimId`, User_refId) VALUES ('" . $_REQUEST["zimbraLogin"] . "','" . $_REQUEST["zimbraPass"] . "','" . $_REQUEST["zimbraId"] . "','" . (isset($_REQUEST["calFolderZimId"]) ? '1' : '0') . "'," . $_REQUEST["id"].")");


    $sql = $db->query("SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_li_User WHERE User_refid =" . $_REQUEST['id']);
    $obj = $db->fetch_object($sql);
}


/*
 * View
 */

llxHeader('', $langs->trans("Utilisateur Zimbra"));



// Show tabs
$head = user_prepare_head($fuser);
$title = $langs->trans("Utilisateur Zimbra");
dol_fiche_head($head, 'zimbraUser', $title, 0, 'user');



$form = new Form($db);

/* * ************************************************************************* */
/*                                                                            */
/* Visu et edition                                                            */
/*                                                                            */
/* * ************************************************************************* */

if ($id) {
    print_fiche_titre($langs->trans("Utilisateur Zimbra"));
    /*
     * Fiche en mode visu
     */
    if ($action != 'edit') {
        print '<table class="border" width="100%">';

        // Login
        print '<tr><td valign="top">' . $langs->trans("Login") . '</td>';
        print '<td>' . $obj->ZimbraLogin . '</td>';

        // Pass
        print '<td valign="top">' . $langs->trans("Pass") . '</td>';
        print '<td><input type="password" disabled="disabled" value="' . $obj->ZimbraPass . '"/></td>';

        print '</tr>' . "\n";

        // Zimbra id
        print '<tr><td valign="top">' . $langs->trans("Zimbra id") . '</td>';
        print '<td>' . $obj->ZimbraId . '</td>';

        // calFolderZimId 
        print '<td valign="top">' . $langs->trans("calFolderZimId") . '</td>';
        print '<td><input type="checkbox" disabled="disabled" name="calFolderZimId" ' . ($obj->calFolderZimId == 1 ? 'checked="checked"' : '') . '/></td>';



        print "</table>";
        print "<br>";

        print '<center><a class="butAction" style="align:right;" href="userZimbra.php?id=' . $fuser->id . '&amp;action=edit">' . $langs->trans("Modify") . '</a></center>';
    }


    /*
     * Fiche en mode edition
     */

    if ($action == 'edit') {

        print '<form action="' . $_SERVER['PHP_SELF'] . '?id=' . $fuser->id . '" method="POST" name="updateuser" enctype="multipart/form-data">';
        print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
        print '<input type="hidden" name="action" value="update">';
        print '<input type="hidden" name="entity" value="' . $conf->entity . '">';
        print '<table width="100%" class="border">';
        ;

        // Login
        print '<tr><td valign="top">' . $langs->trans("Login") . '</td>';
        print '<td><input type="text" name="zimbraLogin" value="' . $obj->ZimbraLogin . '"/></td>';

        // Pass
        print '<td valign="top">' . $langs->trans("Pass") . '</td>';
        print '<td><input type="password" name="zimbraPass" value="' . $obj->ZimbraPass . '"/></td>';

        print '</tr>' . "\n";

        // Zimbra id
        print '<tr><td valign="top">' . $langs->trans("Zimbra id") . '</td>';
        print '<td><input type="text" name="zimbraId" value="' . $obj->ZimbraId . '"/></td>';

        // calFolderZimId 
        print '<td valign="top">' . $langs->trans("calFolderZimId") . '</td>';
        print '<td><input type="checkbox" name="calFolderZimId" ' . ($obj->calFolderZimId == 1 ? 'checked="checked"' : '') . '/></td>';


        print '</table>';

        print '<br><center>';
        print '<input value="' . $langs->trans("Save") . '" class="button" type="submit" name="save">';
        print ' &nbsp; ';
        print '<input value="' . $langs->trans("Cancel") . '" class="button" type="submit" name="cancel">';
        print '</center>';

        print '</form>';

        print '</div>';
    }

    $ldap->close;
}

$db->close();

llxFooter();
?>