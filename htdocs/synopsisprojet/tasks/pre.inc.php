<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
*/
/*
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
  */
/*
 *
 * $Id: pre.inc.php,v 1.7 2008/01/29 19:03:40 eldy Exp $
 */

/**
        \file         htdocs/synopsisprojet/tasks/pre.inc.php
        \ingroup    projet
        \brief      Fichier de gestion du menu gauche du module projet
        \version    $Revision: 1.7 $
*/
require ("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/synopsisprojet/class/synopsisproject.class.php");
require_once(DOL_DOCUMENT_ROOT."/synopsisprojet/class/task.class.php");

$langs->load("synopsisproject@synopsisprojet");
$langs->load("companies");

/*function llxHeader($head = "", $title="", $help_url='')
{
  global $langs;

  top_menu($head, $title,"",1);

  $menu = new Menu();

  $menu->add(DOL_URL_ROOT."/comm/clients.php", $langs->trans("Customers"));

  $menu->add(DOL_URL_ROOT."/synopsisprojet/", $langs->trans("Projects"));
  $menu->add_submenu(DOL_URL_ROOT."/synopsisprojet/liste.php", $langs->trans("List"));

  $menu->add(DOL_URL_ROOT."/synopsisprojet/tasks/", $langs->trans("Tasks"));
  $menu->add_submenu(DOL_URL_ROOT."/synopsisprojet/tasks/mytasks.php", $langs->trans("Mytasks"));

  $menu->add(DOL_URL_ROOT."/synopsisprojet/activity/", $langs->trans("Activity"));
  $menu->add_submenu(DOL_URL_ROOT."/synopsisprojet/activity/myactivity.php", $langs->trans("MyActivity"));

  left_menu($menu->liste, $help_url);
}*/

?>
