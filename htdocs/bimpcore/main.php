<?php

$context = (isset($_REQUEST['bimp_context']) && $_REQUEST['bimp_context'] ? $_REQUEST['bimp_context'] : '');

ini_set('display_errors', 0);

if ($context == 'public') {
    define("NOLOGIN",1);
    define("NOSESSION", 1);
    
    define('XFRAMEOPTIONS_ALLOWALL', true);
    
    $sessionname = '__Host-publicerp';
        session_set_cookie_params(array('SameSite' => 'None', 'Secure' => true, 'path' => '/', 'httponly' => false/*pour test cookie dans iframe*/));
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
    $cspJs = "'self' 'unsafe-inline' 'unsafe-eval'";
    if(BimpCore::getConf('use_csp_nonce', false)){
        if(!defined('csp_nonce'))
            define('csp_nonce', randomPassword(10));
        $cspJs .= " 'strict-dynamic' 'nonce-".csp_nonce."'";
        
    }
    header("Content-Security-Policy: default-src 'self'; script-src ".$cspJs."; style-src 'self' 'unsafe-inline'");
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}


function randomPassword($length, $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789')
{
    for ($i = 0, $z = strlen($chars) - 1, $s = $chars[rand(0, $z)], $i = 1; $i != $length; $x = rand(0, $z), $s .= $chars[$x], $s = ($s[$i] == $s[$i - 1] ? substr($s, 0, -1) : $s), $i = strlen($s)) {

    }
    return $s;
}