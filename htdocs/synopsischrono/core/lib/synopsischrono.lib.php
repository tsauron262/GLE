<?php
/* Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007      Patrick Raguin <patrick.raguin@gmail.com>
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
  * BIMP-ERP by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Create on : 4-1-2009
  *
  * Infos on http://www.synopsis-erp.com
  *
  */
/*
 * or see http://www.gnu.org/
 */

/**
 *    \file       htdocs/lib/company.lib.php
 *    \brief      Ensemble de fonctions de base pour le module societe
 *    \ingroup    societe
 *    \version    $Id: company.lib.php,v 1.31.2.1 2008/09/04 17:04:12 eldy Exp $
 */

/**
 * Enter description here...
 *
 * @param unknown_type $objsoc
 * @return unknown
 */
function chrono_prepare_head($objsoc)
{
    global $langs, $conf, $user, $db;
    $h = 0;
    $head = array();

    $head[$h][0] = DOL_URL_ROOT.'/synopsischrono/card.php?id='.$objsoc->id;
    $head[$h][1] = $langs->trans("Card");
    $head[$h][2] = 'chrono';
    $h++;

    if ($objsoc->model->hasSuivie == 1)
    {
    $head[$h][0] = DOL_URL_ROOT.'/synopsischrono/info.php?id='.$objsoc->id;
    $head[$h][1] = $langs->trans("Info");
    $head[$h][2] = 'info';
    $h++;
    }


    if ($objsoc->model->hasFile == 1)
    {
        $head[$h][0] = DOL_URL_ROOT.'/synopsischrono/document.php?id='.$objsoc->id;
        $head[$h][1] = $langs->trans("Document");
        $head[$h][2] = 'document';
        $h++;
    }

    if ($conf->global->MAIN_MODULE_PROCESS && $user->rights->process->lire &&  DoesElementhasProcess($db,'Chrono'))
    {
        $head[$h][0] = DOL_URL_ROOT.'/Babel_Process/listProcessForElement.php?type=Chrono&id='.$contrat->id;
        $head[$h][1] = $langs->trans("Process");
        $head[$h][2] = 'process';
        $head[$h][4] = 'ui-icon ui-icon-gear';
        $h++;
    }
    
    complete_head_from_modules($conf,$langs,$objsoc,$head,$h,'chrono');
    
    return $head;
}



function iniTabChronoList() {
    global $db;
    $requete = "DELETE FROM " . MAIN_DB_PREFIX . "const WHERE `name` LIKE  '%MAIN_MODULE_SYNOPSISCHRONO_TABS%'";
    $db->query($requete);
    $requete = "SELECT * FROM `" . MAIN_DB_PREFIX . "synopsischrono_conf` WHERE active = 1";
    $sql = $db->query($requete);
    $i = -1;
    $hasPropal = $hasProjet = $hasSoc = 0;
    while ($res = $db->fetch_object($sql)) {
        $i++;
        if ($res->hasPropal == "1")
            $hasPropal = 1;
        if ($res->hasProjet == "1")
            $hasProjet = 1;
        if ($res->hasSociete == "1")
            $hasSoc = 1;
    }

    $sql = $db->query("SELECT c.* FROM `" . MAIN_DB_PREFIX . "synopsischrono_key`, `" . MAIN_DB_PREFIX . "synopsischrono_conf` c WHERE `type_valeur` = 6 AND `type_subvaleur` IN(1000, 1007) AND model_refid = c.id GROUP by c.id");
    while ($result = $db->fetch_object($sql))
        $hasContrat = true;

    $i = 0;
    if ($hasPropal) {
        $i++;
        $type = "propal";
        $requete3 = "INSERT INTO `" . MAIN_DB_PREFIX . "const`(`name`, `entity`, `value`, `type`, `visible`) VALUES ('MAIN_MODULE_SYNOPSISCHRONO_TABS_" . $i . "',1,'" . $type . ":+chrono:Chrono:chrono@synopsischrono:".$user->rights->synopsischrono->read.":/synopsischrono/listByObjet.php?obj=" . $type . "&"/* . $res->idT . */ . "id=__ID__','chaine',0)";
//            die($requete3);
        $sql2 = $db->query($requete3);
    }
    if ($hasProjet) {
        $i++;
        $type = "project";
        $requete3 = "INSERT INTO `" . MAIN_DB_PREFIX . "const`(`name`, `entity`, `value`, `type`, `visible`) VALUES ('MAIN_MODULE_SYNOPSISCHRONO_TABS_" . $i . "',1,'" . $type . ":+chrono:Chrono:chrono@synopsischrono:".$user->rights->synopsischrono->read.":/synopsischrono/listByObjet.php?obj=" . $type . "&"/* . $res->idT . */ . "id=__ID__','chaine',0)";
//            die($requete3);
        $sql2 = $db->query($requete3);
    }
    if ($hasSoc) {
        $type = "thirdparty";
        $type2 = "soc";
        $i++;
        $requete3 = "INSERT INTO `" . MAIN_DB_PREFIX . "const`(`name`, `entity`, `value`, `type`, `visible`) VALUES ('MAIN_MODULE_SYNOPSISCHRONO_TABS_" . $i . "',1,'" . $type . ":+chrono:Chrono:chrono@synopsischrono:".$user->rights->synopsischrono->read.":/synopsischrono/listByObjet.php?obj=" . $type2 . "&"/* . $res->idT . */ . "id=__ID__','chaine',0)";
//            die($requete3);
        $sql2 = $db->query($requete3);
    }
    if ($hasContrat) {
        $type = "contract";
        $type2 = "ctr";
        $i++;
        $requete3 = "INSERT INTO `" . MAIN_DB_PREFIX . "const`(`name`, `entity`, `value`, `type`, `visible`) VALUES ('MAIN_MODULE_SYNOPSISCHRONO_TABS_" . $i . "',1,'" . $type . ":+chrono:Chrono:chrono@synopsischrono:".$user->rights->synopsischrono->read.":/synopsischrono/listByObjet.php?obj=" . $type2 . "&"/* . $res->idT . */ . "id=__ID__','chaine',0)";
//            die($requete3);
        $sql2 = $db->query($requete3);
    }
}

?>