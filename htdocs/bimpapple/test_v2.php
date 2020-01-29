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

//$_SESSION['gsx_acti_token'] = '1a8f3b0a-6a45-4730-b38b-bc2d9adfad2a';

GSX_v2::$debug_mode = true;

$gsx = new GSX_v2();

$result = $gsx->exec('componentIssue', array(
    'componentCode' => 'SAFETY',
//    'device' => array(
//        'id' => 'C02PD3TVFVH6'
//    )
        ));

$gsx->displayErrors();

echo '<pre>';
print_r($result);
echo '</pre>';

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();