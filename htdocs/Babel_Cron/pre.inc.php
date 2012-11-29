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
  *//*
 */

/**
   \file       htdocs/contact/pre.inc.php
   \brief      File to manage left menu for contact area
   \version    $Id: pre.inc.php,v 1.7 2008/03/30 22:25:40 eldy Exp $
*/
require("../main.inc.php");




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
