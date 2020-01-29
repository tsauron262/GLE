<?php

define('NOLOGIN', '1');

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';

set_time_limit(7200);

//llxHeader();

echo '<!DOCTYPE html>';
echo '<html lang="fr">';

echo '<head>';
//echo '<link rel="stylesheet" type="text/css" href="' . DOL_URL_ROOT . '/bimpcore/views/css/ticket.css' . '"/>';
echo '<script src="/test2/includes/jquery/js/jquery.min.js?version=6.0.4" type="text/javascript"></script>';
echo '</head>';

echo '<body>';

global $db;
$bdb = new BimpDb($db);

$sql = 'SELECT b.fk_propal as id_obj, b.rang as position, a.id FROM '.MAIN_DB_PREFIX.'bs_sav_propal_line a LEFT JOIN '.MAIN_DB_PREFIX.'propaldet b ON a.id_line = b.rowid';

$rows = $bdb->executeS($sql, 'array');

foreach ($rows as $r) {
    if ($bdb->update('bs_sav_propal_line', array(
        'id_obj' => (int) $r['id_obj'],
        'position' => (int) $r['position']
    ), '`id` = '.(int) $r['id']) < 0) {
        echo $bdb->db->lasterror() . '<br/><br/>';
    }
}

echo '</body></html>';

//llxFooter();
