<?php

abstract class BimpAPI
{
    # Consts: 

    public static $name = '';
    public static $urls_bases = array();
    public static $requests = array();
    public static $unauthenticate_codes = array('SESSION_IDLE_TIMEOUT');
    public static $tokens_types = array();
    public static $default_requests_type = 'POST'; // POST / GET  / FILE / PUT
    public static $default_post_mode = 'json'; // json / string / array
    public static $default_accept = 'application/json';
    public static $include_debug_json = false;
    public static $allow_multiple_instances = false;

    # Caches: 
    public static $instances = array();

    # Objets:
    public $idx = 0;
    public $userAccount = null;
    public $apiObject = null;

    # Vars instance: 
    public $options = array();
    public $params = array();
    public $errors = array();
    public $last_request_errors = array();
    public $debug_content = '';
    public $is_default_user = false;
    
    public static $asUser = true;

    // Gestion instance:

    public function __construct($api_idx = 0, $id_user_account = 0, $debug_mode = false)
    {
        $this->debug_mode = $debug_mode;
        $this->idx = $api_idx;
        $this->apiObject = BimpCache::findBimpObjectInstance('bimpapi', 'API_Api', array(
                    'name'    => static::$name,
                    'api_idx' => $api_idx
                        ), false);

        if ($this->isApiOk($this->errors)) {
            $this->fetchOptions();
            $this->fetchParams();
            $this->errors = BimpTools::merge_array($this->errors, $this->fetchUserAccount($id_user_account));
        }
    }

    public function isApiOk(&$errors = array())
    {
        if (!BimpObject::objectLoaded($this->apiObject)) {
            $errors[] = 'API non installée ou invalide';
            return 0;
        }

        if (!(int) $this->apiObject->getData('active')) {
            $errors[] = 'API désactivée';
            return 0;
        }

        return 1;
    }

    public function isUserAccountOk(&$errors = array())
    {
        if(!static::$asUser)
            return true;
        
        if (!BimpObject::objectLoaded($this->userAccount)) {
            $errors[] = 'Compte utilisateur absent';
            return 0;
        }

        if ((int) $this->userAccount->getData('id_api') !== (int) $this->apiObject->id) {
            $errors[] = 'Le compte utilisateur #' . $this->userAccount->id . ' ne correspondent pas à cette API';
            return 0;
        }

        if ($this->userAccount->getData('mode') != $this->getOption('mode', 'test')) {
            $errors[] = 'Mode incorrect (' . $this->userAccount->displayData('mode', 'default', false, true) . ') pour ce compte utilisateur';
            return 0;
        }
        return 1;
    }

    public function isOk(&$errors = array())
    {
        return (int) ($this->isApiOk($errors) && $this->isUserAccountOk($errors));
    }

    public function setUser($id_user_account)
    {
        if (BimpObject::objectLoaded($this->userAccount) && (int) $id_user_account === (int) $this->userAccount->id) {
            return array();
        }

        return $this->fetchUserAccount($id_user_account);
    }

    protected function fetchOptions()
    {
        if ($this->isApiOk()) {
            $this->options = array(
                'public_name'           => $this->apiObject->getData('title'),
                'mode'                  => $this->apiObject->getData('mode'),
                'log_errors'            => (int) $this->apiObject->getData('log_errors'),
                'log_requests'          => (int) $this->apiObject->getData('log_requests'),
                'notify_defuser_unauth' => (int) $this->apiObject->getData('notify_defuser_unauth'),
                'connect_timeout'       => (int) $this->apiObject->getData('connect_timeout'),
                'timeout'               => (int) $this->apiObject->getData('exec_timeout'),
            );
        } else {
            $this->options = array(
                'mode'                  => 'test',
                'public_name'           => '',
                'log_errors'            => 1,
                'log_requests'          => 0,
                'notify_defuser_unauth' => 0,
                'connect_timeout'       => 10,
                'exec_timeout'          => 30,
            );
        }
    }

    protected function fetchParams()
    {
        $this->params = array();

        if ($this->isApiOk()) {
            $params = $this->apiObject->getChildrenObjects('params');

            foreach ($params as $param) {
                $this->params[$param->getData('name')] = $param->getData('value');
            }
        }
    }

    protected function fetchUserAccount($id_user_account = 0)
    {
        $errors = array();

        if (!$this->isApiOk($errors)) {
            return $errors;
        }

        $this->userAccount = null;

        if ((int) $id_user_account) {
            $userAccount = BimpCache::getBimpObjectInstance('bimpapi', 'API_UserAccount', $id_user_account);

            if (BimpObject::objectLoaded($userAccount)) {
                $this->userAccount = $userAccount;
            } else {
                $errors[] = 'Le compte utilisateur #' . $id_user_account . ' n\'existent plus';
            }
        } else {
            global $user;
            $mode = $this->getOption('mode', 'test');

            if (BimpObject::objectLoaded($user)) {
                $userAccount = BimpCache::findBimpObjectInstance('bimpapi', 'API_UserAccount', array(
                            'id_api' => $this->apiObject->id,
                            'mode'   => $mode,
                            'users'  => array(
                                'part_type' => 'middle',
                                'part'      => '[' . $user->id . ']'
                            )
                                ), true, false);

                if (BimpObject::objectLoaded($userAccount)) {
                    $this->userAccount = $userAccount;
                }
            }

            if (!BimpObject::objectLoaded($this->userAccount)) {
                $id_default_user_account = (int) $this->apiObject->getDefaultUserAccountId($this->options['mode']);

                if ($id_default_user_account) {
                    $userAccount = BimpCache::getBimpObjectInstance('bimpapi', 'API_UserAccount', $id_default_user_account);

                    if (!BimpObject::objectLoaded($userAccount)) {
                        $errors[] = 'Le compte utilisateur par défaut #' . $id_default_user_account . ' n\'existe plus';
                    } else {
                        $this->userAccount = $userAccount;
                        $this->is_default_user = true;
                    }
                }
            }
        }

        if (!count($errors)) {
            $this->isUserAccountOk($errors);
        }

        return $errors;
    }

    public static function getApiInstance($api_name, $api_idx = 0)
    {
        if (!isset(self::$instances[$api_name][$api_idx])) {
            $api_class = ucfirst($api_name) . 'API';
            $final_class = $api_class;

            if (!class_exists($api_class)) {
                if (file_exists(DOL_DOCUMENT_ROOT . '/bimpapi/classes/apis/' . $api_class . '.php')) {
                    require_once DOL_DOCUMENT_ROOT . '/bimpapi/classes/apis/' . $api_class . '.php';
                }
            }

            if (defined('BIMP_EXTENDS_VERSION')) {
                if (file_exists(DOL_DOCUMENT_ROOT . '/bimpapi/extends/version/' . BIMP_EXTENDS_VERSION . '/classes/apis/' . $api_class . '.php')) {
                    $final_class = $api_class . '_ExtVersion';
                    require_once DOL_DOCUMENT_ROOT . '/bimpapi/extends/version/' . BIMP_EXTENDS_VERSION . '/classes/apis/' . $api_class . '.php';
                }
            }

            if (BimpCore::getExtendsEntity() != '') {
                if (file_exists(DOL_DOCUMENT_ROOT . '/bimpapi/extends/entities/' . BimpCore::getExtendsEntity() . '/classes/apis/' . $api_class . '.php')) {
                    $final_class = $api_class . '_ExtEntity';
                    require_once DOL_DOCUMENT_ROOT . '/bimpapi/extends/entities/' . BimpCore::getExtendsEntity() . '/classes/apis/' . $api_class . '.php';
                }
            }

            if (class_exists($final_class)) {
                self::$instances[$api_name][$api_idx] = new $final_class($api_idx);
            } else {
                return null;
            }
        }

        return self::$instances[$api_name][$api_idx];
    }

    public static function getDefaultApiTitle()
    {
        return ucfirst(static::$name);
    }

    public function getParam($param_name, $default_value = null)
    {
        return BimpTools::getArrayValueFromPath($this->params, $param_name, $default_value);
    }

    public function getOption($option_name, $default_value = null)
    {
        return BimpTools::getArrayValueFromPath($this->options, $option_name, $default_value);
    }

    // Gestion Authentification:

    public function isLogged()
    {
        if(!static::$asUser)
            return true;
        if ($this->isUserAccountOk()) {
            return $this->userAccount->isLogged();
        }

        return 0;
    }

    public function saveToken($type, $token, $logged_end = 'no_update')
    {
        $errors = array();

        if (!$type) {
            $errors[] = 'Type de token absent';
        } elseif (!isset(static::$tokens_types[$type])) {
            $errors[] = 'Type de token invalide: "' . $type . '"';
        }

        if ($this->isUserAccountOk($errors)) {
            $errors = $this->userAccount->saveToken($type, $token, $logged_end);
        }

        return $errors;
    }

    public function connect(&$errors = array(), &$warnings = array())
    {
        $errors[] = 'Fonction de connexion non définie';
        return false;
    }

    // Traiements génériques des requêtes: 

    public function setRequest($request_name, $params)
    {
        $errors = array();
        $warnings = array();
        $response_headers = array();

        $logged = $this->isLogged();

        if (!$logged) {
            $logged = $this->connect($errors, $warnings);
        }

        if ($logged) {
            if ($request_name === 'testRequest') {
                $result = $this->testRequest($errors, $warnings);
            } else {
                $params['allow_reconnect'] = 0;
                $result = $this->execCurl($request_name, $params, $errors, $response_headers);
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings,
            'result'   => $result
        );
    }

    public function processRequestResponse($request_name, $response_code, $response_body, $response_headers = array(), &$infos = '', &$errors = array())
    {
        switch ($response_code) {
            case '400':
                $errors[] = 'Requête incorrecte';
                break;

            case '401':
                return 'unauthenticate';

            case '403':
                $errors[] = 'Accès refusé';
                break;

            case '404':
                $errors[] = 'API non trouvée';
                break;

            case '415':
                $errors[] = 'Format de la requête non supoorté';
                break;

            case '500':
                $errors[] = 'Erreur interne serveur';
                break;
        }
        return $response_body;
    }

    public function getRequestFormValues($request_name, $params, &$errors = array())
    {
        return array();
    }

    public function requestFormFieldsOverride($request_name, &$result, $params = array(), &$warnings = array())
    {
        return array();
    }

    public function onRequestFormSuccess($request_name, $result, &$warnings = array())
    {
        
    }

    // Traitements des requêtes CURL:

    public function execCurl($request_name, $params = array(), &$errors = array(), &$response_headers = array(), &$response_code = -1)
    {
        $return = '';

        $request_label = BimpTools::getArrayValueFromPath(static::$requests, $request_name . '/label', $request_name);
        $infos = '<h3>Requête "' . $request_label . ' (' . $request_name . ')"</h3>';

        if (!isset(static::$requests[$request_name])) {
            $errors[] = 'Requête "' . $request_name . '" non définie';
        } elseif ($this->isOk($errors)) {
            $init_params = $params;

            $params = BimpTools::overrideArray(array(
                        'url_params'      => array(),
                        'headers'         => array(),
                        'fields'          => array(),
                        'curl_options'    => array(),
                        'url_base_type'   => BimpTools::getArrayValueFromPath(static::$requests, $request_name . '/url_base_type', 'default'),
                        'url_end'         => BimpTools::getArrayValueFromPath(static::$requests, $request_name . '/url_end', ''),
                        'type'            => BimpTools::getArrayValueFromPath(static::$requests, $request_name . '/type', static::$default_requests_type),
                        'post_mode'       => BimpTools::getArrayValueFromPath(static::$requests, $request_name . '/post_mode', static::$default_post_mode),
                        'header_out'      => BimpTools::getArrayValueFromPath(static::$requests, $request_name . '/header_out', true),
                        'allow_reconnect' => true
                            ), $params, false, true);

            $url = '';
            if (isset(static::$urls_bases[$params['url_base_type']])) {
                $url = BimpTools::getArrayValueFromPath(static::$urls_bases, $params['url_base_type'] . '/' . $this->options['mode'], '');
            } elseif (isset($this->params['url_base_' . $params['url_base_type'] . '_' . $this->options['mode']])) {
                $url = $this->params['url_base_' . $params['url_base_type'] . '_' . $this->options['mode']];
            }
//            echo BimpTools::displayBacktrace();
            if (!$url) {
                $errors[] = 'Base de l\'URL non définie';
            } else {
                if (isset(static::$requests[$request_name]['url'])) {
                    $url .= static::$requests[$request_name]['url'];
                } elseif (isset(static::$requests[$request_name]['urls'][$this->options['mode']])) {
                    $url .= static::$requests[$request_name]['urls'][$this->options['mode']];
                } elseif (is_string(static::$requests[$request_name])) {
                    $url .= static::$requests[$request_name];
                }

                if (!empty($params['url_end'])) {
                    $url .= $params['url_end'];
                }
                if (is_array($params['url_params']) && !empty($params['url_params'])) {
                    $url .= '?' . BimpTools::makeUrlParamsFromArray($params['url_params']);
                }

                // Initalisation:
                $infos .= '<b>Initialisation: </b>' . $url . ': ';
                $ch = curl_init($url);

                if (!$ch) {
                    $infos . '<span class="danger">[ECHEC]</span>';
                    $errors[] = 'Echec de connexion à l\'url "' . $url . '"';
                } else {
                    $infos .= '<span class="success">[OK]</span><br/><br/>';

                    $infos .= '<pre>Paramètres requête: ' . print_r($params, 1) . '</pre><br/><br/>';

                    $headers = BimpTools::overrideArray($this->getDefaultRequestsHeaders($request_name, $errors), $params['headers']);
                    $curl_options = BimpTools::overrideArray($this->getDefaultCurlOptions($request_name, $errors), $params['curl_options']);

                    // Headers: 
                    if (!isset($headers['Content-Type']) || !$headers['Content-Type']) {
                        $request_content_type = BimpTools::getArrayValueFromPath(static::$requests, $request_name . '/content_type', '');

                        if (!$request_content_type) {
                            switch ($params['post_mode']) {
                                case 'string':
                                    $request_content_type = 'application/x-www-form-urlencoded';
                                    break;

                                case 'array':
                                    $request_content_type = 'multipart/form-data';
                                    break;

                                case 'json':
                                default:
                                    $request_content_type = 'application/json';
                                    break;
                            }
                        }

                        $headers['Content-Type'] = $request_content_type;
                    }

                    if (!isset($headers['Accept']) || !$headers['Accept']) {
                        $headers['Accept'] = BimpTools::getArrayValueFromPath(static::$requests, $request_name . '/accept', '');
                        if (!$headers['Accept']) {
                            $headers['Accept'] = static::$default_accept;
                        }
                    }

                    // Options CURL: 
                    $curl_options[CURLOPT_RETURNTRANSFER] = true;
                    $curl_options[CURLOPT_CONNECTTIMEOUT] = $this->options['connect_timeout'];
                    $curl_options[CURLOPT_TIMEOUT] = $this->options['timeout'];

                    if ($params['header_out']) {
                        $curl_options[CURLINFO_HEADER_OUT] = true;
                        $curl_options[CURLOPT_HEADER] = true;
                    }

                    if (!empty($headers)) {
                        $infos .= 'Headers: <pre>' . print_r($headers, 1) . '</pre><br/><br/>';
                        $headers_str = array();
                        foreach ($headers as $header_name => $header_value) {
                            $headers_str[] = $header_name . ': ' . $header_value;
                        }
                        $curl_options[CURLOPT_HTTPHEADER] = $headers_str;
                    }

                    if (is_array($params['fields']) && !empty($params['fields'])) {
                        $fields = '';

                        switch ($params['post_mode']) {
                            case 'json':
                                $fields = json_encode($params['fields']);
                                break;

                            case 'array':
                                $fields = $params['fields'];
                                break;

                            case 'string':
                                $fields = BimpTools::makeUrlParamsFromArray($params['fields']);
                                break;
                        }

                        if (!empty($fields)) {
                            $curl_options[CURLOPT_POSTFIELDS] = $fields;
                        }
                    }

                    switch ($params['type']) {
                        case 'GET':
                            $curl_options[CURLOPT_HTTPGET] = true;
                            break;

                        case 'PUT':
                            $curl_options[CURLOPT_CUSTOMREQUEST] = 'PUT';
                            $curl_options[CURLOPT_HTTPHEADER][] = 'Content-Length: ' . strlen($curl_options[CURLOPT_POSTFIELDS]);
                            break;
                    }

                    if (!empty($curl_options)) {
                        $infos .= 'CURL OPTIONS : <br/>';
                        foreach ($curl_options as $opt_key => $opt_value) {
                            curl_setopt($ch, $opt_key, $opt_value);
                        }
                    }

                    if (!count($errors)) {
                        // Exécution:
                        $response = curl_exec($ch);

                        // Traitement de la réponse: 
                        $response_code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
                        $response_infos = curl_getinfo($ch);

                        if (isset($response_infos['request_header']) && !empty($response_infos['request_header'])) {
                            $infos .= "<h4>Header REQUEST: </h4><br/>" . str_replace("\n", "<br/>", $response_infos['request_header']);
                        }

                        if (!empty($params['fields'])) {
                            $infos .= "<h4>Body REQUEST:  </h4><br/>" . str_replace("\n", "<br/>", '<pre>' . print_r($params['fields'], 1)) . "</pre><br/><br/>";
                            if (static::$include_debug_json) {
                                $infos .= '<b>JSON: </b></h4><br/><pre>' . json_encode($params['fields']) . '</pre><br/><br/>';
                            }
                        }

                        $infos .= '<b>Code réponse: </b>' . $response_code . '<br/><br/>';
                        if (!(string) $response) {
                            $infos .= '<span class="danger">AUCUNE REPONSE</span><br/><br/>';
                            $infos .= 'INFOS CURL : <pre>';
                            $infos .= print_r($response_infos, 1);
                            $infos .= '</pre>';

                            $errors[] = 'Aucune réponse reçue - Code HTTP: ' . $response_code;
                        } else {
                            if ($params['header_out']) {
                                $response_header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                                $response_header_str = substr($response, 0, $response_header_size);
                                $response_body = substr($response, $response_header_size);
                            } else {
                                $response_header_size = 0;
                                $response_header_str = '';
                                $response_body = $response;
                            }

                            if ($response_header_str) {
                                foreach (explode("\r\n", $response_header_str) as $header) {
                                    if (preg_match('/^([a-zA-Z0-9\-]+): (.+)$/', $header, $matches)) {
                                        $response_headers[$matches[1]] = $matches[2];
                                    }
                                }
                            }

                            if (!in_array($params['type'], array('FILE')) || $response_code != 200) {
                                $response_body_decoded = '';

                                if (is_string($response_body)) {
                                    $response_body_decoded = json_decode($response_body, 1);

                                    if (is_array($response_body_decoded)) {
                                        $response_body = $response_body_decoded;
                                    }
                                }

                                $infos .= "<h4>Body RESPONSE:</h4><br/>" . str_replace("\n", "<br/>", '<pre>' . print_r($response_body, 1)) . '</pre>';
                                if (is_array($response_body) && static::$include_debug_json) {
                                    $infos .= '<b>JSON: </b></h4><br/><pre>' . json_encode($response_body) . '</pre><br/><br/>';
                                }
                            }

                            $return = $this->processRequestResponse($request_name, $response_code, $response_body, $response_headers, $infos, $errors);

                            if ($return === 'unauthenticate') {
                                if ($request_name !== 'authenticate' && $params['allow_reconnect']) {
                                    $infos .= 'Tentative de réauthentification. <br/><br/>';

                                    // On tente une connexion: 
                                    $connect_errors = array();
                                    if ($this->connect($connect_errors)) {
                                        $errors = array();
                                        $response_headers = array();
                                        // On relance la requête: 
                                        $init_params['allow_reconnect'] = false;
                                        return $this->execCurl($request_name, $init_params, $errors, $response_headers);
                                    }

                                    $errors[] = BimpTools::getMsgFromArray($connect_errors, 'Echec de la tentative de réauthentification');
                                    $return = '';
                                } else {
                                    $infos .= 'Pas de tentative de réauthentification. <br/><br/>';
                                }
                            }
                        }
                    }
                }
            }
        }

        if ((count($errors) && $this->options['log_errors'])) {
            BimpCore::addlog('API ' . $this->options['public_name'] . ': Echec requête "' . $request_name . '"', Bimp_Log::BIMP_LOG_ERREUR, 'api', $this->apiObject, array(
                'Erreurs' => $errors,
                'Requête' => str_replace('<br/>', "\n", str_replace('Array', "", $infos))
            ));
        }

        if (!empty($errors)) {
            $infos .= BimpRender::renderAlerts(BimpTools::getMsgFromArray($errors, 'Erreurs') . '<br/><br/>');
        }

        if ($this->options['log_requests']) {
            dol_syslog($infos);
        }

        BimpDebug::addDebug('api', 'API "' . $this->options['public_name'] . '" - Requête "' . $request_name . '"', $infos);

        $this->addDebug($infos);

        return $return;
    }

    public function getDefaultRequestsHeaders($request_name, &$errors = array())
    {
        return array();
    }

    public function getDefaultCurlOptions($request_name, &$errors = array())
    {
        return array();
    }

    public function testRequest(&$errors = array(), &$warnings = array())
    {
        $errors[] = 'Fonction de test non implémentée';
        return array();
    }

    // Tools:

    public function renderJsVars()
    {
        return '';
    }

    public function addDebug($content)
    {
        $this->debug_content .= $content;
    }

    public function displayNoLogged($msg = '', $callback = '')
    {
        if (!$msg) {
            $msg = 'Non connecté à l\'API "' . $this->options['public_name'] . '". Veuillez vous connecter et réitérer l\'opération';
        }

        $url = static::$urls['login'][static::$mode];
        $msg .= '<script type="text/javascript">';
        $msg .= 'BimpApi.openLoginModal($(\'\'), \'' . $url . '\'' . ($callback ? ', ' . $callback : '') . ');';
        $msg .= '</script>';

        return $msg;
    }

    // Getters JS:

    public function getJsApiRequestOnClick($request_name, $fields = array(), $params = array())
    {
        $js = 'BimpApi.ajaxRequest($(this), \'' . static::$name . '\', ' . $this->idx . ', \'' . $request_name . '\', ';

        $js .= htmlentities(json_encode($fields)) . ', ';
        $js .= BimpTools::getArrayValueFromPath($params, 'result_container', 'null') . ', ';

        $js .= '{';
        $js .= 'need_connection: ' . BimpTools::getArrayValueFromPath($params, 'need_connection', 1);
        $js .= '}, ';

        $js .= BimpTools::getArrayValueFromPath($params, 'success_callback', 'null') . ', ';
        $js .= BimpTools::getArrayValueFromPath($params, 'confirm_msg', '\'\'') . ', ';

        $js .= ')';

        return $js;
    }

    // Méthodes statiques:

    public static function getApisArray($include_empty = false, $active_only = true)
    {
        BimpObject::loadClass('bimpapi', 'API_Api');
        return API_Api::getApisArray($include_empty, $active_only, 'name');
    }

    public static function getApisClassesArray()
    {
        $apis = array();

        $dir = DOL_DOCUMENT_ROOT . '/bimpapi/classes/apis';

        if (is_dir($dir)) {
            $files = scandir($dir);

            foreach ($files as $f) {
                if (in_array($f, array('.', '..'))) {
                    continue;
                }

                if (preg_match('/^(.+)API\.php$/', $f, $matches)) {
                    $api_name = strtolower($matches[1]);
                    $class_name = $matches[1] . 'API';

                    if (!class_exists($class_name)) {
                        require_once $dir . '/' . $f;
                    }

                    if (class_exists($class_name)) {
                        $apis[$api_name] = $class_name;
                    }
                }
            }
        }

        return $apis;
    }

    public static function isApiActive($api_name, &$api = null, $api_idx = 0)
    {
        if (is_null($api) && $api_name) {
            $api = BimpCache::findBimpObjectInstance('bimpapi', 'API_Api', array(
                        'name'    => $api_name,
                        'api_idx' => (int) $api_idx
                            ), true, false);
        }

        if (is_a($api, 'API_Api') && BimpObject::objectLoaded($api)) {
            return (int) $api->getData('active');
        }

        return 0;
    }
}
