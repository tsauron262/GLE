<?php

global $bimp_start_time; 
$bimp_start_time = microtime(1);

require_once '../bimpcore/main.php';

ini_set('display_errors', 1);

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
$controller = BimpController::getInstance('bimpcommercial');
$controller->display();