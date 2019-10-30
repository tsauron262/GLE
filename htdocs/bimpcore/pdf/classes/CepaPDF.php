<?php

require_once __DIR__ . '/BimpDocumentPDF.php';
require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';

ini_set('display_errors', 1);

class CepaPDF extends BimpDocumentPDF
{

    public static $type = 'societe';
    public $propal = null;
    public $mode = "normal";
    public $after_totaux_label = 'Bon pour commande';

    public function __construct($db)
    {
        parent::__construct($db);

        $this->langs->load("bills");
        $this->langs->load("propal");
        $this->langs->load("products");

        $this->typeObject = 'societe';

        $this->propal = new Propal($db);
    }

    protected function initData()
    {
        
        require_once DOL_DOCUMENT_ROOT . '/includes/tcpdi/tcpdi.php';
        require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/src/fpdf2.php';
        require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/src/autoload.php';
        $this->pdf2 = new BimpConcatPdf();
        $this->pdf2->addPage();
        $this->pdf2->SetFont('Times');
        $pagecountTpl = $this->pdf2->setSourceFile(DOL_DOCUMENT_ROOT . '/bimpcore/pdf/templates/SEPA-2.pdf');
        $tplidx = $this->pdf2->importPage(1, "/MediaBox");
        $size = $this->pdf2->getTemplateSize($tplIdx);
        $this->pdf2->useTemplate($tplidx, null, null, $size['w'], $size['h'], true);
        $file = $this->getFilePath() . $this->getFileName().'_sepa.pdf';
        
        $soc = BimpCache::getBimpObjectInstance("bimpcore", "Bimp_Societe", $this->object->id);
        
        $this->pdf2->setXY(105,118.7);
        $this->pdf2->Cell(70,8, $soc->getNumSepa(), 0);
            
        $this->pdf2->Output($file, 'F');

    }

    
    public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0) {
        
        $this->init($object);
        return 1;
    }
}
