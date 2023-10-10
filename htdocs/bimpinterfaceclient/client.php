<?php

// Todo: test cookie uniquement si on est dans le contexte d'une i-frame (Ajouter un param URL ?) 

$fc = (isset($_GET['fc']) ? $_GET['fc'] : '');
$_REQUEST['bimp_context'] = 'public';

if ($fc !== 'doc') { // Nécessaire pour l'affichage des docs PDF. 
    header('x-frame-options: ALLOWALL', false);
    define('ALLOW_ALL_IFRAME', true);

    $url = "https://";
    $url .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $url = str_replace('nav_not_compatible', 'compatible', $url);
}

if (isset($_REQUEST['nav_not_compatible'])) {
    echo '<h1>Votre navigateur n\'est pas compatible.</h1><h2> <a href="' . $url . '" target="popup">Merci de cliquer ici</a></h2>';
    die;
}

//if(isset($_SERVER['HTTP_REFERER'])){
//    $result = parse_url($_SERVER['HTTP_REFERER']);
//    if(isset($result['host']) && stripos($result['host'],'ldlc.com') !== false){
//            echo '<h1>Votre navigateur n\'est pas compatible.</h1><h2> <a href="'.$url.'" target="popup">Merci de cliquer ici</a></h2>';
//            die;
//        if(stripos($_SERVER['HTTP_USER_AGENT'], 'Chrome') < 1 && stripos($_SERVER['HTTP_USER_AGENT'], 'Firefox') < 1){
//            if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'))   
//                 $url = "https://";   
//            else  
//                $url = "http://";     
//            $url.= $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];   
//
//        }
//    }
//}

require_once '../bimpcore/main.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

//if (!isset($_COOKIE[$sessionname])) {
//    setcookie($sessionname, session_id(), array('SameSite' => 'None', 'Secure' => true));
//}

BimpCore::setContext("public");

if ($fc !== 'doc') {
    if (!isset($_REQUEST['ajax'])) {
        echo "<script>function testCookie(){"
        . "setTimeout(function() {"
        . "if(document.cookie.match('publicerp') || window.self === window.top){ "
        . "}else{ "
        . "window.open('" . $url . "', '_blank'); "
        . "if(window.location.href.indexOf('?') > 0 || window.location.href.indexOf('/b/') > 0 || window.location.href.indexOf('/a/') > 0) "
        . "window.location.href = window.location.href + '&nav_not_compatible=true';"
        . "else "
        . "window.location.href = window.location.href + '?nav_not_compatible=true';"
        . "}"
        . "}, 500)}; "
        . "testCookie();"
        . "</script>";
    }
}

$controllerName = BimpTools::getValue('fc', 'InterfaceClient');

switch ($controllerName) {
    case 'contrat_ticket':
        $controllerName = 'InterfaceClient';
        $_GET['tab'] = 'contrats';
        if ((int) BimpTools::getValue('id', 0)) {
            $_GET['content'] = 'card';
            $_GET['id_contrat'] = BimpTools::getValue('id');
        }
        break;

    case 'ticket':
    case 'tickets':
        $_GET['tab'] = 'tickets';
        if ($controllerName == 'ticket' && (int) BimpTools::getValue('id', 0)) {
            $_GET['content'] = 'card';
            $_GET['id_ticket'] = BimpTools::getValue('id');
        }
        $controllerName = 'InterfaceClient';
        break;

    case 'user':
    case 'pageUser':
        $controllerName = 'InterfaceClient';
        $_GET['tab'] = 'infos';
        break;
}

$controller = BimpController::getInstance('bimpinterfaceclient', $controllerName);
$controller->display();
?>
