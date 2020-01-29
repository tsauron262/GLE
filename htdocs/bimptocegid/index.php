<?php

require_once '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

//if (!$user->admin && $user->id != 460) {
//    accessforbidden();
//}

$nameController = $_REQUEST['fc'] ? $_REQUEST['fc'] : 'index';
$controller = BimpController::getInstance('bimptocegid', $nameController);

$controller->display();
