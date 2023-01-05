<?php

if (!defined('BIMP_API_LIB')) {
    define('BIMP_API_LIB', 1);
    $dir = __DIR__ . '/classes/';

    include_once $dir . 'BimpAPI.php';
    include_once $dir . 'BimpApiRequest.php';
}