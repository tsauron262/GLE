<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/BimpDocumentPDF.php';

class MandatSepaFinancementPDF extends BimpModelPDF
{

    public $data = null;
    public $srcFile = DOL_DOCUMENT_ROOT . '/bimpfinancement/pdf/mandat_sepa.pdf';

    public function __construct($db, $data)
    {
        $this->data = $data;
        parent::__construct($db);

        $this->add_footer = false;
        $this->add_header = false;
    }

    public function initHeader()
    {
        
    }

    public function initfooter()
    {
        
    }

    protected function renderContent()
    {
        $this->pdf->createHeader('');
        $this->pdf->createFooter('');

        $nom = BimpTools::getArrayValueFromPath($this->data, 'nom', '');
        $address = BimpTools::getArrayValueFromPath($this->data, 'address', '');
        $siren = BimpTools::getArrayValueFromPath($this->data, 'siren', '');
        $email = BimpTools::getArrayValueFromPath($this->data, 'email', '');

        $this->pdf->SetFont('', 'B', 9);
        $this->pdf->SetXY(46, 80);
        $this->pdf->Cell(0, 0, $nom, 0, 2, 'L', 0);

        $y = 81;
        foreach (explode(', ', $address) as $line) {
            $y += 4;
            $this->pdf->SetXY(37, $y);
            $this->pdf->Cell(0, 0, $line, 0, 2, 'L', 0);
        }


        $this->pdf->SetXY(34, 103);
        $this->pdf->Cell(0, 0, $siren, 0, 2, 'L', 0);

        $this->pdf->SetXY(34, 108);
        $this->pdf->Cell(0, 0, $email, 0, 2, 'L', 0);
    }

    public function render($file_name, $display, $display_only = false)
    {
        if (parent::render($file_name, $display, $display_only)) {
            $pdf = new BimpConcatPdf();
            $pdf->mergeFiles($file_name, $this->srcFile, $file_name, $display);
            return 1;
        }

        return 0;
    }
}
