<?php

require_once DOL_DOCUMENT_ROOT .  '/bimpcore/pdf/classes/BimpDocumentPDF.php';

class EtiquetteProd2 extends BimpDocumentPDF
{

    public static $type = 'product';

    public function __construct($db)
    {
        parent::__construct($db);

        $this->langs->load("products");
        $this->typeObject = "product";
        $this->prefName = "Etiquette2_";
    }

    protected function initData()
    {
        parent::initData();
    }

}
