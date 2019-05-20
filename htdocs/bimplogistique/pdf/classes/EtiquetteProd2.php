<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/BimpEtiquettePDF.php';

class EtiquetteProd2 extends BimpEtiquettePDF {

    public function __construct($db) {
        parent::__construct($db, 'L', array(51, 19));

        $this->langs->load("products");
        $this->typeObject = "product";
        $this->prefName = "Etiquette_Magasin_";
    }

    public function writeContent($content = '') {
        $debug = false;
//        $html = "";
        $label = $this->object->label;
        $this->pdf->setXY(6, 1);
//        $label = substr($label, 0, 3);
        $deuxLigne = false;
        if (strlen($label) < 23)
            $this->pdf->SetFont('times', 'B', 10);
        elseif (strlen($label) < 27)
            $this->pdf->SetFont('times', 'B', 8);
        else {
            $label = dol_trunc($label, 69);
            $this->pdf->SetFont('times', 'B', 6);
            if(strlen($label) > 36)
                $deuxLigne = true;
        }
        $this->pdf->MultiCell(43, 5, ($label), $debug, 'C');

        $this->pdf->SetFont('times', '', 8);
        $this->pdf->setXY(6, ($deuxLigne)? 6 : 4);

        $this->pdf->MultiCell(43, 2, dol_trunc($this->object->ref, 18), $debug, 'C');

        $price = $this->object->price * (1 + $this->object->tva_tx / 100);
        $deee = false;
        if (isset($this->object->array_options['options_deee']))
            $deee = price($this->object->array_options['options_deee']);

        $this->pdf->setXY(19, 10);
        $this->pdf->Cell(30, 2, "Prix TTC : " . price($price) . " €", $debug, 0, 'R');
        $this->pdf->SetFont('times', '', 8);
        if ($deee && $deee != "0,00") {
            $this->pdf->setXY(19, 13);
            $this->pdf->SetFont('times', '', 5);
            $this->pdf->Cell(30, 2, "Dont " . $deee . " € d'EcoTaxe", $debug, 0, 'R');
        }
        if (file_exists(static::$tpl_dir . '/' . static::$type . '/logomininoir.jpg'))
            $this->pdf->Image(static::$tpl_dir . '/' . static::$type . '/logomininoir.jpg', 6, ($deuxLigne)? 8.9: 7.9, 9, 6.5);
        
        $codeBar = ($this->object->barcode != "") ? $this->object->barcode : $this->object->ref;
        $hCodeBar = (strlen($codeBar) < 20) ? 1.8 : 2.3;
        $this->pdf->write1DBarcode($codeBar, 'C128', 7, (18 - $hCodeBar), 42, $hCodeBar, '', array('text' => false));
    }

}
