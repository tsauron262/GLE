<?php

require_once __DIR__ . '/../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

ini_set('display_errors', 1);
set_time_limit(0);
ignore_user_abort(0);
top_htmlhead('', 'TESTS GSX V2', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db;
$bdb = new BimpDb($db);

require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_v2.php';

$errors = array();
$debug = '';

$result = GSX_Reservation::cancelReservation(897316, 1046075, 'B1210512436068', $errors, $debug);

echo $debug .'<br/><br/>';

echo 'ERREURS: <pre>';
print_r($errors);
echo '</pre>';

echo 'Result: <pre>';
print_r($result);
echo '</pre>';

echo '</div>';
echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
