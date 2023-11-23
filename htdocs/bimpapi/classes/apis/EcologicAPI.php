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
        'printproducttypelist'   => array(
            'label' => 'Récupérer la liste des types de produit',
            'url'   => 'printproducttypelist'
        ),
        'printproducttypewithlabellist'   => array(
            'label' => 'Récupérer la liste des types de produit',
            'url'   => 'printproducttypewithlabellist'
        ),
        'printbrandlist'   => array(
            'label' => 'Récupérer la liste des mrques',
            'url'   => 'printbrandlist'
        ),
        'createsupportrequest'   => array(
            'label' => 'Envoyer demande',
            'url'   => 'createsupportrequest'
        ),
        'createclaim'   => array(
            'label' => 'Envoyer claim',
            'url'   => 'createclaim'
        ),
        'updateclaim'   => array(
            'label' => 'Modifier claim',
            'url'   => 'updateclaim'
        ),
        'AttachFile'   => array(
            'label' => 'Ajouter document',
            'url'   => 'AttachFile'
        ),
        'updatesupportrequest'   => array(
            'label' => 'Modifier demande',
            'url'   => 'updatesupportrequest'
        ),
        
        
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
        $data = $this->executereqWithCache('printproducttypewithlabellist', array(
            'url_params' => array()
                ), $errors, $warnings);
        
        if(isset($data['ResponseData']))
            $warnings[] = count($data['ResponseData']).' résultats';
        

        return $data;
    }
    
    public function executereqWithCache($name, $params = array(), &$errors = array(), &$warnings = array()){
        $key = 'apiReqEcologic'.$name.base64_encode(json_encode($params));
        if(BimpCache::cacheServerExists($key)){
            $cache = BimpCache::getCacheServeur($key);
        }
        else{
            $cache = $this->execCurl($name, $params, $errors);
            BimpCache::setCacheServeur($key, $cache);
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
    
    
    public function traiteReq(&$errors, &$warnings, $data, $ecologicData, $siteId, $ref, $tabFile, $dateClose, $facRef, $sav){
        
        $params = array();
        $params['fields'] = $data;
        
        
        if(!isset($ecologicData['RequestId'])){//on cré la demande
            $params['url_params'] = array('callDate'=> date("Y-m-d\TH:i:s"), 'repairSiteId'=> $siteId, 'quoteNumber'=> $ref);
            $return = $this->execCurl('createsupportrequest', $params, $errors);
            
            if(isset($return['ResponseData']) && isset($return['ResponseData']['RequestId']) && isset($return/*['ResponseData']*/['IsValid']) && $return/*['ResponseData']*/['IsValid']){
                $warnings = BimpTools::merge_array($warnings, $errors);
                $errors = array();
                $ecologicData['RequestId'] = $return['ResponseData']['RequestId'];
                if($return['ResponseData']['IsValid']){
                    $ecologicData['RequestOk'] = true;
                }
            }
        }
        elseif(isset($ecologicData['RequestId'])  && !isset($ecologicData['ClaimId']) && !isset($ecologicData['RequestOk'])){//on update la demande
            $params['url_params'] = array('claimId'/*attention erreur API, ca devrait être RequestId*/ => $ecologicData['RequestId'],'callDate'=> date("Y-m-d\TH:i:s"), 'repairSiteId'=> $siteId, 'quoteNumber'=> $ref);
            $return = $this->execCurl('updatesupportrequest', $params, $errors);
            
            if(isset($return['ResponseData']) && isset($return['ResponseData']['RequestId']) && $return['ResponseData']['IsValid']){
                $ecologicData['RequestOk'] = true;
            }
            
            
        }
        
        if(isset($ecologicData['RequestId']) && isset($ecologicData['RequestOk']) && $ecologicData['RequestOk'] && !isset($ecologicData['ClaimId'])){//on créer le claim
            $params['url_params'] = array('RequestId' => $ecologicData['RequestId'], 'RepairEndDate' => $dateClose, 'ConsumerInvoiceNumber'=>$facRef, 'repairSiteId'=> $siteId, 'quoteNumber'=> $ref);
            $this->options['log_errors'] = false;
            $return = $this->execCurl('createclaim', $params, $errors);
            $this->options['log_errors'] = true;
            if(isset($return['ResponseData']) && isset($return['ResponseData']['ClaimId'])){
                $warnings = BimpTools::merge_array($warnings, $errors);
                $errors = array();
                $ecologicData['ClaimId'] = $return['ResponseData']['ClaimId'];
            }
        }
        
        //enregistrement avant les fichiers au cas ou....
        $sav->updateField('ecologic_data', $ecologicData);
        
        if(isset($ecologicData['RequestId']) && isset($ecologicData['ClaimId'])){
            $tabExt = array('jpeg', 'jpg', 'png');
            $filesOk = true;
            foreach($tabFile as $i => $fileT){
                if(!is_file($fileT[0] . $fileT[1].'.'.$fileT[2])){
                    foreach($tabExt as $ext){
                        if(is_file($fileT[0] . $fileT[1].'.'.$ext)){
                            $tabFile[$i][2] = $ext;
                            continue(2);
                        }
                    }
                    $errors[] = 'Fichier : '.$fileT[0] . $fileT[1].'.'.$fileT[2].' introuvable';
                    BimpCore::addlog ('Fichier : '.$fileT[0] . $fileT[1].'.'.$fileT[2].' introuvable');
                    $filesOk = false;
                }
            }
            
            if($filesOk){
                foreach($tabFile as $fileT){
                    if(!isset($ecologicData['files']) || !in_array($fileT[1], $ecologicData['files'])){
                        $paramsFile = array();
                        $paramsFile['fields']['FileContent'] = base64_encode(file_get_contents($fileT[0] . $fileT[1].'.'.$fileT[2]));
                        $paramsFile['url_params'] = array('ClaimId' => $ecologicData['ClaimId'], 'FileName' => $fileT[1], 'FileExtension' => $fileT[2], 'DocumentType' => $fileT[3]);
                        $return = $this->execCurl('AttachFile', $paramsFile, $errors);
                        if(stripos($return, 'Code 200') !== false){
//                        if(isset($return['ResponseData']) && $return['ResponseData']['IsValid']){
                            $ecologicData['files'][] = $fileT[1];
                            //enregistrement pendant les fichiers, au cas ou...
                            $sav->updateField('ecologic_data', $ecologicData);
                            $warnings = BimpTools::merge_array($warnings, $errors);
                            $errors = array();
                        }
                        else{
                            $filesOk = false;
                        }
                    }
                }
            }
        }
        
        
        
        if(isset($ecologicData['RequestId']) && isset($ecologicData['ClaimId']) && $filesOk){
            $warnings = array();//Tout semble ok, on vire les ancinne erreur de fichier qui sont résolu entre temps
            $params['url_params'] = array('ClaimId' => $ecologicData['ClaimId'], 'RepairEndDate' => $dateClose, 'ConsumerInvoiceNumber'=>$facRef, 'repairSiteId'=> $siteId, 'quoteNumber'=> $ref, 'Submit' => 'true');
            $return = $this->execCurl('updateclaim', $params, $errors);
            
            if(isset($return['ResponseStatus']) && $return['ResponseStatus'] == "S" && isset($return['ResponseData']) && $return['ResponseData']['IsValid'])
                $sav->updateField('status_ecologic', 99);
        }
        else{
            if(!isset($ecologicData['ClaimId']))
                $errors[] = 'Demande non créer';
            elseif(!$filesOk)
                $errors[] = 'Les fichiers ne sont pas ou partielement envoyées';
            else
                $errors[] = 'Erreur inconnue';
        }
        
        
        
        $sav->updateField('ecologic_data', $ecologicData);
    }

    public function processRequestResponse($request_name, $response_code, $response_body, $response_headers = array(), &$infos = '', &$errors = array())
    {

//        if($request_name == 'authenticate')
        $return = $response_body;
//        else
//        $this->getErrors($response_body, $errors);
        
        if($response_code == 206){
            foreach($return['ResponseData']['ValidationErrors'] as $err){
                $errors[] = implode(' - ', $err);
            }
        }
        
        if($response_code == 404){
            if(isset($return['Message']))
                $errors[] = $return['Message'];
        }
        if($response_code == 400){
                $errors[] = 'Bad request';
        }
        
        if($response_code == 500){
            if(isset($return['ResponseMessage']))
                $errors[] = $return['ResponseMessage'];
            if(isset($return['ResponseErrorMessage']))
                $errors[] = $return['ResponseErrorMessage'];
        }

//        switch ($response_code) {
//            case '0':
//                $errors[] = 'Atradius API: Vérifiez votre connexion internet';
//                break;
//
//            case '400':
//                $errors[] = 'Atradius API: Requête incorrecte';
//                break;
//
//            case '401':
//                $errors[] = 'Atradius API: Non authentifié';
//                $return = 'unauthenticate';
//                break;
//
//            case '403':
//                $errors[] = 'Atradius API: Accès refusé';
//                break;
//
//            case '404':
//                $errors[] = 'Atradius API: API non trouvée';
//                break;
//
//            case '405':
//                $errors[] = 'Atradius API: Format de la requête non supporté';
//                break;
//
//            case '406':
//                $errors[] = 'Atradius API: Non accepté';
//                break;
//
//            case '408':
//                $errors[] = 'Atradius API: Temps écoulé';
//                break;
//
//            case '422':
//                $errors[] = 'Atradius API: Requête non traitable';
//                break;
//
//            case '500':
//                $errors[] = 'Atradius API: Erreur interne serveur';
//                break;
//
//            case '503':
//                $errors[] = 'Atradius API: Service surchargé, merci de réessayer plus tard';
//                break;
//        }
//
//        if (isset($response_body['codeRetour']) && (int) $response_body['codeRetour'] !== 0 && isset($response_body['libelle'])) {
//            $errors[] = $response_body['libelle'];
//        }
//        
//        if (isset($response_body['faultstring']) && (string) $response_body['faultstring']) {
//            $errors[] = $response_body['faultstring'];
//        }

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
