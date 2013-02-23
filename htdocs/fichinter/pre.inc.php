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
 * $Source: /cvsroot/dolibarr/dolibarr/htdocs/fichinter/pre.inc.php,v $
 *
 */
/*
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
  */
require("../main.inc.php");

$langs->load("interventions");

function Synopsis_fichinter_prepare_head($fichinter)
{
  global $langs, $conf, $user, $db;
  $langs->load("fichinter");

  $h = 0;
  $head = array();

  $head[$h][0] = DOL_URL_ROOT.'/fichinter/fiche.php?id='.$fichinter->id;
  $head[$h][1] = $langs->trans("Card");
  $head[$h][2] = 'card';
  $h++;

  $head[$h][0] = DOL_URL_ROOT.'/fichinter/ficheFast.php?id='.$fichinter->id;
  $head[$h][1] = "Fiche rapide";
  $head[$h][2] = 'cardFast';
  $h++;

  $head[$h][0] = DOL_URL_ROOT.'/fichinter/contact.php?id='.$fichinter->id;
    $head[$h][1] = $langs->trans('InterventionContact');
    $head[$h][2] = 'contact';
    $h++;

    if ($conf->use_preview_tabs)
    {
        $head[$h][0] = DOL_URL_ROOT.'/fichinter/apercu.php?id='.$fichinter->id;
        $head[$h][1] = $langs->trans('Preview');
        $head[$h][2] = 'preview';
        $h++;
    }

    $head[$h][0] = DOL_URL_ROOT.'/fichinter/extra.php?id='.$fichinter->id;
    $head[$h][1] = $langs->trans('Extra');
    $head[$h][2] = 'extra';
    $h++;


    $head[$h][0] = DOL_URL_ROOT.'/fichinter/quality.php?id='.$fichinter->id;
    $head[$h][1] = $langs->trans('Qualit&eacute;');
    $head[$h][2] = 'quality';
    $h++;


    $head[$h][0] = DOL_URL_ROOT.'/fichinter/note.php?id='.$fichinter->id;
    $head[$h][1] = $langs->trans('Note');
    $head[$h][2] = 'note';
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/fichinter/document.php?id='.$fichinter->id;
    $head[$h][1] = $langs->trans('Documents');
    $head[$h][2] = 'documents';
    $h++;


    $head[$h][0] = DOL_URL_ROOT.'/fichinter/info.php?id='.$fichinter->id;
    $head[$h][1] = $langs->trans('Info');
    $head[$h][2] = 'info';
    $h++;

  return $head;
}


?>
