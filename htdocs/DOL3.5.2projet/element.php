<?php

/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2008 Regis Houssin        <regis@dolibarr.fr>
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
 * * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.1
 * Create on : 4-1-2009
 *
 * Infos on http://www.finapro.fr
 *
 */
/*
 */

/**
  \file       htdocs/projet/element.php
  \ingroup    projet facture
  \brief      Page des elements par projet
  \version    $Id: element.php,v 1.3 2008/04/29 06:23:29 hregis Exp $
 */
require("./pre.inc.php");

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
if (! empty($conf->propal->enabled))      require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
if (! empty($conf->facture->enabled))     require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
if (! empty($conf->facture->enabled))     require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture-rec.class.php';
if (! empty($conf->commande->enabled))    require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
if (! empty($conf->fournisseur->enabled)) require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
if (! empty($conf->fournisseur->enabled)) require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
if (! empty($conf->contrat->enabled))     require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
if (! empty($conf->ficheinter->enabled))  require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
if (! empty($conf->deplacement->enabled)) require_once DOL_DOCUMENT_ROOT.'/compta/deplacement/class/deplacement.class.php';
if (! empty($conf->agenda->enabled))      require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

if (!isset($_REQUEST['action']))
    $_REQUEST['action'] = '';

require_once(DOL_DOCUMENT_ROOT . "/comm/propal/class/propal.class.php");
require_once(DOL_DOCUMENT_ROOT . "/synopsischrono/class/chrono.class.php");
require_once(DOL_DOCUMENT_ROOT . "/projet/class/project.class.php");
require_once(DOL_DOCUMENT_ROOT . "/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT . "/commande/class/commande.class.php");
require_once(DOL_DOCUMENT_ROOT . "/fourn/class/fournisseur.facture.class.php");
require_once(DOL_DOCUMENT_ROOT . "/fourn/class/fournisseur.commande.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/synopsis_project.lib.php");

$langs->load("project@projet");
$langs->load("companies");
$langs->load("suppliers");
if (isset($conf->facture->enabled))
    $langs->load("bills");
if (isset($conf->commande->enabled))
    $langs->load("orders");
if (isset($conf->propal->enabled))
    $langs->load("propal");

// Securite acces client
$projetid = '';
if ($_GET["id"]) {
    $projetid = $_GET["id"];
}

if ($projetid == '')
    accessforbidden();

// Security check
if ($user->societe_id)
    $socid = $user->societe_id;
$result = restrictedArea($user, 'synopsisprojet', $projetid, 'Synopsis_projet_view');


if ($_REQUEST['action'] == "addElement") {
    if ($_REQUEST['addpropal'] > 0) {
        $requete = "UPDATE " . MAIN_DB_PREFIX . "propal SET fk_projet = " . $_REQUEST['id'] . " WHERE rowid = " . $_REQUEST['addpropal'];
        $sql = $db->query($requete);
    }
    if ($_REQUEST['addcommande'] > 0) {
        $requete = "UPDATE " . MAIN_DB_PREFIX . "commande SET fk_projet = " . $_REQUEST['id'] . " WHERE rowid = " . $_REQUEST['addcommande'];
        $sql = $db->query($requete);
    }
    if ($_REQUEST['addfacture'] > 0) {
        $requete = "UPDATE " . MAIN_DB_PREFIX . "facture SET fk_projet = " . $_REQUEST['id'] . " WHERE rowid = " . $_REQUEST['addfacture'];
        $sql = $db->query($requete);
    }
    if ($_REQUEST['addcommande_fournisseur'] > 0) {
        $requete = "UPDATE commande_fournisseur SET fk_projet = " . $_REQUEST['id'] . " WHERE rowid = " . $_REQUEST['addcommande_fournisseur'];
        $sql = $db->query($requete);
    }
    if ($_REQUEST['addfacture_fournisseur'] > 0) {
        $requete = "UPDATE facture_fournisseur SET fk_projet = " . $_REQUEST['id'] . " WHERE rowid = " . $_REQUEST['addfacture_fournisseur'];
        $sql = $db->query($requete);
    }
    if ($_REQUEST['addsynopsischrono'] > 0) {
        $requete = "UPDATE " . MAIN_DB_PREFIX . "synopsischrono SET projetId = " . $_REQUEST['id'] . " WHERE id = " . $_REQUEST['addsynopsischrono'];
        $sql = $db->query($requete);
    }
}

/*
 *    View
 */
$js = <<< EOF
<script>
jQuery(document).ready(function(){
    jQuery('#addElement').click(function(){
        jQuery('#dialAddElement').dialog('open');
    });
    jQuery('#dialAddElement').dialog({
        autoOpen: false,
        width: 560,
        maxWidth: 560,
        minWidth: 560,
        modal: true,
        title: "Ajouter un &eacute;l&eacute;ment",
        buttons: {
            "OK":function(){
                jQuery('#dialForm').submit();
                jQuery('#dialAddElement').dialog('close');
            },
            "Annuler":function(){
                jQuery('#dialAddElement').dialog('close');
            }
        }
    });
});

</script>
EOF;
llxHeader($js, $langs->trans("Referers"));

$form = new Form($db);
$formproject=new FormProjets($db);

$head = synopsis_project_prepare_head($projet);
dol_fiche_head($head, 'element', $langs->trans("Project"));


print '<table class="border" width="100%" cellpadding=15>';

print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans("Ref") . '</th>
           <td class="ui-widget-content">' . $projet->ref . '</td></tr>';
print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans("Label") . '</th>
           <td class="ui-widget-content">' . $projet->title . '</td></tr>';

print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans("Company") . '</th>
           <td class="ui-widget-content">' . $projet->societe->getNomUrl(1, 'compta') . '</td></tr>';

print '</table>';

print "<br><br><center>";

print "<button id='addElement' class='butAction'>Ajouter un &eacute;l&eacute;ment</button>";

print "</center><br/>";
/*
 * Factures
 */
$listofreferent = array(
    'synopsischrono' => array(
        'title' => "Liste des chronos associ&eacute;es au projet",
        'class' => 'Chrono',
        'test' => $conf->synopsischrono->enabled),
    'propal' => array(
        'title' => "ListProposalsAssociatedProject",
        'class' => 'Propal',
        'test' => $conf->propal->enabled),
    'order' => array(
        'title' => "ListOrdersAssociatedProject",
        'class' => 'Commande',
        'test' => $conf->commande->enabled),
    'invoice' => array(
        'title' => "ListInvoicesAssociatedProject",
        'class' => 'Facture',
        'test' => isset($conf->facture->enabled)),
    'order_supplier' => array(
        'title' => "ListSupplierOrdersAssociatedProject",
        'class' => 'CommandeFournisseur',
        'test' => isset($conf->fournisseur->enabled)),
    'invoice_supplier' => array(
        'title' => "ListSupplierInvoicesAssociatedProject",
        'class' => 'FactureFournisseur',
        'test' => isset($conf->fournisseur->enabled))
//'product'=>array(
//    'title'=>"ProductAssociatedProject",
//    'class'=>'Products',
//    'test'=>true)
);

foreach ($listofreferent as $key => $value) {
    $title = $value['title'];
    $class = $value['class'];
    $qualified = $value['test'];
    if ($qualified) {
        print '<br>';

        load_fiche_titre($langs->trans($title));
        print '<table class="noborder" width="100%">';

        print '<tr class="liste_titre">';
        print '<td width="150">' . $langs->trans("Ref") . '</td>';
        print '<td>' . $langs->trans("Date") . '</td>';
        print '<td align="right">' . $langs->trans("Amount") . '</td>';
        print '</tr>';
        $elementarray = $projet->get_element_list($key);
        if (sizeof($elementarray) > 0 && is_array($elementarray)) {
            $var = true;
            $total = 0;
            for ($i = 0; $i < sizeof($elementarray); $i++) {
                $element = new $class($db);
                $element->fetch($elementarray[$i]);

                $var = !$var;
                print "<tr $bc[$var]>";
                print "<td width=30%>";
                print $element->getNomUrl(1);
                print "</td>\n";
                $date = $element->date;
                if (empty($date))
                    $date = $element->datep;
                print '<td width=30%>' . dol_print_date($date, 'day') . '</td>';
                print '<td width=30% align="right">' . price($element->total_ht) . '</td>';
                print '</tr>';

                $total = $total + $element->total_ht;
            }
            if ($class == "CommandeFournisseur" || $class == 'Commande') {
                print '<tr class="liste_total"><td colspan="2">' . $i . ' ' . $langs->trans("Orders") . '</td>';
            } else if ($class == "Propal") {
                print '<tr class="liste_total"><td colspan="2">' . $i . ' ' . $langs->trans("Proposals") . '</td>';
            } else if ($class == "Facture" || $class == "FactureFournisseur") {
                print '<tr class="liste_total"><td colspan="2">' . $i . ' ' . $langs->trans("Bills") . '</td>';
            } else if ($class == "Chrono") {
                print '<tr class="liste_total"><td colspan="2">' . $i . ' ' . $langs->trans("Chronos") . '</td>';
            }
            print '<td align="right" width="100">' . $langs->trans("TotalHT") . ' : ' . price($total) . '</td>';
            print '</tr>';
        }
        print "</table>";

        /*
         * Barre d'action
         */
        print '<div class="tabsAction">';

        if ($projet->societe->prospect || $projet->societe->client) {
            if ($key == 'synopsischrono' && ($conf->projet->enabled || isset($conf->global->MAIN_MODULE_SYNOPSISPROJET))) {
                print '<a class="butAction" href="' . DOL_URL_ROOT . '/synopsischrono/nouveau.php?projetid=' . $projet->id . '">' . $langs->trans("AddChrono") . '</a>';
            }
            if ($key == 'propal' && isset($conf->propal->enabled) && $user->rights->propale->creer) {
                print '<a class="butAction" href="' . DOL_URL_ROOT . '/comm/addpropal.php?socid=' . $projet->societe->id . '&amp;action=create&amp;projetid=' . $projet->id . '">' . $langs->trans("AddProp") . '</a>';
            }
            if ($key == 'order' && isset($conf->commande->enabled) && $user->rights->commande->creer) {
                print '<a class="butAction" href="' . DOL_URL_ROOT . '/commande/card.php?socid=' . $projet->societe->id . '&amp;action=create&amp;projetid=' . $projet->id . '">' . $langs->trans("AddCustomerOrder") . '</a>';
            }
            if ($key == 'invoice' && isset($conf->facture->enabled) && $user->rights->facture->creer) {
                print '<a class="butAction" href="' . DOL_URL_ROOT . '/compta/facture.php?socid=' . $projet->societe->id . '&amp;action=create&amp;projetid=' . $projet->id . '">' . $langs->trans("AddCustomerInvoice") . '</a>';
            }
        }
        if ($projet->societe->fournisseur) {
            if ($key == 'order_supplier' && isset($conf->fournisseur->enabled) && $user->rights->fournisseur->commande->creer) {
                print '<a class="butAction" href="' . DOL_URL_ROOT . '/fourn/facture/card.php?socid=' . $projet->societe->id . '&amp;action=create&amp;projetid=' . $projet->id . '">' . $langs->trans("AddSupplierInvoice") . '</a>';
            }
            if ($key == 'invoice_supplier' && isset($conf->fournisseur->enabled) && $user->rights->fournisseur->facture->creer) {
                print '<a class="butAction" href="' . DOL_URL_ROOT . '/fourn/commande/card.php?socid=' . $projet->societe->id . '&amp;action=create&amp;projetid=' . $projet->id . '">' . $langs->trans("AddSupplierOrder") . '</a>';
            }
        }
        print '</div>';
    }
}

// Juste pour eviter bug IE qui reorganise mal div precedents si celui-ci absent
print '<div class="tabsAction">';
print '</div>';


print "<div id='dialAddElement'>";

$listofreferent=array(
'propal'=>array(
	'title'=>"ListProposalsAssociatedProject",
	'class'=>'Propal',
	'table'=>'propal',
	'test'=>$conf->propal->enabled && $user->rights->propale->lire),
'order'=>array(
	'title'=>"ListOrdersAssociatedProject",
	'class'=>'Commande',
	'table'=>'commande',
	'test'=>$conf->commande->enabled && $user->rights->commande->lire),
'invoice'=>array(
	'title'=>"ListInvoicesAssociatedProject",
	'class'=>'Facture',
	'table'=>'facture',
	'test'=>$conf->facture->enabled && $user->rights->facture->lire),
'invoice_predefined'=>array(
	'title'=>"ListPredefinedInvoicesAssociatedProject",
	'class'=>'FactureRec',
	'table'=>'facture_rec',
	'test'=>$conf->facture->enabled && $user->rights->facture->lire),
'order_supplier'=>array(
	'title'=>"ListSupplierOrdersAssociatedProject",
	'class'=>'CommandeFournisseur',
	'table'=>'commande_fournisseur',
	'test'=>$conf->fournisseur->enabled && $user->rights->fournisseur->commande->lire),
'invoice_supplier'=>array(
	'title'=>"ListSupplierInvoicesAssociatedProject",
	'class'=>'FactureFournisseur',
	'table'=>'facture_fourn',
	'test'=>$conf->fournisseur->enabled && $user->rights->fournisseur->facture->lire),
'contract'=>array(
	'title'=>"ListContractAssociatedProject",
	'class'=>'Contrat',
	'table'=>'contrat',
	'test'=>$conf->contrat->enabled && $user->rights->contrat->lire),
'intervention'=>array(
	'title'=>"ListFichinterAssociatedProject",
	'class'=>'Fichinter',
	'table'=>'fichinter',
	'disableamount'=>1,
	'test'=>$conf->ficheinter->enabled && $user->rights->ficheinter->lire),
'trip'=>array(
	'title'=>"ListTripAssociatedProject",
	'class'=>'Deplacement',
	'table'=>'deplacement',
	'disableamount'=>1,
	'test'=>$conf->deplacement->enabled && $user->rights->deplacement->lire),
'agenda'=>array(
	'title'=>"ListActionsAssociatedProject",
	'class'=>'ActionComm',
	'table'=>'actioncomm',
	'disableamount'=>1,
	'test'=>$conf->agenda->enabled && $user->rights->agenda->allactions->lire)
);

if ($action=="addelement")
{
	$tablename = GETPOST("tablename");
	$elementselectid = GETPOST("elementselect");
	$result=$project->update_element($tablename, $elementselectid);
	if ($result<0) {
		setEventMessage($mailchimp->error,'errors');
	}
}

foreach ($listofreferent as $key => $value)
{
	$title=$value['title'];
	$classname=$value['class'];
	$tablename=$value['table'];
	$qualified=$value['test'];
	if ($qualified)
	{
		print '<br>';

		load_fiche_titre($langs->trans($title));
		
		$selectList=$formproject->select_element($tablename);
		if ($selectList)
		{
			print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$projectid.'" method="post">';
			print '<input type="hidden" name="tablename" value="'.$tablename.'">';
			print '<input type="hidden" name="action" value="addelement">';
			print '<table><tr><td>'.$langs->trans("SelectElement").'</td>';
			print '<td>'.$selectList.'</td>';
			print '<td><input type="submit" class="button" value="'.$langs->trans("AddElement").'"></td>';
			print '</tr></table>';
			print '</form>';
		}
		print '<table class="noborder" width="100%">';

		print '<tr class="liste_titre">';
		print '<td width="100">'.$langs->trans("Ref").'</td>';
		print '<td width="100" align="center">'.$langs->trans("Date").'</td>';
		print '<td>'.$langs->trans("ThirdParty").'</td>';
		if (empty($value['disableamount'])) print '<td align="right" width="120">'.$langs->trans("AmountHT").'</td>';
		if (empty($value['disableamount'])) print '<td align="right" width="120">'.$langs->trans("AmountTTC").'</td>';
		print '<td align="right" width="200">'.$langs->trans("Status").'</td>';
		print '</tr>';
		$elementarray = $project->get_element_list($key);
		if (count($elementarray)>0 && is_array($elementarray))
		{
			$var=true;
			$total_ht = 0;
			$total_ttc = 0;
			$num=count($elementarray);
			for ($i = 0; $i < $num; $i++)
			{
				$element = new $classname($db);
				$element->fetch($elementarray[$i]);
				$element->fetch_thirdparty();
				//print $classname;

				$var=!$var;
				print "<tr ".$bc[$var].">";

				// Ref
				print '<td align="left">';
				print $element->getNomUrl(1);
				print "</td>\n";

				// Date
				$date=$element->date;
				if (empty($date)) $date=$element->datep;
				if (empty($date)) $date=$element->date_contrat;
				if (empty($date)) $date=$element->datev; //Fiche inter
				print '<td align="center">'.dol_print_date($date,'day').'</td>';

				// Third party
                print '<td align="left">';
                if (is_object($element->client)) print $element->client->getNomUrl(1,'',48);
				print '</td>';

                // Amount
				if (empty($value['disableamount'])) print '<td align="right">'.(isset($element->total_ht)?price($element->total_ht):'&nbsp;').'</td>';

                // Amount
				if (empty($value['disableamount'])) print '<td align="right">'.(isset($element->total_ttc)?price($element->total_ttc):'&nbsp;').'</td>';

				// Status
				print '<td align="right">'.$element->getLibStatut(5).'</td>';

				print '</tr>';

				$total_ht = $total_ht + $element->total_ht;
				$total_ttc = $total_ttc + $element->total_ttc;
			}

			print '<tr class="liste_total"><td colspan="3">'.$langs->trans("Number").': '.$i.'</td>';
			if (empty($value['disableamount'])) print '<td align="right" width="100">'.$langs->trans("TotalHT").' : '.price($total_ht).'</td>';
			if (empty($value['disableamount'])) print '<td align="right" width="100">'.$langs->trans("TotalTTC").' : '.price($total_ttc).'</td>';
			print '<td>&nbsp;</td>';
			print '</tr>';
		}
		print "</table>";


		/*
		 * Barre d'action
		 */
		print '<div class="tabsAction">';

		if ($project->statut > 0)
		{
			if ($project->societe->prospect || $project->societe->client)
			{
				if ($key == 'propal' && ! empty($conf->propal->enabled) && $user->rights->propale->creer)
				{
					print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/propal.php?socid='.$project->societe->id.'&amp;action=create&amp;origin='.$project->element.'&amp;originid='.$project->id.'">'.$langs->trans("AddProp").'</a>';
				}
				if ($key == 'order' && ! empty($conf->commande->enabled) && $user->rights->commande->creer)
				{
					print '<a class="butAction" href="'.DOL_URL_ROOT.'/commande/card.php?socid='.$project->societe->id.'&amp;action=create&amp;origin='.$project->element.'&amp;originid='.$project->id.'">'.$langs->trans("AddCustomerOrder").'</a>';
				}
				if ($key == 'invoice' && ! empty($conf->facture->enabled) && $user->rights->facture->creer)
				{
					print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture/list.php?socid='.$project->societe->id.'&amp;action=create&amp;origin='.$project->element.'&amp;originid='.$project->id.'">'.$langs->trans("AddCustomerInvoice").'</a>';
				}
			}
			if ($project->societe->fournisseur)
			{
				if ($key == 'order_supplier' && ! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->commande->creer)
				{
					print '<a class="butAction" href="'.DOL_URL_ROOT.'/fourn/facture/card.php?socid='.$project->societe->id.'&amp;action=create&amp;origin='.$project->element.'&amp;originid='.$project->id.'">'.$langs->trans("AddSupplierInvoice").'</a>';
				}
				if ($key == 'invoice_supplier' && ! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->facture->creer)
				{
					print '<a class="butAction" href="'.DOL_URL_ROOT.'/fourn/commande/card.php?socid='.$project->societe->id.'&amp;action=create&amp;origin='.$project->element.'&amp;originid='.$project->id.'">'.$langs->trans("AddSupplierOrder").'</a>';
				}
			}
		}

		print '</div>';
	}
}
//if ($projet->societe->fournisseur)
//{
//    foreach(array('commande_fournisseur','facture_fournisseur') as $val)
//    {
//        print "<tr><th class='ui-widget-header ui-state-default'>".$arr[$val];
//        print "<td class='ui-widget-content'> ";
//        print "<select name='add".$val."'>";
//        print "<option value='0'>S&eacute;l&eacute;ctionner-></option>";
//        $requete= "SELECT * FROM ".MAIN_DB_PREFIX."".$val." WHERE fk_soc = ".$projet->societe->id;
//        $sql = $db->query($requete);
//        while($res = $db->fetch_object($sql))
//        {
//            if($val == 'facture_fournisseur')
//            {
//                print "<option value='".$res->rowid."'>".$res->facnumber."</option>";
//            } else {
//                print "<option value='".$res->rowid."'>".$res->ref."</option>";
//            }
//        }
//        print "</select>";
//    }
//}


print "</table>";
print "</form>";
print "</div>";
print '</div>';

$db->close();

llxFooter('$Date: 2008/04/29 06:23:29 $ - $Revision: 1.3 $');
?>
