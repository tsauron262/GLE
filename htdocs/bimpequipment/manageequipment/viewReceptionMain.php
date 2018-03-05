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
$arrayofjs = array('/includes/jquery/plugins/select2/select2.js');


/*
 * 	View
 */

llxHeader('', 'Réception - accueil', '', '', 0, 0, $arrayofjs, $arrayofcss);

print load_fiche_titre('Réception - accueil', $linkback);

print '<div id="alertPlaceholder" style="clear:left"></div>';

print '<div id="allTheFiche" class="object_list_table">';
print '<table id="allInventories" class="noborder objectlistTable" style="margin-top:20px">';
print '<thead>';
print '<th>Identifiant</th>';
print '<th>Responsable</th>';
print '<th>Statut</th>';
print '<th>Date d\'ouverture</th>';
print '<th>Date de réception</th>';
print '<th>Nombre de produit envoyés</th>';
print '<th>entrepot de départ</th>';
print '<th>Lien</th>';
print '</thead>';
print '<tbody>';

$transferObj = new BimpTransfer($db);

//$transfers = $transferObj->getTransfers(GETPOST('entrepot_id'), $transferObj::STATUS_SENT);
$transfers = $transferObj->getTransfers(GETPOST('entrepot_id'));


foreach ($transfers as $transfer) {
    $user = new User($db);
    $user->fetch($transfer['fk_user_create']);
    $transferObj = new BimpTransfer($db);
    $transferObj->id = $transfer['id'];

    print '<tr>';
    print '<td>' . $transfer['id'] . '</td>';
    print '<td>' . $user->getNomUrl(-1, '', 0, 0, 24, 0, '') . '</td>';
    if ($transfer['status'] == $transferObj::STATUS_DRAFT) {
        print '<td>Brouillon</td>';
    } elseif ($transfer['status'] == $transferObj::STATUS_SENT) {
        print '<td>Envoyé</td>';
    } elseif ($transfer['status'] == $transferObj::STATUS_RECEIVED) {
        print '<td>Reçu</td>';
    }
    print '<td>' . $transfer['date_opening'] . '</td>';
    print '<td>' . $transfer['date_closing'] . '</td>';
    print '<td>' . $transferObj->getProductSent() . '</td>';
    print '<td>' . $transfer['fk_warehouse_source'] . '</td>';
    print '<td><input type="button" class="butAction" value="Voir" onclick="location.href=\'' . DOL_URL_ROOT . '/bimpequipment/manageequipment/viewReception.php?id=' . $transfer['id'] . '\'" style="margin-top: 5px"></td>';
    print '</tr>';
}

print '</tbody></table>';

print '</div><br>';


$db->close();

llxFooter();
