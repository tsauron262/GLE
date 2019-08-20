<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';

top_htmlhead('', 'DEL EQUIPMENTS', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db;
$bdb = new BimpDb($db);

set_time_limit(1200);

$line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFournLine', 25026);

if (BimpObject::objectLoaded($line)) {
    $receptions = $line->getData('receptions');

    echo '<pre>';
    print_r($receptions);
    echo '</pre>';
} else {
    echo 'PAS DE LINE';
}
echo 'FIN';

echo '</body></html>';

//llxFooter();
