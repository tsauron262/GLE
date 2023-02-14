<?php

$context = (isset($_REQUEST['bimp_context']) && $_REQUEST['bimp_context'] ? $_REQUEST['bimp_context'] : '');

if ($context == 'public') {
    define("NO_REDIRECT_LOGIN", 1);
}

if (isset($_REQUEST['ajax']) && $_REQUEST['ajax']) {
    $request_id = (isset($_REQUEST['request_id']) ? $_REQUEST['request_id'] : '');

    if (!defined('NOLOGIN'))
        define('NOLOGIN', 1);

    if (!defined('NOCSRFCHECK'))
        define('NOCSRFCHECK', 1);

    require_once __DIR__ . "/../main.inc.php";

    global $db, $user;

    if ($context != 'public') {
        if (isset($_SESSION["dol_login"]) && (string) $_SESSION['dol_login']) {
            $user->fetch(null, $_SESSION["dol_login"]);
            $user->getrights();
        } else {
            die(json_encode(array(
                'request_id' => $request_id,
                'nologged'   => 1,
                'test'       => print_r($_SESSION)
            )));
        }
    }
} else {
    require_once __DIR__ . "/../main.inc.php";
}