<?php
/* Copyright (C) 2005-2007  Regis Houssin  <regis@dolibarr.fr>
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
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.*//*
  * GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Create on : 4-1-2009
  *
  * Infos on http://www.synopsis-erp.com
  *
  *//*
 */

/**
    \file       htdocs/fichinter/info.php
    \ingroup    fichinter
    \brief      Page d'affichage des infos d'une fiche d'intervention
    \version    $Id: info.php,v 1.4 2008/02/25 20:03:26 eldy Exp $
*/

require('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT."/fichinter/class/fichinter.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/fichinter.lib.php");




$langs->load('companies');

$fichinterid = isset($_GET["id"])?$_GET["id"]:'';

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'synopsisficheinter', $fichinterid, 'fichinter');

$msg = false;
$msgKO=false;
if ($_REQUEST['action'] == "changeSrc" && $user->rights->synopsisficheinter->rattacher)
{
    $requete = false;
    $requete1 = false;
    if ($_REQUEST['fk_contrat'] > 0){
        $requete = "UPDATE llx_Synopsis_fichinter SET fk_contrat = ".$_REQUEST['fk_contrat']." WHERE rowid = ".$fichinterid;
    } else {
        $requete = "UPDATE llx_Synopsis_fichinter SET fk_contrat = NULL WHERE rowid = ".$fichinterid;
    }
    if ($_REQUEST['fk_commande'] > 0){
        $requete1 = "UPDATE llx_Synopsis_fichinter SET fk_commande = ".$_REQUEST['fk_commande']." WHERE rowid = ".$fichinterid;
    } else {
        $requete1 = "UPDATE llx_Synopsis_fichinter SET fk_commande = NULL WHERE rowid = ".$fichinterid;
    }
    if ($requete) $sql = $db->query($requete);
    if ($requete1) $sql1 = $db->query($requete1);

    if($sql1 && $sql)
        $msg = "Mise &agrave; jour OK";
    else
        $msgKO = "Mise &agrave; jour KO";
}


/*
*    View
*/

llxHeader('','Info FI');
$fichinter = new Fichinter($db);
$fichinter->fetch($_GET['id']);

$societe = new Societe($db);
$societe->fetch($fichinter->socid);

$head = Synopsis_fichinter_prepare_head($fichinter);
dol_fiche_head($head, 'info', $langs->trans('InterventionCard'));
if ($msg)
{
    print "<div class='ui-state-highlight'><table><tr><td><span class='ui-icon ui-icon-info'></span><td>".$msg."</table></div>";
}
if ($msgKO)
{
    print "<div class='ui-state-error error'><table><tr><td><span class='ui-icon ui-icon-info'></span><td>".$msgKO."</table></div>";
}

$fichinter->info($fichinter->id);

print '<table width="100%"><tr><td>';
dol_print_object_info($fichinter);
print '</td></tr></table>';

print '</div>';

// Juste pour eviter bug IE qui reorganise mal div precedents si celui-ci absent
print '<div class="tabsAction">';
print '</div>';


if($user->rights->synopsisficheinter->rattacher){
    print "<div class='titre'>Rattacher la FI &agrave;</div>";
    print"<form action='info.php?id=".$fichinter->id."&action=changeSrc' METHOD=POST>";
    print "<table cellpadding=15 WIDTH=400>";
    $requete = "SELECT * FROM llx_commande WHERE fk_soc =".$fichinter->fk_soc;
    print "<tr><th class='ui-state-default ui-widget-header'>La commande ";
    $sql = $db->query($requete);
    print "<td class='ui-widget-content'><SELECT name='fk_commande' id='fk_commande'>";
    print "<OPTION value=''>S&eacute;lectionner-></OPTION>";
    while($res=$db->fetch_object($sql)){
        if ($res->rowid == $fichinter->fk_commande)
        {
            print "<option SELECTED value='".$res->rowid."'>".$res->ref ." du ".date('d/m/Y',strtotime($res->date_commande))." ". "(".price($res->total_ht)." &euro;)"."</option>";
        } else {
            print "<option value='".$res->rowid."'>".$res->ref ." du ".date('d/m/Y',strtotime($res->date_commande))." ". "(".price($res->total_ht)." &euro;)"."</option>";
        }
    }
    print "<td class='ui-widget-content' rowspan=2><button class='ui-button'>Modifier</button>";
    $requete = "SELECT * FROM llx_contrat WHERE fk_soc =".$fichinter->fk_soc;
    print "<tr><th class='ui-state-default ui-widget-header'>Le contrat ";
    $sql = $db->query($requete);
    print "<td class='ui-widget-content'><SELECT name='fk_contrat' id='fk_contrat'>";
    print "<OPTION value=''>S&eacute;lectionner-></OPTION>";
    while($res=$db->fetch_object($sql)){
        if ($res->rowid == $fichinter->fk_contrat)
        {
            print "<option SELECTED value='".$res->rowid."'>".$res->ref ." du ".date('d/m/Y',strtotime($res->date_contrat))." "."</option>";
        } else {
            print "<option value='".$res->rowid."'>".$res->ref ." du ".date('d/m/Y',strtotime($res->date_contrat))." "."</option>";
        }
    }
    print "</table>";
    print"</form>";
}
$db->close();

llxFooter('$Date: 2008/02/25 20:03:26 $ - $Revision: 1.4 $');
?>
