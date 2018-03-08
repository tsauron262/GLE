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
$arrayofjs = array('/includes/jquery/plugins/select2/select2.js', '/bimpequipment/manageequipment/js/receptionAjax.js', '/bimpequipment/manageequipment/js/inputEquipment.js');

$transfer = new BimpTransfer($db);
$transfer->fetch(GETPOST('id', 'int'));

/*
 * 	View
 */

llxHeader('', 'Réception', '', '', 0, 0, $arrayofjs, $arrayofcss);

print load_fiche_titre('Réception', $linkback);

if ($transfer->status != $transfer::STATUS_RECEIVED) {
    print '<table class="entry">';
    print '<tr><td><strong>Réf. ou code barre ou numéro de série</strong></td>';
    print '<td><input name="refScan" class="custInput" style="width : 300px"></td></tr>';
    print '<tr><td style="text-align: right;"><strong> Quantité</strong></td>';
    print '<td><input id="qty" type="number" class="custInput" style="width: 60px" value=1 min=1></td></tr>';

    print '</table>';
} else {
    print '<strong>Transfert fermé le : '.$transfer->date_closing.'</strong>';
}

print '<div id="alertTop" style="clear:left"></div>';

print '<table id="product_table" class="noborder objectlistTable" style="margin-top:20px">';
print '<thead>';
print '<th>Groupes scanné</th>';
print '<th>Référence</th>';
print '<th>Numéro de série</th>';
print '<th>Label</th>';
print '<th style="text-align:right">Quantité envoyé</th>';
print '<th style="width:32px"></th>';
print '<th>Quantité reçu</th>';
print '</thead>';
print '<tbody></tbody>';
print '</table>';

print '<br>';

if ($transfer->status != $transfer::STATUS_RECEIVED) {
    print '<input id="register" type="button" class="butAction" value="Enregistrer">';
    print '<input id="closeTransfer" type="button" class="butAction" value="Enregistrer et fermer">';
}

$db->close();

llxFooter();
