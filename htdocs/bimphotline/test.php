<?php

require_once '../main.inc.php';

ini_set('display_errors', 1);

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

$test = BimpObject::getInstance('bimphotline', 'test');

$instance = $test->getConf('fields/field1/object', array(), true, 'object');

echo '<pre>';
print_r($instance);
exit;
