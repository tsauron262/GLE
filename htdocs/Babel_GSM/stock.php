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
require_once(DOL_DOCUMENT_ROOT."/Babel_GSM/gsm.class.php");
if ($user->rights->BabelGSM->BabelGSM_com->AfficheStock !=1)
{
   // var_dump($user->rights->JasperBabel);
    llxHeader();
    print "Ce module ne vous est pas accessible";
    exit(0);
}
llxHeader("", "Dolibarr Stock", '',$jsFile=array(0=>"Babel_GSM/js/babel_gsm.js"));
$gsm = new gsm($db,$user);
$gsm->MainInit();

$langs->load("sendings");

print '<TABLE  width="100%" class="nobordernopadding">';
//Liste les propal, le montant total HT, le status
$requete = "SELECT ".MAIN_DB_PREFIX."entrepot.description," .
        '          ".MAIN_DB_PREFIX."entrepot.rowid,' .
        '          ".MAIN_DB_PREFIX."entrepot.label,' .
        "          ".MAIN_DB_PREFIX."entrepot.lieu" .
        "     FROM ".MAIN_DB_PREFIX."entrepot " .
        " ORDER BY label" .
        "          ";
//print $requete;
$resql=$db->query($requete);
$pair= true;
print "<tr><TH>Ref.</TH><TH>Nom</TH><TH>Lieu</TH>";
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
        print "    <TD align='center'><a href=\"stock_detail.php?stock_id=".$res->rowid."\">".img_object($langs->trans("ShowStock"),"stock").$res->label."</A>";
        print "    <TD align='center'>".$res->description;
        print "    <TD align='center' nowrap >".$res->lieu;
        print "</TR>";
    }
}
print "</TABLE>";
$gsm->jsCorrectSize(true);


?>