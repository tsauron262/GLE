<?php


require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/sizing.php';

top_htmlhead('', 'GET CREDIT CHECK', 0, 0, array(), array());

echo '<body>';

echo 'DEBUT <br/><br/>';

global $db;
$bdb = new BimpDb($db);

$from = "2022-04-01 00:00:00.000";
$to   = "2023-04-01 00:00:00.000";

$sql = 'SELECT s.rowid AS id_soc FROM ' . MAIN_DB_PREFIX . 'societe s';
$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bimpcore_history h on h.id_object = s.rowid';
$sql .= ' WHERE field="outstanding_limit_credit_check"';
$sql .= ' AND h.value = 7000';
$sql .= ' AND h.date > "' . $from . '" AND h.date <"' . $to . '"';


echo $sql . '<br/><br/>';

$rows = $bdb->executeS($sql, 'array');

echo count($rows) . ' clients on eu un crédit check entre ' . $from . ' et ' . $to . ' <br/><br/>';

foreach ($rows as $r) {
    
    $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', (int) $r['id_soc']);
    echo $client->getData('code_client') . ',' . $client->getData('nom') . '' . '<br/>';
    

//    break;
}

echo '<br/>FIN';

echo '</body></html>';
