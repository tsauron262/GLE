<?php

$_REQUEST['bimp_context'] = 'public';

require_once '../bimpcore/main.php';
header('x-frame-options: ALLOWALL',true);
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

BimpCore::setContext("public");

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
