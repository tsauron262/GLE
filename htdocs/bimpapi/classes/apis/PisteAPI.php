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

    public function rechercheClientStructures($siret, $params = array(), &$errors = array(), &$warnings = array())
    {
        $siret = '12345678200051';
        $params = BimpTools::overrideArray(array(
                    'fields' => array(
                        'structure' => array(
                            'identifiantStructure'     => $siret,
                            'typeIdentifiantStructure' => 'SIRET',
                            'statutStructure'          => 'ACTIF'
                        )
                    )
                        ), $params, false, true);

        $response = $this->execCurl('rechercheStructure', $params, $errors);

        if (empty($errors) && (!isset($response['listeStructures']) || empty($response['listeStructures']))) {
            $errors[] = 'Aucune structure trouvée pour ce numéro SIRET';
        }

        return $response;
    }

    public function consulterStructure($id_structure, $params = array(), &$errors = array(), &$warnings = array())
    {
        $params = BimpTools::overrideArray(array(
                    'fields' => array(
                        'idStructureCPP' => (int) $id_structure
                    )
                        ), $params, false, true);

        $response = $this->execCurl('consulterStructure', $params, $errors);

        return $response;
    }

    public function rechercheClientServices($id_structure, $params = array(), &$errors = array(), &$warnings = array())
    {
        $params = BimpTools::overrideArray(array(
                    'fields' => array(
                        'idStructure' => (int) $id_structure
                    )
                        ), $params, false, true);

        $response = $this->execCurl('rechercheService', $params, $errors);

        if (empty($errors) && (!isset($response['listeServices']) || empty($response['listeServices']))) {
            $errors[] = 'Aucun service trouvé';
        }

        return $response;
    }

    public function deposerPdfFacture($id_facture, $params = array(), &$errors = array(), &$warnings = array())
    {
        if (!$id_facture) {
            $errors[] = 'ID de la facture absent';
        } else {
            $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_facture);

            if (!BimpObject::objectLoaded($facture)) {
                $errors[] = 'La facture #' . $id_facture . ' n\'existe plus';
            } else {
                $file_name = dol_sanitizeFileName($facture->getRef()) . '.pdf';
                $dir = $facture->getFilesDir();

                if (!file_exists($dir . '/' . $file_name)) {
                    $errors[] = 'Le fichier "' . $file_name . '" n\'existe pas';
                } else {
                    $params['fields'] = array(
                        'fichierFacture' => base64_encode(file_get_contents($dir . '/' . $file_name)),
                        'nomFichier'     => $file_name,
                        'formatDepot'    => 'PDF_NON_SIGNE'
                    );

                    return $this->execCurl('deposerPdfFacture', $params, $errors);
                }
            }
        }

        return null;
    }

    // Overrides: 

    public function connect(&$errors = array(), &$warnings = array())
    {
        if ($this->options['mode'] === 'test') {
            $client_id = BimpTools::getArrayValueFromPath($this->params, 'test_oauth_client_id', '');
            $client_secret = BimpTools::getArrayValueFromPath($this->params, 'test_oauth_client_secret', '');
        } else {
            $client_id = BimpTools::getArrayValueFromPath($this->params, 'prod_oauth_client_id', '');
            $client_secret = BimpTools::getArrayValueFromPath($this->params, 'prod_oauth_client_secret', '');
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

    public function getRequestFormValues($request_name, $params, &$errors = array())
    {
        $fields = array();

        switch ($request_name) {
            case 'soumettreFacture':
                $id_facture = BimpTools::getArrayValueFromPath($params, 'id_facture', 0);

                if (!$id_facture) {
                    $errors[] = 'ID de la facture à exporter absent';
                } else {
                    $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_facture);

                    if (!BimpObject::objectLoaded($facture)) {
                        $errors[] = 'La facture #' . $id_facture . ' n\'existe plus';
                    } elseif ($facture->isActionAllowed('confirmChorusExport', $errors)) {
                        $client = $facture->getChildObject('client');

                        if (!BimpObject::objectLoaded($client)) {
                            $errors[] = 'Client absent';
                        }

                        $id_fournisseur = 0;

                        if ($this->options['mode'] === 'test') {
                            $id_fournisseur = BimpTools::getArrayValueFromPath($this->params, 'test_id_fournisseur', '');
                        } else {
                            $id_fournisseur = BimpTools::getArrayValueFromPath($this->params, 'prod_id_fournisseur', '');
                        }

                        if (!$id_fournisseur) {
                            $errors[] = 'Identifiant fournisseur absent';
                        }

                        $chorus_data = $facture->getData('chorus_data');

                        $id_pdf = (int) BimpTools::getArrayValueFromPath($chorus_data, 'id_pdf', 0);
                        $num_facture = BimpTools::getArrayValueFromPath($chorus_data, 'num_facture', '');

                        if (!$id_pdf) {
                            $errors[] = 'ID Chorus du PDF absent';
                        }

                        if (!$num_facture) {
                            $errors[] = 'N° Chorus de la facture absent';
                        }

                        $mode_paiement = '';
                        switch ($facture->dol_object->mode_reglement_code) {
                            case 'CHQ':
                                $mode_paiement = 'CHEQUE';
                                break;

                            case 'VIR':
                                $mode_paiement = 'VIREMENT';
                                break;

                            case 'LIQ':
                                $mode_paiement = 'ESPECE';
                                break;

                            case 'PRE':
                            case 'PRELEV':
                                $mode_paiement = 'PRELEVEMENT';
                                break;

                            default:
                                $mode_paiement = 'AUTRE';
                                break;
                        }

                        if (!count($errors)) {
                            $code_service = BimpTools::getArrayValueFromPath($params, 'code_service', '');

                            if ($code_service == 'not_required') {
                                $code_service = '';
                            }

                            $fields = array(
                                'numeroFactureSaisi'    => $num_facture,
                                'dateFacture'           => $facture->getData('datef'),
                                'modeDepot'             => 'DEPOT_PDF_API',
                                'fournisseur'           => array(
                                    'idFournisseur' => $id_fournisseur
                                ),
                                'destinataire'          => array(
                                    'codeDestinataire'     => $client->getData('siret'),
                                    'codeServiceExecutant' => $code_service
                                ),
                                'references'            => array(
                                    'deviseFacture' => 'EUR',
                                    'typeFacture'   => ((int) $facture->getData('type') === Facture::TYPE_CREDIT_NOTE ? 'AVOIR' : 'FACTURE'),
                                    'modePaiement'  => $mode_paiement
                                ),
                                'montantTotal'          => array(
                                    'montantHtTotal'  => $facture->dol_object->total_ht,
                                    'montantTVA'      => $facture->dol_object->total_tva,
                                    'montantTtcTotal' => $facture->dol_object->total_ttc,
                                    'montantAPayer'   => $facture->getRemainToPay()
                                ),
                                'cadreDeFacturation'    => array(
                                    'codeCadreFacturation' => ((int) $facture->getData('paye') ? 'A1_FACTURE_FOURNISSEUR' : 'A2_FACTURE_FOURNISSEUR_DEJA_PAYEE')
                                ),
                                'pieceJointePrincipale' => array(
                                    array(
                                        'pieceJointePrincipaleDesignation' => dol_sanitizeFileName($facture->getRef()) . '.pdf',
                                        'pieceJointePrincipaleId'          => (int) $id_pdf
                                    )
                                )
                            );
                        }
                    }
                }
                break;
        }

        return $fields;
    }

    public function onRequestFormSuccess($request_name, $result, &$warnings = array())
    {
        switch ($request_name) {
            case 'soumettreFacture':
                $errors = array();
                $id_facture = (int) BimpTools::getValue('id_facture', 0);

                if (!$id_facture) {
                    $errors[] = 'ID de la facture absent';
                } else {
                    $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_facture);

                    if (!BimpObject::objectLoaded($facture)) {
                        $errors[] = 'La facture #' . $id_facture . ' n\'existe plus';
                    } else {
                        if (isset($result['empreinteCertificatDepot'])) {
                            $chorus_data = $facture->getData('chorus_data');
                            $chorus_data['certif'] = $result['empreinteCertificatDepot'];
                            $facture->set('chorus_data', $chorus_data);
                        }
                        $facture->set('chorus_status', 2);

                        $up_warnings = array();
                        $errors = $facture->update($up_warnings, true);

                        if (count($errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($errors, 'Echec de la mise à jour de la facture');
                        }

                        $facture->addLog('Export Chorus terminé');
                    }
                }
                break;
        }
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
