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
require_once(DOL_DOCUMENT_ROOT."/expedition/class/expedition.class.php");


$langs->load("sendings");
require_once(DOL_DOCUMENT_ROOT."/Babel_GSM/gsm.class.php");

if ($user->rights->BabelGSM->BabelGSM_com->AfficheExpedition !=1)
{
   // var_dump($user->rights->JasperBabel);
    llxHeader();
    print "Ce module ne vous est pas accessible";
    exit(0);
}
llxHeader("", "Dolibarr Expedition", '',$jsFile=array(0=>"Babel_GSM/js/babel_gsm.js"));
$gsm = new gsm($db,$user);
$gsm->MainInit();
print '<TABLE  width="100%" class="nobordernopadding">';
//Liste les propal, le montant total HT, le status
$requete = "SELECT ".MAIN_DB_PREFIX."expedition.ref as expref," .
        '          ".MAIN_DB_PREFIX."commande.ref as  comref,' .
        '          ".MAIN_DB_PREFIX."commande.rowid as  comrowid,' .
        "          ".MAIN_DB_PREFIX."expedition.date_expedition," .
        "          ".MAIN_DB_PREFIX."expedition.rowid as expid," .
        "          ".MAIN_DB_PREFIX."expedition.fk_statut," .
        "          ".MAIN_DB_PREFIX."expedition.fk_soc" .
        "     FROM ".MAIN_DB_PREFIX."expedition, " .
        "          ".MAIN_DB_PREFIX."commande, " .
        "          ".MAIN_DB_PREFIX."co_exp " .
        "    WHERE ".MAIN_DB_PREFIX."expedition.rowid = ".MAIN_DB_PREFIX."co_exp.fk_expedition " .
        "      AND ".MAIN_DB_PREFIX."commande.rowid = ".MAIN_DB_PREFIX."co_exp.fk_commande ";
if ($_GET["societe_id"] && $_GET["societe_id"] > 0)
{
    $requete .= " AND ".MAIN_DB_PREFIX."commande.fk_soc = ".$_GET['societe_id'];
}
$requete .= " ORDER BY date_expedition," .
        "          fk_statut," .
        "          fk_soc " .
        "          ";
//print $requete;
$resql=$db->query($requete);
$pair= true;
print "<tr><TH>Ref. Exp</TH><TH>Soci&eacute;t&eacute;</TH><TH>Date</TH><TH>Ref. Com</TH><TH>Status</TH>";
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
        print "    <TD align='center'><a href=\"expedition_detail.php?expedition_id=".$res->expid."\">".img_object($langs->trans("ShowSending"),"sending").$res->expref."</A>";
        $soc = new Societe($db);
        $soc->fetch($res->fk_soc);
        $socname = $soc->nom;
        $exp = new Expedition($db);
        $exp->fetch($res->expid);
        $comStatut = $exp->getLibStatut(6);

        if ($soc->client == 1)
        {
            print "    <TD align='left'><A href='client_detail.php?client_id=".$res->fk_soc."'>".img_object($langs->trans("showCompany"),"company")."&nbsp;" .$socname."</A>";
        } else if ($soc->client == 2 ) {
            print "    <TD align='left'><A href='prospect_detail.php?prospect_id=".$res->fk_soc."'>".img_object($langs->trans("showCompany"),"company")."&nbsp;" .$socname."</A>";
        } else if ($soc->client == 0 && $soc->fournisseur == 1 )
        {
            print "    <TD align='left'><A href='fournisseur_detail.php?fournisseur_id=".$res->fk_soc."'>".img_object($langs->trans("showCompany"),"company")."&nbsp;" .$socname."</A>";
        }

        print "    <TD align='center' nowrap >".$res->date_expedition;
        print "    <TD align='center'><a href=\"commande_detail.php?commande_id=".$res->comrowid."\">".img_object($langs->trans("ShowOrder"),"order").$res->comref."</A>";
        print "    <TD align='left' nowrap>".$comStatut;
        print "</TR>";
    }
}
print "</TABLE>";
$gsm->jsCorrectSize(true);


?>