<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/BimpEtiquettePDF.php';



class EtiquetteProd1 extends BimpEtiquettePDF {


    public function __construct($db) {
        parent::__construct($db);

        $this->langs->load("products");
        $this->typeObject = "product";
        $this->prefName = "Etiquette_Stock_";
    }

    public function writeContent($content = '') {
        $debug = false;
//        $html = "";
        
        $this->pdf->SetFont('times', 'B', 15);
        $this->pdf->setXY(6,1);
        $this->pdf->Cell(77,5,dol_trunc($this->object->ref,18),1,0,'C');
        
        
        $this->pdf->setXY(6,8);
        $this->pdf->SetFont('times', '', 10);
        
        $label = $this->object->label;
        $label = dol_trunc($label,90);
        if(strlen($label) > 53)
            $this->pdf->SetFont('times', '', 9);
        
        
        
        $this->pdf->MultiCell(77,5,$label,$debug,'C');
        
//         $html .= "<span class='center'>".$label."</span>";
//
//        $html .= "<div class='tier fleft'></div>";
//        $html .= "<div class='cadre tier fleft'>".dol_print_date(dol_now(), "%B %Y")."</div>";
//        $html .= "<div class='tier fleft'>".price($this->object->price)." €</div>";


        
//        $this->writeContent($html);
        if (file_exists(static::$tpl_dir . '/' . static::$type . '/logomininoir.jpg'))
            $this->pdf->Image( static::$tpl_dir . '/' . static::$type . '/logomininoir.jpg', 6,25,15,10);
        
        
        $codeBar = ($this->object->barcode != "")? $this->object->barcode : $this->object->ref;
        $maxLn = 35;
        $longeur = (strlen($codeBar) < $maxLn)? strlen($codeBar) : $maxLn;
        $this->pdf->write1DBarcode($codeBar, 'C128', 41-($longeur), 18, 7+($longeur*2), 5, '', array('text'=> true));
        
        
        
        $this->pdf->setXY(32,29);
        $this->pdf->Cell(25,5,dol_print_date(dol_now(), "%B %Y"),1,0,'C');
        $this->pdf->setXY(59,29);
        $price = $this->object->price * (1+$this->object->tva_tx/100);
        $this->pdf->Cell(24,5,price($price)." €",$debug,0,'R');
    }

}
