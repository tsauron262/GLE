<?php
require_once '../bimpcore/main.php';

require_once DOL_DOCUMENT_ROOT.'/bimpcore/Bimp_Lib.php';

set_time_limit(5);

$controller = BimpController::getInstance('bimpfinancement');
$controller->display();

// TEST