<?php
/* Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007      Patrick Raguin <patrick.raguin@gmail.com>
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
  * GLE by Synopsis et DRSI
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

    $head[$h][0] = DOL_URL_ROOT.'/Synopsis_Chrono/fiche.php?id='.$objsoc->id;
    $head[$h][1] = $langs->trans("Card");
    $head[$h][2] = 'chrono';
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/Synopsis_Chrono/info.php?id='.$objsoc->id;
    $head[$h][1] = $langs->trans("Info");
    $head[$h][2] = 'info';
    $h++;


    if ($objsoc->model->hasFile == 1)
    {
        $head[$h][0] = DOL_URL_ROOT.'/Synopsis_Chrono/document.php?id='.$objsoc->id;
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
    return $head;
}

?>