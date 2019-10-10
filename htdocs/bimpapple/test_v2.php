<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'TESTS GSX V2', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db;
$bdb = new BimpDb($db);

require_once DOL_DOCUMENT_ROOT.'/bimpapple/classes/GSX_v2.php';

$gsx = new GSX_v2();


echo '<br/>FIN';

echo '</body></html>';

//llxFooter();