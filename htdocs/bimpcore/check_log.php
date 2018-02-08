<?php

if ((isset($_GET['ajax']) && $_GET['ajax']) ||
        isset($_POST['ajax']) && $_POST['ajax']) {
    $sessionname = null;

    foreach ($_COOKIE as $name => $value) {
        if (preg_match('/^(DOLSESSID_.*)$/', $name, $matches)) {
            $sessionname = $matches[1];
            break;
        }
    }

    $request_id = 0;

    if (isset($_GET['request_id'])) {
        $request_id = $_GET['request_id'];
    } elseif (isset($_POST['request_id'])) {
        $request_id = $_POST['request_id'];
    }

    $islogged = false;

    if (!is_null($sessionname)) {
        session_name($sessionname);
        session_start();
        if (isset($_SESSION['dol_login'])) {
            $islogged = true;
        }
    }

    if (!$islogged) {
        die(json_encode(array(
            'request_id' => $request_id,
            'nologged'   => 1
        )));
    }
}