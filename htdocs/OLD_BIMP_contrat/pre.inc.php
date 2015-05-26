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
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id: pre.inc.php,v 1.10 2006/12/04 18:03:23 rodolphe Exp $
 * $Source: /cvsroot/dolibarr/dolibarr/htdocs/contrat/pre.inc.php,v $
 */
/*
  * GLE by Synopsis & DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Create on : 4-1-2009
  *
  * Infos on http://www.synopsis-erp.com
  *
  */
/**
    \file       htdocs/contrat/pre.inc.php
    \ingroup    contrat
    \brief      Fichier de gestion du menu gauche de l'espace contrat
    \version    $Revision: 1.10 $
*/

require("../main.inc.php");


function llxHeader($head = "", $title="", $help_url='', $noscript = false)
{
  global $langs;

  top_menu($head, $title,"",$noscript);

  $menu = new Menu();

  $menu->add(DOL_URL_ROOT."/contrat/index.php", $langs->trans("Contracts"));
  $menu->add_submenu(DOL_URL_ROOT."/societe.php", $langs->trans("NewContract"));
  $menu->add_submenu(DOL_URL_ROOT."/contrat/list.php", $langs->trans("List"));
  $menu->add_submenu(DOL_URL_ROOT."/contrat/services.php", $langs->trans("MenuServices"));
  $menu->add_submenu(DOL_URL_ROOT."/contrat/services.php?mode=0", $langs->trans("MenuInactiveServices"), 2 , true);
  $menu->add_submenu(DOL_URL_ROOT."/contrat/services.php?mode=4", $langs->trans("MenuRunningServices"), 2 , true);
  $menu->add_submenu(DOL_URL_ROOT."/contrat/services.php?mode=4&filter=expired", $langs->trans("MenuExpiredServices"), 2 , true);
  $menu->add_submenu(DOL_URL_ROOT."/contrat/services.php?mode=5", $langs->trans("MenuClosedServices"), 2 , true);
  $menu->add_submenu(DOL_URL_ROOT."/contrat/services.php?mode=6", $langs->trans("MenuClosedServicesGros"), 2 , true);

  $menu->add_submenu(DOL_URL_ROOT."/contrat/facturation.php", $langs->trans("AutoBill"));

  left_menu($menu->liste);
}
?>
