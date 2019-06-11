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

$rows = $bdb->getRows('br_commande_shipment', 1, null, 'array', array('id'));

if (!is_null($rows) && count($rows)) {
    foreach ($rows as $r) {
        $shipment = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeShipment', (int) $r['id']);
        if (BimpObject::objectLoaded($shipment)) {
            echo 'MAJ EXPE ' . $r['id'];

            $errors = $shipment->onLinesChange();

            if (count($errors)) {
                echo 'FAIL <br/>';
                echo '<pre>';
                print_r($errors);
                echo '</pre>';
            } else {
                echo 'OK';
            }
        } else {
            echo 'EXPE D\'ID ' . $r['id'] . ' ABSENTE';
        }

        echo '<br/>';
    }
}

$rows = $bdb->getRows('bl_commande_fourn_reception', 1, null, 'array', array('id'));

if (!is_null($rows) && count($rows)) {
    foreach ($rows as $r) {
        $reception = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeFournReception', (int) $r['id']);
        if (BimpObject::objectLoaded($reception)) {
            echo 'MAJ RECEPT ' . $r['id'];

            $errors = $reception->onLinesChange();

            if (count($errors)) {
                echo 'FAIL <br/>';
                echo '<pre>';
                print_r($errors);
                echo '</pre>';
            } else {
                echo 'OK';
            }
        } else {
            echo 'RECEPT D\'ID ' . $r['id'] . ' ABSENTE';
        }

        echo '<br/>';
    }
}

echo '</body></html>';

//llxFooter();

