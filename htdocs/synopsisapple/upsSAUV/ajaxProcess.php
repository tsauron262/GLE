<?php

if (!isset($_POST['action']))
    die('<p class="error">Aucun numéro ship-to valide fourni</p>');

require_once('../../main.inc.php');

error_reporting(E_ALL);
//error_reporting(E_ERROR);
ini_set('display_errors', 1);

require_once DOL_DOCUMENT_ROOT . '/synopsisapple/ups/GsxUps.class.php';

if (isset($_POST['shipTo']))
    $shipTo = $_POST['shipTo'];
else
    $shipTo = null;
$GU = new GsxUps($shipTo);

switch ($_POST['action']) {
    case 'loadShippingForm':
        $html = $GU->getShippingForm();
        die(json_encode(array(
            'html' => $html
        )));
        break;

    case 'createShipping':
        $result = $GU->createUpsShipment();

        die(json_encode(array(
            'result' => $result
        )));
        break;

    case 'registerShipmentOnGsx':
        $result = $GU->registerShipmentOnGsx();
        die(json_encode(array(
            'result' => $result
        )));
        break;

    case 'loadShipmentDetails':
        if (!isset($_POST['shipId']) || empty($_POST['shipId'])) {
            die(json_encode(array(
                'ok' => 0,
                'html' => '<p class="error">Erreur: ID absent</p>'
            )));
        }
        global $db;
        $ship = new shipment($db, $_POST['shipId']);
        die(json_encode(array(
            'ok' => 1,
            'html' => $ship->getInfosHtml()
        )));
        break;
}