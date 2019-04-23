<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/BimpEtiquettePDF.php';

class EtiquetteProd2 extends BimpEtiquettePDF
{


    public function __construct($db)
    {
        parent::__construct($db, 'L', array(51,19));

        $this->langs->load("products");
        $this->typeObject = "product";
        $this->prefName = "Etiquette_Magasin_";
    }

       protected function renderContent() {

//        $html = "";
        if(strlen($this->object->description) < 23)
            $this->pdf->SetFont('times', 'B', 10);
        elseif(strlen($this->object->description) < 35)
            $this->pdf->SetFont('times', 'B', 7);
        elseif(strlen($this->object->description) < 46)
            $this->pdf->SetFont('times', 'B', 6);
        else{
            $this->object->description = substr($this->object->description,0,43)."...";
            $this->pdf->SetFont('times', 'B', 6);
        }
        $this->pdf->setXY(6,1);
        $this->pdf->Cell(40,5,$this->object->description,0,0,'C');
        
        
        $this->pdf->SetFont('times', '', 8);
        $this->pdf->setXY(6,6);
        $this->pdf->MultiCell(40,5,$this->object->ref,0,'C');
        
//         $html .= "<span class='center'>".$this->object->description."</span>";
//
//        $html .= "<div class='tier fleft'></div>";
//        $html .= "<div class='cadre tier fleft'>".dol_print_date(dol_now(), "%B %Y")."</div>";
//        $html .= "<div class='tier fleft'>".price($this->object->price)." €</div>";


        
//        $this->writeContent($html);
        if (file_exists(static::$tpl_dir . '/' . static::$type . '/logomininoir.jpg'))
            $this->pdf->Image( static::$tpl_dir . '/' . static::$type . '/logomininoir.jpg', 6,10,10,7);
        
        
//        $codeBar = ($this->object->barcode != "")? $this->object->barcode : $this->object->ref;
//        $this->pdf->write1DBarcode($codeBar, 'C128', 26, 16, 35, 8, '', array('text'=> true));
//        
//        
//        
//        $this->pdf->setXY(30,29);
//        $this->pdf->Cell(25,5,dol_print_date(dol_now(), "%B %Y"),1,0,'C');
        $price = $this->object->price * (1+$this->object->tva_tx/100);
        $deee = 2.45;
        
        $this->pdf->setXY(17,10);
        $this->pdf->Cell(25,5,"Prix TTC : ".price($price)." €",0,0,'L');
        $this->pdf->SetFont('times', '', 8);
        if($deee > 0){            
            $this->pdf->setXY(17,13);
            $this->pdf->SetFont('times', '', 7);
            $this->pdf->Cell(25,5,"Dont ".$deee." € d'EcoTaxe",0,1,'L');
        }
    }

}
