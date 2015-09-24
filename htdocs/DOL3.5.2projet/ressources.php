<?php
/*
  
  *
  */
require_once("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/synopsis_project.lib.php");

  $projId = $_REQUEST['id'];
  $project_id=$projId;
  $projet = new Project($db);
  $projet->id = $projId;
  $projet->fetch($projet->id);


//Affiche tabs:

//tabs 0 : imputer des couts au projet
//tabs 1 : graph cout : ligne:1 flux entrant, 2 flux sortant, + area => marge/tréso, + résumé appel de fond etc ... lien avec référents + historique
//tabs 2 : liste des couts du projet
//tabs 3 : rappel des cout horaires + configuration spécifique au projet
//tabs 4 : cout externe falcultatif type matériel, frais généraux
//tabs 5 calendrier


if ($project_id ."x"== "x")
{
    $project_id = -1;
}
$csspath = DOL_URL_ROOT.'/Synopsis_Common/css/';
$jspath = DOL_URL_ROOT.'/Synopsis_Common/jquery/';
$jqueryuipath = DOL_URL_ROOT.'/Synopsis_Common/jquery/ui/';

$header = '';



$header .=  '<script language="javascript">'."\n";
$header .= '    var project_id = '.$project_id.";"."\n";
$header .= '    var user_id = '.$user->id.";"."\n";
$header .= '    var DOL_URL_ROOT = "'.DOL_URL_ROOT.'";'."\n";
$header .= <<<EOF
jQuery(document).ready(function(){
    jQuery("#tabs").tabs({
        cache: false,
        fx: { opacity: "toggle" },
        load: function(e,u){
            if (u.index == 0)
            {
                launch_panel0();
            }
        },
        spinner:"Chargement ...",});
  });
    jQuery.datepicker.setDefaults(jQuery.datepicker.regional['fr']);

EOF;

    $header .= '</script>'."\n";
    $header .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/ui.jqgrid.css" />';
    $header .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/jquery.searchFilter.css" />';
    $header .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/ui.jqgrid.css" />';
    $header .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/jquery.searchFilter.css" />';

    $header .= ' <script src="'.$jspath.'/jqGrid-4.5/js/i18n/grid.locale-fr.js" type="text/javascript"></script>';
    $header .= ' <script src="'.$jspath.'/jqGrid-4.5/js/jquery.jqGrid.js" type="text/javascript"></script>';

    $header .= "<style>#mainbody { min-width: 1200px; } .fiche { min-width:1100px;  }</style>";
    llxHeader($header,"Projet - Ressources","",1);
    $head=synopsis_project_prepare_head($projet);
    dol_fiche_head($head, 'Ressources', $langs->trans("Project"));

//tabs
print <<<EOF

<div id="tabs">
     <ul>
         <li><a href="ajax/xfraisprojet_html-response.php?projId=$project_id"><span>Ressources globales</span></a></li>
     </ul>
</div>


EOF;





?>