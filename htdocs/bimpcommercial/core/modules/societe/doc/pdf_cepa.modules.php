<?php

if (defined('') && in_array(BimpCore::getExtendsEntity(), array('bimp'))) {
    require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/CepaPDF_new.php';
} else {
    require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/CepaPDF.php';
}

/**
 * 	Class to generate PDF proposal Azur
 */
class pdf_cepa extends CepaPDF
{

    public function initData()
    {
        parent::initData();
    }
}
