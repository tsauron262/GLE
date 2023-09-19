<?php

require_once DOL_DOCUMENT_ROOT . '/includes/tecnickcom/tcpdf/tcpdf.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';

class BimpPDF extends TCPDF
{

    protected $header = '';
    protected $footer = '';
    protected $pagination = '';
    public $topMargin = 42;
    public $sideMargin = 10; // old: 10
    public $headerMargin = 6;
    public $footerMargin = 14;
    public static $mmPerPx = 0.353; // Pour 72 dpi
    public static $pxPerMm = 2.835;
    public $addCgvPages = true;
    public static $addCgvPagesType = '';
    public $extra_concat_files = array();

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
        $this->SetAutoPageBreak(true, $this->footerMargin + 2);
        $this->AddPage();
    }

    public function render($filename, $display = true, $display_only = false, $watermark = '', &$errors = array())
    {
        $extra_concat_files = $this->extra_concat_files;

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
                        $errors[] = "Le dossier " . $folder . " n'existe pas et ne peut être créé";
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

        $addCgvPages = ($this->addCgvPages && BimpCore::getConf('pdf_add_cgv', 0, 'bimpcommercial')); //sinon $this->$addCgvPages ce fait ecrasé.
        $this->Output($filename, $output);

        if ($addCgvPages) {
            $fpdfi = new BimpConcatPdf();
            $fpdfi->addCGVPages($filename, $output, static::$addCgvPagesType);
        }

        if ($watermark) {
            $fpdfi = new BimpConcatPdf();
            $fpdfi->addWatermark($filename, $watermark, $output);
        }

        if ($extra_concat_files) {
            $fpdfi = new BimpConcatPdf();
            $fpdfi->addFiles($filename, $extra_concat_files, $output);
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

    protected $extgstates = array();

    public function addCGVPages($fileOrig, $output, $type = '')
    {
        $file = $fileOrig;
        $pagecount = $this->setSourceFile($file);
        for ($i = 0; $i < $pagecount; $i++) {
            $this->AddPage();
            $tplidx = $this->importPage($i + 1, '/MediaBox');
            $this->useTemplate($tplidx);
        }
        $file = DOL_DATA_ROOT . "/bimpcore/pdf/cgv" . $type . ".pdf";
        if (!is_file($file)){
            $file = DOL_DATA_ROOT . "/bimpcore/pdf/cgv.pdf";
            if (!is_file($file)){
                $file = DOL_DOCUMENT_ROOT . "/bimpcore/pdf/cgv" . $type . ".pdf";
                if (!is_file($file))
                    $file = DOL_DOCUMENT_ROOT . "/bimpcore/pdf/cgv.pdf";
            }
        }
        $pagecount = $this->setSourceFile($file);
        for ($i = 0; $i < $pagecount; $i++) {
            $this->AddPage();
            $tplidx = $this->importPage($i + 1, '/MediaBox');
            $this->useTemplate($tplidx);
        }
        $this->Output($fileOrig, $output);
    }

    public function addFiles($file, $files_to_add, $output)
    {
        $pagecount = $this->setSourceFile($file);
        for ($i = 0; $i < $pagecount; $i++) {
            $this->AddPage();
            $tplidx = $this->importPage($i + 1, '/MediaBox');
            $this->useTemplate($tplidx);
        }

        foreach ($files_to_add as $file_to_add) {
            $file_path = '';

            if (is_string($file_to_add)) {
                $file_path = $file_to_add;
            } elseif (isset($file_to_add['file'])) {
                $file_path = $file_to_add['file'];
            }

            $pagecount = $this->setSourceFile($file_path);
            for ($i = 1; $i <= $pagecount; $i++) {
                $this->AddPage();
                $tplidx = $this->importPage($i, '/MediaBox');
                $this->useTemplate($tplidx);

                if (isset($file_to_add['inserts'])) {
                    foreach ($file_to_add['inserts'] as $insert) {
                        if (!isset($insert['page']) || $insert['page'] == 'all' || $insert['page'] == $i) {
                            $font = BimpTools::getArrayValueFromPath($insert, 'font', 'Arial');
                            $style = BimpTools::getArrayValueFromPath($insert, 'style', '');
                            $size = BimpTools::getArrayValueFromPath($insert, 'style', 12);
                            $color = BimpTools::getArrayValueFromPath($insert, 'color', array(0, 0, 0));
                            $x = BimpTools::getArrayValueFromPath($insert, 'x', 0);
                            $y = BimpTools::getArrayValueFromPath($insert, 'y', 0);
                            $align = BimpTools::getArrayValueFromPath($insert, 'align', 'L');

                            $this->SetFont($font, $style, $size);
                            $this->SetTextColor($color[0], $color[1], $color[2]);

                            if (isset($insert['texts'])) {
                                foreach ($insert['texts'] as $t) {
                                    if (is_array($t)) {
                                        $x = BimpTools::getArrayValueFromPath($t, 'x', $x);
                                        $y = BimpTools::getArrayValueFromPath($t, 'y', $y);
                                        $text = $t['text'];
                                    } else {
                                        $text = $t;
                                    }

                                    $this->SetXY($x, $y);
                                    $this->Cell(0, 0, utf8_decode($text), 0, 2, $align, 0);
                                }
                            } elseif (isset($insert['text'])) {
                                $this->SetXY($x, $y);
                                $this->Cell(0, 0, utf8_decode($insert['text']), 0, 2, $align, 0);
                            }
                        }
                    }
                }
            }
        }

        $this->Output($file, $output);
    }

    public function concatFiles($fileName, $files, $output = 'F')
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

    public function mergeFiles($fileName, $file1, $file2, $output = 'F', $file1_page_start = 1, $file2_page_start = 1)
    {
        for ($i = 1; $i < $file1_page_start; $i++) {
            $this->AddPage();
        }

        $this->AddPage();
        $this->page = $file1_page_start;
        
        $pagecount1 = $this->setSourceFile($file1);
        for ($i = 1; $i <= $pagecount1; $i++) {
            if ($i > 1) {
                $this->AddPage();
            }
            $tplidx = $this->importPage($i, '/MediaBox');
            $this->useTemplate($tplidx);
        }

        $pagecount1 += ($file1_page_start - 1);

        $this->page = $file2_page_start - 1;
        $pagecount2 = $this->setSourceFile($file2);
        for ($i = 1; $i <= $pagecount2; $i++) {
            if ($i > $pagecount1) {
                $this->AddPage();
            } else {
                $this->page++;
            }
            $tplidx = $this->importPage($i, '/MediaBox');
            $this->useTemplate($tplidx);
        }

        $this->Output($fileName, $output);
    }

    public function generateDuplicata($srcFile, $destFile = null, $text = 'DUPLICATA', $output = 'F', $text2 = 'Certifié conforme à l\'original')
    {
        $errors = array();

        if (file_exists($srcFile)) {
            if (is_null($destFile)) {
                $path = pathinfo($srcFile);
                if ($output === 'I') {
                    $destFile = $path['filename'] . '_duplicata.' . $path['extension'];
                } else {
                    $destFile = $path['dirname'] . '/' . $path['filename'] . '_duplicata.' . $path['extension'];
                }
            }

            $unit = 'mm';
            $h = 297;
            $w = 210;

            if ($unit == 'pt')
                $k = 1;
            elseif ($unit == 'mm')
                $k = 72 / 25.4;
            elseif ($unit == 'cm')
                $k = 72 / 2.54;
            elseif ($unit == 'in')
                $k = 72;

            $this->SetTextColor(255, 192, 203);

            $savx = $this->getX();
            $savy = $this->getY();

            $watermark_angle = atan($h / $w) / 2;
            $watermark_x_pos = 0;
            $watermark_y_pos = $h / 3;
            $watermark_x = $w / 2;
            $watermark_y = $h / 3;

            $pagecount = $this->setSourceFile($srcFile);

            for ($i = 0; $i < $pagecount; $i++) {
                $this->AddPage();
                $tplidx = $this->importPage($i + 1);
                $this->useTemplate($tplidx);

                $this->SetAlpha(0.4);

                $this->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm', cos($watermark_angle), sin($watermark_angle), -sin($watermark_angle), cos($watermark_angle), $watermark_x * $k, ($h - $watermark_y) * $k, -$watermark_x * $k, -($h - $watermark_y) * $k));

                $this->SetFont('Arial', 'B', 70);
                $this->SetXY($watermark_x_pos, $watermark_y_pos);
                $this->Cell($w - 20, 50, $text, "", 2, "C", 0);

                if ($text2 != '') {
                    $this->SetFont('Arial', 'B', 20);
                    $this->SetXY($watermark_x_pos, $watermark_y_pos + 15);
                    $this->Cell($w - 20, 50, utf8_decode($text2), "", 2, "C", 0);
                }

                $this->_out('Q');
                $this->SetXY($savx, $savy);
                $this->SetAlpha(1);
            }

            $this->Output($destFile, $output);
        } else {
            $errors[] = 'fichier "' . $srcFile . '" inexistant';
        }

        return $errors;
    }

    public function addWatermark($filePath, $watermark, $output)
    {
        $errors = array();

        if (!$filePath) {
            $errors[] = 'Nom du fichier asbent';
        } else {
            if (!file_exists($filePath)) {
                $errors[] = 'Le fichier "' . $filePath . '" n\'existe pas';
            } else {
                $unit = 'mm';
                $h = 297;
                $w = 210;

                if ($unit == 'pt')
                    $k = 1;
                elseif ($unit == 'mm')
                    $k = 72 / 25.4;
                elseif ($unit == 'cm')
                    $k = 72 / 2.54;
                elseif ($unit == 'in')
                    $k = 72;

                $this->SetFont('Arial', 'B', 70);
                $this->SetTextColor(255, 192, 203);

                $savx = $this->getX();
                $savy = $this->getY();

                $watermark_angle = atan($h / $w) / 2;
                $watermark_x_pos = 0;
                $watermark_y_pos = $h / 3;
                $watermark_x = $w / 2;
                $watermark_y = $h / 3;

                $pagecount = $this->setSourceFile($filePath);

                for ($i = 0; $i < $pagecount; $i++) {
                    $this->AddPage();
                    $tplidx = $this->importPage($i + 1);
                    $this->useTemplate($tplidx);

                    $this->SetAlpha(0.4);

                    $this->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm', cos($watermark_angle), sin($watermark_angle), -sin($watermark_angle), cos($watermark_angle), $watermark_x * $k, ($h - $watermark_y) * $k, -$watermark_x * $k, -($h - $watermark_y) * $k));

                    $this->SetXY($watermark_x_pos, $watermark_y_pos);
                    $this->Cell($w - 20, 50, $watermark, "", 2, "C", 0);

                    $this->_out('Q');
                    $this->SetXY($savx, $savy);
                    $this->SetAlpha(1);
                }

                $this->Output($filePath, $output);
            }
        }

        return $errors;
    }

    public function insertSignatureImage($srcFile, $base64_image, $destFile = null, $params = array(), $extra_texts = array())
    {
        $params = BimpTools::overrideArray(array(
                    'x_pos'  => 146,
                    'page'   => null,
                    'width'  => null,
                    'height' => null,
                    'type'   => 'png',
                    'output' => 'F'
                        ), $params);

        $errors = array();

        if (file_exists($srcFile)) {
            if (is_null($destFile)) {
                $destFile = $srcFile;
            }

            $pagecount = $this->setSourceFile($srcFile);

            if (is_null($params['page'])) {
                $params['page'] = $pagecount;
            }

            for ($i = 0; $i < $pagecount; $i++) {
                $this->AddPage();
                $tplidx = $this->importPage($i + 1);
                $this->useTemplate($tplidx);

                if ($params['page'] == ($i + 1)) {
                    $info = getimagesize($base64_image);

                    if ((int) $info[0] && (int) $info[1]) {
                        if (is_null($params['width']) && (int) $params['height']) {
                            $params['width'] = $params['height'] * ($info[0] / $info[1]);
                        } elseif (is_null($params['height']) && (int) $params['width']) {
                            $params['height'] = $params['width'] * ($info[1] / $info[0]);
                        } else {
                            $params['width'] = $info[0];
                            $params['height'] = $info[1];
                        }
                    }


                    $this->Image($base64_image, $params['x_pos'], $params['y_pos'], $params['width'], $params['height'], $params['type']);

                    if (!empty($extra_texts)) {
                        $this->SetFont('Arial', '', 7);

                        foreach ($extra_texts as $key => $value) {
                            if ($value) {
                                $this->SetXY((int) $params['x_pos'] + (int) BimpTools::getArrayValueFromPath($params, $key . '_x_offset', 0), (int) $params['y_pos'] + (int) BimpTools::getArrayValueFromPath($params, $key . '_y_offset', 0));
                                $this->MultiCell(BimpTools::getArrayValueFromPath($params, $key . '_width', 50), 3, $value, 0, 'L');
                            }
                        }
                    }
                }
            }

            $this->Output($destFile, $params['output']);
        } else {
            $errors[] = 'fichier "' . $srcFile . '" inexistant';
        }

        return $errors;
    }

    // Gestion de la transparence: 
    // alpha: real value from 0 (transparent) to 1 (opaque)
    // bm:    blend mode, one of the following:
    //          Normal, Multiply, Screen, Overlay, Darken, Lighten, ColorDodge, ColorBurn,
    //          HardLight, SoftLight, Difference, Exclusion, Hue, Saturation, Color, Luminosity

    function SetAlpha($alpha, $bm = 'Normal')
    {
        // set alpha for stroking (CA) and non-stroking (ca) operations
        $gs = $this->AddExtGState(array('ca' => $alpha, 'CA' => $alpha, 'BM' => '/' . $bm));
        $this->SetExtGState($gs);
    }

    function AddExtGState($parms)
    {
        $n = count($this->extgstates) + 1;
        $this->extgstates[$n]['parms'] = $parms;
        return $n;
    }

    function SetExtGState($gs)
    {
        $this->_out(sprintf('/GS%d gs', $gs));
    }

    function _enddoc()
    {
        if (!empty($this->extgstates) && $this->PDFVersion < '1.4')
            $this->PDFVersion = '1.4';
        parent::_enddoc();
    }

    function _putextgstates()
    {
        for ($i = 1; $i <= count($this->extgstates); $i++) {
            $this->_newobj();
            $this->extgstates[$i]['n'] = $this->n;
            $this->_put('<</Type /ExtGState');
            $parms = $this->extgstates[$i]['parms'];
            $this->_put(sprintf('/ca %.3F', $parms['ca']));
            $this->_put(sprintf('/CA %.3F', $parms['CA']));
            $this->_put('/BM ' . $parms['BM']);
            $this->_put('>>');
            $this->_put('endobj');
        }
    }

    function _putresourcedict()
    {
        parent::_putresourcedict();
        $this->_put('/ExtGState <<');
        foreach ($this->extgstates as $k => $extgstate)
            $this->_put('/GS' . $k . ' ' . $extgstate['n'] . ' 0 R');
        $this->_put('>>');
    }

    function _putresources()
    {
        $this->_putextgstates();
        parent::_putresources();
    }
}
