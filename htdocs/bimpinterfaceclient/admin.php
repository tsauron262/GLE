<?php

require_once '../bimpcore/main.php';

require_once DOL_DOCUMENT_ROOT.'/bimpcore/Bimp_Lib.php';

ini_set('display_errors', 0);
$langs->load('bimp@bimpinterfaceclient');
$controller = BimpController::getInstance('bimpinterfaceclient', 'adminInterface');
$controller->display();
