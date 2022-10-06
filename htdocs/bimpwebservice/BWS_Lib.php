<?php

if (!defined('BWS_LIB_INIT')) {
    define('BWS_LIB_INIT', 1);

    $dir = __DIR__ . '/classes/';
    include_once $dir . 'BWSApi.php';

    if (defined('BIMP_EXTENDS_VERSION')) {
        if (file_exists(DOL_DOCUMENT_ROOT . '/bimpwebservice/extends/version/' . BIMP_EXTENDS_VERSION . '/classes/BWSApi.php')) {
            require_once DOL_DOCUMENT_ROOT . '/bimpwebservice/extends/version/' . BIMP_EXTENDS_VERSION . '/classes/BWSApi.php';
        }
    }

    if (defined('BIMP_EXTENDS_ENTITY')) {
        if (file_exists(DOL_DOCUMENT_ROOT . '/bimpwebservice/extends/entities/' . BIMP_EXTENDS_ENTITY . '/classes/BWSApi.php')) {
            require_once DOL_DOCUMENT_ROOT . '/bimpwebservice/extends/entities/' . BIMP_EXTENDS_ENTITY . '/classes/BWSApi.php';
        }
    }
}