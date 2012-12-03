<?php
/*
  * GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
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
require ("../main.inc.php");


function llxHeader($head = "", $title="", $help_url='')
{
    global $langs;

    top_menu($head, $title);

    $menu = new Menu();

    #left_menu($menu->liste, $help_url);
}
?>
