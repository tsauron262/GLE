<?php
require_once '../main.inc.php';

ini_set('display_errors', 1);

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
$controller = BimpController::getInstance('bimpcore');
$controller->display();