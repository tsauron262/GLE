<?php
/* Copyright (C) 2005-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
  
  */
/*
 *
 * $Id: info.php,v 1.13 2008/01/29 19:03:30 eldy Exp $
 * $Source: /cvsroot/dolibarr/dolibarr/htdocs/commande/info.php,v $
 */

/**
        \file       htdocs/commande/info.php
        \ingroup    commande
        \brief      Page des informations d'une commande
        \version    $Revision: 1.13 $
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/synopsisprojet/class/synopsisproject.class.php");
require_once(DOL_DOCUMENT_ROOT."/synopsisprojet/core/lib/synopsis_project.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php");

$langs->load("synopsisproject@synopsisprojet");

if (!$user->rights->synopsisprojet->lire)
    accessforbidden();


/*
 * Visualisation de la fiche
 *
 */

llxHeader("","Info Projet");

$projet = new SynopsisProject($db);
$projet->fetch($_GET["id"]);
$projet->info($_GET["id"]);
$soc = new Societe($db, $projet->socid);
$soc->fetch($projet->socid);

$head = synopsis_project_prepare_head($projet);
dol_fiche_head($head, 'info', $langs->trans("Project"));

print '<table width="100%"><tr><td>';
old_dol_print_object_info($projet);
print '</td></tr></table>';

print '</div>';

// Juste pour eviter bug IE qui reorganise mal div precedents si celui-ci absent
print '<div class="tabsAction">';
print '</div>';


$db->close();

llxFooter('$Date: 2008/01/29 19:03:30 $ - $Revision: 1.13 $');



function old_dol_print_object_info($object)
{
    global $langs;
    $langs->load("other");

    print "<div class='ui-widget-content' style='padding: 10px;'>";
    print "<table width=100% cellpadding=15>";
    if (isset($object->user_creation) && $object->user_creation->getFullName($langs))
    print "<tr><th width=200 class='ui-widget-header ui-state-default'>".$langs->trans("CreatedBy")."<td class='ui-widget-content'>" . $object->user_creation->getFullName($langs) ;

    if (isset($object->user_creat) && $object->user_creat->getFullName($langs))
    print "<tr><th width=200 class='ui-widget-header ui-state-default'>".$langs->trans("CreatedBy")."<td class='ui-widget-content'>" . $object->user_creat->getFullName($langs);

    if (isset($object->user_resp) && $object->user_resp->getFullName($langs))
    print "<tr><th width=200 class='ui-widget-header ui-state-default'>".$langs->trans("Responsable")."<td class='ui-widget-content'>" . $object->user_resp->getFullName($langs) ;

    if (isset($object->date_creation))
    print "<tr><th class='ui-widget-header ui-state-default'>". $langs->trans("DateCreation")."<td class='ui-widget-content'>" . traiteDate($object->date_creation,"dayhour");

    if (isset($object->user_modification) && $object->user_modification->getFullName($langs))
    print "<tr><th class='ui-widget-header ui-state-default'>". $langs->trans("ModifiedBy")."<td class='ui-widget-content'>" . $object->user_modification->getFullName($langs);

    if (isset($object->date_modification))
    print "<tr><th class='ui-widget-header ui-state-default'>". $langs->trans("DateLastModification")."<td class='ui-widget-content'>" . traiteDate($object->date_modification,"dayhour");

    if (isset($object->user_validation) && $object->user_validation->getFullName($langs))
    print "<tr><th class='ui-widget-header ui-state-default'>". $langs->trans("ValidatedBy")."<td class='ui-widget-content'>" . $object->user_validation->getFullName($langs);

    if (isset($object->date_validation))
    print "<tr><th class='ui-widget-header ui-state-default'>". $langs->trans("DateValidation")."<td class='ui-widget-content'>" . traiteDate($object->date_validation,"dayhour");

    if (isset($object->user_cloture) && $object->user_cloture->getFullName($langs) )
    print "<tr><th class='ui-widget-header ui-state-default'>". $langs->trans("ClosedBy")."<td class='ui-widget-content'>" . $object->user_cloture->getFullName($langs);

    if (isset($object->date_close))
    print "<tr><th class='ui-widget-header ui-state-default'>". $langs->trans("DateClosing")."<td class='ui-widget-content'>" . traiteDate($object->date_close,"dayhour");

    if (isset($object->user_rappro) && $object->user_rappro->getFullName($langs) )
    print "<tr><th class='ui-widget-header ui-state-default'>". $langs->trans("ConciliatedBy")."<td class='ui-widget-content'>" . $object->user_rappro->getFullName($langs);

    if (isset($object->date_rappro))
    print "<tr><th class='ui-widget-header ui-state-default'>". $langs->trans("DateConciliating")."<td class='ui-widget-content'>" . traiteDate($object->date_rappro,"dayhour");

    if (isset($object->datec))
    print "<tr><th class='ui-widget-header ui-state-default'>". $langs->trans("DateCreation")."<td class='ui-widget-content'>" . traiteDate($object->datec,"dayhour");

    if (isset($object->dateo))
    print "<tr><th class='ui-widget-header ui-state-default'>". $langs->trans("Date ouverture")."<td class='ui-widget-content'>" . traiteDate($object->dateo,"dayhour");

    if (isset($object->date_valid))
    print "<tr><th class='ui-widget-header ui-state-default'>". $langs->trans("Date validation")."<td class='ui-widget-content'>" . traiteDate($object->date_valid,"dayhour");

    if (isset($object->date_launch))
    print "<tr><th class='ui-widget-header ui-state-default'>". $langs->trans("Date lancement")."<td class='ui-widget-content'>" . traiteDate($object->date_launch,"dayhour");

    if (isset($object->date_close))
    print "<tr><th class='ui-widget-header ui-state-default'>". $langs->trans("Date cl&ocirc;ture")."<td class='ui-widget-content'>" . traiteDate($object->date_close,"dayhour");




    print "</div>";
}


function traiteDate($date){
    $tab = explode(" ", $date);
    $tab2 = explode("-", $tab[0]);
    $date = $tab2[2] ."-". $tab2[1] ."-". $tab2[0] ." ". $tab[1];
    return $date;
}

?>
