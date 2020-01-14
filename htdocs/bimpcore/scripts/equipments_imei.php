<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_v2.php';

top_htmlhead('', 'Récupération IMEI équipements', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

session_destroy();
set_time_limit(3600); // 1h
ignore_user_abort(0);

global $db;
$bdb = new BimpDb($db);

$gsx = new GSX_v2();

if (!$gsx->logged) {
    echo BimpRender::renderAlerts('Non connecté à GSX');
    exit;
}

$equipment = BimpObject::getInstance('bimpequipment', 'Equipment');

$rows = $equipment->getList(array(
    'imei2' => ''
        ), 10000, 1, 'id', 'desc', 'array', array('id', 'serial'));

echo count($rows) . ' équipement(s) à traiter <br/><br/>';

$nDone = 0;
$nOK = 0;

foreach ($rows as $r) {
    if (!$gsx->logged) {
        echo BimpRender::renderAlerts('Déco de GSX');
        break;
    }

    if (!$r['serial']) {
        continue;
    }

    $ids = Equipment::gsxFetchIdentifiers($r['serial'], $gsx);

    $imei = $ids['imei'];
    $imei2 = $ids['imei2'];
    $meid = $ids['meid'];
    $serial = $ids['serial'];

    if (!$imei) {
        $imei = 'n/a';
    } elseif ($imei !== 'n/a') {
        $nOK++;
    }

    if (!$imei2) {
        $imei2 = 'n/a';
    }

    if (!$meid) {
        $meid = 'n/a';
    }

    $data = array(
        'imei'  => $imei,
        'imei2' => $imei2,
        'meid'  => $meid
    );

    if ($serial && ($imei === $r['serial'] || $imei2 === $r['serial'] || $meid === $r['serial'])) {
        $data['serial'] = $serial;
    }

    $nDone++;

    if ($bdb->update('be_equipment', $data, '`id` = ' . (int) $r['id']) <= 0) {
        echo BimpRender::renderAlerts('ERREUR SQL') . ' ' . $bdb->db->lasterror() . '<br/><br/>';
        break;
    }
}

echo 'Traités: ' . $nDone . '<br/>';
echo 'IMEI récupérés: ' . $nOK . '<br/>';

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();
