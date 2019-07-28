<?php

        set_time_limit(6000);
        
require_once '../bimpcore/main.php';
require_once DOL_DOCUMENT_ROOT.'/bimpcore/Bimp_Lib.php';

$controller = BimpController::getInstance('bimpcommercial');
$controller->display();
