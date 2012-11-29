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
 */

    include_once ("../master.inc.php");
    include_once ("./pre.inc.php");

    //Limit

//TODO :>
//     :>
//     :> modifier show_contact

require ("./main.inc.php");


require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");

if ($user->rights->BabelGSM->BabelGSM_com->AfficheClient !=1)
{
   // var_dump($user->rights->JasperBabel);
    llxHeader();
    print "Ce module ne vous est pas accessible";
    exit(0);
}

$langs->load("companies");
$langs->load("commercial");
$langs->load("interventions");
$langs->load("sendings");
$langs->load("bills");

// Security check
$socid = isset($_GET["client_id"])?$_GET["client_id"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid);

// Initialisation de l'objet Societe
$soc = new Societe($db);

//prob  dans menu

require_once(DOL_DOCUMENT_ROOT."/Babel_GSM/gsm.class.php");

llxHeader("", "Dolibarr Client", '',$jsFile=array(0=>"Babel_GSM/js/babel_gsm.js"));
$gsm = new gsm($db,$user);

    $soc = new Societe($db);
    $soc->id = $socid;
    $result=$soc->fetch($socid);

$langs->load("orders");

$secondary =  "<SPAN id='mnubut_sec0' class='mnubut_sec' onClick='SecMenuDisplayCSS(0)'>Infos Supp&eacute;mentaires </SPAN>\n";
$secondary .= "<DIV name='secMenu' id='secMenu0' class='secMenuDiv'  style='display: none;'>";
$secondary .= " <UL class='SecMenu'>";
$secondary .= "     <LI class='menuSep'>$soc->nom ";
$secondary .=  "    <LI  class='menu' onClick='MenuDisplayCSS_secondary(\"propal\",\"societe_id\",\"".$socid."\")'>".img_object("propal","propal")."Propal</LI>\n";
$secondary .=  "    <LI  class='menu' onClick='MenuDisplayCSS_secondary(\"commande\",\"societe_id\",\"".$socid."\")'>".img_object("order","order")."Commandes</LI>\n";
$secondary .=  "    <LI  class='menu' onClick='MenuDisplayCSS_secondary(\"facture\",\"societe_id\",\"".$socid."\")'>".img_object("bill","bill")."Factures</LI>\n";
$secondary .=  "     <LI  class='menu' onClick='MenuDisplayCSS_secondary(\"expedition\",\"societe_id\",\"".$socid."\")'>".img_object("sending","sending")."Expedition</LI>\n";
$secondary .=  "     <LI  class='menu' onClick='MenuDisplayCSS_secondary(\"intervention\",\"societe_id\",\"".$socid."\")'>".img_object("intervention","intervention")."Intervention</LI>\n";
$secondary .= "</UL></div>";

print $gsm->MainMenu($secondary);


/*
*   View
*/


$form = new Form($db);
$countrynotdefined=$langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')';

    /*
     * Fiche soci�t� en mode visu
     */
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

    if ($soc->client) {
        print '<tr><td>';
        print $langs->trans('CustomerCode').'</td><td colspan="3">';
        print $soc->code_client;
        if ($soc->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
        print '</td></tr>';
    }

    if ($soc->fournisseur) {
        print '<tr><td>';
        print $langs->trans('SupplierCode').'</td><td colspan="3">';
        print $soc->code_fournisseur;
        if ($soc->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
        print '</td></tr>';
    }

    print "<tr><td valign=\"top\">".$langs->trans('Address')."</td><td colspan=\"3\">".nl2br($soc->adresse)."</td></tr>";

    print '<tr><td width="25%">'.$langs->trans('Zip').'</td><td width="25%" colspan=3>'.$soc->cp."</td>";
    print '<tr><td width="25%">'.$langs->trans('Town').'</td><td width="25%" colspan=3>'.$soc->ville."</td></tr>";

    // Country
    print '<tr><td>'.$langs->trans("Country").'</td><td colspan="3">';
    //if ($soc->isInEEC()) print $form->textwithtooltip($soc->pays,$langs->trans("CountryIsInEEC"),1,0);
    print $soc->pays;
    print '</td></tr>';

    print '<tr><td>'.$langs->trans('State').'</td><td colspan="3">'.$soc->departement.'</td>';

    print '<tr><td>'.$langs->trans('Phone').'</td><td colspan="3"><a href="tel:'.preg_replace("/[\Wa-zA-Z.,]*/","",$soc->tel).'">'.$gsm->dol_print_phone($soc->tel).'</a></td>';
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
    if ($soc->capital) print $soc->capital.' '.$langs->trans("Currency".$conf->monnaie);
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
print "</DIV>";


$gsm->jsCorrectSize(true);



 //affiche la liste des contacts

 //affiche les propale en cours

 //affiche les commandes en cours

 //affiche les facture en cours


 //affiche les expedition en cours

$db->close();


?>
