<?php
/*
 ** GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.1
 * Create on : 4-1-2009
 *
 * Infos on http://www.finapro.fr
 *
 */
require_once("pre.inc.php");


//todo widget liste des process disponible

if (!$user->rights->process->lire)
  accessforbidden();

$langs->load("commercial");
$langs->load("orders");

// Securite acces client
$socid='';
if ($_GET["socid"]) { $socid=$_GET["socid"]; }
if ($user->societe_id > 0)
{
  $action = '';
  $socid = $user->societe_id;
}

    llxHeader($js,$langs->trans("Process"),1);

/*
$jQueryDashBoardPath = DOL_URL_ROOT.'/Synopsis_Common/jquery/dashboard/';

$js = '
    <script>var DOL_URL_ROOT="'.DOL_URL_ROOT.'";</script>
    <script>var DOL_DOCUMENT_ROOT="'.DOL_DOCUMENT_ROOT.'";</script>
    <script type="text/javascript" src="'.$jQueryDashBoardPath.'jquery.dashboard.js"></script>
    <link rel="stylesheet" type="text/css" href="'.$jQueryDashBoardPath.'dashboard.css" />

    <script type="text/javascript" src="'.$jQueryDashBoardPath.'dashboard.js"></script>
    <link rel="stylesheet" type="text/css" href="'.$jQueryDashBoardPath.'demo.css" />
    <script type="text/javascript">var userid='.$user->id.';</script>
    <script type="text/javascript">var dashtype="45";</script>

';





    print '<div class="titre">Mon tableau de bord - Process</div>';
    print "<br/>";
    print "<br/>";
    print "<div style='padding: 5px 10px; width: 270px;' class='butAction ui-state-default ui-widget-header ui-corner-all'><em><span style='float: left; margin: -1px 3px 0px 0px' class='ui-icon ui-icon-info'></span><a href='#' onClick='addWidget()'>Ajouter des widgets &agrave; votre tableau de bord.</a></em></div>";
    print "<br/>";
    print '<div id="dashboard">';
    print '  You need javascript to use the dashboard.';
    print '</div>';
*/
$db->close();

llxFooter('$Date: 2008/06/19 08:50:59 $ - $Revision: 1.60 $');


?>