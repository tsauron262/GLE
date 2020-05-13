<?php

if (!defined('BDS_LIB')) {
    define('BDS_LIB', 1);
    $dir = __DIR__ . '/classes/';

    include_once $dir . 'BDSRender.php';
    include_once $dir . 'BDSProcess.php';
    include_once $dir . 'BDSImportProcess.php';
    include_once $dir . 'BDSExportProcess.php';
}