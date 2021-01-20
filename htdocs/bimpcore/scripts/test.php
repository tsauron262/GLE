<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'TEST', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();