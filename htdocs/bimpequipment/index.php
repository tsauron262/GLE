<?php

require_once '../bimpcore/main.php';

ini_set('display_errors', 1);

define('BIMP_NEW', 1);

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
$controller = BimpController::getInstance('bimpequipment');
$controller->display();