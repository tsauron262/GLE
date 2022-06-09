<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapi/classes/BimpAPI.php';

class DocuSignAPI extends BimpAPI {

    // PW web : HLxmS57W3uz8
    // PW API (personnalisé) : sKvb8m69ZAzYak36uenLjN23UXi4W2B6HsKVE3SG76n39my9d8
    // PW d’application : eelkepzlhzrikwev
    
    
    // Clé dev 0688ef6f-60a4-43e5-9865-e5803b8cbe80
    
    public static $name = 'docu_sign';
    public static $title = 'DocuSign';
//    public static $include_debug_json = false;
    public static $urls_bases = array(
        'default' => array(
            'test' => 'https://account-d.docusign.com',
            'prod' => 'https://account.docusign.com'
        ),
        'auth' => array(
            'test' => 'https://account-d.docusign.com',
            'prod' => 'https://account.docusign.com'
        )
    );
    public static $requests = array(
        'getCode' => array(
            'label' => 'Obtenir le code pour le token',
            'url' => '/oauth/auth'
        ),
        'authenticate' => array(
            'label' => 'Authentification',
            'url_base_type' => 'auth',
            'url' => '/oauth/auth/token'
        )
    );
    
    public static $tokens_types = array(
        'access' => 'Token d\'accès'
    );

    public function testRequest(&$errors = array(), &$warnings = array()) {

    }
    public function connect(&$errors = array(), &$warnings = array()) {
//        $errors[] = "<a href='https://account-d.docusign.com/oauth/auth?response_type=code&scope=signature&client_id=3b602db6-78eb-47f2-8a61-454fcb21836e&redirect_uri=http://localhost/bimp-erp/htdocs/bimpapi/test/testCallBack.php'>cliquez ici pour conecter</a>";
        if (!count($errors)) {
            
//            // Get code
//            $code = $this->execCurl('getCode', array(
//                'fields' => array(
//                    'response_type' => 'code',
//                    'scope' => 'signature',
//                    'client_id' => BimpTools::getArrayValueFromPath($this->params, 'test_oauth_client_id', ''),
////                    'redirect_uri' => 'http://localhost/'
//                    )), $errors);           
//            
//            
//            // TODO enlever
            $code = 'eyJ0eXAiOiJNVCIsImFsZyI6IlJTMjU2Iiwia2lkIjoiNjgxODVmZjEtNGU1MS00Y2U5LWFmMWMtNjg5ODEyMjAzMzE3In0.AQoAAAABAAYABwCAwIyM-0naSAgAgEwT1PtJ2kgCAD8yFEKBwg5KgPc3s-p9hmUVAAEAAAAYAAEAAAAFAAAADQAkAAAAM2I2MDJkYjYtNzhlYi00N2YyLThhNjEtNDU0ZmNiMjE4MzZlIgAkAAAAM2I2MDJkYjYtNzhlYi00N2YyLThhNjEtNDU0ZmNiMjE4MzZlNwAWNp3Mvlw4R7fBwQ6pjlPnMAAAVe40-0naSA.NzHWSKIgCiyrPzmKalhcGzze5DsNsWSqWjlmfdS5XdtZFZrF72Qe03GfycoUYRAmlGXpmEVdWQE9C24HP25hLbabtJnJDzvn6UpRIbQWXVoW0pFBZ85fc3aBGwPbZzKunfZfQoM4Ds2pcFl_J0mH5TARAHeGfUQ5UdkikHacWdJGKhlEKwmV0WIStRkKwT1XCAoQ7oy-txk9B6LFASQsjDE5xmxPRTtTRTf7PeMj00Zj4As_pS3RYDMoIZVFE3u3i67SWZJeunMGm3W5BHbfQDXA4h04iLqtds1EDrMNDrlbGMBg6KqUWCJ7etkCfLijp_AsIP5MMVtf9Xh8fDesPA';
            
//            global $out_url;
//            echo "Sortie: " . print_r($out_url, 1);
            
            // Get token
            $result = $this->execCurl('authenticate', array(
                'fields' => array(
                    'grant_type' => 'authorization_code',
                    'code' => $code
                    )), $errors);
            

            if (is_string($result)) {
                $errors[] = $result;
            } elseif (isset($result['data']) && isset($result['data']['accessToken']) && (string) $result['data']['accessToken']) {
                
                
//                $expires_in = (int) BimpTools::getArrayValueFromPath($result, 'expires_in', 3600);
//
//                $dt_now = new DateTime();
//                $dt_now->add(new DateInterval('PT' . $expires_in . 'S'));
//
//                $this->saveToken('access', $result['data']['accessToken'], $dt_now->format('Y-m-d H:i:s'));
            } else {
                $errors[] = 'Echec de la connexion pour une raison inconnue';
            }
        }
        
        sleep(1);

        return (!count($errors));
    }

    public function getDefaultRequestsHeaders($request_name, &$errors = array()) {
//        if ($this->isUserAccountOk($errors)) {
            if ($this->options['mode'] === 'test') {
                $client_id = BimpTools::getArrayValueFromPath($this->params, 'test_oauth_client_id', '');
                $client_secret = BimpTools::getArrayValueFromPath($this->params, 'test_oauth_client_secret', '');
                $api_key = BimpTools::getArrayValueFromPath($this->params, 'test_api_key', '');
            } else {
                $client_id = BimpTools::getArrayValueFromPath($this->params, 'prod_oauth_client_id', '');
                $client_secret = BimpTools::getArrayValueFromPath($this->params, 'prod_oauth_client_secret', '');
                $api_key = BimpTools::getArrayValueFromPath($this->params, 'prod_api_key', '');
            }

            if ($client_id && $client_secret) {
                if ($request_name == 'authenticate') {
                    return array(
                        'Authorization' => 'Basic ' . base64_encode($client_id . ':' . $client_secret)
//                        'Authorization' => 'Basic ' . base64_encode('fezfezfezez:fezfezfez')
                    );
                } else {
                    return array(
//                        'Authorization' => 'Bearer ' . $this->userAccount->getToken('access'),
                    );
                }
            }
//        }

        return array();
    }
    
    
// Auth fonction en prod:
// Code
// https://account-d.docusign.com/oauth/auth?response_type=code&scope=signature&client_id=3b602db6-78eb-47f2-8a61-454fcb21836e&redirect_uri=http://localhost/
// Token
// https://account-d.docusign.com/oauth/auth&response_type=token&scope=signature&client_id=3b602db6-78eb-47f2-8a61-454fcb21836e&redirect_uri=http://localhost/
    
    // Install: 

    public function install(&$warnings = array()) {
        $errors = array();

        $bdb = BimpCache::getBdb();

        if ((int) $bdb->getValue('bimpapi_api', 'id', 'name = \'' . self::$name . '\'')) {
            $errors[] = 'Cette API a déjà été installée';
        } else {
            $api = BimpObject::createBimpObject('bimpapi', 'API_Api', array(
                        'name'  => self::$name,
                        'title' => self::$title
                            ), true, $errors, $warnings);

            if (BimpObject::objectLoaded($api)) {
                $param = (string) BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
                            'id_api' => $api->id,
                            'name' => 'test_oauth_client_secret',
                            'title' => 'Secret client OAuth en mode test',
                            'value' => 'd4a8e30b-7dff-433b-9c8d-4208e0f8cbc2'
                                ), true, $warnings, $warnings);
                
                $param = (string) BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
                            'id_api' => $api->id,
                            'name' => 'test_oauth_client_id',
                            'title' => 'ID Client OAuth en mode test',
                            'value' => '4214323f-c281-4a0e-80f7-37b3ea7d8665'
                                ), true, $warnings, $warnings);

            }
        }

        return $errors;
    }

}