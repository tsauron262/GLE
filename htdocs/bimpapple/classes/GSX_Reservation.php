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

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            curl_setopt($ch, CURLOPT_SSLCERT, $certif_file_path);
            curl_setopt($ch, CURLOPT_SSLCERTPASSWD, '');
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
        $certif_file_path = self::getCertif($soldTo, $errors);

        if (!$certif_file_path) {
            return array();
        }

        $soldTo = BimpTools::addZeros($soldTo, 10);
        $shipTo = BimpTools::addZeros($shipTo, 10);

        $url = self::$base_urls[self::$mode] . 'reservation/' . $shipTo.'/' . $reservation_id;

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

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSLCERT, $certif_file_path);
            curl_setopt($ch, CURLOPT_SSLCERTPASSWD, '');
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


            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSLCERT, $certif_file_path);
            curl_setopt($ch, CURLOPT_SSLCERTPASSWD, '');
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

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            curl_setopt($ch, CURLOPT_SSLCERT, $certif_file_path);
            curl_setopt($ch, CURLOPT_SSLCERTPASSWD, '');
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

            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            curl_setopt($ch, CURLOPT_SSLCERT, $certif_file_path);
            curl_setopt($ch, CURLOPT_SSLCERTPASSWD, '');
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
