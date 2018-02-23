<?php

/**
 *  \file       htdocs/bimpequipment/manageequipment/viewInventory.php
 *  \ingroup    bimpequipment
 *  \brief      Used while checking stock
 */
include_once '../../main.inc.php';

include_once DOL_DOCUMENT_ROOT . '/bimpequipment/manageequipment/lib/entrepot.lib.php';

$arrayofcss = array('/includes/jquery/plugins/select2/select2.css', '/bimpequipment/manageequipment/css/transfertStyles.css', '/bimpcore/views/css/bimpcore_bootstrap_new.css');
$arrayofjs = array('/includes/jquery/plugins/select2/select2.js', '/bimpequipment/manageequipment/js/inputEquipment.js', '/bimpequipment/manageequipment/js/inventoryMainAjax.js');

$inventory_id = GETPOST('id', 'int');

/*
 * 	View
 */

llxHeader('', 'Inventaire n°' . $inventory_id, '', '', 0, 0, $arrayofjs, $arrayofcss);

print load_fiche_titre('Inventaire n°' . $inventory_id, $linkback);

//print '<div class="object_list_table">';
//print '<table id="allInventories" class="noborder objectlistTable" style="margin-top:20px">';
//print '<thead>';
//print '<th>Identifiant</th>';
//print '<th>Responsable</th>';
//print '<th>Statut</th>';
//print '<th>Date d\'ouverture</th>';
//print '<th>Date de fermeture</th>';
//print '<th>Nombre de produit scanné</th>';
//print '<th>Lien</th>';
//print '</thead>';
//print '<tbody></tbody>';
//print '</table>';
//print '<div>';


$db->close();

llxFooter();
