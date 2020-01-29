<?php
/* Copyright (C) 2012-2017		Charlene Benke	<charlie@patas-monkey.com>
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
 *	\file	   htdocs/equipement/list.php
 *	\brief	  List of all equipement
 *	\ingroup	equipement
 */
$res=@include("../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../main.inc.php");		// For "custom" directory

require_once DOL_DOCUMENT_ROOT."/product/class/product.class.php";
require_once DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php";
require_once DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php";
require_once DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/class/html.form.class.php";

dol_include_once('/equipement/class/equipement.class.php');
dol_include_once('/equipement/core/lib/equipement.lib.php');

$langs->load("companies");
$langs->load("equipement@equipement");

$productid=GETPOST('productid', 'int');


// Security check
$equipementid = GETPOST('id', 'int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'equipement', $equipementid, 'equipement');

$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
if ($page == -1)
	$page = 0;

$limit = $conf->liste_limit;
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;


if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="e.ref";


$sall=GETPOST('sall', 'alpha');
$search_ref=GETPOST('search_ref', 'alpha');
$search_refProduct=GETPOST('search_refProduct', 'alpha');
$search_labelProduct=GETPOST('search_labelProduct', 'alpha');
$search_numversion=GETPOST('search_numversion', 'alpha');
$search_company_fourn=GETPOST('search_company_fourn', 'alpha');
$search_reffact_fourn=GETPOST('search_reffact_fourn', 'alpha');
$search_company_client=GETPOST('search_company_client', 'alpha');
$search_reffact_client=GETPOST('search_reffact_client', 'alpha');
$search_entrepot=GETPOST('search_entrepot', 'alpha');
if ($search_entrepot=="") $search_entrepot="-1";
$search_etatequipement=GETPOST('search_etatequipement', 'alpha');
if ($search_etatequipement=="") $search_etatequipement="-1";
$viewstatut=GETPOST('viewstatut', 'alpha');
if ($viewstatut=="") $viewstatut="-1";

$equipementstatic=new Equipement($db);

/*
 *	Action
 */

if (GETPOST("updatecheck") == $langs->trans("Update")) {

	$separatorlist=$conf->global->EQUIPEMENT_SEPARATORLIST;
	if (!isset($separatorlist)) // Si non saisie on utilise le ; par défaut
		$separatorlist=";";
	if ($separatorlist == "__N__")
		$separatorlist="\n";
	if ($separatorlist == "__B__")
		$separatorlist="\b";

	$idlist=explode($separatorlist, GETPOST("idlist"));
	
	foreach ($idlist as $key) {
		if (GETPOST(trim("chk-".$key))) {
			// on récupère les anciennes valeurs
			$equipementstatic->fetch($key);
			// on met à jours que si la case est cochée

			if (GETPOST("chk_statut"))
				$equipementstatic->fk_statut = GETPOST("update_statut");
			else
				$equipementstatic->fk_statut = -1;

			if (GETPOST("chk_etatequipement"))
				$equipementstatic->fk_etatequipement = GETPOST("update_etatequipement");
			else
				$equipementstatic->fk_etatequipement = 0;

			if (GETPOST("chk_soc_client"))
				$equipementstatic->fk_soc_client = GETPOST("update_soc_client");
			else
				$equipementstatic->fk_soc_client = 0;

			if (GETPOST("chk_entrepot"))
				$equipementstatic->fk_etatentrepot = GETPOST("update_entrepot");
			else
				$equipementstatic->fk_etatentrepot = 0;

			if (GETPOST("chk_datee")) {
				if (GETPOST("datee"))
					$equipementstatic->datee = dol_mktime(
									'23', '59', '59',
									GETPOST("dateemonth"), GETPOST("dateeday"), GETPOST("dateeyear")
					);
				else
					$equipementstatic->datee = -1;
			} else
				$equipementstatic->fk_datee = 0;

			if (GETPOST("chk_dateo")) {
				if (GETPOST("dateo"))
					$equipementstatic->dateo = dol_mktime(
									'23', '59', '59',
									GETPOST("dateomonth"), GETPOST("dateoday"), GETPOST("dateoyear")
					);
				else
					$equipementstatic->dateo = -1;
			} else
				$equipementstatic->fk_dateo = 0;


//var_dump($equipementstatic);
			// on met à jour l'équipement
			$equipementstatic->updateInfos($user, GETPOST("update_entrepotmove"));
		}
	}
}

/*
 *	View
 */

llxHeader();

$sql = "SELECT";
$sql.= " e.ref, e.rowid, e.fk_statut, e.fk_product, p.ref as refproduit, p.label as labelproduit,";
$sql.= " e.fk_entrepot, e.quantity,";
$sql.= " e.fk_soc_fourn, sfou.nom as CompanyFourn, e.fk_facture_fourn, ff.ref as refFactureFourn,";
$sql.= " e.fk_soc_client, scli.nom as CompanyClient, e.fk_facture, f.ref as refFacture,";
$sql.= " e.datee, e.dateo, e.dated, ee.libelle as etatequiplibelle, e.numversion ";

$sql.= " FROM ".MAIN_DB_PREFIX."equipement as e";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_equipement_etat as ee on e.fk_etatequipement = ee.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as sfou on e.fk_soc_fourn = sfou.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."entrepot as ent on e.fk_entrepot = ent.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as scli on e.fk_soc_client = scli.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f on e.fk_facture = f.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture_fourn as ff on e.fk_facture_fourn = ff.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on e.fk_product = p.rowid";
$sql.= " WHERE e.entity = ".$conf->entity;

if ($sall) {
	$sql .= " AND (e.ref like '%".$db->escape($sall)."%'";
	$sql .= " OR p.ref like '%".$db->escape($sall)."%'";
	$sql .= " OR p.label like '%".$db->escape($sall)."%'";
	$sql .= " OR e.numversion like '%".$db->escape($sall)."%'";
	$sql .= " OR sfou.nom like '%".$db->escape($sall)."%'";
	$sql .= " OR ff.ref like '%".$db->escape($sall)."%'";
	$sql .= " OR scli.nom like '%".$db->escape($sall)."%'";
	$sql .= " OR f.ref like '%".$db->escape($sall)."%'";
	$sql .= " )";
} else {
	if ($search_ref)				$sql .= " AND e.ref like '%".$db->escape($search_ref)."%'";
	if ($search_labelProduct)		$sql .= " AND p.label like '%".$db->escape($search_labelProduct)."%'";
	if ($search_refProduct)			$sql .= " AND p.ref like '%".$db->escape($search_refProduct)."%'";
	if ($search_numversion)			$sql .= " AND e.numversion like '%".$db->escape($search_numversion)."%'";
	if ($search_company_fourn)		$sql .= " AND sfou.nom like '%".$db->escape($search_company_fourn)."%'";
	if ($search_reffact_fourn)		$sql .= " AND ff.ref like '%".$db->escape($search_reffact_fourn)."%'";
	if ($search_company_client)		$sql .= " AND scli.nom like '%".$db->escape($search_company_client)."%'";
	if ($search_reffact_client)		$sql .= " AND f.ref like '%".$db->escape($search_reffact_client)."%'";
}
if ($search_entrepot >=0)		$sql .= " AND ent.rowid =".$search_entrepot;
if ($search_etatequipement>=0)	$sql .= " AND e.fk_etatequipement =".$search_etatequipement;
if ($viewstatut >=0)			$sql .= " AND e.fk_statut =".$viewstatut;
$sql.= " ORDER BY ".$sortfield." ".$sortorder;
$sql.= $db->plimit($limit+1, $offset);

//print $sql;
$result=$db->query($sql);

if ($result) {
	$num = $db->num_rows($result);
	$urlparam="";
	if ($sall)						$urlparam .= "&sall=".$db->escape($sall);
	if ($search_ref)				$urlparam .= "&search_ref=".$db->escape($search_ref);
	if ($search_refProduct)			$urlparam .= "&search_refProduct=".$db->escape($search_refProduct);
	if ($search_labelProduct)		$urlparam .= "&search_labelProduct=".$db->escape($search_labelProduct);
	if ($search_numversion)			$urlparam .= "&search_numversion=".$db->escape($search_numversion);
	if ($search_company_fourn)		$urlparam .= "&search_company_fourn=".$db->escape($search_company_fourn);
	if ($search_reffact_fourn)		$urlparam .= "&search_reffact_fourn=".$db->escape($search_reffact_fourn);
	if ($search_entrepot)			$urlparam .= "&search_entrepot=".$search_entrepot;
	if ($search_company_client)		$urlparam .= "&search_company_client=".$db->escape($search_company_client);
	if ($search_reffact_client)		$urlparam .= "&search_reffact_client=".$db->escape($search_reffact_client);
	if ($search_etatequipement>=0)	$urlparam .= "&search_etatequipement=".$search_etatequipement;
	if ($viewstatut >=0)			$urlparam .= "&viewstatut=".$viewstatut;

	print_barre_liste($langs->trans("ListOfEquipements"), $page, "list.php", $urlparam, $sortfield, $sortorder, '', $num);

	print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">'."\n";
	print '<input type=hidden name=page value="'.($page).'">';
	print '<table class="noborder" width="100%">';
	
	print "<tr class=\"liste_titre\">";
	print_liste_field_titre($langs->trans("Ref"), $_SERVER["PHP_SELF"],"e.ref","", $urlparam,'', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("RefProduit").'<br>'.$langs->trans("Label"), $_SERVER["PHP_SELF"],"p.ref","", $urlparam,'', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("NumVersion"), $_SERVER["PHP_SELF"],"e.numversion","", $urlparam,'', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("FournisseurFactFourn") , $_SERVER["PHP_SELF"],"sfou.nom","", $urlparam,'align="left" width=200px', $sortfield, $sortorder);
	//print_liste_field_titre($langs->trans("FF"), $_SERVER["PHP_SELF"],"ff.ref","", $urlparam,'', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Entrepot"), $_SERVER["PHP_SELF"],"ent.label","", $urlparam,'align="left" width=150px', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("CompanyClientFactCli"), $_SERVER["PHP_SELF"],"scli.nom","", $urlparam,'align="left" width=200px', $sortfield, $sortorder);
	//print_liste_field_titre($langs->trans("FC"), $_SERVER["PHP_SELF"],"f.ref","", $urlparam,'', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Dateo"), $_SERVER["PHP_SELF"],"e.dateo","", $urlparam,'align="center" width=100px', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Datee"), $_SERVER["PHP_SELF"],"e.datee","", $urlparam,'align="center" width=100px', $sortfield, $sortorder);
	if ($conf->global->EQUIPEMENT_USEDLUODATE == 1) 
		print_liste_field_titre(
						$langs->trans("Dated"), $_SERVER["PHP_SELF"], "e.dated", "",
						$urlparam, 'align="center" width=100px', $sortfield, $sortorder
		);
	
	print_liste_field_titre($langs->trans("EtatEquip"), $_SERVER["PHP_SELF"],"e.fk_equipementetat","", $urlparam,'align="center" width=150px', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Status"), $_SERVER["PHP_SELF"],"e.fk_statut","", $urlparam,'align="right"', $sortfield, $sortorder);
	print '<th align=right>'.$langs->trans("SelAll").'&nbsp;<input type=checkbox id="dochkall"></th>';

	print "</tr>\n";

	print '<tr class="liste_titre">';
	print '<td class="liste_titre" valign=top>';
	print '<input type="text" class="flat" name="search_ref" value="'.$search_ref.'" size="8"></td>';
	print '<td class="liste_titre" valign=top>';
	print '<input type="text" class="flat" name="search_refProduct" value="'.$search_refProduct.'" size="8">';
	print '<input type="text" class="flat" name="search_labelProduct" value="'.$search_labelProduct.'" size="10">';

	print '</td>';
	print '<td class="liste_titre" valign=top>';
	print '<input type="text" class="flat" name="search_numversion" value="'.$search_numversion.'" size="8"></td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_reffact_fourn" value="'.$search_reffact_fourn.'" size="8">&nbsp;';
	print '<input type="text" class="flat" name="search_company_fourn" value="'.$search_company_fourn.'" size="10"></td>';
	print '<td class="liste_titre">';
	select_entrepot($search_entrepot, 'search_entrepot', 1, 1, 0, 0);
	print '</td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_reffact_client" value="'.$search_reffact_client.'" size="8">&nbsp;';
	print '<input type="text" class="flat" name="search_company_client" value="'.$search_company_client.'" size="10"></td>';

	print '<td class="liste_titre" colspan="1" align="right">';
	print '<input class="flat" type="text" size="1" maxlength="2" name="monthdatee" value="'.$monthdatee.'">';
	$syear = $yeardatee;
	if ($syear == '') $syear = date("Y");
	print '&nbsp;/&nbsp;<input class="flat" type="text" size="3" maxlength="4" name="yeardatee" value="'.$syear.'">';
	print '</td>';

	print '<td class="liste_titre" colspan="1" align="right">';
	print '<input class="flat" type="text" size="1" maxlength="2" name="monthdateo" value="'.$monthdateo.'">';
	$syear = $yeardateo;
	if ($syear == '') $syear = date("Y");
	print '&nbsp;/&nbsp;<input class="flat" type="text" size="3" maxlength="4" name="yeardateo" value="'.$syear.'">';
	print '</td>';

	if ($conf->global->EQUIPEMENT_USEDLUODATE == 1) {
		print '<td class="liste_titre" colspan="1" align="right">';
		print '<input class="flat" type="text" size="1" maxlength="2" name="monthdated" value="'.$monthdated.'">';
		$syear = $yeardated;
		if ($syear == '') $syear = date("Y");
		print '&nbsp;/&nbsp;<input class="flat" type="text" size="3" maxlength="4" name="yeardated" value="'.$syear.'">';
		print '</td>';
	}

	// liste des état des équipements
	print '<td class="liste_titre" align="right">';
	print select_equipement_etat($search_etatequipement, 'search_etatequipement', 1, 1);

	print '</td>';

	print '<td class="liste_titre" align="right">';

	print '<select class="flat" name="viewstatut">';
	print '<option value="-1">&nbsp;</option>';
	print '<option ';
	if ($viewstatut=='0') print ' selected ';
	print ' value="0">'.$equipementstatic->LibStatut(0).'</option>';
	print '<option ';
	if ($viewstatut=='1') print ' selected ';
	print ' value="1">'.$equipementstatic->LibStatut(1).'</option>';
	print '<option ';
	if ($viewstatut=='2') print ' selected ';
	print ' value="2">'.$equipementstatic->LibStatut(2).'</option>';
	print '</select>';
	print '</td><td>';
	print '<input class="liste_titre" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png"';
	print ' value="'.dol_escape_htmltag($langs->trans("Search")).'"';
	print ' title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '</td>';
	print "</tr>\n";


	$var=True;
	$idlist="";
	$reflist="";
	$total = 0;
	$i = 0;
	$separatorlist=$conf->global->EQUIPEMENT_SEPARATORLIST;
	$separatorlist =($separatorlist ? $separatorlist : ";");
	if ($separatorlist == "__N__")
		$separatorlist="\n";
	if ($separatorlist == "__B__")
		$separatorlist="\b";

	while ($i < min($num, $limit)) {
		$objp = $db->fetch_object($result);

		$idlist.=$objp->rowid.$separatorlist;
		$reflist.=$objp->ref.$separatorlist;
		$var=!$var;
		print "<tr $bc[$var]>";
		print "<td><a href=# onclick=\"$('.detailligne".$i."').toggle();\" >";
		print img_picto("", "edit_add")."</a>&nbsp;";
		$equipementstatic->id=$objp->rowid;
		$equipementstatic->ref=$objp->ref;
		print $equipementstatic->getNomUrl(1);
		// si c'est un lot
		if ($objp->quantity > 1)
			print ' ('.$objp->quantity.')';
		print "</td>";

		// toujours un produit associé à un équipement
		$productstatic=new Product($db);
		$productstatic->fetch($objp->fk_product);
		print '<td title="'.$productstatic->label.'">';
		print $productstatic->getNomUrl(1);
		print '</td>';
		
		print "<td>";
			print $objp->numversion;
		print '</td>';

		print "<td>";
		if ($objp->fk_soc_fourn > 0) {
			$socfourn = new Societe($db);
			$socfourn->fetch($objp->fk_soc_fourn);
			print $socfourn->getNomUrl(1);
		}
		print '</td>';

		// entrepot
		print "<td>";
		if ($objp->fk_entrepot>0) {
			$entrepotstatic = new Entrepot($db);
			$entrepotstatic->fetch($objp->fk_entrepot);
			print $entrepotstatic->getNomUrl(1);
		}
		print '</td>';

		print "<td>";
		if ($objp->fk_soc_client > 0) {
			$soc = new Societe($db);
			$soc->fetch($objp->fk_soc_client);
			print $soc->getNomUrl(1);
		}
		print '</td>';

		print "<td nowrap align='center'>".dol_print_date($db->jdate($objp->dateo), 'day')."</td>\n";
		print "<td nowrap align='center'>".dol_print_date($db->jdate($objp->datee), 'day')."</td>\n";
		if ($conf->global->EQUIPEMENT_USEDLUODATE == 1) 
			print "<td nowrap align='center'>".dol_print_date($db->jdate($objp->dated), 'day')."</td>\n";

		print '<td align="right">';
		if ($objp->etatequiplibelle)
			print $langs->trans($objp->etatequiplibelle);
		print '</td>';
		print '<td align=right>';
		print $equipementstatic->LibStatut($objp->fk_statut, 5);
		print '</td><td align=right>';
		print '<input type=checkbox class=chkall name=chk-'.$objp->rowid.'>';
		print '</td>';

		// seconde ligne
		print "</tr>\n";
		if ($bc[$var]=='class="pair"')
			print "<tr style='display:none' class='pair detailligne".$i."'>";
		else
			print "<tr style='display:none' class='impair detailligne".$i."'>";
		print "<td>".$equipementstatic->label."</td>";
		print "<td colspan=2>".$productstatic->label."</td>";

		print "<td >";
		if ($equipementstatic->unitweight !=0)
			print $langs->trans("RefFactFourn")." : ".$equipementstatic->unitweight;
		print "</td>";
		print "<td align=left>";
		if ( $objp->fk_facture_fourn > 0 
			&& $user->rights->facture->lire) {
			$factfournstatic = new FactureFournisseur($db);
			$factfournstatic->fetch($objp->fk_facture_fourn);
			print $factfournstatic ->getNomUrl(1);
		}
		print '</td>';
		//print '<td></td>';
		print "<td align=left>";
		if ($objp->fk_facture > 0 
			&& $user->rights->facture->lire) {
			$facturestatic=new Facture($db);
			$facturestatic->fetch($objp->fk_facture);
			print $facturestatic ->getNomUrl(1);
		}
		print '</td>';
		print "<td colspan=5>&nbsp;</td>";

		print "</tr>\n";
		$i++;
	}
	print '</table>';

	print "<label id='showreflist'>";
	print img_picto("", "edit_add")."&nbsp;".$langs->trans("ShowRefEquipement")."</label><br>";
	print '<textarea cols="80" id="reflist" style="display:none;" rows="'.ROWS_6.'">'.$reflist.'</textarea>';
	print '<br>';

	$form = new Form($db);

	print_fiche_titre($langs->trans("EquipementMassChange"));
	print '<br>';
	// to do : after test made it hidden
	print "<input type=hidden size=50 name=idlist value='".$idlist."'>";
	print '<table class="border" width="50%">';

	print "<tr >";
	print "<td align=left >".$langs->trans("Entrepot")."</td>";
	print "<td align=left > ";
	print '<input type=checkbox name="chk_entrepot">&nbsp;';
	select_entrepot($update_entrepot, 'update_entrepot', 1, 1, 0, 1);
	print "</td>";
	print "</tr >";	

	print "<tr >";
	print "<td align=left >".$langs->trans("Customer")."</td>";
	print "<td align=left >";
	print '<input type=checkbox name="chk_soc_client" >';
	print $form->select_company('', 'update_soc_client', '', 'SelectThirdParty', 1);
	print "</td>";
	print "</tr >";	

	print "<tr >";
	print "<td align=left >".$langs->trans("Dateo")."</td>";
	print "<td align=left >";
	print '<input type=checkbox name="chk_dateo" >&nbsp;';
	print $form->select_date('', 'dateo', 0, 0, 1, "dateo");
	print "</td>";
	print "</tr >";	

	print "<tr >";
	print "<td align=left >".$langs->trans("Datee")."</td>";
	print "<td align=left >";
	print '<input type=checkbox name="chk_datee" >&nbsp;';
	print $form->select_date('', 'datee', 0, 0, 1, "datee");
	print "</td>";
	print "</tr >";	

	if ($conf->global->EQUIPEMENT_USEDLUODATE == 1) {
		// Date DLUO
		print "<tr >";
		print "<td align=left >".$langs->trans("DateDluo")."</td>";
		print "<td align=left >";
		print '<input type=checkbox name="chk_dated" >&nbsp;';
		print $form->select_date('', 'dated', 0, 0, 1, "dated");
		print "</td>";
		print "</tr >";	
	}

	print "<tr >";
	print "<td align=left >".$langs->trans("EtatEquip")."</td>";
	print "<td align=left >";
	print '<input type=checkbox name="chk_etatequipement" >&nbsp;';
	print select_equipement_etat('', 'update_etatequipement', 1, 1);
	print "</td>";
	print "</tr >";	

	print "<tr >";
	print "<td align=left >".$langs->trans("Status")."</td>";	
	print "<td align=left>";
	print '<input type=checkbox name="chk_statut" >&nbsp;';
	print '<select class="flat" name="update_statut">';
	print '<option value="-1">&nbsp;</option>';
	print '<option value="0">'.$equipementstatic->LibStatut(0).'</option>';
	print '<option value="1">'.$equipementstatic->LibStatut(1).'</option>';
	print '<option value="2">'.$equipementstatic->LibStatut(2).'</option>';
	print '</select>';
	print "</td>";
	print "</tr >";	

	print "<tr >";	
	print "<td align=center colspan=2>";
	print "<input type=submit name='updatecheck' value='".$langs->trans("Update")."'>";
	print "</td>";
	print "</tr>\n";

	//print '<tr class="liste_total"><td colspan="7" class="liste_total">'.$langs->trans("Total").'</td>';
	//print '<td align="right" nowrap="nowrap" class="liste_total">'.$i.'</td><td>&nbsp;</td>';
	//print '</tr>';

	print '</table>';
//	dol_fiche_end();
	print "</form>\n";
	$db->free($result);
}
else
	dol_print_error($db);

llxFooter();
$db->close();

?>
<script>
$(document).ready(function() {
	$('#showreflist').click(function() {  //on click 
		$('#reflist').toggle();
	});	

	$('#dochkall').click(function(event) {  //on click 
		if (this.checked) { // check select status
			$('.chkall').each(function() { //loop through each checkbox
				this.checked = true;  
			});
		}else{
			$('.chkall').each(function() { //loop through each checkbox
				this.checked = false; 
			});
		}
	});
});
</script>