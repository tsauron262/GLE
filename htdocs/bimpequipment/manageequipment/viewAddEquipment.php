<?php

/**
 *  \file       htdocs/bimpequipment/manageequipment/viewAddEquipment.php
 *  \ingroup    bimpequipment
 *  \brief      Used while adding equipments
 */

include_once '../../main.inc.php';

include_once DOL_DOCUMENT_ROOT.'core/class/html.form.class.php';
include_once DOL_DOCUMENT_ROOT.'/bimpequipment/manageequipment/lib/entrepot.lib.php';

$arrayofcss = array('/includes/jquery/plugins/select2/select2.css', '/bimpequipment/manageequipment/css/addStyles.css', '/bimpcore/views/css/bimpcore_bootstrap_new.css');
$arrayofjs = array('/includes/jquery/plugins/select2/select2.js', '/bimpequipment/manageequipment/js/addAjax.js');


/*
 * 	View
 */

llxHeader('', 'Ajouter équipement', '', '', 0, 0, $arrayofjs, $arrayofcss);

print load_fiche_titre('Ajouter équipement', $linkback);

$entrepots = getAllEntrepots($db);

print 'Entrepot <select id="entrepot" class="select2 cust" style="width: 200px;">';
foreach ($entrepots as $id => $name) {
    print '<option value="'.$id.'">'.$name.'</option>';
}
print '</select><br/><br/>';

$form = new Form($db);
$form->select_produits();
print '<strong id="alertProd" style="color: red ; margin-left: 10px"> </strong>';

print '<div class="object_list_table">';
print '<table id="hereEquipment" class="noborder objectlistTable">';
print '<thead><tr class="headerRow">';
print '<th>Nombre de produits scannés</th>';
print '<th>Identifiant du produit</th>';
print '<th>Numéro de série</th>';
print '<th>Note</th>';
print '<th>Réponse serveur</th>';
print '</tr></thead>';
print '<tbody></tbody>';
print '</table>';

print '<br/><input id="enregistrer" type="button" class="butAction" value="Enregistrer">';

print '<br/><div id="alertMessage"></div>';


$db->close();

llxFooter();
