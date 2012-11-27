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
    include_once ("./main.inc.php");
    include_once ("./pre.inc.php");

    //Limit
//var_dump($user->rights->BabelGSM);
if ($user->rights->BabelGSM->BabelGSM->Affiche !=1)
{
   // var_dump($user->rights->JasperBabel);
//    llxHeader();
    print "Ce module ne vous est pas accessible";
    exit(0);
}
global $user;
$langs->load("synopsisGene@Synopsis_Tools");
//require ("./main.inc.php");

require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
require_once(DOL_DOCUMENT_ROOT."/Babel_GSM/gsm.class.php");

llxHeader("", "Dolibarr GSM", '',$jsFile=array(0=>"Babel_GSM/js/babel_gsm.js"));
$gsm = new gsm($db,$user);
$gsm->MainInit(true);

print "<br/><br/><div>Bienvenue sur GLE pour GSM ! </div>";



  $urllogo=DOL_URL_ROOT.'/theme/'.$conf->theme.'/Logo-72ppp.png';
        print "<a href='http://www.synopsis-erp.com style='border:0px; position: fixed; bottom:0px;' ><img width=110 height=40 style='position: fixed; bottom: 0px;border:0px;' src='".$urllogo."' /></a>";

$gsm->jsCorrectSize(true);

?>