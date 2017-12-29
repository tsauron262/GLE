<?php

require_once __DIR__.'/BimpPDF.php';

class InvoicePDF
{

    protected $pdf = null;
    public $tpl_dir = DOL_DOCUMENT_ROOT . '/bimpcore/pdf/templates/invoice/';
    public $header_vars = array(
        'logo_img' => DOL_DATA_ROOT . '/mycompany/logos/logo.jpg',
        'logo_width' => '120'
    );
    public $footer_vars = array(
        'footer_line_1' => 'Société anonyme à conseil d administration - Capital de 1085372€ - N° de déclaration d’activité: 320 387 483 00060',
        'footer_line_2' => 'NAF: 4741Z - RCS: Lyon B 320 387 483 - TVA intracommunautaire: FR 34 320387483'
    );

    public function __construct()
    {
        $this->pdf = new BimpPDF();
    }

    public function render($file_name, $display)
    {
        $header = $this->pdf->renderTemplate($this->tpl_dir . '/header.html', $this->header_vars);
        $this->pdf->createHeader($header);
        $footer = $this->pdf->renderTemplate($this->tpl_dir . '/footer.html', $this->footer_vars);
        $this->pdf->createFooter($footer);

        $this->pdf->writePage('<p>CONTENT</p>');

        $this->pdf->render($file_name, $display);
    }
}
