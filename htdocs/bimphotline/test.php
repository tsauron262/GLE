<?php
require_once '../main.inc.php';

ini_set('display_errors', 1);
require_once __DIR__.'/classes/BimpDb.php';
require_once __DIR__.'/classes/BimpObject.php';
require_once __DIR__.'/classes/BDS_Tools.php';
require_once __DIR__.'/classes/TestObject.php';

$jsFiles = array(
    '/bimpdatasync/views/js/functions.js',
    '/bimpdatasync/views/js/ajax.js'
);


llxHeader('', '', '', false, false, false, $jsFiles);

echo '<link type="text/css" rel="stylesheet" href="./views/css/font-awesome.css"/>';
echo '<link type="text/css" rel="stylesheet" href="./views/css/styles.css"/>';

echo TestObject::renderFormAndList();