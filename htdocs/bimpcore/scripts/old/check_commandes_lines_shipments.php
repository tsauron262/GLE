<?php

die('Désactivé'); 

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'CHECK BL DISPARUS', 0, 0, array(), array());

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

$bdb = new BimpDb($db);
$rows = $bdb->getRows('bimp_commande_line', 'type != 2', null, 'array', array('id', 'id_obj', 'shipments'), 'id', 'desc');
$shipments = BimpCache::getBimpObjectList('bimplogistique', 'BL_CommandeShipment');

foreach ($rows as $r) {
    if ((string) $r['shipments']) {
        $ships = json_decode($r['shipments'], 1);

        foreach ($ships as $id_s => $data) {
            if ((isset($data['qty']) && (float) $data['qty']) ||
                    (isset($data['equipments']) && count($data['equipments']))) {
                if (!in_array((int) $id_s, $shipments)) {
                    echo 'CMDE #' . $r['id_obj'] . ' - LN #' . $r['id'] . ' - EXP #' . $id_s . '<br/>';
                }
            }
        }
    }
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();
