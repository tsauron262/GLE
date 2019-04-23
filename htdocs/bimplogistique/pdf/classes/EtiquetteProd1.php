<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/BimpEtiquettePDF.php';



class EtiquetteProd1 extends BimpEtiquettePDF {

    public static $type = 'product';

    public function __construct($db) {
        parent::__construct($db);

        $this->langs->load("products");
        $this->typeObject = "product";
        $this->prefName = "Etiquette1_";
    }

    protected function renderContent() {

        $html = "";
        $html .= $this->object->ref;
        $html .= "<br/>";
        $html .= dol_print_date(dol_now());


        $this->writeContent($html);
        
        
        $codeBar = ($this->object->barcode != "")? $this->object->barcode : $this->object->ref;
        $this->pdf->write1DBarcode($codeBar, 'C128', 45, '', '', '', '', array('text'=> true));
    }

}
