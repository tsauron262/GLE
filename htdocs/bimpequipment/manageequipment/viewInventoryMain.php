<?php

/**
 *  \file       htdocs/bimpequipment/manageequipment/viewInventoryMain.php
 *  \ingroup    bimpequipment
 *  \brief      Home of inventory
 */
include_once '../../main.inc.php';

include_once DOL_DOCUMENT_ROOT . '/bimpequipment/manageequipment/lib/entrepot.lib.php';

$arrayofcss = array('/includes/jquery/plugins/select2/select2.css', '/bimpequipment/manageequipment/css/transfertStyles.css', '/bimpcore/views/css/bimpcore_bootstrap_new.css');
$arrayofjs = array('/includes/jquery/plugins/select2/select2.js', '/bimpequipment/manageequipment/js/inputEquipment.js', '/bimpequipment/manageequipment/js/inventoryMainAjax.js');

$fk_entrepot = GETPOST('entrepot');

if ($fk_entrepot == '')
    $fk_entrepot = $user->array_options['options_defaultentrepot'];

/*
 * 	View
 */

llxHeader('', 'Inventaire', '', '', 0, 0, $arrayofjs, $arrayofcss);

print load_fiche_titre('Inventaire - accueil', $linkback);

$entrepots = getAllEntrepots($db);

print '<div class="separatorDiv">';

print '<div class="separatorDiv" style="margin:-7px 0px 0px -7px ; float:left">';
print '<h4><strong>Inventaires existants</strong></h4>';
print '</div><br><br><br>';

print '<div id="divEntrepot" style="float:left">';
print '<strong>Entrepôt</strong></br>';
print '<select id="entrepotTable" class="select2 cust" style="width: 200px;">';
print '<option></option>';
foreach ($entrepots as $id => $name) {
    if ($fk_entrepot == $id)
        print '<option value="' . $id . '" selected>' . $name . '</option>';
    else
        print '<option value="' . $id . '" >' . $name . '</option>';
}
print '</select> ';
print '</div><br/>';

print '<div id="alertPlaceholder" style="clear:left"></div>';


print '<div id="allTheFiche" class="object_list_table">';
print '<table id="allInventories" class="noborder objectlistTable" style="margin-top:20px">';
print '<thead>';
print '<th>Identifiant</th>';
print '<th>Responsable</th>';
print '<th>Statut</th>';
print '<th>Date d\'ouverture</th>';
print '<th>Date de fermeture</th>';
print '<th>Nombre de produit scannés</th>';
print '<th>Lien</th>';
print '</thead>';
print '<tbody></tbody>';
print '</table>';
print '</div>';


print '</div><br>';

if ($user->rights->bimpequipment->inventory->create) {
    print '<div class="separatorDiv">';

    print '<div class="separatorDiv" style="margin:-7px 0px 0px -7px ; float:left">';
    print '<h4><strong>Créer inventaire</strong></h4>';
    print '</div><br><br><br>';

    print '<p/>Sélectionnez un entrepôt pour créer un inventaire</p>';
    print '<select id="entrepotCreate" class="select2 cust" style="width: 200px;">';
    print '<option></option>';
    foreach ($entrepots as $id => $name) {
        if ($fk_entrepot == $id)
            print '<option value="' . $id . '" selected>' . $name . '</option>';
        else
            print '<option value="' . $id . '" >' . $name . '</option>';
    }
    print '</select> ';

    print '<input id="createInventory" type="button" class="butAction" value="Créer inventaire">';

    print '<div id="alertCreate" style="clear:left"></div><br/>';


    print '</div>';
} else {
    print '<strong style="color:grey">Vous n\'avez pas les droits requis pour créer un inventaire.</strong>';
}

$db->close();

llxFooter();
