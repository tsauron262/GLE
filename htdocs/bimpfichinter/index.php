<?php

require("../main.inc.php");

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
//error_reporting(E_ERROR);
//ini_set('display_errors', 1);

$controllerName = "fichinter_list";
$objName = "Bimp_Fichinter";


if (BimpTools::isSubmit('id') && GETPOST('id') > 0) {
    if(BimpTools::isSubmit('di') || $_REQUEST['fc'] == "demandinter"){
        $controllerName = "demandinter";
        $objName = "Bimp_Demandinter";
    }
    else
        $controllerName = "fichinter";
}



$controller = BimpController::getInstance('bimpfichinter', $controllerName);
$controller->display();
