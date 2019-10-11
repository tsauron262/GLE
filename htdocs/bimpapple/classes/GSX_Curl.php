<?php

class GSX_Curl
{


    public function checkConnexion()
    {
        $this->init('authenticate/check');
        $result = $this->exec(array());
        print_r($result);
        if ($result == '')
            return 1;
        return 0;
    }

    public function traiteError($error)
    {
        if ($error->code == 'AUTH_TOKEN_STILL_ACTIVE') {
            echo '<br/>Token encore valide : ' . $error->message . '<br/>';
            $erreurInc = false;
        } elseif ($error->code == 'UNAUTHORIZED') {
            echo '<br/>Token invalide : ' . $error->message . '<br/>';
            $_SESSION['apple_token'] = '';
            $erreurInc = false;
        } else {
            $this->errors[] = $error;
        }
    }

    public function printErrors()
    {
        if (count($this->errors)) {
            echo '<pre>' . print_r($this->errors, 1);
            if (!$this->ch) {
                echo 'Echec de la connexion au service';
            } else
                echo curl_error($this->ch);
        }
    }

    public function error($error, $log_error = false)
    {
        if (self::$debug_mode) {
            echo BimpRender::renderAlerts($error);
        }

        $this->errors[] = $error;

        if ($log_error) {
            dol_syslog('[ERREUR GSX]', LOG_ERR);
        }
    }

    // Méthodes statiques: 

    

    public static function logError($err)
    {
        echo "erreur : " . $err;
    }

    public function logErrorCurl($str)
    {
        self::logError($str);

        if (self::$debug_mode) {
            echo '<pre>';
            print_r(curl_getinfo($this->ch), 1);
            print_r(curl_error($this->ch), 1);
            echo '</pre>';
        }
    }

    public static function displayDebug($msg, $class = '')
    {
        if (self::$debug_mode) {
            if ($class) {
                echo BimpRender::renderAlerts($msg, $class);
            }
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
            "shipToCode"    => $this->shipTo,
            "fromDate"      => $from,
            "toDate"        => $to,
            "productCode"   => $productCode,
            "currentStatus" => "RESERVED"
        );

        return parent::exec($params);
    }
}
