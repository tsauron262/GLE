<?php

require_once __DIR__ . '/BimpModelPDF.php';
require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';

ini_set('display_errors', 1);

class CepaPDF extends BimpModelPDF
{

    public static $type = 'societe';
    public $propal = null;
    public $rib = null;
    public $mode = "normal";
    public $signature_bloc = true;
    public $signature_bloc_label = 'Bon pour commande';
    public $pdf2 = null;

    public function __construct($db, $orientation = 'P', $format = 'A4')
    {
        parent::__construct($db, $orientation = 'P', $format = 'A4');

        $this->langs->load("bills");
        $this->langs->load("propal");
        $this->langs->load("products");

        $this->typeObject = 'societe';

        $this->propal = new Propal($db);
//        $this->pdf2 = new BimpPDF($orientation, $format);
        $this->pdf2 = pdf_getInstance($format);
    }

    protected function initData()
    {
        require_once DOL_DOCUMENT_ROOT . '/includes/tcpdi/tcpdi.php';
        require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/src/fpdf2.php';
        require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/src/autoload.php';
        $this->pdf2->addPage();
        $this->pdf2->SetFont('Times');

        $file_path = BimpCore::requireFileForEntity('bimpcore', 'pdf/docs_sources/SEPA.pdf', true);

        if (!$file_path || !file_exists($file_path)) {
            $this->errors[] = 'Fichier source "SEPA.pdf" absent';
        } else {
            $pagecountTpl = $this->pdf2->setSourceFile($file_path);
            $tplidx = $this->pdf2->importPage(1, "/MediaBox");
            $this->pdf2->useTemplate($tplidx, 0, 0, 0, 0, true);
//        $size = $this->pdf2->getTemplateSize($tplidx);
//        $this->pdf2->useTemplate($tplidx, null, null, $size['w'], $size['h'], true);

            $soc = BimpCache::getBimpObjectInstance("bimpcore", "Bimp_Societe", $this->object->id);

            $rib = $soc->getDefaultRib(true);

            if (BimpObject::objectLoaded($rib)) {
                $rum = $rib->getData('rum');
                $file = $this->getFilePath() . $rib->getFileName();

                $this->pdf2->setXY(120, 107.3);
                $this->pdf2->Cell(70, 8, $rum, 0);

                $off_x = 0;
                $off_y = 0;

                switch (BimpCore::getEntity()) {
                    case 'actimac':
                        $off_x = 2;
                        $off_y = 0.7;
                        break;
                }

                $this->pdf2->setXY(60 + $off_x, 40 + $off_y);
                $this->pdf2->Cell(70, 8, $soc->getData('code_client'), 0);

                $this->pdf2->Close();

                $this->pdf2->Output($file, 'F');
            } else {
                $this->errors[] = 'Aucun RIB par défaut pour ce client';
            }
        }
    }
//    public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0) {
//        
//        $this->init($object);
//        return 1;
//    }
}
