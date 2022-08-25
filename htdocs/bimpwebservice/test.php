<?php

require_once("../main.inc.php");
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

set_time_limit(0);
ignore_user_abort(0);

top_htmlhead('', 'TEST WS', 0, 0, array(), array());

echo '<body>';

$errors[] = array();

$url = 'http://10.192.20.122/flodev/bimpwebservice/request.php?test=t1';

echo 'URL: ' . $url . '<br/><br/>';

$ch = curl_init($url);

if (!$ch) {
    $errors[] = 'URL invalide';
} else {
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'Content-Type: multipart/form-data'
    ));
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLINFO_HEADER_OUT, false);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, array(
        'test1' => 'TEST 1',
        'test2' => 'TEST 2'
    ));

    $resp = curl_exec($ch);

    echo 'Réponse: <br/><br/>';
    echo '<pre>';
    print_r(json_decode($resp, 1));
    echo '</pre>';
}

echo '</body></html>';

