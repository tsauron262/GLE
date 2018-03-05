<?php

/**
 *  \file       htdocs/bimpequipment/manageequipment/viewTransfertEquipment.php
 *  \ingroup    bimpequipment
 *  \brief      Used while adding equipments
 */
include_once '../../main.inc.php';

include_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
include_once DOL_DOCUMENT_ROOT . '/bimpequipment/manageequipment/lib/entrepot.lib.php';
include_once DOL_DOCUMENT_ROOT . '/bimpequipment/manageequipment/class/bimptransfer.class.php';

$arrayofcss = array('/includes/jquery/plugins/select2/select2.css', '/bimpequipment/manageequipment/css/transfertStyles.css', '/bimpcore/views/css/bimpcore_bootstrap_new.css');
$arrayofjs = array('/includes/jquery/plugins/select2/select2.js', '/bimpequipment/manageequipment/js/receptionAjax.js');


/*
 * 	View
 */

llxHeader('', 'Réception', '', '', 0, 0, $arrayofjs, $arrayofcss);

print load_fiche_titre('Réception - accueil', $linkback);

print '<div id="alertPlaceholder" style="clear:left"></div>';

print '<div id="allTheFiche" class="object_list_table">';
print '<table id="allInventories" class="noborder objectlistTable" style="margin-top:20px">';
print '<thead>';
print '<th>Groupes scanné</th>';
print '<th>Identifiant</th>';
print '<th>Référence</th>';
print '<th>Numéro de série</th>';
print '<th>Label</th>';
print '<th>Quantité envoyé</th>';
print '<th>Quantité reçu</th>';
print '</thead>';
print '<tbody></tbody>';
print '</table>';

print '</div><br>';


$db->close();

llxFooter();