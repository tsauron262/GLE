<?php

class curlRequestApple
{

    public static $numbersNumChars = 10;
    public $soldTo;
    public $shipTo;
    public $pword;
    public $certPath;
    public $requestName;
    public $errors = array();
    public $tokenApple = '';
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
            echo 'aucune connexion vers GSX';
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
    
    public function reqLogin($userAppleId){
        
        
        if(isset($_SESSION['apple_token']) && $_SESSION['apple_token'] != '')
            $this->tokenApple = $_SESSION['apple_token'];
        elseif(isset($_REQUEST['apple_token']) && $_REQUEST['apple_token'] != '')
            $this->tokenApple = $_REQUEST['apple_token'];
        else{
            echo 'ATTENTION merci d\'inscrire un token dans l\'url';
            return 0;
        }
        
        $this->init('authenticate/token');
        $result = $this->exec(array('userAppleId'=>$userAppleId, 'authToken'=>$this->tokenApple));
        if(isset($result->authToken)){
            $_SESSION['apple_token'] = $result->authToken;
            return 1;
        }
        if(!count($this->errors))
            return 1;//deja logé
        return 0;
    }
    
    public function checkConnexion(){
        $this->init('authenticate/check');
        $result = $this->exec(array());
        print_r($result);
        if($result == '')
            return 1;
        return 0;
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
        $data = json_decode($data);
        if(count($data->errors)){
            foreach($data->errors as $error)
                $this->traiteError($error);
        }
            
        return $data;
    }
    
    public function traiteError($error){
        if($error->code == 'AUTH_TOKEN_STILL_ACTIVE'){
            echo '<br/>Token encore valide : '.$error->message.'<br/>';
            $erreurInc = false;
        }
        elseif($error->code == 'UNAUTHORIZED'){
            echo '<br/>Token invalide : '.$error->message.'<br/>';
            $_SESSION['apple_token'] = '';
            $erreurInc = false;
        }
        else{
            $this->errors[] = $error;
        }
    }
    
    public function printErrors(){
        if(count($this->errors)){
            echo '<pre>'.print_r($this->errors,1);
            if (!$this->ch) {
                echo 'Echec de la connexion au service';
            }
            else
                echo curl_error($this->ch);
        }
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

