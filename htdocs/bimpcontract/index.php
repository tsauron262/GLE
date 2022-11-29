<?php

require("../main.inc.php");

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

if (BimpTools::isSubmit('id') && GETPOST('id') > 0) {
    $controller = BimpController::getInstance('bimpcontract', 'contrat');
    $controller->display();
}
else{
    $controller = BimpController::getInstance('bimpcontract', 'index');
    $controller->display();
}

