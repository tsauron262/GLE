<?php

BimpCore::displayHeaderFiles();
define('BIMP_NO_HEADER', 1);
if(!$_REQUEST['id']){
    $controller = BimpController::getInstance('bimpinterfaceclient', 'user');
} else {
    $controller = BimpController::getInstance('bimpinterfaceclient', 'pageUser');
}

$controller->displayHeaderFiles();
$controller->display();

?>