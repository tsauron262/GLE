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

$fk_entrepot = GETPOST('boutique');

if ($fk_entrepot == '')
    $fk_entrepot = $user->array_options['options_defaultentrepot'];

/**
 * Functions
 */
function printOptionsBoutique($boutiques, $fk_entrepot) {
    print '<option></option>';

    if ($fk_entrepot != '') {
        foreach ($boutiques as $id => $name) {
            if ($id == $fk_entrepot || $fk_entrepot == $name)
                print '<option value="' . $id . '" selected>' . $name . '</option>';
            else
                print '<option value="' . $id . '">' . $name . '</option>';
        }
    } else {
        foreach ($boutiques as $id => $name) {
            print '<option value="' . $id . '">' . $name . '</option>';
        }
    }
}

/*
 * 	View
 */

llxHeader('', 'Accueil boutique', '', '', 0, 0, $arrayofjs, $arrayofcss);

print load_fiche_titre('Accueil boutique', $linkback);

$boutiques = getAllEntrepots($db);

print '<div id="shopDiv" style="float:left">';
print '<strong>Boutique</strong> ';
print '<select id="warehouseSelect" class="select2 cust" style="width: 200px;">';
printOptionsBoutique($boutiques, $fk_entrepot);
print '</select> ';
print '</div>';

print '<br/>';


print '<br/><div id="allTheFiche" class="fadeInOut">';
print '<div id="ph_links"></div>';
//
//print '<h4><strong>Transferts en cours</strong></h4>';
//print '<div id="alertPlaceHolder" style="clear : left"></div>';
//
//print '<div class="object_list_table">';
//print '<table id="table_transfer" class="noborder objectlistTable" style="margin-top:10px">';
//print '<thead>';
//print '<th>Ref</th>';
//print '<th>Responsable</th>';
//print '<th>Statut</th>';
//print '<th>Date d\'ouverture</th>';
//print '<th>Date de réception</th>';
//print '<th>Nombre de produit envoyés</th>';
//print '<th>Entrepot de départ</th>';
//print '<th>Lien</th>';
//print '</thead>';
//print '<tbody></tbody>';
//print '</table>';
//print '</div>';

print '<h4><strong>Commandes fournisseur en attentes</strong></h4>';

print '<div class="object_list_table">';
print '<table id="table_order" class="noborder objectlistTable" style="margin-top:10px">';
print '<thead>';
print '<th>Fournisseur</th>';
print '<th>Statut</th>';
print '<th>Date d\'ouverture</th>';
//print '<th>Nombre de produit commandé</th>';
print '<th>Lien livraison</th>';
print '</thead>';
print '<tbody></tbody>';
print '</table>';
print '</div>';

print '</div>';
$db->close();

llxFooter();
