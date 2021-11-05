<?php

require_once __DIR__ . '/GSX_Const.php';

class GSX_v2 extends GSX_Const
{

    protected static $instance = null;
    protected $ch;
    public $baseUrl = '';
    public $appleId = '';
    public $applePword = '';
    public $soldTo = '';
    public $shipTo = '';
    public $certPath = '';
    public $certPathKey = '';
    public $certPword = '';
    public $acti_token = '';
    public $auth_token = '';
    public $logged = false;
    public $errors = array(
        'init' => array(),
        'curl' => array()
    );
    public $n = 0;

    public function __construct($shipTo = '')
    {
        global $user;

        $this->baseUrl = self::$urls['base'][self::$mode];

        switch (self::$mode) {
            case 'test':
                $this->appleId = self::$test_ids['apple_id'];
                $this->applePword = self::$test_ids['apple_pword'];
                $this->shipTo = BimpTools::addZeros(self::$test_ids['ship_to'], self::$numbersNumChars);
                $this->soldTo = BimpTools::addZeros(self::$test_ids['sold_to'], self::$numbersNumChars);
                break;

            case 'prod':
                if (isset($user->array_options['options_apple_id']) && (string) $user->array_options['options_apple_id']) {
                    $this->appleId = BimpTools::addZeros($user->array_options['options_apple_id'], self::$numbersNumChars);
                } else {
                    $this->appleId = self::$default_ids['apple_id'];
                }
                if (isset($user->array_options['options_apple_pword']) && (string) $user->array_options['options_apple_pword']) {
                    $this->applePword = $user->array_options['options_apple_pword'];
                } else {
                    $this->applePword = self::$default_ids['apple_pword'];
                }

                if ($shipTo) {
                    $this->shipTo = BimpTools::addZeros($shipTo, self::$numbersNumChars);
                } elseif (isset($user->array_options['options_apple_shipto']) && (string) $user->array_options['options_apple_shipto']) {
                    $this->shipTo = BimpTools::addZeros($user->array_options['options_apple_shipto'], self::$numbersNumChars);
                } else {
                    $this->shipTo = BimpTools::addZeros(self::$default_ids['ship_to'], self::$numbersNumChars);
                }

                $this->soldTo = BimpTools::addZeros(self::$default_ids['sold_to'], self::$numbersNumChars);
                break;
        }

        $certInfo = self::getCertifInfo($this->soldTo);

        $this->certPath = $certInfo['path'];
        $this->certPathKey = $certInfo['pathKey'];
        $this->certPword = $certInfo['pass'];

        if (isset($user->array_options['options_gsx_acti_token']) && (string) $user->array_options['options_gsx_acti_token']) {
            $this->acti_token = $user->array_options['options_gsx_acti_token'];
        }

        if (isset($_REQUEST['gsx_auth_token']) && $_REQUEST['gsx_auth_token'] != '') {
            $this->auth_token = $_REQUEST['gsx_auth_token'];
        } elseif (isset($user->array_options['options_gsx_auth_token']) && (string) $user->array_options['options_gsx_auth_token']) {
            $this->auth_token = $user->array_options['options_gsx_auth_token'];
        }

        // On considère qu'on est loggé si un athentication token est en cours.
        // On délog en cas d'échec de requête avec code "UNAUTHORIZED"
        if ($this->auth_token) {
            $this->logged = true;
        } elseif ($this->acti_token) {
            $this->authenticate();
        }
    }

    public function __destruct()
    {
        if ($this->ch) {
            curl_close($this->ch);
        }
    }

    public static function getInstance($force_new = false, $shipTo = '')
    {
        if (is_null(self::$instance) || $force_new) {
            self::$instance = new GSX_v2($shipTo);
        }

        return self::$instance;
    }

    // Gestion du login: 

    public function setActivationToken($token)
    {
        if (!(string) $token) {
            return array(
                'Token absent'
            );
        }
        $this->acti_token = $token;

        if ($this->reauthenticate()) {
            $this->saveToken('acti', $token);
            return array();
        }

        return $this->getErrors();
    }

    public function authenticate()
    {
        if ($this->logged) {
            return 1;
        }

        if (!$this->acti_token) {
            $this->initError('Token d\'activation absent');
            return 0;
        }

        $this->displayDebug('Tentative d\'authentification (token ' . $this->acti_token . ') : ');

        $result = $this->exec('authenticate', array(
            'userAppleId' => $this->appleId,
            'authToken'   => $this->acti_token
        ));

        if (isset($result['authToken'])) {
            $this->displayDebug('OK (Auth token ' . $result['authToken'] . ')');
            $this->saveToken('auth', $result['authToken']);
//            global $user, $langs;
//            mailSyn2('auth GSX', 'tommy@bimp.fr', null, $user->getFullName($langs).' id : '.$this->appleId.' auth OK' . date('l jS \of F Y h:i:s A'));
            $this->logged = true;
            return 1;
        }

        $this->displayDebug('échec');
        $this->initError('Echec authentification (token ' . $this->acti_token . ')');

        if ($this->appleId == 'admin.gle@bimp.fr') {
            global $gsx_logout_mail_send;

            if (!$gsx_logout_mail_send) {
                global $user, $langs;
                mailSyn2('auth GSX bad', 'tommy@bimp.fr, f.martinez@bimp.fr', null, $user->getFullName($langs) . ' id : ' . $this->appleId . ' auth bad' . date('l jS \of F Y h:i:s A'));
                BimpTools::sendSmsAdmin('Attention Compte admin.gle déconnecté de GSX');
                $gsx_logout_mail_send = true;
            }
        }

        $this->logged = false;
        $this->saveToken('acti', '');

        return 0;
    }

    public function reauthenticate()
    {
        if ($this->auth_token) {
            $this->saveToken('auth', '');
        }

        $this->logged = false;

        return $this->authenticate();
    }

    public function saveToken($type, $token)
    {
        $field = '';
        switch ($type) {
            case 'acti':
                $this->acti_token = $token;
                $field = 'gsx_acti_token';
                break;

            case 'auth':
                $this->auth_token = $token;
                $field = 'gsx_auth_token';
                break;
        }

        if ($field) {
            if (self::$mode === 'prod') {
                $is_default = ($this->appleId === self::$default_ids['apple_id']);
                BimpCache::getBdb()->update('user_extrafields', array(
                    $field => $token
                        ), '`apple_id` = \'' . $this->appleId . '\'' . ($is_default ? ' OR `apple_id` IS NULL OR `apple_id` = \'\'' : ''));
            } else {
                BimpCache::getBdb()->update('user_extrafields', array(
                    $field => $token
                        ), '1');
            }
        }
    }

    // Traitements des requêtes CURL: 

    protected function init($request_name, &$error = '', $url_params = array(), $extra = array())
    {
        if ($this->ch) {
            curl_close($this->ch);
        }

        if (!(string) $request_name) {
            $error = 'Nom de la requête absent';
            return 0;
        }

        if (!isset(self::$urls['req'][$request_name])) {
            $error = 'URL de la requête "' . $request_name . '" non définie';
            return 0;
        }

        if (!$this->auth_token && $request_name !== 'authenticate') {
            $error = 'Token d\'authentification absent';
            return 0;
        }

        if (!$this->soldTo) {
            $error = 'Numéro soldTo absent';
            return 0;
        }

        if (!$this->shipTo) {
            $error = 'Numéro shipTo absent';
            return 0;
        }

        $url = $this->baseUrl . self::$urls['req'][$request_name];

        if (isset($extra['url_params']) && is_array($extra['url_params'])) {
            foreach ($extra['url_params'] as $name => $value) {
                $url_params[$name] = $value;
            }
        }

        if (!empty($url_params)) {
            $url .= '?';
            $fl = true;
            foreach ($url_params as $name => $value) {
                if (!$fl) {
                    $url .= '&';
                } else {
                    $fl = false;
                }
                $url .= $name . '=' . $value;
            }
        }

        $this->displayDebug($url);

        $this->ch = curl_init($url);

        if (!$this->ch) {
            return 0;
        }

        $headers = array(
            'Accept: application/json' . (in_array($request_name, self::$fileContentRequests) ? ',application/octet-stream' : ''),
            'Content-Type: application/json',
//            'Accept-Language: fr_FR', // OLD
            'X-Apple-Client-Locale: fr-FR',
            'X-Apple-SoldTo: ' . $this->soldTo,
            'X-Apple-ShipTo: ' . $this->shipTo
        );

        if ($request_name !== 'authenticate') {
            $headers[] = 'X-Apple-Auth-Token: ' . $this->auth_token;
            $headers[] = 'X-Apple-Service-Version: v2';
        }

        if (isset($extra['headers']) && is_array($extra['headers'])) {
            foreach ($extra['headers'] as $extra_header) {
                $headers[] = $extra_header;
            }
        }

        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($this->ch, CURLOPT_SSLCERT, $this->certPath);
        curl_setopt($this->ch, CURLOPT_SSLKEY, $this->certPathKey);

        curl_setopt($this->ch, CURLOPT_SSLCERTPASSWD, $this->certPword);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($this->ch, CURLOPT_HEADER, true);

        return 1;
    }

    public function exec($request_name, $params, &$response_headers = array(), $extra = array())
    {
        if (!(string) $request_name) {
            $this->curlError('(inconnue)', 'Nom de la requête absent', '', true);
            return false;
        }

        if (!$this->logged && $request_name !== 'authenticate') {
            if (!$this->authenticate()) {
                return false;
            }
        }

        $this->displayDebug('Tentative d\'éxécution de la requête "' . $request_name . '"');

        $url_params = array();
        if (in_array($request_name, self::$getRequests)) {
            $url_params = $params;
            $params = array();
        }

        $error = '';
        if (!$this->init($request_name, $error, $url_params, $extra)) {
            $this->curlError($request_name, 'Echec de l\'initialisation de CURL' . ($error ? ' - ' . $error : ''));
            return false;
        }

        if (count($params)) {
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, json_encode($params));
        }

        if (in_array($request_name, self::$getRequests)) {
            curl_setopt($this->ch, CURLOPT_HTTPGET, true);
        }

        $data = curl_exec($this->ch);

        $response_code = (int) curl_getinfo($this->ch, CURLINFO_RESPONSE_CODE);

        if (!$data) {
            $this->curlError($request_name, 'Aucune réponse reçue - Code HTTP: ' . $response_code);
            if (self::$log_requests || self::$debug_mode || BimpDebug::isActive()) {
                $information = curl_getinfo($this->ch);

                $infos = "<h4>Header REQUEST: </h4><br/>" . str_replace("\n", "<br/>", $information['request_header']);
                $infos .= "<h4>Body REQUEST:  </h4><br/>" . str_replace("\n", "<br/>", '<pre>' . print_r($params, 1)) . "</pre><br/><br/>";
                $infos .= '<b>JSON: </b></h4><br/><pre>' . json_encode($params) . '</pre><br/><br/>';
                $infos .= '<span class="danger">AUCUNE REPONSE</span><br/>';
                $infos .= '<b>Code réponse: </b>' . $response_code . '<br/><br/>';

                if (self::$log_requests) {
                    dol_syslog(str_replace('<br/>', "\n", str_replace('Array', "", $infos)), 3);
                }

                if (self::$debug_mode) {
                    echo '<br/><br/>' . $infos;
                }

                if (BimpDebug::isActive()) {
                    BimpDebug::addDebug('gsx', 'Requête "' . $request_name . '"', $infos);
                }
            }
            return false;
        }

        $header_size = curl_getinfo($this->ch, CURLINFO_HEADER_SIZE);
        $headers = substr($data, 0, $header_size);
        $data = substr($data, $header_size);

        foreach (explode("\r\n", $headers) as $header) {
            if (preg_match('/^([a-zA-Z0-9\-]+): (.+)$/', $header, $matches)) {
                $response_headers[$matches[1]] = $matches[2];
            }
        }

        if (!in_array($request_name, self::$fileContentRequests) || $response_code != 200) {
            $data = json_decode($data, 1);
        }

        if (self::$log_requests || self::$debug_mode || BimpDebug::isActive()) {
            $information = curl_getinfo($this->ch);

            $infos = "<h4>Header REQUEST:</h4><br/>" . str_replace("\n", "<br/>", $information['request_header']);
            $infos .= "<h4>Body REQUEST:</h4><br/>" . str_replace("\n", "<br/>", '<pre>' . print_r($params, 1)) . '</pre><br/><br/>';
            $infos .= '<b>JSON: </b></h4><br/><pre>' . json_encode($params) . '</pre><br/><br/>';
            $infos .= "<h4>Header RESPONSE:</h4><br/>" . str_replace("\n", "<br/>", '<pre>' . print_r($response_headers, 1)) . '</pre><br/>';
            $infos .= '<b>Code réponse: </b>' . $response_code . '<br/><br/>';

            if (is_array($data)) {
                $infos .= "<h4>Body RESPONSE:</h4><br/>" . str_replace("\n", "<br/>", '<pre>' . print_r($data, 1)) . '</pre>';
                $infos .= '<b>JSON: </b></h4><br/><pre>' . json_encode($data) . '</pre><br/><br/>';
            }

            if (self::$log_requests) {
                dol_syslog(str_replace('<br/>', "\n", str_replace('Array', "", $infos)), 3);
            }

            if (self::$debug_mode) {
                echo '<br/><br/>' . $infos;
            }

            if (BimpDebug::isActive()) {
                BimpDebug::addDebug('gsx', 'Requête "' . $request_name . '"', $infos);
            }
        }

        if (is_array($data) && isset($data['errors']) && count($data['errors'])) {
            $curl_errors = array();
            foreach ($data['errors'] as $error) {
                $msg = '';
                switch ($error['code']) {
                    case 'SESSION_IDLE_TIMEOUT':
                        // On tente une nouvelle authentification: 
                        if ($request_name !== 'authenticate') {
                            $this->displayDebug('Non authentifié');
                            if ($this->reauthenticate()) {
                                return $this->exec($request_name, $params, $response_headers, $extra_headers);
                            }
                            return false;
                        } else {
                            return false;
                        }

                    case 'AUTH_TOKEN_STILL_ACTIVE':
                    default:
                        $msg = $error['message'];
                        $curl_errors[] = $msg . ($error['code'] ? ' (Code: ' . $error['code'] . ')' : '');
                        $this->curlError($request_name, BimpTools::getArrayValueFromPath($error, 'message', 'Erreur inconnue'), BimpTools::getArrayValueFromPath($error, 'code', ''));
                        break;
                }
            }
            $data = false;
        }

        return $data;
    }

    // Requêtes - Equipements: 

    public function productDetailsBySerial($serial, $requestType = 'repair')
    {
        return $this->exec('productDetails', array(
                    'requestType' => 'REPAIR',
                    'device'      => array(
                        'id' => $this->traiteSerialApple($serial)
                    )
        ));
    }

    public function serialEligibility($serial)
    {
        return $this->exec('repairEligibility', array(
                    'device' => array(
                        'id' => $this->traiteSerialApple($serial)
                    )
        ));
    }

    // Requêtes - Parts: 

    public function partsSummaryBySerial($serial)
    {
        return $this->exec('partsSummary', array(
                    'devices' => array(
                        array(
                            'id' => $this->traiteSerialApple($serial)
                        )
                    )
        ));
    }

    public function partsSummaryBySerialAndIssue($serial, BS_Issue $issue = null)
    {
        $params = array(
            'devices' => array(
                array(
                    'id' => $this->traiteSerialApple($serial)
                )
            )
        );

        if (BimpObject::objectLoaded($issue) && (string) $issue->getData('category_code')) {
            $params['componentIssues'] = array(
                array(
                    'componentCode'   => $issue->getData('category_code'),
                    'reproducibility' => $issue->getData('reproducibility'),
                    'priority'        => 1,
                    'type'            => $issue->getData('type'),
                    'issueCode'       => $issue->getData('issue_code'),
                    'order'           => 1
                )
            );
        }

        return $this->exec('partsSummary', $params);
    }

    public function getIssueCodesBySerial($serial)
    {
        return $this->exec('componentIssue', array(
                    'device' => array(
                        'id' => $this->traiteSerialApple($serial)
                    )
        ));
    }

    // Requêtes - Repairs: 

    public function repairSummaryByIdentifier($identifier, $identifier_type)
    {
        $params = array();

        switch ($identifier_type) {
            case 'serial':
                $params['device'] = array(
                    'id' => $this->traiteSerialApple($identifier)
                );
                break;

            case 'repairId':
                $params['repairIds'] = array($identifier);
                break;
        }

        return $this->exec('repairSummary', $params);
    }

    public function repairDetails($repairId)
    {
        return $this->exec('repairDetails', array(
                    'repairId' => $repairId
        ));
    }

    public function repairUpdateStatus($repairId, $statusCode)
    {
        return $this->exec('repairUpdate', array(
                    'repairId'     => $repairId,
                    'repairStatus' => $statusCode
        ));
    }

    public function repairQestions($serial, $repairType, $issues, $parts, $coverageOption = '', $consumerLaw = '')
    {
        $params = array(
            'device'     => array(
                'id' => $this->traiteSerialApple($serial)
            ),
            'repairType' => $repairType
        );

        if ($coverageOption) {
            $params['coverageOption'] = $coverageOption;
        }

        if ($consumerLaw) {
            $params['consumerLaw'] = $consumerLaw;
        }

        if (isset($issues) && !empty($issues)) {
            $params['componentIssues'] = $issues;
        }

        if (isset($parts) && !empty($parts)) {
            $params['parts'] = $parts;
        }

        return $this->exec('repairQuestions', $params);
    }

    // Reqêtes - Diagnostiques: 

    public function diagnosticSuites($serial)
    {
        return $this->exec('diagnosticSuites', array(
                    'deviceId' => $this->traiteSerialApple($serial)
        ));
    }

    public function runDiagnostic($serial, $suiteId)
    {
        return $this->exec('diagnosticTest', array(
                    'diagnostics' => array(
                        'suiteId' => (string) $suiteId
                    ),
                    'device'      => array(
                        'id' => $this->traiteSerialApple($serial)
                    )
        ));
    }

    public function diagnosticStatus($serial)
    {
        return $this->exec('diagnosticStatus', array(
                    'device' => array(
                        'id' => $this->traiteSerialApple($serial)
                    )
        ));
    }

    public function diagnosticsLookup($serial)
    {
        return $this->exec('diagnosticsLookup', array(
                    'device' => array(
                        'id' => $this->traiteSerialApple($serial)
                    )
        ));
    }

    // Requêtes - Retours groupés:

    public function getPartsPendingReturnsForShipTo($shipto = '')
    {
        if (self::$mode === 'test') {
            $shipto = BimpTools::addZeros('897316', 10);
        }

        if (!$shipto) {
            return array();
        }

        $shipto = BimpTools::addZeros($shipto, 10);

        return $this->exec('returnsLookup', array(
                    'returnStatusType' => 'PENDING',
                    'shipTo'           => $shipto
        ));
    }

    public function createReturn($shipTo, $shipmentDetails, $parts)
    {
        if (self::$mode == 'test') {
            $shipTo = self::$test_ids['ship_to'];
        }

        $params = array(
            'shipTo'          => BimpTools::addZeros($shipTo, 10),
            'shipmentDetails' => array(
                'packageMeasurements' => array(
                    'length' => BimpTools::getArrayValueFromPath($shipmentDetails, 'length', 0),
                    'width'  => BimpTools::getArrayValueFromPath($shipmentDetails, 'width', 0),
                    'height' => BimpTools::getArrayValueFromPath($shipmentDetails, 'height', 0),
                    'weight' => BimpTools::getArrayValueFromPath($shipmentDetails, 'weight', 0)
                )
            ),
            'parts'           => $parts
        );

        if (isset($shipmentDetails['notes']) && (string) $shipmentDetails['notes']) {
            $params['shipmentDetails']['notes'] = (string) $shipmentDetails['notes'];
        }

        if (isset($shipmentDetails['carrierCode']) && (string) $shipmentDetails['carrierCode']) {
            $params['shipmentDetails']['carrierCode'] = (string) $shipmentDetails['carrierCode'];
        }

        if (isset($shipmentDetails['trackingNumber']) && (string) $shipmentDetails['trackingNumber']) {
            $params['shipmentDetails']['trackingNumber'] = (string) $shipmentDetails['trackingNumber'];
        }

        return $this->exec('returnsManage', $params);
    }

    public function updateReturn($shipTo, $bulkReturId, $parts = array(), $shipmentDetails = array())
    {
        if (self::$mode == 'test') {
            $shipTo = self::$test_ids['ship_to'];
        }

        $params = array(
            'bulkReturn' => $bulkReturId,
            'shipTo'     => BimpTools::addZeros($shipTo, 10)
        );

        if (!empty($shipmentDetails)) {
            $params['shipmentDetails'] = array();

            if (isset($shipmentDetails['length'])) {
                $params['shipmentDetails']['packageMeasurements']['length'] = $shipmentDetails['length'];
            }
            if (isset($shipmentDetails['width'])) {
                $params['shipmentDetails']['packageMeasurements']['width'] = $shipmentDetails['width'];
            }
            if (isset($shipmentDetails['height'])) {
                $params['shipmentDetails']['packageMeasurements']['height'] = $shipmentDetails['height'];
            }
            if (isset($shipmentDetails['weight'])) {
                $params['shipmentDetails']['packageMeasurements']['weight'] = $shipmentDetails['weight'];
            }

            if (isset($shipmentDetails['notes']) && (string) $shipmentDetails['notes']) {
                $params['shipmentDetails']['notes'] = (string) $shipmentDetails['notes'];
            }

            if (isset($shipmentDetails['carrierCode']) && (string) $shipmentDetails['carrierCode']) {
                $params['shipmentDetails']['carrierCode'] = (string) $shipmentDetails['carrierCode'];
            }

            if (isset($shipmentDetails['trackingNumber']) && (string) $shipmentDetails['trackingNumber']) {
                $params['shipmentDetails']['trackingNumber'] = (string) $shipmentDetails['trackingNumber'];
            }
        }

        if (!empty($parts)) {
            $params['parts'] = $parts;
        }

        return $this->exec('returnsManage', $params);
    }

    public function getBulkReturnReport($shipTo, $returnId)
    {
        if (self::$mode === 'test') {
            $shipTo = BimpTools::addZeros('897316', 10);
        }

        if (!$shipTo) {
            return array();
        }

        $shipTo = BimpTools::addZeros($shipTo, 10);

        return $this->exec('returnsLookup', array(
                    'returnStatusType' => 'RETURN_REPORT',
                    'shipTo'           => $shipTo,
                    'bulkReturnId'     => $returnId
        ));
    }

    public function getPartReturnLabel($shipTo, $parts)
    {
        if (self::$mode == 'test') {
            $shipTo = self::$test_ids['ship_to'];
        }

        if ($shipTo) {
            $shipTo = BimpTools::addZeros($shipTo, 10);

            foreach ($parts as $key => $part) {
                $parts[$key]['shipTo'] = $shipTo;
            }
        }

        $response_headers = array();

        return $this->exec('getFile', array(
                    'identifiers' => $parts
                        ), $response_headers, array('url_params' => array('documentType' => 'returnLabel'))
        );
    }

    public function getBulkReturnLabel($shipTo, $bulkReturnId)
    {
        if (self::$mode == 'test') {
            $shipTo = self::$test_ids['ship_to'];
        }

        $shipTo = BimpTools::addZeros($shipTo, 10);

        $response_headers = array();

        return $this->exec('getFile', array(
                    'identifiers' => array(
                        array(
                            'bulkReturnId' => $bulkReturnId,
                            'shipTo'       => $shipTo
                        )
                    )), $response_headers, array('url_params' => array('documentType' => 'bulkReturnLabel'))
        );
    }

    public function getReturnPackingList($shipTo, $bulkReturnId)
    {
        if (self::$mode == 'test') {
            $shipTo = self::$test_ids['ship_to'];
        }

        $shipTo = BimpTools::addZeros($shipTo, 10);

        $response_headers = array();

        return $this->exec('getFile', array(
                    'identifiers' => array(
                        array(
                            'bulkReturnId' => $bulkReturnId,
                            'shipTo'       => $shipTo
                        )
                    )), $response_headers, array('url_params' => array('documentType' => 'returnsPackingList'))
        );
    }

    // Requêtes - Divers:

    public function filesUpload($serial, $files, $module = '')
    {
        $params = array(
            'attachments' => array(),
            'device'      => array(
                'id' => $this->traiteSerialApple($serial)
            )
        );

        foreach ($files as $file_data) {
            $params['attachments'][] = array(
                'name'        => $file_data['name'],
                'sizeInBytes' => $file_data['size']
            );
        }

        $extra = array();

        if ($module) {
            $extra['url_params'] = array(
                'module' => $module
            );
        }
        $headers = array();
        $data = $this->exec('filesUpload', $params, $headers, $extra);

        if (!is_array($data) || empty($data) || !isset($data['attachments'])) {
            return false;
        }

        if (!isset($headers['X-Apple-AppToken']) || !(string) $headers['X-Apple-AppToken']) {
            $msg = 'Echec de l\'envoi des fichiers. Paramètre X-Apple-AppToken non reçu';
            $this->curlError('filesUpload', $msg);
            return false;
        }

        if (!isset($headers['X-Apple-Gigafiles-Cid']) || !(string) $headers['X-Apple-Gigafiles-Cid']) {
            $msg = 'Echec de l\'envoi des fichiers. Paramètre X-Apple-AppToken non reçu';
            $this->curlError('filesUpload', $msg);
            return false;
        }

        $result = array();

        foreach ($data['attachments'] as $attachment) {
            $file_data = array_shift($files);

            $ch = curl_init($attachment['uploadUrl']);
//            
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'X-Apple-AppToken: ' . $headers['X-Apple-AppToken'],
                'X-Apple-Gigafiles-Cid: ' . $headers['X-Apple-Gigafiles-Cid']
            ));

            curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($file_data['path']));

            if (curl_exec($ch) && (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE) === 200) {
                $result[] = array(
                    'name' => $attachment['name'],
                    'id'   => $attachment['id']
                );
            } else {
                $msg = 'Echec de l\'envoi du fichier "' . $attachment['name'] . '"';
                $this->curlError('filesUpload', $msg);
                return false;
            }
        }

        return $result;
    }

    // Gestion des erreurs:

    public function initError($msg, $log_error = false)
    {
        $this->errors['init'][] = $msg;

        if (self::$debug_mode) {
            echo BimpRender::renderAlerts($msg);
        }

        if ($log_error && self::$log_errors) {
            BimpCore::addlog('Erreur init GSX', Bimp_Log::BIMP_LOG_ERREUR, 'gsx', null, array(
                'msg' => $msg
            ));
        }

        if (BimpDebug::isActive()) {
            BimpDebug::addDebug('gsx', '<span class="danger">ERREUR init GSX</span>', BimpRender::renderAlerts($msg));
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
            BimpCore::addlog('Erreur CURL GSX', Bimp_Log::BIMP_LOG_ERREUR, 'gsx', null, array(
                'request' => $request_name,
                'msg'     => $msg,
                'code'    => $code
            ));
        }

        if (BimpDebug::isActive()) {
            BimpDebug::addDebug('gsx', '<span class="danger">ERREUR requête "' . $request_name . '"</span>', BimpRender::renderAlerts($msg . ' (CODE: ' . $code . ')'));
        }
    }

    public function displayDebug($msg)
    {
        if (self::$debug_mode) {
            echo $msg . '<br/>';
        }
    }

    public function getErrors()
    {
        $errors = array();

        if (!empty($this->errors['init'])) {
            $errors[] = BimpTools::getMsgFromArray($this->errors['init'], 'Erreurs d\'initialisation GSX');
        }

        if (!empty($this->errors['curl'])) {
            foreach ($this->errors['curl'] as $error) {
                $msg = '';
                if (isset($error['request']) && (string) $error['request']) {
                    $msg .= 'Echec de l\'éxécution de la requête "' . $error['request'] . '"';
                }
                if (isset($error['msg']) && (string) $error['msg']) {
                    $msg .= ($msg ? ': ' : '') . $error['msg'];
                }
                if (isset($error['code']) && (string) $error['code']) {
                    $msg .= ' (Code: ' . $error['code'] . ')';
                }

                $errors[] = $msg;
                if (isset($error['code']) && $error['code'] == 'UNAUTHORIZED') {
                    $onclick = 'gsxLogOut();';
                    $errors[] = '<a onclick="' . $onclick . '">Cliquez ici pour vous déconnecté de GSX.</a>';
                }
            }
        }

        return $errors;
    }

    public function displayErrors()
    {
        $errors = $this->getErrors();

        if (!empty($errors)) {
            return BimpRender::renderAlerts($errors);
        }

        return '';
    }

    public function resetErrors()
    {
        $this->errors = array(
            'init' => array(),
            'curl' => array()
        );
    }

    public function displayNoLogged($msg = '', $callback = '')
    {
        if (!$msg) {
            $msg = 'Non connecté à GSX. Veuillez vous connecter et réitérer l\'opération';
        }
        $msg .= '<script type="text/javascript">';
        $msg .= 'gsx_open_login_modal($(\'\')' . ($callback ? ', ' . $callback : '') . ');';
        $msg .= '</script>';

        return $msg;
    }

    // Divers: 

    public function traiteSerialApple($serial)
    {
        if (stripos($serial, 'S') === 0) {
            return substr($serial, 1);
        }
        return $serial;
    }
}
