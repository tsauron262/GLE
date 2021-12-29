<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapi/classes/BimpAPI.php';

class GsxApi extends BimpAPI
{

    public static $name = 'gsx';
    public static $include_debug_json = true;
    public static $urls_bases = array(
        'default'     => array(
            'prod' => 'https://api-partner-connect.apple.com/',
            'test' => 'https://api-partner-connect-uat.apple.com/',
        ),
        'login'       => array(
            'test' => 'https://login-partner-connect-uat.apple.com',
            'prod' => 'https://login-partner-connect.apple.com',
        ),
        'file_upload' => array(
            'test' => 'https://partner-connect-uat.corp.apple.com/',
            'prod' => 'https://partner-connect.corp.apple.com/',
        )
    );
    public static $requests = array();

//    public static function install(&$warnings = array())
//    {
//        $errors = array();
//
//        $bdb = BimpCache::getBdb();
//
//        if ((int) $bdb->getValue('api_bimp_api', 'id', 'name = \'gsx\'')) {
//            $errors[] = 'Cette API a déjà été installée';
//        } else {
//            $api = BimpObject::createBimpObject('bimpapi', 'API_Api', array(
//                        'name'  => 'gsx',
//                        'title' => 'GSX'
//                            ), true, $errors, $warnings);
//
//            if (BimpObject::objectLoaded($api)) {
////                $param = BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
////                            'id_api' => $api->id,
////                            'name'   => 'test_oauth_client_id',
////                            'title'  => 'ID Client OAuth en mode test'
////                                ), true, $warnings, $warnings);
//            }
//        }
//
//        return $errors;
//    }
}
