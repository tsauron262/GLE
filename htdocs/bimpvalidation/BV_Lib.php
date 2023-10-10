<?php

if (!defined('BV_LIB')) {
    define('BV_LIB', 1);

    if (!defined('BIMP_LIB')) {
        require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
    }

    require_once DOL_DOCUMENT_ROOT . '/bimpvalidation/classes/BimpValidation.php';
    
    BimpObject::loadClass('bimpvalidation', 'BV_Rule');
    BimpObject::loadClass('bimpvalidation', 'BV_Demande');
}