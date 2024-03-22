<?php

require_once("../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'TESTS', 0, 0, array(), array());

echo '<body style="padding: 30px">';

BimpCore::displayHeaderFiles();

global $db, $user;
$bdb = new BimpDb($db);

if (!BimpObject::objectLoaded($user)) {
    echo BimpRender::renderAlerts('Aucun utilisateur connecté');
    exit;
}

if (!$user->admin) {
    echo BimpRender::renderAlerts('Seuls les admin peuvent exécuter ce script');
    exit;
}

require_once DOL_DOCUMENT_ROOT.'/bimpcore/pdf/classes/CepaPDF_new.php';
$client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', 454033);

$pdf = new CepaPDFNew($db);
$pdf->client = $client;
$pdf->render(DOL_DOCUMENT_ROOT . '/bimpcore/cepa_test2.pdf', 'F');

echo '<script>window.open(\'' . DOL_URL_ROOT . '/bimpcore/cepa_test2.pdf\')</script>';



//CREATE TABLE `llx_bimp_test` (
//  `id` int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
//  `base1` varchar(255) NOT NULL DEFAULT '',
//  `base2` varchar(255) NOT NULL DEFAULT ''
//);
//
//CREATE TABLE `llx_bimp_test_ext1` (
//  `id` int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
//  `id_obj` int(11) NOT NULL DEFAULT 0,
//  `entity` int(11) NOT NULL DEFAULT 1,
//  `field_filter` varchar(255) NOT NULL DEFAULT '',
//  `ext1_1` varchar(255) NOT NULL DEFAULT '',
//  `ext1_2` varchar(255) NOT NULL DEFAULT ''
//);
//
//CREATE TABLE `llx_bimp_test_ext2` (
//  `id` int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
//  `id_obj` int(11) NOT NULL DEFAULT 0,
//  `ext2_1` varchar(255) NOT NULL DEFAULT '',
//  `ext2_2` varchar(255) NOT NULL DEFAULT ''
//);
//
//
//
//
//
// TEST CREATE: 
//$obj = BimpObject::getInstance('bimpcore', 'Test');
//
//$obj->validateArray(array(
//    'base1'  => 'aaa',
//    'base2'  => 'bbb',
//    'ext1_1' => 'ccc',
//    'ext1_2' => 'ddd',
//    'ext2_1' => 'eee',
//    'ext2_2' => 'fff'
//));
//
//$err = $obj->create($w, true);
//
//echo '*** TEST CREATE ***<br/>';
//echo 'WARN<pre>';
//print_r($w);
//echo '</pre>';
//echo 'Err<pre>';
//print_r($err);
//echo '</pre>';
//echo 'DATA : ';
//$obj->printData();
//
//
//
//
//
// TEST UPDATE : 
//global $conf;
//$conf->entity = 12;
//$obj = BimpCache::getBimpObjectInstance('bimpcore', 'Test', 2);
//$obj = BimpObject::getInstance('bimpcore', 'Test');
//$obj->validateArray(array(
////    'base1'  => 'AAA',
////    'ext1_1' => 'BBB',
//    'ext2_2' => 'fkghfkgjhdfkhgdflkjhfdkljg',
//));
//$err = $obj->update($w, true);
//$obj->updateField('ext1_2', 'ZZZ');
//$obj->updateField('ext2_2', '123456789');
//echo '*** TEST UPDATE ***<br/>';
//echo 'WARN<pre>';
//print_r($w);
//echo '</pre>';
//echo 'Err<pre>';
//print_r($err);
//echo '</pre>';
//echo 'DATA : ';
//$obj->printData();
// TEST SQL KEYS : 
//$filters = array();
//$joins = array();
//$err = array();
//
//echo 'Key base 1 : ' . $obj->getFieldSqlKey('base1', 'a', null, $filters, $joins, $err) . '<br/>';
//echo 'Key ext1 1 : ' . $obj->getFieldSqlKey('ext1_1', 'a', null, $filters, $joins, $err) . '<br/>';
//echo 'Key ext2 1 : ' . $obj->getFieldSqlKey('ext2_1', 'a', null, $filters, $joins, $err) . '<br/>';
//echo 'Key ext2 2 : ' . $obj->getFieldSqlKey('ext2_2', 'a', null, $filters, $joins, $err) . '<br/>';
//
//echo 'FILTERS<pre>';
//print_r($filters);
//echo '</pre>';
//echo 'JOINS<pre>';
//print_r($joins);
//echo '</pre>';
//echo 'ERR<pre>';
//print_r($err);
//echo '</pre>';
// TEST FILTERS: 
//global $conf;
//$conf->entity = 12;
//$obj = BimpObject::getInstance('bimpcore', 'Test');
//$rows = $obj->getList(array(
//    'ext2_2' => 'fff'
//));
//
//echo 'ROWS<pre>';
//print_r($rows);
//echo '</pre>';
// TEST DELETE : 
//$obj = BimpCache::getBimpObjectInstance('bimpcore', 'Test', 4);
//$err = $obj->delete($w, true);
//echo '*** TEST DELETE ***<br/>';
//echo 'WARN<pre>';
//print_r($w);
//echo '</pre>';
//echo 'Err<pre>';
//print_r($err);
//echo '</pre>';


echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
