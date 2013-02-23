<?php
/* Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.*//*
  * GLE by Synopsis & DRSI
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
        \file       htdocs/contrat/info.php
        \ingroup    contrat
        \brief      Page des informations d'un contrat
        \version    $Id: info.php,v 1.16 2008/03/01 01:26:47 eldy Exp $
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/lib/contract.lib.php');
require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");

$langs->load("contracts");

// Security check
$contratid = isset($_GET["id"])?$_GET["id"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'contrat',$contratid,'');


/*
* View
*/

llxHeader();

$contrat = $contrat=getContratObj($_REQUEST["id"]);
$contrat->fetch($_GET["id"]);
$contrat->info($_GET["id"]);

$head = contract_prepare_head($contrat);
$head = $contrat->getExtraHeadTab($head);

$hselected = 4;

dolibarr_fiche_head($head, $hselected, $langs->trans("Contract"));


print '<table width="100%"><tr><td>';
dolibarr_print_object_info($contrat);
print '</td></tr></table>';


//Liste renouvellement
print "<br/>";
print "<div class='titre'>D&eacute;tails</div>";
print "<br/>";

$contrat=getContratObj($_GET["id"]);
$result=$contrat->fetch($_GET["id"]);
if ($result > 0) $result=$contrat->fetch_lignes(true);
print "<ul style='list-style: none; padding-left: 0px; padding-top:0; margin-bottom:0; margin-top: 0px;'>";
print "<table id='jsContrat'>";
$html = "";
foreach($contrat->lignes as $key=>$val)
{
    $requete = "SELECT durRenew,
                       unix_timestamp(date_renouvellement) as date_renouvellement
                  FROM Babel_contrat_renouvellement
                 WHERE contratdet_refid = ".$val->id
           ." ORDER BY date_renouvellement DESC
                  ";
    $sql = $db->query($requete);
    while ($res = $db->fetch_object($sql))
    {
        if ($res->date_renouvellement > 0)
        {
            $html .= "<tr><td colspan=2><li>";
            $html .= $contrat->display1Line($val);
            $html .= '<tr><td colspan=2 class="ui-widget-content" style="padding:2em;">'.img_info().'  Renouv. le '.date('d/m/Y',$res->date_renouvellement). " pour ".$res->durRenew."m";
        }
    }
    if ($val->statut>4){
        $html .= '<tr><td class="ui-widget-content" style="padding:2em;" colspan=2>'.img_warning().'  Cl&ocirc;turer le '.$val->date_fin_reel;
    }
}
print $html;
print "</table><ul>";
print '</div>';
print  "<script>";
print  <<<EOF
    jQuery(document).ready(function(){
        jQuery("#jsContrat li").dblclick(function(){
            var id = jQuery(this).attr('id');
            location.href=DOL_URL_ROOT+'/Synopsis_Contrat/contratDetail.php?id='+id;
                });
            });

EOF;

print  "</script>";


// Juste pour eviter bug IE qui reorganise mal div precedents si celui-ci absent
print '<div class="tabsAction">';
print '</div>';

$db->close();

llxFooter('$Date: 2008/03/01 01:26:47 $ - $Revision: 1.16 $');
?>
