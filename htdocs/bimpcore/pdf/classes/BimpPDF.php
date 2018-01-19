<?php

require_once DOL_DOCUMENT_ROOT . '/includes/tecnickcom/tcpdf/tcpdf.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';

class BimpPDF extends TCPDF
{

    protected $header = '';
    protected $footer = '';
    protected $pagination = '';
    public $topMargin = 40;
    public $sideMargin = 10;
    public $headerMargin = 10;
    public $footerMargin = 20;
    public static $mmPerPx = 0.353; // Pour 72 dpi
    public static $pxPerMm = 2.835;

    public function __construct($orientation = 'P', $format = 'A4')
    {
        parent::__construct($orientation, 'mm', $format);
    }

    public function createHeader($header)
    {
        $this->header = $header;
    }

    public function createFooter($footer)
    {
        $this->footer = $footer;
    }

    public function Header()
    {
        $this->writeHTML($this->header);
    }

    public function Footer()
    {
        $this->writeHTML($this->footer);
    }

    public function newPage()
    {
        $this->SetHeaderMargin($this->headerMargin);
        $this->SetFooterMargin($this->footerMargin);
        $this->setMargins($this->sideMargin, $this->topMargin, $this->sideMargin);
        $this->SetAutoPageBreak(true, $this->footerMargin + 5);
        $this->AddPage();
    }

    public function render($filename, $display = true)
    {
        $this->lastPage();

        if (stripos($filename, ".pdf") === false)
            $filename .= ".pdf";

        $tabT = explode("/", $filename);
        $nomPure = $tabT[count($tabT) - 1];


        if ($display === true) {
            $display = 'I';
        } elseif ($display === false) {
            $display = 'F';
        }
        if ($display == 'F') {// on enregistre sur server
            $output = 'F';

            $folder = str_replace($nomPure, "", $filename);
            if (!is_dir($folder))
                if (!mkdir($folder))
                    die("Le dossier " . $folder . " n'existe pas et ne peut être créé");
        } else {
            if ($display == 'DS') {//On enregistre et on download
                $this->Output($filename, 'F');
                $display = 'D';
            } elseif ($display == 'IS') {//On enregistre et on affiche
                $this->Output($filename, 'F');
                $display = 'I';
            }

            $filename = $nomPure;
            if ($display == 'D') { //On download
                $output = 'D';
            } elseif ($display == 'S') { // retour en châine de caractères
                $output = 'S';
            } else {               //On affiche
                $output = 'I';
                $filename = $nomPure;
            }
        }

        return $this->Output($filename, $output);
    }
}
