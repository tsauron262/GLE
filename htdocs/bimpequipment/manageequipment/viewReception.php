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
 * Functions
 */

function printTable($title, $suffix) {
    print '<h4><strong>' . $title . '</strong></h4>';

    print '<table id="table_'.$suffix.'" class="noborder objectlistTable" style="margin-top:20px">';
    print '<thead>';
    print '<th>Date d\'envoie</th>';
    print '<th>Référence</th>';
    print '<th>Numéro de série</th>';
    print '<th>Label</th>';
    print '<th>Prix</th>';
    print '<th style="text-align:right">Quantité envoyé</th>';
    print '<th style="width:32px"></th>';
    print '<th>Quantité reçu</th>';
    print '</thead>';
    print '<tbody></tbody>';
    print '</table>';
    print '<br>';
}

/*
 * View
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
    print '<div id="alertTop" style="clear:left"></div><div style="clear:both"></div>';

    printTable('En attente', 'pending');
} else {
    print '<strong>Transfert fermé le : ' . $transfer->date_closing . '</strong>';
}

printTable('Reçu', 'received');
printTable('Abandonné', 'canceled');

if ($transfer->status != $transfer::STATUS_RECEIVED) {
    print '<input id="register" type="button" class="butAction" value="Enregistrer">';
    if ($user->rights->bimpequipment->transfer->close)
        print '<input id="closeTransfer" type="button" class="butActionDelete" value="Clôturer le transfert"><br/><br/>';
}

$db->close();

llxFooter();
