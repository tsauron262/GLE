<?php

class GSX_Reservation
{

    public static $mode = 'prod';
    public static $products_codes = array('IPOD', 'IPAD', 'IPHONE', 'WATCH', 'APPLETV', 'MAC', 'BEATS', 'AIRPODS', 'HOMEPOD');
    public static $certifs = array(
        'test' => array(
            897316 => 'test.pem',
            579256 => 'privatekey.nopass.pem'
        ),
        'prod' => array(
            897316 => 'prod.pem',
            579256 => 'proditrb.pem'
        )
    );
    public static $base_urls = array(
        'test' => 'https://asp-partner-ut.apple.com/api/v1/partner/',
        'prod' => 'https://asp-partner.apple.com/api/v1/partner/'
    );
    public static $default_tech_id = 'G1DFE7494B';
    protected static $gsx_v2 = null;

    public static function useGsxV2()
    {
        return (int) BimpCore::getConf('use_gsx_v2_for_reservations', 0);
    }

    public static function getGsxV2()
    {
        if (is_null(self::$gsx_v2)) {
            if (!class_exists('GSX_v2')) {
                require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_v2.php';
            }

            self::$gsx_v2 = new GSX_v2();
        }

        return self::$gsx_v2;
    }

    public static function getCertif($soldTo, &$errors = array())
    {
        if (!isset(self::$certifs[self::$mode][$soldTo])) {
            $errors[] = 'Aucun certificat pour le soldTo ' . $soldTo;
            return '';
        }

        $certif_file_path = DOL_DOCUMENT_ROOT . '/bimpapple/certif/' . self::$certifs[self::$mode][$soldTo];
        if (!file_exists($certif_file_path)) {
            $errors[] = 'Le fichier de certificat "' . $certif_file_path . '" n\'existe pas';
            return '';
        }

        return $certif_file_path;
    }

    public static function fetchReservationsSummay($soldTo, $shipTo, $productCode, $from, $to, &$errors = array(), &$debug = '')
    {
        // GSX V2: 
        if (self::useGsxV2()) {
            if (!(int) $soldTo || !(int) $shipTo) {
                return array();
            }

            $gsx_v2 = self::getGsxV2();

            if (!$gsx_v2->logged) {
                $errors[] = 'Non connecté à GSX';
                return array();
            }

            $gsx_v2->setSoldTo($soldTo);
            $gsx_v2->resetErrors();

            $reservations = $gsx_v2->fetchReservationsSummary($shipTo, $from, $to, $productCode);

            $curl_errors = $gsx_v2->errors['curl'];

            foreach ($curl_errors as $error) {
                if (isset($error['code']) && $error['code'] == 'RESERVATIONS_NOT_FOUND') {
                    return array();
                }
            }

            $errors = BimpTools::merge_array($errors, $gsx_v2->getErrors());

            return $reservations;
        }

        // Ancienne méthode: 

        $certif_file_path = self::getCertif($soldTo, $errors);

        if (!$certif_file_path) {
            return array();
        }

        $soldTo = BimpTools::addZeros($soldTo, 10);
        $shipTo = BimpTools::addZeros($shipTo, 10);

        $url = self::$base_urls[self::$mode] . 'reservation/search';

        $debug .= '<h4>Requête Fetch Reservations Summary</h4>';
        $debug .= 'URL: <b>' . $url . '</b>';

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

            require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_Const.php';
            $certInfo = GSX_Const::getCertifInfo($soldTo);

            curl_setopt($ch, CURLOPT_SSLKEY, $certInfo['pathKey']);
            curl_setopt($ch, CURLOPT_SSLCERT, $certInfo['path']);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
//            curl_setopt($ch, CURLOPT_SSLCERT, $certif_file_path);
//            curl_setopt($ch, CURLOPT_SSLCERTPASSWD, '');
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
                $errors[] = 'Echec requête CURL <br/>URL: ' . $url . '<br/>Params: <pre>' . print_r($params, 1) . '</pre>';
                if ($error_msg) {
                    $errors[] = 'ERR CURL: ' . $error_msg;
                }
            } else {
                return json_decode($data, 1);
            }
        }

        return array();
    }

    public static function fetchReservation($soldTo, $shipTo, $reservation_id, &$errors = array(), &$debug = '')
    {
        // GSX V2: 
        if (self::useGsxV2()) {
            $gsx_v2 = self::getGsxV2();

            if (!$gsx_v2->logged) {
                $errors[] = 'Non connecté à GSX';
                return array();
            }

            $gsx_v2->soldTo = BimpTools::addZeros($soldTo, GSX_v2::$numbersNumChars);
            $gsx_v2->shipTo = BimpTools::addZeros($shipTo, GSX_v2::$numbersNumChars);
            $gsx_v2->resetErrors();

            $reservation = $gsx_v2->fetchReservation($shipTo, $reservation_id);
            $errors = BimpTools::merge_array($errors, $gsx_v2->getErrors());

            return $reservation;
        }

        // Ancienne méthode: 

        $certif_file_path = self::getCertif($soldTo, $errors);

        if (!$certif_file_path) {
            return array();
        }

        $soldTo = BimpTools::addZeros($soldTo, 10);
        $shipTo = BimpTools::addZeros($shipTo, 10);

        $url = self::$base_urls[self::$mode] . 'reservation/' . $shipTo . '/' . $reservation_id;

        $debug .= '<h4>Requête Fetch Reservation</h4>';
        $debug .= 'URL: <b>' . $url . '</b>';

        $ch = curl_init($url);

        if (!$ch) {
            $errors[] = 'Echec connexion CURL';
        } else {
            $headers = array(
                'Accept: application/json',
                'Content-type: application/json',
                'X-PARTNER-SOLDTO: ' . $soldTo
            );

//            $this->DebugData($headers, 'CURL REQUEST HEADER');
            require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_Const.php';
            $certInfo = GSX_Const::getCertifInfo($soldTo);

            curl_setopt($ch, CURLOPT_SSLKEY, $certInfo['pathKey']);
            curl_setopt($ch, CURLOPT_SSLCERT, $certInfo['path']);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
//            curl_setopt($ch, CURLOPT_SSLCERT, $certif_file_path);
//            curl_setopt($ch, CURLOPT_SSLCERTPASSWD, '');
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

    public static function fetchAvailableSlots($soldTo, $shipTo, $product_code, &$errors = array(), &$debug = '')
    {
        // GSX V2: 
        if (self::useGsxV2()) {
            $gsx_v2 = self::getGsxV2();

            if (!$gsx_v2->logged) {
                $errors[] = 'Non connecté à GSX';
                return array();
            }

            $gsx_v2->soldTo = BimpTools::addZeros($soldTo, GSX_v2::$numbersNumChars);
            $gsx_v2->shipTo = BimpTools::addZeros($shipTo, GSX_v2::$numbersNumChars);
            $gsx_v2->resetErrors();

            $slots = $gsx_v2->fetchAvailableSlots($shipTo, $product_code);
            $errors = BimpTools::merge_array($errors, $gsx_v2->getErrors());

            return $slots;
        }

        // Ancienne méthode: 

        $certif_file_path = self::getCertif($soldTo, $errors);

        if (!$certif_file_path) {
            return array();
        }

        $soldTo = BimpTools::addZeros($soldTo, 10);
        $shipTo = BimpTools::addZeros($shipTo, 10);

        $url = self::$base_urls[self::$mode] . 'availableSlots/' . $shipTo . '/' . $product_code;

        $debug .= '<h4>Requête Fetch Available Slots</h4>';
        $debug .= 'URL: <b>' . $url . '</b>';

        $ch = curl_init($url);

        if (!$ch) {
            $errors[] = 'Echec connexion CURL';
        } else {
            $headers = array(
                'Accept: application/json',
                'Content-type: application/json',
                'X-PARTNER-SOLDTO: ' . $soldTo
            );

//            require_once(DOL_DOCUMENT_ROOT.'/bimpapple/classes/GSX_v2.php');
//            $certInfo = GSX_v2::getCertifInfo($soldTo);
//            curl_setopt($ch, CURLOPT_SSLCERT, $certInfo['path']);
//            curl_setopt($ch, CURLOPT_SSLKEY, $certInfo['pathKey']);
//            curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $certInfo['pass']);


            require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_Const.php';
            $certInfo = GSX_Const::getCertifInfo($soldTo);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSLKEY, $certInfo['pathKey']);
            curl_setopt($ch, CURLOPT_SSLCERT, $certInfo['path']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $data = curl_exec($ch);
            $error_msg = '';

            if (curl_errno($ch)) {
                $error_msg = curl_error($ch);
            }

            curl_close($ch);
            if (stripos($data, '403 Forbidden')) {
                mailSyn2('Probléme GSX', 'dev@bimp.fr', null, 'Recuperation des slots de réservations impossible ' . print_r($data, 1));
            }

            if ($data === false) {
                $errors[] = 'Echec requête CURL';
                if ($error_msg) {
                    $errors[] = 'ERR CURL: ' . $error_msg;
                }
            } else {
                return json_decode($data, 1);
            }
        }

        return array();
    }

    public static function createReservation($soldTo, $shipTo, $params, &$errors = array(), &$debug = '')
    {
        // GSX V2: 
        if (self::useGsxV2()) {
            $gsx_v2 = self::getGsxV2();

            if (!$gsx_v2->logged) {
                $errors[] = 'Non connecté à GSX';
                return array();
            }

            $gsx_v2->soldTo = BimpTools::addZeros($soldTo, GSX_v2::$numbersNumChars);
            $gsx_v2->shipTo = BimpTools::addZeros($shipTo, GSX_v2::$numbersNumChars);
            $gsx_v2->resetErrors();

            $reservations = $gsx_v2->createReservation($shipTo, $params);
            $errors = BimpTools::merge_array($errors, $gsx_v2->getErrors());

            return $reservations;
        }

        // Ancienne méthode: 

        $certif_file_path = self::getCertif($soldTo, $errors);

        if (!$certif_file_path) {
            return array();
        }

        $soldTo = BimpTools::addZeros($soldTo, 10);
        $shipTo = BimpTools::addZeros($shipTo, 10);

        $url = self::$base_urls[self::$mode] . 'reservation';

        $debug .= '<h4>Requête Create Reservation</h4>';
        $debug .= 'URL: <b>' . $url . '</b><br/>';

        $ch = curl_init($url);

        if (!$ch) {
            $errors[] = 'Echec connexion CURL';
        } else {
            $headers = array(
                'Accept: application/json',
                'Content-type: application/json',
                'X-PARTNER-SOLDTO: ' . $soldTo
            );

            $params['shipToCode'] = BimpTools::addZeros($shipTo, 10);

            if (!isset($params['emailLanguageCode'])) {
                $params['emailLanguageCode'] = 'fr_fr';
            }

            if (!isset($params['createdBy'])) {
                $params['createdBy'] = self::$default_tech_id;
            }
            require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_Const.php';
            $certInfo = GSX_Const::getCertifInfo($soldTo);
            curl_setopt($ch, CURLOPT_SSLKEY, $certInfo['pathKey']);
            curl_setopt($ch, CURLOPT_SSLCERT, $certInfo['path']);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
//            curl_setopt($ch, CURLOPT_SSLCERT, $certif_file_path);
//            curl_setopt($ch, CURLOPT_SSLCERTPASSWD, '');
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
                $errors[] = 'Echec requête CURL';
                if ($error_msg) {
                    $errors[] = 'ERR CURL: ' . $error_msg;
                }
            } else {
                $debug .= '<br/><br/>DATA RECUE: <br/>';
                $debug .= $data;
                return json_decode($data, 1);
            }
        }

        return array();
    }

    public static function cancelReservation($soldTo, $shipTo, $reservationId, &$errors = array(), &$debug = '', $params = array())
    {
        // GSX V2: 
        if (self::useGsxV2()) {
            $gsx_v2 = self::getGsxV2();

            if (!$gsx_v2->logged) {
                $errors[] = 'Non connecté à GSX';
                return array();
            }

            $gsx_v2->soldTo = BimpTools::addZeros($soldTo, GSX_v2::$numbersNumChars);
            $gsx_v2->shipTo = BimpTools::addZeros($shipTo, GSX_v2::$numbersNumChars);
            $gsx_v2->resetErrors();

            $result = $gsx_v2->cancelReservation($shipTo, $reservationId);
            $errors = BimpTools::merge_array($errors, $gsx_v2->getErrors());

            return $result;
        }

        // Ancienne méthode: 

        $certif_file_path = self::getCertif($soldTo, $errors);

        if (!$certif_file_path) {
            return array();
        }

        $soldTo = BimpTools::addZeros($soldTo, 10);
        $shipTo = BimpTools::addZeros($shipTo, 10);

        $url = self::$base_urls[self::$mode] . 'reservation';

        $debug .= '<h4>Requête Update Reservation</h4>';
        $debug .= 'URL: <b>' . $url . '</b><br/>';

        $ch = curl_init($url);

        if (!$ch) {
            $errors[] = 'Echec connexion CURL';
        } else {
            $headers = array(
                'Accept: application/json',
                'Content-type: application/json',
                'X-PARTNER-SOLDTO: ' . $soldTo
            );

            $params = BimpTools::overrideArray(array(
                        'cancelReason' => 'CUSTOMER_CANCELLED',
                        'modifiedBy'   => self::$default_tech_id
                            ), $params);

            $params['reservationId'] = $reservationId;
            $params['currentStatus'] = 'CANCELLED';
            $params['shipToCode'] = BimpTools::addZeros($shipTo, 10);

            require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_Const.php';
            $certInfo = GSX_Const::getCertifInfo($soldTo);

            curl_setopt($ch, CURLOPT_SSLKEY, $certInfo['pathKey']);
            curl_setopt($ch, CURLOPT_SSLCERT, $certInfo['path']);

            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
//            curl_setopt($ch, CURLOPT_SSLCERT, $certif_file_path);
//            curl_setopt($ch, CURLOPT_SSLCERTPASSWD, '');
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
                $errors[] = 'Echec requête CURL';
                if ($error_msg) {
                    $errors[] = 'ERR CURL: ' . $error_msg;
                }
            } else {
                $debug .= '<br/><br/>DATA RECUE: <br/>';
                $debug .= $data;
                return json_decode($data, 1);
            }
        }

        return null;
    }
}
