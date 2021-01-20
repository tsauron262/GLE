<?php

require("../main.inc.php");

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

$controllerName = 'index';

if(BimpTools::isSubmit('fc') && GETPOST('fc') == 'fi') {
    if (BimpTools::isSubmit('id') && GETPOST('id') > 0)
        $controllerName = 'fi';
}



$controller = BimpController::getInstance("bimptechnique", $controllerName);
$controller->display();

