<?php

require("../main.inc.php");

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
//error_reporting(E_ERROR);
//ini_set('display_errors', 1);

if(BimpTools::isSubmit('di') || $_REQUEST['fc'] == "demandinterv"){
    if (BimpTools::isSubmit('id') && GETPOST('id') > 0) {
        $controller = BimpController::getInstance('bimpfichinter', 'demandinter');
        $controller->display();
    }
    else{
        $controller = BimpController::getInstance('bimpfichinter', 'demandinter_list');
        $controller->display();
    }
}
else{
    if (BimpTools::isSubmit('id') && GETPOST('id') > 0) {
        $controller = BimpController::getInstance('bimpfichinter', 'fichinter');
        $controller->display();
    }
    else{
        $controller = BimpController::getInstance('bimpfichinter', 'fichinter_list');
        $controller->display();
    }
}

