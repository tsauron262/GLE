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

require ("./main.inc.php");

require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
require_once(DOL_DOCUMENT_ROOT."/Babel_GSM/gsm.class.php");
if ($user->rights->BabelGSM->BabelGSM_ctrlGest->AffichePaiement !=1)
{
   // var_dump($user->rights->JasperBabel);
    llxHeader();
    print "Ce module ne vous est pas accessible";
    exit(0);
}
llxHeader("", "Dolibarr Paiement", '',$jsFile=array(0=>"Babel_GSM/js/babel_gsm.js"));
$gsm = new gsm($db,$user);
$gsm->MainInit();

print '<TABLE  width="100%" class="nobordernopadding">';
//Liste les propal, le montant total HT, le status
$requete = "SELECT distinct ".MAIN_DB_PREFIX."paiement.rowid," .
        "          ".MAIN_DB_PREFIX."paiement.amount, " .
        "          ".MAIN_DB_PREFIX."societe.nom as snom, " .
        "          CONCAT_WS('/',day(".MAIN_DB_PREFIX."paiement.datep),month(".MAIN_DB_PREFIX."paiement.datep),year(".MAIN_DB_PREFIX."paiement.datep)) as datep," .
        "          ".MAIN_DB_PREFIX."paiement.statut" .
        "     FROM ".MAIN_DB_PREFIX."paiement " .
        " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture on  ".MAIN_DB_PREFIX."paiement.rowid = ".MAIN_DB_PREFIX."paiement_facture.fk_paiement " .
        " LEFT JOIN ".MAIN_DB_PREFIX."facture on   ".MAIN_DB_PREFIX."paiement_facture.fk_facture = ".MAIN_DB_PREFIX."facture.rowid " .
        " LEFT JOIN ".MAIN_DB_PREFIX."societe on   ".MAIN_DB_PREFIX."societe.rowid = ".MAIN_DB_PREFIX."facture.fk_soc " .
        "  ORDER BY ".MAIN_DB_PREFIX."paiement.datep,".MAIN_DB_PREFIX."societe.nom, ".MAIN_DB_PREFIX."paiement.statut" .
        "          ";
//print $requete;
$resql=$db->query($requete);
$pair= true;
print "<TR><TH>&nbsp;</TH><TH>Date</TH><TH>Montant HT</TH><TH>Soci&eacute;t&eacute;</TH><TH>Statut</TH>";
if ($resql)
{
    while ($res=$db->fetch_object($resql))
    {
        if ($pair)
        {
            $pair=false;
            print "<TR class='pair'>";
        } else {
            $pair=true;
            print "<TR class='impair'>";
        }
        print "    <TD align='left'><A href='paiement_detail.php?paiement_id=".$res->rowid."'>".img_object($langs->trans("showPayment"),"payment")."&nbsp;PAI. #".$res->rowid."</A>";
        print "    <TD align='center'>".$res->datep;
        print "    <TD align='center' nowrap >".price(intval($res->amount),0,'',1,0);
        print "    <TD align='left'><A href='client_detail.php?client_id=".$res->rowid."'>".img_object($langs->trans("showCompany"),"company")."&nbsp;" .$res->snom."</A>";
        $txtPaiement = "";
        if ($res->statut == 1)
        {
            $txtPaiement = "Valid&eacute;";
        } else {
            $txtPaiement = "A valider";
        }
        print "    <TD align='center'>".$txtPaiement;
        print "</TR>";
    }
}
print "</TABLE>";
$gsm->jsCorrectSize();

?>