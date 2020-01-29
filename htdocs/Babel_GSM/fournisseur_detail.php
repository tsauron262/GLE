<?php
/*
 ** BIMP-ERP by Synopsis et DRSI
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

    include_once ("../master.inc.php");
    include_once ("./pre.inc.php");

    //Limit

//TODO :>
//     :>
//     :> modifier show_contact

require ("./main.inc.php");


require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/prospect.class.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.class.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");

$langs->load("companies");
$langs->load("commercial");
$langs->load("bills");
if ($user->rights->BabelGSM->BabelGSM_fourn->AfficheFourn !=1)
{
   // var_dump($user->rights->JasperBabel);
    llxHeader();
    print "Ce module ne vous est pas accessible";
    exit(0);
}
// Security check
$socid = isset($_GET["fournisseur_id"])?$_GET["fournisseur_id"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid);

// Initialisation de l'objet Societe
$soc = new Societe($db);

//prob  dans menu

require_once(DOL_DOCUMENT_ROOT."/Babel_GSM/gsm.class.php");

llxHeader("", "Dolibarr Prospect", '',$jsFile=array(0=>"Babel_GSM/js/babel_gsm.js"));
$gsm = new gsm($db,$user);
$secondary =  "<SPAN id='mnubut_sec1' class='mnubut_sec' onClick='MenuDisplayCSS_secondary(\"produits\",\"societe_id\",\"".$socid."\")'>Produits</SPAN>\n";

print $gsm->MainMenu($secondary);


/*
*   View
*/


$form = new Form($db);
$countrynotdefined=$langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')';

    /*
     * Fiche societe en mode visu
     */
    $soc = new Fournisseur($db);
    $soc->id = $socid;
    $result=$soc->fetch($socid);
    if ($result < 0)
    {
        dol_print_error($db,$soc->error);
        exit;
    }

//    $head = societe_prepare_head($soc);
//
//    dol_fiche_head($head, 'company', $langs->trans("ThirdParty"));


    // Confirmation de la suppression de la facture

    if ($mesg)
    {
        print '<div class="error ui-state-error">';
        print $mesg;
        print '</div>';
    }

    print '<form name="formsoc" method="post">';
    print '<table class="border" width="100%">';

    //
    print '<tr><td width="20%">'.$langs->trans('Name').'</td>';
    print '<td colspan="3">';
//    print $form->showrefnav($soc,'socid','',1,'rowid','nom');
print $soc->nom;
    print '</td></tr>';

    print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$soc->prefix_comm.'</td></tr>';

        print '<tr><td>';
        print $langs->trans('SupplierCode').'</td><td colspan="3">';
        print $soc->code_fournisseur;
        if ($soc->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
        print '</td></tr>';

    print "<tr><td valign=\"top\">".$langs->trans('Address')."</td><td colspan=\"3\">".nl2br($soc->address)."</td></tr>";

    print '<tr><td width="25%">'.$langs->trans('Zip').'</td><td width="25%" colspan=3>'.$soc->zip."</td>";
    print '<tr><td width="25%">'.$langs->trans('Town').'</td><td width="25%" colspan=3>'.$soc->town."</td></tr>";

    // Country
    print '<tr><td>'.$langs->trans("Country").'</td><td colspan="3">';
    //if ($soc->isInEEC()) print $form->textwithtooltip($soc->pays,$langs->trans("CountryIsInEEC"),1,0);
    print $soc->pays;
    print '</td></tr>';

    print '<tr><td>'.$langs->trans('State').'</td><td colspan="3">'.$soc->departement.'</td>';

    print '<tr><td>'.$langs->trans('Phone').'</td><td colspan="3"><a href="'.preg_replace("/[\Wa-zA-Z.,]*/","",$soc->phone).'">'.$gsm->dol_print_phone($soc->phone).'</a></td>';
    print '</TR><TR><td>'.$langs->trans('Fax').'</td><td colspan="3">'.dol_print_phone($soc->fax).'</td></tr>';

    print '<tr><td>'.$langs->trans('EMail').'</td><td colspan="3">';
    if ($soc->email) { print '<a href="mailto:'.$soc->email.'" target="_blank">'.$soc->email.'</a>'; }
    else print '&nbsp;';
    print '</td>';

    print '</TR><TR><td>'.$langs->trans('Web').'</td><td colspan="3">';
    if ($soc->url) { print '<a href="http://'.$soc->url.'" target="_blank">http://'.dol_trunc($soc->url,32).'</a>'; }
    else print '&nbsp;';
    print '</td></tr>';

    // ProfId1 (SIREN pour France)
    //Prob : verifier ??? ici
    $profid=$langs->transcountry('ProfId1',$soc->pays_code);
    if ($profid!='-')
    {
        print '<tr><td>'.$profid.'</td><td colspan="3">';
        print $soc->siren;
        if ($soc->siren)
        {
            if ($soc->id_prof_check(1,$soc) > 0) print ' &nbsp; '.$soc->id_prof_url(1,$soc);
            else print ' <font class="error">('.$langs->trans("ErrorWrongValue").')</font>';
        }
        print '</td>';
    }
    // ProfId2 (SIRET pour France)
    $profid=$langs->transcountry('ProfId2',$soc->pays_code);
    if ($profid!='-')
    {
        print '</TR><TR><td>'.$profid.'</td><td colspan="3">';
        print $soc->siret;
        if ($soc->siret)
        {
            if ($soc->id_prof_check(2,$soc) > 0) print ' &nbsp; '.$soc->id_prof_url(2,$soc);
            else print ' <font class="error">('.$langs->trans("ErrorWrongValue").')</font>';
        }
        print '</td></tr>';
    }
    else print '<td>&nbsp;</td><td>&nbsp;</td></tr>';

    // ProfId3 (APE pour France)
    $profid=$langs->transcountry('ProfId3',$soc->pays_code);
    if ($profid!='-')
    {
        print '<tr><td>'.$profid.'</td><td colspan=3>';
        print $soc->ape;
        if ($soc->ape)
        {
            if ($soc->id_prof_check(3,$soc) > 0) print ' &nbsp; '.$soc->id_prof_url(3,$soc);
            else print ' <font class="error">('.$langs->trans("ErrorWrongValue").')</font>';
        }
        print '</td>';
    }
    else print '<tr><td>&nbsp;</td><td>&nbsp;</td>';
    // ProfId4 (NU pour France)
    $profid=$langs->transcountry('ProfId4',$soc->pays_code);
    if ($profid!='-')
    {
        print '</TR><TR><td>'.$profid.'</td><td colspan=3>';
        print $soc->idprof4;
        if ($soc->idprof4)
        {
            if ($soc->id_prof_check(4,$soc) > 0) print ' &nbsp; '.$soc->id_prof_url(4,$soc);
            else print ' <font class="error">('.$langs->trans("ErrorWrongValue").')</font>';
        }
        print '</td></tr>';
    }

    // Assujeti TVA
    $html = new Form($db);
    print '<tr><td>';
    print $langs->trans('VATIsUsed');
    print '</td><td colspan=3>';
    print yn($soc->tva_assuj);
    print '</td>';

    print '</TR><TR><td >'.$langs->trans('VATIntra').'</td><td colspan=3>';
    if ($soc->tva_intra)
    {
        $s='';
        $code=substr($soc->tva_intra,0,2);
        $num=substr($soc->tva_intra,2);
        $s.=$soc->tva_intra;
        $s.='<input type="hidden" name="tva_intra_code" size="1" maxlength="2" value="'.$code.'">';
        $s.='<input type="hidden" name="tva_intra_num" size="12" maxlength="18" value="'.$num.'">';
        $s.=' &nbsp; ';

            print $s.'<a href="'.$langs->transcountry("VATIntraCheckURL",$soc->id_pays).'" target="_blank" alt="'.$langs->trans("VATIntraCheckableOnEUSite").'">'.img_picto($langs->trans("VATIntraCheckableOnEUSite"),'help').'</a>';

    }
    else
    {
        print '&nbsp;';
    }
    print '</td>';

    print '</tr>';

    // Capital
    print '<tr><td>'.$langs->trans('Capital').'</td><td colspan="3">';
    if ($soc->capital) print $soc->capital.' '.$langs->trans("Currency".$conf->global->MAIN_MONNAIE);
    else print '&nbsp;';
    print '</td></tr>';

    // Statut juridique
    print '<tr><td>'.$langs->trans('JuridicalStatus').'</td><td colspan="3">'.$soc->forme_juridique.'</td></tr>';

    // Type + Staff
    $arr = $form->typent_array(1);
    $soc->typent= $arr[$soc->typent_code];
    print '<tr><td>'.$langs->trans("Type").'</td><td colspan=3>'.$soc->typent.'</td></tr><TR><td>'.$langs->trans("Staff").'</td><td colspan=3>'.$soc->effectif.'</td></tr>';

    // RIB
    print '<tr><td>';
    print $langs->trans('RIB');
    print '</td>';
    print '<td colspan="3">';
    print $soc->display_rib();
    print '</td></tr>';

    // Maison mere
    print '<tr><td>';
    print $langs->trans('ParentCompany');
    print '<td colspan="3">';
    if ($soc->parent)
    {
        $socm = new Societe($db);
        $socm->fetch($soc->parent);
        //TODO : if prospect => prospect.php sinon client.php
        print '<a href="soc.php?socid='.$socm->id.'">'.img_object($langs->trans("ShowCompany"),'company').' '.$socm->nom.'</a>'.($socm->code_client?"(".$socm->code_client.")":"").' - '.$socm->ville;
    }
    else {
        print $langs->trans("NoParentCompany");
    }
    print '</td></tr>';

    // Commerciaux
    print '<tr><td>';
    print $langs->trans('SalesRepresentatives');
    print '<td colspan="3">';

    $sql = "SELECT count(sc.rowid) as nb";
    $sql.= " FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql.= " WHERE sc.fk_soc =".$soc->id;

    $resql = $db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $obj = $db->fetch_object($resql);
        print $obj->nb?($obj->nb):$langs->trans("NoSalesRepresentativeAffected");
    }
    else {
        dol_print_error($db);
    }
    print '</td></tr>';


    print '</table>';
    print '</form>';
    print "</div>\n";


    /*
     * Liste des contacts
     */
    $gsm->show_contacts($conf,$langs,$db,$soc);



  /*
   * Liste des commandes associees
   */
  $orderstatic = new CommandeFournisseur($db);

  $sql  = "SELECT p.rowid,p.ref, p.date_commande as dc, p.fk_statut";
  $sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as p ";
    $sql.= " WHERE p.fk_soc =".$soc->id;
    $sql.= " ORDER BY p.date_commande DESC";
    $sql.= " ".$db->plimit($MAXLIST);
    $resql=$db->query($sql);
    if ($resql)
    {
        $i = 0 ;
        $num = $db->num_rows($resql);
        if ($num > 0)
        {
            print '<table class="noborder" width="100%">';
            print '<tr class="liste_titre">';
            print '<td colspan="3">';
            print '<table class="noborder" width="100%"><tr><td>'.$langs->trans("LastOrders",($num<$MAXLIST?$num:$MAXLIST)).'</td>';
            print '<td align="right"><a href="commande/liste.php?socid='.$soc->id.'">'.$langs->trans("AllOrders").' ('.$num.')</td></tr></table>';
            print '</td></tr>';
        }
        while ($i < $num && $i <= $MAXLIST)
        {
            $obj = $db->fetch_object($resql);
            $var=!$var;

            print "<tr $bc[$var]>";
            print '<td><a href="commandeFourn_detail.php?commande_id='.$obj->rowid.'">'.img_object($langs->trans("ShowOrder"),"order")." ".$obj->ref.'</a></td>';
            print '<td align="center" width="80">';
            if ($db->jdate($obj->dc))
            {
                print dol_print_date($db->jdate($obj->dc),'day');
            }
            else
            {
                print "-";
            }
            print '</td>';
            print '<td align="right" >'.$orderstatic->LibStatut($obj->fk_statut,5).'</td>';
            print '</tr>';
            $i++;
        }
        $db->free($resql);
        if ($num > 0)
        {
            print "</table><br>";
        }
    }
    else
    {
        dol_print_error($db);
    }


    /*
     * Liste des factures associees
     */
    $MAXLIST=5;

    $langs->load('bills');
    $facturestatic = new FactureFournisseur($db);

    $sql = 'SELECT p.rowid,p.libelle,p.ref,p.fk_statut, p.datef as df, total_ttc as amount, paye';
    $sql.= ' FROM '.MAIN_DB_PREFIX.'facture_fourn as p';
    $sql.= ' WHERE p.fk_soc = '.$soc->id;
    $sql.= ' ORDER BY p.datef DESC';
    $resql=$db->query($sql);
    if ($resql)
    {
        $i = 0 ;
        $num = $db->num_rows($resql);
        if ($num > 0)
        {
            print '<table class="noborder" width="100%">';
            print '<tr class="liste_titre">';
            print '<td colspan="4">';
            print '<table class="noborder" width="100%"><tr><td>'.$langs->trans('LastSuppliersBills',($num<=$MAXLIST?"":$MAXLIST)).'</td><td align="right"><a href="facture/list.php?socid='.$soc->id.'">'.$langs->trans('AllBills').' ('.$num.')</td></tr></table>';
            print '</td></tr>';
        }
        while ($i < min($num,$MAXLIST))
        {
            $obj = $db->fetch_object($resql);
            $var=!$var;
            print '<tr '.$bc[$var].'>';
            print '<td>';
            print '<a href="facture/card.php?facid='.$obj->rowid.'">';
            print img_object($langs->trans('ShowBill'),'bill').' '.$obj->ref.'</a> '.dol_trunc($obj->libelle,14).'</td>';
            print '<td align="center" >'.dol_print_date($db->jdate($obj->df),'day').'</td>';
            print '<td align="right" >'.price($obj->amount).'</td>';
            print '<td align="right" >'.$facturestatic->LibStatut($obj->paye,$obj->fk_statut,5).'</td>';
            print '</tr>';
            $i++;
        }
        $db->free($resql);
        if ($num > 0)
        {
            print '</table><br>';
        }
    }
    else
    {
        dol_print_error($db);
    }




$gsm->jsCorrectSize(true);



 //affiche la liste des contacts

 //affiche les propale en cours

 //affiche les commandes en cours

 //affiche les factures en cours


 //affiche les expeditions en cours

$db->close();


?>
