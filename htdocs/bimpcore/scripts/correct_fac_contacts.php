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
$sql .= ' WHERE f.fk_facture_source > 0';
$sql .= ' AND f.datec > \'2019-06-30\'';
$sql .= ' AND (SELECT COUNT(ec.fk_socpeople) FROM ' . MAIN_DB_PREFIX . 'element_contact ec';
$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_type_contact tc ON tc.rowid = ec.fk_c_type_contact';
$sql .= ' WHERE tc.element = \'facture\'';
$sql .= ' AND tc.source = \'internal\'';
$sql .= ' AND tc.code = \'SALESREPFOLL\'';
$sql .= ' AND ec.element_id = f.rowid) = 0';

echo $sql . '<br/>';

$rows = $bdb->executeS($sql, 'array');

echo $bdb->db->lasterror();
echo 'N: ' . count($rows);
exit;

if (is_array($rows)) {
    foreach ($rows as $r) {
        $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $r['rowid']);

        if (BimpObject::objectLoaded($fac)) {
//            $errors = $fac->checkContacts();
//            
//            if (count($errors)) {
//                echo BimpRender::renderAlerts(BimpTools::getMsgFromArray($errors, 'Fac #' . $r['rowid']));
//            }
            $src = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $fac->getData('fk_facture_source'));
            if (BimpObject::objectLoaded($fac)) {
                echo 'COPIE CONTACT POUR FAC #' . $fac->id;
                $errors = array();
                $fac->copyContactsFromOrigin($src, $errors);

                if (count($errors)) {
                    echo BimpRender::renderAlerts($errors);
                } else {
                    echo 'OK';
                }
                echo '<br/>';
            }
        }
    }
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();