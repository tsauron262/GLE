<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'Corr fac', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db, $user;

if (!BimpObject::objectLoaded($user)) {
    echo BimpRender::renderAlerts('Aucun utilisateur connecté');
    exit;
}

if (!$user->admin) {
    echo BimpRender::renderAlerts('Seuls les admin peuvent exécuter ce script');
}

$id_comm_line = 1251499;
$id_fac_line = 2753935;
$id_fac = 636799;

$bdb = new BimpDb($db);

//$bdb->delete('object_line_equipment', 'object_type = \'facture\' AND id_object_line = 2753935');

$rows = $bdb->getRows('br_reservation', 'id_commande_client_line = 1251499', NULL, 'array', array('id', 'id_equipment'));

foreach ($rows as $r) {
    $eq[] = (int) $r['id_equipment'];
}

$factures = array(
    $id_fac => array(
        'qty'        => 5000,
        'equipments' => $eq
    )
);

$comm_line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', $id_comm_line);

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();
