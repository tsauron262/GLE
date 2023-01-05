<?php

require("../main.inc.php");

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';



$controller = BimpController::getInstance('bimpfinanc');
$controller->display();
