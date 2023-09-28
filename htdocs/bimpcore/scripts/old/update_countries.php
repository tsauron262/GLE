<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'UPDATE COUNTRIES', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db;
$bdb = new BimpDb($db);

ini_set('display_errors', 1);
error_reporting(E_ALL);
$rows = file(__DIR__ . '/docs/countries.csv', FILE_IGNORE_NEW_LINES);

$table = 'c_country';
foreach ($rows as $i => $r) {
    $data = explode(';', $r);

    if ($bdb->update($table, array(
                'code_iso' => $data[2]
                    ), '`code` = \'' . $data[1] . '\'') <= 0) {
        echo 'ECHEC ' . $data[1] . ' => ' . $data[2] . ' - ' . $bdb->db->lasterror() . '<br/>';
    } else {
        echo 'OK ' . $data[1] . ' => ' . $data[2] . '<br/>';
    }
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();