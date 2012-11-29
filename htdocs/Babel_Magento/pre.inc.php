<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 * $Id: pre.inc.php,v 1.6 2005/04/19 10:40:38 rodolphe Exp $
 * $Source: /cvsroot/dolibarr/dolibarr/htdocs/synopsis_demandeinterv/pre.inc.php,v $
 *
 */
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
require("../main.inc.php");

$langs->load("synopsisGene@Synopsis_Tools");

function llxHeader($head = "", $urlp = "")
{
  global $user, $conf, $langs;

  top_menu($head,"E-Commerce","",1);

//  $menu = new Menu();
//
//  $menu->add(DOL_URL_ROOT."/comm/clients.php", $langs->trans("Customers"));
//  $menu->add("index.php", $langs->trans("DIs"));

  left_menu($menu->liste);
}


?>
