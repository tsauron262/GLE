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
function process_prepare_head($objsoc)
{
    global $langs, $conf, $user, $db;
    $h = 0;
    $head = array();

    $head[$h][0] = DOL_URL_ROOT . '/bimpdatasync/process.php?id_process=' . $objsoc->id;
    $head[$h][1] = $langs->trans("Paramètres généraux");
    $head[$h][2] = 'general';
    $h++;

    $head[$h][0] = DOL_URL_ROOT . '/bimpdatasync/process.php?id_process=' . $objsoc->id . '&tab=parameters';
    $head[$h][1] = $langs->trans("Paramètres personnalisés");
    $head[$h][2] = 'parameters';
    $h++;

    $head[$h][0] = DOL_URL_ROOT . '/bimpdatasync/process.php?id_process=' . $objsoc->id . '&tab=options';
    $head[$h][1] = $langs->trans("Options");
    $head[$h][2] = 'options';
    $h++;

    $head[$h][0] = DOL_URL_ROOT . '/bimpdatasync/process.php?id_process=' . $objsoc->id . '&tab=matching';
    $head[$h][1] = $langs->trans("Correspondances");
    $head[$h][2] = 'matching';
    $h++;

    $head[$h][0] = DOL_URL_ROOT . '/bimpdatasync/process.php?id_process=' . $objsoc->id . '&tab=triggers';
    $head[$h][1] = $langs->trans("Triggers");
    $head[$h][2] = 'triggers';
    $h++;

    $head[$h][0] = DOL_URL_ROOT . '/bimpdatasync/process.php?id_process=' . $objsoc->id . '&tab=operations';
    $head[$h][1] = $langs->trans("Opérations");
    $head[$h][2] = 'operations';
    $h++;

    if (in_array($objsoc->type, array('import', 'export'))) {
        $head[$h][0] = DOL_URL_ROOT . '/bimpdatasync/process.php?id_process=' . $objsoc->id . '&tab=crons';
        $head[$h][1] = $langs->trans("Tâches planifiées");
        $head[$h][2] = 'crons';
        $h++;
    }

    $head[$h][0] = DOL_URL_ROOT . '/bimpdatasync/process.php?id_process=' . $objsoc->id . '&tab=objects';
    switch ($objsoc->type) {
        case 'sync':
            $head[$h][1] = $langs->trans("Objets synchronisés");
            break;

        case 'import':
            $head[$h][1] = $langs->trans("Objets importés");
            break;

        case 'export':
            $head[$h][1] = $langs->trans("Objets exportés");
            break;
        
        default:
            $head[$h][1] = $langs->trans("Objets");
            break;
    }
    $head[$h][2] = 'objects';
    $h++;

//    complete_head_from_modules($conf, $langs, $objsoc, $head, $h, 'chrono');

    return $head;
}
?>
