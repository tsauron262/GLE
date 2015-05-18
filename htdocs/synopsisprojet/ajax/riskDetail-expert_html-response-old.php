<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 10 aout 09
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : riskDetail-expert_html-response.php
  * GLE-1.1
  */



//impacte de la tâche % à ces dépendances ( input type percent manuel ) % occurence et % gravite + cout de la tache
//liste des taches du projet en treeview
//on click => affiche détail avec formulaire

$project_id = $_REQUEST['project_id'];
$selectedTask = $_REQUEST['selectedTask'];


require_once('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT.'/synopsisprojet/class/synopsisproject.class.php');

print "<html><head>";
print "</head><body>";

$csspath = DOL_URL_ROOT.'/Synopsis_Common/css/';
$jspath = DOL_URL_ROOT.'/Synopsis_Common/jquery/';
$jqueryuipath = DOL_URL_ROOT.'/Synopsis_Common/jquery/ui/';

$header = '<link rel="stylesheet" type="text/css" href="'.$csspath.'ui.all.css" />'."\n";
$header .= '<script language="javascript" src="'.$jspath.'jquery-1.3.2.js"></script>'."\n";

$header .= '<link rel="stylesheet" href="'.$csspath.'flick/jquery-ui-1.7.2.custom.css" type="text/css" />'."\n";
$header .= '<link type="text/css" rel="stylesheet" href="'.DOL_URL_ROOT.'/Synopsis_Common/css/jquery.treeview.css" />';
$header .= ' <script src="'.$jqueryuipath.'/ui.selectmenu.js" type="text/javascript"></script>';
$header .= " <script > jQuery(document).ready(function(){ jQuery('select').selectmenu(); });  </script>\n";

$header .= '<script language="javascript" src="'.$jspath.'jquery.treeview.js"></script>'."\n";

print $header;

$js="showRisk";
$project = new SynopsisProject($db);
$project->fetch($project_id);
$project->showTreeTask($js);


print '<div id="loadRisk" style="float: left; padding-left: 10pt;">';


print '</div>';
$header = "<script type='text/javascript'>";
$header .= "
    jQuery('#projetTree').treeview({collapsed: true,
                animated: 'medium',
                control:'#sidetreecontrol',
                prerendered: true,
                persist: 'location',});
function showRisk(pId,obj)
{
    jQuery.ajax({
        data: 'taskId='+pId,
        url: 'ajax/riskDetail_html.php',
        success: function(msg){
            jQuery('#loadRisk').replaceWith(msg);
        }
    });
}

";
if ($selectedTask > 0)
{
    $header .= "showRisk(".$selectedTask.")";
}
$header .= "</script>";
print $header;
print "</body></html>";

?>
