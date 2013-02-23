<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
*/
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
/*
 */

/**
    \file         htdocs/projet/pre.inc.php
    \ingroup    projet
    \brief      Fichier de gestion du menu gauche du module projet
    \version    $Id: pre.inc.php,v 1.18 2008/03/02 22:20:46 eldy Exp $
*/

require ("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");

$langs->load("synopsisGene@Synopsis_Tools");
$langs->load("companies");
$langs->load("bills");
$langs->load("orders");
$langs->load("commercial");


function llxHeader($head = "", $title="", $help_url='', $noscript = false)
{
  global $langs;

  top_menu($head, $title,"",$noscript);

  $menu = new Menu();


  left_menu($menu->liste, $help_url);
}
?>
