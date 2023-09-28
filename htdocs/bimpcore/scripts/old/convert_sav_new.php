<?php

define('NOLOGIN', '1');

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(7200);

//llxHeader();

echo '<!DOCTYPE html>';
echo '<html lang="fr">';

echo '<head>';
//echo '<link rel="stylesheet" type="text/css" href="' . DOL_URL_ROOT . '/bimpcore/views/css/ticket.css' . '"/>';
echo '<script src="/test2/includes/jquery/js/jquery.min.js?version=6.0.4" type="text/javascript"></script>';
echo '</head>';

echo '<body>';

global $db;
$bdb = new BimpDb($db);

$where = '`id_propal` > 0';

$id_sav = (int) BimpTools::getValue('id_sav', 0);
if ($id_sav) {
    $where .= ' AND `id` = ' . $id_sav;
} else {
    $where .= ' AND `status` != 999';
}

$rows = $bdb->getRows('bs_sav', $where, null, 'array', array(
    'id', 'id_propal'
        ));

if (!is_null($rows) && count($rows)) {
    $sav = BimpObject::getInstance('bimpsupport', 'BS_SAV');
    $sav->check_version = false;
    $equipment = BimpObject::getInstance('bimpequipment', 'Equipment');
    BimpObject::loadClass('bimpsupport', 'BS_SavPropalLine');
    $i = 0;

    echo count($rows) . ' SAV à traiter <br/><br/>';

    $i = 1;
    foreach ($rows as $r) {
        echo $i . ' - SAV ' . $r['id'] . ' ';
        $sav->fetch((int) $r['id']);
        
        $errors = $sav->convertSav($equipment);

        if (count($errors)) {
            echo '<br/>';
            echo BimpTools::getMsgFromArray($errors, '[ECHEC]');
        } else {
            echo '[OK]';
        }

        echo '<br/><br/>';

        $i++;
    }
} else {
    echo 'AUCUN SAV A TRAITER TROUVE';
}

echo '</body></html>';

//llxFooter();
