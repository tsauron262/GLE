<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 *
 * $Id: pre.inc.php,v 1.4 2005/10/18 14:03:05 eldy Exp $
 * $Source: /cvsroot/dolibarr/dolibarr/htdocs/domain/pre.inc.php,v $
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
  * Version 1.1
  * Create on : 4-1-2009
  *
  * Infos on http://www.finapro.fr
  *
  */
require_once("../../main.inc.php");


function llxHeader($head = "", $title="", $help_url='', $noscript = false)
{
  global $langs;

  top_menu($head, $title,"",$noscript);


    $menu = new Menu();

    if ($conf->societe->enabled)
    {
        $menu->add(DOL_URL_ROOT."/societe.php", $langs->trans("Companies"),"company");

        if ($conf->rights->societe->creer)
        {
            $menu->add_submenu(DOL_URL_ROOT."/soc.php?&action=create", $langs->trans("NewCompany"));
        }

        $menu->add_submenu(DOL_URL_ROOT."/contact/index.php", $langs->trans("Contacts"));

        $menu->add_submenu("notify/index.php", $langs->trans("Notifications"));
    }

    left_menu($menu->liste);
}
?>
