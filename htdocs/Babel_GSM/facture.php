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

require_once ("./main.inc.php");

require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/Babel_GSM/gsm.class.php");
if ($user->rights->BabelGSM->BabelGSM_ctrlGest->AfficheFacture !=1)
{
   // var_dump($user->rights->JasperBabel);
    llxHeader();
    print "Ce module ne vous est pas accessible";
    exit(0);
}
llxHeader("", "Dolibarr Factures", '',$jsFile=array(0=>"Babel_GSM/js/babel_gsm.js"));
$gsm = new gsm($db,$user);
$gsm->MainInit();

print '<TABLE  style=\'max-width:"90%"\' class="nobordernopadding">';
//Liste les propal, le montant total HT, le status
$requete = "SELECT ".MAIN_DB_PREFIX."facture.ref," .
        "          ".MAIN_DB_PREFIX."facture.fk_soc, " .
        "          ".MAIN_DB_PREFIX."facture.total," .
        "          ".MAIN_DB_PREFIX."facture.datec," .
        "          ".MAIN_DB_PREFIX."facture.paye," .
        "          ".MAIN_DB_PREFIX."facture.rowid" .
        "     FROM ".MAIN_DB_PREFIX."facture ";
if ($_GET["societe_id"] && $_GET["societe_id"] > 0)
{
    $requete .= " WHERE ".MAIN_DB_PREFIX."facture.fk_soc = ".$_GET['societe_id'];
}
$requete .= " ORDER BY fk_statut," .
        "          fk_soc, " .
        "          datef";
//print $requete;
$resql=$db->query($requete);
$pair= true;
print "<TH>Ref.</TH><TH>Soci&eacute;t&eacute;</TH><TH>Total HT</TH><TH>Statut</TH>";
//print $requete;
if ($resql)
{
//        print "toto";
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
        print "    <TD align='left'><A href='facture_detail.php?facture_id=".$res->rowid."'>".img_object($langs->trans("showBill"),"bill")."&nbsp;" .$res->ref."</A>";
        $soc = new Societe($db);
        $soc->fetch($res->fk_soc);
        $socname = $soc->nom;
        $fac = new Facture($db,$res->fk_soc,$res->rowid);
        $fac->fetch($res->rowid);
        if ($soc->client == 1)
        {
            print "    <TD align='left'><A href='client_detail.php?client_id=".$res->fk_soc."'>".img_object($langs->trans("showCompany"),"company")."&nbsp;" .$socname."</A>";
        } else if ($soc->client == 2 ) {
            print "    <TD align='left'><A href='prospect_detail.php?prospect_id=".$res->fk_soc."'>".img_object($langs->trans("showCompany"),"company")."&nbsp;" .$socname."</A>";
        } else if ($soc->client == 0 && $soc->fournisseur == 1 )
        {
            print "    <TD align='left'><A href='fournisseur_detail.php?fournisseur_id=".$res->fk_soc."'>".img_object($langs->trans("showCompany"),"company")."&nbsp;" .$socname."</A>";
        }
        print "    <TD align='center' nowrap >".price(intval($res->total),0,'',0,0);
        print "    <TD align='center' nowrap>".$fac->getLibStatut(2);
        print "</TR>";
    }
}
print "</TABLE>";
print "</DIV>";

$gsm->jsCorrectSize(true);

?>