<?php

if (!defined('BDS_LIB')) {
    define('BDS_LIB', 1);
    $dir = __DIR__ . '/classes/';

    include_once $dir . 'BimpDb.php';
    include_once $dir . 'BDS_Tools.php';
    include_once $dir . 'BDS_Report.php';
    include_once $dir . 'BDS_Process.php';
    include_once $dir . 'BDS_ImportProcess.php';
    include_once $dir . 'BDS_ExportProcess.php';
    include_once $dir . 'BDS_SyncProcess.php';
}