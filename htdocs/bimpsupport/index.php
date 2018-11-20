<?php

require_once '../bimpcore/main.php';

require_once DOL_DOCUMENT_ROOT.'/bimpcore/Bimp_Lib.php';

ini_set('display_errors', 0);
$controller = BimpController::getInstance('bimpsupport');
$controller->display();
