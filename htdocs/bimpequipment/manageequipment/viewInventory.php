<?php

/**
 *  \file       htdocs/bimpequipment/manageequipment/viewInventory.php
 *  \ingroup    bimpequipment
 *  \brief      Used while checking stock
 */
include_once '../../main.inc.php';

include_once DOL_DOCUMENT_ROOT . '/bimpequipment/manageequipment/lib/entrepot.lib.php';

$arrayofcss = array('/includes/jquery/plugins/select2/select2.css', '/bimpequipment/manageequipment/css/transfertStyles.css', '/bimpcore/views/css/bimpcore_bootstrap_new.css');
$arrayofjs = array('/includes/jquery/plugins/select2/select2.js', '/bimpequipment/manageequipment/js/inputEquipment.js', '/bimpequipment/manageequipment/js/inventoryAjax.js');

$inventory_id = GETPOST('id', 'int');

/*
 * 	View
 */

llxHeader('', 'Inventaire n°' . $inventory_id, '', '', 0, 0, $arrayofjs, $arrayofcss);

print load_fiche_titre('Inventaire n°' . $inventory_id, $linkback);

print '<strong>Réf. ou code barre ou numéro de série</strong> ';
print '<input name="refScan" class="custInput" style="width : 300px">';

print '<div id="alertPlaceHolder" style="clear:both"></div>';
print '<div class="object_list_table">';
print '<table id="productTable" class="noborder objectlistTable" style="margin-top:20px">';
print '<thead><tr class="headerRow">';
print '<th>Numéro du groupe</th>';
print '<th>Identifiant</th>';
print '<th>Référence</th>';
print '<th>Numéro de série</th>';
print '<th>Label</th>';
print '<th>Quantité Totale</th>';
print '<th>Quantité Manquante</th>';
print '<th>Quantité Indiqué</th>';
print '</tr></thead>';
print '<tbody></tbody>';
print '</table>';
print '<div>';


$db->close();

llxFooter();
