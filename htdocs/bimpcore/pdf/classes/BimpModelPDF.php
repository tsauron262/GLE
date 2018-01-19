<?php

require_once __DIR__ . '/BimpPDF.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once __DIR__.'/BimpPDF_AmountsTable.php';

Abstract class BimpModelPDF
{

    protected $pdf = null;
    public static $tpl_dir = DOL_DOCUMENT_ROOT . '/bimpcore/pdf/templates/';
    public static $type = '';
    public $header_vars = array();
    public $footer_vars = array();
    public $header = null;
    public $footer = null;
    public $prefName = "";
    public $object;
    public $text = '';
    public $fromCompany = null;
    private $isInit = false;
    public $errors = array();
    public $langs;

    public function __construct($orientation = 'P', $format = 'A4')
    {
        global $conf, $mysoc, $langs;

        $this->langs = $langs;

        $this->langs->load("main");
        $this->langs->load("dict");
        $this->langs->load("companies");

        $this->pdf = new BimpPDF($orientation, $format);
        $this->fromCompany = $mysoc;
        if (empty($this->fromCompany->country_code)) {
            $this->fromCompany->country_code = substr($langs->defaultlang, -2);
        }
    }

    // Initialisation

    protected function initHeader()
    {
        global $conf;

        $this->header_vars = array(
            'logo_img'     => $conf->mycompany->dir_output . '/logos/' . $this->fromCompany->logo,
            'logo_width'   => '120',
            'header_right' => ''
        );
    }

    protected function initfooter()
    {
        $line1 = '';
        $line2 = '';

        global $db, $conf;

        if ($this->fromCompany->forme_juridique_code) {
            $line1 .= $this->langs->convToOutputCharset(getFormeJuridiqueLabel($this->fromCompany->forme_juridique_code));
        }

        if ($this->fromCompany->capital) {
            $captital = price2num($this->fromCompany->capital);
            if (is_numeric($captital) && $captital > 0) {
                $line1.=($line1 ? " - " : "") . $this->langs->transnoentities("CapitalOf", price($captital, 0, $this->langs, 0, 0, 0, $conf->currency));
            } else {
                $line1.=($line1 ? " - " : "") . $this->langs->transnoentities("CapitalOf", $captital, $this->langs);
            }
        }

        if ($this->fromCompany->idprof1 && ($this->fromCompany->country_code != 'FR' || !$this->fromCompany->idprof2)) {
            $field = $this->langs->transcountrynoentities("ProfId1", $this->fromCompany->country_code);
            if (preg_match('/\((.*)\)/i', $field, $reg)) {
                $field = $reg[1];
            }
            $line1 .= ($line1 ? " - " : "") . $field . ": " . $this->langs->convToOutputCharset($this->fromCompany->idprof1);
        }

        if ($this->fromCompany->idprof2) {
            $field = $this->langs->transcountrynoentities("ProfId2", $this->fromCompany->country_code);
            if (preg_match('/\((.*)\)/i', $field, $reg)) {
                $field = $reg[1];
            }
            $line1 .= ($line1 ? " - " : "") . $field . ": " . $this->langs->convToOutputCharset($this->fromCompany->idprof2);
        }

        if ($this->fromCompany->idprof3) {
            $field = $this->langs->transcountrynoentities("ProfId3", $this->fromCompany->country_code);
            if (preg_match('/\((.*)\)/i', $field, $reg))
                $field = $reg[1];
            $line2 .= ($line2 ? " - " : "") . $field . ": " . $this->langs->convToOutputCharset($this->fromCompany->idprof3);
        }

        if ($this->fromCompany->idprof4) {
            $field = $this->langs->transcountrynoentities("ProfId4", $this->fromCompany->country_code);
            if (preg_match('/\((.*)\)/i', $field, $reg)) {
                $field = $reg[1];
            }
            $line2 .= ($line2 ? " - " : "") . $field . ": " . $this->langs->convToOutputCharset($this->fromCompany->idprof4);
        }

        if ($this->fromCompany->idprof5) {
            $field = $this->langs->transcountrynoentities("ProfId5", $this->fromCompany->country_code);
            if (preg_match('/\((.*)\)/i', $field, $reg)) {
                $field = $reg[1];
            }
            $line2 .= ($line2 ? " - " : "") . $field . ": " . $this->langs->convToOutputCharset($this->fromCompany->idprof5);
        }

        if ($this->fromCompany->idprof6) {
            $field = $this->langs->transcountrynoentities("ProfId6", $this->fromCompany->country_code);
            if (preg_match('/\((.*)\)/i', $field, $reg))
                $field = $reg[1];
            $line2 .= ($line2 ? " - " : "") . $field . ": " . $this->langs->convToOutputCharset($this->fromCompany->idprof6);
        }
        // IntraCommunautary VAT
        if ($this->fromCompany->tva_intra != '') {
            $line2 .= ($line2 ? " - " : "") . $this->langs->transnoentities("VATIntraShort") . ": " . $this->langs->convToOutputCharset($this->fromCompany->tva_intra);
        }

        $this->footer_vars = array(
            'footer_line_1' => $line1,
            'footer_line_2' => $line2,
        );
    }

    protected function initData() {}

    function init($object)
    {
        $this->object = $object;
        $this->initHeader();
        $this->initfooter();
        $this->initData();
        $this->isInit = true;
    }

    // Construction du document:

    public function render($file_name, $display)
    {
        if (!$this->isInit)
            $this->init(null);

        if (is_null($this->header)) {
            if (file_exists(self::$tpl_dir . '/' . static::$type . '/header.html')) {
                $this->header = $this->pdf->renderTemplate(self::$tpl_dir . '/' . static::$type . '/header.html', $this->header_vars);
            } else {
                $this->header = $this->renderTemplate(self::$tpl_dir . '/header.html', $this->header_vars);
            }
        }
        $this->pdf->createHeader($this->header);

        if (is_null($this->footer)) {
            if (file_exists(self::$tpl_dir . '/' . static::$type . '/footer.html')) {
                $this->footer = $this->pdf->renderTemplate(self::$tpl_dir . '/' . static::$type . '/footer.html', $this->footer_vars);
            } else {
                $this->footer = $this->renderTemplate(self::$tpl_dir . '/footer.html', $this->footer_vars);
            }
        }
        $this->pdf->createFooter($this->footer);
        
        $this->pdf->newPage();

        $this->renderContent();

        return $this->pdf->render($file_name, $display);
    }

    protected function renderContent()
    {
        $this->writeContent($this->text);
    }

    public function writeContent($content)
    {
        $styles = '<style>';
        if (file_exists(self::$tpl_dir . '/styles.css')) {
            $css = $this->renderTemplate(self::$tpl_dir . '/styles.css');
            if ($css) {
                $styles .= $css;
            }
        }
        if (file_exists(self::$tpl_dir . '/' . static::$type . '/styles.css')) {
            $css = $this->renderTemplate(self::$tpl_dir . '/' . static::$type . '/styles.css');
            if ($css) {
                $styles .= $css;
            }
        }
        $styles .= '</style>' . "\n";
        
        $this->pdf->writeHTML($styles . $content, true, false, true, false, '');
    }

    // Rendus HTML: 

    public function renderTemplate($file, $vars = array())
    {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            foreach ($vars as $name => $value) {
                $content = str_replace('{' . $name . '}', $value, $content);
            }
            return $content;
        } else {
            $this->errors[] = 'Template file "' . $file . '" is missing';
        }
        return '';
    }

    function renderTable($arrayHead, $arrayData)
    {
        $arrayUtil = array();
        foreach ($arrayHead as $clef => $label) {
            foreach ($arrayData as $ligne) {
                $val = $ligne[$clef];
                if (is_array($val))
                    $val = $val[0];

                //if ($val !== null && $val !== "" && $val !== "0")
                $arrayUtil[$clef] = $label;
            }
        }

        $html = "<table><tr style='background-color: green;'>";
        foreach ($arrayUtil as $valT) {
            if (is_array($valT))
                $label = $valT[0];
            else
                $label = $valT;
            $html .= "<th>" . $label . "</th>";
        }
        $html .= "</tr>";

        foreach ($arrayData as $ligne) {
            $html .= "<tr>";
            foreach ($arrayUtil as $clef => $valT) {
                $html .= "<td>";
                if (isset($ligne[$clef])) {
                    $unit = "";
                    $val = $ligne[$clef];
                    if (is_array($valT) && isset($valT[1]) && $val != "")
                        $unit = $valT[1];
                    if (stripos($unit, "%") || $unit == "€")
                        $val = price($val);
                    $html .= $val . " " . $unit;
                }
                $html .= "</td>";
            }
            $html .= "</tr>";
        }
        $html .= "</table>";

        return $html;
    }

    public function renderAddresses($thirdparty, $contact = null)
    {
        $html = '';

        $sender_infos = pdf_build_address($this->langs, $this->fromCompany, $thirdparty);
        $sender_infos = str_replace("\n", '<br/>', $sender_infos);
        $target_infos = pdf_build_address($this->langs, $this->fromCompany, $thirdparty, $contact, !is_null($contact) ? 1 : 0, 'target');
        $target_infos = str_replace("\n", '<br/>', $target_infos);

        $html .= '<div class="section addresses_section">';
        $html .= '<table style="width: 100%" cellspacing="0" cellpadding="3px">';
        $html .= '<tr>';
        $html .= '<td style="width: 40%">' . $this->langs->transnoentities('BillFrom') . ': </td>';
        $html .= '<td style="width: 5%"></td>';
        $html .= '<td style="width: 55%">' . $this->langs->transnoentities('BillTo') . ': </td>';
        $html .= '</tr>';
        $html .= '</table>';

        $html .= '<table style="width: 100%" cellspacing="0" cellpadding="10px">';
        $html .= '<tr>';
        $html .= '<td class="sender_address" style="width: 40%">';
        $html .= '<div class="bold">' . $this->langs->convToOutputCharset($this->fromCompany->name) . '</div>';
        $html .= $sender_infos;
        $html .= '</td>';
        $html .= '<td style="width: 5%"></td>';
        $html .= '<td style="width: 55%" class="border">';
        $html .= '<div class="bold">' . pdfBuildThirdpartyName($thirdparty, $this->langs) . '</div>';
        $html .= $target_infos;
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    // Gestion du fichier de destination

    public function getFilePath()
    {
        global $conf;

        $path = DOL_DATA_ROOT . "/divers/";

        $nObj = $this->typeObject;
        if ($nObj != "") {
            if (isset($conf->$nObj) && isset($conf->$nObj->dir_output))
                $path = $conf->$nObj->dir_output . "/";
            else
                $path .= $nObj . "/";
        }

        if (isset($this->object) && isset($this->object->ref))
            $path .= dol_sanitizeFileName($this->object->ref) . "/";

        return $path;
    }

    public function getFileName()
    {
        $name = "doc_" . $this->typeObject;
        if (isset($this->object)) {
            if (isset($this->object->ref))
                $name = dol_sanitizeFileName($this->object->ref);
            elseif (isset($this->object->id))
                $name .= "_" . dol_sanitizeFileName($this->object->id);
        }
        return $this->prefName . $name;
    }

    public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
    {
        $this->init($object);
        $file = $this->getFilePath() . $this->getFileName();

        $this->render($file, false);

        return 1;
    }
}
