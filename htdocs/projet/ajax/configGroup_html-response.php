<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
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
//    //default value = group value
//  $hrm = new hrm($db);
//  $hrm->listTeam();
print "<html><head>";
print "</head><body>";

$csspath = DOL_URL_ROOT.'/Synopsis_Common/css/';
$jspath = DOL_URL_ROOT.'/Synopsis_Common/jquery/';
$jqueryuipath = DOL_URL_ROOT.'/Synopsis_Common/jquery/ui/';
$imgPath= DOL_URL_ROOT."/Synopsis_Common/images";


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

$header .= ' <script src="js/configGroup.js" type="text/javascript"></script>';

print $header;


            print '<table id="gridListProj1" class="scroll" cellpadding="0" cellspacing="0"></table>';
            print '<div id="gridListProj1Pager" class="scroll" style="text-align:center;"></div>';

print "</body></html>";

?>
