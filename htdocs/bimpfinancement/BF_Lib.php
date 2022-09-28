<?php

if (!defined('BF_LIB')) {
    define('BF_LIB', 1);

    $dir = DOL_DOCUMENT_ROOT . '/bimpfinancement/classes';

    if (is_dir($dir)) {
        foreach (scandir($dir) as $file) {
            if (in_array($file, array('.', '..'))) {
                continue;
            }

            require_once $dir . '/' . $file;
        }
    }
}