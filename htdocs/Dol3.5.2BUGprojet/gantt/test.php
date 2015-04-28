<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 21 juil. 09
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : test.php
  * GLE-1.1
  */


require_once('../../master.inc.php');
?>
<!DOCTYPE html>
<html>
<head>


    <meta http-equiv="Content-Type" content="text/html; charset=utf8" />
    <title>Timepicker Example Page</title>
<?php
$csspath = DOL_URL_ROOT.'/Synopsis_Common/css/';
$jspath = DOL_URL_ROOT.'/Synopsis_Common/jquery/';
$jqueryuipath = DOL_URL_ROOT.'/Synopsis_Common/jquery/ui/';

$header = '<link rel="stylesheet" type="text/css" href="'.$csspath.'ui.all.css" />'."\n";
$header .= '<link rel="stylesheet" type="text/css" href="css/jsgantt.css" />'."\n";
$header .= '<link rel="stylesheet" type="text/css" href="css/GLEgantt.css" />'."\n";
$header .= '<script language="javascript" src="'.$jspath.'jquery-1.3.2.js"></script>'."\n";

$header .= '<link rel="stylesheet" href="'.$csspath.'flick/jquery-ui-1.7.2.custom.css" type="text/css" />'."\n";


$header .= '<script language="javascript" src="'.$jqueryuipath.'ui.core.js"></script>'."\n";
$header .= '<script language="javascript" src="'.$jqueryuipath.'ui.resizable.js"></script>'."\n";
$header .= '<script language="javascript" src="'.$jqueryuipath.'ui.draggable-patched.js"></script>'."\n";
$header .= '<script language="javascript" src="'.$jqueryuipath.'ui.dialog.js"></script>'."\n";
$header .= '<script language="javascript" src="'.$jqueryuipath.'ui.slider.js"></script>'."\n";
$header .= '<script language="javascript" src="'.$jqueryuipath.'ui.tabs.js"></script>'."\n";
$header .= '<script language="javascript" src="'.$jqueryuipath.'ui.accordion.js"></script>'."\n";
$header .= '<script language="javascript" src="'.$jspath.'jquery.validate.js"></script>'."\n";
$header .= '<script language="javascript" src="'.$jspath.'jquery.treeview-manualopen.js"></script>'."\n";
$header .= ' <script src="'.$jqueryuipath.'/ui.selectmenu.js" type="text/javascript"></script>';
$header .= " <script > jQuery(document).ready(function(){ jQuery('select').selectmenu({style: 'dropdown', maxHeight: 300 }); });  </script>\n";

$header .= '<script language="javascript" src="'.$jqueryuipath.'effects.core.js"></script>'."\n";
$header .= '<script language="javascript" src="'.$jqueryuipath.'effects.slide.js"></script>'."\n";
$header .= '<script language="javascript" src="'.$jqueryuipath.'effects.bounce.js"></script>'."\n";
$header .= '<script language="javascript" src="'.$jqueryuipath.'effects.shake.js"></script>'."\n";
$header .= '<script language="javascript" src="'.$jqueryuipath.'effects.highlight.js"></script>'."\n";
$header .= '<script language="javascript" src="'.$jqueryuipath.'effects.scale.js"></script>'."\n";

$header .= '<link rel="stylesheet" href="'.$csspath.'jquery.treeview.css" type="text/css" />'."\n";
$header .= "<style> .horaire td { text-align: center; } .treeview li {  cursor: pointer; } #AddToTable { cursor: pointer; }  .notSelectable { font-style: italic; color: #CCCCCC; cursor: no-drop;  } .delFromTable { cursor: pointer; } #accordion{ overflow: hidden; max-height: 300px min-height: 250px;  } .treeview span { font-weight: 500; padding-left: 3px; } .treeview li { margin-top: 1px; font-size: 90%; padding-top: 3px;  }</style>";

print $header;



//get Tranche Horaire par type => test.js
$requete = "SELECT *
              FROM ".MAIN_DB_PREFIX."Synopsis_projet_trancheHoraire
          ORDER BY day,
                    abs(SUBSTRING(debut,1,2)) ASC ,
                    abs(SUBSTRING(debut,-2)) ASC ";
$sql = $db->query($requete);
print "<script>";
print " var facteurDefault = 100;";
print " var DOL_DOCUMENT_ROOT = '".DOL_DOCUMENT_ROOT."';";
print " var DOL_URL_ROOT = '".DOL_URL_ROOT."';";
print " var trancheHoraire = new Array();";
print "     trancheHoraire[1] = new Array();";
print "     trancheHoraire[6] = new Array();";
print "     trancheHoraire[7] = new Array();";
print "     trancheHoraire[8] = new Array();";
while ($res = $db->fetch_object($sql))
{
    print "\n";
    if ($res->day ."x" == "x")
    {
        print 'jour = 1;';
    } else
    {
        print 'jour = '.$res->day.';';
    }


    $debut=0;
    if (preg_match('/([0-9]{2}):([0-9]{2})/',$res->debut,$arr))
    {
        $debut = intval($arr[1]) * 3600 + intval($arr[2]) * 60;
    }
    $fin=0;
    if (preg_match('/([0-9]{2}):([0-9]{2})/',$res->fin,$arr))
    {
        $fin = intval($arr[1]) * 3600 + intval($arr[2]) * 60;
    }
    print "  trancheHoraire[jour][".$res->id."] = new Array();";
    print "  trancheHoraire[jour][".$res->id."]['debut'] = ".$debut.";";
    print "  trancheHoraire[jour][".$res->id."]['fin'] = ".$fin.";";
    print "  trancheHoraire[jour][".$res->id."]['facteur'] = ".$res->facteur.";";
}
print "</script>";


?>

</head>
<body>

<script type="text/javascript" language="javacript" src='js/test.js'>

</script>

<style type="text/css">
    body{ font: 80% "Trebuchet MS", sans-serif; margin: 50px;}
</style>
TODO :
<ul>
    <li>Integration dialog</li>
</ul>

<table width=700>
    <tbody>
        <tr>
            <td width=300 valign=top>
                <div id='accordion' class="ui-accordion ui-widget ui-helper-reset" >
                    <h3><a href="#">&Eacute;quipe de travail</a></h3>
                    <div style="max-width: 345px; min-width: 300px; max-height: 368px; height: 270px; min-height: 270px;">
                        <div id='tree'>
                          <ul class="treeview">
<?php
require_once(DOL_DOCUMENT_ROOT."/Synopsis_Hrm/hrm.class.php");
$hrm = new Hrm($db);
$html = $hrm->getOrgTree();
print $html;
?>
                            </ul>
                        </div>
                    </div>
                    <h3><a href="#">Personne</a></h3>
                    <div style="max-width: 345px; min-width: 300px; max-height: 368px; height: 270px; min-height: 270px;">
                        <table width=100%>
                            <tbody>
                                <tr>
                                    <td>
                                        <SELECT id="SelUser" style="max-width: 200px;width: 200px;" disabled=false>
                                            <OPTION SELECTED value="-1">Select-></OPTION>
                                            <OPTION value="59">Utilisateur</OPTION>
                                            <OPTION value="39">Demo Demo</OPTION>
                                        </SELECT>
                                    </td>
                                    <td width=30px><button id="SelUserBut">&gt;&gt;</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </td>
            <td valign=top>
                <table  width='100%'>
                    <tbody>
                        <tr>
                            <td valign=top><div id='toChange'>NewForm</div></td>
                        </tr>
                    </tbody>
                </table>
            </td>
            <td width=250 align=right valign=top>
EOF;
print " <div id='AddToTable'><img height=16 width=16 src='".DOL_URL_ROOT."/theme/".$conf->theme."/plus.gif'></div>";
print <<<EOF
            </td>
        </tr>
    </tbody>
</table>

<table width=100% style='border-collapse: collapse;'>
<thead><tr><th class='ui-state-default ui-th-column'>Type</th>
           <th class='ui-state-default ui-th-column'>Nom</th>
           <th class='ui-state-default ui-th-column'>Occupation</th>
           <th class='ui-state-default ui-th-column'>R&ocirc;le</th>
           <th class='ui-state-default ui-th-column'>Action</th>
        </tr></thead>
<tbody id='result'>
</tbody>
</table>


</body>
</html>
