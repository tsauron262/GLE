<?php
require_once '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/includes/nusoap/lib/nusoap.php';
require_once DOL_DOCUMENT_ROOT . '/synopsisapple/gsxDatas.class.php';
require_once DOL_DOCUMENT_ROOT . '/synopsisapple/partsCart.class.php';

//error_reporting(E_ALL);
////error_reporting(E_ERROR);
//ini_set('display_errors', 1);

$coefPrix = 1;

//$userId = 'Corinne@actitec.fr';
//$password = 'cocomart01';
//$serviceAccountNo = '0000100635';
//$userId = 'tysauron@gmail.com';
//$password = 'freeparty';
//$serviceAccountNo = '0000100520';
//ephesussav
//@Ephe2014#
//
//$userId = 's.trerieux@bimp.fr';
//$password = '30Amelie38';
//$serviceAccountNo = '0000100520';

function fetchPartsList() {
    $parts = array();
    $i = 1;
    while (true) {
        if (isset($_POST['part_' . $i . '_ref'])) {
            $parts[] = array(
                'partNumber' => $_POST['part_' . $i . '_ref'],
                'comptiaCode' => (isset($_POST['part_' . $i . '_comptiaCode']) ? $_POST['part_' . $i . '_comptiaCode'] : 0),
                'comptiaModifier' => (isset($_POST['part_' . $i . '_comptiaModifier']) ? $_POST['part_' . $i . '_comptiaModifier'] : 0),
                'qty' => (isset($_POST['part_' . $i . '_qty']) ? $_POST['part_' . $i . '_qty'] : 1),
                'componentCode' => (isset($_POST['part_' . $i . '_componentCode']) ? $_POST['part_' . $i . '_componentCode'] : ''),
                'partDescription' => (isset($_POST['part_' . $i . '_partDescription']) ? $_POST['part_' . $i . '_partDescription'] : 'Composant ' . $i),
                'stockPrice' => (isset($_POST['part_' . $i . '_stockPrice']) ? $_POST['part_' . $i . '_stockPrice'] : '')
            );
        } else
            break;
        $i++;
    }
    return $parts;
}

function isIphone($serial) {
    if (preg_match('/^[0-9]{15,16}$/', $serial))
            return true;
    return false;
}
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
//        case 'addCartToPropal':
//            if (isset($_GET['chronoId'])) {
//                require_once(DOL_DOCUMENT_ROOT . "/comm/propal/class/propal.class.php");
//                require_once(DOL_DOCUMENT_ROOT . "/synopsischrono/Chrono.class.php");
//                $chr = new Chrono($db);
//                $chr->fetch($_GET['chronoId']);
//                $propalId = $chr->propalid;
//                if (!$propalId > 0)
//                    $propalId = $chr->createPropal();
//
//                $propal = new Propal($db);
//                $propal->fetch($propalId);
//                $cards = new partsCart($db, null, $_GET['chronoId']);
//                $cards->loadCart();
//                foreach ($cards->partsCart as $part) {
//                    $prix = convertPrix($part['stockPrice'], $part['partNumber'], $part['partDescription']);
//                    $propal->addline($part['partNumber'] . " - " . $part['partDescription'], $prix, $part['qty'], $part['stockPrice']);
//                }
//                require_once(DOL_DOCUMENT_ROOT . "/core/modules/propale/modules_propale.php");
//                propale_pdf_create($db, $propal, null, $langs);
//                echo '<ok>Reload</ok>';
////                } else {
////                    echo '<p class="error">Une erreur est survenue  : Pas de Propal</p>' . "\n";
////                }
//            } else {
//                echo '<p class="error">Une erreur est survenue (chrono id absent)</p>' . "\n";
//            }
//            break;

        case 'loadProduct':
            if (isset($_GET['serial'])) {
                if (isset($_GET['prodId'])) {
                    $datas = new gsxDatas($_GET['serial'], $userId, $password, $serviceAccountNo);
                    if ($datas->connect)
                        echo $datas->getLookupHtml($_GET['prodId']);
                    else
                        echo $datas->getGSXErrorsHtml();
                } else {
                    echo '<p class="error">Une erreur est survenue  : ID produit absent</p>' . "\n";
                }
            } else {
                echo '<p class="error">Une erreur est survenue (numéro de série absent)</p>' . "\n";
            }
            break;

        case 'loadInfoProduct':
            if (isset($_GET['serial'])) {
                if (isset($_GET['prodId'])) {
                    $datas = new gsxDatas($_GET['serial'], $userId, $password, $serviceAccountNo);
                    if ($datas->connect)
                        echo $datas->getLookupHtml($_GET['prodId']);
                    else
                        echo $datas->getGSXErrorsHtml();
                } else {
                    echo '<p class="error">Une erreur est survenue  : ID produit absent</p>' . "\n";
                }
            } else {
                echo '<p class="error">Une erreur est survenue (numéro de série absent)</p>' . "\n";
            }
            break;

        case 'loadRepairForm':
            if (isset($_GET['serial'])) {
                if (isset($_GET['prodId'])) {
                    if (isset($_GET['requestType'])) {
                        $datas = new gsxDatas($_GET['serial'], $userId, $password, $serviceAccountNo);
                        echo $datas->getRequestFormHtml($_GET['requestType'], $_GET['prodId']);
                    } else {
                        echo '<p class="error">Une erreur est survenue (Type de requête absent)</p>';
                    }
                } else {
                    echo '<p class="error">Une erreur est survenue (prodId absent)</p>' . "\n";
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
            if (!isset($_POST['serial']))
                die('<p class="error">Une erreur est survenue: numéro de série absent.</p>');
            if (!isset($_GET['chronoId']))
                die('<p class="error">Une erreur est survenue (chrono id absent)</p>');

            $parts = fetchPartsList();
            if (!count($parts))
                die('<p class="error">Une erreur est survenue: aucun produit dans le panier</p>');

            global $db;
            $cart = new partsCart($db, $_POST['serial'], $_GET['chronoId']);
            $cart->setPartsCart($parts);
            if ($cart->saveCart()) {
                if (isset($_POST['addToPropal']) && ($_POST['addToPropal'] == 1)) {
                    require_once(DOL_DOCUMENT_ROOT . "/comm/propal/class/propal.class.php");
                    require_once(DOL_DOCUMENT_ROOT . "/synopsischrono/Chrono.class.php");
                    $chr = new Chrono($db);
                    $chr->fetch($_GET['chronoId']);
                    $propalId = $chr->propalid;
                    if (!$propalId > 0)
                        $propalId = $chr->createPropal();

                    $propal = new Propal($db);
                    $propal->fetch($propalId);
                    foreach ($cart->partsCart as $part) {
                        $prix = convertPrix($part['stockPrice'], $part['partNumber'], $part['partDescription']);
                        $propal->addline($part['partNumber'] . " - " . $part['partDescription'], $prix, $part['qty'], "20", 0, 0, 0, 0, 'HT', 0, 0, 0, 0, 0, 0, 0, $part['stockPrice']);
                    }
                    $propal->fetch($propalId);
                    require_once(DOL_DOCUMENT_ROOT . "/core/modules/propale/modules_propale.php");
                    propale_pdf_create($db, $propal, null, $langs);
                    echo 'ok<ok>Reload</ok>';
//                    } else {
//                        echo '<p class="error">Une erreur est survenue  : Pas de Propal</p>' . "\n";
//                    }
                } else {
                    echo '<p class="confirmation">Panier correctement enregistré (' . count($cart->partsCart) . ' produit(s))</p>';
                }
            } else {
                echo $cart->displayErrors();
            }
            break;

        case 'loadPartsCart':
            if (isset($_GET['serial']) && isset($_GET['prodId'])) {
                global $db;
                if (!isset($db))
                    die('noDb');
                $cart = new partsCart($db, $_GET['serial'], isset($_GET['chronoId']) ? $_GET['chronoId'] : null);
                $cart->loadCart();
                if (count($cart->partsCart)) {
                    echo $cart->getJsScript($_GET['prodId']);
                } else {
                    die('noPart');
                }
            } else {
                die('noSerial');
            }
            break;

        case 'sendGSXRequest':
            if (isset($_GET['serial'])) {
                if (isset($_GET['request'])) {
                    if (isset($_GET['prodId'])) {
                        $datas = new gsxDatas($_GET['serial'], $userId, $password, $serviceAccountNo);
                        echo $datas->processRequestForm($_GET['prodId'], $_GET['request']);
                    } else {
                        echo '<p class="error">Une erreur est survenue (prodId absent)</p>' . "\n";
                    }
                } else {
                    echo '<p class="error">Une erreur est survenue: type de requête absent.</p>';
                }
            } else {
                echo '<p class="error">Une erreur est survenue (numéro de série absent)</p>' . "\n";
            }
            break;

        case 'loadSmallInfoProduct':
            $GSXdatas = new gsxDatas($_REQUEST["serial"], $userId, $password, $serviceAccountNo);
            if ($GSXdatas->connect) {
                $response = $GSXdatas->gsx->lookup($_REQUEST["serial"], 'warranty');
                if (isset($response) && count($response)) {
                    if (isset($response['ResponseArray']) && count($response['ResponseArray'])) {
                        if (isset($response['ResponseArray']['responseData']) && count($response['ResponseArray']['responseData'])) {
                            $datas = $response['ResponseArray']['responseData'];
                            $machineinfo = $datas['productDescription'];
//                            $garantie = $datas['warrantyStatus'];
//                            $garantie = $datas['coverageEndDate'];
                            $garantie = dateAppleToDate($datas['coverageEndDate']);
                            $dateAchat = dateAppleToDate($datas['estimatedPurchaseDate']);
                            echo "tabResult = Array('" . $machineinfo . "', '" . $garantie . "', '" . $dateAchat . "');";
                        }
                    }
                }
            } else
                echo $GSXdatas->getGSXErrorsHtml();
            break;

        case 'importRepair':
            if (isset($_GET['chronoId'])) {
                $gsxDatas = new gsxDatas(isset($_GET['importNumber'])?$_GET['importNumber']:null,
                        $userId, $password, $serviceAccountNo);
                echo $gsxDatas->importRepair($_GET['chronoId']);
            } else {
                echo '<p class="error">Une erreur est survenue (Chrono Id absent)</p>' . "\n";
            }
            break;

        case 'closeRepair':
            if (isset($_GET['repairRowId'])) {
                $gsxDatas = new gsxDatas(null, $userId, $password, $serviceAccountNo);
                echo $gsxDatas->closeRepair($_GET['repairRowId']);
            } else {
                echo '<p class="error">Une erreur est survenue (ID réparation absent)</p>' . "\n";
            }
            break;
    }
    die('');
}

function dateAppleToDate($date) {
    $garantieT = explode("/", $date);
    if (isset($garantieT[2]))
        return $garantieT[0] . "/" . $garantieT[1] . "/20" . $garantieT[2];
    else
        return "";
}

function convertPrix($prix, $ref, $desc) {
    $coefPrix = 1;
    $constPrix = 0;
    $tabCas1 = array("DN661", "FD661", "NF661", "RA", "RB", "RC", "RD", "RE", "RG", "SA", "SB", "SC", "SD", "SE", "X661", "XB", "XC", "XD", "XE", "XF", "ZD661", "ZK661");
    $tabCas2 = array("SVC,IPOD", "Ipod nano");
    $tabCas3 = array("661");
    $tabCas4 = array("iphone", "BAT,IPHONE", "SVC,IPHONE");

    $cas = 0;
    foreach ($tabCas1 as $val)
        if (stripos($ref, $val) === 0)
            $cas = 1;
    foreach ($tabCas2 as $val)
        if (stripos($desc, $val) === 0)
            $cas = 1;
    foreach ($tabCas3 as $val)
        if (stripos($ref, $val) === 0)
            $cas = 2;
    foreach ($tabCas4 as $val)
        if (stripos($desc, $val) === 0)
            $cas = 2;

    if ($cas == 0) {
        if ($prix > 300)
            $coefPrix = 0.8;
        elseif ($prix > 150)
            $coefPrix = 0.7;
        elseif ($prix > 50)
            $coefPrix = 0.6;
        else {
            $coefPrix = 0.6;
            $constPrix = 10;
        }
    } elseif ($cas == 1) {
        $constPrix = 45;
    } elseif ($cas == 2) {
        $constPrix = 45;
    }
    $prix = (($prix + $constPrix) / $coefPrix);
    return $prix;
}

?>
