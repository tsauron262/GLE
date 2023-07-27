<?php

$context = (isset($_REQUEST['bimp_context']) && $_REQUEST['bimp_context'] ? $_REQUEST['bimp_context'] : '');

ini_set('display_errors', 0);

if ($context == 'public') {
    define("NOLOGIN",1);
    define("NOSESSION", 1);
    
    define('XFRAMEOPTIONS_ALLOWALL', true);
    
    $sessionname = 'publicerp';
        session_set_cookie_params(array('SameSite' => 'None', 'Secure' => true, 'path' => '/'));
	session_name($sessionname);
        
        $test = session_id();
        
//        echo '<pre>'.$test;
//        print_r($_COOKIE);
        
//        echo '<br/>'.session_id().' finfinfin';
//        session_id('testssessionid');
//        session_id($test);
	session_start();
        
        
//        if(isset($_COOKIE[$sessionname]) && $_COOKIE[$sessionname] != $test && $test != ''){
//            $_COOKIE[$sessionname] = $test;
//            echo 'oui';
//        }
        
//        echo '<pre>';
//        echo session_id().'<br/>fin<br/>';
//        print_r($_COOKIE);
//        print_r($_SESSION);
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
                'nologged'   => 1
            )));
        }
    }
} else {
    require_once __DIR__ . "/../main.inc.php";
}