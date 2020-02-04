<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'CONRRECTION CONTACTS FACTURES', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db;
$bdb = new BimpDb($db);

$sql = 'SELECT f.rowid FROM ' . MAIN_DB_PREFIX . 'facture f';
$sql .= ' WHERE f.rowid = 478030';

$rows = $bdb->executeS($sql, 'array');

if (is_array($rows)) {
    foreach ($rows as $r) {
        $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $r['rowid']);

        if (BimpObject::objectLoaded($fac)) {
            $errors = $fac->checkContacts();
            
            if (count($errors)) {
                echo BimpRender::renderAlerts(BimpTools::getMsgFromArray($errors, 'Fac #' . $r['rowid']));
            }
        }
    }
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();