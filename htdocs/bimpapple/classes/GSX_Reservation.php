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

            $params['shipToCode'] = BimpTools::addZeros($shipTo, 10);

            if (!isset($params['emailLanguageCode'])) {
                $params['emailLanguageCode'] = 'fr_fr';
            }

            if (!isset($params['createdBy'])) {
                // ====> Voir comment récupérer techId par défaut pour chaque shipTo.
//                $sql = 'SELECT ef.apple_techid FROM ' . MAIN_DB_PREFIX.'user_extrafields ef';
//                $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX.'user u ON i.rowid = ef.fk_object';
//                $sql .= ' WHERE ef.apple_shipto = \''.$shipTo.'\' AND ef.apple_techid IS NOT NULL AND ef.apple_techid != \'\'';
//                $sql .= ' AND u.statut = 1';
//                $sql .= ' ORDER BY u.rowid ASC'

                $techId = 'FRA0326R';

                $params['createdBy'] = $techId;
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
                return json_decode($data, 1);
            }
        }

        return array();
    }
}
