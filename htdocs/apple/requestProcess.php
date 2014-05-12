<?php

error_reporting(E_ALL);
//error_reporting(E_ERROR);
ini_set('display_errors', 1);

require_once '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/includes/nusoap/lib/nusoap.php';
require_once DOL_DOCUMENT_ROOT . '/apple/gsxDatas.class.php';

$userId = 'Corinne@actitec.fr';
$password = 'cocomart01';
$serviceAccountNo = '0000100635';

function fetchPartsList() {
    $parts = array();
    $i = 1;
    while (true) {
        if (isset($_POST['part_' . $i . '_ref'])) {
            $parts[] = array(
                'partNumber' => $_POST['part_' . $i . '_ref'],
                'quantity' => isset($_POST['part_' . i . '_qty']) ? $_POST['part_' . i . '_qty'] : 1
            );
        } else
            break;
        $i++;
    }
    return $parts;
}

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'loadProduct':
            if (isset($_GET['serial'])) {
                if (isset($_GET['prodId'])) {
                    $datas = new gsxDatas($_GET['serial'], $userId, $password, $serviceAccountNo);
                    echo $datas->getLookupHtml($_GET['prodId']);
                } else {
                    echo '<p class="error">Une erreur est survenue  : ID produit absent</p>' . "\n";
                }
            } else {
                echo '<p class="error">Une erreur est survenue (numéro de série absent)</p>' . "\n";
            }
            break;

        case 'loadRepairForm':
            if (isset($_GET['serial'])) {
                if (isset($_GET['requestType'])) {
                    $datas = new gsxDatas($_GET['serial'], $userId, $password, $serviceAccountNo);
                    echo $datas->getRequestFormHtml($_GET['requestType']);
                } else {
                    echo '<p class="error">Une erreur est survenue (Type de requête absent)</p>';
                }
            } else {
                echo '<p class="error">Une erreur est survenue (numéro de série absent)</p>' . "\n";
            }
            break;

        case 'loadParts':
            if (isset($_GET['serial']) && $_GET['serial']) {
                if (isset($_GET['prodId'])) {
                    $datas = new gsxDatas($_GET['serial'], $userId, $password, $serviceAccountNo);
                    echo $datas->getPartsListHtml($_GET['prodId']);
                } else {
                    echo '<p class="error">Impossible d\'obtenir la liste des composants : ID produit absent</p>' . "\n";
                }
            } else {
                echo '<p class="error">Impossible d\'obtenir la liste des composants : numéro de série invalide</p>' . "\n";
            }
            break;

        case 'loadCompTIACodes':
            $datas = new gsxDatas($_GET['serial'], $userId, $password, $serviceAccountNo);
            $results = $datas->getCompTIACodesArray();
            if ($results === 'fail')
                die('fail');

            $eval = '';
            foreach ($results['grps'] as $gpe => $codes) {
                foreach ($codes as $code => $desc) {
                    $eval .= 'CTIA.addCode(\'' . $gpe . '\', \'' . $code . '\', \'' . addslashes($desc) . '\')' . "\n";
                }
            }
            foreach ($results['mods'] as $mod => $desc) {
                $eval .= 'CTIA.addModifier(\'' . $mod . '\', \'' . addslashes($desc) . '\')' . "\n";
            }
            echo $eval;
            break;

        case 'savePartsCart':
            if (isset($_POST['serial'])) {
                $parts = fetchPartsList();
                if (count($parts))
                    echo '<p class="confirmation">Le panier a été correctement enregistré (' . count($parts) . ' produit(s))</p>';
                else {
                    echo '<p class="error">Une erreur est survenue: aucun produit dans le panier</p>';
                }
            } else {
                echo '<p class="error">Une erreur est survenue: numéro de série absent.</p>';
            }
            break;

        case 'sendPartsOrder':
            if (isset($_POST['serial'])) {
                $parts = fetchPartsList();
            }
            break;

        case 'sendGSXRequest':
            llxHeader();
            echo '<link type="text/css" rel="stylesheet" href="appleGSX.css"/>'."\n";
            echo '<script type="text/javascript" src="./appleGsxScripts.js"></script>'."\n";
            if (isset($_GET['request'])) {
                $GSXRequest = new GSX_Request($_GET['request']);
                echo $GSXRequest->processRequestForm();
            } else {
                echo '<p class="error">Une erreur est survenue: type de requête absent.</p>';
            }
            break;
    }
    die('');
}
?>
