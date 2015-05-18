<?php

/* Copyright (C) 2002-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2005      Lionel Cousteix      <etm_ltd@tiscali.co.uk>
 * Copyright (C) 2011      Herve Prot           <herve.prot@symeos.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/user/card.php
 *       \brief      Tab of user card
 */
require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT . "/user/class/user.class.php");
require_once(DOL_DOCUMENT_ROOT . "/user/class/usergroup.class.php");
require_once(DOL_DOCUMENT_ROOT . "/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/images.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/usergroups.lib.php");
//if ($conf->ldap->enabled)
//    require_once(DOL_DOCUMENT_ROOT . "/core/class/ldap.class.php");
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


$sql = $db->query("SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_hrm_user WHERE user_id =" . $_REQUEST['id']);
$obj = $db->fetch_object($sql);


if ($action == 'update' && !$_POST["cancel"]) {
    $_REQUEST["startDate"] = convertDate($_REQUEST["startDate"]);
    if (isset($obj->user_id))
        $req ="UPDATE `" . MAIN_DB_PREFIX . "Synopsis_hrm_user` SET `couthoraire`='" . $_REQUEST["couthoraire"] . "',`startDate`='" . $_REQUEST["startDate"] . "',`hrm_id`='" . $_REQUEST["hrm_id"] . "' WHERE user_id =" . $_REQUEST["id"];
    else
        $req ="INSERT INTO `" . MAIN_DB_PREFIX . "Synopsis_hrm_user` (`couthoraire`, `startDate`, `hrm_id`, user_id) VALUES ('" . $_REQUEST["couthoraire"] . "','" . $_REQUEST["startDate"] . "','" . $_REQUEST["hrm_id"] . "'," . $_REQUEST["id"].")";
    $sql = $db->query($req);

    $sql = $db->query("SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_hrm_user WHERE user_id =" . $_REQUEST['id']);
    $obj = $db->fetch_object($sql);
}

function convertDate($date){
        if($date == '')
            return '';
        $tabTemp = explode(" ", $date);
        $tabTemp2 = explode("-", $tabTemp[0]);
        if(!isset($tabTemp2[2]))
            $tabTemp2 = explode("/", $tabTemp[0]);
        $tabTemp3 = explode(":", $tabTemp[1]);
        return $tabTemp2[2]."/".$tabTemp2[1]."/".$tabTemp2[0]." ".$tabTemp3[0].":".$tabTemp3[1];    
}


/*
 * View
 */

llxHeader('', $langs->trans("Coût horaire"));



// Show tabs
$head = user_prepare_head($fuser);
$title = $langs->trans("Côut horaire");
dol_fiche_head($head, 'coutUser', $title, 0, 'user');



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
        print '<tr><td valign="top">' . $langs->trans("Coût horaire") . '</td>';
        print '<td>' . $obj->couthoraire . '</td>';

        // Pass
        print '<td valign="top">' . $langs->trans("Date début") . '</td>';
        $date = convertDate($obj->startDate);
        print '<td><input type="text" disabled="disabled" value="' . $date . '"/></td>';

        print '</tr>' . "\n";

        // Zimbra id
        print '<tr><td valign="top">' . $langs->trans("Hrm id") . '</td>';
        print '<td>' . $obj->hrm_id . '</td>';



        print "</table>";
        print "<br>";

        print '<center><a class="butAction" style="align:right;" href="userPrix.php?id=' . $fuser->id . '&amp;action=edit">' . $langs->trans("Modify") . '</a></center>';
    }


    /*
     * Fiche en mode edition
     */

    if ($action == 'edit') {
        $date = convertDate($obj->startDate);
        print '<script src="' . DOL_URL_ROOT . '/Synopsis_Common/jquery/ui/ui.datetimepicker.js" type="text/javascript"></script>';
        print "<script>
            
        jQuery(document).ready(function(){
                jQuery('.datepicker').each(function(){
                    jQuery(this).datetimepicker();  
                    jQuery(this).val('".$date."');
                });
        });
        </script>";

        print '<form action="' . $_SERVER['PHP_SELF'] . '?id=' . $fuser->id . '" method="POST" name="updateuser" enctype="multipart/form-data">';
        print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
        print '<input type="hidden" name="action" value="update">';
        print '<input type="hidden" name="entity" value="' . $conf->entity . '">';
        print '<table width="100%" class="border">';
        ;

        // Login
        print '<tr><td valign="top">' . $langs->trans("Coût horaire") . '</td>';
        print '<td><input type="text" name="couthoraire" value="' . $obj->couthoraire . '"/></td>';

        // Pass
        print '<td valign="top">' . $langs->trans("Date debut") . '</td>';
        $date = convertDate($obj->startDate);
        print '<td><input class="datepicker" type="text" name="startDate" value="' . $date . '"/></td>';

        print '</tr>' . "\n";

        // Zimbra id
        print '<tr><td valign="top">' . $langs->trans("Hrm id") . '</td>';
        print '<td><input type="text" name="hrm_id" value="' . $obj->hrm_id . '"/></td>';


        print '</table>';

        print '<br><center>';
        print '<input value="' . $langs->trans("Save") . '" class="button" type="submit" name="save">';
        print ' &nbsp; ';
        print '<input value="' . $langs->trans("Cancel") . '" class="button" type="submit" name="cancel">';
        print '</center>';

        print '</form>';

        print '</div>';
    }

//    $ldap->close;
}

$db->close();

llxFooter();
?>