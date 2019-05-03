<?php

require_once __DIR__ . '/BimpModelPDF.php';

class BimpEtiquettePDF extends BimpModelPDF
{

    // Format en px : 252 x 102
    public function __construct($db, $orientation = 'L', $format = array(89, 36))
    {
        parent::__construct($db, $orientation, $format);
        $this->pdf->addCgvPages = false;
        $this->pdf->headerMargin = 2;
        $this->pdf->topMargin = 2;
        $this->pdf->sideMargin = 6;
        $this->pdf->footerMargin = 1;
        self::$type = "etiquettes";
    }
}
