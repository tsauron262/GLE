<?php

class GSX_Reservation
{
    public static function fetchAvailableSlots($soldTo, $shipTo, $product_code, $pword, $certif_file_path, &$errors = array(), &$debug = '')
    {
        if (!file_exists($certif_file_path)) {
            $errors[] = 'Le fichier de certificat "' . $certif_file_path . '" n\'existe pas';
            return array();
        }
        
        $soldTo = BimpTools::addZeros($soldTo, 10);
        $shipTo = BimpTools::addZeros($shipTo, 10);

        $url = 'https://asp-partner.apple.com/api/v1/partner/availableSlots/' . $shipTo . '/' . $product_code;

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
