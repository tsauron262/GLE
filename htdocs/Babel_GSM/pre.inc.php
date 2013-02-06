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
/**
        \file         htdocs/webcal/pre.inc.php
        \ingroup    webcalendar
        \brief      Fichier de gestion du menu gauche du module webcalendar
        \version    $Id: pre.inc.php,v 1.2 2008/01/29 19:03:42 eldy Exp $
*/


function llxHeader($head = "", $title="", $help_url='',$jsFile="")
{
    global $langs;
 global $user, $conf, $langs, $db, $dolibarr_main_authentication;

//    if (! $conf->top_menu)  $conf->top_menu ='eldy_backoffice.php';
//    if (! $conf->left_menu) $conf->left_menu='eldy_backoffice.php';
//print dirname($_SERVER['PHP_SELF']);
    top_htmlhead($head, $title,0,0,$jsFile,array(0 => "/Babel_GSM/css/Babel_GSM.css", 1 => "/theme/auguria/auguria.css.php"));
//function top_htmlhead($head, $title='', $disablejs=0, $disablehead=0, $arrayofjs='', $arrayofcss='')


    print '<body id="mainbody" style="max-width: 100%;min-width: 98%; width: 98%;"><div id="dhtmltooltip"></div>';


//    left_menu($menu->liste, $help_url);
}

function llxHeaderDocuments($head = "", $title="", $help_url='',$jsFile="",$cssFile="")
{
    global $langs;
 global $user, $conf, $langs, $db, $dolibarr_main_authentication;

    if (! $conf->top_menu)  $conf->top_menu ='eldy_backoffice.php';
    if (! $conf->left_menu) $conf->left_menu='eldy_backoffice.php';
//print dirname($_SERVER['PHP_SELF']);
    top_htmlhead($head, $title,0,0,$jsFile,$cssFile);
//function top_htmlhead($head, $title='', $disablejs=0, $disablehead=0, $arrayofjs='', $arrayofcss='')


    print '<body id="mainbody" style="max-width: 98%;min-width: 98%; width: 98%;"><div id="dhtmltooltip"></div>';


//    left_menu($menu->liste, $help_url);
}


?>
