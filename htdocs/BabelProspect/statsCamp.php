<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 8-1-2009
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : recapCamp.php
  * GLE-1.1
  */


  //Grid avec la liste et les resultats
  //Subgrid avec les notes d'avancement



  require_once('pre.inc.php');
//  require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");

$campagneId = $_REQUEST['campagneId'];

//$langs->load("companies");
//$langs->load("commercial");
//$langs->load("bills");
//$langs->load("synopsisGene@Synopsis_Tools");

//// Security check
//$socid = isset($_GET["socid"])?$_GET["socid"]:'';
//if ($user->societe_id) $socid=$user->societe_id;
//$result = restrictedArea($user, 'societe', $socid);
//// Initialisation de l'objet Societe
//$soc = new Societe($db);
//$soc->fetch($socid);



$langs->load("companies");
$langs->load("commercial");
$langs->load("bills");
$langs->load("synopsisGene@Synopsis_Tools");

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid);
// Initialisation de l'objet Societe
$soc = new Societe($db);
$soc->fetch($socid);

//var_dump($user->rights->prospectbabe->Prospection);

if (!$user->rights->prospectbabe->Prospection->stats )
{
    accessforbidden();
}

$jspath = DOL_URL_ROOT."/Synopsis_Common/jquery";
$jsMainpath = DOL_URL_ROOT."/Synopsis_Common/js";
$jqueryuipath = DOL_URL_ROOT."/Synopsis_Common/jquery/ui";
$css = DOL_URL_ROOT."/Synopsis_Common/css";
$imgPath = DOL_URL_ROOT."/Synopsis_Common/images";

$js .= "<style type='text/css'>body { position: static; }</style>";
$js .= ' <script src="'.$jsMainpath.'/swfobject.js" type="text/javascript"></script>';


$urlencoded = urlencode("action=actComPie&campagneId=".$campagneId);
$urlencoded1 = urlencode("action=actComBar&campagneId=".$campagneId);
$urlencoded2 = urlencode("action=statutSocPie&campagneId=".$campagneId);
$urlencoded3 = urlencode("action=successPie&campagneId=".$campagneId);
$urlencoded4 = urlencode("action=clotureOkKoLine&campagneId=".$campagneId);
$urlencoded5 = urlencode("action=stCommBarChart&campagneId=".$campagneId);
$urlencoded6 = urlencode("action=clotureOkKoPerCom&campagneId=".$campagneId);
$urlencoded7 = urlencode("action=CAsinceProspect&campagneId=".$campagneId);
$urlencoded8 = urlencode("action=CApositifReturnSoc&campagneId=".$campagneId);
$urlencoded9 = urlencode("action=SocNotePerCom&campagneId=".$campagneId);
$urlencoded10 = urlencode("action=avancSocPerCom&campagneId=".$campagneId);


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
"../Synopsis_Common/open-flash-chart/open-flash-chart.swf", "my_chart", "100%", "100%",
"9.0.0", "expressInstall.swf",
{"data-file":"ajax/stats_json.php?$urlencoded"} );
swfobject.embedSWF(
"../Synopsis_Common/open-flash-chart/open-flash-chart.swf", "my_chart1", "100%", "100%",
"9.0.0", "expressInstall.swf",
{"data-file":"ajax/stats_json.php?$urlencoded1"} );
swfobject.embedSWF(
"../Synopsis_Common/open-flash-chart/open-flash-chart.swf", "my_chart2", "100%", "100%",
"9.0.0", "expressInstall.swf",
{"data-file":"ajax/stats_json.php?$urlencoded2"} );

swfobject.embedSWF(
"../Synopsis_Common/open-flash-chart/open-flash-chart.swf", "my_chart3", "100%", "100%",
"9.0.0", "expressInstall.swf",
{"data-file":"ajax/stats_json.php?$urlencoded3"} );


swfobject.embedSWF(
"../Synopsis_Common/open-flash-chart/open-flash-chart.swf", "my_chart4", "100%", "100%",
"9.0.0", "expressInstall.swf",
{"data-file":"ajax/stats_json.php?$urlencoded4"} );

swfobject.embedSWF(
"../Synopsis_Common/open-flash-chart/open-flash-chart.swf", "my_chart5", "100%", "100%",
"9.0.0", "expressInstall.swf",
{"data-file":"ajax/stats_json.php?$urlencoded5"} );

swfobject.embedSWF(
"../Synopsis_Common/open-flash-chart/open-flash-chart.swf", "my_chart6", "100%", "100%",
"9.0.0", "expressInstall.swf",
{"data-file":"ajax/stats_json.php?$urlencoded6"} );

swfobject.embedSWF(
"../Synopsis_Common/open-flash-chart/open-flash-chart.swf", "my_chart7", "100%", "100%",
"9.0.0", "expressInstall.swf",
{"data-file":"ajax/stats_json.php?$urlencoded7"} );


swfobject.embedSWF(
"../Synopsis_Common/open-flash-chart/open-flash-chart.swf", "my_chart8", "100%", "100%",
"9.0.0", "expressInstall.swf",
{"data-file":"ajax/stats_json.php?$urlencoded8"} );


swfobject.embedSWF(
"../Synopsis_Common/open-flash-chart/open-flash-chart.swf", "my_chart9", "100%", "100%",
"9.0.0", "expressInstall.swf",
{"data-file":"ajax/stats_json.php?$urlencoded9"} );

swfobject.embedSWF(
"../Synopsis_Common/open-flash-chart/open-flash-chart.swf", "my_chart10", "100%", "100%",
"9.0.0", "expressInstall.swf",
{"data-file":"ajax/stats_json.php?$urlencoded10"} );



$(document).ready(function(){
    $("#resize").resizable();
    $("#resize1").resizable();
    $("#resize2").resizable();
    $("#resize3").resizable();
    $("#resize4").resizable();
    $("#resize5").resizable();
    $("#resize6").resizable();
    $("#resize7").resizable();
    $("#resize8").resizable();
    $("#resize9").resizable();
    $("#resize10").resizable();
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

llxHeader($js,"Stats - Campagne",1);
//print 'ajax/stats_json.php?'.$urlencoded7;

print '<div class="fiche"> ';

print '<div class="tabs">';
print '<a class="tabTitle">'.$langs->trans('Campagne Prospection').'</a>';

if ($user->rights->prospectbabe->Prospection->Affiche || $user->rights->prospectbabe->Prospection->permAccess)
{
    print '<a class="tab" href="'.DOL_URL_ROOT.'/BabelProspect/nouvelleProspection.php?action=listCamp&id='.$campagneId.'">'.$langs->trans('Fiche').'</a>';
}


//print '<a class="tab" href="'.DOL_URL_ROOT.'/BabelProspect/nouvelleProspection.php?action=stats&id='.$campagne_id.'">'.$langs->trans('Statistiques').'</a>';
if ($user->rights->prospectbabe->Prospection->permAccess || $user->rights->prospectbabe->Prospection->Affiche)
{
    print '<a class="tab" href="'.DOL_URL_ROOT.'/BabelProspect/affichePropection.php?action=list&campagneId='.$campagneId.'">Prospection</a>';
}

if ($user->rights->prospectbabe->Prospection->recap)
{
    print '<a  class="tab" href="'.DOL_URL_ROOT.'/BabelProspect/recapCamp.php?campagneId='.$campagneId.'">R&eacute;capitulatif</a>';
}
print '<a id="active" class="tab" href="'.DOL_URL_ROOT.'/BabelProspect/statsCamp.php?campagneId='.$campagneId.'">Statistiques</a>';
print '</div>';
print '<div class="tabBar">';


//jquery tabs
print '<div id="tabs">';
print '    <ul>';
print '        <li><a href="#fragment-Gen"><span>G&eacute;n&eacute;ralit&eacute;</span></a></li>';
print '        <li><a href="#fragment-Perf"><span>Performance prospection</span></a></li>';
print '        <li><a href="#fragment-Result"><span>R&eacute;sultats</span></a></li>';
print '        <li><a href="#fragment-Post"><span>Post-campagne</span></a></li>';
print '    </ul>';

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
print '<div id="resize" style="background-color: #CCCCCC; width:400px; height:300px; padding: -10px">';
print '    <div id="my_chart"></div>';
print '</div>';
print '</td><td>';
print '<div id="resize1" style="background-color: #CCCCCC; width:400px; height:300px; padding: -10px">';
print '    <div id="my_chart1"></div>';
print '</div>';
print '</td>';
print '</tbody></table>';

print '    </div>';
print '    <div id="fragment-Post">';

print '<table>';
print '<tbody>';
print '<tr>';
print '<td colspan=1>';
print '<div id="resize7" style="background-color: #CCCCCC; width:400px; height:300px; padding: -10px">';
print '    <div id="my_chart7"></div>';
print '</div>';
print '</td>';
print '</tr>';
print '<tr>';
print '<td colspan=1>';
print '<div id="resize8" style="background-color: #CCCCCC; width:400px; height:300px; padding: -10px">';
print '    <div id="my_chart8"></div>';
print '</div>';
print '</td>';
print '</tr>';

print '</tbody></table>';

print '    </div>';
print '    <div id="fragment-Result">';

print '<table>';
print '<tbody>';
print '<tr>';
print '<td colspan=1>';
print '<div id="resize4" style="background-color: #CCCCCC; width:400px; height:300px; padding: -10px">';
print '    <div id="my_chart4"></div>';
print '</div>';
print '</td>';
print '<td colspan=1>';
print '<div id="resize5" style="background-color: #CCCCCC; width:400px; height:300px; padding: -10px">';
print '    <div id="my_chart5"></div>';
print '</div>';
print '</td></tr>';
print '</tbody>';
print '</table>';
print '<table>';
print '<tbody>';
print '<tr>';
print '<td colspan=1>';
print '<div id="resize6" style="background-color: #CCCCCC; width:400px; height:300px; padding: -10px">';
print '    <div id="my_chart6"></div>';
print '</div>';
print '</td>';
print '</tr>';

print '    </div>';
print '</div>';
print '</div></div>';


?>