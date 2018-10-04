<?php

/**
 *  \file       htdocs/bimpequipment/manageequipment/viewInventory.php
 *  \ingroup    bimpequipment
 *  \brief      Used while checking stock
 */
include_once '../../main.inc.php';

include_once DOL_DOCUMENT_ROOT . '/bimpequipment/manageequipment/lib/entrepot.lib.php';
include_once DOL_DOCUMENT_ROOT . '/bimpequipment/manageequipment/class/bimpinventory.class.php';

$arrayofcss = array('/includes/jquery/plugins/select2/select2.css', '/bimpequipment/manageequipment/css/transfertStyles.css', '/bimpcore/views/css/bimpcore_bootstrap_new.css');
$arrayofjs = array('/includes/jquery/plugins/select2/select2.js', '/bimpequipment/manageequipment/js/inputEquipment.js', '/bimpequipment/manageequipment/js/inventoryAjax.js');

$inventory_id = GETPOST('id', 'int');

/*
 * 	View
 */

llxHeader('', 'Inventaire n°' . $inventory_id, '', '', 0, 0, $arrayofjs, $arrayofcss);

print load_fiche_titre('Inventaire n°' . $inventory_id, $linkback);

$inventory = new BimpInventory($db);
$inventory->fetch($inventory_id);

if ($inventory->statut != $inventory::STATUT_CLOSED) {
    print '<strong>Réf. ou code barre ou numéro de série</strong> ';
    print '<input name="refScan" class="custInput" style="width : 300px">';
}

print '<a style="font-weight: bold; float:right" href="' . DOL_URL_ROOT . '/bimpequipment/manageequipment/viewInventoryMain.php">Retour liste</a>';

print '<div id="alertPlaceHolder" style="clear:both"></div>';

if ($user->rights->bimpequipment->inventory->read) {
    print '<div class="object_list_table">';
    print '<table id="productTable" class="noborder objectlistTable" style="margin-top:20px">';
    print '<thead><tr class="headerRow">';
    print '<th>Numéro du groupe</th>';
    print '<th>Identifiant</th>';
    print '<th>Référence</th>';
    print '<th>Numéro de série</th>';
    print '<th>Label</th>';
    print '<th>Prix Achat</th>';
    print '<th>Quantité Totale</th>';
    print '<th>Quantité Manquante</th>';
    print '<th>Quantité Scanné</th>';
    print '</tr></thead>';
    print '<tbody></tbody>';
    print '</table>';
    print '<div>';

    
    if ($inventory->statut != $inventory::STATUT_CLOSED and $user->rights->bimpequipment->inventory->create)
        print '<input id="closeInventory" type="button" class="butAction" value="Fermer l\'inventaire">';
    
}

$db->close();

llxFooter();
