<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Bariley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013      Cédric Salvador      <csalvador@gpcsolutions.fr>
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
 */

/**
        \file       htdocs/projet/liste.php
        \ingroup    projet
        \brief      Page liste des projets
        \version    $Id: liste.php,v 1.16 2008/03/02 22:20:46 eldy Exp $
*/

require("./pre.inc.php");

$langs->load("project@projet");    
if (!$user->rights->synopsisprojet->lire) accessforbidden();

$socid = ( isset($_REQUEST["socid"]) && is_numeric($_REQUEST["socid"]) ? $_REQUEST["socid"] : 0 );

$title = $langs->trans("Projects");

// Securite acces client
if ($user->societe_id > 0) $socid = $user->societe_id;

if ($socid > 0)
{
  $soc = new Societe($db);
  $soc->fetch($socid);
  $title .= ' (<a href="liste.php">'.$soc->nom.'</a>)';
}

//



/**
 * Affichage de la liste des projets
 *
 */


$jspath = DOL_URL_ROOT."/Synopsis_Common/jquery";
$jqueryuipath = DOL_URL_ROOT."/Synopsis_Common/jquery/ui";
$css = DOL_URL_ROOT."/Synopsis_Common/css";
$imgPath = DOL_URL_ROOT."/Synopsis_Common/images";

$js  = '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/ui.jqgrid.css" />';
$js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/jquery.searchFilter.css" />';
    $js .= ' <script src="'.$jspath.'/jqGrid-4.5/js/i18n/grid.locale-fr.js" type="text/javascript"></script>';
    $js .= ' <script src="'.$jspath.'/jqGrid-4.5/js/jquery.jqGrid.js" type="text/javascript"></script>';
$js .= "<style type='text/css'>body { position: static; }</style>";
$js .= "<style type='text/css'>.ui-progressbar{ height: 13px; background-color: #ffffff; margin: 0px; float: left; width: 100%;}</style>";
$js .= "<style type='text/css'>.ui-progressbar-value{ border:1px solid #000000;float: left; width: 100%; }</style>";


$jqgridJs = <<<EOF
<script type='text/javascript'>
EOF;
$jqgridJs .= 'var gridimgpath="'.$imgPath.'/images/";';
$jqgridJs .= 'var userId="'.$user->id.'";';
$jqgridJs .= 'var socId="'.$socid.'";';

$sql = "SELECT p.rowid as projectid, p.ref, p.title, p.fk_statut, p.public, p.fk_user_creat";
$sql.= ", p.datec as date_create, p.dateo as date_start, p.datee as date_end";
$sql.= ", s.nom, s.rowid as socid";
$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on p.fk_soc = s.rowid";
$sql.= " WHERE p.entity = ".$conf->entity;
if ($mine || ! $user->rights->projet->all->lire) $sql.= " AND p.rowid IN (".$projectsListId.")";
// No need to check company, as filtering of projects must be done by getProjectsAuthorizedForUser
//if ($socid || ! $user->rights->societe->client->voir)	$sql.= "  AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".$socid.")";
if ($socid) $sql.= "  AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".$socid.")";
if ($search_ref)
{
	$sql .= natural_search('p.ref', $search_ref);
}
if ($search_label)
{
	$sql .= natural_search('p.title', $search_label);
}
if ($search_societe)
{
	$sql .= natural_search('s.nom', $search_societe);
}
$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($conf->liste_limit+1, $offset);

dol_syslog("list allowed project sql=".$sql);
$resql = $db->query($sql);
if ($resql)
{
	$var=true;
	$num = $db->num_rows($resql);
	$i = 0;

        }
        jQuery("#ui-datepicker-div").addClass("promoteZ");
        jQuery("#ui-timepicker-div").addClass("promoteZ");

});

</script>
EOF;

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"p.ref","","","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Label"),$_SERVER["PHP_SELF"],"p.title","","","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","","","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Visibility"),$_SERVER["PHP_SELF"],"p.public","","","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],'p.fk_statut',"","",'align="right"',$sortfield,$sortorder);
	print "</tr>\n";

	print '<tr class="liste_titre">';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_ref" value="'.$search_ref.'" size="6">';
	print '</td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_label" value="'.$search_label.'">';
	print '</td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_societe" value="'.$search_societe.'">';
	print '</td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre" align="right"><input class="liste_titre" type="image" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'"></td>';
	print "</tr>\n";

	while ($i < $num)
	{
		$objp = $db->fetch_object($resql);

		$projectstatic->id = $objp->projectid;
		$projectstatic->user_author_id = $objp->fk_user_creat;
		$projectstatic->public = $objp->public;

		$userAccess = $projectstatic->restrictedProjectArea($user);

		if ($userAccess >= 0)
		{
			$var=!$var;
			print "<tr ".$bc[$var].">";

			// Project url
			print "<td>";
			$projectstatic->ref = $objp->ref;
			print $projectstatic->getNomUrl(1);
			print "</td>";

			// Title
			print '<td>';
			print dol_trunc($objp->title,24);
			print '</td>';

			// Company
			print '<td>';
			if ($objp->socid)
			{
				$socstatic->id=$objp->socid;
				$socstatic->nom=$objp->nom;
				print $socstatic->getNomUrl(1);
			}
			else
			{
				print '&nbsp;';
			}
			print '</td>';

			// Visibility
			print '<td align="left">';
			if ($objp->public) print $langs->trans('SharedProject');
			else print $langs->trans('PrivateProject');
			print '</td>';

			// Status
			$projectstatic->statut = $objp->fk_statut;
			print '<td align="right">'.$projectstatic->getLibStatut(3).'</td>';

			print "</tr>\n";

		}

		$i++;
	}

	$db->free($resql);
}
else
{
	dol_print_error($db);
}

print "</table>";




//$staticsoc=new Societe($db);
//
//$sql = "SELECT p.rowid as projectid, p.ref, p.title, p.dateo as do";
//$sql .= ", s.nom, s.rowid as socid, s.client";
//if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
//$sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
//if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
//$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on s.rowid = p.fk_soc";
//$sql .= " WHERE 1 = 1 ";
//if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
//if ($socid)
//{
//  $sql .= " AND s.rowid = ".$socid;
//}
//if ($_GET["search_ref"])
//{
//  $sql .= " AND p.ref LIKE '%".addslashes($_GET["search_ref"])."%'";
//}
//if ($_GET["search_label"])
//{
//  $sql .= " AND p.title LIKE '%".addslashes($_GET["search_label"])."%'";
//}
//if ($_GET["search_societe"])
//{
//  $sql .= " AND s.nom LIKE '%".addslashes($_GET["search_societe"])."%'";
//}
//$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);
//
//$var=true;
//$resql = $db->query($sql);
//if ($resql)
//{
//  $num = $db->num_rows($resql);
//  $i = 0;

//print_barre_liste($langs->trans("Projects"), $page, "liste.php", "", $sortfield, $sortorder, "", $num);

print '<table id="gridListProj" class="scroll" cellpadding="0" cellspacing="0"></table>';
print '<div id="gridListProjPager" class="scroll" style="text-align:center;"></div>';

//  print '<table class="noborder" width="100%">';
//  print '<tr class="liste_titre">';
//  print_liste_field_titre($langs->trans("Ref"),"liste.php","p.ref","","","",$sortfield,$sortorder);
//  print_liste_field_titre($langs->trans("Label"),"liste.php","p.title","","","",$sortfield,$sortorder);
//  print_liste_field_titre($langs->trans("Company"),"liste.php","s.nom","","","",$sortfield,$sortorder);
//  print '<td>&nbsp;</td>';
//  print "</tr>\n";
//
//  print '<form method="get" action="liste.php">';
//  print '<tr class="liste_titre">';
//  print '<td valign="right">';
//  print '<input type="text" class="flat" name="search_ref" value="'.$_GET["search_ref"].'">';
//  print '</td>';
//  print '<td valign="right">';
//  print '<input type="text" class="flat" name="search_label" value="'.$_GET["search_label"].'">';
//  print '</td>';
//  print '<td valign="right">';
//  print '<input type="text" class="flat" name="search_societe" value="'.$_GET["search_societe"].'">';
//  print '</td>';
//  print '<td class="liste_titre" align="center"><input class="liste_titre" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans("Search").'">';
//  print "</td>";
//  print "</tr>\n";
//
//  while ($i < $num)
//    {
//      $objp = $db->fetch_object($resql);
//      $var=!$var;
//      print "<tr $bc[$var]>";
//      print "<td><a href=\"card.php?id=$objp->projectid\">".img_object($langs->trans("ShowProject"),"project")." ".$objp->ref."</a></td>\n";
//      print "<td><a href=\"card.php?id=$objp->projectid\">".$objp->title."</a></td>\n";
//
//      // Company
//      print '<td>';
//      if ($objp->socid)
//      {
//          $staticsoc->id=$objp->socid;
//          $staticsoc->nom=$objp->nom;
//          print $staticsoc->getNomUrl(1);
//         }
//         else
//         {
//         print '&nbsp;';
//        }
//    print '</td>';
//
//      print '<td>&nbsp;</td>';
//      print "</tr>\n";
//
//      $i++;
//    }
//
//  $db->free($resql);
//}
//else
//{
//  dol_print_error($db);
//}
//
//print "</table>";

$db->close();


llxFooter('$Date: 2008/03/02 22:20:46 $ - $Revision: 1.16 $');

?>
