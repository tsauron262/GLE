<?php 
BimpCore::displayHeaderFiles();
define('BIMP_NO_HEADER', 1);


if($_REQUEST['id'] && !$_REQUEST['contrat']) {
    $controller = BimpController::getInstance('bimpsupport', 'ticket');
}
elseif($_REQUEST['contrat']){
    $controller = BimpController::getInstance('bimpinterfaceclient', 'contrat_ticket');
}
else {
    $controller = BimpController::getInstance('bimpinterfaceclient', 'ticket');
}




$controller->displayHeaderFiles();

$controller->display();
?>