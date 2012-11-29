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
        if (!$user->admin|| !$user->local_admin || !$conf->global->MAIN_MODULE_BABELGA) accessforbidden();
        llxHeader($header,$langs->trans("Gestion d'actif"),"",1);
        $linkback='<a href="'.DOL_URL_ROOT.'/admin/Babel_GA.php">'.$langs->trans("Module gestion d'actif").'</a>&nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;&nbsp;';
        $linkback.='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';

        print_fiche_titre($langs->trans("Gestion d'actif"),$linkback,'setup');
    }

    $requete = "SELECT distinct(dateValidite) as dv
                  FROM Babel_GA_taux_cessionnaire
                 WHERE cessionnaire_id = ".$socid;
    $type=$_REQUEST['type'];
    switch($type)
    {
        case 'fournisseur':
        {
            $requete = "SELECT distinct(dateValidite) as dv
                          FROM Babel_GA_taux_fournisseur
                         WHERE fournisseur_id = ".$socid;
        }
        break;
        case 'user':
        {
            $requete = "SELECT distinct(dateValidite) as dv
                          FROM Babel_GA_taux_user
                         WHERE user_id = ".$socid;
        }
        break;
        case 'client':
        {
            $requete = "SELECT distinct(dateValidite) as dv
                          FROM Babel_GA_taux_client
                         WHERE client_id = ".$socid;
        }
        break;
        case 'dflt':
        {
            $requete = "SELECT distinct(dateValidite) as dv
                          FROM Babel_GA_taux_default";
        }
        break;
    }
    $sql = $db->query($requete);
    //print $requete;
    print '<form id="form" action="#">';
    print '    <fieldset><div style="max-width: 850px;min-width: 850px;">';
    print '        <label for="valueA">Date:</label>';
    print '        <select name="valueA" id="valueA" class="">';
    $i=0;
    print '<option value="Courant">Courant</option>';
    while ($res=$db->fetch_object($sql))
    {
        $date = date('d/m/Y',strtotime($res->dv));
        print '<option value="'.$date.'">'.$date.'</option>';
        $i++;
    }
    print '         </select>';
    print '    </div></fieldset>';
    print '</form>';
    print '<div id="displayTable">';

    $bga = new BabelGA($db);
    $bga->fetch_taux($socid,$type);
    $bga->drawFinanceTable($date,false);

    print '</div>';
    print "<script>";
    print " var cessionnaireID = ".$socid.";";
    print " var type = '".$type."';";
    print <<<EOF
        jQuery(document).ready(function(){
                jQuery('#valueA').selectToUISlider({ tooltip: true,
                    change: function(e,u){
                        //correct select fake box
                        var valtmp = parseInt(u.value) + 1;
                        //jQuery('#valueA').find('option::nth-child('+ valtmp +')').val()
                        jQuery('#valueA').find('option::nth-child('+ valtmp +')').attr("selected",true);
                        jQuery('#valueA-button')
                            .find('span.ui-selectmenu-status')
                            .text(jQuery('#valueA')
                                .find('option:nth-child('+ valtmp +')')
                                .val()
                             );
                        //Ajax Get
                        jQuery.ajax({
                            url: "ajax/ficheTaux-html_response.php",
                            data:"&type="+type+"&id="+cessionnaireID+"&date="+jQuery('#valueA').find('option:nth-child('+ valtmp +')').val(),
                            datatype: 'html',
                            success: function(msg)
                            {
                                jQuery('#displayTable').replaceWith('<div id="displayTable">'+msg+'</div>');
                                jQuery('#displayTable').find('td').effect("highlight", {}, 3000);
                            }
                        });
                    }
                });

        });
EOF;
    print "</script>";
    print "<style>";
    print <<<EOF

        #form {
            margin:0 30px;
        }
        #form fieldset {
            border:0 none;
            margin-top:1em;
        }
        #form label {
            font-size:1.1em;
            font-weight:normal;
            margin-right:0.5em;
            float: left;
        }
        .screenReaderContext {
            display:none;
        }
        .ui-slider-horizontal {
            float: right;
            min-width: 500px;
            width: 500px;
            max-width: 500px;
        }
        .ui-selectmenu{
            float: left;
        }
EOF;
    print "</style>";
}

?>