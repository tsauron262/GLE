<?php
if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] != "admin_signature" || $_SERVER['PHP_AUTH_PW'] != "xMj5tuRg") {
    header('WWW-Authenticate: Basic realm="My Realm"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'NON autorisé';
    exit;
}

define("NOLOGIN", 1);

require_once '../bimpcore/main.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

global $db, $user;
$user = new User($db);
$user->fetch(1);

$controller = BimpController::getInstance('bimpcore', 'signature_pad');
$controller->display();