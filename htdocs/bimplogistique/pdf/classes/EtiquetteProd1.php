<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/BimpEtiquettePDF.php';



class EtiquetteProd1 extends BimpEtiquettePDF {


    public function __construct($db) {
        parent::__construct($db);

        $this->langs->load("products");
        $this->typeObject = "product";
        $this->prefName = "Etiquette_Stock_";
    }

    protected function renderContent() {

//        $html = "";
        
        $this->pdf->SetFont('times', 'B', 15);
        $this->pdf->setXY(6,1);
        $this->pdf->Cell(77,5,$this->object->ref,1,0,'C');
        
        
        $this->pdf->setXY(6,8);
        $this->pdf->SetFont('times', '', 10);
        
        $label = $this->object->label;
        if(strlen($label) > 53)
            $this->pdf->SetFont('times', '', 9);
        
        if(strlen($label) > 118)
            $label = substr($label,0,115)."...";
        
        
        $this->pdf->MultiCell(77,5,$label,0,'C');
        
//         $html .= "<span class='center'>".$label."</span>";
//
//        $html .= "<div class='tier fleft'></div>";
//        $html .= "<div class='cadre tier fleft'>".dol_print_date(dol_now(), "%B %Y")."</div>";
//        $html .= "<div class='tier fleft'>".price($this->object->price)." €</div>";


        
//        $this->writeContent($html);
        if (file_exists(static::$tpl_dir . '/' . static::$type . '/logomininoir.jpg'))
            $this->pdf->Image( static::$tpl_dir . '/' . static::$type . '/logomininoir.jpg', 5,25,15,10);
        
        
        $codeBar = ($this->object->barcode != "")? $this->object->barcode : $this->object->ref;
        $this->pdf->write1DBarcode($codeBar, 'C128', 26, 16, 35, 8, '', array('text'=> true));
        
        
        
        $this->pdf->setXY(30,29);
        $this->pdf->Cell(25,5,dol_print_date(dol_now(), "%B %Y"),1,0,'C');
        $this->pdf->setXY(60,29);
        $price = $this->object->price * (1+$this->object->tva_tx/100);
        $this->pdf->Cell(25,5,price($price)." €",0,0,'C');
    }

}
