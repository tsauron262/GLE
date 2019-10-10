<?php

require_once __DIR__ . '/GSX_Const.php';

class GSX_v2 extends GSX_Const
{

    protected $ch;
    public $baseUrl = '';
    public $soldTo = '';
    public $shipTo = '';
    public $certPath = '';
    public $certPathKey = '';
    public $token = '';
    public $pword = '';
    public $logged = false;
    public $errors = array(
        'init' => array(),
        'curl' => array()
    );

    public function __construct($soldTo = '')
    {
        global $user;

        if (self::$mode === 'prod') {
            $this->baseUrl = self::$test_baseUrl;
            if (isset($user->array_options['options_apple_id']) && (string) $user->array_options['options_apple_id']) {
                $this->shipTo = BimpTools::addZeros($user->array_options['options_apple_shipto'], self::$numbersNumChars);
            }
            $soldTo = BimpTools::addZeros($soldTo, self::$numbersNumChars);
        } else {
            $this->baseUrl = self::$prod_baseUrl;
            $this->shipTo = BimpTools::addZeros(self::$test_shipTo, self::$numbersNumChars);
            $this->soldTo = BimpTools::addZeros(self::$test_soldTo, self::$numbersNumChars);
        }

        $certInfo = self::getCertifInfo($soldTo);

        $this->soldTo = $soldTo;
        $this->pword = $certInfo['pass'];
        $this->certPath = $certInfo['path'];
        $this->certPathKey = $certInfo['pathKey'];

        if (isset($_SESSION['apple_token']) && $_SESSION['apple_token'] != '') {
            $this->token = $_SESSION['apple_token'];
        } elseif (isset($_REQUEST['apple_token']) && $_REQUEST['apple_token'] != '') {
            $this->token = $_REQUEST['apple_token'];
        }
    }

    public function authenticate($userAppleId)
    {
        if ($this->logged) {
            return 1;
        }

        if (!$this->token) {
            $this->initError('Token absent');
            return 0;
        }

        $result = $this->exec(array(
            'userAppleId' => $userAppleId,
            'authToken'   => $this->token
                ), 'authenticate');

        if (isset($result->authToken)) {
            $this->token = $result->authToken;
            $_SESSION['apple_token'] = $this->token;
            $this->logged = true;
            return 1;
        }

        return 0;
    }

    public function init($request_name, &$error = '')
    {
        if ($this->ch) {
            curl_close($this->ch);
        }

        if (!(string) $request_name) {
            $error = 'Nom de la requête absent';
            return 0;
        }

        if (!isset(self::$requests_urls[])) {
            $error = 'URL de la requête "' . $request_name . '" non définie';
            return 0;
        }

        $this->ch = curl_init(self::$base_url . self::$requests_urls[$request_name]);

        if (!$this->ch) {
            $error = 'Echec de l\'initialisation de CURL';
            return 0;
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
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 30);

        return '';
    }

    public function exec($params, $request_name = '')
    {
        if ($request_name) {
            if (!$this->init($request_name)) {
                $this->curlError($request_name, 'Echec de l\'initialisation de CURL');
                return false;
            }
        } elseif (!$this->ch) {
            $this->curlError('(inconnue)', 'Nom de la requête absent', '', true);
            return false;
        }

        if (count($params)) {
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, json_encode($params));
        }

        $data = curl_exec($this->ch);

        if ($data === false) {
            return false;
        }

        $data = json_decode($data);

        if (isset($data->errors) && count($data->errors)) {
            foreach ($data->errors as $error) {
                $msg = '';
                switch ($error->code) {
                    case 'UNAUTHORIZED':
                        $msg = 'Token invalide - ' . $error->message;
                        $_SESSION['apple_token'] = '';
                        $this->token = '';
                        break;

                    case 'AUTH_TOKEN_STILL_ACTIVE':
                    default:
                        $msg = $error->message;
                        break;
                }

                $this->curlError($request_name, $msg, $error->code);
            }
        }

        return $data;
    }

    public function initError($msg, $log_error = false)
    {
        $this->errors['init'][] = $msg;

        if (self::$debug_mode) {
            echo BimpRender::renderAlerts($msg);
        }

        if ($log_error && self::$log_errors) {
            dol_syslog('[ERREUR INIT GSX]', LOG_ERR);
        }
    }

    public function curlError($request_name, $msg, $code = '', $log_error = false)
    {
        $this->errors['curl'][] = array(
            'request' => $request_name,
            'msg'     => $msg,
            'code'    => $code
        );

        if (self::$debug_mode) {
            echo BimpRender::renderAlerts(BimpTools::getMsgFromArray(array(
                        'Message'     => $msg,
                        'Code erreur' => $code
                            ), 'Echec de l\'éxécution de la requête "' . $request_name . '"'));
        }

        if ($log_error && self::$log_errors) {
            dol_syslog('[ERREUR INIT GSX]', LOG_ERR);
        }
    }

    public function __destruct()
    {
        if ($this->ch) {
            curl_close($this->ch);
        }
    }
}
