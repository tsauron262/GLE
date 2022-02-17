<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapi/classes/BimpAPI.php';

class AtradiusAPI extends BimpAPI
{

    public static $name = 'atradius';
    public static $include_debug_json = false;
    public static $urls_bases = array(
        'default' => array(
            'prod' => 'https://api.atradius.com',
            'test' => 'https://api-uat.atradius.com'
        ),
        'auth'    => array(
            'prod' => 'https://api.atradius.com/authenticate/v2/tokens',
            'test' => 'https://api-uat.atradius.com/authenticate/v1/tokens'
        )
    );
    public static $requests = array(
        'authenticate'       => array(
            'label'         => 'Authentification',
            'url_base_type' => 'auth'
        ),
        'buyerDetails' => array(
            'label' => 'Details client',
            'url'   => '/credit-insurance/organisation-management/v1/buyers/my-buyers'
        ),
        'buyerDetails2' => array(
            'label' => 'Details client',
            'url'   => '/credit-insurance/organisation-management/v1/buyers/'
        ),
        'consulterStructure' => array(
            'label' => 'Consulter une structure',
            'url'   => '/cpro/structures/v1/consulter'
        ),
        'rechercheService'   => array(
            'label' => 'Recherche service',
            'url'   => '/cpro/structures/v1/rechercher/services'
        ),
        'deposerPdfFacture'  => array(
            'label' => 'Dépôt PDF facture',
            'url'   => '/cpro/factures/v1/deposer/pdf'
        ),
        'soumettreFacture'   => array(
            'label' => 'Envoi données facture',
            'url'   => '/cpro/factures/v1/soumettre'
        )
    );
    public static $tokens_types = array(
        'access' => 'Token d\'accès'
    );

    // Requêtes: 
    
    public function testRequest(&$errors = array(), &$warnings = array())
    {
//        return $this->execCurl('buyerDetails', array(
//                    'url_params'          => array(
//                        'customerId' => '512642950'
//                    )
//                        ), $errors);
        
        return $this->execCurl('buyerDetails2', array(
                    'url_end'          => '12345678'
                        ), $errors);
    }


    // Overrides: 

    public function connect(&$errors = array(), &$warnings = array())
    {

       
        if (!count($errors)) {
            $result = $this->execCurl('authenticate', array(
                'fields' => array('inut'=>'inut')), $errors);
//            print_r($result);
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

        return (!count($errors));
    }

    public function getDefaultRequestsHeaders($request_name, &$errors = array())
    {
        if ($this->isUserAccountOk($errors)) {
            if ($this->options['mode'] === 'test') {
                $client_id = BimpTools::getArrayValueFromPath($this->params, 'test_oauth_client_id', '');
                $client_secret = BimpTools::getArrayValueFromPath($this->params, 'test_oauth_client_secret', '');
            } else {
                $client_id = BimpTools::getArrayValueFromPath($this->params, 'prod_oauth_client_id', '');
                $client_secret = BimpTools::getArrayValueFromPath($this->params, 'prod_oauth_client_secret', '');
            }

            if ($client_id && $client_secret) {
                if ($request_name == 'authenticate') {
                    return array(
                        'Atradius-App-Key' => BimpTools::getArrayValueFromPath($this->params, 'test_api_key', ''),
                        'Authorization'  => 'Basic ' . base64_encode($client_id . ':' . $client_secret)
                    );
                }
                else{
                    return array(
                        'Authorization' => 'Bearer ' . $this->userAccount->getToken('access'),
                        'Atradius-App-Key' => BimpTools::getArrayValueFromPath($this->params, 'test_api_key', '')
                    );
                }
            }
        }

        return array();
    }

    public function processRequestResponse($request_name, $response_code, $response_body, $response_headers = array(), &$infos = '', &$errors = array())
    {
        $return = $response_body;
        switch ($response_code) {
            case '400':
                $errors[] = 'Requête incorrecte';
                break;

            case '401':
                $errors[] = 'Non autentifié';
                $return = 'unauthenticate';
                break;

            case '403':
                $errors[] = 'Accès refusé';
                break;

            case '404':
                $errors[] = 'API non trouvée';
                break;

            case '405':
                $errors[] = 'Format de la requête non supoorté';
                break;

            case '500':
                $errors[] = 'Erreur interne serveur';
                break;
        }

        if (isset($response_body['codeRetour']) && (int) $response_body['codeRetour'] !== 0 && isset($response_body['libelle'])) {
            $errors[] = $response_body['libelle'];
        }

        return $return;
    }

//    public function testRequest(&$errors = array(), &$warnings = array())
//    {
//        return $this->execCurl('rechercheStructure', array(
//                    'allow_reconnect' => 0,
//                    'fields'          => array(
//                        'structure' => array(
//                            'identifiantStructure'     => '18008901303720',
//                            'typeIdentifiantStructure' => 'SIRET'
//                        )
//                    )
//                        ), $errors);
//    }


    // Install: 

    public function install(&$warnings = array())
    {
        $errors = array();

        $bdb = BimpCache::getBdb();

        if ((int) $bdb->getValue('bimpapi_api', 'id', 'name = \'atradius\'')) {
            $errors[] = 'Cette API a déjà été installée';
        } else {
            $api = BimpObject::createBimpObject('bimpapi', 'API_Api', array(
                        'name'  => 'atradius',
                        'title' => 'Atradius'
                            ), true, $errors, $warnings);

            if (BimpObject::objectLoaded($api)) {
                $param = BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
                            'id_api' => $api->id,
                            'name'   => 'prod_oauth_client_id',
                            'title'  => 'ID Client OAuth en mode production'
                                ), true, $warnings, $warnings);

                $param = BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
                            'id_api' => $api->id,
                            'name'   => 'prod_oauth_client_secret',
                            'title'  => 'Secret client OAuth en mode production'
                                ), true, $warnings, $warnings);

                $param = BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
                            'id_api' => $api->id,
                            'name'   => 'prod_api_key',
                            'title'  => 'Clé API en mode production'
                                ), true, $warnings, $warnings);

                $param = BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
                            'id_api' => $api->id,
                            'name'   => 'prod_api_secret',
                            'title'  => 'Secret API en mode production'
                                ), true, $warnings, $warnings);

                $param = BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
                            'id_api' => $api->id,
                            'name'   => 'prod_id_fournisseur',
                            'title'  => 'Identifiant fournisseur en mode production'
                                ), true, $warnings, $warnings);

                $param = BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
                            'id_api' => $api->id,
                            'name'   => 'test_oauth_client_id',
                            'title'  => 'ID Client OAuth en mode test'
                                ), true, $warnings, $warnings);

                $param = BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
                            'id_api' => $api->id,
                            'name'   => 'test_oauth_client_secret',
                            'title'  => 'Secret client OAuth en mode test'
                                ), true, $warnings, $warnings);

                $param = BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
                            'id_api' => $api->id,
                            'name'   => 'test_api_key',
                            'title'  => 'Clé API en mode test'
                                ), true, $warnings, $warnings);

                $param = BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
                            'id_api' => $api->id,
                            'name'   => 'test_api_secret',
                            'title'  => 'Secret API en mode test'
                                ), true, $warnings, $warnings);

                $param = BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
                            'id_api' => $api->id,
                            'name'   => 'test_id_fournisseur',
                            'title'  => 'Identifiant fournisseur en mode test'
                                ), true, $warnings, $warnings);
            }
        }

        return $errors;
    }
}
