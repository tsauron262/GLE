<?php

define('NOLOGIN', 1);
require_once '../bimpcore/main.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpwebservice/BWS_Lib.php';

header("Content-Type: application/json");

//$response = array(
//    'server' => $_SERVER,
//    'post'   => $_POST,
//    'get'    => $_GET
//);
//die(json_encode($response, JSON_UNESCAPED_UNICODE));

$errors = array();
$response = array();

$request_name = (isset($_GET['req']) ? $_GET['req'] : '');

if (!$request_name) {
    $errors[] = array(
        'code'    => 'REQUEST_MISSING',
        'message' => 'Nom de la requête absent'
    );
} else {
    $login = (isset($_SERVER['HTTP_BWS_LOGIN']) ? $_SERVER['HTTP_BWS_LOGIN'] : '');
    $pword = (isset($_SERVER['HTTP_BWS_LOGIN']) ? $_SERVER['HTTP_BWS_PW'] : '');
    
    if (!$login) {
        $errors[] = array(
            'code'    => 'LOGIN_MISSING',
            'message' => 'Identifiant du compte utilisateur absent'
        );
    }

    if (!$pword) {
        $errors[] = array(
            'code'    => 'PWORD_MISSING',
            'message' => 'Mot de passe absent'
        );
    }

    if (!count($errors)) {
        $bws = new BWSApi($request_name, $_POST);
        if ($bws->init(base64_decode($login), base64_decode($pword))) {
            $response = $bws->exec();
        }
        $errors = $bws->getErrors();
    }
}

if (count($errors)) {
    $response = array('errors' => $errors);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);

