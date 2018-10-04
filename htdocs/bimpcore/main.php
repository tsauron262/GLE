<?php

if ((isset($_GET['ajax']) && $_GET['ajax']) ||
        isset($_POST['ajax']) && $_POST['ajax']) {

    if (isset($_GET['request_id'])) {
        $request_id = $_GET['request_id'];
    } elseif (isset($_POST['request_id'])) {
        $request_id = $_POST['request_id'];
    } else {
        $request_id = '';
    }

    if (!defined('NOLOGIN'))
        define('NOLOGIN', 1);

    if (!defined('NOCSRFCHECK'))
        define('NOCSRFCHECK', 1);

    require_once __DIR__ . "/../main.inc.php";

    global $db, $user;

    if (isset($_SESSION["dol_login"])) {
        $user->fetch(null, $_SESSION["dol_login"]);
        $user->getrights();
    } else {
        die(json_encode(array(
            'request_id' => $request_id,
            'nologged'   => 1
        )));
    }
} else {
    require_once __DIR__ . "/../main.inc.php";
}