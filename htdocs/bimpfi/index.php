<?php

require("../main.inc.php");

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

$module = 'bimpfi';
$controllerName = 'index';

if (BimpTools::isSubmit('id') && GETPOST('id') > 0)
    $controllerName = 'fiche';


$controller = BimpController::getInstance($module, $controllerName);
$controller->display();

