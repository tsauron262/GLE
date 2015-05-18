<?php
/*
  */
 /**
  *
  * Name : Hressources.php
  * GLE-1.0
  */


require_once("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/synopsisprojet/core/lib/synopsis_project.lib.php");

  $project_id = $_REQUEST['id'];
  $projet = new SynopsisProject($db);
  $projet->id = $project_id;
  $projet->fetch($projet->id);



if ($project_id ."x"== "x")
{
    $project_id = -1;
    accessforbidden();
}
$csspath = DOL_URL_ROOT.'/Synopsis_Common/css/';
$jspath = DOL_URL_ROOT.'/Synopsis_Common/jquery/';
$jqueryuipath = DOL_URL_ROOT.'/Synopsis_Common/jquery/ui/';


$header = "<style>.ActorActive{ background-color: #C1C1FF; -moz-border-radius: 8px; -webkit-border-radius: 8px; }
                   .ActorOver  { background-color: #C1A1AF; -moz-border-radius: 8px; -webkit-border-radius: 8px; }
                   .dayOff { background-color: #00FF00; }
                   .projDay { background-color: #C1C1FF; }
                   .weekEnd { background-color: grey;}
                   .projDayMoreThanFull { background-color: #FF1010; }
                   .vacation { background-color: #FFFF00; }
                   #pagertableJQ_center { display: none;}
</style>";

$header .=  '<script language="javascript">'."\n";
$header .= '    var project_id = '.$project_id.";"."\n";
$header .= '    var user_id = '.$user->id.";"."\n";
$header .= '    var DOL_URL_ROOT = "'.DOL_URL_ROOT.'";'."\n";
$header .= '  $(document).ready(function(){
    $("#tabs").tabs({cache: true,fx: { opacity: "toggle" },
        spinner:"Chargement ...",});
  });
';
$header .= '</script>'."\n";


//Ressources matos only
// Charge la liste du meteriel / salle / etc , permet de voir le calendrier de chaque ressource, et le responsable
// Possiblilité de voir le cot, reserver etc ...

llxHeader($header,"Projet - Ressources","",1);
    $head=synopsis_project_prepare_head($projet);
    dol_fiche_head($head, 'HRessources', $langs->trans("Project"));

//tabs
//1 :> affiche les ressrouces du projet
//2 :> ressource global + resa
//3 :> cout des resources


//tabs
print <<<EOF

<div id="tabs">
     <ul>
         <li><a href="ajax/Hressource_html-response.php?projet_id=$project_id"><span>Ressources du projet</span></a></li>
         <li><a href="ajax/HTressource_html-response.php?projet_id=$project_id"><span>D&eacute;tail des ressources</span></a></li>
         <li><a href="ajax/Plantressource_html-response.php?projet_id=$project_id"><span>Plan de ressources</span></a></li>
         <li><a href="ajax/Hcoutressource_html-response.php?projet_id=$project_id"><span>Co&ucirc;t</span></a></li>
     </ul>
</div>


EOF;


?>
