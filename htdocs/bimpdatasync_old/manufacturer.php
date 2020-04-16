<?php

require_once '../main.inc.php';
require_once __DIR__ . '/BDS_Lib.php';

ini_set('display_errors', 1);

$jsFiles = array(
    '/bimpdatasync/views/js/functions.js',
    '/bimpdatasync/views/js/ajax.js'
);

llxHeader('', '', '', false, false, false, $jsFiles);

echo '<link type="text/css" rel="stylesheet" href="./views/css/font-awesome.css"/>';
echo '<link type="text/css" rel="stylesheet" href="./views/css/styles.css"/>';

print load_fiche_titre('Fabricants', '', 'title_generic.png');

echo Manufacturer::renderList();
