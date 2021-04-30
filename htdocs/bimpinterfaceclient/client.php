<?php

$_REQUEST['bimp_context'] = 'public';

require_once '../bimpcore/main.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

BimpCore::setContext("public");

$controllerName = BimpTools::getValue('fc', 'InterfaceClient');
$controller = BimpController::getInstance('bimpinterfaceclient', $controllerName);
$controller->display();

?>
