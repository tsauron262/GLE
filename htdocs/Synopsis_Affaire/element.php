<?php
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
  *//*
 */

/**
        \file       htdocs/projet/element.php
        \ingroup    projet facture
        \brief      Page des elements par projet
        \version    $Id: element.php,v 1.3 2008/04/29 06:23:29 hregis Exp $
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/Synopsis_Affaire/Affaire.class.php");
require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/expedition/class/expedition.class.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.commande.class.php");
require_once('fct_affaire.php');

$langs->load("projects");
$langs->load("companies");
$langs->load("suppliers");
if ($conf->facture->enabled)  $langs->load("bills");
if ($conf->commande->enabled) $langs->load("orders");
if ($conf->propal->enabled)   $langs->load("propal");

// Securite acces client
$affaireid='';
if ($_REQUEST["id"]) { $affaireid=$_REQUEST["id"]; }

if ($affaireid == '') accessforbidden();

// Security check
if ($user->societe_id) $socid=$user->societe_id;
//$result = restrictedArea($user, 'affaire', $affaireid);


/*
*    View
*/
$js = <<<EOF
<script>
jQuery(document).ready(function(){
    jQuery('#accordion').accordion({ animated: 'bounceslide', active: false, collapsible: true , autoHeight: false, });
});

</script>
EOF;
llxHeader($js,$langs->trans("Referers"),"",1);

$affaire = new Affaire($db);
$affaire->fetch($affaireid);

print_cartoucheAffaire($affaire,'element',"");

print '</div>';


/*
 * Factures
 */
$listofreferent=array(
'propale'=>array(
    'title'=>"ListProposalsAssociatedAffaire",
    'class'=>'Propal',
    'test'=>$conf->propal->enabled),
'commande'=>array(
    'title'=>"ListOrdersAssociatedAffaire",
    'class'=>'Commande',
    'test'=>$conf->commande->enabled),
'facture'=>array(
    'title'=>"ListInvoicesAssociatedAffaire",
    'class'=>'Facture',
    'test'=>$conf->facture->enabled),
'facture fournisseur'=>array(
    'title'=>"ListSupplierOrdersAssociatedAffaire",
    'class'=>'CommandeFournisseur',
    'test'=>$conf->fournisseur->enabled),
'commande fournisseur'=>array(
    'title'=>"ListSupplierInvoicesAssociatedAffaire",
    'class'=>'FactureFournisseur',
    'test'=>$conf->fournisseur->enabled),
'domaine'=>array(
    'title'=>"DomainAssociatedAffaire",
    'class'=>'Domaine',
    'test'=>$conf->synopsisaffaire->enabled),
'expedition'=>array(
    'title'=>"ExpeditionAssociatedAffaire",
    'class'=>'expedition',
    'test'=>$conf->expedition->enabled),
'livraison'=>array(
    'title'=>"LivraisonAssociatedAffaire",
    'class'=>'livraison',
    'test'=>$conf->expedition->enabled)
);
        print '<br>';
print '<div id="accordion">';
foreach ($listofreferent as $key => $value)
{
    $title=$value['title'];
    $class=$value['class'];
    $qualified=$value['test'];
    if ($qualified)
    {
        $elementarray = $affaire->get_element_list($key);
        if (sizeof($elementarray)>0 && is_array($elementarray))
        {
            print '<h3><a href="#">'.$langs->trans($title.'short')." (". sizeof($elementarray) . ')</a></h3>';
            print "<div>";
            load_fiche_titre($langs->trans($title));
            print '<table class="noborder" width="100%">';

            print '<tr class="liste_titre">';
            print '<td width="150">'.$langs->trans("Ref").'</td>';
            print '<td>'.$langs->trans("Date").'</td>';
            print '<td align="right">'.$langs->trans("Amount").'</td>';
            print '</tr>';
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
                if ($class== "CommandeFournisseur" || $class=='Commande' || $class=="Propal" ||
                    $class=="Facture" || $class=="FactureFournisseur" )
                    {
                        print '<td width=30% align="right">'.price($element->total_ht).'</td>';
                    } else {
                        print '<td align="right">&nbsp;</td>';
                    }
                print '</tr>';

                $total = $total + $element->total_ht;
            }
            $nodisplay=false;
            if ($class == "CommandeFournisseur" || $class=='Commande')
            {
                print '<tr class="liste_total"><td colspan="2">'.$i.' '.$langs->trans("Orders").'</td>';
            } else if ($class=="Propal"){
                print '<tr class="liste_total"><td colspan="2">'.$i.' '.$langs->trans("Proposals").'</td>';
            } else if($class=="Facture" || $class == "FactureFournisseur"){
                print '<tr class="liste_total"><td colspan="2">'.$i.' '.$langs->trans("Bills").'</td>';
            } else {
                $nodisplay=true;
            }
            if (!$nodisplay)
            {
                print '<td align="right" width="100">'.$langs->trans("TotalHT").' : '.price($total).'</td>';
            } else {
                print '<td>&nbsp;</td>';
            }
            print '</tr>';
            print "</table>";
            print "</div>";
        }

    }
}
print "</div>";
print "</div>";

    /*
    * Barre d'action
    */
    print '<div class="tabsAction">';

    if ($affaire->societe->prospect || $affaire->societe->client)
    {
        if ($key == 'propal' && $conf->propal->enabled && $user->rights->propale->creer)
        {
            print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/addpropal.php?socid='.$affaire->societe->id.'&amp;action=create&amp;projetid='.$affaire->id.'">'.$langs->trans("AddProp").'</a>';
        }
        if ($key == 'order' && $conf->commande->enabled && $user->rights->commande->creer)
        {
            print '<a class="butAction" href="'.DOL_URL_ROOT.'/commande/card.php?socid='.$affaire->societe->id.'&amp;action=create&amp;projetid='.$affaire->id.'">'.$langs->trans("AddCustomerOrder").'</a>';
        }
        if ($key == 'invoice' && $conf->facture->enabled && $user->rights->facture->creer)
        {
            print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture.php?socid='.$affaire->societe->id.'&amp;action=create&amp;projetid='.$affaire->id.'">'.$langs->trans("AddCustomerInvoice").'</a>';
        }
    }
    if ($affaire->societe->fournisseur)
    {
        if ($key == 'order_supplier' && $conf->fournisseur->enabled && $user->rights->fournisseur->commande->creer)
        {
            print '<a class="butAction" href="'.DOL_URL_ROOT.'/fourn/facture/card.php?socid='.$affaire->societe->id.'&amp;action=create&amp;projetid='.$affaire->id.'">'.$langs->trans("AddSupplierInvoice").'</a>';
        }
        if ($key == 'invoice_supplier' && $conf->fournisseur->enabled && $user->rights->fournisseur->facture->creer)
        {
            print '<a class="butAction" href="'.DOL_URL_ROOT.'/fourn/commande/card.php?socid='.$affaire->societe->id.'&amp;action=create&amp;projetid='.$affaire->id.'">'.$langs->trans("AddSupplierOrder").'</a>';
        }
    }
    print '</div>';
// Juste pour eviter bug IE qui reorganise mal div precedents si celui-ci absent

$db->close();

llxFooter('$Date: 2008/04/29 06:23:29 $ - $Revision: 1.3 $');
?>
