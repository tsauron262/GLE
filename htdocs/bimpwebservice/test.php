<?php

require_once("../main.inc.php");
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

set_time_limit(0);
ignore_user_abort(0);

top_htmlhead('', 'TEST WS', 0, 0, array(), array());

echo '<body>';

// Cryptage: 
//$simple_string = "Welcome to GeeksforGeeks\n";
//echo "Original String: " . $simple_string . '<br/>';
//$ciphering = "AES-128-CTR";
//$encryption_iv = 'dfgkjn54fg@fhsb532sfdfd';
//$encryption_key = "GeeksforGeeks";
//$encryption = openssl_encrypt($simple_string, $ciphering,
//                              $encryption_key, 0, $encryption_iv);
//echo "Encrypted String: " . $encryption . "<br/>";
//$decryption = openssl_decrypt($encryption, $ciphering,
//                              $encryption_key, 0, $encryption_iv);
//echo "Decrypted String: " . $decryption;
// TEST REQUËTE WEBSERVICE: 
//$errors[] = array();
//$url = 'http://10.192.20.122/flodev/bimpwebservice/request.php?req=deleteObject';
//
//echo 'URL: ' . $url . '<br/><br/>';
//
//$ch = curl_init($url);
//
//if (!$ch) {
//    $errors[] = 'URL invalide';
//} else {
//    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
//        'Accept: application/json',
//        'Content-Type: multipart/form-data',
//        'BWS-LOGIN: ' . base64_encode('user1@test.fr'),
//        'BWS-PW: ' . base64_encode('HhQncpqF24WS')
//    ));
//
//    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//    curl_setopt($ch, CURLINFO_HEADER_OUT, false);
//    curl_setopt($ch, CURLOPT_HEADER, false);
//    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
//    curl_setopt($ch, CURLOPT_POSTFIELDS, array(
//        'module'      => 'bimpequipment',
//        'object_name' => 'Equipment',
//        'id'          => 507790
//    ));
//
//    $resp = curl_exec($ch);
//
//    echo 'Réponse: <br/><br/>';
//    echo '<pre>';
//    print_r(json_decode($resp, 1));
//    echo '</pre>';
//}
//
//
//
//
// Test Requête via ErpAPI : 

$id_api = 4;
$api_obj = BimpCache::getBimpObjectInstance('bimpapi', 'API_Api', $id_api);

if (BimpObject::objectLoaded($api_obj)) {
    $api = BimpAPI::getApiInstance('erp', $api_obj->getData('api_idx'));
    $errors = array();
    $reponse = $api->getObjectData('bimpequipment', 'Equipment', 507775, null, $errors);

    if (count($errors)) {
        echo 'ERREURS: <pre>';
        print_r($errors);
        echo '</pre>';
    }

    echo 'RESPONSE: <pre>';
    print_r($response);
    echo '</pre>';
} else {
    echo 'API #' . $id_api . ' KO';
}

echo '</body></html>';

