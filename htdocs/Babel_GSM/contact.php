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

require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/Babel_GSM/gsm.class.php");
if ($user->rights->BabelGSM->BabelGSM_com->AfficheContact !=1)
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
$requete = "SELECT ".MAIN_DB_PREFIX."socpeople.rowid" .
        "     FROM ".MAIN_DB_PREFIX."socpeople " .
        " ORDER BY name" .
        "          ";
//print $requete;
$resql=$db->query($requete);
$pair= true;
print "<TR><TH>Nom</TH><TH>Soci&eacute;t&eacute;</TH>";
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
        $cont = new Contact($db);
        $cont->fetch($res->rowid);
        $soc = new Societe($db);
        $soc->fetch($cont->socid);

        print "    <td><a href='contact_detail.php?contact_id=".$cont->id."'> ".img_object("contact","contact")."&nbsp;".$cont->getFullName($langs,0)." </a></td>";
        print "    <TD align='left'><A href='client_detail.php?client_id=".$res->rowid."'>".img_object($langs->trans("showCompany"),"company")."&nbsp;" .$soc->nom."</A>";

        print "</TR>";
    }
}
print "</TABLE>";

$gsm->jsCorrectSize(true);

?>