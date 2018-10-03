<?php

/* Copyright (C) 2005      Patrick Rouillon     <patrick@rouillon.net>
 * Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2015 Philippe Grand       <philippe.grand@atoo-net.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *     \file       htdocs/commande/contact.php
 *     \ingroup    commande
 *     \brief      Onglet de gestion des contacts de commande
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/order.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

$arrayofcss = array('/includes/jquery/plugins/select2/select2.css', '/bimpequipment/manageequipment/css/transfertStyles.css', '/bimpcore/views/css/bimpcore_bootstrap_new.css');
$arrayofjs = array('/includes/jquery/plugins/select2/select2.js', '/bimpequipment/manageequipment/js/orderClientAjax.js', '/bimpequipment/manageequipment/js/inputEquipment.js');

$langs->load("companies");
$langs->load("bills");
$langs->load("orders");

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'alpha');

// Security check
if ($user->societe_id)
    $socid = $user->societe_id;
$result = restrictedArea($user, 'commande', $id, '');


$object = new Commande($db);
if (!$object->fetch($id, $ref) > 0) {
    dol_print_error($db);
    exit;
}

/*
 * Functions
 */

function printTable($id, $header) {
    print '<div class="object_list_table">';
    print '<table id="' . $id . '" class="noborder objectlistTable" style="margin-bottom:50px">';
    print '<thead><tr class="headerRow">';
    print '<th>Numéro groupe</th>';
    print '<th>Référence</th>';
    print '<th>Numéro de série</th>';
    print '<th>Label</th>';
    print '<th style="text-align: right">Quantité ' . $header . '</th>';
    print '<th style="width: 10px"></th>';
    print '<th>Modifier</th>';
//    print '<th>Quantité total</th>';
//    print '<th>Mettre en stock <input type="checkbox" name="checkAll"></th>';
    print '</tr></thead>';
    print '<tbody></tbody>';
    print '</table>';
}

function printEntryAndTable($header, $status) {
    print '<h4><strong>' . $header . '</strong></h4>';
    print '<span style="width: 175px ; display: inline-block;"><strong>Ref ou label ou code barre</strong></span>';
    print '<input name="scan_' . $status . '" class="custInput" style="width : 300px"><br/>';
    print '<span style="width: 175px ; display: inline-block;"><strong>Quantité</strong></span>';
    print '<input id="qty_' . $status . '" type="number" class="custInput" style="width: 60px ; margin-top: 5px ; margin-bottom: 5px " value=1 min=1>';
    print '<div id="alert_' . $status . '" style="clear:left"></div>';
    printTable('table_' . $status, $header);
}

/*
 * View
 */

llxHeader('', 'BimpExpédition', $help_url, '', 0, 0, $arrayofjs, $arrayofcss);

$form = new Form($db);

if ($id > 0 || !empty($ref)) {
    $object->fetch_thirdparty();

    $head = commande_prepare_head($object);

    dol_fiche_head($head, 'bimporderclient', $langs->trans("CustomerOrder"), -1, 'order');

    // Order card

    $linkback = '<a href="' . DOL_URL_ROOT . '/commande/list.php?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';


    $morehtmlref = '<div class="refidno">';
    // Ref customer
    $morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
    $morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
    // Thirdparty
    $morehtmlref.='<br>' . $langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1);
    // Project
    if (!empty($conf->projet->enabled)) {
        $langs->load("projects");
        $morehtmlref.='<br>' . $langs->trans('Project') . ' ';
        if ($user->rights->commande->creer) {
            if ($action != 'classify')
            //$morehtmlref.='<a href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
                $morehtmlref.=' : ';
            if ($action == 'classify') {
                //$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
                $morehtmlref.='<form method="post" action="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '">';
                $morehtmlref.='<input type="hidden" name="action" value="classin">';
                $morehtmlref.='<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                $morehtmlref.=$formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
                $morehtmlref.='<input type="submit" class="button valignmiddle" value="' . $langs->trans("Modify") . '">';
                $morehtmlref.='</form>';
            } else {
                $morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
            }
        } else {
            if (!empty($object->fk_project)) {
                $proj = new Project($db);
                $proj->fetch($object->fk_project);
                $morehtmlref.='<a href="' . DOL_URL_ROOT . '/projet/card.php?id=' . $object->fk_project . '" title="' . $langs->trans('ShowProject') . '">';
                $morehtmlref.=$proj->ref;
                $morehtmlref.='</a>';
            } else {
                $morehtmlref.='';
            }
        }
    }
    $morehtmlref.='</div>';


    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


    print '<div class="fichecenter">';
    print '<div class="underbanner clearboth"></div>';


    $cssclass = "titlefield";
    print '</div>';
}

printEntryAndTable('Commandé', 'ordered');
printEntryAndTable('Expédié', 'sent');
printEntryAndTable('Reçu', 'received');

dol_fiche_end();

llxFooter();
$db->close();
