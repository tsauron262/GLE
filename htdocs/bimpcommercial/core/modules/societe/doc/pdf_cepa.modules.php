<?php

if (defined('BIMP_LIB') && in_array(BimpCore::getExtendsEntity(), array('bimp'))) {
    require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/CepaPDF.php';

    class pdf_cepa extends CepaPDF
    {

        public function initData()
        {
            parent::initData();
        }
    }

} else {
    require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/CepaPDF_old.php';

    class pdf_cepa extends CepaPDF_Old
    {

        public function initData()
        {
            parent::initData();
        }
    }
}


