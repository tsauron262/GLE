<?php

define('NOLOGIN', 1);
define('NOCSRFCHECK', 1);
require_once '../bimpcore/main.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpwebservice/BWS_Lib.php';

header("Content-Type: application/json");

//if (preg_match('/^\{.+\}$/', $_POST)) {
//    $_POST = json_decode($_POST, 1);
//}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_SERVER['CONTENT_TYPE'] == 'application/json') {
	$_POST = json_decode(file_get_contents('php://input'), true);
}

$debug = false;
if ((isset($_GET['debug']) && (int) $_GET['debug']) || $debug) {
    $response = array(
        'server' => $_SERVER,
        'post' => $_POST,
        'get'  => $_GET
    );
    die(json_encode($response, JSON_UNESCAPED_UNICODE));
}

$errors = array();
$response = array();

$request_name = (isset($_GET['req']) ? $_GET['req'] : '');
$response_code = 200;

if (!$request_name) {
    $errors[] = array(
        'code'    => 'REQUEST_MISSING',
        'message' => 'Nom de la requête absent'
    );
} else {
    $login = (isset($_SERVER['HTTP_BWS_LOGIN']) ? $_SERVER['HTTP_BWS_LOGIN'] : '');
    $token = (isset($_SERVER['HTTP_BWS_TOKEN']) ? $_SERVER['HTTP_BWS_TOKEN'] : '');

    if (!$login) {
        $errors[] = array(
            'code'    => 'LOGIN_MISSING',
            'message' => 'Identifiant du compte utilisateur absent'
        );
    }

    if ($request_name !== 'authenticate') {
        if (!$token) {
            $errors[] = array(
                'code'    => 'TOKEN_MISSING',
                'message' => 'Token absent'
            );
			$response_code = 401;
        }
    } else {
        $pw = isset($_POST['pword']) ? $_POST['pword'] : '';

        if (!$pw) {
            $errors[] = 'Mot de passe absent';
			$response_code = 401;
        }
    }

    if (!count($errors)) {
        $bws = BWSApi::getInstance($request_name, $_POST);
        if ($bws->init($login, $token)) {
            $response = $bws->exec();
        }
        $errors = $bws->getErrors();
		$response_code = $bws->response_code;
    }
}

if (count($errors)) {
    $response = array('errors' => $errors);
}

http_response_code($response_code);
echo json_encode($response, JSON_UNESCAPED_UNICODE);

