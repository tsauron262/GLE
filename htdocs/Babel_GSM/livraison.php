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
//TODO: secondary menu => deco comme le premier, mais dans une autre couleur + entete avec icone
require ("./main.inc.php");


$langs->load("companies");
$langs->load("sendings");

require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
require_once(DOL_DOCUMENT_ROOT."/livraison/livraison.class.php");
require_once(DOL_DOCUMENT_ROOT."/Babel_GSM/gsm.class.php");
if ($user->rights->BabelGSM->BabelGSM_com->AfficheLivraison !=1)
{
   // var_dump($user->rights->JasperBabel);
    llxHeader();
    print "Ce module ne vous est pas accessible";
    exit(0);
}
llxHeader("", "Dolibarr Clients", '',$jsFile=array(0=>"Babel_GSM/js/babel_gsm.js"));
$gsm = new gsm($db,$user);
$gsm->MainInit();


print '<TABLE  width="100%" class="nobordernopadding">';
//Liste les propal, le montant total HT, le status
$requete = "SELECT ".MAIN_DB_PREFIX."livraison.rowid" .
        "     FROM ".MAIN_DB_PREFIX."livraison " .
        " ORDER BY ref" .
        "          ";
//print $requete;
$resql=$db->query($requete);
$pair= true;
print "<TR><TH>Nom</TH><TH>".$langs->trans('Company')."</TH><TH>date</TH><TH>statut</TH>";
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
        $liv=new Livraison($db);
        $liv->fetch($res->rowid);
        $soc = new Societe($db);
        $soc->fetch($liv->socid);
        print "    <TD><a href='livraison_detail.php?livraison_id=".$liv->id."'> ".img_object("trip","trip")."&nbsp;".$liv->ref."</A></TD>";
        print "    <TD align='left'><A href='client_detail.php?client_id=".$res->rowid."'>".img_object($langs->trans("showCompany"),"company")."&nbsp;" .$soc->nom."</A>";
        print "    <TD align=center>".dol_print_date($liv->date_valid,"day")."</TD>";
        print "<TD>".$liv->getLibStatut(4)."</TD>";
        print "</TR>";
    }
}
print "</TABLE>";
$gsm->jsCorrectSize(true);


?>