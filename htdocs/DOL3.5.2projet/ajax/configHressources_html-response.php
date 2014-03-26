<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 24 aout 2009
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : configJour_html-response.php
  * GLE-1.1
  */

  require_once('../../main.inc.php');

//  require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Hrm/hrm.class.php');
    //default value = group value

    //Si value = 0 ou null => group value
  //JqGrid
  //Cout jour/h de chaque personne du HRM
  //Stock dans base projet
  //Cout jour avec historique !!!!!!!
  //vu par groupe et configuration de groupe
    //default value = group value
//  $hrm = new hrm($db);
print "<html><head>";
print "</head><body>";

//  $hrm->listTeam();

//   require_once('Var_Dump.php');
//   Var_Dump::displayInit(array('display_mode' => 'HTML4_Text'), array('mode' => 'normal','offset' => 4));
$csspath = DOL_URL_ROOT.'/Synopsis_Common/css/';
$jspath = DOL_URL_ROOT.'/Synopsis_Common/jquery/';
$jqueryuipath = DOL_URL_ROOT.'/Synopsis_Common/jquery/ui/';
$imgPath= DOL_URL_ROOT."/Synopsis_Common/images";

$header = '<link rel="stylesheet" type="text/css" href="'.$csspath.'ui.all.css" />'."\n";
$header .= '<script language="javascript" src="'.$jspath.'jquery-1.3.2.js"></script>'."\n";

$header .= '<link rel="stylesheet" href="'.$csspath.'flick/jquery-ui-1.7.2.custom.css" type="text/css" />'."\n";

$header .= '<script language="javascript" src="'.$jqueryuipath.'ui.core.js"></script>'."\n";
$header .= '<script language="javascript" src="'.$jqueryuipath.'ui.tabs.js"></script>'."\n";
$header .= '<script language="javascript" src="'.$jqueryuipath.'i18n/jquery-ui-i18n.js"></script>'."\n";

//$header .= '<script language="javascript" src="'.$jspath.'jquery.validate.js"></script>'."\n";
$header .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/ui.jqgrid.css" />';
$header .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/jquery.searchFilter.css" />';
    $header .= ' <script src="'.$jspath.'/jqGrid-3.5/src/i18n/grid.locale-fr.js" type="text/javascript"></script>';
    $header .= ' <script src="'.$jspath.'/jqGrid-3.5/jquery.jqGrid.min.js" type="text/javascript"></script>';

$header .= <<<EOF
    <script type="text/javascript">
    var gridimgpath = "$imgPath/images/";
    var userId = "$user->id";
    </script>
EOF;

$header .= ' <script src="js/configHressources.js" type="text/javascript"></script>';

print $header;


            print '<table id="gridListProj" class="scroll" cellpadding="0" cellspacing="0"></table>';
            print '<div id="gridListProjPager" class="scroll" style="text-align:center;"></div>';

print "</body></html>";

?>
