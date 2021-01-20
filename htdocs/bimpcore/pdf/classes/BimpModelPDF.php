<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

require_once __DIR__ . '/BimpPDF.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once __DIR__ . '/BimpPDF_AmountsTable.php';

Abstract class BimpModelPDF
{

    public $db;
    protected $pdf = null;
    public static $tpl_dir = DOL_DOCUMENT_ROOT . '/bimpcore/pdf/templates/';
    public static $use_cgv = false;
    public $result = array();
    public static $type = '';
    public $header_vars = array();
    public $footer_vars = array();
    public $header = null;
    public $footer = null;
    public $prefName = "";
    public $object;
    public $text = '';
    public $fromCompany = null; // En-tête
    public $footerCompany = null; // Pied de page
    private $isInit = false;
    public $errors = array();
    public $langs;
    public $typeObject = '';
    public $primary = '000000';
    public $watermark = '';

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

        $this->primary = BimpCore::getParam('pdf/primary', '000000');

        $this->pdf = new BimpPDF($orientation, $format);
        $this->pdf->addCgvPages = static::$use_cgv;

        $this->fromCompany = clone $mysoc; // Sender (en-tête)
        $this->footerCompany = clone $mysoc; // Pied de page. 

        if (empty($this->fromCompany->country_code)) {
            $this->fromCompany->country_code = substr($langs->defaultlang, -2);
        }

        if (!defined('BIMP_LIB')) {
            require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
        }
    }

    // Initialisation:

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
        BimpPDF::$addCgvPagesType = $object->array_options['options_type'];
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
        
        return $this->pdf->render($file_name, $display, $display_only, $this->watermark);
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

    public function renderFullBlock($method)
    {
        if (!method_exists($this, $method)) {
            return;
        }

        $pdf = clone $this->pdf;

        $page_num = $this->pdf->getPage();
        $this->{$method}();
        $cur_page = (int) $this->pdf->getPage();

        if ($cur_page > $page_num) {
            unset($this->pdf);
            $this->pdf = $pdf;
            $this->pdf->newPage();
            $this->{$method}();
        } else {
            unset($pdf);
        }
    }

    public function writeFullBlock($html)
    {
        $pdf = clone $this->pdf;

        $page_num = $this->pdf->getPage();
        $this->writeContent($html);
        $cur_page = (int) $this->pdf->getPage();

        if ($cur_page > $page_num) {
            unset($this->pdf);
            $this->pdf = $pdf;
            $this->pdf->newPage();
            $this->writeContent($html);
        } else {
            unset($pdf);
        }
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

    public function renderTable($arrayHead, $arrayData)
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

    public function getSenderInfosHtml()
    {
        $html = '<br/><span style="font-size: 15px; color: #' . $this->primary . ';">' . $this->fromCompany->name . '</span><br/>';
        $html .= '<span style="font-size: 8px">' . $this->fromCompany->address . '<br/>' . $this->fromCompany->zip . ' ' . $this->fromCompany->town . '<br/>';
        if ($this->fromCompany->phone) {
            $html .= 'Tél. : ' . $this->fromCompany->phone . '<br/>';
        }
        $html .= '</span>';
        $html .= '<span style="color: #' . $this->primary . '; font-size: 7px;">';
        if ($this->fromCompany->url) {
            $html .= $this->fromCompany->url . ($this->fromCompany->email ? ' - ' : '');
        }
        if ($this->fromCompany->email) {
            $html .= $this->fromCompany->email;
        }
        $html .= '</span>';
        return $html;
    }

    public function getEntrepotAddressHtml($entrepot)
    {
        $html = '';

        if (BimpObject::objectLoaded($entrepot)) {
            $html .= '<span style="font-size: 10px; font-weight: bold;">';
            $html .= $entrepot->libelle . ' - ' . $entrepot->lieu;
            $html .= '</span><br/>';

            $html .= '<span style="font-size: 9px;">';
            if ((string) $entrepot->address) {
                $html .= $entrepot->address . '<br/>';
            }
            if ((string) $entrepot->zip) {
                $html .= $entrepot->zip . ' ';
            }

            if ((string) $entrepot->town) {
                $html .= $entrepot->town;
            }

            $html .= '</span>';
        }

        return $html;
    }

    public function getBankHtml($account, $only_number = false)
    {
        global $conf;

        require_once DOL_DOCUMENT_ROOT . '/core/class/html.formbank.class.php';

        $this->langs->load('banks');

        $bickey = "BICNumber";

        if ($account->getCountryCode() == 'IN') {
            $bickey = "SWIFT";
        }

        $usedetailedbban = $account->useDetailedBBAN();

        if (!$only_number) {
            $html .= '<span style="font-style: italic">' . $this->langs->transnoentities('PaymentByTransferOnThisBankAccount') . ':</span><br/><br/>';
        }

        if ($usedetailedbban) {
            $html .= '<strong>' . $this->langs->transnoentities("Bank") . '</strong>: ';
            $html .= $this->langs->convToOutputCharset($account->bank) . '<br/>';

            if (empty($conf->global->PDF_BANK_HIDE_NUMBER_SHOW_ONLY_BICIBAN)) {
                foreach ($account->getFieldsToShow() as $val) {
                    $content = '';

                    switch ($val) {
                        case 'BankCode':
                            $content = $account->code_banque;
                            break;
                        case 'DeskCode':
                            $content = $account->code_banque;
                            break;
                        case 'BankAccountNumber':
                            $content = $account->code_banque;
                            break;
                        case 'BankAccountNumberKey':
                            $content = $account->code_banque;
                            break;
                    }

                    if ($content) {
                        $html .= '<strong>' . $this->langs->transnoentities($val) . '</strong>: ';
                        $html .= $this->langs->convToOutputCharset($content);
                        $html .= '<br/>';
                    }
                }
            }
        } else {
            $html .= '<strong>' . $this->langs->transnoentities('Bank') . '</strong>: ' . $this->langs->convToOutputCharset($account->bank) . '<br/>';
            $html .= '<strong>' . $this->langs->transnoentities('BankAccountNumber') . '</strong>: ' . $this->langs->convToOutputCharset($account->number) . '<br/>';
        }

        if (!$only_number && !empty($account->domiciliation)) {
            $html .= '<strong>' . $this->langs->transnoentities('Residence') . '</strong>: ' . $this->langs->convToOutputCharset($account->domiciliation) . '<br/>';
        }

        if (!empty($account->proprio)) {
            $html .= '<strong>' . $this->langs->transnoentities('BankAccountOwner') . '</strong>: ' . $this->langs->convToOutputCharset($account->proprio) . '<br/>';
        }

        $ibankey = FormBank::getIBANLabel($account);

        if (!empty($account->iban)) {
            $ibanDisplay_temp = str_replace(' ', '', $this->langs->convToOutputCharset($account->iban));
            $ibanDisplay = "";

            $nbIbanDisplay_temp = dol_strlen($ibanDisplay_temp);
            for ($i = 0; $i < $nbIbanDisplay_temp; $i++) {
                $ibanDisplay .= $ibanDisplay_temp[$i];
                if ($i % 4 == 3 && $i > 0)
                    $ibanDisplay .= " ";
            }

            $html .= '<strong>' . $this->langs->transnoentities($ibankey) . '</strong>: ' . $ibanDisplay . '<br/>';
        }

        if (!empty($account->bic)) {
            $html .= '<strong>' . $this->langs->transnoentities($bickey) . '</strong>: ' . $this->langs->convToOutputCharset($account->bic) . '<br/>';
        }

        return $html;
    }

    // Gestion du fichier de destination:

    public function getFilePath()
    {
        global $conf;

        $path = DOL_DATA_ROOT . "/divers/";

        $nObj = $this->typeObject;
        if (is_object($this->object_conf)) {
            $objConf = $this->object_conf;
        } elseif ($nObj != "") {
            if (isset($conf->$nObj))
                $objConf = $conf->$nObj;
            else
                $path .= $nObj . "/";
        }
        if (is_object($objConf)) {
            if (isset($objConf->dir_output))
                $path = $objConf->dir_output . "/";
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

    // Tools:

    public function calculeWidthHieghtLogo($width, $height, $maxWidth, $maxHeight)
    {
        if ($width > $maxWidth) {
            $height = round(($maxWidth / $width) * $height);
            $width = $maxWidth;
        }

        if ($height > $maxHeight) {
            $width = round(($maxHeight / $height) * $width);
            $height = $maxHeight;
        }
        return array($width, $height);
    }
}
