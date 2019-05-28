<?php

require_once __DIR__ . '/BimpModelPDF.php';

class BimpEtiquettePDF extends BimpModelPDF
{

    public $qty_etiquettes = 1;

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
    
    protected function renderContent()
    {
        $html = $this->getContentHtml();
        
        $this->writeContent(str_replace('etiquette_number', 1, $html));

        for ($i = 2; $i <= $this->qty_etiquettes; $i++) {
            $this->pdf->newPage();
            $this->writeContent(str_replace('etiquette_number', $i, $html));
        }
    }
    
    protected function getContentHtml()
    {
        // Générer ici le contenu HTML de l'étiquette. 
        // Si plusieurs étiquettes numérotées à générer, il est possible d'insérer le mot-clé "etiquette_number" qui sera automatiquement remplacé
        // par le n° de l'étiquette. 
        
        return '';
    }
}
