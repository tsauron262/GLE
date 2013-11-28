<?php
/* Copyright (C) 2006-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 * or see http://www.gnu.org/
 *
 * $Id: synopsisdemandeinterv.lib.php,v 1.4 2007/08/28 07:46:01 hregis Exp $
 * $Source: /cvsroot/dolibarr/dolibarr/htdocs/lib/synopsisdemandeinterv.lib.php,v $
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
/**
   \file       htdocs/lib/synopsisdemandeinterv.lib.php
   \brief      Ensemble de fonctions de base pour le module synopsisdemandeinterv
   \ingroup    synopsisdemandeinterv
   \version    $Revision: 1.4 $

   Ensemble de fonctions de base de dolibarr sous forme d'include
*/

function synopsisdemandeinterv_prepare_head($synopsisdemandeinterv)
{
  global $langs, $conf, $user, $db;
  $langs->load("synopsisdemandeinterv");

  $h = 0;
  $head = array();

  $head[$h][0] = DOL_URL_ROOT.'/synopsisdemandeinterv/fiche.php?id='.$synopsisdemandeinterv->id;
  $head[$h][1] = $langs->trans("Card");
  $head[$h][2] = 'card';
  $h++;

  $head[$h][0] = DOL_URL_ROOT.'/synopsisdemandeinterv/contact.php?id='.$synopsisdemandeinterv->id;
    $head[$h][1] = $langs->trans('InterventionContact');
    $head[$h][2] = 'contact';
    $h++;

    if (isset($conf->use_preview_tabs) && $conf->use_preview_tabs)
    {
        $head[$h][0] = DOL_URL_ROOT.'/synopsisdemandeinterv/apercu.php?id='.$synopsisdemandeinterv->id;
        $head[$h][1] = $langs->trans('Preview');
        $head[$h][2] = 'preview';
        $h++;
    }

    $head[$h][0] = DOL_URL_ROOT.'/synopsisdemandeinterv/extra.php?id='.$synopsisdemandeinterv->id;
    $head[$h][1] = $langs->trans('Extra');
    $head[$h][2] = 'extra';
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/synopsisdemandeinterv/note.php?id='.$synopsisdemandeinterv->id;
    $head[$h][1] = $langs->trans('Note');
    $head[$h][2] = 'note';
    $h++;
    $head[$h][0] = DOL_URL_ROOT.'/synopsisdemandeinterv/document.php?id='.$synopsisdemandeinterv->id;
    $head[$h][1] = $langs->trans('Documents');
    $head[$h][2] = 'documents';
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/synopsisdemandeinterv/info.php?id='.$synopsisdemandeinterv->id;
    $head[$h][1] = $langs->trans('Info');
    $head[$h][2] = 'info';
    $h++;

    if ($conf->global->MAIN_MODULE_SYNOPSISPROCESS && $user->rights->process->lire &&  DoesElementhasProcess($db,'synopsisdemandeinterv'))
    {
        $head[$h][0] = DOL_URL_ROOT.'/Synopsis_Process/listProcessForElement.php?type=synopsisdemandeinterv&id='.$synopsisdemandeinterv->id;
        $head[$h][1] = $langs->trans("Process");
        $head[$h][2] = 'process';
        $head[$h][4] = 'ui-icon ui-icon-gear';
        $h++;
    }

  return $head;
}

?>
