<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_Reservation.php';
require_once DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDSImportProcess.php';
BimpCore::requireFileForEntity('bimpsupport', 'centre.inc.php');

class BDS_ImportsGSXReservationsProcess extends BDSImportProcess
{

    public static $default_public_title = 'Imports Réservations GSX';
    public static $products_codes = array('IPOD', 'IPAD', 'IPHONE', 'WATCH', 'APPLETV', 'MAC', 'BEATS');
    protected $current_reservations = null;
    protected $apple_ids = null;

    public function __construct(\BDS_Process $process, $options = [], $references = [])
    {
        parent::__construct($process, $options, $references);
        require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_v2.php';
    }

    // Init opérations:

    public function initTest(&$data, &$errors = array())
    {
        $dates = array();

        if (isset($this->options['date_from']) && $this->options['date_from'] && isset($this->options['date_to']) && $this->options['date_to']) {
            $dates[] = array(
                'from' => $this->options['date_from'],
                'to'   => $this->options['date_to']
            );
        } else {
            $dt = new DateTime();
            $interval = new DateInterval('P1D');

            for ($i = 0; $i < 3; $i++) {
                $date = array();
                $date['from'] = $dt->format('Y-m-d');
                $dt->add($interval);
                $date['to'] = $dt->format('Y-m-d');
                $dates[] = $date;
                if ($i < 2) {
                    $dt->add($interval);
                }
            }
        }

        $soldTo = BimpTools::getArrayValueFromPath($this->params, 'sold_to', '');

        if (!$soldTo) {
            $errors[] = 'N° soldTo absent des paramètres';
        }

        $shipTos = $this->getAppleIdentifiers($errors);

        if (empty($shipTos)) {
            $errors[] = 'Aucun n° shipTo trouvé';
        }

        if (!count($errors)) {
            $data['result_html'] = '';

            foreach ($dates as $date) {
                $data['result_html'] .= '<h2>Du ' . date('d / m / Y', strtotime($date['from'])) . ' au ' . date('d / m / Y', strtotime($date['to'])) . '</h2>';

                foreach ($shipTos as $shipTo) {
                    $html = '';

                    $fetch_errors = array();
                    $reservations = GSX_Reservation::fetchReservationsSummay($soldTo, $shipTo, '', $date['from'], $date['to'], $fetch_errors, $this->debug_content);
                    $open = false;
                    $class = 'success';

                    if (count($fetch_errors)) {
                        $html .= BimpRender::renderAlerts(BimpTools::getMsgFromArray($fetch_errors, 'Echec récupération des réservations'));
                        $gsx = GSX_Reservation::getGsxV2();

                        if (!$gsx->logged) {
                            $data['result_html'] .= $html;
                            break 2;
                        }
                    } elseif (count($reservations)) {
                        $html .= '<pre>';
                        $html .= print_r($reservations, 1);
                        $html .= '</pre>';
                    } else {
                        $html .= BimpRender::renderAlerts('Aucune réservation', 'warning');
                        $class = 'warning';
                    }

                    $html .= '<br/>';

                    $title = '<span class="' . $class . '">SoldTo ' . $soldTo . ' - ShipTo ' . $shipTo . '</span>';
                    $data['result_html'] .= BimpRender::renderFoldableContainer($title, $html, array(
                                'open' => $open
                    ));
                }
            }
        }
    }

    public function initProcessReservations(&$data, &$errors = array())
    {
        $dates = array();
        if (isset($this->options['date_from']) && $this->options['date_from'] && isset($this->options['date_to']) && $this->options['date_to']) {
            $dates[] = array(
                'from' => $this->options['date_from'],
                'to'   => $this->options['date_to']
            );
        } else {
            $dt = new DateTime();
            $interval = new DateInterval('P1D');

            for ($i = 0; $i < 3; $i++) {
                $date = array();
                $date['from'] = $dt->format('Y-m-d');
                $dt->add($interval);
                $date['to'] = $dt->format('Y-m-d');
                $dates[] = $date;
                if ($i < 2) {
                    $dt->add($interval);
                }
            }
        }

        $soldTo = BimpTools::getArrayValueFromPath($this->params, 'sold_to', '');
        if (!$soldTo) {
            $errors[] = 'N° soldTo absent des paramètres';
        }

        $shipTos = $this->getAppleIdentifiers($errors);

        if (empty($shipTos)) {
            $errors[] = 'Aucun n° shipTo trouvé';
        }

        foreach ($dates as $date) {
            if ($date['from'] > $date['to']) {
                $errors[] = 'Dates invalides: du ' . date('d/ m / Y', strtotime($date['from'])) . ' au ' . $date('d/ m / Y', strtotime($date['to']));
            }
        }

        if (!count($errors)) {
            $data['steps'] = array();

            foreach ($dates as $date) {
                $data['steps']['process_from_' . $date['from'] . '_to_' . $date['to']] = array(
                    'label'    => 'Récupération et traitement des réservations du ' . date('d / m / Y', strtotime($date['from'])) . ' au ' . date('d / m / Y', strtotime($date['to'])),
                    'on_error' => 'continue'
                );
            }
        }
    }

    public function initGetReservationsTypes(&$data, &$errors = array())
    {
        $data['result_html'] = '';

        $gsx = GSX_Reservation::getGsxV2();

        if (!$gsx->logged) {
            $errors[] = 'Non connecté à GSX';
        } else {
            $result = $gsx->attributeLookup('RESERVATION_TYPE');

            $errors = array_merge($errors, $gsx->getErrors());

            if (is_array($result)) {
                $data['result_html'] .= '<pre>' . print_r($result, 1) . '</pre>';
            }
        }
    }

    // Process opérations:

    public function executeProcessReservations($step_name, &$errors = array(), $extra_data = array())
    {
        $result = array();

        if (preg_match('/^process_from_(.+)_to_(.+)$/', $step_name, $matches)) {
            $from = $matches[1];
            $to = $matches[2];

            $soldTo = BimpTools::getArrayValueFromPath($this->params, 'sold_to', '');
            if (!$soldTo) {
                $errors[] = 'N° soldTo absent des paramètres';
            }

            $shipTos = $this->getAppleIdentifiers($errors);

            if (empty($shipTos)) {
                $errors[] = 'Aucun n° shipTo trouvé';
            }

            if ($from > $to) {
                $errors[] = 'Dates invalides';
            }

            if (!count($errors)) {
                $this->processReservations($soldTo, $shipTos, $from, $to);
            }
        } else {
            $errors[] = 'Etape invalide: "' . $step_name . '"';
        }

        return $result;
    }

    // Traitements:

    public function processReservations($soldTo, $shipTos, $from, $to)
    {
        global $user, $langs;
        $this->Info('Process reservations. User : ' . $user->getFullName($langs));
        foreach ($shipTos as $shipTo) {
            $one_res_done = false;
            $this->debug_content .= '<h3>SoldTo: ' . $soldTo . ' - ShipTo: ' . $shipTo . '</h3><br/>';

            $fetch_errors = array();
            $result = GSX_Reservation::fetchReservationsSummay($soldTo, $shipTo, '', $from, $to, $fetch_errors, $this->debug_content);

            if (count($fetch_errors)) {
                $this->Error('SoldTo ' . $soldTo . ' - ShipTo ' . $shipTo . ': ' . BimpTools::getMsgFromArray($fetch_errors, 'Echec récupération des réservations'));
            } elseif (is_array($result)) {
                if (isset($result['errors'])) {
                    if (isset($result['errors'][0]['code']) && $result['errors'][0]['code'] === 'SYS.RSV.023') { // todo: à checker
                        $this->debug_content .= BimpRender::renderAlerts('Aucune réservation', 'warning');
                    } elseif (isset($result['errors'][0]['code']) && in_array($result['errors'][0]['code'], array('SYS.STR.005', 'SYS.STR.002', 'SYS.STR.006'))) {
                        $this->debug_content .= BimpRender::renderAlerts('ShipTo Invalide (msg: ' . $result['errors'][0]['message'] . ' - code: ' . $result['errors'][0]['code'] . ')', 'warning');
                    } elseif (is_array($result['errors'])) {
                        foreach ($result['errors'] as $error) {
                            if (isset($error['message'])) {
                                $this->Error('SoldTo ' . $soldTo . ' - ShipTo ' . $shipTo . ': ' . $error['message'] . (isset($error['code']) ? ' (Code: ' . $error['code'] . ')' : ''));
                            }
                        }
                    }
                } elseif (isset($result['reservations'])) {
                    $this->DebugData($result, 'RESPONSE');

                    foreach ($result['reservations'] as $reservation) {
                        if (isset($reservation['reservationId'])) {
                            $this->debug_content .= '<span class="bold">Réservation ' . $reservation['reservationId'] . ': <br/></span>';
                            if ($this->reservationExists($reservation['reservationId'])) {
                                $this->debug_content .= BimpRender::renderAlerts('Déjà enregistrée', 'success');
                            } else {
                                $fetch_errors = array();
                                $reservation_data = GSX_Reservation::fetchReservation($soldTo, $shipTo, $reservation['reservationId'], $fetch_errors, $this->debug_content);

                                if (count($fetch_errors)) {
                                    $this->Error('SoldTo ' . $soldTo . ' - ShipTo ' . $shipTo . ': ' . BimpTools::getMsgFromArray($fetch_errors, 'Echec récupération des réservations'));
                                } else {
                                    $this->DebugData($reservation_data, 'RESPONSE');
                                    if (isset($result['errors'])) {
                                        foreach ($reservation['errors'] as $error) {
                                            $this->Error('SoldTo ' . $soldTo . ' - ShipTo ' . $shipTo . ': ' . $error['message'] . (isset($error['code']) ? ' (Code: ' . $error['code'] . ')' : ''), null, $reservation['reservationId']);
                                        }
                                    } if (is_array($reservation_data)) {
                                        $this->processReservation($reservation_data, $shipTo, $reservation['reservationId']);
                                        $one_res_done = true;
                                    }
                                }
                            }
                            $this->debug_content .= '<br/><br/>';
                        }

                        if ($one_res_done && isset($this->options['test_one']) && (int) $this->options['test_one']) {
                            break;
                        }
                    }
                }
            }

            $this->debug_content .= '<br/><br/>';
            if ($one_res_done && isset($this->options['test_one']) && (int) $this->options['test_one']) {
                break;
            }
        }
    }

    public function processReservation($data, $shipTo, $resId)
    {
        if ($resId) {
            $users = $this->getUsersByShipTo($shipTo);

            if (empty($users)) {
                $this->Error('Aucun utilisateur pour le shipTo ' . $shipTo, null, $resId);
            } else {
                $client = null;

                // Création du client: 
                if (isset($data['customer']) && !empty($data['customer'])) {
                    $client = $this->getExistingClient($data['customer'], $resId);
                }

                // Création de l'équipement: 
//                $equipment = $this->getOrCreateEquipment(BimpTools::getArrayValueFromPath($data, 'product/serialNumber', ''), $resId);
                // Création du SAV: 
//                $centre = '';
//                foreach ($users as $u) {
//                    if (!empty($u['centre'])) {
//                        $centre = $u['centre'];
//                        break;
//                    }
//                }
//                $sav_errors = array();
//                $sav = $this->createSav($client, $equipment, $centre, $resId, $data, $sav_errors);
//
//                if (BimpObject::objectLoaded($sav)) {
//                    $crea_date = '';
//
//                    if (isset($data['createdDate']) && !empty($data['createdDate'])) {
//                        $dt = new DateTime($data['createdDate']);
//
//                        if (isset($data['storeTimeZone']) && !empty($data['storeTimeZone'])) {
//                            $dt->setTimezone($data['storeTimeZone']);
//                        }
//
//                        $crea_date = $dt->format('d / m / Y H:i');
//                    }
//
//                    $res_date = '';
//                    if (isset($data['reservationDate']) && !empty($data['reservationDate'])) {
//                        $dt = new DateTime($data['reservationDate']);
//
//                        if (isset($data['storeTimeZone']) && !empty($data['storeTimeZone'])) {
//                            $dt->setTimezone($data['storeTimeZone']);
//                        }
//
//                        $res_date = $dt->format('d / m / Y H:i');
//                    }
//
//                    $sav->addNote('Création automatique' . "\n" . 'ID réservation Apple: ' . $resId . ($crea_date ? "\n" . 'Date création réservation: ' . $crea_date : '') . ($res_date ? "\n" . 'Date réservation: ' . $res_date : ''));
//                }

                $this->createActionComm($data, $client, $users, $resId, $shipTo);
            }
        }
    }

    public function getExistingClient($client_data, $resId)
    {
        $client = null;
        $email = BimpTools::getArrayValueFromPath($client_data, 'emailId', '');

        if (!$email) {
            $this->Alert('Impossible de créer ou récupérer le client (e-mail absent)', null, $resId);
            $this->report->increaseObjectData('bimpcore', 'Bimp_Client', 'nbIgnored');
        } else {
            // Recherche via UserClient: 
            $userClient = BimpCache::findBimpObjectInstance('bimpinterfaceclient', 'BIC_UserClient', array(
                        'email_custom' => array(
                            'custom' => 'LOWER(email) = \'' . strtolower($email) . '\''
                        )
//                        'email' => $email
                            ), true);

            if (BimpObject::objectLoaded($userClient)) {
                $client = $userClient->getParentInstance();

                if (BimpObject::objectLoaded($client)) {
                    $this->debug_content .= BimpRender::renderAlerts('Client #' . $client->id . ' trouvé pour l\'e-mail ' . $email . ' - vai UserClient #' . $userClient->id, 'info');
                    return $client;
                }
            }

            // Recherche via contact client: 
            $contact = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_Contact', array(
                        'email' => $email
                            ), true);

            if (BimpObject::objectLoaded($contact)) {
                $client = $contact->getParentInstance();

                if (BimpObject::objectLoaded($client)) {
                    $this->debug_content .= BimpRender::renderAlerts('Client #' . $client->id . ' trouvé pour l\'e-mail ' . $email . ' - vai Contact #' . $contact->id, 'info');
                    return $client;
                }
            }

            // Recherche client: 
            $client = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_Client', array(
                        'client' => array(
                            'in' => array(1, 2, 3)
                        ),
                        'email'  => $email
                            ), true);

            if (BimpObject::objectLoaded($client)) {
                $this->debug_content .= BimpRender::renderAlerts('Client #' . $client->id . ' trouvé pour l\'e-mail ' . $email, 'info');
                return $client;
            }

//            if (!BimpObject::objectLoaded($client)) {
//                if ((int) BimpTools::getArrayValueFromPath($this->options, 'create_client', 0)) {
//                    $client = BimpObject::getInstance('bimpcore', 'Bimp_Client');
//                    $this->setCurrentObject($client);
//
//                    $name = BimpTools::getArrayValueFromPath($client_data, 'lastname', '');
//
//                    if (isset($client_data['firstName'])) {
//                        $name .= ($name ? ' ' : '') . $client_data['firstName'];
//                    }
//
//                    $address = BimpTools::getArrayValueFromPath($client_data, 'addressLine1', '');
//
//                    if (isset($client_data['addressLine2']) && !empty($client_data['addressLine2'])) {
//                        $address .= ($address ? "\n" : '') . $client_data['addressLine2'];
//                    }
//
//                    $client_warnings = array();
//
//                    $client_errors = $client->validateArray(array(
//                        'fk_typent'    => 8,
//                        'email'        => $email,
//                        'client'       => 1,
//                        'code_client'  => -1,
//                        'status'       => 1,
//                        'tva_assuj'    => 1,
//                        'fk_pays'      => 1,
//                        'note_private' => 'Créé automatiquement via réservations Apple',
//                        'nom'          => $name,
//                        'address'      => $address,
//                        'zip'          => BimpTools::getArrayValueFromPath($client_data, 'postalCode', '00000'),
//                        'town'         => BimpTools::getArrayValueFromPath($client_data, 'city', 'Inconnue'),
//                        'phone'        => BimpTools::getArrayValueFromPath($client_data, 'phoneNumber', '')
//                    ));
//
//                    if (!count($client_errors)) {
//                        $client_errors = $client->create($client_warnings, true);
//                    }
//
//                    if (count($client_errors)) {
//                        $this->incIgnored();
//                        $this->Error(BimpTools::getMsgFromArray($client_errors, 'Echec de la création du client'), $client, $resId);
//                    } else {
//                        $this->incCreated();
//                        $this->Success('Client créé avec succès', $client, $resId);
//                    }
//
//                    if (count($client_warnings)) {
//                        $this->Alert(BimpTools::getMsgFromArray($client_warnings, 'Erreurs lors de la création du client'), $client, $resId);
//                    }
//                }
//            }
        }

        return null;
    }

    public function getOrCreateEquipment($serial, $resId)
    {
        $equipment = null;

        if (!$serial) {
            $this->Alert('Impossible de créer ou récupérer l\'équipement (numéro de série absent)', null, $resId);
            $this->report->increaseObjectData('bimpequipment', 'Equipment', 'nbIgnored');
        } else {
            $equipment = BimpCache::findBimpObjectInstance('bimpequipment', 'Equipment', array(
                        'serial' => $serial
                            ), true);

            if (BimpObject::objectLoaded($equipment)) {
                $this->debug_content .= BimpRender::renderAlerts('Equipement #' . $equipment->id . ' trouvé pour le serial ' . $serial, 'info');
                return $equipment;
            }

//            if (!BimpObject::objectLoaded($equipment)) {
//                if ((int) BimpTools::getArrayValueFromPath($this->options, 'create_equipment', 0)) {
//                    $equipment = BimpObject::getInstance('bimpequipment', 'Equipment');
//                    $this->setCurrentObject($equipment);
//
//                    $eq_warnings = array();
//
//                    $eq_errors = $equipment->validateArray(array(
//                        'serial'        => $serial,
//                        'product_label' => '*****'
//                    ));
//
//                    if (!count($eq_errors)) {
//                        $eq_errors = $equipment->create($eq_warnings, true);
//                    }
//
//                    if (count($eq_errors)) {
//                        $this->incIgnored();
//                        $this->Error(BimpTools::getMsgFromArray($eq_errors, 'Echec de la création de l\'équipement'), $equipment, $resId);
//                    } else {
//                        $this->incCreated();
//                        $this->Success('Equipement créé avec succès', $equipment, $resId);
//                    }
//
//                    if (count($eq_warnings)) {
//                        $this->Alert(BimpTools::getMsgFromArray($eq_warnings, 'Erreurs lors de la création du client'), $equipment, $resId);
//                    }
//                }
//            }
        }

        return null;
    }

    public function createSav($client, $equipment, $centre, $resId, $data, &$errors = array())
    {
        return null;
//        if (!(int) BimpTools::getArrayValueFromPath($this->options, 'create_sav', 0)) {
//            return null;
//        }
//
//        $sav = null;
//        if (!BimpObject::objectLoaded($equipment)) {
//            $errors[] = 'Equipement absent';
//        }
//
//        if (!BimpObject::objectLoaded($client)) {
//            $errors[] = 'Client absent';
//        }
//
//        if (!$centre) {
//            $errors[] = 'Centre absent';
//        }
//
//        if (!count($errors)) {
//            $sav = BimpCache::findBimpObjectInstance('bimpsupport', 'BS_SAV', array(
//                        'id_equipment' => $equipment->id,
//                        'status'       => array(
//                            'operator' => '<',
//                            'value'    => 999
//                        )
//                            ), true);
//
//            if (BimpObject::objectLoaded($sav)) {
//                $errors[] = 'Un SAV est déjà en cours pour l\'équipement ' . $equipment->getLink() . ' : ' . $sav->getLink();
//            }
//        }
//
//        if (count($errors)) {
//            $this->Alert(BimpTools::getMsgFromArray($errors, 'Impossible de créer le SAV'), null, $resId);
//            $this->report->increaseObjectData('bimpsupport', 'BS_SAV', 'nbIgnored');
//        } else {
//            $sav_warnings = array();
//
//            $sav = BimpObject::getInstance('bimpsupport', 'BS_SAV');
//            $this->setCurrentObject($sav);
//
//            $symptomes = BimpTools::getArrayValueFromPath($data, 'product/issueReported', '');
//
//            if (isset($data['notes']) && !empty($data['notes'])) {
//                $i = 1;
//                foreach ($data['notes'] as $note) {
//                    if (isset($note['text']) && (string) $data['text']) {
//                        $symptomes .= ($symptomes ? "\n\n" : '') . 'Note ' . $i . ': ' . $data['text'];
//                        $i++;
//                    }
//                }
//            }
//
//            if (!$symptomes) {
//                $symptomes = '/';
//            }
//
//            $sav_errors = $sav->validateArray(array(
//                'code_centre'  => $centre,
//                'id_client'    => $client->id,
//                'id_equipment' => $equipment->id,
//                'symptomes'    => $symptomes,
//                'pword_admin'  => '/'
//            ));
//
//            if (!count($sav_errors)) {
//                $sav_errors = $sav->create($sav_warnings, true);
//            }
//
//            if (count($sav_errors)) {
//                $this->incIgnored();
//                $this->Error(BimpTools::getMsgFromArray($sav_errors, 'Echec de la création du SAV'), $sav, $resId);
//            } else {
//                $this->incCreated();
//                $this->Success('SAV créé avec succès', $sav, $resId);
//            }
//
//            if (count($sav_warnings)) {
//                $this->Alert(BimpTools::getMsgFromArray($sav_warnings, 'Erreurs lors de la création du SAV'), $sav, $resId);
//            }
//        }
//
//        return $sav;
    }

    public function createActionComm($data, $client, $users, $resId, $shipTo)
    {
        $this->setCurrentObjectData('bimpcore', 'Bimp_ActionComm');

        global $db, $user;

        BimpTools::loadDolClass('comm/action', 'actioncomm', 'ActionComm');
        $ac = new ActionComm($db);

//        $sav_note = '';
//
//        if (BimpObject::objectLoaded($sav)) {
//            $sav_note = 'SAV: ' . $sav->getLink() . "\n";
//        } elseif ((int) BimpTools::getArrayValueFromPath($this->options, 'create_sav', 0)) {
//            $sav_note = 'LE SAV N\'A PAS PU ETRE CREE' . "\n\n";
//
//            if (count($sav_errors)) {
//                $sav_note .= 'Erreurs: ' . "\n";
//
//                foreach ($sav_errors as $sav_error) {
//                    $sav_note .= ' - ' . $sav_error . "\n";
//                }
//            }
//        }

        $ac->type_id = 52;
        $ac->label = 'Réservation SAV (Site Apple)';
        $ac->transparency = 1;

        $dateBegin = new DateTime(date('Y-m-d H:i:s', strtotime($data['reservationDate'])));
        $dateEnd = new DateTime(date('Y-m-d H:i:s', strtotime($data['reservationDate'])));
        $dateEnd->add(new DateInterval('PT20M'));

        $ac->datep = $db->jdate($dateBegin->format('Y-m-d H:i:s'));
        $ac->datef = $db->jdate($dateEnd->format('Y-m-d H:i:s'));

        $usersAssigned = array();

        foreach ($users as $u) {
            $usersAssigned[] = array('id' => $u['id'], 'transparency' => 1, 'answer_status' => 1);
        }

        $ac->userassigned = $usersAssigned;
        if (!isset($usersAssigned[0])) {
            $this->Error('Impossible de créer le RDV - Aucun utilisateur', null, $resId);
            $this->incIgnored();
            return;
        } else {
            $ac->userownerid = $usersAssigned[0]['id'];
            $ac->note = '';

            if (BimpObject::objectLoaded($client)) {
                $ac->socid = $client->id;
            } else {
                if (isset($data['customer']) && !empty($data['customer'])) {
                    $ac->note .= 'Infos client: ';
                    $client_fname = BimpTools::getArrayValueFromPath($data, 'customer/firstname', '');
                    $client_lname = BimpTools::getArrayValueFromPath($data, 'customer/lastname', '');
                    $client_email = BimpTools::getArrayValueFromPath($data, 'customer/emailId', '');
                    $client_phone = BimpTools::getArrayValueFromPath($data, 'customer/phone/primaryPhone', '');

                    if ($client_fname || $client_lname) {
                        $ac->note .= "\n" . $client_fname . ($client_fname ? ' ' : '') . $client_lname;
                    }

                    if ($client_email) {
                        $ac->note .= "\n" . $client_email;
                    }

                    if ($client_phone) {
                        $ac->note .= "\n" . $client_phone;
                    }

                    $ac->note .= "\n";
                }
            }

            if (isset($data['notes']) && count($data['notes'])) {
                $ac->note .= ($ac->note ? "\n" : '') . 'Notes client: ';
                foreach ($data['notes'] as $note) {
                    if (isset($note['note']) && (string) $note['note']) {
                        $ac->note .= ($ac->note ? "\n" : '') . $note['note'];
                    }
                }
            }

            $ac->array_options['options_resgsx'] = $resId;

//        $ac->insertExtraFields();
//        date_default_timezone_set("GMT");

            $fk_ac = $ac->create($user);

//        date_default_timezone_set("Europe/Paris");

            if ($fk_ac <= 0) {
                $this->Error(BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($ac), 'Echec de la création du RDV'), null, $resId);
                $this->incIgnored();
                return;
            }

            $bimp_ac = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ActionComm', $fk_ac);
            $this->Success('Ajout RDV agenda effectué avec succès', $bimp_ac, $resId);
            $this->incCreated();

            $dateBegin->setTimezone(new DateTimeZone("Europe/Paris"));
            $dateEnd->setTimezone(new DateTimeZone("Europe/Paris"));

            // Envoi e-mails users: 
            $subject = 'Nouvelle Reservation SAV le ' . $dateBegin->format('d / m / Y') . ' à ' . $dateBegin->format('H\Hi');
            $message = 'Bonjour,' . "\n\n";
            $message .= 'Une nouvelle réservation SAV a été ajouté à votre agenda:' . "\n\n";

            $message .= 'Un e-mail a été envoyé au client pour qu\'il finalise sa demande sur le site bimp.fr' . "\n\n";

            $message .= "\t" . 'ID réservation: ' . $resId . "\n";
            $message .= "\t" . 'Date: ' . $dateBegin->format('d/m/Y') . ' à ' . $dateBegin->format('H\Hi') . ".\n";

            if (isset($data['product']['productCode']) && (string) $data['product']['productCode']) {
                $message .= "\t" . 'Type de produit: ' . $data['product']['productCode'] . ".\n";
            }

            if (BimpObject::objectLoaded($client)) {
                $message .= "\t" . 'Client : ' . $client->getLink() . "\n";
                $message .= "\t" . 'E-mail client: ' . $client->getData('email') . "\n";
            }

            if (isset($data['notes']) && count($data['notes'])) {
                $message .= "\n";
                $message .= "\t" . 'Notes:' . "\n";
                foreach ($data['notes'] as $note) {
                    if (isset($note['note']) && (string) $note['note']) {
                        $message .= "\t\t - " . $note['text'] . "\n";
                    }
                }
            }

//            $emails = '';
//
//            foreach ($users as $u) {
//                if (isset($u['email']) && !empty($u['email'])) {
//                    $emails .= ($emails ? ',' : '') . $u['email'];
//                }
//            }
//
//            $emails = BimpTools::cleanEmailsStr($emails);
//
//            if ($emails) {
////                $this->debug_content .= 'Envoi e-mail à "' . $emails . '": ';
////                if (mailSyn2($subject, $emails, '', $message)) {
////                    $this->debug_content .= '<span class="success">OK</span>';
////                } else {
////                    $this->debug_content .= '<span class="danger">ECHEC</span>';
////                    $this->Error('Echec envoi e-mail aux utilisateurs suite ajout RDV SAV (Destinataire(s): ' . $emails . ')', $sav, $data['reservationId']);
////                    BimpCore::addlog('Echec envoi e-mail aux utilisateurs suite ajout RDV SAV', Bimp_Log::BIMP_LOG_URGENT, 'bds', $sav, array(
////                        'Destinataires' => $emails
////                    ));
////                }
//
//                $this->debug_content .= '<br/>';
//            } else {
//                $this->Alert('Aucun e-mail utilisateur pour notification RDV SAV', $sav, $resId);
//                BimpCore::addlog('Aucun e-mail utilisateur pour notification RDV SAV', Bimp_Log::BIMP_LOG_URGENT, 'bds', $sav);
//            }
            // Envoi e-mail client: 
            $email_client = '';

            if (BimpObject::objectLoaded($client)) {
                $email_client = $client->getData('email');
            } elseif (isset($data['customer']['emailId'])) {
                $email_client = $data['customer']['emailId'];
            }

            if ($email_client) {
                $messageClient = "Bonjour,
Merci d’avoir pris rendez-vous dans notre Centre de Services Agrée Apple, nous vous confirmons la prise en compte de votre réservation.\n\n";

                $url = '';
                if ($shipTo) {
                    $url = BimpObject::getPublicBaseUrl(false, BimpPublicController::getPublicEntityForSecteur('S'));

                    if ($url) {
                        $url .= 'fc=savForm&resgsx=' . $resId . '&ac=' . $ac->id . '&centre_id=' . $shipTo;
                        $messageClient .= "<b>Afin de faciliter votre prise en charge, merci de compléter vos informations sur notre site";
                        $messageClient .= ' en cliquant <a href="' . $url . '">sur ce lien</a></b>' . "\n\n";
                        $messageClient .= "Nous souhaitons également attirer votre attention sur les points suivants :\n";
                    }
                }

                if (!$url) {
                    $messageClient .= "Afin de faciliter votre prise en charge, nous souhaitons attirer votre attention sur les points suivants :\n";
                }

                $messageClient .= "- Vous devez sauvegarder vos données car nous serons peut-être amenés à les effacer de votre appareil.

- Vous devez désactiver la fonction « localiser » dans le menu iCloud avec votre mot de passe.

- Le délai de traitement des réparations est habituellement de 7 jours.


Conditions particulières aux iPhones


- Pour certains types de pannes sous garantie, un envoi de l’iPhone dans un centre Apple peut être nécessaire, entrainant un délai plus long (jusqu’à 10 jours ouvrés), dans ce cas un téléphone de prêt est possible (sous réserve de disponibilité). Si cela vous intéresse, merci de vous munir d’un chèque de caution.

La plupart de nos centres peuvent effectuer une réparation de votre écran d’iPhone sous 24h00. Pour savoir si votre centre SAV est éligible à ce type de réparation consultez notre site internet.

Nous proposons des services de sauvegarde des données, de protection de votre téléphone… venez nous rencontrer pour découvrir tous les services que nous pouvons vous proposer.
Votre satisfaction est notre objectif, nous mettrons tout en œuvre pour vous satisfaire et réduire les délais d’immobilisation de votre produit Apple.
Bien cordialement
L’équipe BIMP";

                $from = 'savbimp@bimp.fr';

                $centre = '';
                foreach ($users as $u) {
                    if (!empty($u['centre'])) {
                        $centre = $u['centre'];
                        break;
                    }
                }
                $centres = BimpCache::getCentres();
                if (isset($centres[$centre]) && isset($centres[$centre]['mail']))
                    $from = $centres[$centre]['mail'];

                $to = BimpTools::cleanEmailsStr($email_client);
                $this->debug_content .= 'Envoi e-mail client à ' . $to . ': ';

                $bimpMail = new BimpMail((is_object($client) ? $client : 'none'), "Votre rendez-vous SAV BIMP", $to, $from, str_replace("\n", "<br/>", $messageClient));

                if (BimpCore::isEntity('bimp')) {
                    $bimpMail->setFromType('ldlc');
                }
                $mail_errors = array();

                if ($bimpMail->send($mail_errors)) {
                    $this->Success('Envoi e-mail client OK (Destinataire(s): ' . $to . ', From : ' . $from . ')', $to, null, $resId);
                    $this->debug_content .= '<span class="success">OK</span>';
                } else {
                    $this->debug_content .= '<span class="danger">ECHEC</span>';
                    $this->Error(BimpTools::getMsgFromArray($mail_errors, 'Echec envoi e-mail au client suite ajout RDV SAV (Destinataire(s): ' . $to . ')'), null, $resId);
                    BimpCore::addlog('Echec envoi e-mail au client suite ajout RDV SAV', Bimp_Log::BIMP_LOG_URGENT, 'bds', null, array(
                        'Destinataire' => $to,
                        'Erreurs'      => $mail_errors
                    ));
                }

                $this->debug_content .= '<br/>';
            } else {
                $this->Alert('Aucun e-mail client pour notification RDV SAV', null, $resId);
                BimpCore::addlog('Aucun e-mail client pour notification RDV SAV', Bimp_Log::BIMP_LOG_URGENT, 'bds', null);
            }
        }
    }

    // Getters:

    public function getAppleIdentifiers(&$errors = array())
    {
        if (is_null($this->apple_ids)) {
            $this->apple_ids = array();
            $centres = BimpCache::getCentres();

            foreach ($centres as $code_centre => $centre_data) {
                $shipTo = BimpTools::getArrayValueFromPath($centre_data, 'shipTo', '');

                if ($shipTo /*&& !in_array((int) $shipTo, GSX_v2::$oldShipTos)*/ && !in_array((int) $shipTo, $this->apple_ids) && $centre_data['active'] == 1) {
                    $this->apple_ids[] = (int) $shipTo;
                }
            }
        }

        return $this->apple_ids;
    }

    public function getCurrentReservations()
    {
        if (is_null($this->current_reservations)) {
            $date = new DateTime();
            $date->sub(new DateInterval('P2D'));

            $sql = 'SELECT ace.resgsx as resId FROM ' . MAIN_DB_PREFIX . 'actioncomm_extrafields ace ';
            $sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'actioncomm ac ON ace.fk_object = ac.id ';
            $sql .= 'WHERE ace.resgsx IS NOT NULL AND ac.datep > \'' . $date->format('Y-m-d H:i:s') . '\'';

            $rows = $this->db->executeS($sql, 'array');

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    $this->current_reservations[] = $r['resId'];
                }
            }
        }

        return $this->current_reservations;
    }

    public static function getUsersByShipTo($shipTo)
    {
        BimpObject::loadClass('bimpcore', 'Bimp_User');
        return Bimp_User::getUsersByShipto($shipTo);
    }

    public function reservationExists($resId)
    {
        $id = (int) $this->db->getValue('actioncomm_extrafields', 'rowid', 'resgsx = \'' . $resId . '\'');

        return ($id ? true : false);
    }

    // Install:

    public static function install(&$errors = array(), &$warnings = array(), $title = '')
    {
        // Process: 
        $process = BimpObject::createBimpObject('bimpdatasync', 'BDS_Process', array(
                    'name'        => 'ImportsGSXReservations',
                    'title'       => ($title ? $title : static::$default_public_title),
                    'description' => 'Import des réservations SAV depuis la plateforme GSX d\'Apple',
                    'type'        => 'import',
                    'active'      => 1
                        ), true, $errors, $warnings);

        if (BimpObject::objectLoaded($process)) {
            // Params: 
            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'sold_to',
                'label'      => 'N° SoldTo',
                'value'      => ''
                    ), true, $warnings, $warnings);

            // Options: 

            $options = array();

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Du',
                        'name'          => 'date_from',
                        'info'          => 'Laisser vide pour utiliser la date du jour',
                        'type'          => 'date',
                        'default_value' => '',
                        'required'      => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options[] = (int) $opt->id;
            }

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Au',
                        'name'          => 'date_to',
                        'info'          => 'Laisser vide pour J+6',
                        'type'          => 'date',
                        'default_value' => '',
                        'required'      => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options[] = (int) $opt->id;
            }

            // Opération "Test":

            $op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
                        'id_process'  => (int) $process->id,
                        'title'       => 'Test',
                        'name'        => 'test',
                        'description' => 'Test de connexion et affichage des réservations à traiter',
                        'warning'     => '',
                        'active'      => 1,
                        'use_report'  => 0,
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($op)) {
                $warnings = array_merge($warnings, $op->addAssociates('options', $options));
            }

            // Options suppl. process: 

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Tester une seule entrée',
                        'name'          => 'test_one',
                        'info'          => '',
                        'type'          => 'toggle',
                        'default_value' => 0,
                        'required'      => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options[] = (int) $opt->id;
            }

//            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
//                        'id_process'    => (int) $process->id,
//                        'label'         => 'Créer SAV',
//                        'name'          => 'create_sav',
//                        'info'          => '',
//                        'type'          => 'toggle',
//                        'default_value' => 0,
//                        'required'      => 0
//                            ), true, $warnings, $warnings);
//
//            if (BimpObject::objectLoaded($opt)) {
//                $options[] = (int) $opt->id;
//            }
//            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
//                        'id_process'    => (int) $process->id,
//                        'label'         => 'Créer équipement',
//                        'name'          => 'create_equipment',
//                        'info'          => '',
//                        'type'          => 'toggle',
//                        'default_value' => 0,
//                        'required'      => 0
//                            ), true, $warnings, $warnings);
//
//            if (BimpObject::objectLoaded($opt)) {
//                $options[] = (int) $opt->id;
//            }
//            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
//                        'id_process'    => (int) $process->id,
//                        'label'         => 'Créer client',
//                        'name'          => 'create_client',
//                        'info'          => '',
//                        'type'          => 'toggle',
//                        'default_value' => 0,
//                        'required'      => 0
//                            ), true, $warnings, $warnings);
//
//            if (BimpObject::objectLoaded($opt)) {
//                $options[] = (int) $opt->id;
//            }
            // Opération Process: 

            $op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
                        'id_process'    => (int) $process->id,
                        'title'         => 'Traiter les réservations',
                        'name'          => 'ProcessReservations',
                        'description'   => 'Recherche des réservations à traiter et création des SAV',
                        'warning'       => '',
                        'active'        => 1,
                        'use_report'    => 1,
                        'reports_delay' => 90
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($op)) {
                $warnings = array_merge($warnings, $op->addAssociates('options', $options));
            }
        }
    }
}
