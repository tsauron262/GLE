<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
  * GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Create on : 4-1-2009
  *
  * Infos on http://www.synopsis-erp.com
  *
  *//*
 *
 * $Id: pre.inc.php,v 1.28 2008/01/29 19:03:36 eldy Exp $
 */

/**
        \file       htdocs/fourn/pre.inc.php
        \ingroup    fournisseur,facture
        \brief      Fichier gestionnaire du menu fournisseurs
*/

require("../main.inc.php");
require_once DOL_DOCUMENT_ROOT."/fourn/fournisseur.class.php";
require_once DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php";



function llxHeader($head = "", $title="", $addons='') {
  global $user, $conf, $langs;

  $langs->load("suppliers");
  $langs->load("propal");

  top_menu($head, $title);

  $menu = new Menu();
}
?>
