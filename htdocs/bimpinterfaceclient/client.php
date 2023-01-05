<?php

header('x-frame-options: ALLOWALL',false);
define('ALLOW_ALL_IFRAME', true);
$_REQUEST['bimp_context'] = 'public';

//echo '<pre>';
//print_r($_SERVER);
//echo '</pre>';

$url = "https://";       
$url.= $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];  
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
//            echo '<h1>Votre navigateur n\'est pas compatible.</h1><h2> <a href="'.$url.'" target="popup">Merci de cliquer ici</a></h2>';
//            die;
//        }
//    }
//}

require_once '../bimpcore/main.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

if(!isset($_COOKIE[$sessionname])){
    setcookie($sessionname, session_id(), array('SameSite' => 'None', 'Secure'=>true));
}

BimpCore::setContext("public");



echo "<script>function testCookie(){"
. "setTimeout(function() {"
    . "if(document.cookie.match('DOLSESSID_')){ "
    . "}else{ "
            . "window.open('".$url."', '_blank'); "
            . "cells = document.getElementsByTagName('form');"
            . "for (var i = 0; i < cells.length; i++) {"
                . "cells[i].innerHTML = '<h1>Votre navigateur n\'est pas compatible.</h1><h2> <a href=\"".$url."\" target=\"popup\">Merci de cliquer ici</a></h2>';"
            . "}"
    . "}"
. "}, 500)}; "
. "document.onload=\"testCookie()\";testCookie();"
. "</script>";


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