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

//$rows = $bdb->getRows('be_package_place', '1', null, 'array');
//echo $db->lasterror();
//
//foreach ($rows as $r) {
//    if (!(int) $r['id_package']) {
//        continue;
//    }
//
//    $where = '`type` = ' . $r['type'];
//    $where .= ' AND `id_entrepot` = ' . (int) $r['id_entrepot'];
//    $where .= ' AND `id_client` = ' . (int) $r['id_client'];
//    $where .= ' AND `id_user` = ' . (int) $r['id_user'];
//    $where .= ' AND `place_name` LIKE \'' . $r['place_name'] . '\'';
//    $where .= ' AND `id_equipment` IN ';
//    $where .= '(SELECT DISTINCT e.id FROM ' . MAIN_DB_PREFIX . 'be_equipment e WHERE e.id_package = ' . (int) $r['id_package'] . ')';
//
//    if ($bdb->update('be_equipment_place', array(
//                'date' => $r['date']
//                    ), $where) <= 0) {
//        echo $r['id'] . ': ' . $db->lasterror() . '<br/>';
//    }
//}

//$sql = 'SELECT DISTINCT `id_entrepot` FROM ' . MAIN_DB_PREFIX . 'be_equipment_place';
//$rows = $bdb->executeS($sql, 'array');
//
//$list = array();
//$ent = new Entrepot($db);
//
//foreach ($rows as $r) {
//    $ent->fetch((int) $r['id_entrepot']);
//
//    if (!BimpObject::objectLoaded($ent)) {
//        $list[] = (int) $r['id_entrepot'];
//    }
//}
//
//echo '<pre>';
//print_r($list);
//echo '</pre>';

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();

