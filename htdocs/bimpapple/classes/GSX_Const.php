<?php

class GSX_Const
{

    // Toutes les données en dur pour GSX V2 sont à mettre ici: 
    public static $mode = 'test'; // test ou prod
    public static $debug_mode = true;
    public static $log_errors = true;
    public static $numbersNumChars = 10;
    public static $test_baseUrl = 'https://partner-connect-uat.apple.com/gsx/api/';
    public static $prod_baseUrl = '';
    public static $test_userAppleId = 'olys_tech_aprvlreqrd@olys.com';
    public static $test_soldTo = 897316;
    public static $test_shipTo = 897316;
    public static $requests_urls = array(
        'authenticate' => 'authenticate/token'
    );

    public static function getCertifInfo($soldTo)
    {
        $soldTo = intval($soldTo);
        $tabCert = array(
            897316 => array(0 => array('testCertif.pem', '', 'test.key'), 1 => array('prod.pem', '')),
            579256 => array(1 => array('proditrb.pem', ''), 0 => array('privatekey.nopass.pem', '')));

        $pass = $certif = $pathKey = '';
        if (isset($tabCert[$soldTo])) {
            $prod = 0;
            if (isset($tabCert[$soldTo][$prod][0])) {
                $certif = $tabCert[$soldTo][$prod][0];
            }
            if (isset($tabCert[$soldTo][$prod][1])) {
                $pass = $tabCert[$soldTo][$prod][1];
            }
            if (isset($tabCert[$soldTo][$prod][2])) {
                $pathKey = $tabCert[$soldTo][$prod][2];
            }
        }

        if (empty($certif)) {
            self::logError('Aucun certificat trouvé pour le soldTo "' . $soldTo . '"');
            return 0;
        }
        $folder = DOL_DOCUMENT_ROOT . '/bimpapple/certif/api2/';
        return array("pass" => $pass, "pathKey" => $folder . $pathKey, 'path' => $folder . $certif);
    }
}
