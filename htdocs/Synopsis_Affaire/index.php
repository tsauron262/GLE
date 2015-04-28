<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 7 juil. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : index.php
  * GLE-1.1
  */

  //TODO liste des affaires
  //TODO boutton création affaire
  require_once('pre.inc.php');

    // Securite acces client

//    if ($projetid == '') accessforbidden();

    // Security check
    if ($user->societe_id) $socid=$user->societe_id;
    $result = restrictedArea($user, 'affaire', $projetid);


    /*
    *    View
    */

$jQueryDashBoardPath = DOL_URL_ROOT.'/Synopsis_Common/jquery/dashboard/';

$js = '
    <script>var DOL_URL_ROOT="'.DOL_URL_ROOT.'";</script>
    <script>var DOL_DOCUMENT_ROOT="'.DOL_DOCUMENT_ROOT.'";</script>
    <script type="text/javascript" src="'.$jQueryDashBoardPath.'jquery.dashboard.js"></script>
    <link rel="stylesheet" type="text/css" href="'.$jQueryDashBoardPath.'dashboard.css" />

    <script type="text/javascript" src="'.$jQueryDashBoardPath.'dashboard.js"></script>
    <link rel="stylesheet" type="text/css" href="'.$jQueryDashBoardPath.'demo.css" />
    <script type="text/javascript">var userid='.$user->id.';</script>
    <script type="text/javascript">var dashtype="41";</script>

';

/*
* Affichage page
*/



    llxHeader($js,$langs->trans("Affaires"));


    print '<div class="titre">Mon tableau de bord - Affaire</div>';
    print "<br/>";
    print "<br/>";
    print "<div style='padding: 5px 10px; width: 270px;' class='butAction ui-state-default ui-widget-header ui-corner-all'><em><span style='float: left; margin: -1px 3px 0px 0px' class='ui-icon ui-icon-info'></span><a href='#' onClick='addWidget()'>Ajouter des widgets &agrave; votre tableau de bord.</a></em></div>";
    print "<br/>";
    print '<div id="dashboard">';
    print '  You need javascript to use the dashboard.';
    print '</div>';

?>
