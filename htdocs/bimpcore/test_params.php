<?php

require_once("../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'TESTS PARAMS', 0, 0, array(), array());

echo '<body>';

echo '--- TEST GET CONF --- <br/>';
echo 'TEST VAL BASE:  ' . BimpCore::getConf('test_val_base', null, 'bimpcommercial');
echo '<br/>';

echo 'TEST VAL DEF YML: ' . BimpCore::getConf('test_def_val', null, 'bimpcommercial');
echo '<br/>';

echo 'TEST VAL DEF GETCONF: '. BimpCore::getConf('test_def_val', 'VAL GET CONF OK', 'bimpcommercial');
echo '<br/>';

$val = BimpCore::getConf('test_no_val', null, 'bimpcommercial');
echo 'TEST NO VAL: ' . (is_null($val) ? 'OK' : 'FAIL');
echo '<br/>';

BimpCore::getConf('ghjgfh', null, 'bimpcommercial');

echo 'TEST WRONG MODULE: ' . BimpCore::getConf('test_val_base', 'OK', 'bimpcore');
echo '<br/><br/><br/>';

$p = BimpObject::getInstance('bimpcore', 'Bimp_Product');
echo '--- TEST CONF YML --- <br/>';
echo 'TEST VAL BASE:  ' . $p->getConf('test_val_base');
echo '<br/>';

echo 'TEST VAL DEF YML: ' . $p->getConf('test_def_yml');
echo '<br/>';

echo 'TEST VAL DEF GETCONF: ' . $p->getConf('test_def_conf');
echo '<br/>';

echo 'TEST NO VAL: '. $p->getConf('test_no_val', 'NO VAL OK');
echo '<br/>';

echo 'TEST NO PARAM: ' . $p->getConf('test_no_val', 'NO PARAM OK');
echo '<br/>';

echo 'TEST WRONG MODULE: ' . $p->getConf('test_module', 'OK');
echo '<br/>';

echo '</body></html>';

//llxFooter();
