<?php
/*
  * GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 27 janv. 2011
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : index.php
  * GLE-1.2
  */

header("Location: ".str_replace("index", "liste", $_SERVER['REQUEST_URI']));
exit;
require_once("pre.inc.php");


//todo widget liste des chrono disponible
if (!$user->rights->synopsischrono->read)
  accessforbidden();

$langs->load("chrono");

// Securite acces client
$socid='';
if ($_GET["socid"]) { $socid=$_GET["socid"]; }
if ($user->societe_id > 0)
{
  $action = '';
  $socid = $user->societe_id;
}
$jQueryDashBoardPath = DOL_URL_ROOT.'/Synopsis_Common/jquery/dashboard/';

$js = '
    <script>var DOL_URL_ROOT="'.DOL_URL_ROOT.'";</script>
    <script>var DOL_DOCUMENT_ROOT="'.DOL_DOCUMENT_ROOT.'";</script>
    <script type="text/javascript" src="'.$jQueryDashBoardPath.'jquery.dashboard.js"></script>
    <link rel="stylesheet" type="text/css" href="'.$jQueryDashBoardPath.'dashboard.css" />

    <script type="text/javascript" src="'.$jQueryDashBoardPath.'dashboard.js"></script>
    <link rel="stylesheet" type="text/css" href="'.$jQueryDashBoardPath.'demo.css" />
    <script type="text/javascript">var userid='.$user->id.';</script>
    <script type="text/javascript">var dashtype="46";</script>

';

    llxHeader($js,$langs->trans("Chrono"),1);




    print '<div class="titre">Mon tableau de bord - Chrono</div>';
    print "<br/>";
    print "<br/>";
    print "<div style='padding: 5px 10px; width: 270px;' class='butAction ui-state-default ui-widget-header ui-corner-all'><em><span style='float: left; margin: -1px 3px 0px 0px' class='ui-icon ui-icon-info'></span><a href='#' onClick='addWidget()'>Ajouter des widgets &agrave; votre tableau de bord.</a></em></div>";
    print "<br/>";
    print '<div id="dashboard">';
    print '  You need javascript to use the dashboard.';
    print '</div>';

$db->close();

llxFooter('$Date: 2008/06/19 08:50:59 $ - $Revision: 1.60 $');


?>
