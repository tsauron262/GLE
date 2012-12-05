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
require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/Synopsis_DemandeInterv/demandeInterv.class.php");
require_once(DOL_DOCUMENT_ROOT."/Babel_GSM/gsm.class.php");
if ($user->rights->BabelGSM->BabelGSM_tech->AfficheIntervention !=1)
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
//Liste les propal, le montant total HT, le status Fichinter
$requete = "SELECT ".MAIN_DB_PREFIX."Synopsis_demandeInterv.rowid," .
        "          ".MAIN_DB_PREFIX."Synopsis_demandeInterv.fk_soc," .
        "          concat_ws('/',day(".MAIN_DB_PREFIX."Synopsis_demandeInterv.datei),month(".MAIN_DB_PREFIX."Synopsis_demandeInterv.datei),year(".MAIN_DB_PREFIX."Synopsis_demandeInterv.datei)) as dateFi" .
        "     FROM ".MAIN_DB_PREFIX."Synopsis_demandeInterv ";
if ($_GET["societe_id"] && $_GET["societe_id"] > 0)
{
    $requete .= " WHERE ".MAIN_DB_PREFIX."Synopsis_demandeInterv.fk_soc = ".$_GET['societe_id'];
}
$requete .= " ORDER BY datei," .
        "          fk_statut," .
        "          fk_soc " .
        "          ";
//print $requete;
$resql=$db->query($requete);
$pair= true;
print "<tr><TH>Ref.</TH><TH>Soci&eacute;t&eacute;</TH><TH>Date</TH><TH>Statut</TH>";
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
        $fi = new demandeInterv($db,$res->fk_soc);
        $fi->fetch($res->rowid);
        print "    <TD align='left'><A href='demandeInterv_detail.php?intervention_id=".$res->rowid."'>".img_object($langs->trans("showIntervention"),"intervention")."&nbsp;" .$fi->ref."</A>";
        $soc = new Societe($db);
        $soc->fetch($res->fk_soc);
        $socname = $soc->nom;
        $com = new Commande($db,$res->fk_soc,$res->rowid);
        $com->fetch($res->rowid);
        $fiStatut = $fi->getLibStatut(2);
        if ($soc->client == 1)
        {
            print "    <TD align='left'><A href='client_detail.php?client_id=".$res->fk_soc."'>".img_object($langs->trans("showCompany"),"company")."&nbsp;" .$socname."</A>";
        } else if ($soc->client == 2 ) {
            print "    <TD align='left'><A href='prospect_detail.php?prospect_id=".$res->fk_soc."'>".img_object($langs->trans("showCompany"),"company")."&nbsp;" .$socname."</A>";
        } else if ($soc->client == 0 && $soc->fournisseur == 1 )
        {
            print "    <TD align='left'><A href='fournisseur_detail.php?fournisseur_id=".$res->fk_soc."'>".img_object($langs->trans("showCompany"),"company")."&nbsp;" .$socname."</A>";
        }
        print "    <TD align='center' nowrap >".$res->dateFi;
        print "    <TD align='left' nowrap>".$fiStatut;
        print "</TR>";
    }
}
print "</TABLE>";
$gsm->jsCorrectSize(true);


?>