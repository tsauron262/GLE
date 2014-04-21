<?php

//error_reporting(E_ALL);
//error_reporting(E_ERROR);
//ini_set('display_errors', 1);

require_once '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/includes/nusoap/lib/nusoap.php';
require_once ( 'gsxDatas.class.php' );

$userId = 'Corinne@actitec.fr';
$password = 'cocomart01';
$serviceAccountNo = '0000100635';

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'newSerial':
            if (isset($_GET['serial'])) {
                $datas = new gsxDatas($_GET['serial'], $userId, $password, $serviceAccountNo);
                echo $datas->getLookupHtml();
            } else {
                echo '<p class="error">Aucun numéro de série fournit</p>' . "\n";
            }
            break;

        case 'loadParts':
            if (isset($_GET['serial']) && $_GET['serial']) {
                $datas = new gsxDatas($_GET['serial'], $userId, $password, $serviceAccountNo);
                echo $datas->getPartsListHtml();
            } else {
                echo '<p class="error">Impossible d\'obtenir la liste des composants : numéro de série invalide</p>' . "\n";
            }
            break;

        case 'savePartsCart':
            echo '<p class="confirmation">Le panier a été correctement enregistré</p>';
            break;

        case 'sendPartsOrder':
            break;
    }
    die('');
}
?>
