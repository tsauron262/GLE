<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapi/classes/BimpAPI.php';

class PisteAPI extends BimpAPI
{

    public static $name = 'piste';
    public static $include_debug_json = false;
    public static $urls_bases = array(
        'default' => array(
            'prod' => 'https://api.piste.gouv.fr',
            'test' => 'https://sandbox-api.piste.gouv.fr'
        ),
        'auth'    => array(
            'prod' => 'https://oauth.piste.gouv.fr/api/oauth/token',
            'test' => 'https://sandbox-oauth.piste.gouv.fr/api/oauth/token'
        )
    );
    public static $requests = array(
        'authenticate'       => array(
            'label'         => 'Authentification',
            'url_base_type' => 'auth',
            'post_mode'     => 'string'
        ),
        'rechercheStructure' => array(
            'label' => 'Recherche stucture',
            'url'   => '/cpro/structures/v1/rechercher'
        ),
        'rechercheService'   => array(
            'label' => 'Recherche service',
            'url'   => '/cpro/structures/v1/rechercher/services'
        ),
        'soumettreFacture' => array()
    );
    public static $tokens_types = array(
        'access' => 'Token d\'accès'
    );

    // Requêtes: 

    public function rechercheClientStructures($siret, $params = array(), &$errors = array(), &$warnings = array())
    {
//        $params = BimpTools::overrideArray(array(
//                    'fields' => array(
//                        'structure' => array(
//                            'identifiantStructure'     => $siret,
//                            'typeIdentifiantStructure' => 'SIRET',
//                            'statutStructure'          => 'ACTIF'
//                        )
//                    )
//                        ), $params, false, true);
//
//        $response = $this->execCurl('rechercheStructure', $params, $errors);
//
//        if (empty($errors) && (!isset($response['codeRetour']) || $response['codeRetour'] !== 200 || !isset($response['listeStructures']) || empty($response['listeStructures']))) {
//            $errors[] = 'Erreur inconnue';
//        }
//
//        return $response;

        return array(
            'listeStructures' => array(
                array(
                    'idStructureCPP'       => '001',
                    'designationStructure' => 'Struct 1'
                ),
                array(
                    'idStructureCPP'       => '002',
                    'designationStructure' => 'Struct 2'
                )
            )
        );
    }

    public function rechercheClientServices($id_structure, $params = array(), &$errors = array(), &$warnings = array())
    {
//        $params = BimpTools::overrideArray(array(
//                    'fields' => array(
//                        'idStructure' => $id_structure
//                    )
//                        ), $params, false, true);
//
//        $response = $this->execCurl('rechercheService', $params, $errors);
//
//        if (empty($errors) && (!isset($response['codeRetour']) || $response['codeRetour'] !== 200 || !isset($response['listeServices']) || empty($response['listeServices']))) {
//            $errors[] = 'Erreur inconnue';
//        }
//
//        return $response;

        if ($id_structure === '001') {
            return array(
                'listeServices' => array(
                    array(
                        'idService'      => 11,
                        'libelleService' => 'Service 1-1',
                        'estActif'       => true,
                    ),
                    array(
                        'idService'      => 12,
                        'libelleService' => 'Service 1-2',
                        'estActif'       => false,
                    )
                )
            );
        } else {
            return array(
                'listeServices' => array(
                    array(
                        'idService'      => 21,
                        'libelleService' => 'Service 2-1',
                        'estActif'       => true,
                    ),
                    array(
                        'idService'      => 22,
                        'libelleService' => 'Service 2-2',
                        'estActif'       => false,
                    )
                )
            );
        }
    }

    public function soumettreFacture($id_facture, &$errors = array(), &$warnings = array())
    {
        $facture = null;
        $client = null;
        $siret = '';
        $structure_data = array();
        $service_data = array();

        // Fetchs instances et vérifs: 
        if (!$id_facture) {
            $errors[] = 'ID de la facture absent';
        } else {
            $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_facture);

            if (BimpObject::objectLoaded($facture)) {
                if (!in_array((int) $facture->getData('fk_statut'), array(Facture::STATUS_VALIDATED, Facture::STATUS_CLOSED))) {
                    $errors[] = 'Le statut actuel de la facture ne permet pas cette opération';
                } elseif (!$facture->field_exists('chorus_status')) {
                    $errors[] = 'Le champ "Statut chorus" n\'est pas paramétré pour les factures';
                } elseif ((int) $facture->getData('chorus_status') > 0) {
                    $errors[] = 'Cette facture a déjà été déposée sur chorus';
                } else {
                    $client = $facture->getChildObject('client');

                    if (!BimpObject::objectLoaded($client)) {
                        $errors[] = 'Client absent';
                    } elseif (!in_array($client->dol_object->typent_code, array('TE_ADMIN'))) {
                        $errors[] = 'Ce client n\'est pas une administration';
                    } else {
                        $siret = $client->getData('siret');

                        if (!$siret) {
                            $errors[] = 'N° SIRET du client absent';
                        }
                    }
                }
            }
        }

        if (!count($errors)) {
            $fields = array();
            $id_structure = 0;

            // Recherche ID structure du client:
            $request_errors = array();
            $response = $this->rechercheStructureBySiret($siret, $request_errors);

            if (empty($request_errors) && (!isset($response['codeRetour']) || $response['codeRetour'] !== 200 || !isset($response['listeStructures']) || empty($response['listeStructures']))) {
                $request_errors[] = 'Erreur inconnue';
            } elseif (count($response['listeStructures']) > 1) {
                $request_errors[] = 'Plusieurs structures trouvées pour le n° SIRET "' . $siret . '"';
            } else {
                $id_structure = (int) $response['listeStructures'][0]['idStructureCPP'];

                if (!$id_structure) {
                    $request_errors[] = 'Identifiant de la structure du client absent de la réponse';
                }
            }

            if (!empty($request_errors)) {
                $errors[] = BimpTools::getMsgFromArray($request_errors, 'Echec de la récupération de l\'identifiant de la structure du client depuis Chorus');
                return null;
            }

            $fields = array(
                'modeDepot'                  => 'SAISIE_API',
                'numeroFactureSaisi'         => 1,
                'dateFacture'                => $facture->getData('datef'),
                'destinataire'               => array(
                    'codeDestinataire'     => '',
                    'codeServiceExecutant' => ''
                ),
                'fournisseur'                => array(
                    'idFournisseur'                       => '',
                    'idServiceFournisseur'                => '',
                    'codeCoordonneesBancairesFournisseur' => ''
                ),
                'cadreDeFacturation'         => array(
                    'codeCadreFacturation'  => '',
                    'codeStructureValideur' => '',
                    'codeServiceValideur'   => ''
                ),
                'SoumettreFactureReferences' => array(
                    'deviseFacture'        => 'EURO',
                    'typeFacture'          => ($facture->getData('type') === Facture::TYPE_CREDIT_NOTE ? 'AVOIR' : 'FACTURE'),
                    'typeTva'              => 'TVA_SUR_DEBIT',
                    'motifExonerationTva'  => '',
                    'numeroMarche'         => '',
                    'numeroBonCommande'    => '',
                    'numeroFactureOrigine' => '',
                    'modePaiement'         => ''
                ),
                'montantTotal'               => array(
                    'montantHtTotal' => $facture->dol_object->total_ht,
                    'montantTVA'     => $facture->dol_object->total_tva
                )
            );
        }
    }

    // Overrides: 

    public function connect(&$errors = array(), &$warnings = array())
    {
        if ($this->options['mode'] === 'test') {
            $client_id = BimpTools::getArrayValueFromPath($this->params, 'test_oauth_client_id', '');
            $client_secret = BimpTools::getArrayValueFromPath($this->params, 'test_oauth_client_secret', '');
        } else {
            $client_id = BimpTools::getArrayValueFromPath($this->params, '', '');
            $client_secret = BimpTools::getArrayValueFromPath($this->params, '', '');
        }

        if (!$client_id) {
            $errors[] = 'ID Client non paramétré dans le mode actuel';
        }

        if (!$client_secret) {
            $errors[] = 'Secret Client non paramétré dans le mode actuel';
        }

        if (!count($errors)) {
            $result = $this->execCurl('authenticate', array(
                'fields' => array(
                    'grant_type'    => 'client_credentials',
                    'client_id'     => $client_id,
                    'client_secret' => $client_secret,
                    'scope'         => 'openid'
                )
                    ), $errors);

            if (is_string($result)) {
                $errors[] = $result;
            } elseif (isset($result['access_token']) && (string) $result['access_token']) {
                $expires_in = (int) BimpTools::getArrayValueFromPath($result, 'expires_in', 3600);

                $dt_now = new DateTime();
                $dt_now->add(new DateInterval('PT' . $expires_in . 'S'));

                $this->saveToken('access', $result['access_token'], $dt_now->format('Y-m-d H:i:s'));
            } else {
                $errors[] = 'Echec de la connexion pour une raison inconnue';
            }
        }

        return (!count($errors));
    }

    public function getDefaultRequestsHeaders($request_name, &$errors = array())
    {
        if ($this->isUserAccountOk($errors)) {
            $login = $this->userAccount->getData('login');
            $pw = $this->userAccount->getData('pword');

            if ($login && $pw) {
                if ($request_name !== 'authenticate') {
                    return array(
                        'Authorization' => 'Bearer ' . $this->userAccount->getToken('access'),
                        'cpro-account'  => base64_encode($login . ':' . $pw)
                    );
                }
            } else {
                if (!$login) {
                    $errors[] = 'Identifiant absent pour le compte utilisateur "' . $this->userAccount->getData('name') . '"';
                }
                if (!$pw) {
                    $errors[] = 'Mot de passe absent pour le compte utilisateur "' . $this->userAccount->getData('name') . '"';
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

            case '415':
                $errors[] = 'Format de la requête non supoorté';
                break;

            case '500':
                $errors[] = 'Erreur interne serveur';
                break;
        }

        if (isset($response_body['codeRetour']) && (int) $response_body['codeRetour'] !== 200 && isset($response_body['libelle'])) {
            $errors[] = $response_body['libelle'];
        }

        return $return;
    }

    public function testRequest(&$errors = array(), &$warnings = array())
    {
        return $this->execCurl('rechercheStructure', array(
                    'allow_reconnect' => 0,
                    'fields'          => array(
                        'structure' => array(
                            'identifiantStructure'     => '425551',
                            'typeIdentifiantStructure' => 'SIRET'
                        )
                    )
                        ), $errors);
    }

    // Install: 

    public function install(&$warnings = array())
    {
        $errors = array();

        $bdb = BimpCache::getBdb();

        if ((int) $bdb->getValue('api_bimp_api', 'id', 'name = \'piste\'')) {
            $errors[] = 'Cette API a déjà été installée';
        } else {
            $api = BimpObject::createBimpObject('bimpapi', 'API_Api', array(
                        'name'  => 'piste',
                        'title' => 'Piste'
                            ), true, $errors, $warnings);

            if (BimpObject::objectLoaded($api)) {
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
            }
        }

        return $errors;
    }
}
