<?php
require_once("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
if ($conf->facture->enabled) require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
if ($conf->propal->enabled) require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
if ($conf->commande->enabled) require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
if ($conf->contrat->enabled) require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");

if ($conf->global->MAIN_MODULE_BABELGA) require_once(DOL_DOCUMENT_ROOT."/Babel_GA/BabelGA.class.php");

$langs->load("companies");
$langs->load("orders");
$langs->load("bills");
$langs->load("contracts");
if ($conf->fichinter->enabled) $langs->load("interventions");

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe',$socid,'');

$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="nom";


$jqueryuipath = DOL_URL_ROOT.'/Synopsis_Common/jquery/ui/';

$jspath = DOL_URL_ROOT.'/Synopsis_Common/jquery/';
$header .= '<script language="javascript" src="'.$jspath.'jquery.validate.min.js"></script>'."\n";
$header .= '<script language="javascript" src="'.$jqueryuipath.'ui.selectToUISlider.js"></script>'."\n";


$userstatic=new User($db);

$form = new Form($db);

if ($socid."x" != "x")
{
    if ($socid > 0)
    {
        // On recupere les donnees societes par l'objet
        llxHeader($header,$langs->trans('CustomerCard'));
        $objsoc = new Societe($db);
        $objsoc->id=$socid;
        $objsoc->fetch($socid,$to);

        $dac = utf8_decode(strftime("%Y-%m-%d %H:%M", time()));
        if ($errmesg)
        {
            print "<b>$errmesg</b><br>";
        }

        $head = societe_prepare_head($objsoc);

        dol_fiche_head($head, 'cessionnaire', $langs->trans("ThirdParty"));
    } else {
        if (!$user->admin || !$user->local_admin || !$conf->global->MAIN_MODULE_BABELGA) accessforbidden();
        llxHeader($header,$langs->trans("Gestion d'actif"),"",1);
        $linkback='<a href="'.DOL_URL_ROOT.'/admin/Babel_GA.php">'.$langs->trans("Module gestion d'actif").'</a>&nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;&nbsp;';
        $linkback.='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';

        print_fiche_titre($langs->trans("Gestion d'actif"),$linkback,'setup');
    }
    print '<H2> Evolution du taux de marge</h2><br/>';
    $requete = "SELECT *
                  FROM Babel_GA_taux_marge
                 WHERE obj_refid = ".$socid . "
                   AND type_ref = '".$_REQUEST['type']."'
              ORDER BY dateTx Desc";
              //print $requete;
    $sql = $db->query($requete);
    print "<table width=300>";
    print "<th class='ui-widget-header ui-state-default'>Date<th class='ui-widget-header ui-state-default'>Taux";
    while ($res = $db->fetch_object($sql))
    {
        print "<tr><td align='center' class='ui-widget-content'>".date('d/m/Y',strtotime($res->dateTx));
        print "    <td align='center' class='ui-widget-content'>".$res->taux." %";
    }
    print "</table>";
}

?>