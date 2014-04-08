<?php

require_once('../main.inc.php');
llxHeader();


require_once NUSOAP_PATH.'/nusoap.php';


require_once ( 'test.class.php' );

$details = array (
    'apiMode'           => 'production',
    'regionCode'        => 'apac',
    'userId'            => 'Corinne@actitec.fr',
    'password'          => 'cocomart01',
    'serviceAccountNo'  => '0000100635',
    'languageCode'      => 'en',
    'userTimeZone'      => 'AEST' ,
    'returnFormat'      => 'php' ,
);

$gsx = new GSX ( $details );

echo "mi";
echo "<pre>"; print_r($gsx->lookup ( 'C02H21L8DHJQ' ));
echo "<pre>"; print_r($gsx->part ( array ( 'serialNumber' =>'C02H21L8DHJQ' , 'partDescription' => 'op' )));
//echo "<pre>"; print_r($gsx->part ( array ( 'partNumber' =>'Z661-6061')));

echo "fin";