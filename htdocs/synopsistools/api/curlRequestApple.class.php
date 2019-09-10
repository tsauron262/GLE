<?php

class curlRequestApple
{

    public static $numbersNumChars = 10;
    public $soldTo;
    public $shipTo;
    public $pword;
    public $certPath;
    public $requestName;
    protected $ch;

    public function __construct($soldTo, $shipTo)
    {
        if ($this->display_debug) {
            echo '<br/><br/>Init Curl pour shipTo: ' . $shipTo . ' (soldTo: ' . $soldTo . '): <br/>';
        }

        $soldTo = self::addZero($soldTo, self::$numbersNumChars);
        $shipTo = self::addZero($shipTo, self::$numbersNumChars);
        
        $certInfo = self::getCertifInfo($soldTo);
        $this->soldTo = $soldTo;
        $this->shipTo = $shipTo;
        $this->pword = $certInfo['pass'];
        $this->certPath = $certInfo['path'];
        $this->certPathKey = $certInfo['pathKey'];
    }
    
    public static function addZero($str, $nbCarac){
        $str = ''.$str;
        if (strlen($str) < $nbCarac) {
            $n = ($nbCarac - strlen($str));
            while ($n > 0) {
                $str = '0' . $str;
                $n--;
            }
        }
        return $str;
    }
    
    public static function getCertifInfo($soldTo){
        $soldTo = intval($soldTo);
        $tabCert = array(
            897316 => array(0 => array('testCertif.pem', '', 'test.key'), 1 => array('prod.pem', '')),
            579256 => array(1 => array('proditrb.pem', ''), 0 => array('privatekey.nopass.pem', '')));

        $pass = $certif=$pathKey= '';
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
        return array("pass"=>$pass, "pathKey"=>$folder.$pathKey, 'path'=>$folder.$certif);
    }
    
    public static function logError($err){
        
        
        
        echo "erreur : ".$err;
    }
    
    public function logErrorCurl($str){
        self::logError($str);
        echo "<pre>".print_r(curl_getinfo($this->ch),1).print_r(curl_error($this->ch),1);
    }

    public function __destruct()
    {
        if ($this->ch) {
            curl_close($this->ch);
        }
    }

    public function init($url)
    {
        if ($this->ch) {
            curl_close($this->ch);
        }

        $this->ch = curl_init('https://partner-connect-uat.apple.com/gsx/api/'.$url);

        if (!$this->ch) {
            return false;
        }

        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
            'X-Apple-SoldTo: ' . $this->soldTo,
            'X-Apple-ShipTo: ' . $this->shipTo
        );

        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($this->ch, CURLOPT_SSLCERT, $this->certPath);
        curl_setopt($this->ch, CURLOPT_SSLKEY, $this->certPathKey);
        
        
        curl_setopt($this->ch, CURLOPT_SSLCERTPASSWD, $this->pword);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT ,10); 
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 30);
        return true;
    }
    
    public function reqLogin($userAppleId, $token){
        $this->init('authenticate/token');
        $return = $this->exec(array('userAppleId'=>$userAppleId, 'authToken'=>$token));
        return $return;
    }
    

    public function exec($params)
    {
        if (!$this->ch)
            return false;

        if (count($params))
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, json_encode($params));

        $data = curl_exec($this->ch);
        if ($data === false) {
            $this->logErrorCurl('pas de rep');
            return false;
        }
        
        echo 'result : ';
        return json_decode($data);
    }

    public function getLastError()
    {
        if (!$this->ch) {
            return 'Echec de la connexion au service';
        }
        return curl_error($this->ch);
    }
}

class CurlRequestAppleApi extends curlRequestApple
{

    public function __construct($soldTo, $shipTo)
    {
        parent::__construct($soldTo, $shipTo);
    }

    public function fetch($from, $to, $productCode)
    {
        $params = array(
            "shipToCode" => $this->shipTo,
            "fromDate" => $from,
            "toDate" => $to,
            "productCode" => $productCode,
            "currentStatus" => "RESERVED"
        );

        return parent::exec($params);
    }
}

