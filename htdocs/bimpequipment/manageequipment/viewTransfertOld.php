<?php

/**
 *  \file       htdocs/bimpequipment/manageequipment/viewTransfertEquipment.php
 *  \ingroup    bimpequipment
 *  \brief      Used while adding equipments
 */
include_once '../../main.inc.php';

include_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
include_once DOL_DOCUMENT_ROOT.'/bimpequipment/manageequipment/lib/entrepot.lib.php';

$arrayofcss = array('/includes/jquery/plugins/select2/select2.css', '/bimpequipment/manageequipment/css/transfertStyles.css', '/bimpcore/views/css/bimpcore_bootstrap_new.css');
$arrayofjs = array('/includes/jquery/plugins/select2/select2.js', '/bimpequipment/manageequipment/js/inputEquipment.js', '/bimpequipment/manageequipment/js/transfertAjax.js');


/*
 * 	View
 */

llxHeader('', 'Transférer équipement', '', '', 0, 0, $arrayofjs, $arrayofcss);

print load_fiche_titre('Transférer équipement', $linkback);

$entrepots = getAllEntrepots($db);

print '<div id="divEntrepotStart" style="float:left">';
print '<strong>Entrepôt source</strong></br>';
print '<select id="entrepotStart" class="select2 cust" style="width: 200px;">';
print '<option></option>';
foreach ($entrepots as $id => $name) {
    print '<option value="' . $id . '">' . $name . '</option>';
}
print '</select> ';
print '</div>';

print '<div id="divEntrepotEnd" class="fadeInOut">';

print '<div  style="float:left ; margin: 14px 5px 0px 5px"> ';
print '<img src="css/next.png" > ';
print '</div>';
print '<div  style="float:left">';
print '<strong>Entrepôt Destination</strong><br>';

print '<select id="entrepotEnd" class="select2 cust" style="width: 200px;">';
print '<option></option>';
foreach ($entrepots as $id => $name) {
        print '<option value="' . $id . '">' . $name . '</option>';
}
print '</select></div></div><br/><br/><br/>';

print '<div id="allTheFiche" class="fadeInOut">';

print '<table class="entry">';
print '<tr><td><strong>Réf. ou code barre ou numéro de série</strong></td>';
print '<td><input name="refScan" class="custInput" style="width : 300px"></td></tr>';


$form = new Form($db);
print '<tr><td style="text-align: right;"><strong>Ref ou label</strong></td>';
print '<td>';
$form->select_produits('', 'productid', '', 20, 0, 1, 2, '', 1);
print '</td></tr>';
print '<tr><td style="text-align: right;"><strong> Quantité</strong></td>';
print '<td><input id="qty" type="number" class="custInput" style="width: 60px" value=1 min=1></td></tr>';
print '<tr><td style="text-align: right;"><strong>Ajouter</strong></td>';
print '<td><img id="addProduct" src="css/plus.ico" class="clickable" style="margin-bottom : -7px"></td></tr>';

print '</table>';

print '<div class="object_list_table">';
print '<table id="productTable" class="noborder objectlistTable" style="margin-top:20px">';
print '<thead><tr class="headerRow">';
print '<th>Groupes scanné</th>';
print '<th>Identifiant</th>';
print '<th>Référence</th>';
print '<th>Numéro de série</th>';
print '<th>Label</th>';
print '<th style="border-right:none">Quantité</th>';
print '<th style="border-left:none">Modifier</th>';
print '<th>Produit Restant</th>';
print '<th>Supprimer</th>';
print '</tr></thead>';
print '<tbody></tbody>';
print '</table>';
print '<div>';


print '<div id="alertProd" style="clear:left"></div><br/><br/><br/>';

print '<input id="enregistrer" type="button" class="butAction" value="Transférer">';

print '<br/><div id="alertEnregistrer" style="clear:left"></div><br/>';

print '<audio id="bipAudio" preload="auto"><source src="audio/bip.wav" type="audio/mp3" /></audio>';
print '<audio id="bipAudio2" preload="auto"><source src="audio/bip2.wav" type="audio/mp3" /></audio>';
print '<audio id="bipError" preload="auto"><source src="audio/error.wav" type="audio/mp3" /></audio>';

include(DOL_DOCUMENT_ROOT . "/bimpequipment/manageequipment/scan/scan.php");

print '</div>';
$db->close();

llxFooter();
