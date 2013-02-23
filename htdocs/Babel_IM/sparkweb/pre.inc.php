<?php
/* Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.*//*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Create on : 4-1-2009
  *
  * Infos on http://www.finapro.fr
  *
  *//*
 */

/**
        \file         htdocs/webcal/pre.inc.php
        \ingroup    webcalendar
        \brief      Fichier de gestion du menu gauche du module webcalendar
        \version    $Id: pre.inc.php,v 1.2 2008/01/29 19:03:42 eldy Exp $
*/
require ("./main.inc.php");


function llxHeader($head = "", $title="", $help_url='')
{
    global $langs;

    top_menu($head, $title);

    //$menu = new Menu();
//    left_menu($menu->liste, $help_url);
}
?>
