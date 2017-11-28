<?php

if (!defined('BDS_LIB')) {
    define('BDS_LIB', 1);
    $dir = __DIR__ . '/classes/';

    include_once $dir . 'BDSDb.php';
    include_once $dir . 'BDS_Tools.php';

    include_once $dir . 'Manufacturer.class.php';
    include_once $dir . 'BDSProcess.class.php';
    include_once $dir . 'BDSProcessParameter.class.php';
    include_once $dir . 'BDSProcessOption.class.php';
    include_once $dir . 'BDSProcessMatchingValues.class.php';
    include_once $dir . 'BDSProcessCustomMatchingValues.class.php';
    include_once $dir . 'BDSProcessTriggerAction.class.php';
    include_once $dir . 'BDSProcessOperation.class.php';
    include_once $dir . 'BDSProcessCronOption.class.php';
    include_once $dir . 'BDSProcessCron.class.php';

    include_once $dir . 'BDS_Report.php';
    include_once $dir . 'BDS_Process.php';
    include_once $dir . 'BDS_ImportProcess.php';
    include_once $dir . 'BDS_ExportProcess.php';
    include_once $dir . 'BDS_SyncProcess.php';
    include_once $dir . 'BDS_WSProcess.php';
}