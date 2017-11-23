<?php
//echo '<input type="hidden" id="start_time" value="'.date('H:i:s').'"/>';
require_once '../main.inc.php';

ini_set('display_errors', 1);

//echo '<input type="hidden" id="begin_time" value="'.date('H:i:s').'"/>';

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
$controller = BimpController::getInstance('bimphotline');
$controller->display();

//echo '<input type="hidden" id="end_time" value="'.date('H:i:s').'"/>';