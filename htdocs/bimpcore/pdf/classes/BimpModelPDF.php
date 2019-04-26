<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

require_once __DIR__ . '/BimpPDF.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once __DIR__ . '/BimpPDF_AmountsTable.php';

Abstract class BimpModelPDF
{

    public $db;
    protected $pdf = null;
    public static $tpl_dir = DOL_DOCUMENT_ROOT . '/bimpcore/pdf/templates/';
    public $result = array();
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
    public $typeObject = '';

    public function __construct($db, $orientation = 'P', $format = 'A4')
    {
        global $mysoc, $langs, $conf;

        $conf->global->MAIN_MAX_DECIMALS_SHOWN = str_replace("...", "", $conf->global->MAIN_MAX_DECIMALS_SHOWN);

        $this->db = $db;
        $this->langs = $langs;

        $this->langs->load("errors");
        $this->langs->load("main");
        $this->langs->load("dict");
        $this->langs->load("companies");

        $this->pdf = new BimpPDF($orientation, $format);

        $this->fromCompany = $mysoc;
        if (empty($this->fromCompany->country_code)) {
            $this->fromCompany->country_code = substr($langs->defaultlang, -2);
        }
        if (!defined('BIMP_LIB')) {
            require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
        }
    }

    // Initialisation

    protected function initData()
    {
        
    }

    protected function initHeader()
    {
        
    }

    protected function initfooter()
    {
        
    }

    public function init($object)
    {
        $this->object = $object;
        $this->initData();
        $this->initHeader();
        $this->initfooter();
        $this->isInit = true;
    }

    // Construction du document:

    public function render($file_name, $display, $display_only = false)
    {
        if (!$this->isInit)
            $this->init(null);

        if (is_null($this->header)) {
            if (file_exists(static::$tpl_dir . '/' . static::$type . '/header.html')) {
                $this->header = $this->renderTemplate(static::$tpl_dir . '/' . static::$type . '/header.html', $this->header_vars);
            } else {
                $this->header = $this->renderTemplate(static::$tpl_dir . '/header.html', $this->header_vars);
            }
        }

        if (count($this->errors)) {
            return 0;
//            $this->displayErrors();
//            exit;
        }

        $this->pdf->createHeader($this->header);

        if (is_null($this->footer)) {
            if (file_exists(static::$tpl_dir . '/' . static::$type . '/footer.html')) {
                $this->footer = $this->renderTemplate(static::$tpl_dir . '/' . static::$type . '/footer.html', $this->footer_vars);
            } else {
                $this->footer = $this->renderTemplate(static::$tpl_dir . '/footer.html', $this->footer_vars);
            }
        }
        $this->pdf->createFooter($this->footer);

        $this->pdf->newPage();

        $this->renderContent();

        return $this->pdf->render($file_name, $display, $display_only);
    }

    protected function renderContent()
    {
        $this->writeContent($this->text);
    }

    public function writeContent($content)
    {
        $styles = '<style>';
        if (file_exists(static::$tpl_dir . '/styles.css')) {
            $css = $this->renderTemplate(static::$tpl_dir . '/styles.css');
            if ($css) {
                $styles .= $css;
            }
        }
        if (file_exists(static::$tpl_dir . '/' . static::$type . '/styles.css')) {
            $css = $this->renderTemplate(static::$tpl_dir . '/' . static::$type . '/styles.css');
            if ($css) {
                $styles .= $css;
            }
        }
        $styles .= '</style>' . "\n";

        $this->pdf->writeHTML($styles . $content, false, false, true, false, '');
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
        if (!is_dir($path))
            dol_mkdir($path);

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
        $this->result["fullpath"] = $file;
        return 1;
    }

    // Affichages erreurs: 

    public function displayErrors()
    {
        if (count($this->errors)) {
            echo count($this->errors) . ' erreur(s) détectée(s):<br/>';
            foreach ($this->errors as $error) {
                echo ' - ' . $error . '<br/>';
            }
        }
    }
}
