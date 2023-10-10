<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapi/classes/BimpAPI.php';

class DocusignAPI extends BimpAPI
{

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
    public static $urls_bases = array(
        'default' => array(
            'test' => 'https://demo.docusign.net',
            'prod' => 'https://eu.docusign.net'
        ),
        'auth'    => array(
            'test' => 'https://account-d.docusign.com',
            'prod' => 'https://account.docusign.com'
        )
    );
    public static $requests = array(
        'getCode'         => array(
            'label' => 'Obtenir le code pour le token',
            'url'   => '/oauth/auth'
        ),
        'authenticate'    => array(
            'label'         => 'Authentification',
            'url_base_type' => 'auth',
            'url'           => '/oauth/token'
        ),
        'createEnvelope'  => array(
            'label' => 'Envoie signature'
        ),
        'getEnvelope'     => array(
            'label' => 'Obtention signature'
        ),
        'getEnvelopeFile' => array(
            'label' => 'Obtention d\'un fichier de signature',
        ),
        'getTemplates'    => array(
            'label' => 'Obtention des modèles'
        ),
        'getUser'         => array(
            'label' => 'Obtention de l\'utilisateur'
        ),
        'createHook'      => array(
            'label' => 'Création du webhook'
        )
    );
    // Liste des requêtes où l'utilisateur doit utiliser SON compte et pas celui par défaut
    public static $user_requests = array('createEnvelope', 'getEnvelope', 'getEnvelopeFile', 'getTemplates');
    public static $tokens_types = array(
        'access'  => 'Token d\'accès',
        'code'    => 'Code pour obtenir les tokens',
        'refresh' => 'Token de rafraîchissement',
    );

    public function getBaseUrl($type = 'default')
    {
        return BimpTools::getArrayValueFromPath(static::$urls_bases, $type . '/' . $this->options['mode'], '');
    }

    // Requêtes:

    public function createEnvelope($file_dir, $file_name, $subject, $signers, &$errors = array(), &$warnings = array())
    {
        $id_account = $this->getParam($this->getOption('mode', 'test') . '_id_compte_api', '');

        if (!$id_account) {
            $errors[] = 'ID compte DocuSign non configuré pour le mode "' . $this->getOption('mode', 'test') . '"';
        }

        if (!is_file($file_dir . $file_name)) {
            $errors[] = 'Fichier "' . $file_name . '" absent';
        }

        if (!count($errors)) {
            $result = $this->execCurl('createEnvelope', array(
                'fields'  => array(
                    'status'       => 'sent',
                    'emailSubject' => $subject,
                    'documents'    => array(
                        array(
                            'documentBase64' => base64_encode(file_get_contents($file_dir . $file_name)),
                            'documentId'     => 1,
                            'name'           => $file_name
                        ),
                    ),
                    'recipients'   => array('signers' => $signers)
                ),
                'type'    => 'FILE',
                'url_end' => '/restapi/v2.1/accounts/' . $id_account . '/envelopes'
                    ), $errors);
        }

        return $result;
    }

    public function getEnvelope($id_envelope, &$errors = array(), &$warnings = array())
    {
        if (!$id_envelope) {
            $errors[] = 'ID Enveloppe DocuSign absent';
        }

        $id_account = $this->getParam($this->getOption('mode', 'test') . '_id_compte_api', '');

        if (!$id_account) {
            $errors[] = 'ID compte DocuSign non configuré pour le mode "' . $this->getOption('mode', 'test') . '"';
        }

        if (!count($errors)) {
            return $this->execCurl('getEnvelope', array(
                        'url_end' => '/restapi/v2.1/accounts/' . $id_account . '/envelopes/' . $id_envelope // . '/consumer_disclosure/FR' // . '/comments/transcript'
                            ), $errors);
        }

        return array();
    }

    public function getEnvelopeFile($id_envelope, $id_document = 1, &$errors = array(), &$warnings = array())
    {
        // Pour télécharger le certificat : $id_document = 'certificat'

        if (!$id_envelope) {
            $errors[] = 'ID envelope DocuSign absent';
            return array();
        }

        $id_account = $this->getParam($this->getOption('mode', 'test') . '_id_compte_api', '');

        return $this->execCurl('getEnvelopeFile', array(
                    'url_end' => '/restapi/v2.1/accounts/' . $id_account . '/envelopes/' . $id_envelope . '/documents/' . $id_document,
                    'headers' => array(
                        'Content-Transfer-Encoding' => 'base64'
                    )
                        ), $errors);
    }

    public function setUserIdAccount($id_user, &$errors = array(), &$warnings = array())
    {
        if ((int) $id_user < 1) {
            $errors[] = "Id de l'utilisateur non valide";
            return '';
        }

        $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $id_user);

        if (!BimpObject::objectLoaded($user)) {
            $errors[] = 'L\'utilisateur #' . $id_user . ' n\'existe pas';
            return '';
        } else {
            $userAccount = BimpCache::findBimpObjectInstance('bimpapi', 'API_UserAccount', array(
                        'id_api' => $this->apiObject->id,
                        'users'  => array(
                            'part_type' => 'middle',
                            'part'      => '[' . $user->id . ']'
                        )
                            ), true, false);

            if (BimpObject::objectLoaded($userAccount)) {
                // Le compte utilisateur existe déjà. 
                // On ne déclenche pas d'erreur
                $this->userAccount = $userAccount;
                return $userAccount->getData('login');
            }
        }

        if (!$user->getData('email')) {
            $errors[] = 'Adresse e-mail absente pour l\'utilisateur "' . $user->getName() . '"';
        }

        $id_account = $this->getParam($this->getOption('mode', 'test') . '_id_compte_api', '');
        if (!$id_account) {
            $errors[] = 'ID compte DocuSign non configuré pour le mode "' . $this->getOption('mode', 'test') . '"';
        }

        if (!empty($errors)) {
            return '';
        }

        $data = $this->execCurl('getUser', array(
            'url_params' => array(
                'email'           => $user->getData('email'),
                'additional_info' => 'true'
            ),
            'url_end'    => '/restapi/v2.1/accounts/' . $id_account . '/users/'
                ), $errors);

        if (!isset($data['users'][0]) || !is_array($data['users'][0])) {
            $errors[] = 'Utilisateur inconnu pour l\'adresse email : "' . $user->getData('email') . '"';
            return '';
        }

        $remote_user = $data['users'][0];

        if (!BimpObject::objectLoaded($this->apiObject)) {
            $id_api = (int) BimpCache::getBdb()->getValue('bimpapi_api', 'id', 'name = "docusign"');
        } else {
            $id_api = (int) $this->apiObject->id;
        }

        $user_account = BimpObject::getInstance("bimpapi", "API_UserAccount");
        $create_errors = $user_account->validateArray(array(
            'id_api' => (int) $id_api,
            'users'  => array((int) $user->id),
            'name'   => $user->getData('firstname') . ' ' . $user->getData('lastname'),
            'login'  => $remote_user['userId'],
            'pword'  => '',
            'tokens' => array()
        ));

        if (!count($create_errors)) {
            $create_warnings = array();
            $create_errors = $user_account->create($create_warnings, true);
        }

        if (count($create_errors)) {
            $errors[] = BimpTools::getMsgFromArray($create_errors, 'Echec de la création du compte utilisateur');
        } else {
            $this->userAccount = $user_account;
        }

        return BimpTools::getArrayValueFromPath($remote_user, 'userId', '');
    }

    public function createHook(&$errors = array(), &$warnings = array())
    {
        $id_account = $this->getParam($this->getOption('mode', 'test') . '_id_compte_api', '');

        if (!$id_account) {
            $errors[] = 'ID compte DocuSign non configuré pour le mode "' . $this->getOption('mode', 'test') . '"';
        }

        if (!empty($errors)) {
            return array();
        }

        $params = array(
            'allUsers'                => 'true',
            'allowEnvelopePublish'    => 'true',
            'enableLog'               => 'true',
            'requiresAcknowledgement' => 'true',
            "eventData"               => array('version' => "restv2.1"),
            'deliveryMode'            => 'SIM',
            'envelopeEvents'          => array('Completed'),
            'connectId'               => "1", // hook_enveloppe_signed
            'configurationType'       => 'custom',
            'urlToPublishTo'          => "https:/" . DOL_URL_ROOT . "/bimpapi/retour/DocusignEnvelopeSigned.php",
            'name'                    => 'Hook enveloppe signée',
        );

        return $this->execCurl('createHook', array(
                    'fields'  => $params,
                    'url_end' => '/restapi/v2.1/accounts/' . $id_account . '/connect'
                        ), $errors);

        //  "connectId": "sample string 1",
        //  "configurationType": "sample string 2",
        //  "urlToPublishTo": "sample string 3",
        //  "name": "sample string 4",
        //  "allowEnvelopePublish": "sample string 5",
        //  "enableLog": "sample string 6",
        //  "includeDocuments": "sample string 7",
        //  "includeCertificateOfCompletion": "sample string 8",
        //  "requiresAcknowledgement": "sample string 9",
        //  "signMessageWithX509Certificate": "sample string 10",
        //  "useSoapInterface": "sample string 11",
        //  "includeTimeZoneInformation": "sample string 12",
        //  "includeHMAC": "sample string 13",
        //  "includeEnvelopeVoidReason": "sample string 14",
        //  "includeSenderAccountasCustomField": "sample string 15",
        //  "envelopeEvents": "sample string 16",
        //  "recipientEvents": "sample string 17",
        //  "userIds": "sample string 18",
        //  "soapNamespace": "sample string 19",
        //  "allUsers": "sample string 20",
        //  "includeCertSoapHeader": "sample string 21",
        //  "includeDocumentFields": "sample string 22"
    }

    // Overrides:

    public function execCurl($request_name, $params = [], &$errors = [], &$response_headers = [], &$response_code = -1)
    {
        if (in_array($request_name, self::$user_requests) && BimpObject::objectLoaded($this->userAccount)) {
            global $user;

            if (!BimpObject::objectLoaded($user)) {
                $errors[] = 'Aucun utilisateur ERP connecté';
                return array();
            }

            if (!$this->userAccount->isUserIn($user->id)) {
                $remote_id_user = $this->setUserIdAccount((int) $user->id, $errors);

                if (!count($errors) && !$remote_id_user) {
                    $errors[] = "Aucun compte DocuSign pour l\'utilisateur \"" . $user->getName() . "\" ou celui-ci n'est pas renseigné sur l\'interface DocuSign";
                }

                if (count($errors)) {
                    return array();
                }
            }
        }

        return parent::execCurl($request_name, $params, $errors, $response_headers, $response_code);
    }

    public function processRequestResponse($request_name, $response_code, $response_body, $response_headers = array(), &$infos = '', &$errors = array())
    {
        $return = $response_body;
        switch ($response_code) {
//            case '400':
//                $errors[] = 'Requête incorrecte';
//                break;

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

        if (isset($return['errorCode']) || isset($return['message'])) {
            $msg = '';
            if (isset($return['errorCode'])) {
                $msg .= $return['errorCode'];
            }
            if (isset($return['message'])) {
                $msg .= ($msg ? ' : ' : '') . $return['message'];
            }

            if ($msg) {
                $errors[] = $msg;
            }
        }

        return $return;
    }

    public function testRequest(&$errors = array(), &$warnings = array())
    {
//        return $this->setUserIdAccount(242, $errors, $warnings);
//        $id_account = $this->userAccount->getData('login');
//        
//        
//        $params = array();
//        $params['id_account'] = $id_account;
////        $params['id_envelope'] = '829172a0-2169-4716-8b72-89f7ed6b7cec';
////        
////        $this->getEnvelope($params);
//        
////        $this->getTemplates($params);
//        
////        $this->setUserIdAccount(1224, $errors);
//
////        $this->reqCreateEnvelope($params, $errors);
//        
        $this->createHook($params, $errors);
    }

    public function connect(&$errors = array(), &$warnings = array())
    {
        $result = '';
        $code = $this->userAccount->getToken('code');
        $refresh_token = $this->userAccount->getToken('refresh');

        if (!(string) $code) {
            // Code absent, on redirige l'utilisateur pour qu'il puisse se connecter
            $client_id = BimpTools::getArrayValueFromPath($this->params, $this->options['mode'] . '_oauth_client_id', '');

            if (BimpCore::isModeDev()) {
                $url_redirect = 'https://erp2.bimp.fr/bimpinv01072020/bimpapi/retour/DocusignAuthentificationSuccess.php?mode_dev=1';
            } else {
                $url_redirect = 'https://' . $_SERVER['HTTP_HOST'] . DOL_URL_ROOT . '/bimpapi/retour/DocusignAuthentificationSuccess.php';
            }

            $_SESSION['id_user_docusign'] = $this->userAccount->id;
            $url = $this->getBaseUrl('auth') . "/oauth/auth?response_type=code&scope=signature&client_id=" . $client_id . "&redirect_uri=" . urlencode($url_redirect);
            $errors[] = $this->userAccount->getData('name') . " n'est pas connecté à DocuSign <a target='_blank' href='" . $url . "'>cliquez ici</a>";
        } else {
            // Authentification via refresh token
            if ((string) $refresh_token) {
                $result = $this->execCurl('authenticate', array(
                    'fields' => array(
                        'grant_type'    => 'refresh_token',
                        'refresh_token' => $refresh_token
                    )), $errors);
            }

            // Si échec, test avec code: 
            $access_token = BimpTools::getArrayValueFromPath($result, 'access_token', '');
            if (!$access_token) {
                $result = $this->execCurl('authenticate', array(
                    'fields' => array(
                        'grant_type' => 'authorization_code',
                        'code'       => $code
                    )), $errors);

                if (isset($result['refresh_token']) && $result['refresh_token']) {
                    $refresh_token = $result['refresh_token'];
                    $expires_in = (int) BimpTools::getArrayValueFromPath($result, 'expires_in', 3600);
                    $dt_now = new DateTime();
                    $dt_now->add(new DateInterval('PT' . $expires_in . 'S'));
                    $this->saveToken('refresh', $refresh_token, $dt_now->format('Y-m-d H:i:s'));

                    // Nouvelle tentative via refresh token :
                    $result = $this->execCurl('authenticate', array(
                        'fields' => array(
                            'grant_type'    => 'refresh_token',
                            'refresh_token' => $refresh_token
                        )), $errors);
                }
            }

            if ((isset($result['access_token']) && $result['access_token'])) {
                $expires_in = (int) BimpTools::getArrayValueFromPath($result, 'expires_in', 3600);
                $dt_now = new DateTime();
                $dt_now->add(new DateInterval('PT' . $expires_in . 'S'));
                $this->saveToken('access', $result['access_token'], $dt_now->format('Y-m-d H:i:s'));
            } else {
                $error = 'Echec de la connexion';
                if($result['error_description'] == 'expired_client_token'){
                    // Code absent, on redirige l'utilisateur pour qu'il puisse se connecter
                    $client_id = BimpTools::getArrayValueFromPath($this->params, $this->options['mode'] . '_oauth_client_id', '');

                    if (BimpCore::isModeDev()) {
                        $url_redirect = 'https://erp2.bimp.fr/bimpinv01072020/bimpapi/retour/DocusignAuthentificationSuccess.php?mode_dev=1';
                    } else {
                        $url_redirect = 'https://' . $_SERVER['HTTP_HOST'] . DOL_URL_ROOT . '/bimpapi/retour/DocusignAuthentificationSuccess.php';
                    }

                    $_SESSION['id_user_docusign'] = $this->userAccount->id;
                    $url = $this->getBaseUrl('auth') . "/oauth/auth?response_type=code&scope=signature&client_id=" . $client_id . "&redirect_uri=" . urlencode($url_redirect);
                    $errors[] = $this->userAccount->getData('name') . " n'est pas connecté à DocuSign <a target='_blank' href='" . $url . "'>cliquez ici</a>";
                }
                else{
                    if (is_string($result) && $result) {
                        $error .= ' - ' . $result;
                    } elseif (isset($result['error']) && $result['error']) {
                        $error .= ' - ' . $result['error'];
                    } elseif (isset($result['error_description']) && $result['error_description']) {
                        $error .= ' - ' . $result['error_description'];
                    } else {
                        $error .= ' pour une raison inconnue (Aucune réponse)';
                    }
                    $errors[] = $error;
                }
            }
        }

        return (!count($errors));
    }

    public function getDefaultRequestsHeaders($request_name, &$errors = array())
    {
        if ($this->isUserAccountOk($errors)) {
            $client_id = $this->getParam($this->getOption('mode', 'test') . '_oauth_client_id', '');
            $client_secret = $this->getParam($this->getOption('mode', 'test') . '_oauth_client_secret', '');
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

    public static function getDefaultApiTitle()
    {
        return self::$title;
    }

    // Install:

    public function install($title = '', &$warnings = array())
    {
        $errors = array();

        // Pas de valeurs en dur dans le code !! 

        $api = BimpObject::createBimpObject('bimpapi', 'API_Api', array(
                    'name'  => self::$name,
                    'title' => ($title ? $title : $this->getDefaultApiTitle())
                        ), true, $errors, $warnings);

        if (BimpObject::objectLoaded($api)) {
            $param = BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
                        'id_api' => $api->id,
                        'name'   => 'test_oauth_client_secret',
                        'title'  => 'Secret client OAuth en mode test',
                        'value'  => ''
                            ), true, $warnings, $warnings);

            $param = BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
                        'id_api' => $api->id,
                        'name'   => 'test_oauth_client_id',
                        'title'  => 'ID Client OAuth en mode test',
                        'value'  => ''
                            ), true, $warnings, $warnings);

            $param = BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
                        'id_api' => $api->id,
                        'name'   => 'test_id_compte_api',
                        'title'  => 'Identifiant de compte API mode test',
                        'value'  => ''
                            ), true, $warnings, $warnings);

            $param = BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
                        'id_api' => $api->id,
                        'name'   => 'prod_oauth_client_secret',
                        'title'  => 'Secret client OAuth en mode prod',
                        'value'  => ''
                            ), true, $warnings, $warnings);

            $param = BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
                        'id_api' => $api->id,
                        'name'   => 'prod_oauth_client_id',
                        'title'  => 'ID Client OAuth en mode prod',
                        'value'  => ''
                            ), true, $warnings, $warnings);

            $param = BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
                        'id_api' => $api->id,
                        'name'   => 'prod_id_compte_api',
                        'title'  => 'Identifiant de compte API mode prod',
                        'value'  => ''
                            ), true, $warnings, $warnings);
        }

        return $errors;
    }
}
