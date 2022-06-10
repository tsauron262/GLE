<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapi/classes/BimpAPI.php';

class AtradiusAPI extends BimpAPI {
    
    const CREDIT_CHECK = 'credit-check'; // Limité à 7000 euros
    const CREDIT_LIMIT = 'credit-limit'; // Utilisé à partir de + de 7000 euros
    
    const STATUS_VALID    = 'DECISION';
    const STATUS_EN_COURS = 'REFERRED';
    
    public static $name = 'atradius';
    public static $include_debug_json = false;
    public static $urls_bases = array(
        'default' => array(
            'prod' => 'https://api.atradius.com',
            'test' => 'https://api-uat.atradius.com'
        ),
        'auth' => array(
            'prod' => 'https://api.atradius.com/authenticate/v2/tokens',
            'prod' => 'https://api-uat.atradius.com/authenticate/v1/tokens',
            'test' => 'https://api-uat.atradius.com/authenticate/v1/tokens'
        )
    );
    public static $requests = array(
        'authenticate' => array(
            'label' => 'Authentification',
            'url_base_type' => 'auth'
        ),
        'getMyBuyer' => array(
            'label' => 'Details ce nos client',
            'url' => '/credit-insurance/organisation-management/v1/buyers/my-buyers'
        ),
        'getBuyer' => array(
            'label' => 'Details client',
            'url' => '/credit-insurance/organisation-management/v1/buyers'
        ),
        'getCover' => array(
            'label' => 'Get assurances',
            'url' => '/credit-insurance/cover-management/v1/covers'
        ),
        'createCover' => array(
            'label' => 'Créer assurance',
            'url' => '/credit-insurance/cover-management/v1/covers',
        ),
        'updateCover' => array(
            'label' => 'MAJ assurance',
            'url' => '/credit-insurance/cover-management/v1/covers',
        ),
        'deleteCover' => array(
            'label' => 'Suppression assurance',
            'url' => '/credit-insurance/cover-management/v1/covers',
        ),
    );
    public static $tokens_types = array(
        'access' => 'Token d\'accès'
    );

    // Requêtes: 
    public function getMyBuyer($filters, &$errors = array()) {

        
        $data = $this->execCurlCustom('getMyBuyer', array(
            'url_params' => $filters
                ), $errors);

        return $data;
    }
    

    /**
     * $filters = filtre de l'API (sauf customerId qui est automatique
     * si pas buyerId => utilise siren
     */
    // customerId est définit automatiquement
    public function getCover($filters = array(), &$errors = array(), &$warnings = array(), $format_output = true) {
        
        BimpObject::loadClass('bimpcore', 'Bimp_Client');
                
        $response = $this->execCurlCustom('getCover', array(
            'url_params' => $filters
                ), $errors, $header, $code);


        $status = (int) Bimp_Client::STATUS_ATRADIUS_OK;
        foreach($response['data'] as $k => $c) {
            
            // Il y a une demande en cours d'arbitrage
            if(isset($c['coverStatus']) and $c['coverStatus'] == self::STATUS_EN_COURS) {
                $status = (int) Bimp_Client::STATUS_ATRADIUS_EN_ATTENTE;
                $warnings[] = "Une demande pour un montant de " . $c['creditLimitApplicationAmountInPolicyCurrency'] . " euros est en cours d'arbitrage";
            }
            
            // Il y a une décision prise pour cet acheteur
            if(isset($c['coverStatus']) and $c['coverStatus'] == self::STATUS_VALID)
                $cover = $response['data'][$k];
            
            
            if(isset($c['firstAmtDecision']) and isset($c['firstAmtDecision']['decisionConditions'])) {
                
                foreach($c['firstAmtDecision']['decisionConditions'] as $cond)
                    $warnings[] = $cond['conditionDescription'];
                    
            }
            
        }

        // Pas de couverture trouvé => on force TODO test
        if(!is_array($cover))
            $cover = $response['data'][0];
        
        if(!is_array($cover)) {
            return array();
        }

        // Date expire
        $has_special_limit = (int) isset($cover['withdrawalDate']);
        // A une date d'expiration réduite
        if($has_special_limit) {
            $date_expire = new DateTime($cover['withdrawalDate']);
        // Pas de date d'expiration spécifique => 1 an
        } else {
            $date_expire = new DateTime($cover['decisionDate']);
            $date_expire->add(new DateInterval('P1Y'));
        }

        if((string) $cover['coverType'] == self::CREDIT_CHECK)
            $amount = $cover['totalDecision']['decisionAmtInPolicyCurrency'];
        elseif(isset($cover['totalDecision']) and isset($cover['totalDecision']['decisionAmtInPolicyCurrency']))
            $amount = (int) $cover['totalDecision']['decisionAmtInPolicyCurrency'];
        else
            $amount = 0;
        

        return array(
            'cover_type'        => (string) $cover['coverType'],
            'has_special_limit' => (int) $has_special_limit,
            'code_return'       => (int) $code,
            'amount'            => (int) $amount,
            'date_expire'       => (string) $date_expire->format('Y-m-d H:i:s'),
            'status'            => (int) $status
        );

        
    }


    // Ne pas utiliser directement ! Passer par setCovers
    private function createCover($params = array(), &$errors = array(), &$warnings = array(), &$success = '') {
        
        global $user;
            
        if(!$user->rights->bimpcommercial->gestion_recouvrement)
            $errors[] = "Vous n'avez pas le droit de créer les assurances Atradius";
        
        
        if(!isset($params['currencyCode']) and $params['coverType'] != self::CREDIT_CHECK)
            $params['currencyCode'] = 'EUR';

        $data = $this->execCurlCustom('createCover', array(
            'fields' => $params,
            'type' => 'POST'
            ), $errors, $response_headers, $code, array(), $success);
        
        
        BimpObject::loadClass('bimpcore', 'Bimp_Client');
        
        if($params['coverType'] == self::CREDIT_CHECK)
            $cover_type = "La demande de crédit check";
        elseif($params['coverType'] == self::CREDIT_LIMIT)
            $cover_type = "La demande de limite de crédit";

        // Statut de la demande
        switch ($code) {
            case 201:
                $data['status'] = (int) Bimp_Client::STATUS_ATRADIUS_OK;
                $success .= $cover_type . " a été créer<br/>";
                break;
            case 202:
                $data['status'] = (int) Bimp_Client::STATUS_ATRADIUS_EN_ATTENTE;
                $warnings .= $cover_type . " est en cours d'arbitrage<br/>";
                break;
            default:
                $data['status'] = (int) Bimp_Client::STATUS_ATRADIUS_REFUSE;
                $errors .= $cover_type . " a été refusé<br/>";
                break;
        }

        return $data;
    }

    // Ne pas utiliser directement ! Passer par setCovers
    private function updateCover($params = array(), &$errors = array(), &$success = '') {
        
        global $user;
            
        if(!$user->rights->bimpcommercial->gestion_recouvrement)
            $errors[] = "Vous n'avez pas le droit de mettre à jour les assurances Atradius";
        
        
        if($params['coverType'] == self::CREDIT_CHECK) {
            $errors[] = "Tentative de mise à jour de crédit check impossible";
            return array();
        }
            
        if(!isset($params['currencyCode']))
            $params['currencyCode'] = 'EUR';

        $data = $this->execCurlCustom('updateCover', array(
            'fields' => $params,
            'type' => 'PUT',
            'curl_options' => array()
            ), $errors, $response_headers, $code, array(), $success);
        
        BimpObject::loadClass('bimpcore', 'Bimp_Client');

        // Statut de la demande
        switch ($code) {
            case 201:
                $data['status'] = (int) Bimp_Client::STATUS_ATRADIUS_OK;
                $warnings[] = "La demande a été mise à jour";
                break;
            case 202:
                $data['status'] = (int) Bimp_Client::STATUS_ATRADIUS_EN_ATTENTE;
                $warnings[] = "Demande en cours d'arbitrage";
                break;
            default:
                $data['status'] = (int) Bimp_Client::STATUS_ATRADIUS_REFUSE;
                $errors[] = "Demande refusée";
                break;
        }

        return $data;
    }

    public function getBuyerIdBySiren($siren, &$errors = array()) {
        
//        $this->connect($errors);
//        die('rr');
        // Définir id atra pour le client
        $params_get = array(
            'country' => 'FRA',
            'uid'     => $siren,
            'uidType' => 'SN'
        );
        $data = $this->execCurlCustom('getBuyer', array(
            'url_params' => $params_get
                ), $errors, $response_headers, $response_code, array('customerId', 'policyId'));
        if (isset($data['data']) && isset($data['data'][0]) && isset($data['data'][0]) && isset($data['data'][0]['buyerId'])) {
            if (is_array($data['errors']) and count($data['errors']))
                $errors = BimpTools::merge_array($errors, $data['errors']);
            return (int) $data['data'][0]['buyerId'];
        }

        $errors[] = 'Client non trouvé (par SIREN ' . $siren . ')';
        return 0;
    }
    
    private function deleteCover($params = array(), &$errors = array(), &$success = '') {
        
        $params['action'] = 'cancel';
        
        if (!count($errors)) {
            
            $data = $this->execCurlCustom('deleteCover', array(
                'fields' => $params,
                'type' => 'PUT',
                'curl_options' => array()
                ), $errors, $response_headers, $response_code, array(), $success);
        }
        
        return $data;
    }

    
    // Interface
    public function setCovers($params, &$errors = array(), &$warnings = array(), &$success = '') {
        
        $decisions = array();
        $now = new DateTime();
        
//        print_r($params);
//        die();
        
        // Override des paramètres
//        $params = BimpTools::overrideArray(array(
//            'coverType' => (string) self::CREDIT_CHECK,
//            'buyerId'   => (int) $params['buyerId']
//        ), $params, false, true);
        
        if((int) $params['creditLimitAmount'] < 7000) 
            $params['creditLimitAmount'] = 7000;
        
        
        // Crédit check
        if((int) $params['creditLimitAmount'] <= 12000) {
            
            $params_cc = array(
                'coverType' => (string) $params_cc['coverType'] = self::CREDIT_CHECK,
                'buyerId'   => (int)    $params['buyerId'],
                'customerRefNumber' => $params['customerRefNumber']
            );
            
            $new_cover = $this->setCover($params_cc, $errors, $warnings, $success);
            if(count($new_cover))
                $decisions[] = $new_cover;
        }
        
        // Limit de crédit
        if(7000 < (int) $params['creditLimitAmount']) {

            $params_cl = array(
                'coverType'         => (string) $params['coverType'] = self::CREDIT_LIMIT,
                'creditLimitAmount' => (int)    $params['creditLimitAmount'],
                'buyerId'           => (int)    $params['buyerId'],
                'customerRefNumber' => $params['customerRefNumber']
            );
            
            $new_cover = $this->setCover($params_cl, $errors, $warnings, $success);
            if(count($new_cover))
                $decisions[] = $new_cover;
        }
        

        
        return $decisions;
    }
    
    private function setCover($params, &$errors = array(), &$warnings = array(), &$success = '') {
        
        // TODO ajouter currency code si CL
        $params_get = array(
            'buyerId'   => (int)    $params['buyerId'],
            'coverType' => (string) $params['coverType']
        );
        
        // On ne filtre pas par coverType si on veut créer un crédit check
        if((string) $params['coverType'] == self::CREDIT_CHECK)
            unset($params_get['coverType']);
        
        $cover = $this->getCover($params_get, $errors, $warnings);
        
//        if(count($warnings)) {
//            
//            return array();
//        }

        if((string) $cover['cover_type'] == self::CREDIT_LIMIT and (string) $params['coverType'] == self::CREDIT_CHECK and 0 < (int) $cover['creditLimitAmount']) {
            $warnings[] = "Il y a déjà une limite de crédit pour ce client, on ignore la création de crédit check";
            return array();
        }
        
        // Il n'y a pas encore d'assurance pour ce client, on le créer
        if(empty($cover) /* bloque lorsqu'il y a une assaurance retiré or (int) $cover['amount'] < 1*/) {
            
            return $this->createCover($params, $errors);
            
        
        // Augmentation de la limite
        } elseif($params['coverType'] != self::CREDIT_CHECK and (int) $cover['amount'] < $params['creditLimitAmount']) {
            
            $params_cl = array(
                'action'            => 'supersede',
                'creditLimitAmount' => $params['creditLimitAmount'],
                'buyerId'           => $params['buyerId'],
                'coverType'         => $params['coverType'],
                'customerRefNumber' => $params['customerRefNumber']
            );

            return $this->updateCover($params_cl, $errors, $success);
            
        // Réduction de la limite
        } elseif($params['coverType'] != self::CREDIT_CHECK and (int) $cover['amount'] > $params['creditLimitAmount']) {
            
            $params_cl = array(
                'action'            => 'reduce',
                'creditLimitAmount' => $params['creditLimitAmount'],
                'buyerId'           => $params['buyerId'],
                'coverType'         => $params['coverType'],
                'customerRefNumber' => $params['customerRefNumber']
            );

            return $this->updateCover($params_cl, $errors, $success);
            
        } else {
            if($params['coverType'] == self::CREDIT_CHECK)
                $warnings[] = "Tentative de mettre à jour un crédit check";
            else
                $warnings[] = "Tentative de changer la limite de crédit pour la même somme";
        }
                
    }
    

    // Tools
    private function getSuccess($response_code, &$success = '') {
        switch ($response_code) {
            case 200:
                $success .= "Action réussi<br/>";
                break;
            case 201:
                $success .= "Action réussi, assurance créée<br/>";
                break;
            case 202:
                $success .= "Action réussi, en attente d'approbation<br/>";
                break;
        }
    }
    
    private function getErrors($response, &$errors) {
        
        if(isset($response['errors'])) {
            $this->getError($response, $errors);
            
        } else {
            
            foreach ($response as $k => $unused)
                $this->getError($response[$k], $errors);

        }
        
        return $response;
    }
    
    private function getError($response, &$errors) {
        if(isset($response['errors'])){
            foreach($response['errors'] as $k => $e) {

                if(isset($e['source']['parameter']))
                    $errors[] = $e['detail'] . " pour le paramètre " . $e['source']['parameter'];
                else
                    $errors[] = $this->translateError($e['detail']);


            }
        }
        
        return $response;
    }
    
    private function translateError($msg) {
        switch ($msg) {
            case "Credit limit/credit check already exists for this buyer.":
                return "Il existe déjà une limite de crédit ou un crédit check pour cet acheteur.";
                
            case "Must be positive number greater than zero and a multiple of 1000.":
                return "Le montant de la couverture doit être supérieur à zéro et un multiple de 1000.";
                
            case "New Application cannot supersede existing cover":
                return "La couverture ne peut-être remplacée.";
                
            case "No buyer match found":
                return "Aucun acheteur ne correspond à cette requête.";

        }
        
        return $msg;
    }
    
    
    // Settings

    // SIREN
    // crédit check + limit : 501759088
    // date limit : 445248149
    // crédit check tout seul : 320916109
    
    // BUYER ID
    // CC tout seul 37838620
    // CL tout seul 4239457
    // Les 2 ? 82576511
    
    public function testRequest(&$errors = array(), &$warnings = array()) {
        
        $this->getCover(array(
//              'buyerId' => 53242955,
            'buyerId' => $this->getBuyerIdBySiren(389271214),
//            'coverType' => self::CREDIT_LIMIT
//            'coverType' => self::CREDIT_LIMIT
                ), $errors);


    }

    // Overrides: 
    
    public function execCurlCustom($request_name, $params = array(), &$errors = array(), &$response_headers = array(), &$response_code = -1, $dont_set = array(), &$success = '') {
        
        // URL FIELD
        if(isset($params['url_params'])) {
            if((!isset($params['url_params']['customerId']) or (int) $params['url_params']['customerId'] == 0) and ! in_array('customerId', $dont_set))
                $params['url_params']['customerId'] = (int) BimpTools::getArrayValueFromPath($this->params, 'customer_id', 0);

            if(!isset($params['url_params']['policyId']) and ! in_array('policyId', $dont_set))
                $params['url_params']['policyId'] = (string) BimpTools::getArrayValueFromPath($this->params, 'policy_id', 0);

            if(!isset($params['url_params']['buyerId']) and isset($params['url_params']['siren']) and ! in_array('siren', $dont_set)) {
                $params['url_params']['buyerId'] = $this->getBuyerIdBySiren($params['url_params']['siren']);
                unset($params['url_params']['siren']);
            }
            
        }
        
        // POSTFIELD
        if(isset($params['fields'])) {
            if((!isset($params['fields']['customerId']) or (int) $params['fields']['customerId'] == 0) and ! in_array('customerId', $dont_set))
                $params['fields']['customerId'] = (int) BimpTools::getArrayValueFromPath($this->params, 'customer_id', 0);

            if(!isset($params['fields']['policyId']) and ! in_array('policyId', $dont_set))
                $params['fields']['policyId'] = (string) BimpTools::getArrayValueFromPath($this->params, 'policy_id', 0);

            if(!isset($params['fields']['buyerId']) and isset($params['fields']['siren']) and ! in_array('siren', $dont_set)) {
                $params['fields']['buyerId'] = $this->getBuyerIdBySiren($params['fields']['siren']);
                unset($params['fields']['siren']);
            }
            
        }
        
        $return = $this->execCurl($request_name, $params, $errors, $response_headers, $response_code);
        
        $this->getSuccess($response_code, $success);
                
        return $return;
    }
    

    public function connect(&$errors = array(), &$warnings = array()) {
        if (!count($errors)) {
            $result = $this->execCurlCustom('authenticate', array(
                'fields' => array('inut' => 'inut')), $errors);

            if (is_string($result)) {
                $errors[] = $result;
            } elseif (isset($result['data']) && isset($result['data']['accessToken']) && (string) $result['data']['accessToken']) {
                $expires_in = (int) BimpTools::getArrayValueFromPath($result, 'expires_in', 3600);

                $dt_now = new DateTime();
                $dt_now->add(new DateInterval('PT' . $expires_in . 'S'));

                $this->saveToken('access', $result['data']['accessToken'], $dt_now->format('Y-m-d H:i:s'));
            } else {
                $errors[] = 'Echec de la connexion pour une raison inconnue';
            }
        }
        
        sleep(1);

        return (!count($errors));
    }

    public function getDefaultRequestsHeaders($request_name, &$errors = array()) {
        if ($this->isUserAccountOk($errors)) {
            if ($this->options['mode'] === 'test') {
                $client_id = BimpTools::getArrayValueFromPath($this->params, 'test_oauth_client_id', '');
                $client_secret = BimpTools::getArrayValueFromPath($this->params, 'test_oauth_client_secret', '');
                $apiKey = BimpTools::getArrayValueFromPath($this->params, 'test_api_key', '');
            } else {
                $client_id = BimpTools::getArrayValueFromPath($this->params, 'prod_oauth_client_id', '');
                $client_secret = BimpTools::getArrayValueFromPath($this->params, 'prod_oauth_client_secret', '');
                $apiKey = BimpTools::getArrayValueFromPath($this->params, 'prod_api_key', '');
            }

            if ($client_id && $client_secret) {
                if ($request_name == 'authenticate') {
                $client_id = BimpTools::getArrayValueFromPath($this->params, 'test_oauth_client_id', '');
                $client_secret = BimpTools::getArrayValueFromPath($this->params, 'test_oauth_client_secret', '');
                $apiKey = BimpTools::getArrayValueFromPath($this->params, 'test_api_key', '');
                    return array(
                        'Atradius-App-Key' => $apiKey,
                        'Authorization' => 'Basic ' . base64_encode($client_id . ':' . $client_secret)
                    );
                } else {
                    return array(
                        'Authorization' => 'Bearer ' . $this->userAccount->getToken('access'),
                        'Atradius-App-Key' => $apiKey
                    );
                }
            }
        }

        return array();
    }

    public function processRequestResponse($request_name, $response_code, $response_body, $response_headers = array(), &$infos = '', &$errors = array()) {

//        if($request_name == 'authenticate')
            $return = $response_body;
//        else
        $this->getErrors($response_body, $errors);

        switch ($response_code) {
            case '0':
                $errors[] = 'Atradius API: Vérifiez votre connexion internet';
                break;
            
            case '400':
                $errors[] = 'Atradius API: Requête incorrecte';
                break;

            case '401':
                $errors[] = 'Atradius API: Non authentifié';
                $return = 'unauthenticate';
                break;

            case '403':
                $errors[] = 'Atradius API: Accès refusé';
                break;

            case '404':
                $errors[] = 'Atradius API: API non trouvée';
                break;

            case '405':
                $errors[] = 'Atradius API: Format de la requête non supporté';
                break;

            case '406':
                $errors[] = 'Atradius API: Non accepté';
                break;
            
            case '408':
                $errors[] = 'Atradius API: Temps écoulé';
                break;

            case '422':
                $errors[] = 'Atradius API: Requête non traitable';
                break;

            case '500':
                $errors[] = 'Atradius API: Erreur interne serveur';
                break;
            
            case '503':
                $errors[] = 'Atradius API: Service surchargé, merci de réessayer plus tard';
                break;
        }
                
        if (isset($response_body['codeRetour']) && (int) $response_body['codeRetour'] !== 0 && isset($response_body['libelle'])) {
            $errors[] = $response_body['libelle'];
        }

        return $return;
    }

    // Install: 

    public function install(&$warnings = array()) {
        $errors = array();

        $bdb = BimpCache::getBdb();

        if ((int) $bdb->getValue('bimpapi_api', 'id', 'name = \'atradius\'')) {
            $errors[] = 'Cette API a déjà été installée';
        } else {
            $api = BimpObject::createBimpObject('bimpapi', 'API_Api', array(
                        'name' => 'atradius',
                        'title' => 'Atradius'
                            ), true, $errors, $warnings);

            if (BimpObject::objectLoaded($api)) {
                $param = (string) BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
                            'id_api' => $api->id,
                            'name' => 'prod_oauth_client_id',
                            'title' => 'ID Client OAuth en mode production'
                                ), true, $warnings, $warnings);

                $param = (string) BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
                            'id_api' => $api->id,
                            'name' => 'prod_oauth_client_secret',
                            'title' => 'Secret client OAuth en mode production'
                                ), true, $warnings, $warnings);

                $param = (string) BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
                            'id_api' => $api->id,
                            'name' => 'prod_api_key',
                            'title' => 'Clé API en mode production'
                                ), true, $warnings, $warnings);

                $param = (string) BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
                            'id_api' => $api->id,
                            'name' => 'test_oauth_client_id',
                            'title' => 'ID Client OAuth en mode test'
                                ), true, $warnings, $warnings);

                $param = (string) BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
                            'id_api' => $api->id,
                            'name' => 'test_oauth_client_secret',
                            'title' => 'Secret client OAuth en mode test'
                                ), true, $warnings, $warnings);

                $param = (string) BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
                            'id_api' => $api->id,
                            'name' => 'test_api_key',
                            'title' => 'Clé API en mode test'
                                ), true, $warnings, $warnings);

                $param = (int) BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
                            'id_api' => $api->id,
                            'name' => 'customer_id',
                            'title' => 'Id customer'
                                ), true, $warnings, $warnings);

                $param = (string) BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
                            'id_api' => $api->id,
                            'name'   => 'policy_id',
                            'title'  => 'Id policy'
                                ), true, $warnings, $warnings);
            }
        }

        return $errors;
    }
    
    
    // Les requêtes

}