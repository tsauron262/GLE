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
  ** GLE by Synopsis et DRSI
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
require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
require_once(DOL_DOCUMENT_ROOT."/Synopsis_Chrono/Chrono.class.php");
require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/synopsis_project.lib.php");

$langs->load("projectsSyn@projet");
$langs->load("companies");
$langs->load("suppliers");
if ($conf->facture->enabled)  $langs->load("bills");
if ($conf->commande->enabled) $langs->load("orders");
if ($conf->propal->enabled)   $langs->load("propal");

// Securite acces client
$projetid='';
if ($_GET["id"]) { $projetid=$_GET["id"]; }

if ($projetid == '') accessforbidden();

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'synopsisprojet', $projetid);


if($_REQUEST['action']=="addElement")
{
    if ($_REQUEST['addpropal'] > 0 )
    {
        $requete = "UPDATE ".MAIN_DB_PREFIX."propal SET fk_projet = ".$_REQUEST['id']." WHERE rowid = ".$_REQUEST['addpropal'];
        $sql = $db->query($requete);
    }
    if ($_REQUEST['addcommande'] > 0 )
    {
        $requete = "UPDATE ".MAIN_DB_PREFIX."commande SET fk_projet = ".$_REQUEST['id']." WHERE rowid = ".$_REQUEST['addcommande'];
        $sql = $db->query($requete);
    }
    if ($_REQUEST['addfacture'] > 0 )
    {
        $requete = "UPDATE ".MAIN_DB_PREFIX."facture SET fk_projet = ".$_REQUEST['id']." WHERE rowid = ".$_REQUEST['addfacture'];
        $sql = $db->query($requete);
    }
    if ($_REQUEST['addcommande_fournisseur'] > 0 )
    {
        $requete = "UPDATE commande_fournisseur SET fk_projet = ".$_REQUEST['id']." WHERE rowid = ".$_REQUEST['addcommande_fournisseur'];
        $sql = $db->query($requete);
    }
    if ($_REQUEST['addfacture_fournisseur'] > 0 )
    {
        $requete = "UPDATE facture_fournisseur SET fk_projet = ".$_REQUEST['id']." WHERE rowid = ".$_REQUEST['addfacture_fournisseur'];
        $sql = $db->query($requete);
    }
    if ($_REQUEST['addSynopsis_Chrono'] > 0 )
    {
        $requete = "UPDATE ".MAIN_DB_PREFIX."Synopsis_Chrono SET projetId = ".$_REQUEST['id']." WHERE id = ".$_REQUEST['addSynopsis_Chrono'];
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
llxHeader($js,$langs->trans("Referers"));

$projet = new Project($db);
$projet->fetch($_GET["id"]);
$projet->societe->fetch($projet->societe->id);

$head=synopsis_project_prepare_head($projet);
dol_fiche_head($head, 'element', $langs->trans("Project"));


print '<table class="border" width="100%" cellpadding=15>';

print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("Ref").'</th>
           <td class="ui-widget-content">'.$projet->ref.'</td></tr>';
print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("Label").'</th>
           <td class="ui-widget-content">'.$projet->title.'</td></tr>';

print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("Company").'</th>
           <td class="ui-widget-content">'.$projet->societe->getNomUrl(1,'compta').'</td></tr>';

print '</table>';

print "<br><br><center>";

print "<button id='addElement' class='butAction'>Ajouter un &eacute;l&eacute;ment</button>";

print "</center><br/>";
/*
 * Factures
 */
$listofreferent=array(
'Synopsis_Chrono'=>array(
    'title'=>"Liste des chronos associ&eacute;es au projet",
    'class'=>'Chrono',
    'test'=>$conf->synopsischrono->enabled),
'propal'=>array(
    'title'=>"ListProposalsAssociatedProject",
    'class'=>'Propal',
    'test'=>$conf->propal->enabled),
'order'=>array(
    'title'=>"ListOrdersAssociatedProject",
    'class'=>'Commande',
    'test'=>$conf->commande->enabled),
'invoice'=>array(
    'title'=>"ListInvoicesAssociatedProject",
    'class'=>'Facture',
    'test'=>$conf->facture->enabled),
'order_supplier'=>array(
    'title'=>"ListSupplierOrdersAssociatedProject",
    'class'=>'CommandeFournisseur',
    'test'=>$conf->fournisseur->enabled),
'invoice_supplier'=>array(
    'title'=>"ListSupplierInvoicesAssociatedProject",
    'class'=>'FactureFournisseur',
    'test'=>$conf->fournisseur->enabled)
//'product'=>array(
//    'title'=>"ProductAssociatedProject",
//    'class'=>'Products',
//    'test'=>true)
);

foreach ($listofreferent as $key => $value)
{
    $title=$value['title'];
    $class=$value['class'];
    $qualified=$value['test'];
    if ($qualified)
    {
        print '<br>';

        print_titre($langs->trans($title));
        print '<table class="noborder" width="100%">';

        print '<tr class="liste_titre">';
        print '<td width="150">'.$langs->trans("Ref").'</td>';
        print '<td>'.$langs->trans("Date").'</td>';
        print '<td align="right">'.$langs->trans("Amount").'</td>';
        print '</tr>';
        $elementarray = $projet->get_element_list($key);
        if (sizeof($elementarray)>0 && is_array($elementarray))
        {
            $var=true;
            $total = 0;
            for ($i = 0; $i<sizeof($elementarray);$i++)
            {
                $element = new $class($db);
                $element->fetch($elementarray[$i]);

                $var=!$var;
                print "<tr $bc[$var]>";
                print "<td width=30%>";
                print $element->getNomUrl(1);
                print "</td>\n";
                $date=$element->date;
                if (empty($date)) $date=$element->datep;
                print '<td width=30%>'.dol_print_date($date,'day').'</td>';
                print '<td width=30% align="right">'.price($element->total_ht).'</td>';
                print '</tr>';

                $total = $total + $element->total_ht;
            }
            if ($class == "CommandeFournisseur" || $class=='Commande')
            {
                print '<tr class="liste_total"><td colspan="2">'.$i.' '.$langs->trans("Orders").'</td>';
            } else if ($class=="Propal"){
                print '<tr class="liste_total"><td colspan="2">'.$i.' '.$langs->trans("Proposals").'</td>';
            } else if($class=="Facture" || $class=="FactureFournisseur"){
                print '<tr class="liste_total"><td colspan="2">'.$i.' '.$langs->trans("Bills").'</td>';
            } else if($class=="Chrono"){
                print '<tr class="liste_total"><td colspan="2">'.$i.' '.$langs->trans("Chronos").'</td>';
            }
            print '<td align="right" width="100">'.$langs->trans("TotalHT").' : '.price($total).'</td>';
            print '</tr>';
        }
        print "</table>";

        /*
        * Barre d'action
        */
        print '<div class="tabsAction">';

        if ($projet->societe->prospect || $projet->societe->client)
        {
            if ($key == 'Synopsis_Chrono' && ($conf->projet->enabled || isset($conf->global->MAIN_MODULE_SYNOPSISPROJET)))
            {
                print '<a class="butAction" href="'.DOL_URL_ROOT.'/Synopsis_Chrono/nouveau.php?projetid='.$projet->id.'">'.$langs->trans("AddChrono").'</a>';
            }
            if ($key == 'propal' && $conf->propal->enabled && $user->rights->propale->creer)
            {
                print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/addpropal.php?socid='.$projet->societe->id.'&amp;action=create&amp;projetid='.$projet->id.'">'.$langs->trans("AddProp").'</a>';
            }
            if ($key == 'order' && $conf->commande->enabled && $user->rights->commande->creer)
            {
                print '<a class="butAction" href="'.DOL_URL_ROOT.'/commande/fiche.php?socid='.$projet->societe->id.'&amp;action=create&amp;projetid='.$projet->id.'">'.$langs->trans("AddCustomerOrder").'</a>';
            }
            if ($key == 'invoice' && $conf->facture->enabled && $user->rights->facture->creer)
            {
                print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture.php?socid='.$projet->societe->id.'&amp;action=create&amp;projetid='.$projet->id.'">'.$langs->trans("AddCustomerInvoice").'</a>';
            }
        }
        if ($projet->societe->fournisseur)
        {
            if ($key == 'order_supplier' && $conf->fournisseur->enabled && $user->rights->fournisseur->commande->creer)
            {
                print '<a class="butAction" href="'.DOL_URL_ROOT.'/fourn/facture/fiche.php?socid='.$projet->societe->id.'&amp;action=create&amp;projetid='.$projet->id.'">'.$langs->trans("AddSupplierInvoice").'</a>';
            }
            if ($key == 'invoice_supplier' && $conf->fournisseur->enabled && $user->rights->fournisseur->facture->creer)
            {
                print '<a class="butAction" href="'.DOL_URL_ROOT.'/fourn/commande/fiche.php?socid='.$projet->societe->id.'&amp;action=create&amp;projetid='.$projet->id.'">'.$langs->trans("AddSupplierOrder").'</a>';
            }
        }
        print '</div>';
    }
}

// Juste pour eviter bug IE qui reorganise mal div precedents si celui-ci absent
print '<div class="tabsAction">';
print '</div>';


print "<div id='dialAddElement'>";

print "<form id='dialForm' method=POST action='element.php?id=".$_REQUEST['id']."'><input type='hidden' name='action' value='addElement'>";
print "<table cellpadding=15>";
$arr['propal']="Propositions commerciales";
$arr['commande']="Commandes";
$arr['facture']="Factures";
$arr['Synopsis_Chrono']="Chronos";
$arr['commande_fournisseur']="Commande fournisseurs";
$arr['facture_fournisseur']="Factures fournisseurs";
foreach(array('Synopsis_Chrono', 'propal','commande','facture') as $val)
{
    print "<tr><th class='ui-widget-header ui-state-default'>".$arr[$val];
    print "<td class='ui-widget-content'> ";
    print "<select name='add".$val."'>";
    print "<option value='0'>S&eacute;l&eacute;ctionner-></option>";
    if($val == "Synopsis_Chrono")
        $requete= "SELECT *, id as rowid FROM ".MAIN_DB_PREFIX."".$val." WHERE fk_societe = ".$projet->societe->id;
    else
        $requete= "SELECT * FROM ".MAIN_DB_PREFIX."".$val." WHERE fk_soc = ".$projet->societe->id;
    $sql = $db->query($requete);
    while($res = $db->fetch_object($sql))
    {
        if($val == 'facture')
        {
            print "<option value='".$res->rowid."'>".$res->facnumber."</option>";
        } else {
            print "<option value='".$res->rowid."'>".$res->ref."</option>";
        }
    }
    print "</select>";
}
if ($projet->societe->fournisseur)
{
    foreach(array('commande_fournisseur','facture_fournisseur') as $val)
    {
        print "<tr><th class='ui-widget-header ui-state-default'>".$arr[$val];
        print "<td class='ui-widget-content'> ";
        print "<select name='add".$val."'>";
        print "<option value='0'>S&eacute;l&eacute;ctionner-></option>";
        $requete= "SELECT * FROM ".MAIN_DB_PREFIX."".$val." WHERE fk_soc = ".$projet->societe->id;
        $sql = $db->query($requete);
        while($res = $db->fetch_object($sql))
        {
            if($val == 'facture_fournisseur')
            {
                print "<option value='".$res->rowid."'>".$res->facnumber."</option>";
            } else {
                print "<option value='".$res->rowid."'>".$res->ref."</option>";
            }
        }
        print "</select>";
    }
}


print "</table>";
print "</form>";
print "</div>";
print '</div>';

$db->close();

llxFooter('$Date: 2008/04/29 06:23:29 $ - $Revision: 1.3 $');
?>
