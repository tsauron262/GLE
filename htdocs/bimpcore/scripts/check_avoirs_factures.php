<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

top_htmlhead('', 'CHECK COMMANDES', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db;
$bdb = new BimpDb($db);

$sql = 'SELECT `rowid` FROM ' . MAIN_DB_PREFIX . 'facture a';
$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'facture f on a.fk_facture_source = f.rowid';
$sql .= ' WHERE a.fk_facture_source > 0';
$sql .= ' AND f.paye = 0 && f.fk_statut = 1';

$rows = $bdb->executeS($sql, 'array');

echo '<pre>';
print_r($rows);
exit;

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();

