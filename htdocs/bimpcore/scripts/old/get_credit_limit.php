<?php


require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';

top_htmlhead('', 'GET CREDIT LIMIT', 0, 0, array(), array());

echo '<body>';

echo 'DEBUT <br/><br/>';


global $db;
$bdb = new BimpDb($db);
$clients = array();

$list_ok = '';
$list_autre = '';
$before = "2022-05-04 00:00:00.000";

$sql =  ' SELECT id_object, value FROM ' . MAIN_DB_PREFIX . 'bimpcore_history';
$sql .= ' WHERE field="outstanding_limit_atradius"';
$sql .= ' AND   date <"' . $before . '"';
$sql .= ' ORDER BY id ASC';

echo $sql . '<br/><br/>';

$rows = $bdb->executeS($sql, 'array');

foreach ($rows as $r)
    $clients[$r['id_object']] = $r['value'];

echo count($clients) . ' clients on eu une limite de crédit <br/><br/>';

foreach($clients as $id_soc => $val) {
    
    if(0 < $val){
        $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', (int) $id_soc);
        
        if((int) $client->getData('outstanding_limit_atradius') == (int) $val)
            $list_ok .= $client->getData('code_client') . ',' . $client->getData('nom') .  '<br/>';
        else
            $list_autre .= $client->getData('code_client') . ',' . $client->getData('nom') .  '<br/>';
        
    }
}

echo $list_ok;

echo "<br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/>SUITE<br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/>";
echo $list_autre;

echo '<br/>FIN';

echo '</body></html>';
