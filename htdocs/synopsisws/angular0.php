<?php

if (!defined('NOLOGIN'))
    define('NOLOGIN', 1);
if (!defined('NOCSRFCHECK'))
    define('NOCSRFCHECK', 1);
require("../main.inc.php");


include_once DOL_DOCUMENT_ROOT . '/core/lib/security2.lib.php';
include_once DOL_DOCUMENT_ROOT . '/synopsisws/class/synopsisWs.class.php';

global $db, $user;
if (isset($_SESSION["dol_login"])) {
    $user->fetch(null, $_SESSION["dol_login"]);
    $user->getrights();
}

function gleLogin($login, $pass) {
    global $user;
    $login = str_replace("'","",$login);;
    $pass = str_replace("'","",$pass);
    if ($id = checkLoginPassEntity($login, $pass, 0, array("dolibarr", "http"))) {
        $user->fetch("", $id);
        $user->getrights();

        $_SESSION["dol_login"] = $user->login;
        $_SESSION["dol_entity"] = $conf->entity;
        return json_encode(array('login' => $user->login));
    } else {
        return json_encode(array('ereurCode' => "errorLogin"));
    }
}

if (0 && (!isset($user) || $user->id < 1)) {
    if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
        gleLogin($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
    }
}


header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
header('Access-Control-Allow-Credentials: true');
header('Content-Type:application/json; charset=utf-8');

if ($user->id < 1 && $_REQUEST["object"] != "login") {
    //header('WWW-Authenticate: Basic realm="My Realm"');
    //header('HTTP/1.0 401 Unauthorized');
    echo json_encode(array("ereurCode" => "noLogin"));
    die;
}

//Fin partie init login ....


$params = (isset($_REQUEST['params'])) ? str_replace("!eg!", "=", str_replace("!et!", " AND ", $_REQUEST['params'])) : null;

$idObject = (isset($_REQUEST['idObject'])) ? $_REQUEST['idObject'] : -100;

if (isset($_REQUEST['object']) && $_REQUEST['object'] != "") {
    $typeObjet = $_REQUEST['object'];

    $angular = new synopsisWs($db);
    $angular->init($typeObjet, $params);


    //die($req);


    if (isset($_REQUEST['action']) && $_REQUEST['action'] == "data") {
        if ($idObject >= 0) {
            $angular->getOne($idObject);
        } else {
            $angular->getList();
        }
    }
    if (isset($_REQUEST['action']) && $_REQUEST['action'] == "shema") {
        $angular->getShema();
    }
    if (isset($_REQUEST['action']) && $_REQUEST['action'] == "setData") {
        $angular->setData($idObject, $_REQUEST['data']);
    }
}

function errorSortie($str) {
    echo "Erreur : " . $str;
    die;
    dol_syslog($str, 3);
}
