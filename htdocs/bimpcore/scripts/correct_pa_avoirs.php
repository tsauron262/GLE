<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';

top_htmlhead('', 'MAJ PA AVOIRS', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db;
$bdb = new BimpDb($db);

set_time_limit(1200);

$sql = 'SELECT l.rowid, l.buy_price_ht, l.subprice FROM llx_facturedet l';
$sql .= ' LEFT JOIN llx_facture f ON f.rowid = l.fk_facture ';
$sql .= ' WHERE f.type = 2 AND ((l.subprice < 0 AND l.buy_price_ht > 0) OR (l.subprice > 0 AND l.buy_price_ht < 0))';

$rows = $bdb->executeS($sql, 'array');

echo '<pre>';
print_r($rows);
exit;

foreach ($rows as $r) {
    if ($bdb->update('facturedet', array(
                'buy_price_ht' => (float) ($r['buy_price_ht'] * -1)
                    ), 'rowid = ' . (int) $r['rowid']) <= 0) {
        echo $bdb->db->lasterror() . '<br/>';
    }
}

echo '</body></html>';

//llxFooter();
