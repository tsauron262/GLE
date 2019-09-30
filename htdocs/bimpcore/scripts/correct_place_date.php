<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

top_htmlhead('', 'CORRECT PLACE DATE', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

echo 'DEBUT <br/><br/>';

global $db;
$bdb = new BimpDb($db);

$rows = $bdb->getRows('be_equipment_place', '1', null, 'array', array('id', 'date'));

$txt = '';
foreach ($rows as $r) {
    $txt .= (int) $r['id'] . ';' . $r['date'] . "\n";
}

error_reporting(E_ALL);
file_put_contents(DOL_DATA_ROOT.'/bimpcore/correction_places_dates.csv', $txt);

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();

