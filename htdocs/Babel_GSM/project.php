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
require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
require_once(DOL_DOCUMENT_ROOT."/Babel_GSM/gsm.class.php");
if ($user->rights->BabelGSM->BabelGSM_com->AfficheProjet !=1)
{
   // var_dump($user->rights->JasperBabel);
    llxHeader();
    print "Ce module ne vous est pas accessible";
    exit(0);
}
llxHeader("", "Dolibarr Clients", '',$jsFile=array(0=>"Babel_GSM/js/babel_gsm.js"));
$gsm = new gsm($db,$user);
$gsm->MainInit();
$langs->load("companies");

print '<TABLE  width="100%" class="nobordernopadding">';
//Liste les propal, le montant total HT, le status
$requete = "SELECT ".MAIN_DB_PREFIX."projet.rowid" .
        "     FROM ".MAIN_DB_PREFIX."projet " .
        " ORDER BY title" .
        "          ";
//print $requete;
$resql=$db->query($requete);
$pair= true;
print "<TR><TH>Libelle</TH><th>.".$langs->trans("Company")."</TH><th>Nb T&acirc;ches</th>";
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
        $proj = new Project($db);
        $proj->fetch($res->rowid);
        $arr= $proj->getTasksArray();
        $soc = new Societe($db);
        $soc->fetch($proj->socid);

        print "    <td><a href='product_detail.php?product_id=".$proj->id."'> ".img_object("project","project")."&nbsp;".$proj->title." </a></td>";
        $gsm->getGSMSocNameUrl($soc,$langs,1);
        print "    <TD>".count($arr)."</TD>";

        print "</TR>";
    }
}
print "</TABLE>";

$gsm->jsCorrectSize(true);
?>