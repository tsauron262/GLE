<?php

// Requêtes Ajax seulement:
if ((!isset($_REQUEST['ajax']) || !(int) $_REQUEST['ajax'])) {
    die('ACCES NON AUTORISE');
}

require_once '../bimpcore/main.php';

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

$controller = BimpController::getInstance('bimpapple', 'gsx');
$controller->display();
