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
//     :> vcf
//     :> modifier show_contact

require ("./main.inc.php");


require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");

$langs->load("companies");
$langs->load("commercial");
$langs->load("bills");
if ($user->rights->BabelGSM->BabelGSM_com->AfficheContact !=1)
{
   // var_dump($user->rights->JasperBabel);
    llxHeader();
    print "Ce module ne vous est pas accessible";
    exit(0);
}
// Security check
$socid = isset($_GET["client_id"])?$_GET["client_id"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid);
//
//// Initialisation de l'objet Societe
//$soc = new Societe($db);

//prob  dans menu

require_once(DOL_DOCUMENT_ROOT."/Babel_GSM/gsm.class.php");

llxHeader("", "Dolibarr Factures", '',$jsFile=array(0=>"Babel_GSM/js/babel_gsm.js"));
$gsm = new gsm($db,$user);
//$secondary =  "<SPAN id='mnubut_sec1' class='mnubut_sec' onClick='MenuDisplayCSS_secondary(\"propal\",\"societe_id\",\"".$socid."\")'>Propales</SPAN>\n";

print $gsm->MainMenu($secondary);

//Fiche
$objsoc = new Societe($db);
$form = new Form($db);
$contact = new Contact($db);
$contact->fetch($_GET['contact_id']);
    /*
    * Fiche en mode visualisation
    *
    */
    if ($msg) print '<div class="error ui-state-error">'.$msg.'</div>';

    print '<table class="border" width="100%">';

    // Ref
    print '<tr><td width="20%">'.$langs->trans("Ref").'</td><td colspan="3">';
    print $contact->ref;
    print '</td></tr>';

    // Name
    print '<tr><td>'.$langs->trans("Lastname").'</td><td colspan=3>'.$contact->name.'</td><tr>';
    print '<td>'.$langs->trans("Firstname").'</td><td width="25%" colspan=3>'.$contact->firstname.'</td></tr>';

    // Company
    if ($contact->socid > 0)
    {
        $objsoc->fetch($contact->socid);

        print '<tr><td>'.$langs->trans("Company").'</td>';
        $gsm->getGSMSocNameUrl($objsoc,$langs,3);
        print '</td></tr>';
    }
    else
    {
        print '<tr><td>'.$langs->trans("Company").'</td><td colspan="3">';
        print $langs->trans("ContactNotLinkedToCompany");
        print '</td></tr>';
    }

    // Civility
    print '<tr><td width="15%">'.$langs->trans("UserTitle").'</td><td colspan="3">';
    print $contact->getCivilityLabel();
    print '</td></tr>';

    print '<tr><td>'.$langs->trans("PostOrFunction" ).'</td><td colspan="3">'.$contact->poste.'</td>';

    // Address
    print '<tr><td>'.$langs->trans("Address").'</td><td colspan="3">'.nl2br($contact->address).'</td></tr>';

    print '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td colspan="3">'.$contact->cp.'&nbsp;';
    print $contact->ville.'</td></tr>';

    print '<tr><td>'.$langs->trans("Country").'</td><td colspan="3">';
    print $contact->pays;
    print '</td></tr>';
    $phonePro = $gsm->dol_print_phone($contact->phone_pro,$objsoc->pays_code);
    print '<tr><td>'.$langs->trans("PhonePro").'</td><td><a href="tel:'.preg_replace("/[\Wa-zA-Z.,]*/","",$contact->phone_pro).'">'.$phonePro.'</a></td><tr>';
    $phonePerso = $gsm->dol_print_phone($contact->phone_perso,$objsoc->pays_code);

    print '<td>'.$langs->trans("PhonePerso").'</td><td><a href="tel:'.preg_replace("/[\Wa-zA-Z.,]*/","",$contact->phone_perso).'">'.$phonePerso.'</a></td></tr>';
    $PhoneMobile = $gsm->dol_print_phone($contact->phone_mobile,$objsoc->pays_code);
    print '<tr><td>'.$langs->trans("PhoneMobile").'</td><td><a href="tel:'.preg_replace("/[\Wa-zA-Z.,]*/","",$contact->phone_mobile).'"> '.$PhoneMobile.'</a></td>';
    print '<tr><td>'.$langs->trans("Fax").'</td><td>'.dol_print_phone($contact->fax,$objsoc->pays_code).'</td></tr>';

    print '<tr><td>'.$langs->trans("EMail").'</td><td>';
    if ($contact->email && ! ValidEmail($contact->email))
    {
        print '<font class="error">'.$langs->trans("ErrorBadEMail",$contact->email)."</font>";
    }
    else
    {
        print "<a href='mailto:".$contact->email."'>".$contact->email."</a>";
    }
    print '</td>';
    if ($conf->mailing->enabled)
    {
        $langs->load("mails");
        print '<tr><td >'.$langs->trans("NbOfEMailingsReceived").'</td>';
        print '<td>'.$contact->getNbOfEMailings().'</td>';
    }
    else
    {
        print '<td colspan="2">&nbsp;</td>';
    }
    print '</tr>';

    // Jabberid
    print '<tr><td>Jabberid</td><td colspan="3">'.$contact->jabberid.'</td></tr>';

    print '<tr><td>'.$langs->trans("ContactVisibility").'</td><td colspan="3">';
    print $contact->LibPubPriv($contact->priv);
    print '</td></tr>';

    print '<tr><td valign="top">'.$langs->trans("Note").'</td><td colspan="3">';
    print nl2br($contact->note);
    print '</td></tr>';

    $contact->load_ref_elements();
    if ($conf->commande->enabled)
    {
        print '<tr><td>'.$langs->trans("ContactForOrders").'</td><td colspan="3">';
        print $contact->ref_commande?$contact->ref_commande:$langs->trans("NoContactForAnyOrder");
        print '</td></tr>';
    }

    if ($conf->propal->enabled)
    {
        print '<tr><td>'.$langs->trans("ContactForProposals").'</td><td colspan="3">';
        print $contact->ref_propal?$contact->ref_propal:$langs->trans("NoContactForAnyProposal");
        print '</td></tr>';
    }

    if ($conf->contrat->enabled)
    {
        print '<tr><td>'.$langs->trans("ContactForContracts").'</td><td colspan="3">';
        print $contact->ref_contrat?$contact->ref_contrat:$langs->trans("NoContactForAnyContract");
        print '</td></tr>';
    }

    if ($conf->facture->enabled)
    {
        print '<tr><td>'.$langs->trans("ContactForInvoices").'</td><td colspan="3">';
        print $contact->ref_facturation?$contact->ref_facturation:$langs->trans("NoContactForAnyInvoice");
        print '</td></tr>';
    }

    print '<tr><td>'.$langs->trans("DolibarrLogin").'</td><td colspan="3">';
    if ($contact->user_id)
    {
        $dolibarr_user=new User($db);
        $result=$dolibarr_user->fetch($contact->user_id);
        print $dolibarr_user->getLoginUrl(1);
    }
    else print $langs->trans("NoDolibarrAccess");
    print '</td></tr>';

    print "</table>";

    print "</div>";



    print show_actions_todo($conf,$langs,$db,$objsoc,$contact);

    print show_actions_done($conf,$langs,$db,$objsoc,$contact);


//Info Perso

//export vcard
print $langs->trans("ExportCardToFormat").': ';
print '<a href="'.DOL_URL_ROOT.'/contact/vcard.php?id='.$_GET["contact_id"].'">';
print img_vcard($langs->trans("VCard")).' ';
print $langs->trans("VCard");




$gsm->jsCorrectSize(true);

$db->close();


?>
