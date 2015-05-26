<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
*/
/*
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
  */
/*
 */

/**
        \file       htdocs/contrat/index.php
        \ingroup    contrat
        \brief      Page liste des contrats
        \version    $Revision: 1.62 $
*/


require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT ."/notify.class.php");

if (!$user->rights->commande->lire) accessforbidden();

$langs->load("orders");

// Securite acces client
$socid='';
if ($_GET["socid"]) { $socid=$_GET["socid"]; }
if ($user->societe_id > 0)
{
  $action = '';
  $socid = $user->societe_id;
}

  $js="";

$jQueryDashBoardPath = DOL_URL_ROOT.'/Synopsis_Common/jquery/dashboard/';

$js = '
    <script>var DOL_URL_ROOT="'.DOL_URL_ROOT.'";</script>
    <script>var DOL_DOCUMENT_ROOT="'.DOL_DOCUMENT_ROOT.'";</script>
    <script type="text/javascript" src="'.$jQueryDashBoardPath.'jquery.dashboard.js"></script>
    <link rel="stylesheet" type="text/css" href="'.$jQueryDashBoardPath.'dashboard.css" />

    <script type="text/javascript" src="'.$jQueryDashBoardPath.'dashboard.js"></script>
    <link rel="stylesheet" type="text/css" href="'.$jQueryDashBoardPath.'demo.css" />
    <script type="text/javascript">var userid='.$user->id.';</script>
    <script type="text/javascript">var dashtype="23";</script>

';

    llxHeader($js,$langs->trans("Contrats"),0);


    print '<div class="titre">Mon tableau de bord - Contrats</div>';
    print "<br/>";
    print "<br/>";
    print "<div style='padding: 5px 10px; width: 270px;' class='ui-button ui-state-default ui-widget-header ui-corner-all'><em><span style='float: left; margin: -1px 3px 0px 0px' class='ui-icon ui-icon-info'></span><a href='#' onClick='addWidget()'>Ajouter des widgets &agrave; votre tableau de bord.</a></em></div>";
    print "<br/>";
    print '<div id="dashboard">';
    print '  You need javascript to use the dashboard.';
    print '</div>';




//
//require("./pre.inc.php");
//require_once (DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
//
//$langs->load("products");
//$langs->load("companies");
//$langs->load("contracts");
//
//$sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
//$sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
//$page = isset($_GET["page"])?$_GET["page"]:$_POST["page"];
//
//$statut=isset($_GET["statut"])?$_GET["statut"]:1;
//
//// Security check
//$contratid = isset($_GET["id"])?$_GET["id"]:'';
//if ($user->societe_id) $socid=$user->societe_id;
//$result = restrictedArea($user, 'contrat',$contratid,'');
//
//$staticcompany=new Societe($db);
//$staticcontrat=new Contrat($db);
//$staticcontratligne=new ContratLigne($db);
//
//
///*
// * View
// */
//
//llxHeader();
//
//print_fiche_titre($langs->trans("ContractsArea"));
//
//
//print '<table class="notopnoleftnoright" width="100%">';
//
//print '<tr><td width="30%" valign="top" class="notopnoleft">';
//
///*
// * Recherche Contrat
// */
//if ($conf->contrat->enabled)
//{
//    $var=false;
//    print '<form method="post" action="'.DOL_URL_ROOT.'/contrat/list.php">';
//    print '<table class="noborder" width="100%">';
//    print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("SearchAContract").'</td></tr>';
//    print '<tr '.$bc[$var].'>';
//    print '<td nowrap>'.$langs->trans("Ref").':</td><td><input type="text" class="flat" name="search_contract" size="18"></td>';
//    print '<td rowspan="2"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
//    print '<tr '.$bc[$var].'><td nowrap>'.$langs->trans("Other").':</td><td><input type="text" class="flat" name="sall" size="18"></td>';
//    print '</tr>';
//    print "</table></form>\n";
//    print "<br>";
//}
//
//// Legend
//$var=false;
//print '<table class="noborder" width="100%">';
//print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("ServicesLegend").'</td></tr>';
//print '<tr '.$bc[$var].'><td nowrap>';
//print $staticcontratligne->LibStatut(0,4).'<br />';
//print $staticcontratligne->LibStatut(4,4).'<br />';
//print $staticcontratligne->LibStatut(5,4).'<br />';
//print '</td></tr>';
//print '</table>';
//
///**
// * Draft contratcs
// */
//if ($conf->contrat->enabled && $user->rights->contrat->lire)
//{
//        $sql  = "SELECT DISTINCT c.ref as ref, c.rowid, c.type, ";
//        $sql.= " s.nom, s.rowid as socid";
//        if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user ";
//        $sql .= " FROM ".MAIN_DB_PREFIX."contrat as c, ".MAIN_DB_PREFIX."contratdet as cd, ".MAIN_DB_PREFIX."societe as s";
//        if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
//        $sql .= " WHERE s.rowid = c.fk_soc
//                    AND c.statut = 0
//                    AND cd.fk_contrat = c.rowid ";
//        if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
//
//        if ($socid)
//        {
//            $sql .= " AND f.fk_soc = ".$socid;
//        }
//        $sql .= " ORDER BY cd.tms DESC LIMIT 20 ";
//
//    $resql = $db->query($sql);
//
//    if ( $resql )
//    {
//        $var = false;
//        $num = $db->num_rows($resql);
//
//        print '<br>';
//        print '<table class="noborder" width="100%">';
//        print '<tr class="liste_titre">';
//        print '<td colspan="3">'.$langs->trans("DraftContracts").($num?' ('.$num.')':'').'</td>';
////        print "<td>Type</td>";
//        print '</tr>';
//        if ($num)
//        {
//            $companystatic=new Societe($db);
//
//            $i = 0;
//            $tot_ttc = 0;
//            while ($i < $num)
//            {
//                $obj = $db->fetch_object($resql);
//                print '<tr '.$bc[$var].'><td nowrap>';
//                $staticcontrat->ref=$obj->ref;
//                $staticcontrat->id=$obj->rowid;
//                print $staticcontrat->getNomUrl(1,'');
//                print '</td>';
//                print '<td nowrap>';
//                $companystatic->id=$obj->socid;
//                $companystatic->fetch($obj->socid);
//                $companystatic->nom=$obj->nom;
//                $companystatic->client=1;
//                print $companystatic->getNomUrl(1,'',16);
//                print '</td>';
//                $staticcontrat->typeContrat = $obj->type;
//                $typeC = $staticcontrat->getTypeContrat();
//                print "<td>";
//                print $typeC['Nom'];
//                print '</td>';
//                print '</tr>';
//                $tot_ttc+=$obj->total_ttc;
//                $i++;
//                $var=!$var;
//            }
//        }
//        else
//        {
//            print '<tr colspan="3" '.$bc[$var].'><td>'.$langs->trans("NoContracts").'</td></tr>';
//        }
//        print "</table><br>";
//        $db->free($resql);
//    }
//    else
//    {
//        dol_print_error($db);
//    }
//}
//
//print '</td><td width="70%" valign="top" class="notopnoleftnoright">';
//
//
//// Last modified contracts
//$max=5;
//$sql = 'SELECT ';
//$sql.= ' sum('.$db->ifsql("cd.statut=0",1,0).') as nb_initial,';
//$sql.= ' sum('.$db->ifsql("cd.statut=4 AND cd.date_fin_validite > sysdate()",1,0).') as nb_running,';
//$sql.= ' sum('.$db->ifsql("cd.statut=4 AND (cd.date_fin_validite IS NULL OR cd.date_fin_validite <= sysdate())",1,0).') as nb_late,';
//$sql.= ' sum('.$db->ifsql("cd.statut=5",1,0).') as nb_closed,';
//$sql.= " c.rowid as cid, c.ref, c.datec, c.tms, c.statut, s.nom, s.rowid as socid";
//if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
//$sql.= " FROM ".MAIN_DB_PREFIX."societe as s,";
//if (!$user->rights->societe->client->voir && !$socid) $sql .= " ".MAIN_DB_PREFIX."societe_commerciaux as sc,";
//$sql.= " ".MAIN_DB_PREFIX."contrat as c";
//$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contratdet as cd ON c.rowid = cd.fk_contrat";
//$sql.= " WHERE c.fk_soc = s.rowid AND c.statut > 0";
//if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
//if ($socid > 0) $sql .= " AND s.rowid = ".$socid;
//$sql.= " GROUP BY c.rowid, c.datec, c.statut, s.nom, s.rowid";
//$sql.= " ORDER BY c.tms DESC";
//$sql.= " LIMIT ".$max;
//
//$result=$db->query($sql);
//if ($result)
//{
//    $num = $db->num_rows($result);
//    $i = 0;
//
//    print '<table class="noborder" width="100%">';
//
//    print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("LastContracts",5).'</td>';
//    print '<td align="center">'.$langs->trans("DateModification").'</td>';
//    //print '<td align="left">'.$langs->trans("Status").'</td>';
//    print '<td align="right" width="80" colspan="3">'.$langs->trans("Services").'</td>';
//    print "</tr>\n";
//
//    $var=True;
//    while ($i < $num)
//    {
//        $obj = $db->fetch_object($result);
//        $var=!$var;
//
//        print "<tr $bc[$var]>";
//        print "<td>";
//        $staticcontrat->ref=($obj->ref?$obj->ref:$obj->cid);
//        $staticcontrat->id=$obj->cid;
//        print $staticcontrat->getNomUrl(1,16);
//        if ($obj->nb_late) print img_warning($langs->trans("Late"));
//        print '</td>';
//        print '<td>';
//        $staticcompany->id=$obj->socid;
//        $staticcompany->fetch($obj->socid);
//        $staticcompany->nom=$obj->nom;
//        print $staticcompany->getNomUrl(1,'',20);
//        print '</td>';
//        print '<td align="center">'.dol_print_date($obj->tms,'dayhour').'</td>';
//        //print '<td align="left">'.$staticcontrat->LibStatut($obj->statut,2).'</td>';
//        print '<td align="right" width="32">'.($obj->nb_initial>0 ? $obj->nb_initial.$staticcontratligne->LibStatut(0,3):'').'</td>';
//        print '<td align="right" width="32">'.($obj->nb_running+$obj->nb_late>0 ? ($obj->nb_running+$obj->nb_late).$staticcontratligne->LibStatut(4,3):'').'</td>';
//        print '<td align="right" width="32">'.($obj->nb_closed>0 ? $obj->nb_closed.$staticcontratligne->LibStatut(5,3):'').'</td>';
//        print "</tr>\n";
//        $i++;
//    }
//    $db->free($result);
//
//    print "</table>";
//
//}
//else
//{
//    dol_print_error($db);
//}
//
//print '<br>';
//
//
//// Not activated services
//$sql = "SELECT cd.rowid as cid, c.ref, cd.statut, cd.label, cd.description as note, cd.fk_contrat, c.fk_soc, s.nom";
//if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
//$sql.= " FROM ".MAIN_DB_PREFIX."contratdet as cd, ".MAIN_DB_PREFIX."contrat as c, ".MAIN_DB_PREFIX."societe as s";
//if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
//$sql.= " WHERE c.statut=1 AND cd.statut = 0";
//$sql.= " AND is_financement = 0 AND cd.fk_contrat = c.rowid AND c.fk_soc = s.rowid";
//if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
//if ($socid > 0) $sql.= " AND s.rowid = ".$socid;
//$sql.= " ORDER BY cd.tms DESC";
//
//if ( $db->query($sql) )
//{
//    $num = $db->num_rows();
//    $i = 0;
//
//    print '<table class="noborder" width="100%">';
//
//    print '<tr class="liste_titre"><td colspan="4">'.$langs->trans("NotActivatedServices").'</td>';
//    print "</tr>\n";
//
//    $var=True;
//    while ($i < $num)
//    {
//        $obj = $db->fetch_object();
//        $var=!$var;
//        print "<tr $bc[$var]>";
//
//        print '<td>';
//        $staticcontrat->ref=($obj->ref?$obj->ref:$obj->fk_contrat);
//        $staticcontrat->id=$obj->fk_contrat;
//        print $staticcontrat->getNomUrl(1,16);
//        print '</td>';
//        print '<td nowrap="1">';
//        print '<a href="'.DOL_URL_ROOT.'/contrat/card.php?id='.$obj->fk_contrat.'">'.img_object($langs->trans("ShowService"),"service");
//        if ($obj->note) print ' '.dol_trunc(html_entity_decode($obj->note),20).'</a></td>';
//        else print '</a> '.dol_trunc($obj->label,20).'</td>';
//        print '<td>';
//        $staticcompany->id=$obj->fk_soc;
//        $staticcompany->fetch($obj->fk_soc);
//        $staticcompany->nom=$obj->nom;
//        print $staticcompany->getNomUrl(1,'',20);
//        print '</td>';
//        print '<td width="16" align="right"><a href="ligne.php?id='.$obj->fk_contrat.'&ligne='.$obj->cid.'">';
//        print $staticcontratligne->LibStatut($obj->statut,3);
//        print '</a></td>';
//        print "</tr>\n";
//        $i++;
//    }
//    $db->free();
//
//    print "</table>";
//
//}
//else
//{
//    dol_print_error($db);
//}
//
//print '<br>';
//
//// Last modified services
//$max=5;
//
//$sql = "SELECT c.ref, c.fk_soc, ";
//$sql.= " cd.rowid as cid, cd.statut, cd.label, cd.description as note, cd.fk_contrat,";
//$sql.= " s.nom";
//if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
//$sql.= " FROM ".MAIN_DB_PREFIX."contratdet as cd, ".MAIN_DB_PREFIX."contrat as c, ".MAIN_DB_PREFIX."societe as s";
//if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
//$sql.= " WHERE is_financement = 0 AND cd.fk_contrat = c.rowid AND c.fk_soc = s.rowid";
//if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
//if ($socid > 0) $sql.= " AND s.rowid = ".$socid;
//$sql.= " ORDER BY cd.tms DESC";
//
//$resql=$db->query($sql);
//if ($resql)
//{
//    $num = $db->num_rows($resql);
//    $i = 0;
//
//    print '<table class="noborder" width="100%">';
//
//    print '<tr class="liste_titre"><td colspan="4">'.$langs->trans("LastModifiedServices",min($num,$max)).'</td>';
//    print "</tr>\n";
//
//    $var=True;
//    while ($i < min($num,$max))
//    {
//        $obj = $db->fetch_object($resql);
//        $var=!$var;
//        print "<tr $bc[$var]>";
//        print '<td nowrap="nowrap">';
//        $staticcontrat->ref=($obj->ref?$obj->ref:$obj->fk_contrat);
//        $staticcontrat->id=$obj->fk_contrat;
//        print $staticcontrat->getNomUrl(1,16);
//        if ($obj->nb_late) print img_warning($langs->trans("Late"));
//        print '</td>';
//        print '<td><a href="'.DOL_URL_ROOT.'/contrat/card.php?id='.$obj->fk_contrat.'">'.img_object($langs->trans("ShowService"),"service");
//        if ($obj->note) print ' '.dol_trunc(html_entity_decode($obj->note),20).'</a></td>';
//        else print '</a> '.dol_trunc($obj->label,20).'</td>';
//        print '<td>';
//        $staticcompany->id=$obj->fk_soc;
//        $staticcompany->fetch($obj->fk_soc);
//        $staticcompany->nom=$obj->nom;
//        print $staticcompany->getNomUrl(1,'',20);
//        print '</td>';
//        print '<td nowrap="nowrap" align="right"><a href="'.DOL_URL_ROOT.'/contrat/card.php?id='.$obj->fk_contrat.'&ligne='.$obj->cid.'">';
//        print $staticcontratligne->LibStatut($obj->statut,5);
//        print '</a></td>';
//        print "</tr>\n";
//        $i++;
//    }
//    $db->free();
//
//    print "</table>";
//
//}
//else
//{
//    dol_print_error($db);
//}
//
//print '</td></tr></table>';
//
//print '<br>';
//
//$db->close();
//
//llxFooter('$Date: 2008/07/12 11:24:01 $ - $Revision: 1.62 $');
?>
