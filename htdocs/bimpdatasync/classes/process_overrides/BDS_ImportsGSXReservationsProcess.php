<?php

require_once DOL_DOCUMENT_ROOT . "/bimpsupport/centre.inc.php";

class BDS_ImportsGSXReservationsProcess extends BDSImportProcess
{

    public static $products_codes = array('IPOD', 'IPAD', 'IPHONE', 'WATCH', 'APPLETV', 'MAC', 'BEATS');
    public static $certifs = array(
        897316 => array(
            0 => array('test.pem', ''),
            1 => array('prod.pem', '')),
        579256 => array(
            1 => array('proditrb.pem', ''),
            0 => array('privatekey.nopass.pem', ''))
    );
    protected $current_reservations = null;
    protected $apple_ids = null;

    // Init opérations:

    public function initTest(&$data, &$errors = array())
    {
        $from = ($this->options['date_from'] ? $this->options['date_from'] : date('Y-m-d', strtotime('-2 day')));
        $to = ($this->options['date_to'] ? $this->options['date_to'] : date('Y-m-d'));

        $apple_ids = $this->getAppleIdentifiers($errors);

        if (empty($apple_ids)) {
            $errors[] = 'Aucun couple shipTo / soldTo trouvé';
        }

        if (!count($errors)) {
            $data['result_html'] = '';

            foreach (self::$products_codes as $product_code) {
                $pc_html = '';

                foreach ($apple_ids as $ids) {
                    $pc_html .= '<h3>SoldTo: ' . $ids['soldTo'] . ' - ShipTo: ' . $ids['shipTo'] . '</h3><br/>';

                    if (!array_key_exists($ids['soldTo'], self::$certifs)) {
                        $pc_html .= BimpRender::renderAlerts('Aucun certificat pour ce soldTo');
                        continue;
                    }

                    $certif = '';
                    $pword = '';

                    if (isset(self::$certifs[$ids['soldTo']][1][0])) {
                        $certif = self::$certifs[$ids['soldTo']][1][0];
                    }

                    if (isset(self::$certifs[$ids['soldTo']][1][1])) {
                        $pword = self::$certifs[$ids['soldTo']][1][1];
                    }

                    $fetch_errors = array();
                    $reservations = $this->fetchReservationsSummary($ids['soldTo'], $ids['shipTo'], $pword, $certif, $from, $to, $product_code, $fetch_errors);

                    if (count($fetch_errors)) {
                        $pc_html .= BimpRender::renderAlerts(BimpTools::getMsgFromArray($fetch_errors, 'Echec récupération des réservations'));
                    } elseif (count($reservations)) {
                        $pc_html .= '<pre>';
                        $pc_html .= print_r($reservations, 1);
                        $pc_html .= '</pre>';
                    } else {
                        $pc_html .= BimpRender::renderAlerts('Aucune réservation', 'warning');
                    }

                    $pc_html .= '<br/>';
                }

                $data['result_html'] .= BimpRender::renderFoldableContainer('Code produit "' . $product_code . '"', $pc_html);
            }
        }
    }

    public function initProcessReservations(&$data, &$errors = array())
    {
        $from = ($this->options['date_from'] ? $this->options['date_from'] : date('Y-m-d', strtotime('-2 day')));
        $to = ($this->options['date_to'] ? $this->options['date_to'] : date('Y-m-d'));

        $apple_ids = $this->getAppleIdentifiers($errors);

        if (empty($apple_ids)) {
            $errors[] = 'Aucun couple shipTo / soldTo trouvé';
        }

        if ($from > $to) {
            $errors[] = 'Dates invalides';
        }

        if (!count($errors)) {
            $data['steps'] = array();

            foreach (self::$products_codes as $code) {
                $data['steps']['process_' . $code] = array(
                    'label'    => 'Récupération et traitement des réservations - ' . $code,
                    'on_error' => 'continue'
                );
            }
        }
    }

    // Process opérations:

    public function executeProcessReservations($step_name, &$errors = array())
    {
        $result = array();

        if (preg_match('/^process_(.+)$/', $step_name, $matches)) {
            $code = $matches[1];

            if (in_array($code, self::$products_codes)) {
                $this->processProductCodeReservations($code, $errors);
            } else {
                $errors[] = 'Code invalide: ' . $code;
            }
        } else {
            $errors[] = 'Etape invalide: "' . $step_name . '"';
        }

        return $result;
    }

    // Traitements:

    public function processProductCodeReservations($product_code, &$errors = array())
    {
        $from = ($this->options['date_from'] ? $this->options['date_from'] : date('Y-m-d', strtotime('-2 day')));
        $to = ($this->options['date_to'] ? $this->options['date_to'] : date('Y-m-d'));
        $apple_ids = $this->getAppleIdentifiers($errors);

        if (empty($apple_ids)) {
            $errors[] = 'Aucun couple shipTo / soldTo trouvé';
        }

        if ($from > $to) {
            $errors[] = 'Dates invalides';
        }

        if (!count($errors)) {
            foreach ($apple_ids as $ids) {
                $this->debug_content .= '<h3>SoldTo: ' . $ids['soldTo'] . ' - ShipTo: ' . $ids['shipTo'] . '</h3><br/>';

                if (!array_key_exists($ids['soldTo'], self::$certifs)) {
                    $this->debug_content .= BimpRender::renderAlerts('Aucun certificat pour ce soldTo');
                    continue;
                }

                $certif = '';
                $pword = '';

                if (isset(self::$certifs[$ids['soldTo']][1][0])) {
                    $certif = self::$certifs[$ids['soldTo']][1][0];
                }

                if (isset(self::$certifs[$ids['soldTo']][1][1])) {
                    $pword = self::$certifs[$ids['soldTo']][1][1];
                }

                $fetch_errors = array();
                $result = $this->fetchReservationsSummary($ids['soldTo'], $ids['shipTo'], $pword, $certif, $from, $to, $product_code, $fetch_errors);

                if (count($fetch_errors)) {
                    $this->Error($product_code . ' - SoldTo ' . $ids['soldTo'] . ' - ShipTo ' . $ids['shipTo'] . ': ' . BimpTools::getMsgFromArray($fetch_errors, 'Echec récupération des réservations'));
                } elseif (is_array($result)) {
                    if (isset($result['faults'])) {
                        if (isset($result['faults'][0]['code']) && $result['faults'][0]['code'] === 'SYS.RSV.023') {
                            $this->debug_content .= BimpRender::renderAlerts('Aucune réservation', 'warning');
                        } elseif (isset($result['faults'][0]['code']) && in_array($result['faults'][0]['code'], array('SYS.STR.005', 'SYS.STR.002', 'SYS.STR.006'))) {
                            $this->debug_content .= BimpRender::renderAlerts('ShipTo Invalide (msg: ' . $result['faults'][0]['message'] . ' - code: ' . $result['faults'][0]['code'] . ')', 'warning');
                        } elseif (is_array($result['faults'])) {
                            foreach ($result['faults'] as $fault) {
                                if (isset($fault['message'])) {
                                    $this->Error($product_code . ' - SoldTo ' . $ids['soldTo'] . ' - ShipTo ' . $ids['shipTo'] . ': ' . $fault['message'] . (isset($fault['code']) ? ' (Code: ' . $fault['code'] . ')' : ''));
                                }
                            }
                        }
                    } elseif (isset($result['response']['reservations'])) {
                        $this->DebugData($result, 'RESPONSE');
                        $current_reservations = $this->getCurrentReservations();

                        foreach ($result['response']['reservations'] as $reservation) {
                            if (isset($reservation['reservationId'])) {
                                $this->debug_content .= '<span class="bold">Réservation ' . $reservation['reservationId'] . ': <br/></span>';
                                if (in_array($reservation['reservationId'], $current_reservations)) {
                                    $this->debug_content .= BimpRender::renderAlerts('Déjà enregistrée', 'success');
                                } else {
                                    $fetch_errors = array();
                                    $reservation_data = $this->fetchReservation($ids['soldTo'], $ids['shipTo'], $pword, $certif, $reservation['reservationId'], $fetch_errors);

                                    if (count($fetch_errors)) {
                                        $this->Error($product_code . ' - SoldTo ' . $ids['soldTo'] . ' - ShipTo ' . $ids['shipTo'] . ': ' . BimpTools::getMsgFromArray($fetch_errors, 'Echec récupération des réservations'));
                                    } else {
                                        $this->DebugData($reservation_data, 'RESPONSE');
                                        if (isset($reservation_data['response'])) {
                                            $this->processReservation($reservation_data['response'], $ids['shipTo'], $reservation['reservationId']);
                                        } elseif (isset($result['faults'])) {
                                            foreach ($reservation['faults'] as $fault) {
                                                $this->Error($product_code . ' - SoldTo ' . $ids['soldTo'] . ' - ShipTo ' . $ids['shipTo'] . ': ' . $fault['message'] . (isset($fault['code']) ? ' (Code: ' . $fault['code'] . ')' : ''), null, $reservation['reservationId']);
                                            }
                                        }
                                    }
                                }
                                $this->debug_content .= '<br/><br/>';
                            }
                        }
                    }
                }

                $this->debug_content .= '<br/><br/>';
            }
        }
    }

    public function processReservation($data, $shipTo)
    {
        if (isset($data['reservationId'])) {
            $resId = $data['reservationId'];
            $users = $this->getUsersByShipTo($shipTo);

            if (empty($users)) {
                $this->Error('Aucun utilisateur pour le shipTo ' . $shipTo, null, $resId);
            } else {
                $client = null;

                // Création du client: 
                if (isset($data['customer']) && !empty($data['customer'])) {
                    $client = $this->getOrCreateClient($data['customer'], $resId);
                }

                // Création de l'équipement: 
                $equipment = $this->getOrCreateEquipment(BimpTools::getArrayValueFromPath($data, 'product/serialNumber', ''), $resId);

                // Création du SAV: 
                $centre = '';
                foreach ($users as $u) {
                    if (!empty($u['centre'])) {
                        $centre = $u['centre'];
                        break;
                    }
                }

                $sav = $this->createSav($client, $equipment, $centre, $resId, $data);

                if (BimpObject::objectLoaded($sav)) {
                    $crea_date = '';

                    if (isset($data['createdDate']) && !empty($data['createdDate'])) {
                        $dt = new DateTime($data['createdDate']);

                        if (isset($data['storeTimeZone']) && !empty($data['storeTimeZone'])) {
                            $dt->setTimezone($data['storeTimeZone']);
                        }

                        $crea_date = $dt->format('d / m / Y H:i');
                    }

                    $res_date = '';
                    if (isset($data['reservationDate']) && !empty($data['reservationDate'])) {
                        $dt = new DateTime($data['reservationDate']);

                        if (isset($data['storeTimeZone']) && !empty($data['storeTimeZone'])) {
                            $dt->setTimezone($data['storeTimeZone']);
                        }

                        $res_date = $dt->format('d / m / Y H:i');
                    }

                    $sav->addNote('Création automatique' . "\n" . 'ID réservation Apple: ' . $resId . ($crea_date ? "\n" . 'Date création réservation: ' . $crea_date : '') . ($res_date ? "\n" . 'Date réservation: ' . $res_date : ''));

                    $this->createActionComm($data, $client, $sav, $equipment, $users);
                }
            }
        }
    }

    public function fetchReservationsSummary($soldTo, $shipTo, $pword, $certif_filename, $from, $to, $productCode, &$errors = array())
    {
        if (!isset($this->params['api_url']) || !$this->params['api_url']) {
            $errors[] = 'URL API Absente des paramètres';
            return array();
        }

        if (!file_exists(DOL_DOCUMENT_ROOT . '/' . $this->params['certif_dir'] . $certif_filename)) {
            $errors[] = 'Le fichier de certificat "' . $certif_filename . '" n\'existe pas';
            return array();
        }

        $url = $this->params['api_url'] . 'search';
        $this->debug_content .= '<h4>Requête Fetch Reservations Summary</h4>';
        $this->debug_content .= 'URL: <b>' . $url . '</b>';

        $ch = curl_init($url);

        if (!$ch) {
            $errors[] = 'Echec connexion CURL';
        } else {
            $soldTo = BimpTools::addZeros($soldTo, 10);
            $shipTo = BimpTools::addZeros($shipTo, 10);

            $headers = array(
                'Accept: application/json',
                'Content-type: application/json',
                'X-PARTNER-SOLDTO: ' . $soldTo
            );

            $params = array(
                "shipToCode"    => $shipTo,
                "fromDate"      => $from,
                "toDate"        => $to,
                "productCode"   => $productCode,
                "currentStatus" => "RESERVED"
            );

//            $this->DebugData($headers, 'CURL REQUEST HEADER');
//            $this->DebugData($params, 'CURL REQUEST PARAMS');

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            curl_setopt($ch, CURLOPT_SSLCERT, DOL_DOCUMENT_ROOT . '/' . $this->params['certif_dir'] . $certif_filename);
            curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $pword);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $data = curl_exec($ch);
            $error_msg = '';

            if (curl_errno($ch)) {
                $error_msg = curl_error($ch);
            }

            curl_close($ch);

            if ($data === false) {
                $errors[] = 'Echec requête CURL <br/>URL: ' . $this->params['api_url'] . 'search <br/>Params: <pre>' . print_r($params, 1) . '</pre>';
                if ($error_msg) {
                    $errors[] = 'ERR CURL: ' . $error_msg;
                }
            } else {
                return json_decode($data, 1);
            }
        }

        return array();
    }

    public function fetchReservation($soldTo, $shipTo, $pword, $certif_filename, $reservationId, &$errors = array())
    {
        if (!isset($this->params['api_url']) || !$this->params['api_url']) {
            $errors[] = 'URL API Absente des paramètres';
            return array();
        }

        if (!file_exists(DOL_DOCUMENT_ROOT . '/' . $this->params['certif_dir'] . $certif_filename)) {
            $errors[] = 'Le fichier de certificat "' . $certif_filename . '" n\'existe pas';
            return array();
        }

        $soldTo = BimpTools::addZeros($soldTo, 10);
        $shipTo = BimpTools::addZeros($shipTo, 10);

        $url = $this->params['api_url'] . $shipTo . '/' . $reservationId;

        $this->debug_content .= '<h4>Requête Fetch Reservation</h4>';
        $this->debug_content .= 'URL: <b>' . $url . '</b>';

        $ch = curl_init($url);

        if (!$ch) {
            $errors[] = 'Echec connexion CURL';
        } else {
            $headers = array(
                'Accept: application/json',
                'Content-type: application/json',
                'X-PARTNER-SOLDTO: ' . $soldTo
            );

            $this->DebugData($headers, 'CURL REQUEST HEADER');

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSLCERT, DOL_DOCUMENT_ROOT . '/' . $this->params['certif_dir'] . $certif_filename);
            curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $pword);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $data = curl_exec($ch);

            $error_msg = '';

            if (curl_errno($ch)) {
                $error_msg = curl_error($ch);
            }

            curl_close($ch);

            if ($error_msg) {
                $errors[] = 'ERREUR CURL: ' . $error_msg;
            }

            if ($data === false) {
                $errors[] = 'Echec requête CURL';
            } else {
                return json_decode($data, 1);
            }
        }

        return array();
    }

    public function getOrCreateClient($client_data, $resId)
    {
        $client = null;
        $email = BimpTools::getArrayValueFromPath($client_data, 'emailId', '');

        if (!$email) {
            $this->Alert('Impossible de créer le client (e-mail absent)', null, $resId);
            $this->report->increaseObjectData('bimpcore', 'Bimp_Client', 'nbIgnored');
        } else {
            $client = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_Client', array(
                        'client' => array(
                            'in' => array(1, 2, 3)
                        ),
                        'email'  => $email
                            ), true);


            if (!BimpObject::objectLoaded($client)) {
                $client = BimpObject::getInstance('bimpcore', 'Bimp_Client');
                $this->setCurrentObject($client);

                $name = BimpTools::getArrayValueFromPath($client_data, 'lastname', '');

                if (isset($client_data['firstName'])) {
                    $name .= ($name ? ' ' : '') . $client_data['firstName'];
                }

                $address = BimpTools::getArrayValueFromPath($client_data, 'addressLine1', '');

                if (isset($client_data['addressLine2']) && !empty($client_data['addressLine2'])) {
                    $address .= ($address ? "\n" : '') . $client_data['addressLine2'];
                }

                $client_warnings = array();

                $client_errors = $client->validateArray(array(
                    'email'        => $email,
                    'client'       => 1,
                    'code_client'  => -1,
                    'status'       => 1,
                    'tva_assuj'    => 1,
                    'fk_pays'      => 1,
                    'note_private' => 'Créé automatiquement via réservations Apple',
                    'nom'          => $name,
                    'address'      => $address,
                    'zip'          => BimpTools::getArrayValueFromPath($client_data, 'postalCode', '00000'),
                    'town'         => BimpTools::getArrayValueFromPath($client_data, 'city', 'Inconnue'),
                    'phone'        => BimpTools::getArrayValueFromPath($client_data, 'phoneNumber', '')
                ));

                if (!count($client_errors)) {
                    $client_errors = $client->create($client_warnings, true);
                }

                if (count($client_errors)) {
                    $this->incIgnored();
                    $this->Error(BimpTools::getMsgFromArray($client_errors, 'Echec de la création du client'), $client, $resId);
                } else {
                    $this->incCreated();
                    $this->Success('Client créé avec succès', $client, $resId);
                }

                if (count($client_warnings)) {
                    $this->Alert(BimpTools::getMsgFromArray($client_warnings, 'Erreurs lors de la création du client'), $client, $resId);
                }
            } else {
                $this->debug_content .= BimpRender::renderAlerts('Client #' . $client->id . ' trouvé pour l\'e-mail ' . $email, 'info');
            }
        }

        return $client;
    }

    public function getOrCreateEquipment($serial, $resId)
    {
        $equipment = null;

        if (!$serial) {
            $this->Alert('Impossible de créer l\'équipement (numéro de série absent)', null, $resId);
            $this->report->increaseObjectData('bimpequipment', 'Equipment', 'nbIgnored');
        } else {
            $equipment = BimpCache::findBimpObjectInstance('bimpequipment', 'Equipment', array(
                        'serial' => $serial
                            ), false);

            if (!BimpObject::objectLoaded($equipment)) {
                $equipment = BimpObject::getInstance('bimpequipment', 'Equipment');
                $this->setCurrentObject($equipment);

                $eq_warnings = array();

                $eq_errors = $equipment->validateArray(array(
                    'serial'        => $serial,
                    'product_label' => '*****'
                ));

                if (!count($eq_errors)) {
                    $eq_errors = $equipment->create($eq_warnings, true);
                }

                if (count($eq_errors)) {
                    $this->incIgnored();
                    $this->Error(BimpTools::getMsgFromArray($eq_errors, 'Echec de la création de l\'équipement'), $equipment, $resId);
                } else {
                    $this->incCreated();
                    $this->Success('Equipement créé avec succès', $equipment, $resId);
                }

                if (count($eq_warnings)) {
                    $this->Alert(BimpTools::getMsgFromArray($eq_warnings, 'Erreurs lors de la création du client'), $equipment, $resId);
                }
            } else {
                $this->debug_content .= BimpRender::renderAlerts('Equipement #' . $equipment->id . ' trouvé pour le serial ' . $serial, 'info');
            }
        }

        return $equipment;
    }

    public function createSav($client, $equipment, $centre, $resId, $data)
    {
        $sav = null;
        $errors = array();
        if (!BimpObject::objectLoaded($equipment)) {
            $errors[] = 'Equipement absent';
        }

        if (!BimpObject::objectLoaded($client)) {
            $errors[] = 'Client absent';
        }

        if (!$centre) {
            $errors[] = 'Centre absent';
        }

        $sav = BimpCache::findBimpObjectInstance('bimpsupport', 'BS_SAV', array(
                    'id_equipment' => $equipment->id,
                    'status'       => array(
                        'operator' => '<',
                        'value'    => 999
                    )
                        ), true);

        if (BimpObject::objectLoaded($sav)) {
//            $errors[] = 'Un SAV est déjà en cours pour l\'équipement ' . $equipment->getLink() . ' : ' . $sav->getLink();
        }

        if (count($errors)) {
            $this->Alert(BimpTools::getMsgFromArray($errors, 'Impossible de créer le SAV'), null, $resId);
            $this->report->increaseObjectData('bimpsupport', 'BS_SAV', 'nbIgnored');
        } else {
            $sav_warnings = array();

            $sav = BimpObject::getInstance('bimpsupport', 'BS_SAV');
            $this->setCurrentObject($sav);

            $symptomes = BimpTools::getArrayValueFromPath($data, 'product/issueReported', '');

            if (isset($data['notes']) && !empty($data['notes'])) {
                $i = 1;
                foreach ($data['notes'] as $note) {
                    if (isset($note['text']) && (string) $data['text']) {
                        $symptomes .= ($symptomes ? "\n\n" : '') . 'Note ' . $i . ': ' . $data['text'];
                        $i++;
                    }
                }
            }

            if (!$symptomes) {
                $symptomes = '/';
            }

            $sav_errors = $sav->validateArray(array(
                'code_centre'  => $centre,
                'id_client'    => $client->id,
                'id_equipment' => $equipment->id,
                'symptomes'    => $symptomes,
                'pword_admin'  => '/'
            ));

            if (!count($sav_errors)) {
                $sav_errors = $sav->create($sav_warnings, true);
            }

            if (count($sav_errors)) {
                $this->incIgnored();
                $this->Error(BimpTools::getMsgFromArray($sav_errors, 'Echec de la création du SAV'), $sav, $resId);
            } else {
                $this->incCreated();
                $this->Success('SAV créé avec succès', $sav, $resId);
            }

            if (count($sav_warnings)) {
                $this->Alert(BimpTools::getMsgFromArray($sav_warnings, 'Erreurs lors de la création du SAV'), $sav, $resId);
            }
        }

        return $sav;
    }

    public function createActionComm($data, $client, $sav, $equipment, $users)
    {
        $this->setCurrentObjectData('bimpcore', 'Bimp_ActionComm');

        global $db, $user;
        BimpTools::loadDolClass('comm/action', 'actioncomm', 'ActionComm');
        $ac = new ActionComm($db);

        $ac->type_id = 52;
        $ac->label = 'Réservation SAV';
        $ac->transparency = 1;

        $dateBegin = new DateTime($data['reservationDate'], new DateTimeZone($data['storeTimeZone']));
        $dateEnd = new DateTime($data['reservationDate'], new DateTimeZone($data['storeTimeZone']));
        $dateEnd->add(new DateInterval('PT2H'));
//        $dateBegin->setTimezone(new DateTimeZone("Europe/Paris"));
//        $dateEnd->setTimezone(new DateTimeZone("Europe/Paris"));

        $ac->datep = $db->jdate($dateBegin->format('Y-m-d H:i:s'));
        $ac->datef = $db->jdate($dateEnd->format('Y-m-d H:i:s'));

        $usersAssigned = array();

        foreach ($users as $u) {
            $usersAssigned[] = array('id' => $u['id'], 'transparency' => 1, 'answer_status' => 1);
        }

        $ac->userassigned = $usersAssigned;
        if (!isset($usersAssigned[0])) {
            $this->Error('Impossible de créer le RDV - Aucun utilisateur', null, $data['reservationId']);
            $this->incIgnored();
            return;
        } else {
            $ac->userownerid = $usersAssigned[0]['id'];

            if (count($data['notes'])) {
                $ac->note = '';

                foreach ($data['notes'] as $note) {
                    if (isset($note['text']) && (string) $note['text']) {
                        $ac->note .= ($ac->note ? ' - ' : '') . $note['text'];
                    }
                }
            }

            if (BimpObject::objectLoaded($client)) {
                $ac->socid = $client->id;
            }

            $ac->array_options['options_resgsx'] = $data['reservationId'];

//        $ac->insertExtraFields();
//        date_default_timezone_set("GMT");

            $fk_ac = $ac->create($user);

//        date_default_timezone_set("Europe/Paris");

            if ($fk_ac <= 0) {
                $this->Error(BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($ac), 'Echec de la création du RDV'), null, $data['reservationId']);
                $this->incIgnored();
                return;
            }

            $bimp_ac = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ActionComm', $fk_ac);
            $this->Success('Ajout RDV agenda effectué avec succès', $bimp_ac, $data['reservationId']);
            $this->incCreated();

            $dateBegin->setTimezone(new DateTimeZone("Europe/Paris"));
            $dateEnd->setTimezone(new DateTimeZone("Europe/Paris"));

            // Envoi e-mails users: 
            $subject = 'Nouvelle Reservation SAV le ' . $dateBegin->format('d / m / Y') . ' à ' . $dateBegin->format('H\Hi');
            $message = 'Bonjour,' . "\n\n";
            $message .= 'Une nouvelle réservation SAV a été ajouté à votre agenda:' . "\n\n";

            if (BimpObject::objectLoaded($sav)) {
                $message .= "\t" . 'Accès au SAV : ' . $sav->getLink() . "\n";
            }

            $message .= "\t" . 'ID réservation: ' . $data['reservationId'] . "\n";
            $message .= "\t" . 'Date: ' . $dateBegin->format('d/m/Y') . ' à ' . $dateBegin->format('H\Hi') . ".\n";

            if (isset($data['product']['productCode']) && (string) $data['product']['productCode']) {
                $message .= "\t" . 'Type de produit: ' . $data['product']['productCode'] . ".\n";
            }

            if (BimpObject::objectLoaded($equipment)) {
                $message .= "\t" . 'Equipement: ' . $equipment->getLink() . "\n";
            }

            if (BimpObject::objectLoaded($client)) {
                $message .= "\t" . 'Client : ' . $client->getLink() . "\n";
                $message .= "\t" . 'E-mail client: ' . $client->getData('email') . "\n";
            }

            if (isset($data['notes']) && count($data['notes'])) {
                $message .= "\n";
                $message .= "\t" . 'Notes:' . "\n";
                foreach ($data['notes'] as $note) {
                    if (isset($note['text']) && (string) $note['text']) {
                        $message .= "\t\t - " . $note['text'] . "\n";
                    }
                }
            }

            $emails = '';

            foreach ($users as $u) {
                if (isset($u['email']) && !empty($u['email'])) {
                    $emails .= ($emails ? ',' : '') . $u['email'];
                }
            }

            $emails = BimpTools::cleanEmailsStr($emails);

            if ($emails) {
                $this->debug_content .= 'Envoi e-mail à "' . $emails . '": ';

                if (mailSyn2($subject, $emails, '', $message)) {
                    $this->logError('Echec de l\'envoi du mail de notification (ID réservation: ' . $data['reservationId'] . ')');
                    $this->debug_content .= '<span class="success">OK</span>';
                } else {
                    $this->debug_content .= '<span class="danger">ECHEC</span>';
                    $this->Error('Echec envoi e-mail aux utilisateurs suite ajout RDV SAV (Destinataire(s): ' . $emails . ')', $sav, $data['reservationId']);
                    BimpCore::addlog('Echec envoi e-mail aux utilisateurs suite ajout RDV SAV', Bimp_Log::BIMP_LOG_URGENT, 'bds', $sav, array(
                        'Destinataires' => $emails
                    ));
                }

                $this->debug_content .= '<br/>';
            } else {
                $this->Alert('Aucun e-mail utilisateur pour notification RDV SAV', $sav, $data['reservationId']);
                BimpCore::addlog('Aucun e-mail utilisateur pour notification RDV SAV', Bimp_Log::BIMP_LOG_URGENT, 'bds', $sav);
            }

            // Envoi e-mail client: 
            if (BimpObject::objectLoaded($client)) {
                $email_client = BimpTools::cleanEmailsStr($client->getData('email'));

                if ($email_client) {
                    $messageClient = "Bonjour,
Merci d’avoir pris rendez-vous dans notre Centre de Services Agrée Apple, nous vous confirmons la prise en compte de votre réservation.
Afin de préparer au mieux votre prise en charge, nous souhaitons attirer votre attention sur les points suivants :
- Vous devez sauvegarder vos données car nous serons peut-être amenés à les effacer de votre appareil.

- Vous devez désactiver la fonction « localiser » dans le menu iCloud avec votre mot de passe.

- Le délai de traitement des réparations est habituellement de 7 jours.


Conditions particulières aux iPhones


- Pour certains types de pannes sous garantie, un envoi de l’iPhone dans un centre Apple peut être nécessaire, entrainant un délai plus long (jusqu’à 10 jours ouvrés), dans ce cas un téléphone de prêt est possible (sous réserve de disponibilité). Si cela vous intéresse, merci de vous munir d’un chèque de caution.

La plupart de nos centres peuvent effectuer une réparation de votre écran d’iPhone sous 24h00. Pour savoir si votre centre SAV est éligible à ce type de réparation consultez nottre site internet.

Nous proposons des services de sauvegarde des données, de protection de votre téléphone… venez nous rencontrer pour découvrir tous les services que nous pouvons vous proposer.
Votre satisfaction est notre objectif, nous mettrons tout en œuvre pour vous satisfaire et réduire les délais d’immobilisation de votre produit Apple.
Bien cordialement
L’équipe BIMP";

                    $from = '';
                    $centre = 'savbimp@bimp.fr';

                    if (BimpObject::objectLoaded($sav)) {
                        $centre = $sav->getData('code_centre');
                    }

                    if ($centre) {
                        global $tabCentre;
                        $centreData = isset($tabCentre[$centre]) ? $tabCentre[$centre] : array();

                        if (isset($centreData[1]) && $centreData[1]) {
                            $from = "SAV BIMP<" . $centreData[1] . ">";
                        }
                    }

                    $this->debug_content .= 'Envoi e-mail client à ' . $email_client . ': ';

                    if (mailSyn2("RDV SAV BIMP", $email_client, $from, str_replace("\n", "<br/>", $messageClient))) {
                        $this->debug_content .= '<span class="success">OK</span>';
                    } else {
                        $this->debug_content .= '<span class="danger">ECHEC</span>';
                        $this->Error('Echec envoi e-mail au client suite ajout RDV SAV (Destinataire(s): ' . $emails . ')', $sav, $data['reservationId']);
                        BimpCore::addlog('Echec envoi e-mail au client suite ajout RDV SAV', Bimp_Log::BIMP_LOG_URGENT, 'bds', $sav, array(
                            'Destinataire' => $email_client
                        ));
                    }

                    $this->debug_content .= '<br/>';
                } else {
                    $this->Alert('Aucun e-mail client pour notification RDV SAV', $sav, $data['reservationId']);
                    BimpCore::addlog('Aucun e-mail client pour notification RDV SAV', Bimp_Log::BIMP_LOG_URGENT, 'bds', $sav);
                }
            }
        }
    }

    // Getters:

    public function getAppleIdentifiers(&$errors = array())
    {
        if (is_null($this->apple_ids)) {
            $sql = 'SELECT DISTINCT(CAST(`apple_shipto` AS UNSIGNED)) as shipTo, `apple_service` as soldTo FROM ' . MAIN_DB_PREFIX . 'user_extrafields';
            $sql .= ' WHERE `apple_shipto` IS NOT NULL AND `apple_service` IS NOT NULL';

            $rows = $this->db->executeS($sql, 'array');

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    $this->apple_ids[] = array(
                        'shipTo' => (int) $r['shipTo'],
                        'soldTo' => (int) $r['soldTo']
                    );
                }

                if ($this->display_debug)
                    echo 'Chargement de la liste des shipto OK<br/>';
            } else {
                $errors[] = 'Echec du chargement des couples shipTo / soldTo - ' . $this->db->err();
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

    public function getUsersByShipTo($shipTo)
    {
        if (!$shipTo) {
            return array();
        }

        $cache_key = 'users_gsx_data_fro_shipto_' . $shipTo;

        if (!isset(BimpCache::$cache[$cache_key])) {
            BimpCache::$cache[$cache_key] = array();

            $sql = 'SELECT u.`rowid` as id, u.email, ue.apple_techid as techid, ue.apple_centre as centre_sav FROM ' . MAIN_DB_PREFIX . 'user u';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'user_extrafields ue ON u.rowid = ue.fk_object';
            $sql .= ' WHERE ue.apple_shipto = \'' . $shipTo . '\' AND ue.apple_techid IS NOT NULL AND u.statut = 1 AND ue.gsxresa = 1';

            $rows = BimpCache::getBdb()->executeS($sql, 'array');

            $centre_sav = '';

            foreach ($rows as $r) {
                if (!empty($r['centre_sav'])) {
                    $centres = explode(' ', $r['centre_sav']);
                    if (isset($centres[0]) && $centres[0] != "") {
                        $centre_sav = $centres[0];
                    } elseif (isset($centres[1]) && $centres[1] != "") {
                        $centre_sav = $centres[1];
                    }
                }

                BimpCache::$cache[$cache_key][] = array(
                    'id'     => $r['id'],
                    'techid' => $r['techid'],
                    'email'  => $r['email'],
                    'centre' => $centre_sav
                );
            }
        }

        return BimpCache::$cache[$cache_key];
    }

    // Install: 

    public static function install(&$errors = array(), &$warnings = array())
    {
        // Process: 
        $process = BimpObject::createBimpObject('bimpdatasync', 'BDS_Process', array(
                    'name'        => 'ImportsGSXReservations',
                    'title'       => 'Imports Réservations GSX',
                    'description' => 'Import des réservations SAV depuis la plateforme GSX d\'Apple',
                    'type'        => 'import',
                    'active'      => 1
                        ), true, $errors, $warnings);

        if (BimpObject::objectLoaded($process)) {

            // Params: 

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'api_url',
                'label'      => 'URL API',
                'value'      => 'https://asp-partner.apple.com/api/v1/partner/reservation/'
                    ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'certif_dir',
                'label'      => 'Dossier certificats',
                'value'      => 'bimpapple/certif/'
                    ), true, $warnings, $warnings);

            // Options: 

            $options = array();

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Du',
                        'name'          => 'date_from',
                        'info'          => 'Laisser vide pour J-2',
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
                        'info'          => 'Laisser vide pour utiliser la date du jour',
                        'type'          => 'date',
                        'default_value' => '',
                        'required'      => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options[] = (int) $opt->id;
            }

            // Opérations: 
            
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
        }
    }
}
