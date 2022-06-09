<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapi/classes/BimpAPI.php';

class DocusignAPI extends BimpAPI {

    // PW web : HLxmS57W3uz8
    // PW API (personnalisé) : sKvb8m69ZAzYak36uenLjN23UXi4W2B6HsKVE3SG76n39my9d8
    // PW d’application : eelkepzlhzrikwev
    
    
    // Clé dev 0688ef6f-60a4-43e5-9865-e5803b8cbe80
    
    public static $name = 'Docusign';
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
            'url' => '/oauth/token'
        )
    );
    
    public static $tokens_types = array(
        'access' => 'Token d\'accès'
    );

    public function testRequest(&$errors = array(), &$warnings = array()) {

    }
    public function connect(&$errors = array(), &$warnings = array(), $tentative = 0) {
        $urlRedirect = "https://erp2.bimp.fr/bimpinv01072020";
        $tentative++;
        if($tentative > 4)
            die('boucle infinit');
        $code = $this->userAccount->getToken('code');
        if($code.'x' == 'x')
            $errors[] = "<a target='_blank' href='https://account-d.docusign.com/oauth/auth?response_type=code&scope=signature&client_id=3b602db6-78eb-47f2-8a61-454fcb21836e&redirect_uri=".$urlRedirect."/bimpapi/retour/RetDocuSign.php'>cliquez ici pour conecter</a>";
        else {
            
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
            } elseif (isset($result['access_token'])) {
                
                
                $expires_in = (int) BimpTools::getArrayValueFromPath($result, 'expires_in', 3600);

                $dt_now = new DateTime();
                $dt_now->add(new DateInterval('PT' . $expires_in . 'S'));

                $this->saveToken('access', $result['access_token'], $dt_now->format('Y-m-d H:i:s'));
                $this->saveToken('refresh', $result['refresh_token'], $dt_now->format('Y-m-d H:i:s'));
            } elseif(isset($result['error_description'])){
                $error = $result['error_description'];
                        
                if($error == 'expired_client_token'){
                    $this->saveToken('code', '');
                    return $this->connect($errors, $warnings);
                }
                else        
                    $errors[] = $result['error_description'];
                
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
                $param = BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
                            'id_api' => $api->id,
                            'name' => 'test_oauth_client_secret',
                            'title' => 'Secret client OAuth en mode test',
                            'value' => 'd4a8e30b-7dff-433b-9c8d-4208e0f8cbc2'
                                ), true, $warnings, $warnings);
                
                $param = BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
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