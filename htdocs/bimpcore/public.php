<?php

$_REQUEST['bimp_context'] = 'public';
require_once '../bimpcore/main.php';

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

BimpCore::setContext('public');

if (!BimpCore::getConf('module_version_bimpinterfaceclient', '')) {
    accessforbidden();
}

$module = BimpTools::getBacktraceArray('module');
$controller_name = BimpTools::getValue('fc', 'index', true, 'alphanohtml');

if (!$module || !$controller_name) {
    accessforbidden();
}

$controller = BimpController::getInstance($module, $controller_name);
$controller->display();

?>
