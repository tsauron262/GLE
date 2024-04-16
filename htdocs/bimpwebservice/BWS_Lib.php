<?php

if (!defined('BWS_LIB_INIT')) {
    define('BWS_LIB_INIT', 1);

    $dir = __DIR__ . '/classes/';
    include_once $dir . 'BWSApi.php';

    if (BimpCore::getVersion()) {
        if (file_exists(DOL_DOCUMENT_ROOT . '/bimpwebservice/extends/version/' . BimpCore::getVersion() . '/classes/BWSApi.php')) {
            require_once DOL_DOCUMENT_ROOT . '/bimpwebservice/extends/version/' . BimpCore::getVersion() . '/classes/BWSApi.php';
        }
    }

    if (BimpCore::getExtendsEntity() != '') {
        if (file_exists(DOL_DOCUMENT_ROOT . '/bimpwebservice/extends/entities/' . BimpCore::getExtendsEntity() . '/classes/BWSApi.php')) {
            require_once DOL_DOCUMENT_ROOT . '/bimpwebservice/extends/entities/' . BimpCore::getExtendsEntity() . '/classes/BWSApi.php';
        }
    }
}