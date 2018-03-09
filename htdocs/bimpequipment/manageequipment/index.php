<?php

/**
 *  \file       htdocs/bimpequipment/manageequipment/home.php
 *  \ingroup    bimpequipment
 *  \brief      Redirect user on multiple link
 */
include_once '../../main.inc.php';

include_once DOL_DOCUMENT_ROOT . '/bimpequipment/manageequipment/lib/entrepot.lib.php';

$arrayofcss = array('/includes/jquery/plugins/select2/select2.css', '/bimpequipment/manageequipment/css/transfertStyles.css', '/bimpcore/views/css/bimpcore_bootstrap_new.css');
$arrayofjs = array('/includes/jquery/plugins/select2/select2.js', '/bimpequipment/manageequipment/js/index.js');


/*
 * 	View
 */

llxHeader('', 'Accueil boutique', '', '', 0, 0, $arrayofjs, $arrayofcss);

print load_fiche_titre('Accueil boutique', $linkback);

$entrepots = getAllEntrepots($db);

print '<div id="shopDiv" style="float:left">';
print '<strong>Boutique</strong> ';
print '<select id="warehouseSelect" class="select2 cust" style="width: 200px;">';
print '<option></option>';
foreach ($entrepots as $id => $name) {
    print '<option value="' . $id . '">' . $name . '</option>';
}
print '</select> ';
print '</div>';

print '<br/>';


print '<br/><div id="allTheFiche" class="fadeInOut">';
print '<div id="ph_links"></div>';
print '<div id="alertPlaceHolder" style="clear : left"></div><br/><br/>';

print '<h4><strong>Transferts en cours</strong></h4>';

print '<div class="object_list_table">';
print '<table id="table_transfer" class="noborder objectlistTable" style="margin-top:10px">';
print '<thead>';
print '<th>Responsable</th>';
print '<th>Statut</th>';
print '<th>Date d\'ouverture</th>';
print '<th>Date de réception</th>';
print '<th>Nombre de produit envoyés</th>';
print '<th>Entrepot de départ</th>';
print '<th>Lien</th>';
print '</thead>';
print '<tbody>';
print '</div>';

print '<h4><strong>Commandes fournisseur en attentes</strong></h4>';

print '<div class="object_list_table">';
print '<table id="table_order" class="noborder objectlistTable" style="margin-top:10px">';
print '<thead>';
print '<th>Responsable</th>';
print '<th>Statut</th>';
print '<th>Date d\'ouverture</th>';
print '<th>Date de réception</th>';
print '<th>Nombre de produit envoyés</th>';
print '<th>Lien livraison</th>';
print '</thead>';
print '<tbody>';
print '</div>';

print '</div>';
$db->close();

llxFooter();
