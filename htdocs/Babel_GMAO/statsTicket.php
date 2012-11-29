<?php
require_once('pre.inc.php');

//// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid);
// Initialisation de l'objet Societe
//$soc = new Societe($db);
//$soc->fetch($socid);


$jspath = DOL_URL_ROOT."/Synopsis_Common/jquery";
$jsMainpath = DOL_URL_ROOT."/Synopsis_Common/js";
$jqueryuipath = DOL_URL_ROOT."/Synopsis_Common/jquery/ui";
$css = DOL_URL_ROOT."/Synopsis_Common/css";
$imgPath = DOL_URL_ROOT."/Synopsis_Common/images";

$js = "<style type='text/css'>body { position: static; }</style>";
$js .= ' <script src="'.$jsMainpath.'/swfobject.js" type="text/javascript"></script>';
//$js .= ' <script src="'.$jqueryuipath.'/ui.selectmenu.js" type="text/javascript"></script>';
//$js .= " <script > jQuery(document).ready(function(){ jQuery('select').selectmenu({style: 'dropdown', maxHeight: 300 }); });  </script>\n";
$urlencoded1 = urlencode("action=closeByDay");
$urlencoded2 = urlencode("action=globalByDay");
$urlencoded3 = urlencode("action=vueStatutTicket");
$urlencoded4 = urlencode("action=pendingByDay");

$js .= <<<EOF
<script type="text/javascript">
swfobject.embedSWF(
"../Synopsis_Common/open-flash-chart/open-flash-chart.swf", "my_chart1", "100%", "100%",
"9.0.0", "expressInstall.swf",
{"data-file":"ajax/rt-stats_json.php?$urlencoded1"} );

swfobject.embedSWF(
"../Synopsis_Common/open-flash-chart/open-flash-chart.swf", "my_chart2", "100%", "100%",
"9.0.0", "expressInstall.swf",
{"data-file":"ajax/rt-stats_json.php?$urlencoded2"} );

swfobject.embedSWF(
"../Synopsis_Common/open-flash-chart/open-flash-chart.swf", "my_chart3", "100%", "100%","9.0.0", "expressInstall.swf",
{"data-file":"ajax/rt-stats_json.php?$urlencoded3"} );

swfobject.embedSWF(
"../Synopsis_Common/open-flash-chart/open-flash-chart.swf", "my_chart4", "100%", "100%","9.0.0", "expressInstall.swf",
{"data-file":"ajax/rt-stats_json.php?$urlencoded4"} );



jQuery(document).ready(function(){
    jQuery("#resize1").resizable();
    jQuery("#resize2").resizable();
    jQuery("#resize3").resizable();
    jQuery("#resize4").resizable();
    jQuery("#tabs").tabs({cache: true, fx: { opacity: 'toggle' }
        spinner:"Chargement ...",});
});

function ofc_resize(left, width, top, height)
{
    var tmp = new Array(
    'left:'+left,
    'width:'+ width,
    'top:'+top,
    'height:'+height );

    jQuery("#resize_info").html( tmp.join('<br>') );
}
</script>

EOF;


$rtUrl = $conf->global->GLE_RT_ROOT.'/rt/Search/Chart.html?Order=ASC|ASC|ASC|ASC&Query=Queue+%3D+%27General%27&Rows=50&OrderBy=id|||&Format=%27+++%3Cb%3E%3Ca+href%3D%22__WebPath__%2FTicket%2FDisplay.html%3Fid%3D__id__%22%3E__id__%3C%2Fa%3E%3C%2Fb%3E%2FTITLE%3A%23%27%2C%0D%0A%27%3Cb%3E%3Ca+href%3D%22__WebPath__%2FTicket%2FDisplay.html%3Fid%3D__id__%22%3E__Subject__%3C%2Fa%3E%3C%2Fb%3E%2FTITLE%3ASubject%27%2C%0D%0A%27__Status__%27%2C%0D%0A%27__QueueName__%27%2C%0D%0A%27__OwnerName__%27%2C%0D%0A%27__Priority__%27%2C%0D%0A%27__NEWLINE__%27%2C%0D%0A%27%27%2C%0D%0A%27%3Csmall%3E__Requestors__%3C%2Fsmall%3E%27%2C%0D%0A%27%3Csmall%3E__CreatedRelative__%3C%2Fsmall%3E%27%2C%0D%0A%27%3Csmall%3E__ToldRelative__%3C%2Fsmall%3E%27%2C%0D%0A%27%3Csmall%3E__LastUpdatedRelative__%3C%2Fsmall%3E%27%2C%0D%0A%27%3Csmall%3E__TimeLeft__%3C%2Fsmall%3E%27&ChartStyle=bar&PrimaryGroupBy=Status';
//TODO changer user
$rtUser=$conf->global->GLE_RT_USER;
$rtPass=$conf->global->GLE_RT_PASS;

$tmp = md5(time());
$expire = time()+60*60;
setcookie("loginCookieValue",$tmp,$expire,"/");
$requete = "DELETE FROM Babel_GMAO_login WHERE userid =".$user->id;
$sql = $db->query($requete);
$requete = "INSERT INTO Babel_GMAO_login (userid,cookieVal) VALUES (".$user->id.",'".$tmp."')";
$sql = $db->query($requete);

//$rtUrl .= '?user='.$rtUser."&pass=".$rtPass;

//require_once('../main.inc.php');
$js .= '<style type="text/css">.vmenu{ display: none;}</style>';

llxHeader($js, "Ticket interface",1);

print "<span><a href='index.php'>Retour</a></span><br/>";

print "<div id='tabs'>";

print '<ul>';
    print '<li><a href="#fragment-1"><span>Statistiques</span></a></li>';
    print '<li><a href="#fragment-2"><span>Rapport tickets</span></a></li>';
print '</ul>';


print "<div id='fragment-1'>";
print "<iframe src='".$rtUrl."' id='iframeRT' width='1100' height='1100'>";
print "</iframe>";
print "</div>";
print "<div id='fragment-2'>";

print "<table><tr><td>";
print '<div id="resize1" style="background-color: #CCCCCC; width:400px; height:300px; padding: -10px">';
print '    <div id="my_chart1"></div>';
print '</div>';
print "<td>";
print '<div id="resize2" style="background-color: #CCCCCC; width:400px; height:300px; padding: -10px">';
print '    <div id="my_chart2"></div>';
print '</div>';
print "<tr><td>";
print '<div id="resize3" style="background-color: #CCCCCC; width:400px; height:300px; padding: -10px">';
print '    <div id="my_chart3"></div>';
print '</div>';
print '<td>';
print '<div id="resize4" style="background-color: #CCCCCC; width:400px; height:300px; padding: -10px">';
print '    <div id="my_chart4"></div>';
print '</div>';
print "</table>";





print "</div>";
print "</div>";

?>
