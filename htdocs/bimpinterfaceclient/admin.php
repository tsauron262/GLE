<?php

require_once '../bimpcore/main.php';

require_once DOL_DOCUMENT_ROOT.'/bimpcore/Bimp_Lib.php';

BimpTools::setContext("private");

$langs->load('bimp@bimpinterfaceclient');

ini_set('display_errors', 0);
//$nameController = $_REQUEST['fc']? $_REQUEST['fc'] : 'user';
$_POST['tab'] = 'user_client';
$controller = BimpController::getInstance('bimpcore', 'clients');
$controller->display();
