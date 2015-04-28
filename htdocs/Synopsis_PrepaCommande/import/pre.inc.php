<?php
/*
  ** GLE by Synopsis et DRSI
  *
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
        \file       htdocs/comm/pre.inc.php
        \ingroup    commercial
        \brief      Fichier de gestion du menu gauche de l'espace commercial
        \version    $Revision: 1.32 $
*/
require("../../main.inc.php");


function llxHeader($head = "", $title = "",$noscript = "")
{
  global $user, $conf, $langs;

  $langs->load("companies");
  $langs->load("commercial");

  top_menu($head, $title,"",$noscript);

  $menu = new Menu();

  // Clients
//  $menu->add(DOL_URL_ROOT."/BabelProspect/nouvelleProspection.php", $langs->trans("Campagne de prospection"));
////  if ($user->rights->societe->creer)
////    {
//      $menu->add_submenu(DOL_URL_ROOT."/BabelProspect/nouvelleProspection.php?action=add", $langs->trans("Nouvelle campagne"));
////    }
//
//  $menu->add_submenu(DOL_URL_ROOT."/BabelProspect/nouvelleProspection.php?action=list", $langs->trans("Liste"));
//
//var_dump($menu);
//var_dump($user->rights);

  left_menu($menu->liste);
}
function llxHeaderNoMenu($head = "", $title = "",$noscript = "")
{
  global $user, $conf, $langs;

  $langs->load("companies");
  $langs->load("commercial");


    top_htmlhead($head, $title,"",$noscript);

    print '<body id="mainbody"><div id="dhtmltooltip"></div>';
        if (is_dir(DOL_DOCUMENT_ROOT."/Synopsis_Common/js/wz_tooltip"))
        {
            print '<script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/Synopsis_Common/js/wz_tooltip/wz_tooltip.js"></script>'."\n";
        }



}

?>
