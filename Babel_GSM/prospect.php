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
require_once(DOL_DOCUMENT_ROOT."/prospect.class.php");
require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/Babel_GSM/gsm.class.php");
if ($user->rights->BabelGSM->BabelGSM_com->AfficheProspect !=1)
{
   // var_dump($user->rights->JasperBabel);
    llxHeader();
    print "Ce module ne vous est pas accessible";
    exit(0);
}
llxHeader("", "Dolibarr Prospects", '',$jsFile=array(0=>"Babel_GSM/js/babel_gsm.js"));
$gsm = new gsm($db,$user);
$gsm->MainInit();

$langs->load("companies");


print '<TABLE  width="100%" class="nobordernopadding">';
//Liste les propal, le montant total HT, le status
$requete = "SELECT ".MAIN_DB_PREFIX."societe.rowid" .
        "     FROM ".MAIN_DB_PREFIX."societe " .
        "    WHERE ".MAIN_DB_PREFIX."societe.client = 2 " .
        " ORDER BY nom" .
        "          ";
//print $requete;
$resql=$db->query($requete);
$pair= true;
print "<TR><TH>Nom</TH><TH>Ville</TH><TH>Statut</TH>";
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
        $soc = new Prospect($db);
        $soc->fetch($res->rowid);

        print "    <TD align='left'><A href='prospect_detail.php?prospect_id=".$res->rowid."'>".img_object($langs->trans("showCompany"),"company")."&nbsp;" .$soc->nom."</A>";
        $socStatut = $soc->statut_commercial;
        print "    <TD align='center'>".$soc->ville;
        print "    <TD align='left'>".$soc->getLibStatut(4);
        print "</TR>";
    }
}
print "</TABLE>";
$gsm->jsCorrectSize(true);


?>