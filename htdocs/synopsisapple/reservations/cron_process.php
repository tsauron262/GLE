<?php

//define("NOLOGIN", 1);

require_once('../../main.inc.php');

global $db, $tabCert, $user, $gsx, $dateBegin, $dateEnd, $productCodes, $display_debug, $currentReservations, $debugMails;

// Active tous les echos et désactive les logs: 
$display_debug = true;

// mettre à false pour envoyer les mails aux bons destinataires:
$debugMails = false;

if ($display_debug)
    llxHeader();

ini_set('display_errors', 1);
error_reporting(E_ERROR);

require_once DOL_DOCUMENT_ROOT . '/synopsisapple/reservations/curlRequest.class.php';
require_once DOL_DOCUMENT_ROOT . '/synopsisapple/certifs.php';
require_once DOL_DOCUMENT_ROOT . '/synopsischrono/class/chrono.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/client.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';


$date = new DateTime();
$dateBegin = $date->format('Y-m-d');
$date->add(new DateInterval('P2D'));
$dateEnd = $date->format('Y-m-d');
unset($date);

$user = new User($db);
$user->fetch(1);
$user->getrights();

$productCodes = array(
    'IPOD', 'IPAD', 'IPHONE', 'WATCH', 'APPLETV', 'MAC', 'BEATS'
);

$currentReservations = getCurrentReservations();

function logError($error)
{
    global $display_debug;

    if (is_string($error)) {
        if ($display_debug) {
            echo $error . '<br/>';
        } else {
            dol_syslog("Réservations Apple, erreur: " . $error, LOG_ERR);
        }
    } else if (is_array($error)) {
        foreach ($error as $e) {
            if ($display_debug) {
                echo $e . '<br/>';
            } else {
                dol_syslog("Réservations Apple, erreur: " . $e, LOG_ERR);
            }
        }
    }
}

function initGsx()
{
    global $gsx;

    if (isset($gsx))
        return;

    $details = array(
        'apiMode' => 'production',
        'regionCode' => 'emea',
        'userId' => 'admin.gle@bimp.fr',
        'serviceAccountNo' => '897316',
        'languageCode' => 'fr',
        'userTimeZone' => 'CEST',
        'returnFormat' => 'php',
    );

    require_once DOL_DOCUMENT_ROOT . '/synopsisapple/GSX.class.php';

    $gsx = new GSX($details, false, 'production');
    if (count($gsx->errors['init'])) {
        logError($gsx->errors['init']);
    }
    if (count($gsx->errors['soap'])) {
        logError($gsx->errors['soap']);
    }
}

function getCurrentReservations()
{
    global $db;

    $date = new DateTime();
    $date->sub(new DateInterval('P2D'));
    $sql = 'SELECT ace.resgsx as resId FROM ' . MAIN_DB_PREFIX . 'actioncomm_extrafields ace ';
    $sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'actioncomm ac ON ace.fk_object = ac.id ';
    $sql .= 'WHERE ace.resgsx IS NOT NULL AND ac.datep > \'' . $date->format('Y-m-d H:i:s') . '\'';

    $result = $db->query($sql);
    $reservations = array();

    if ($result) {
        if ($db->num_rows($result)) {
            while ($obj = $db->fetch_object($result)) {
                $reservations[] = $obj->resId;
            }
        }
    }
    return $reservations;
}

function getUsersByShipTo($shipTo)
{
    global $db, $display_debug;
    $users = array();

    $sql = 'SELECT u.`rowid` as id, u.email, ue.apple_techid as techid, ue.apple_centre as centre_sav FROM ' . MAIN_DB_PREFIX . 'user u';
    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'user_extrafields ue ON u.rowid = ue.fk_object';
    $sql .= ' WHERE ue.apple_shipto = ' . $shipTo . ' AND ue.apple_techid IS NOT NULL';

    $result = $db->query($sql);
    if ($result) {
        if ($db->num_rows($result)) {
            $centre_sav = '';
            while ($obj = $db->fetch_object($result)) {
                if (!$centre_sav) {
                    if (!empty($obj->centre_sav)) {
                        $centres = explode(' ', $obj->centre_sav);
                        if (isset($centres[0]) && $centres[0] != "")
                            $centre_sav = $centres[0];
                        elseif (isset($centres[1]) && $centres[1] != "")
                            $centre_sav = $centres[1];
                    }
                }
                $users[] = array(
                    'id' => $obj->id,
                    'techid' => $obj->techid,
                    'email' => $obj->email,
                    'centre' => $centre_sav
                );
            }
        }
    } else {
        if ($display_debug) {
            echo 'Echec de la récupération de la liste des users pour le shipTo ' . $shipTo . '<br/>';
            echo $db->lasterror() . '<br/>';
        }
        return false;
    }
    return $users;
}

function getOrCreateCustomer($customer)
{
    if (!isset($customer->emailId) || empty($customer->emailId))
        return false;

    global $db, $user;
    $sql = 'SELECT `rowid` as id FROM ' . MAIN_DB_PREFIX . 'societe ';
    $sql .= 'WHERE `client` IN (1,2,3) AND `email` = \'' . $customer->emailId . '\'';

    $result = $db->query($sql);
    if ($result) {
        if ($db->num_rows($result)) {
            $obj = $db->fetch_object($result);
            if ($obj->id) {
                $client = new Client($db);
                $client->fetch($obj->id);
                return $client;
            }
        }
    } else {
        echo $db->lasterror();
    }

    global $display_debug;
    if ($display_debug) {
        echo 'Pas de client - Création<br/>';
    }

    $client = new Client($db);
    $client->email = $customer->emailId;
    $client->client = 1;
    $client->code_client = -1;
    $client->status = 1;

    if (isset($customer->firstName) && !empty($customer->firstName))
        $client->name = $customer->firstName . ((isset($customer->lastName) && !empty($customer->lastName)) ? ' ' . $customer->lastName : '');
    if (isset($customer->phoneNumber) && !empty($customer->phoneNumber))
        $client->phone = $customer->phoneNumber;
    if (isset($customer->addressLine1) && !empty($customer->addressLine1))
        $client->address = $customer->addressLine1 . ((isset($customer->addressLine2) && !empty($customer->addressLine2)) ? ' ' . $customer->addressLine2 : '');
    if (isset($customer->postalCode) && !empty($customer->postalCode))
        $client->zip = $customer->postalCode;
    if (isset($customer->city) && !empty($customer->city))
        $client->town = $customer->city;

    $res = $client->create($user);
    if ($res < 0) {
        logError('Echec création client pour e-mail: "$customer->emailId" (Retour Client::create(): ' . $res . ')');
        return false;
    }
    return $client;
}

function getProductBySerial($serial)
{
    global $db;

    $sql = 'SELECT `id` FROM ' . MAIN_DB_PREFIX . 'synopsischrono_chrono_101 ';
    $sql .= 'WHERE `N__Serie` = \'' . $serial . '\'';

    $result = $db->query($sql);
    if ($result) {
        if ($db->num_rows($result)) {
            $obj = $db->fetch_object($result);

            return $obj->id;
        }
    }

    return 0;
}

function createProduct($serial, $client_id)
{
    global $db, $gsx;

    if (!isset($gsx))
        initGsx();

    $chronoProd = new Chrono($db);
    $chronoProd->model_refid = 101;
    $chronoProd->socid = $client_id;
    $prod_id = $chronoProd->create();

    if ($prod_id <= 0) {
        logError('Echec de la création du chrono_101 pour le numéro de série: ' . $serial);
        return 0;
    }

    $dataArrProd = array(
        1011 => $serial
    );
    $gsxCheck = false;

    if (!count($gsx->errors['init']) && !count($gsx->errors['soap'])) {
        if (preg_match('/^[0-9]{15,16}$/', $serial)) {
            $gsx->isIphone = true;
        } else {
            $gsx->isIphone = false;
        }

        $response = $gsx->lookup($serial, 'warranty');
        if (isset($response['ResponseArray']['responseData']) && count($response['ResponseArray']['responseData'])) {
            $datas = $response['ResponseArray']['responseData'];

            $gsxCheck = true;

            $chronoProd->description = $datas['productDescription'];
            $chronoProd->update($prod_id);

            $dataArrProd[1014] = $datas['estimatedPurchaseDate'];
            $dataArrProd[1015] = $datas['coverageEndDate'];
            $dataArrProd[1064] = $datas['warrantyStatus'];
        } else if (count($gsx->errors['soap'])) {
            logError($gsx->errors['soap']);
            $gsx->resetSoapErrors();
        }
    }

    if (!$gsxCheck) {
        logError('Les informations du produit (chrono: ' . $chronoProd->id . ', serial: ' . $serial . ') , n\'ont pas pu être récupéré depuis GSX.');
    }

    $chronoProd->setDatas($prod_id, $dataArrProd);
    return $prod_id;
}

function processReservation($resa, $users)
{
    if (!count($users))
        return;

    $customer = null;
    global $db, $user, $display_debug;

    if ($display_debug) {
        echo 'Traitement de la réservation : "' . $resa->reservationId . '"<br/>';
        echo 'Données reçues: <pre>';
        print_r($resa);
        echo '</pre>';
    }

    if (isset($resa->customer) && !empty($resa->customer)) {
        $customer = getOrCreateCustomer($resa->customer);
    }

    if (isset($resa->product->serialNumber) && !empty($resa->product->serialNumber)) {
        $id_product = getProductBySerial($resa->product->serialNumber);
        if (!$id_product) {
            $id_product = createProduct($resa->product->serialNumber, (isset($customer->id) ? $customer->id : 0));
        }
    } else {
        $id_product = 0;
    }
    
    //Selection du centre
    $centre = '';
    foreach ($users as $u) {
        if (!empty($u['centre'])) {
            $centre = $u['centre'];
            break;
        }
    }
    $_REQUEST['centre'] = $centre;

    // Création SAV:
    $chrono = new Chrono($db);
    $chrono->model_refid = 105;
    if (isset($customer->id))
        $chrono->socid = $customer->id;
    $chrono_id = $chrono->create();

    if ($chrono_id <= 0) {
        logError('Echec de la création du chrono_105 (ID réservation: ' . $resa->reservationId . ')');
        $chrono_id = 0;
    }

    if ($chrono_id) {

        if ($display_debug)
            echo 'CENTRE: ' . $centre . '<br/>';

        $chrono->setDatas($chrono_id, array(
            1060 => $centre,
            1047 => (isset($resa->product->issueReported) ? $resa->product->issueReported : '')
        ));

        $chrono->fetch($chrono_id);
//        $chrono->ref = str_replace("{CENTRE}", $centre, $chrono->ref);
//        $chrono->update($chrono_id);
    }


    // Liaison SAV / produit: 
    if ($id_product && $chrono_id) {
        $lien = new lien($db);
        $lien->cssClassM = "type:SAV";
        $lien->fetch(3);
        $lien->setValue($chrono_id, array($id_product));
    }

    // Ajout agenda:
    $ac = new ActionComm($db);
    $ac->type_id = 52;
    $ac->label = 'Réservation GSX';
    $ac->transparency = 1;

    $dateBegin = new DateTime($resa->reservationDate);
    $dateEnd = new DateTime($resa->reservationDate);
    $dateEnd->add(new DateInterval('PT1H'));
    $ac->datep = $dateBegin->format('Y-m-d H:i:s') . '<br/>';
    $ac->datef = $dateEnd->format('Y-m-d H:i:s');

    $usersAssigned = array();
    foreach ($users as $u)
        $usersAssigned[] = array('id' => $u['id'], 'transparency' => 1);
    $ac->userassigned = $usersAssigned;

    $note_text = '';
    if (count($resa->notes)) {
        $first = true;
        foreach ($resa->notes as $note) {
            if (!$first) {
                $note_text .= ' - ';
            } else {
                $first = false;
            }
            $note_text .= $note->text;
        }
        $ac->note = $note_text;
    }

    if ($customer !== false) {
        $ac->socid = $customer->id;
    }

    $fk_ac = $ac->add($user);
    if (!$fk_ac) {
        logError('Echec de la création du RDV pour la réservation "' . $resa->reservationId . '"');
        return;
    }

    $ac->array_options['options_resgsx'] = $resa->reservationId;
    $ac->insertExtraFields();

    // Envoi mails: 
    $subject = 'Nouvelle Reservation GSX le ' . $dateBegin->format('d/m/Y') . ' a ' . $dateBegin->format('H\Hi');
    $message = 'Bonjour,' . "\n\n";
    $message .= 'Une nouvelle réservation GSX a été ajouté à votre agenda:' . "\n\n";
    $message .= "\t" . 'Accès au SAV : ' . $chrono->getNomUrl() . "\n";
    $message .= "\t" . 'ID réservation: ' . $resa->reservationId . "\n";
    $message .= "\t" . 'Date: ' . $dateBegin->format('d/m/Y') . ' à ' . $dateBegin->format('H\Hi') . ".\n";
    $message .= "\t" . 'Type de produit: ' . $resa->product->productCode . ".\n";
    $message .= "\t" . 'Numéro de série du produit: ';

    if (isset($resa->product->serialNumber) && !empty($resa->product->serialNumber))
        $message .= $resa->product->serialNumber . ".\n";
    else
        $message .= ' [Non communiqué]' . "\n";

    if (isset($customer)) {
        $message .= "\t" . 'Client : ' . $customer->name . ' (' . $customer->email . ').' . "\n";
    }

    if (count($resa->notes)) {
        $message .= "\n";
        $message .= "\t" . 'Notes:' . "\n";
        foreach ($resa->notes as $note) {
            $message .= "\t\t - " . $note->text . ".\n";
        }
    }

    $mails = '';
    $first = true;
    foreach ($users as $u) {
        if (isset($u['email']) && !empty($u['email'])) {
            if (!$first)
                $mails .= ',';
            else
                $first = false;
            $mails .= $u['email'];
        }
    }

    if ($mails) {
        if ($display_debug) {
            echo 'Envoi du mail à "' . $mails . '". ';
            global $debugMails;
            if ($debugMails)
                $mails = $debugMails;
        }
        if (!mailSyn2($subject, $mails, '', $message)) {
            logError('Echec de l\'envoi du mail de notification (ID réservation: ' . $resa->reservationId . ')');
        } else if ($display_debug) {
            echo '[OK].<br/>';
        }
    }
    if ($display_debug) {
        echo '[OK]<br/><br/>';
//        exit;
    }
}

function fetchReservationSummary($soldTo, $shipTo)
{
    global $display_debug, $tabCert, $dateBegin, $dateEnd, $productCodes, $currentReservations;

    if ($display_debug) {
        echo '<br/><br/>FetchReservationSummary pour shipTo: ' . $shipTo . ' (soldTo: ' . $soldTo . '): <br/>';
    }

    $pass = '';
    $certif = '';

    if (array_key_exists($soldTo, $tabCert)) {
        if (isset($tabCert[$soldTo][1][0])) {
            $certif = $tabCert[$soldTo][1][0];
        }
        if (isset($tabCert[$soldTo][1][1])) {
            $pass = $tabCert[$soldTo][1][1];
        }
    }

    if (empty($pass) || empty($certif)) {
        logError('Aucun certificat trouvé pour le soldTo "' . $soldTo . '"');
        return;
    }

    $RS = new CurlReservationSummary($soldTo, $shipTo, $pass, $certif);

    foreach ($productCodes as $productCode) {
        if ($display_debug) {
            echo 'Code ' . $productCode . ': <br/>';
        }
        $data = $RS->fetch($dateBegin, $dateEnd, $productCode);
        if ($data === false) {
            logError('Echec de la récupération des réservations pour le code produit "' . $productCode . '" - ' . $RS->getLastError());
        } else {
            if (isset($data->faults) && count($data->faults)) {
                $continue = false;
                $break = false;
                foreach ($data->faults as $fault) {
                    if (in_array($fault->code, array('SYS.RSV.023'))) {
                        if ($display_debug) {
                            echo 'Aucune réservation<br/>';
                        }
                        $continue = true; // Aucune réservation pour la période donnée et le type de produit. 
                        break;
                    } else if (in_array($fault->code, array('SYS.STR.002', 'SYS.STR.006'))) {
                        if ($display_debug) {
                            echo '<red>shipTo invalide<br/>' . $fault->message . ' (code: ' . $fault->code . ').</red>';
                        }
                        $break = true;
                        break;
                    } else {
                        $msg = 'Echec de la récupération des réservations - ' . $fault->message . ' (code: ' . $fault->code . ').';
                        logError($msg);
                        $continue = true;
                    }
                }
                if ($continue) {
                    continue;
                } else if ($break) {
                    break;
                }
            } else if (isset($data->response->reservations) && count($data->response->reservations)) {
                $users = getUsersByShipTo($shipTo);
                $CR = new CurlReservation($soldTo, $shipTo, $pass, $certif);
                if ($display_debug) {
                    echo count($data->response->reservations) . ' réservation(s) à traiter.<br/>';
                }
                foreach ($data->response->reservations as $reservation) {
                    if (isset($reservation->reservationId)) {
                        if (in_array($reservation->reservationId, $currentReservations)) {
                            echo 'Réservation "' . $reservation->reservationId . '" déjà enregistrée.<br/>';
                            continue;
                        }
                        $r = $CR->fetch($reservation->reservationId);
                        if (isset($r->faults) && count($r->faults)) {
                            foreach ($r->faults as $r) {
                                $msg = 'Echec de la récupération des données de la réservation "' . $reservation->reservationId . '" - ' . $fault->message . ' (code: ' . $fault->code . ').';
                                logError($msg);
                            }
                        } else {
                            if (isset($r->response) && !empty($r->response)) {
                                processReservation($r->response, $users);
                            } else {
                                $msg = 'Echec de la récupération des données de la réservation "' . $reservation->reservationId;
                                logError($msg);
                            }
                        }
                    }
                }
                unset($CR);
            } else {
                if (is_array($data) && count($data)) {
                    foreach ($data as $d) {
                        if (isset($d->message) && !empty($d->message)) {
                            $txt = 'Echec de la récupération réservations (shipTo: "' . $shipTo . '", code produit: "' . $productCode . '") - ';
                            $txt .= $d->message;
                            if (isset($d->code) && !empty($d->code)) {
                                $txt .= ' (Code: ' . $d->code . ').';
                            }
                            logError($txt);
                        }
                    }
                }
            }
        }
    }
}
$sql = 'SELECT DISTINCT(CAST(`apple_shipto` AS UNSIGNED)) as shipTo, `apple_service` as soldTo FROM ' . MAIN_DB_PREFIX . 'user_extrafields';
$sql .= ' WHERE `apple_shipto` IS NOT NULL AND `apple_service` IS NOT NULL';

$result = $db->query($sql);
$numbers = array();

if ($result) {
    if ($db->num_rows($result)) {
        while ($obj = $db->fetch_object($result)) {
            $numbers[] = array(
                'shipTo' => (int) $obj->shipTo,
                'soldTo' => (int) $obj->soldTo
            );
        }
    }
} else {
    logError('Echec du chargement des couples shipTo / soldTo - ' . $db->lasterror());
}

foreach ($numbers as $n) {
    if (!empty($n['soldTo']) && !empty($n['shipTo'])) {
        fetchReservationSummary($n['soldTo'], $n['shipTo']);
    }
}


if ($display_debug)
    llxFooter();