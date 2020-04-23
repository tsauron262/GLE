<?php
require_once DOL_DOCUMENT_ROOT . '/includes/tecnickcom/tcpdf/tcpdf.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';

class BimpPDF extends TCPDF
{

    protected $header = '';
    protected $footer = '';
    protected $pagination = '';
    public $topMargin = 42;
    public $sideMargin = 10;
    public $headerMargin = 10;
    public $footerMargin = 30;
    public static $mmPerPx = 0.353; // Pour 72 dpi
    public static $pxPerMm = 2.835;
    public $addCgvPages = true;

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
        $this->SetAutoPageBreak(true, $this->footerMargin);
        $this->AddPage();
    }

    public function render($filename, $display = true, $display_only = false)
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
                if (!mkdir($folder)) {
                    if (!BimpTools::isSubmit('ajax')) {
                        die("Le dossier " . $folder . " n'existe pas et ne peut être créé");
                    } else {
                        return 0;
                    }
                }
        } else {
            if ($display == 'DS') {//On enregistre et on download
                $this->Output($filename, 'F');
                $display = 'D';
            } elseif ($display == 'IS') {//On enregistre et on affiche
                //$this->Output($filename, 'F');
                $display = 'I';
            }

            if ($display == 'D') { //On download
                $output = 'D';
            } elseif ($display == 'S') { // retour en châine de caractères
                $output = 'S';
            } else {               //On affiche
                $output = 'I';
            }
        }


        if ($output == "I" && !$display_only) {
            $afficher = true;
            $output = "F";
        }

        $addCgvPages = ($this->addCgvPages && BimpCore::getConf("CGV_BIMP")); //sinon $this->$addCgvPages ce fait ecrasé.
        $this->Output($filename, $output);

        if ($addCgvPages) {
            $fpdfi = new BimpConcatPdf();
            $fpdfi->addCGVPages($filename, $output);
        }

        if ($afficher) {
            header("Content-type: application/pdf");
            header("Content-Disposition: inline; filename=" . $nomPure);
            @readfile($filename);
            die;
        }

        return 1;
    }

    // Outils: 

    public function addVMargin($margin)
    {
        $this->SetY($this->GetY() + $margin);
    }
}

use setasign\Fpdi\Fpdi;

require_once DOL_DOCUMENT_ROOT . '/includes/tcpdi/tcpdi.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/src/fpdf2.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/src/autoload.php';

class BimpConcatPdf extends Fpdi
{

    public function addCGVPages($fileOrig, $output)
    {
        $file = $fileOrig;
        $pagecount = $this->setSourceFile($file);
        for ($i = 0; $i < $pagecount; $i++) {
            $this->AddPage();
            $tplidx = $this->importPage($i + 1, '/MediaBox');
            $this->useTemplate($tplidx);
        }
        $file = DOL_DOCUMENT_ROOT . "/bimpcore/pdf/cgv.pdf";
        $pagecount = $this->setSourceFile($file);
        for ($i = 0; $i < $pagecount; $i++) {
            $this->AddPage();
            $tplidx = $this->importPage($i + 1, '/MediaBox');
            $this->useTemplate($tplidx);
        }
        $this->Output($fileOrig, $output);
    }

    public function concatFiles($fileName, $files, $output)
    {
        foreach ($files as $file) {
            $pagecount = $this->setSourceFile($file);
            for ($i = 0; $i < $pagecount; $i++) {
                $this->AddPage();
                $tplidx = $this->importPage($i + 1, '/MediaBox');
                $this->useTemplate($tplidx);
            }
        }

        $this->Output($fileName, $output);
    }
}

//class BimpWatermarkPDF extends Fpdi
//{
//
//    public $_tplIdx;
//    public $angle = 0;
//    public $fullPathToFile;
//    public $rotatedText = 'Muvi Entertainment Pvt. Ltd.';
//
//    function __construct($fullPathToFile, $rotate_text)
//    {
//        $this->fullPathToFile = $fullPathToFile;
//        if ($rotate_text)
//            $this->rotatedText = $rotate_text;
//        parent::__construct();
//    }
//
//    function Rotate($angle, $x = -1, $y = -1)
//    {
//        if ($x == -1)
//            $x = $this->x;
//        if ($y == -1)
//            $y = $this->y;
//        if ($this->angle != 0)
//            $this->_out('Q');
//        $this->angle = $angle;
//        if ($angle != 0) {
//            $angle *= M_PI / 180;
//            $c = cos($angle);
//            $s = sin($angle);
//            $cx = $x * $this->k;
//            $cy = ($this->h - $y) * $this->k;
//            $this->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm', $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy));
//        }
//    }
//
//    function _endpage()
//    {
//        if ($this->angle != 0) {
//            $this->angle = 0;
//            $this->_out('Q');
//        }
//        parent::_endpage();
//    }
//
//    function Header()
//    {
//        //Put the watermark
//        $this->SetFont('Arial', 'B', 50);
//        $this->SetTextColor(255, 192, 203);
//        $this->RotatedText(20, 230, $this->rotatedText, 45);
//        if ($this->fullPathToFile) {
//            if (is_null($this->_tplIdx)) {
//                $this->numPages = $this->setSourceFile($this->fullPathToFile);
//                $this->_tplIdx = $this->importPage(1);
//            }
//            $this->useTemplate($this->_tplIdx, 0, 0, 200);
//        }
//    }
//
//    function RotatedText($x, $y, $txt, $angle)
//    {
//        //Text rotated around its origin
//        $this->Rotate($angle, $x, $y);
//        $this->Text($x, $y, $txt);
//        $this->Rotate(0);
//    }
//}
