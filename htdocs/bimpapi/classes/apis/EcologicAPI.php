<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapi/classes/BimpAPI.php';

class EcologicAPI extends BimpAPI
{
    //token prod 9de90458-8d42-43a6-9a9c-4b0c066004f0
    //URL du site SwaggerUI = https://preprod-apiecologic.e-reparateur.eco/apidoc/ui/index#/
    //URL du site Swagger = https://apiecologic.e-reparateur.eco/apidoc/ui/index#/

    
    public static $asUser = false;
    public static $name = 'ecologic';
    public static $include_debug_json = false;
    public static $urls_bases = array(
        'default' => array(
            'prod' => 'https://apiecologic.e-reparateur.eco/api/v1/ecosupport/',
            'test' => 'https://preprod-apiecologic.e-reparateur.eco/api/v1/ecosupport'
        ),
//        'auth'    => array(
//            'prod' => 'https://api.atradius.com/authenticate/v2/tokens', /* bug si auth sur l'url de prod */
//            'prod' => 'https://api-uat.atradius.com/authenticate/v1/tokens',
//            'test' => 'https://api-uat.atradius.com/authenticate/v1/tokens'
//        )
    );
    public static $requests = array(
        'getrepairsitesbyats' => array(
            'label'         => 'Récupération des informations du site associé à la clé API ',
            'url'           => 'getrepairsitesbyats'
        ),
//        'getMyBuyer'   => array(
//            'label' => 'Details de nos client',
//            'url'   => '/credit-insurance/organisation-management/v1/buyers/my-buyers'
//        ),
//        'getBuyer'     => array(
//            'label' => 'Details client',
//            'url'   => '/credit-insurance/organisation-management/v1/buyers'
//        ),
//        'getCover'     => array(
//            'label' => 'Get assurances',
//            'url'   => '/credit-insurance/cover-management/v1/covers'
//        ),
//        'createCover'  => array(
//            'label' => 'Créer assurance',
//            'url'   => '/credit-insurance/cover-management/v1/covers',
//        ),
//        'updateCover'  => array(
//            'label' => 'Editer assurance',
//            'url'   => '/credit-insurance/cover-management/v1/covers',
//        ),
//        'deleteCover'  => array(
//            'label' => 'Suppression assurance',
//            'url'   => '/credit-insurance/cover-management/v1/covers',
//        ),
//        'decisions'    => array(
//            'label' => 'Vérification des décisions',
//            'url'   => '/credit-insurance/cover-management/v1/covers'
//        )
    );
//    public static $tokens_types = array(
//        'access' => 'Token d\'accès'
//    );

    // Requêtes: 

    // Settings:

    public function testRequest(&$errors = array(), &$warnings = array())
    {
        $data = $this->executereqWithCache('getrepairsitesbyats', array(
            'url_params' => array()
                ), $errors, $warnings);
        
        if(isset($data['ResponseData']))
            $warnings[] = count($data['ResponseData']).' résultats';
        

        return $data;
    }
    
    public function executereqWithCache($name, $params = array(), &$errors = array(), &$warnings = array()){
        $key = 'apiReqEcologic'.$name;//.base64_encode(json_encode($params));
        $warnings[] = 'key'.$key;
        $cache = BimpCache::getCacheServeur($key);
        if(is_null($cache)){
            $cache = $this->execCurl('getrepairsitesbyats', $params, $errors);
            BimpCache::setCacheServeur($key, $cache);
        }
        else{
            die('cache ok');
        }
        return $cache;
    }

    // Overrides: 

   

    public function getDefaultRequestsHeaders($request_name, &$errors = array())
    {
        return array(
            'api_key'    => BimpTools::getArrayValueFromPath($this->params, $this->getOption('mode', 'test').'_api_key', '')
        );
    }

    public function processRequestResponse($request_name, $response_code, $response_body, $response_headers = array(), &$infos = '', &$errors = array())
    {

//        if($request_name == 'authenticate')
        $return = $response_body;
//        else
//        $this->getErrors($response_body, $errors);

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
        
        if (isset($response_body['faultstring']) && (string) $response_body['faultstring']) {
            $errors[] = $response_body['faultstring'];
        }

        return $return;
    }

    // Install: 

    public function install($title = '', &$warnings = array())
    {
        $errors = array();

        $api = BimpObject::createBimpObject('bimpapi', 'API_Api', array(
                    'name'  => 'ecologic',
                    'title' => ($title ? $title : $this->getDefaultApiTitle())
                        ), true, $errors, $warnings);

        if (BimpObject::objectLoaded($api)) {

            BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
                        'id_api' => $api->id,
                        'name'   => 'prod_api_key',
                        'title'  => 'Clé API en mode production'
                            ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
                        'id_api' => $api->id,
                        'name'   => 'test_api_key',
                        'title'  => 'Clé API en mode test'
                            ), true, $warnings, $warnings);
        }

        return $errors;
    }
}
