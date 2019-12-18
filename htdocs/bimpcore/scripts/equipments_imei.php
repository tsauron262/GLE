<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_v2.php';

top_htmlhead('', 'Récupération IMEI équipements', 0, 0, array(), array());

echo '<body>';

//session_destroy();
set_time_limit(3600); // 1h
ignore_user_abort(1);

BimpCore::displayHeaderFiles();

global $db;
$bdb = new BimpDb($db);

$gsx = new GSX_v2();

if (!$gsx->logged) {
    echo BimpRender::renderAlerts('Non connecté à GSX');
    exit;
}

$equipment = BimpObject::getInstance('bimpequipment', 'Equipment');

$rows = $equipment->getList(array(
    'imei' => ''
        ), null, null, 'id', 'desc', 'array', array('id', 'serial'));

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

    $imei = Equipment::fetchImei($r['serial'], $gsx);

    if (!$imei) {
        $imei = 'n/a';
    } elseif ($imei !== 'n/a') {
        $nOK++;
    }

    $nDone++;

    if ($bdb->update('be_equipment', array(
                'imei' => $imei
                    ), '`id` = ' . (int) $r['id']) <= 0) {
        echo BimpRender::renderAlerts('ERREUR SQL') . ' ' . $bdb->db->lasterror() . '<br/><br/>';
        break;
    }
}

echo 'Traités: ' . $nDone . '<br/>';
echo 'IMEI récupérés: ' . $nOK . '<br/>';

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();