<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id: pre.inc.php,v 1.6 2005/04/27 22:12:22 eldy Exp $
 * $Source: /cvsroot/dolibarr/dolibarr/htdocs/compta/deplacement/pre.inc.php,v $
 *
 */
/*
  ** BIMP-ERP by Synopsis et DRSI
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
      \file           htdocs/compta/deplacement/pre.inc.php
      \ingroup      deplacement
      \brief          Fichier gestionnaire du menu deplacement
*/

require_once("../../main.inc.php");
require_once("./deplacement.class.php");

$deplacement_type[0] = "voiture";
//$deplacement_type[1] = "train";
//$deplacement_type[2] = "avion";

function llxHeader($head = "") {
  global $user, $conf, $langs;

  top_menu($head);

  $menu = new Menu();

  $menu->add("index.php",$langs->trans("Trips"));
  $menu->add_submenu("card.php?action=create",$langs->trans("NewTrip"));

  left_menu($menu->liste);
}

?>
