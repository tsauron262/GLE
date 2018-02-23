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


/*
 * 	View
 */

llxHeader('', 'Inventaire', '', '', 0, 0, $arrayofjs, $arrayofcss);

print load_fiche_titre('Inventaire - accueil', $linkback);

$entrepots = getAllEntrepots($db);

print '<h4 style="margin-top:-15px ; margin-bottom:-10px"><strong>Inventaires existants</strong></h4></br>';

print '<div id="divEntrepot" style="float:left">';
print '<strong>Entrepôt</strong></br>';
print '<select id="entrepotTable" class="select2 cust" style="width: 200px;">';
print '<option></option>';
foreach ($entrepots as $id => $name) {
    print '<option value="' . $id . '">' . $name . '</option>';
}
print '</select> ';
print '</div><br/>';

print '<div id="alertPlaceholder" style="clear:left"></div>';

print '<div id="allTheFiche" >';

print '<div class="object_list_table">';
print '<table id="allInventories" class="noborder objectlistTable" style="margin-top:20px">';
print '<thead>';
print '<th>Identifiant</th>';
print '<th>Responsable</th>';
print '<th>Statut</th>';
print '<th>Date d\'ouverture</th>';
print '<th>Date de fermeture</th>';
print '<th>Nombre de produit scanné</th>';
print '<th>Lien</th>';
print '</thead>';
print '<tbody></tbody>';
print '</table>';
print '<div>';

print '</div>';

print '<br><hr class="hrShadow">';
print '<h4><strong>Créer inventaire</strong></h4></br>';

print '<p/>Sélectionnez un entrepôt pour créer un inventaire</p>';
print '<select id="entrepotCreate" class="select2 cust" style="width: 200px;">';
print '<option></option>';
foreach ($entrepots as $id => $name) {
    print '<option value="' . $id . '">' . $name . '</option>';
}
print '</select> ';

$db->close();

llxFooter();
