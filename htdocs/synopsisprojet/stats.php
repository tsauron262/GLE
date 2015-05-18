<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 27 sept. 2009
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : stats.php
  * GLE-1.1
  */


//          => refaire camembert en diagramme Barre + graph par section + section par volume d'heure par projet + 2 barre 1 besoin et 1 effectif = diagramme de charge
//        => + graph par mois + volume effectif / prevu par section (cuml barr)
//           + graph par section de travail
//        => appel de fond



  require_once('pre.inc.php');
require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/synopsisprojet/core/lib/synopsis_project.lib.php");
  $projId = $_REQUEST['id'];
  $projet = new SynopsisProject($db);
  $projet->id = $projId;
  $projet->fetch($projet->id);
  $project_id = $projId;


//// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid);
// Initialisation de l'objet Societe
//$soc = new Societe($db);
//$soc->fetch($socid);

$ProjId=$_REQUEST['id'];

$jspath = DOL_URL_ROOT."/Synopsis_Common/jquery";
$jsMainpath = DOL_URL_ROOT."/Synopsis_Common/js";
$jqueryuipath = DOL_URL_ROOT."/Synopsis_Common/jquery/ui";
$css = DOL_URL_ROOT."/Synopsis_Common/css";
$imgPath = DOL_URL_ROOT."/Synopsis_Common/images";

$js = "<style type='text/css'>body { position: static; }</style>";
$js .= ' <script src="'.$jsMainpath.'/swfobject.js" type="text/javascript"></script>';


$urlencoded20 = urlencode("action=paiementIO&projId=".$projId);
$urlencoded40 = urlencode("action=fraisProjet&projId=".$projId);
$urlencoded41 = urlencode("action=ressource&projId=".$projId);



//$jqgridJs = <<<EOF
//<script type='text/javascript'>
//EOF;
//$jqgridJs .= 'var gridimgpath="'.$imgPath.'/images/";';
//$jqgridJs .= 'var userId="'.$user->id.'";';
//$jqgridJs .= 'var socId="'.$soc->id.'"; ';
//
$js .= <<<EOF
<script type="text/javascript">
swfobject.embedSWF(
"../Synopsis_Common/open-flash-chart/open-flash-chart.swf", "my_chart20", "100%", "100%",
"9.0.0", "expressInstall.swf",
{"data-file":"ajax/stats_json.php?$urlencoded20"} );

swfobject.embedSWF(
"../Synopsis_Common/open-flash-chart/open-flash-chart.swf", "my_chart40", "100%", "100%",
"9.0.0", "expressInstall.swf",
{"data-file":"ajax/stats_json.php?$urlencoded40"} );

swfobject.embedSWF(
"../Synopsis_Common/open-flash-chart/open-flash-chart.swf", "my_chart41", "100%", "100%","9.0.0", "expressInstall.swf",
{"data-file":"ajax/stats_json.php?$urlencoded41"} );




$(document).ready(function(){
    $("#resize20").resizable();
    $("#resize40").resizable();
    $("#resize41").resizable();
    $("#tabs").tabs({cache: true,fx: { opacity: 'toggle' },
        spinner:"Chargement ...",});
});

function ofc_resize(left, width, top, height)
{
    var tmp = new Array(
    'left:'+left,
    'width:'+ width,
    'top:'+top,
    'height:'+height );

    $("#resize_info").html( tmp.join('<br>') );
}
</script>

EOF;

llxHeader($js,"Stats - Projets","",1);
//print 'ajax/stats_json.php?'.$urlencoded7;

print '<div class="fiche"> ';

print '<div class="tabs">';
    $head=synopsis_project_prepare_head($projet);
    dol_fiche_head($head, 'Stats', $langs->trans("Stats"));


//jquery tabs
print '<div id="tabs">';
print '    <ul>';
print '        <li><a href="#fragment-Gen"><span>G&eacute;n&eacute;ralit&eacute;</span></a></li>';
print '        <li><a href="#fragment-Perf"><span>Tr&eacute;sorerie</span></a></li>';
print '        <li><a href="#fragment-Det"><span>D&eacute;tails</span></a></li>';
print '    </ul>';

print '    <div id="fragment-Det">';
print '<table>';
print '<tbody>';
print '<tr>';
print '<td>';
print '<div id="resize40" style="background-color: #CCCCCC; width:400px; height:300px; padding: -10px">';
print '    <div id="my_chart40"></div>';
print '</div>';
print '</td>';
print '<td>';
print '<div id="resize41" style="background-color: #CCCCCC; width:400px; height:300px; padding: -10px">';
print '    <div id="my_chart41"></div>';
print '</div>';
print '</td>';
print '</tr>';
print '</table>';

print '    </div>';

print '    <div id="fragment-Gen">';

print '<table>';
print '<tbody>';
print '<tr><td>';
print '<div id="resize2" style="background-color: #CCCCCC; width:400px; height:300px; padding: -10px">';
print '    <div id="my_chart2"></div>';
print '</div>';
print '</td><td>';
print '<div id="resize3" style="background-color: #CCCCCC; width:400px; height:300px; padding: -10px">';
print '    <div id="my_chart3"></div>';
print '</div>';
print '</td></tr>';
print '<tr><td>';
print '<div id="resize9" style="background-color: #CCCCCC; width:400px; height:300px; padding: -10px">';
print '    <div id="my_chart9"></div>';
print '</div>';
print '</td>';
print '<td>';
print '<div id="resize10" style="background-color: #CCCCCC; width:400px; height:300px; padding: -10px">';
print '    <div id="my_chart10"></div>';
print '</div>';
print '</td>';
print '</tr>';

print '</tbody></table>';

print '    </div>';

print '    <div id="fragment-Perf">';

print '<table>';
print '<tbody>';
print '<tr><td>';
print '<div id="resize20" style="background-color: #CCCCCC; width:400px; height:300px; padding: -10px">';
print '    <div id="my_chart20"></div>';
print '</div>';
print '</td><td>';
print '<div id="resize21" style="background-color: #CCCCCC; width:400px; height:300px; padding: -10px">';
print '    <div id="my_chart21"></div>';
print '</div>';
print '</td>';
print '</tbody></table>';

print '    </div>';

print '</div>';
print '</div></div>';


llxFooter('$Date: 2008/09/10 09:46:02 $ - $Revision: 1.57.2.1 $');
?>
