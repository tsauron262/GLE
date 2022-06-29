<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapi/classes/BimpAPI.php';

class DocusignAPI extends BimpAPI {

    // PW web (dev@bimp.fr): HLxmS57W3uz8
    
    public static $name = 'Docusign';
    public static $title = 'DocuSign';
    public static $modeles = array(
        'BContract_contrat' => array(
            'id_template' => ''
            )
        );

//    public static $include_debug_json = false;
    public static $urls_bases = array(
        'default' => array(
            'test' => 'https://demo.docusign.net',
            'prod' => 'https://docusign.net'
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
        ),
        'sendEnvelope' => array(
            'label' => 'Envoie signature'
        ),
        'getEnvelope' => array(
            'label' => 'Obtention signature'
        ),
        'getTemplates' => array(
            'label' => 'Obtention des modèles'
        )
    );
    
    public static $tokens_types = array(
        'access'  => 'Token d\'accès',
        'code'    => 'Code pour obtenir les tokens',
        'refresh' => 'Token de rafraîchissement',
    );

    public function testRequest(&$errors = array(), &$warnings = array()) {
        
        $id_account = $this->userAccount->getData('login');
        $params['id_account'] = $id_account;
        
//        $this->getTemplates($params);

        $this->reqCreateEnvelope($params, $errors);
        
        

    }
    
    // Requêtes
    private function reqCreateEnvelope($params, &$errors = array()) {
                
        $id_account = $params['id_account'];
        
        
        
        $result = $this->execCurl('sendEnvelope', array(
            'fields' => array(
                'status' => 'sent',
                'documents' => array(
                        array(
                            'documentBase64' => base64_encode(file_get_contents('/var/www/html/bimp-erp/htdocs/document.php?modulepart=propal&file=PRB2202-042113_B/PRB2202-042113_B.pdf')),
                            'documentId' => 1,
//                            'fileExtension' => 'pdf',
                            'name' => 'document_a_signer.pdf')
                    ),
//                'accountId' => 'ab9a0f53-6356-40b9-9b28-3f5c667711e2', // id Annie
//                'templateId' => '78918717-f580-4623-8c86-18e0e2f0130d', // template Annie
                'accountId' => $id_account,
                'templateId' => '60db17e5-31bd-45da-ab9e-f8edc45f9944', // template Dynamique
                'emailSubject' => "Demande de signature",
                'templateRoles' => array(
                    array(
                        'roleName' => 'Signer1',
                        'name' => 'Hank Scorpio',
                        'email' => 'pelegrinromain@gmail.com',
                    )
                )
            ),
            'type' => 'FILE',
            'url_end' => '/restapi/v2.1/accounts/' . $id_account . '/envelopes'
            ), $errors);
        
        
        
//Array
//(
//    [envelopeId] => d710d84c-4563-4343-af90-e5f4c7b77dd6
//    [uri] => /envelopes/d710d84c-4563-4343-af90-e5f4c7b77dd6
//    [statusDateTime] => 2022-06-14T13:20:08.6500000Z
//    [status] => created
//)
        
        
//        "accountId": "301424",
//           "emailSubject": "This request is sent from a Template",
//                "templateId": "55A80182-xxxx-xxxx-xxxx-FD1E1C0F9D74",
//                "templateRoles": [{ "roleName": "Signer1", "name": "Hank Scorpio", "email": "hscorpio@example.com" }],
//                        "status": "sent" 
        
        $param_get = array(
            'id_account'  => (string) $id_account,
            'id_envelope' => (string) $result['envelopeId']
        );
        $this->getEnvelope($param_get, $errors);
        
    }
    
    private function getEnvelope($params, &$errors = array()) {
        
        $data = $this->execCurl('getEnvelope', array(
            'url_end' => '/restapi/v2.1/accounts/' . $params['id_account'] . '/envelopes/' . $params['id_envelope']
            ), $errors);
        
        return $data;
    }
    
    
    private function getTemplates($params, &$errors = array()) {
//        search_text
        // d8b68247-2825-4156-a2ae-e15b1c84be8f
        $result = $this->execCurl('getTemplates', array(
            'url_end' => '/restapi/v2.1/accounts/' . $params['id_account'] . '/templates'
            ), $errors);
        
        return $result;
    }
    
    // Interface
    public function createEnvelope($object, &$errors = array()) {
        
        
        
        if(!in_array($object->object_name, array_keys(self::$modeles))) {
            $errors[] = "Type d'objet non prit en charge: " .  $object->object_name;
            return 0;
        }
        
        
        return reqCreateEnvelope($errors);
    }

    
    
//    {"code":"eyJ0eXAiOiJNVCIsImFsZyI6IlJTMjU2Iiwia2lkIjoiNjgxODVmZjEtNGU1MS00Y2U5LWFmMWMtNjg5ODEyMjAzMzE3In0.AQsAAAABAAYABwAAozRjIU3aSAgAAC-7qiFN2kgCAD8yFEKBwg5KgPc3s-p9hmUVAAEAAAAYAAEAAAAFAAAADQAkAAAAM2I2MDJkYjYtNzhlYi00N2YyLThhNjEtNDU0ZmNiMjE4MzZlIgAkAAAAM2I2MDJkYjYtNzhlYi00N2YyLThhNjEtNDU0ZmNiMjE4MzZlMAAAozRjIU3aSBIAAQAAAAsAAABpbnRlcmFjdGl2ZTcAFjadzL5cOEe3wcEOqY5T5w.ypLiRy7Y6e73rDmL64brh9WyKhmV8xPKKTeWD_moe7rUWq0XgqiSOs_x0XgdAIt2wWxkjU0CFHaItPZC8MlnKQxnSaTnLrF4flPJzRPN0F5_xGSbyl_TBEL_C-0ZjoiuC53gaBB-g-4R3Genh70Q3QZsMLw-F_EtGMZYjRG1AxXN0KA0nmJS2hr3rWHeMv3s6Sw8K4KVBTKBLtgfGrLZiwuayssBeQDi3ThJ9Vm2hicL8bqFbs1SC6TCNDmsvcaWf1KL9i5sK1An42YdmpD8D638OUcnLwo2UmYM4Qnz4gTnGBKFML-7SXqlFCdznAXruVfrbbHdBs5HWOuxloxiKA","access":"eyJ0eXAiOiJNVCIsImFsZyI6IlJTMjU2Iiwia2lkIjoiNjgxODVmZjEtNGU1MS00Y2U5LWFmMWMtNjg5ODEyMjAzMzE3In0.AQsAAAABAAUABwCAwGBnIU3aSAgAgACEdWRN2kgCAD8yFEKBwg5KgPc3s-p9hmUVAAEAAAAYAAEAAAAFAAAADQAkAAAAM2I2MDJkYjYtNzhlYi00N2YyLThhNjEtNDU0ZmNiMjE4MzZlIgAkAAAAM2I2MDJkYjYtNzhlYi00N2YyLThhNjEtNDU0ZmNiMjE4MzZlEgABAAAACwAAAGludGVyYWN0aXZlMAAAozRjIU3aSDcAFjadzL5cOEe3wcEOqY5T5w.HNdZEoKxklEcR4A9CXjiyhVeeS5LfTWLah50LXjpvhGgqeXYUyG6VberwBEHWjZhnAS5Ska9ecXcimgzBCT5m8_IT1WhQQGxPfo-RYVRpXNagr81ZcPkZ-EtioVbKCBK_JYeDP7jNIbItd_tbCFIqhbIriaE3dFUzDpeACcv5lKv04BMfDGvt7tR9IOpuBboPEZo2SK-2w4b_wdBxnCBghv1sruQv_UyE14_u1cEz9trC_0MJ3MZ4otq_0A_LeHm1dXjp1DiOTMIMWjSawUnvjDWfardjm5y7LNjeQF1DAlt-E6Z_kENDR_OLq8nWbFRk-QRA_cehLSYS2c8shoHfg","refresh":"eyJ0eXAiOiJNVCIsImFsZyI6IlJTMjU2Iiwia2lkIjoiNjgxODVmZjEtNGU1MS00Y2U5LWFmMWMtNjg5ODEyMjAzMzE3In0.AQoAAAABAAgABwCAwGBnIU3aSAgAgEDFX7Rk2kgCAD8yFEKBwg5KgPc3s-p9hmUVAAEAAAAYAAEAAAAFAAAADQAkAAAAM2I2MDJkYjYtNzhlYi00N2YyLThhNjEtNDU0ZmNiMjE4MzZlIgAkAAAAM2I2MDJkYjYtNzhlYi00N2YyLThhNjEtNDU0ZmNiMjE4MzZlMAAAozRjIU3aSDcAFjadzL5cOEe3wcEOqY5T5w.RGVsnx_SeR2U0aFCnmcUgxWI2dw7MEyUF4RaqrdpTAGaucyrUY2Tz7ZV0zO6T-qUDh13HQeG8243PjkD31xoU8MJxmmeM_PCA35cORaaUT7PIFFKihc7lWixdrJN3ytQ7TuoRxa29vVHChcwGCGPGr2fGoCKNdfbuGWlo0-EcMh9dCTS6kNC-Cb6WZBiZxqz7AQkS0dMX1VWb5DF2AbA9fm6ve2Ex500RnklbFWRWtoJ4vPKAN5GRH8W9thjmu2QaU49ElLa5r2Jg1p7hmKwPHlTLTmSau5THCBJPWZXEJ_aQpsiFMoKS12qgUG98NuvfFlegH2aSRVJ04Z6uAQIMA"}
    
    public function connect(&$errors = array(), &$warnings = array(), $tentative = 0) {
        
        if($this->userAccount->isLogged())
            return count($errors);
        else {
            $this->addDebug('AAAAAAAAAAAAAAAAAAA : ' . $this->userAccount->getData('name') . ' nest pas connecté<br/>');
        }
        
        $result = '';
        $urlRedirect = "http://localhost/bimp-erp/htdocs";
        $tentative++;
        if($tentative > 4)
            die('boucle infinit');
        $code = $this->userAccount->getToken('code');
        $refresh_token = $this->userAccount->getToken('refresh');
        if($code . 'x' == 'x' and $refresh_token . 'x' == 'x'){
            $_SESSION['id_user_docusign'] = $this->userAccount->id;
            $errors[] = "<a target='_blank' href='" . $this->getBaseUrl() ."/oauth/auth?response_type=code&scope=signature&client_id=3b602db6-78eb-47f2-8a61-454fcb21836e&redirect_uri=".$urlRedirect."/bimpapi/retour/RetDocuSign.php'>cliquez ici pour conecter</a>";
        } else {
            
            if($refresh_token . 'x' != 'x') {
                $result = $this->execCurl('authenticate', array(
                    'fields' => array(
                        'grant_type' => 'refresh_token',
                        'refresh_token' => $refresh_token
                        )), $errors);
                
//                $this->saveToken('refresh', '');
            }
            
            $access_token = BimpTools::getArrayValueFromPath($result, 'access_token', '');
            if(!$access_token) {
                // Get token 
                $result = $this->execCurl('authenticate', array(
                    'fields' => array(
                        'grant_type' => 'authorization_code',
                        'code' => $code
                        )), $errors);
            }
            
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
//                    $this->saveToken('code', '');
                    return $this->connect($errors, $warnings);
                }
                else        
                    $errors[] = $result['error_description'];
                
            } else {
                $errors[] = 'Echec de la connexion pour une raison inconnue';
            }
        }
        
//        sleep(1);

        return (!count($errors));
    }
    
    public function getBaseUrl($type = 'default') {
        return BimpTools::getArrayValueFromPath(static::$urls_bases, $type . '/' . $this->options['mode'], '');
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
                    );
                } else {
                    return array(
                        'Authorization' => 'Bearer ' . $this->userAccount->getToken('access'),
                    );
                }
            }
//        }

        return array();
    }
    
    
// Auth fonction en prod:
// Code
// https://account-d.docusign.com/oauth/auth?response_type=code&scope=signature&client_id=3b602db6-78eb-47f2-8a61-454fcb21836e&redirect_uri=http://localhost/

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









//Header REQUEST:
//
//POST /oauth/token HTTP/1.1
//Host: account-d.docusign.com
//Authorization: Basic NDIxNDMyM2YtYzI4MS00YTBlLTgwZjctMzdiM2VhN2Q4NjY1OmQ0YThlMzBiLTdkZmYtNDMzYi05YzhkLTQyMDhlMGY4Y2JjMg==
//Content-Type: application/json
//Accept: application/json
//Content-Length: 718
//
//Body REQUEST:
//
//Array
//(
//    [grant_type] => authorization_code
//    [code] => eyJ0eXAiOiJNVCIsImFsZyI6IlJTMjU2Iiwia2lkIjoiNjgxODVmZjEtNGU1MS00Y2U5LWFmMWMtNjg5ODEyMjAzMzE3In0.AQoAAAABAAYABwCA8axZKEraSAgAgH0zoShK2kgCAD8yFEKBwg5KgPc3s-p9hmUVAAEAAAAYAAEAAAAFAAAADQAkAAAAM2I2MDJkYjYtNzhlYi00N2YyLThhNjEtNDU0ZmNiMjE4MzZlIgAkAAAAM2I2MDJkYjYtNzhlYi00N2YyLThhNjEtNDU0ZmNiMjE4MzZlNwAWNp3Mvlw4R7fBwQ6pjlPnMAAAVe40-0naSA.VDPs6QGLcItNIQci32UoDKyddzrZj5TAkgqZ2JehJNR8anqF9xKGEvp5w2i0g3TgLvg-ddmUhwWiQXthx2XtuR6gFG58AbtjH1yHX_60MtcV6Acnu0hctW-HJTOHtYyGtr-5hmdCdf6x0FInOt0VwXEtgHdDvGRoyCmPHAvNd0HvbcHEalivMoOAY7Q4MF7O82vFvM8Z47-oLae9seKrHf_dvnzvtxk7uIwt386Us324JZne3SmHRZbFbiXCpVf3QIsuiezqLWZlPb46O9rO9v1ZKke4CU7ZXQTBy7TVBRYPAPePunKHxa9EBFh8ds2s0EpvQDjusNx7h9QEUZRbnQ
//)
//
//
//
//Code réponse: 400
//
//Body RESPONSE:
//
//Array
//(
//    [error] => invalid_grant
//    [error_description] => expired_client_token
//)
//
//Erreurs :
//
//    Requête incorrecte