<?php

if ((!isset($_REQUEST['fc']) || !$_REQUEST['fc'])) {
// Requêtes Ajax seulement:
    if ((!isset($_REQUEST['ajax']) || !(int) $_REQUEST['ajax'])) {
        die('ACCES NON AUTORISE');
    }
    
    $controller = 'api';
} else {
    $controller = $_REQUEST['fc'];
}

require_once '../bimpcore/main.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

$controller = BimpController::getInstance('bimpapi', $controller);
$controller->display();
