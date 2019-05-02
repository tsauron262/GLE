<?php

require_once DOL_DOCUMENT_ROOT . '/synopsisapple/reservations/curlRequest.class.php';
require_once DOL_DOCUMENT_ROOT . '/synopsisapple/certifs.php';
require_once DOL_DOCUMENT_ROOT . '/synopsischrono/class/chrono.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/client.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT . '/synopsistools/SynDiversFunction.php';

class Reservations
{

    // Active tous les echos et désactive les logs: 
    var $display_debug = false;
    // mettre à false pour envoyer les mails aux bons destinataires:
    var $debugMails = false;
    public $createProductAndChrono = false;
    
    private $currentReservations = array();
    
    var $nbNew = 0;

    function __construct($db)
    {
        $this->db = $db;
    }

    public function get_reservations()
    {
        global $user, $productCodes;


        $user = new User($this->db);
        $user->fetch(1);
        $user->getrights();

        $productCodes = array(
            'IPOD', 'IPAD', 'IPHONE', 'WATCH', 'APPLETV', 'MAC', 'BEATS'
        );

        $this->getCurrentReservations();

        $sql = 'SELECT DISTINCT(CAST(`apple_shipto` AS UNSIGNED)) as shipTo, `apple_service` as soldTo FROM ' . MAIN_DB_PREFIX . 'user_extrafields';
        $sql .= ' WHERE `apple_shipto` IS NOT NULL AND `apple_service` IS NOT NULL';

        $result = $this->db->query($sql);
        $numbers = array();

        if ($result) {
            if ($this->db->num_rows($result)) {
                while ($obj = $this->db->fetch_object($result)) {
                    $numbers[] = array(
                        'shipTo' => (int) $obj->shipTo,
                        'soldTo' => (int) $obj->soldTo
                    );
                }
            }

            if ($this->display_debug)
                echo 'Chargement de la liste des shipto OK<br/>';
        } else {
            $this->logError('Echec du chargement des couples shipTo / soldTo - ' . $this->db->lasterror(),3);
        }

        foreach ($numbers as $n) {
            if (!empty($n['soldTo']) && !empty($n['shipTo'])) {
                $date = new DateTime();
                foreach(array(1,2,3) as $inut){
                    $dateBegin = $date->format('Y-m-d');
                    $date->add(new DateInterval('P2D'));
                    $dateEnd = $date->format('Y-m-d');
                    if(!$this->fetchReservationSummary($n['soldTo'], $n['shipTo'], $dateBegin, $dateEnd))
                            break;
                    $date->add(new DateInterval('P2D'));
                }
            }
        }
        
        $this->output .= ($this->nbNew>0)? $this->nbNew." nouvelle réservations" : "Pas de nouvelle reservations";

        return "OK";
    }

    function logError($error)
    {

        if (is_string($error)) {
            if ($this->display_debug) {
                echo $error . '<br/>';
            } else {
                dol_syslog("Réservations Apple, erreur: " . $error, LOG_ERR);
            }
        } else if (is_array($error)) {
            foreach ($error as $e) {
                if ($this->display_debug) {
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
            $this->logError($gsx->errors['init']);
        }
        if (count($gsx->errors['soap'])) {
            $this->logError($gsx->errors['soap']);
        }
    }

    function getCurrentReservations()
    {

        $date = new DateTime();
        $date->sub(new DateInterval('P2D'));
        $sql = 'SELECT ace.resgsx as resId FROM ' . MAIN_DB_PREFIX . 'actioncomm_extrafields ace ';
        $sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'actioncomm ac ON ace.fk_object = ac.id ';
        $sql .= 'WHERE ace.resgsx IS NOT NULL AND ac.datep > \'' . $date->format('Y-m-d H:i:s') . '\'';

        $result = $this->db->query($sql);
        $reservations = array();

        if ($result) {
            if ($this->db->num_rows($result)) {
                while ($obj = $this->db->fetch_object($result)) {
                    $reservations[] = $obj->resId;
                }
            }
        }
        $this->currentReservations = $reservations;
    }

    function getUsersByShipTo($shipTo)
    {
        $users = array();

        $sql = 'SELECT u.`rowid` as id, u.email, ue.apple_techid as techid, ue.apple_centre as centre_sav FROM ' . MAIN_DB_PREFIX . 'user u';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'user_extrafields ue ON u.rowid = ue.fk_object';
        $sql .= ' WHERE ue.apple_shipto = ' . $shipTo . ' AND ue.apple_techid IS NOT NULL AND u.statut = 1 AND ue.gsxresa = 1';

        $result = $this->db->query($sql);
        if ($result) {
            if ($this->db->num_rows($result)) {
                $centre_sav = '';
                while ($obj = $this->db->fetch_object($result)) {
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
            if ($this->display_debug) {
                echo 'Echec de la récupération de la liste des users pour le shipTo ' . $shipTo . '<br/>';
                echo $this->db->lasterror() . '<br/>';
            }
            return false;
        }
        return $users;
    }

    function getOrCreateCustomer($customer)
    {
        if (!isset($customer->emailId) || empty($customer->emailId))
            return false;

        global $user;
        $sql = 'SELECT `rowid` as id FROM ' . MAIN_DB_PREFIX . 'societe ';
        $sql .= 'WHERE `client` IN (1,2,3) AND `email` = \'' . $customer->emailId . '\'';

        $result = $this->db->query($sql);
        if ($result) {
            if ($this->db->num_rows($result)) {
                $obj = $this->db->fetch_object($result);
                if ($obj->id) {
                    $client = new Client($this->db);
                    $client->fetch($obj->id);
                    return $client;
                }
            }
        } else {
            echo $this->db->lasterror();
        }

        if ($this->display_debug) {
            echo 'Pas de client - Création<br/>';
        }

        $client = new Client($this->db);
        $client->email = $customer->emailId;
        $client->client = 1;
        $client->code_client = -1;
        $client->status = 1;
        $client->tva_assuj = 1;
        $client->country_id = 1;

        if (isset($customer->firstName) && !empty($customer->firstName))
            $client->name = ((isset($customer->lastName) && !empty($customer->lastName)) ? $customer->lastName.' ' : '').$customer->firstName;
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
            $this->logError('Echec création client pour e-mail: "' . $customer->emailId . '" (Retour Client::create(): ' . $res . ')');
            return false;
        }
        return $client;
    }

    function getProductBySerial($serial)
    {
        $sql = 'SELECT `id` FROM ' . MAIN_DB_PREFIX . 'synopsischrono_chrono_101 ';
        $sql .= 'WHERE `N__Serie` = \'' . $serial . '\'';

        $result = $this->db->query($sql);
        if ($result) {
            if ($this->db->num_rows($result)) {
                $obj = $this->db->fetch_object($result);

                return $obj->id;
            }
        }

        return 0;
    }

    function createProduct($serial, $client_id)
    {
        global $gsx;

        if (!isset($gsx))
            $this->initGsx();

        $chronoProd = new Chrono($this->db);
        $chronoProd->model_refid = 101;
        $chronoProd->socid = $client_id;
        $prod_id = $chronoProd->create();

        if ($prod_id <= 0) {
            $this->logError('Echec de la création du chrono_101 pour le numéro de série: ' . $serial);
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
                $this->logError($gsx->errors['soap']);
                $gsx->resetSoapErrors();
            }
        }

        if (!$gsxCheck) {
            $this->logError('Les informations du produit (chrono: ' . $chronoProd->id . ', serial: ' . $serial . ') , n\'ont pas pu être récupéré depuis GSX.');
        }

        $chronoProd->setDatas($prod_id, $dataArrProd);
        return $prod_id;
    }

    function processReservation($resa, $users)
    {
        if (!count($users))
            return;

        $customer = false;
        global $user;

        if ($this->display_debug) {
            echo 'Traitement de la réservation : "' . $resa->reservationId . '"<br/>';
            echo 'Données reçues: <pre>';
            print_r($resa);
            echo '</pre>';
        }

        if (isset($resa->customer) && !empty($resa->customer)) {
            $customer = $this->getOrCreateCustomer($resa->customer);
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
        if ($this->createProductAndChrono) {
            if (isset($resa->product->serialNumber) && !empty($resa->product->serialNumber)) {
                $id_product = $this->getProductBySerial($resa->product->serialNumber);
                if (!$id_product) {
                    $id_product = $this->createProduct($resa->product->serialNumber, (isset($customer->id) ? $customer->id : 0));
                }
            } else {
                $id_product = 0;
            }

            $chrono = new Chrono($this->db);
            $chrono->model_refid = 105;
            if (isset($customer->id))
                $chrono->socid = $customer->id;
            $chrono_id = $chrono->create();

            if ($chrono_id <= 0) {
                $this->logError('Echec de la création du chrono_105 (ID réservation: ' . $resa->reservationId . ')');
                $chrono_id = 0;
            }

            if ($chrono_id) {

                if ($this->display_debug)
                    echo 'CENTRE: ' . $centre . '<br/>';

                $chrono->setDatas($chrono_id, array(
                    1060 => $centre,
                    1047 => (isset($resa->product->issueReported) ? $resa->product->issueReported : '')
                ));

                $chrono->fetch($chrono_id);
//        $chrono->ref = str_replace("{CENTRE}", $centre, $chrono->ref);
//        $chrono->update($chrono_id);
            }
        } else {
            $chrono = 0;
            $id_product = 0;
        }

        // Liaison SAV / produit: 
        if ($id_product && $chrono_id) {
            $lien = new lien($this->db);
            $lien->cssClassM = "type:SAV";
            $lien->fetch(3);
            $lien->setValue($chrono_id, array($id_product));
        }

        // Ajout agenda:
        $ac = new ActionComm($this->db);
        $ac->type_id = 52;
        $ac->label = 'Réservation GSX';
        $ac->transparency = 1;

        $dateBegin = new DateTime($resa->reservationDate, new DateTimeZone("GMT"));
        $dateEnd = new DateTime($resa->reservationDate, new DateTimeZone("GMT"));
        $dateEnd->add(new DateInterval('PT2H'));
//        $dateBegin->setTimezone(new DateTimeZone("Europe/Paris"));
//        $dateEnd->setTimezone(new DateTimeZone("Europe/Paris"));

        $ac->datep = $dateBegin->format('Y-m-d H:i:s');
        $ac->datef = $dateEnd->format('Y-m-d H:i:s');

        $usersAssigned = array();
        foreach ($users as $u)
            $usersAssigned[] = array('id' => $u['id'], 'transparency' => 1, 'answer_status' => 1);
        $ac->userassigned = $usersAssigned;
        if(!isset($usersAssigned[0]))
            return false;
        $ac->userownerid = $usersAssigned[0]['id'];

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

        $ac->array_options['options_resgsx'] = $resa->reservationId;
//        $ac->insertExtraFields();
//        date_default_timezone_set("GMT");
        $fk_ac = $ac->create($user);
//        date_default_timezone_set("Europe/Paris");

        if (!$fk_ac) {
            $this->logError('Echec de la création du RDV pour la réservation "' . $resa->reservationId . '"');
            return;
        }


        $dateBegin->setTimezone(new DateTimeZone("Europe/Paris"));
        $dateEnd->setTimezone(new DateTimeZone("Europe/Paris"));

        // Envoi mails: 
        $subject = 'Nouvelle Reservation GSX le ' . $dateBegin->format('d/m/Y') . ' a ' . $dateBegin->format('H\Hi');
        $message = 'Bonjour,' . "\n\n";
        $message .= 'Une nouvelle réservation GSX a été ajouté à votre agenda:' . "\n\n";
        if ($chrono && $chrono_id) {
            $message .= "\t" . 'Accès au SAV : ' . $chrono->getNomUrl() . "\n";
        }
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
            $message .= "\t" . '' . $customer->getNomUrl(1) . "\n";
        }

        if (isset($chrono) && $chrono->id > 0) {
            $message .= "\t" . 'SAV : ' . $chrono->getNomUrl(1)  . "\n";
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
            if ($this->display_debug) {
                if ($this->debugMails)
                    $mails = $this->debugMails;
                echo 'Envoi du mail à "' . $mails . '". ';
            }
            if (!mailSyn2($subject, $mails, '', $message)) {
                $this->logError('Echec de l\'envoi du mail de notification (ID réservation: ' . $resa->reservationId . ')');
            } else if ($this->display_debug) {
                echo '[OK].<br/>';
            }
            $messageClient = "Bonjour,
Merci d’avoir pris rendez-vous dans notre Centre de Services Agrée Apple, nous vous confirmons la prise en compte de votre réservation.
Afin de préparer au mieux votre prise en charge, nous souhaitons attirer votre attention sur les points suivants :
- Vous devez sauvegarder vos données car nous serons peut-être amenés à les effacer de votre appareil.

- Le délai de traitement des réparations est habituellement de 7 jours.


Conditions particulières aux iPhones

- Vous devez désactiver la fonction « localiser mon iPhone » dans le menu iCloud de votre téléphone avec votre mot de passe iCloud.

- Pour certains types de pannes sous garantie, un envoi de l’iPhone dans un centre Apple peut être nécessaire, entrainant un délai plus long (jusqu’à 10 jours ouvrés), dans ce cas un téléphone de prêt est possible (sous réserve de disponibilité). Si cela vous intéresse, merci de vous munir d’un chèque de caution.

- Les réparations d’écrans d’iPhones sont directement envoyées chez Apple pour calibrer correctement votre iPhone et nécessitent donc un délai moyen de 10 jours ouvrés.

Nous proposons des services de sauvegarde des données, de protection de votre téléphone… venez nous rencontrer pour découvrir tous les services que nous pouvons vous proposer.
Votre satisfaction est notre objectif, nous mettrons tout en œuvre pour vous satisfaire et réduire les délais d’immobilisation de votre produit Apple.
Bien cordialement
L’équipe BIMP";
            $mailsCli = $customer->email;
            if ($mailsCli && $mailsCli != "" && mailSyn2("RDV SAV BIMP", $mailsCli, '', str_replace("\n", "<br/>", $messageClient))) {
                if ($this->display_debug) {
                    echo '[OK].<br/>';
                }
            }
            else{
                $this->logError('Echec de l\'envoi du mail au client de notification (ID réservation: ' . $resa->reservationId . ')');
                mailSyn2("resa sans email", "tommy@bimp.fr", "admin@bimp.fr", "Resa sans mail client ".print_r($resa,1));
            }
            $this->nbNew++;
        }
        else{
            mailSyn2("resa sans email", "tommy@bimp.fr", "admin@bimp.fr", "Resa sans mail user ".print_r($resa,1));
        }
        if ($this->display_debug) {
            echo '[OK]<br/><br/>';
            exit;
        }
    }

    function fetchReservationSummary($soldTo, $shipTo, $du, $au)
    {
        global $tabCert, $productCodes;

        if ($this->display_debug) {
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

        if (empty($certif)) {
            $this->logError('Aucun certificat trouvé pour le soldTo "' . $soldTo . '"');
            return 0;
        }

        $RS = new CurlReservationSummary($soldTo, $shipTo, $pass, $certif);

        foreach ($productCodes as $productCode) {
            if ($this->display_debug) {
                echo 'Code ' . $productCode . ': <br/>';
            }
            $data = $RS->fetch($du, $au, $productCode);
            if ($data === false) {
                $this->logError('Echec de la récupération des réservations pour le code produit "' . $productCode . '" - ' . $RS->getLastError());
                return 0;
            } else {
                if (isset($data->faults) && count($data->faults)) {
                    $continue = false;
                    $break = false;
                    foreach ($data->faults as $fault) {
                        if (in_array($fault->code, array('SYS.RSV.023'))) {
                            if ($this->display_debug) {
                                echo 'Aucune réservation<br/>';
                            }
                            $continue = true; // Aucune réservation pour la période donnée et le type de produit. 
                            break;
                        } else if (in_array($fault->code, array('SYS.STR.002', 'SYS.STR.006', 'SYS.STR.005'))) {
                            if ($this->display_debug) {
                                echo '<span class="red">shipTo invalide<br/>' . $fault->message . ' (code: ' . $fault->code . ').</span>';
                            }
                            $break = true;
                            break;
                        } else {
                            $msg = 'Echec de la récupération des réservations soldTo '.$soldTo.' shipTo '.$shipTo.' - ' . $fault->message . ' (code: ' . $fault->code . ').';
                            $this->logError($msg);
                            $continue = true;
                        }
                    }
                    if ($continue) {
                        continue;
                    } else if ($break) {
                        break;
                    }
                } else if (isset($data->response->reservations) && count($data->response->reservations)) {
                    $users = $this->getUsersByShipTo($shipTo);
                    $CR = new CurlReservation($soldTo, $shipTo, $pass, $certif);
                    if ($this->display_debug) {
                        echo count($data->response->reservations) . ' réservation(s) à traiter.<br/>';
                    }
                    foreach ($data->response->reservations as $reservation) {
                        if (isset($reservation->reservationId)) {
                            if (in_array($reservation->reservationId, $this->currentReservations)) {
                                if ($this->display_debug)
                                    echo 'Réservation "' . $reservation->reservationId . '" déjà enregistrée.<br/>';
                                continue;
                            }
                            $r = $CR->fetch($reservation->reservationId);
                            if (isset($r->faults) && count($r->faults)) {
                                foreach ($r->faults as $r) {
                                    $msg = 'Echec de la récupération des données de la réservation "' . $reservation->reservationId . '" - ' . $fault->message . ' (code: ' . $fault->code . ').';
                                    $this->logError($msg);
                                }
                            } else {
                                if (isset($r->response) && !empty($r->response)) {
                                    $this->processReservation($r->response, $users);
                                    $this->currentReservations[] = $reservation->reservationId;
                                } else {
                                    $msg = 'Echec de la récupération des données de la réservation "' . $reservation->reservationId;
                                    $this->logError($msg);
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
                                $this->logError($txt);
                            }
                        }
                    }
                }
            }
        }
        return 1;
    }
}