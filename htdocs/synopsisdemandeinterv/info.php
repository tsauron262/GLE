<?php

/* Copyright (C) 2005-2007  Regis Houssin  <regis.houssin@capnetworks.com>
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
 * GLE by Synopsis et DRSI
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
/*
 */

/**
  \file       htdocs/synopsisdemandeinterv/info.php
  \ingroup    synopsisdemandeinterv
  \brief      Page d'affichage des infos d'une fiche d'intervention
  \version    $Id: info.php,v 1.4 2008/02/25 20:03:26 eldy Exp $
 */
require('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT . "/synopsisdemandeinterv/class/synopsisdemandeinterv.class.php");
require_once(DOL_DOCUMENT_ROOT . "/synopsisdemandeinterv/core/lib/synopsisdemandeinterv.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/fichinter.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/functions2.lib.php");

$langs->load('companies');

$synopsisdemandeintervid = isset($_GET["id"]) ? $_GET["id"] : '';

// Security check
if ($user->societe_id)
    $socid = $user->societe_id;
$result = restrictedArea($user, 'synopsisdemandeinterv', $synopsisdemandeintervid, 'synopsisdemandeinterv');


/*
 *    View
 */

llxHeader();

$synopsisdemandeinterv = new Synopsisdemandeinterv($db);
$synopsisdemandeinterv->fetch($_GET['id']);

if ($synopsisdemandeinterv->id) {

    $societe = new Societe($db);
    $societe->fetch($synopsisdemandeinterv->socid);

    $head = synopsisdemandeinterv_prepare_head($synopsisdemandeinterv);
    dol_fiche_head($head, 'info', $langs->trans('DI'));

    $synopsisdemandeinterv->info($synopsisdemandeinterv->id);

    print '<table width="100%"><tr><td>';
    dol_print_object_info($synopsisdemandeinterv);
    print '</td></tr></table>';

    print '</div>';

// Juste pour eviter bug IE qui reorganise mal div precedents si celui-ci absent
    print '<div class="tabsAction">';
    print '</div>';
}

$db->close();

llxFooter('$Date: 2008/02/25 20:03:26 $ - $Revision: 1.4 $');
?>
