<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapi/classes/BimpAPI.php';

class DocusignAPI extends BimpAPI {

    // PW web (dev@bimp.fr): HLxmS57W3uz8
    
// Auth fonction en prod:
// Code
// https://account-d.docusign.com/oauth/auth?response_type=code&scope=signature&client_id=3b602db6-78eb-47f2-8a61-454fcb21836e&redirect_uri=http://localhost/
    
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
        'getEnvelopeFile' => array(
            'label' => 'Obtention d\'un fichier de signature',
        ),
        'getTemplates' => array(
            'label' => 'Obtention des modèles'
        ),
        'getUser' => array(
            'label' => 'Obtention de l\'utilisateur'
        )
    );
    
    
    // Liste des requêtes ou l'utilisateur doit utiliser SON compte et pas celui par défaut
    public static $user_requests = array('authenticate', 'sendEnvelope', 'getEnvelope', 'getEnvelopeFile', 'getTemplates');
    
    public static $tokens_types = array(
        'access'  => 'Token d\'accès',
        'code'    => 'Code pour obtenir les tokens',
        'refresh' => 'Token de rafraîchissement',
    );

    // Requêtes
    
    public function createEnvelope($params, $object, &$errors = array(), &$warnings = array()) {
        
        $signature = $object->getChildObject('signature');
        if (!BimpObject::objectLoaded($signature))
            $errors[] = ucfirst($object->getLabel('this')) . ' n\'est lié à aucune signature';

        

        $id_account = $this->userAccount->getData('login');
        
        $result = $this->execCurlCustom('sendEnvelope', array(
            'fields' => array(
                'status' => 'sent',
                'emailSubject' => "Merci de signer " . $object->getLabel('this'),
                'documents' => array(
                        array(
                            'documentBase64' => base64_encode(file_get_contents($params['file'])),
                            'documentId' => 1,
                            'name' => $object->getSignatureDocFileName())
                    ),
                'recipients' => array('signers' => $this->getSigners($params, $object, $errors))
            ),
            'type' => 'FILE',
            'url_end' => '/restapi/v2.1/accounts/' . $id_account . '/envelopes'
            ), $errors, $response_headers, $response_code, $warnings);
        
        
        return $result;
    }
    
    public function getEnvelope($params, &$errors = array(), &$warnings = array()) {
        
        $data = $this->execCurlCustom('getEnvelope', array(
            'url_end' => '/restapi/v2.1/accounts/' . $params['id_account'] . '/envelopes/' . $params['id_envelope'] // . '/consumer_disclosure/FR' // . '/comments/transcript'
            ), $errors, $response_headers, $response_code, $warnings);
        
        
        return $data;
    }
    
    public function getEnvelopeFile($params, &$errors = array(), &$warnings = array()) {
        
        if(!isset($params['id_document']))
            $params['id_document'] = 1;
        
        if(!isset($params['id_account']))
            $params['id_account'] = $this->userAccount->getData('login');
        
        $data = $this->execCurlCustom('getEnvelopeFile', array(
            'url_end' => '/restapi/v2.1/accounts/' . $params['id_account'] . '/envelopes/' . $params['id_envelope'] . '/documents/' . $params['id_document'],
//            'url_params' => array('outputFile' => 'test'),
            'headers' => array(
                'Content-Transfer-Encoding' => 'base64',
                ''
            )
           ), $errors, $response_headers, $response_code, $warnings);
        
        return $data;
    }
    
    
    public function setUserIdAccount($id_user, &$errors = array(), &$warnings = array()) {
        
        // Tests
        if((int) $id_user < 1) {
            $errors[] = "Id de l'utilisateur non valide";
            return '';
        }
        
        $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $id_user);
        
        if(!$user->field_exists('id_docusign'))
            $errors[] = "Le champs id_docusign n'existe pas";
        
        if($user->getData('email') == '')
            $errors[] = "L'utilisateur n'a pas d'email";
        
        if(!empty($errors))
            return '';

        // Requête
         $id_account = $this->userAccount->getData('login');
//        $id_account = BimpTools::getArrayValueFromPath($this->params, 'default_acc_id', ''); // TODO
        
        $data = $this->execCurlCustom('getUser', array(
            'url_params' => array('email' => $user->getData('email'), 'additional_info' => 'true'),
            'url_end' => '/restapi/v2.1/accounts/' . $id_account . '/users/'
            ), $errors, $response_headers, $response_code, $warnings);
        

        // Traitement
        $remote_user = $data['users'][0];
        
        if(!is_array($remote_user)) {
            $errors[] = "Client inconnu pour l'adresse email : " . $user->getData('email');
            return '';
        }
        
        $errors = BimpTools::merge_array($errors, $user->updateField('id_docusign', $remote_user['defaultAccountId']));
        
        
        
        return $remote_user['defaultAccountId'];
    }
    
    // Getters
    
    public function getSigners($params, $object, &$errors = array()) {
        $signers = array();
        switch (get_class($object)) {
            case 'BContract_contrat':
                $signers = $this->getSignersContract($params, $object);
                break;
            
            case 'Bimp_Propal':
                $signers = $this->getSignersPropal($params, $object);
                break;

            default:
                $errors[] = "Type d'object non prit en charge : " . get_class($object);
                break;
        }
        
        return $signers;
    }
    
    public function getSignersContract($params, $object = null) {
       $client = $params['client'];
//       $comm = $params['comm'];
       
        $signers = array(
            
            // Client
            array (
                'email'       => 'pelegrinromain@gmail.com', // TODO $comm['email']
                'name'        =>  $client['prenom']  . ' ' . $client['nom'],
                'signerEmail' => 'pelegrinromain@gmail.com', // TODO $client['email']
                'recipientId' => '1',
                'routingOrder'=> '1',
                'emailNotification' => array(
                    'emailSubject' => "Merci de signer " . $object->getLabel('this'),
                    'emailBody' => $object->getDefaultSignDistEmailContent()
                ),
                'tabs'        => array(
                    'signHereTabs' => array(
                        array(
                            'name'          => "Signez ici",
                            'anchorString'  => "Signature des conditions générales de contrat",
                            'anchorXOffset' => 0,
                            'anchorYOffset' => 75
                        )
                    )
                )
            )
        );
        
        return $signers;
    }
    
    // TODO supprimé une fois l'autre validé
    public function getSignersContractOld($params, $object = null) {
       $client = $params['client'];
       $comm = $params['comm'];
       
        $signers = array(
            
            // Commercial
            array(
                'email'       => 'r.pelegrin@bimp.fr', // TODO $comm['email']
                'name'        =>  $comm['nom'] . ' ' . $comm['prenom'],
                'signerEmail' => 'r.pelegrin@bimp.fr', // TODO $comm['email']
                'recipientId' => '1',
                'routingOrder'=> '1',
                'emailNotification' => array(
                    'emailSubject' => "Merci de signer " . $object->getLabel('this'),
                    'emailBody' => $object->getDefaultSignDistEmailContent()
                ),
                'tabs'        =>  array(
                    'signHereTabs' => array(
                        array(
                            'name'          => "Signez ici vendeur 1 ",
                            'anchorString'  => "Nom et fonction du signataire :",
                            'anchorXOffset' => 25,
                            'anchorYOffset' => 95
                        ),
                        array(
                            'name'          => "Signez ici vendeur 2",
                            'anchorString'  => "+ paraphe sur chaque page",
                            'anchorXOffset' => -200,
                            'anchorYOffset' => 20
                        )
                    ),
                    'dateSignedTabs' => array(
                        array(
                            'name'          => "Date signature",
                            'anchorString'  => "Nom et fonction du signataire :",
                            'anchorXOffset' => 17,
                            'anchorYOffset' => 15,
                            'fontSize'      => 'Size12'
                        )
                    ),
                    'textTabs' => array(
                        array(
                            'name'          => "Nom",
                            'anchorString'  => "Nom et fonction du signataire :",
                            'anchorXOffset' => 100,
                            'anchorYOffset' => 0,
                            'value'         => $comm['nom'] . ' ' . $comm['prenom']
                        ),
                        array(
                            'name'          => "Fonction",
                            'anchorString'  => "Nom et fonction du signataire :",
                            'anchorXOffset' => 100,
                            'anchorYOffset' => 15,
                            'value'         => $comm['fonction']
                        )
                    ),
                )
            ),
            
            // Client
            array (
                'email'       => 'pelegrinromain@gmail.com', // TODO $comm['email']
                'name'        =>  $client['prenom']  . ' ' . $client['nom'],
                'signerEmail' => 'pelegrinromain@gmail.com', // TODO $client['email']
                'recipientId' => '2',
                'routingOrder'=> '2',
                'emailNotification' => array(
                    'emailSubject' => "Merci de signer " . $object->getLabel('this'),
                    'emailBody' => $object->getDefaultSignDistEmailContent()
                ),
                'tabs'        => array(
                    'signHereTabs' => array(
                        array(
                            'name'          => "Signez ici",
                            'anchorString'  => "Signature des conditions générales de contrat",
                            'anchorXOffset' => 0,
                            'anchorYOffset' => 75
                        ),
                        array(
                            'anchorString'  => "+ paraphe sur chaque page",
                            'anchorXOffset' => 0,
                            'anchorYOffset' => 85,
                            'fontSize'      => 'Size12'
                        )
                    ),
                    'textTabs' => array(
                        array(
                            'name'          => "Paraphe",
                            'anchorString'  => "Paraphe :",
                            'anchorXOffset' => 37,
                            'anchorYOffset' => -3,
                            'value'         => ucfirst(substr($client['nom'], 0, 1)) . ucfirst(substr($client['prenom'], 0, 1)),                ),
                        array(
                            'name'          => "Nom + fonction",
                            'anchorString'  => "+ paraphe sur chaque page",
                            'anchorXOffset' => 0,
                            'anchorYOffset' => 33,
                            'value'         => $client['nom'] . ' ' . (isset($client['fonction']) ? $client['fonction'] : '')
                        ),
                        array(
                            'name'          => "Lu et approuvé",
                            'anchorString'  => "Nom, fonction et cachet du signataire :",
                            'anchorXOffset' => 120,
                            'anchorYOffset' => -18,
                            'value'         => "Lu et approuvé"
                        ),
                        array(
                            'name'          => "Nom",
                            'anchorString'  => "Nom, fonction et cachet du signataire :",
                            'anchorXOffset' => 120,
                            'anchorYOffset' => -8,
                            'value'         => $client['nom'] . ' ' . $client['prenom']
                        ),
                        array(
                            'name'          => "Fonction",
                            'anchorString'  => "Nom, fonction et cachet du signataire :",
                            'anchorXOffset' => 120,
                            'anchorYOffset' => 5,
                            'value'         => (isset($client['fonction']) ? $client['fonction'] : '')
                        )
                    ),
                    'dateSignedTabs' => array(
                        array(
                            'name'          => "Date signature 1",
                            'anchorString'  => "Signature des conditions générales de contrat",
                            'anchorXOffset' => 11,
                            'anchorYOffset' => 15,
                            'fontSize'      => 'Size12'
                        ),
                        array(
                            'name'          => "Date signature 2",
                            'anchorString'  => "+ paraphe sur chaque page",
                            'anchorXOffset' => 0,
                            'anchorYOffset' => 20,
                            'fontSize'      => 'Size12'
                        )
                    )
                )
            )
        );
        
        return $signers;
    }
    
    public function getSignersPropal($params, $object = null) {

        return array(
            'signHereTabs' => array(
                array(
                    'name'          => "Signez ici",
                    'anchorString'  => "Signature + Cachet avec SIRET :",
                    'anchorXOffset' => 30,
                    'anchorYOffset' => 50
                )
            ),
            'lastNameTabs' => array(
                array(
                    'anchorString'        => "Nom du signataire :",
                    'anchorXOffset'       => 60,
                    'anchorCaseSensitive' => true,
                    'value'         => $params['nom_signataire']
                )
            ),
            'firstNameTabs' => array(
                array(
                    'anchorString'  => "Prénom du signataire :",
                    'anchorXOffset' => 72,
                    'value'         => $params['prenom_signataire']
                )
            ),
            'textTabs' => array(
                array(
                    'name'          => "Fonction",
                    'anchorString'  => "Fonction du signataire :",
                    'anchorXOffset' => 65,
                    'value'         => $params['fonction_signataire'],
                    'locked'        => false
                )
            ),
            'dateSignedTabs' => array(
                array(
                    'anchorString'  => "Date de signature :",
                    'anchorXOffset' => 60
                )
            )
        );
    }
    
    // Interface
    

    
    // Traitement
    
    public function getBaseUrl($type = 'default') {
        return BimpTools::getArrayValueFromPath(static::$urls_bases, $type . '/' . $this->options['mode'], '');
    }

    
    // Override
    
    private function execCurlCustom($request_name, $params = array(), &$errors = array(), &$response_headers = array(), &$response_code = -1, &$warnings = array()) {
        
        if(isset($params['depth'])) {
            if(4 < $params['depth']) {
                $errors[] = "execCurlCustom exécuté " . $params['depth'] . " fois";
                return array();
            }
        }
        
        // Il s'agit d'une requête suivit par un utilisateur.
        // On empèche l'utilisation du compte par défaut
        if(in_array($request_name, self::$user_requests)) {
            global $user;
            $bimp_user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $user->id);
            $id_docusign = $bimp_user->getData('id_docusign');

            // Le compte connecté est différent de celui de l'utilisateur
            if($id_docusign != $this->userAccount->getData('login')) {
                // L'utilisateur a un compte DocuSign renseigné dans l'ERP
                if($id_docusign) {
                    $this->fetchUserAccount(0, $id_docusign);
                
                // Le compte DocuSign de l'utilisateur n'est pas définit
                } else {
                    // On vérifie si le compte DocuSign de l'utilisateur existe
                    $remote_id_user = $this->setUserIdAccount((int) $user->id, $errors);
                    if(count($errors))
                        return array();
                    
                    // Il existe, si le compte utilisateur de l'API n'existe pas on le créer
                    // On connecte le compte utilisateur de l'API
                    if($remote_id_user) {
                        $this->fetchUserAccount(0, $remote_id_user);
                        $connexion_ok = $this->connect($errors, $warnings);
                        
                        if($connexion_ok and !count($warnings)) {
                            $params['depth']++;
                            return $this->execCurlCustom($request_name, $params, $errors, $response_headers, $response_code, $warnings);
                        } else {
                            return array();
                        }
                        
                    // Il n'existe pas
                    } else {
                        $errors[] = "Le compte utilisateur \"" . $id_docusign . "\" n'existe pas ou n'est pas renseigner comme appartenant à Bimp";
                        return array();
                    }
                }
            }
        }
        
        if(isset($params['depth']))
            unset($params['depth']);

        if(empty($errors) and empty($warnings))
            $return = $this->execCurl($request_name, $params, $errors, $response_headers, $response_code);
        
        // Gestion des erreurs
        if(is_array($return)) {
            if(isset($return['errorCode']) or isset($return['message']))
                $errors[] = $return['errorCode'] . ' : ' . $return['message'];

        }
        
        return $return;
    }

    public function testRequest(&$errors = array(), &$warnings = array()) {
        
        $id_account = $this->userAccount->getData('login');
        $params = array();
        $params['id_account'] = '46a93e07-3d31-4e8b-9cca-42706740d150';
        $params['id_envelope'] = '829172a0-2169-4716-8b72-89f7ed6b7cec';
        
        $this->getEnvelope($params);
        
//        $this->getTemplates($params);
        
//        $this->setUserIdAccount(1224, $errors);

//        $this->reqCreateEnvelope($params, $errors);
        
        

    }

    public function connect(&$errors = array(), &$warnings = array(), $tentative = 0) {
        
        if($this->userAccount->isLogged())
            return count($errors);
        else {
            $this->addDebug($this->userAccount->getData('name') . ' nest pas connecté<br/>');
        }
        
        $result = '';
//        $url_redirect = DOL_URL_ROOT . '/bimpapi/retour/RetDocuSign.php';
        $url_redirect = 'http://localhost/bimp-erp/htdocs/bimpapi/retour/RetDocuSign.php';
       $client_id = BimpTools::getArrayValueFromPath($this->params, $this->options['mode'] . '_oauth_client_id', '');

        $tentative++;
        if($tentative > 4)
            die('boucle infinit');
        
        $code = $this->userAccount->getToken('code');
        $refresh_token = $this->userAccount->getToken('refresh');
        
        // On redirige l'utilisateur pour qu'il puisse se connecter
        if($code . 'x' == 'x' and $refresh_token . 'x' == 'x') {
            $_SESSION['id_user_docusign'] = $this->userAccount->id;
            
            $link = $this->getBaseUrl('auth') . "/oauth/auth?response_type=code&scope=signature&client_id=" . $client_id . "&redirect_uri=" . $url_redirect;
            $errors[] = $this->userAccount->getData('name') . " n'est pas connecté à DocuSign <a target='_blank' href='" . $link ."'>cliquez ici</a>";
        
            
        // Il a déjà rentré ses identifiant
        } else {
            
            // On utilise le token de raffraichissement si il existe
            if($refresh_token . 'x' != 'x') {
                $result = $this->execCurlCustom('authenticate', array(
                    'fields' => array(
                        'grant_type' => 'refresh_token',
                        'refresh_token' => $refresh_token
                        )), $errors, $response_headers, $response_code, $warnings);
            }
            
            // On utilise le token d'accès si il existe
            $access_token = BimpTools::getArrayValueFromPath($result, 'access_token', '');
            if(!$access_token) {
                // Get token 
                $result = $this->execCurlCustom('authenticate', array(
                    'fields' => array(
                        'grant_type' => 'authorization_code',
                        'code' => $code
                        )), $errors, $response_headers, $response_code, $warnings);
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
        
        return (!count($errors));
    }
    
    public function getDefaultRequestsHeaders($request_name, &$errors = array()) {
        if ($this->isUserAccountOk($errors)) {
            $client_id = BimpTools::getArrayValueFromPath($this->params, $this->options['mode'] . '_oauth_client_id', '');
            $client_secret = BimpTools::getArrayValueFromPath($this->params, $this->options['mode'] . '_oauth_client_secret', '');

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
        }

        return array();
    }
    
    protected function fetchUserAccount($id_user_account = 0, $id_docusign = '') {
        global $user;
        $fetch_user_acc++;
        if($fetch_user_acc >= 4)
            die('Trop de fetch user acc');
        
        
        if($id_docusign == '') {
            $bimp_user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $user->id);
            $id_docusign = $bimp_user->getData('id_docusign');
        }
        
        
        if($id_docusign != '') {
            $user_account = BimpCache::findBimpObjectInstance('bimpapi', 'API_UserAccount', array(
                                        'id_api' => $this->apiObject->id,
                                        'login'  => $id_docusign
                                        ), true);
            
            // Le compte utilisateur n'existe pas dans l'ERP, on le créer
            if(!BimpObject::objectLoaded($user_account)) {
                
                if(!BimpObject::objectLoaded($bimp_user))
                    $bimp_user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $user->id);
                
                if(!$this->id)
                    $id_api = (int) BimpCache::getBdb()->getValue('bimpapi_api', 'id', 'name = "docusign"');
                else
                    $id_api = (int) $this->id;

                global $dont_rollback;
                $dont_rollback = true;
                $user_account = BimpObject::getInstance("bimpapi", "API_UserAccount");
                $values = array(
                    'id_api' => (int) $id_api,
                    'users' => array((int) $bimp_user->id),
                    'name' => $bimp_user->getData('firstname') . ' ' . $bimp_user->getData('lastname'),
                    'login' => $id_docusign,
                    'pword' => 'inutile',
                    'tokens' => array()
                );
                $e_v = $user_account->validateArray($values);
                $e_c = $user_account->create();
            }
            
            $id_user_account = $user_account->id;
        }
        
        return parent::fetchUserAccount($id_user_account);
    }

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
                
                $param = BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
                            'id_api' => $api->id,
                            'name' => 'prod_oauth_client_secret',
                            'title' => 'Secret client OAuth en mode test',
                            'value' => ''
                                ), true, $warnings, $warnings);
                
                $param = BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
                            'id_api' => $api->id,
                            'name' => 'prod_oauth_client_id',
                            'title' => 'ID Client OAuth en mode test',
                            'value' => ''
                                ), true, $warnings, $warnings);
                
//                $param = BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
//                            'id_api' => $api->id,
//                            'name' => 'url_redirect',
//                            'title' => 'Lien vers la racine du projet',
//                            'value' => DOL_URL_ROOT . '/'
//                                ), true, $warnings, $warnings);

            }
        }

        return $errors;
    }

}