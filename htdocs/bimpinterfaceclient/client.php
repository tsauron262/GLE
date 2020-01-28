<?php
define("NO_REDIRECT_LOGIN", 1);
require_once '../bimpcore/main.php';

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';


BimpTools::setContext("public");


// Désactivation de l'autantification DOLIBARR


// REQUIREMENTS
//require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

//$couverture = $userClient->my_soc_is_cover();
//$couverture = Array();


$nameController = $_REQUEST['fc'] ? $_REQUEST['fc'] : 'index';
$controller = BimpController::getInstance('bimpinterfaceclient', $nameController);
$controller->display();
    
?>
