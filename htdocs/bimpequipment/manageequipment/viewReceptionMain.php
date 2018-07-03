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
include_once DOL_DOCUMENT_ROOT . '/product/stock/class/entrepot.class.php';

$arrayofcss = array('/includes/jquery/plugins/select2/select2.css', '/bimpequipment/manageequipment/css/transfertStyles.css', '/bimpcore/views/css/bimpcore_bootstrap_new.css');
$arrayofjs = array('/includes/jquery/plugins/select2/select2.js');


/*
 * 	View
 */

llxHeader('', 'Réception - accueil', '', '', 0, 0, $arrayofjs, $arrayofcss);

print load_fiche_titre('Réception - accueil', $linkback);

print '<div id="alertPlaceholder" style="clear:left"></div>';

function printTableReception($db) {
    print '<div id="allTheFiche" class="object_list_table">';
    print '<table id="allInventories" class="noborder objectlistTable" style="margin-top:20px">';
    print '<thead>';
    print '<th>Identifiant</th>';
    print '<th>Responsable</th>';
    print '<th>Statut</th>';
    print '<th>Date d\'ouverture</th>';
    print '<th>Date de réception</th>';
    print '<th>Nombre de produit envoyés</th>';
    print '<th>Entrepot de départ</th>';
    print '<th>Entrepot arrivé</th>';
    print '<th>Lien</th>';
    print '</thead>';
    print '<tbody>';

    $obj_transfer = new BimpTransfer($db);

//$transfers = $obj_transfer->getTransfers(GETPOST('entrepot_id'), $obj_transfer::STATUS_SENT);
    $transfers = $obj_transfer->getTransfers(null, null, null, GETPOST('entrepot_id'));


    foreach ($transfers as $transfer) {
        $user = new User($db);
        $user->fetch($transfer['fk_user_create']);
        $obj_transfer = new BimpTransfer($db);
        $obj_transfer->id = $transfer['id'];
        $doli_warehouse = new Entrepot($db);
        $doli_warehouse->fetch($transfer['fk_warehouse_source']);
        $doli_warehouse2 = new Entrepot($db);
        $doli_warehouse2->fetch($transfer['fk_warehouse_dest']);

        print '<tr>';
        print '<td>' . $transfer['ref'] . '</td>';
        print '<td>' . $user->getNomUrl(-1, '', 0, 0, 24, 0, '') . '</td>';
        if ($transfer['status'] == $obj_transfer::STATUS_DRAFT) {
            print '<td>Brouillon</td>';
        } elseif ($transfer['status'] == $obj_transfer::STATUS_SENT) {
            print '<td>Envoyé</td>';
        } elseif ($transfer['status'] == $obj_transfer::STATUS_RECEIVED_PARTIALLY) {
            print '<td>Reçu partiellement</td>';
        } elseif ($transfer['status'] == $obj_transfer::STATUS_RECEIVED) {
            print '<td>Reçu</td>';
        }
        print '<td>' . $transfer['date_opening'] . '</td>';
        print '<td>' . $transfer['date_closing'] . '</td>';
        print '<td>' . $obj_transfer->getProductSent() . '</td>';
        print '<td>' . $doli_warehouse->getNomUrl() . '</td>';
        print '<td>' . $doli_warehouse2->getNomUrl() . '</td>';
        print '<td><input type="button" class="butAction" value="Voir" onclick="location.href=\'' . DOL_URL_ROOT . '/bimpequipment/manageequipment/viewReception.php?id=' . $transfer['id'] . '\'" style="margin-top: 5px"></td>';
        print '</tr>';
    }

    print '</tbody></table>';
}

printTableReception($db);

//print '</div><br>';


$db->close();

llxFooter();
