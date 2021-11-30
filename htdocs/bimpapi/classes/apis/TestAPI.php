<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapi/classes/BimpAPI.php';

class TestApi extends BimpAPI
{

    public static $name = 'test';
    public static $public_name = 'Test';
    public static $mode = 'test';
    
    public static $urls = array(
        'base'  => array(
            'prod' => '',
            'test' => ''
        ),
        'login' => array(
            'prod' => '',
            'test' => DOL_URL_ROOT . '/bimpcore/index.php?fc=admin'
        )
    );

    // Overrides statics: 

    public static function getCertifInfo($params)
    {
        
    }

    public function getAuthenticateParams()
    {
        
    }
}
