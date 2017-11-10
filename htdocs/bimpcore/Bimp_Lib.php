<?php

if (!defined('BIMP_LIB')) {
    define('BIMP_LIB', 1);
    $dir = __DIR__ . '/classes/';

    require_once __DIR__ . '/libs/spyc/Spyc.php';

    require_once $dir . 'BimpDb.php';
    require_once $dir . 'BimpTools.php';
    require_once $dir . 'BimpConfig.php';
    require_once $dir . 'BimpInput.php';
    require_once $dir . 'BimpRender.php';
    require_once $dir . 'BimpList.php';
    require_once $dir . 'BimpForm.php';
    require_once $dir . 'BimpView.php';
    require_once $dir . 'BimpObject.php';
    require_once $dir . 'BimpController.php';
}