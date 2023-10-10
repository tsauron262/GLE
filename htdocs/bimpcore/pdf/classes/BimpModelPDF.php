<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

require_once __DIR__ . '/BimpPDF.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once __DIR__ . '/BimpPDF_AmountsTable.php';

Abstract class BimpModelPDF
{
    # Constantes: 

    public static $type = '';
    public static $tpl_dir = DOL_DOCUMENT_ROOT . '/bimpcore/pdf/templates/';
    public static $use_cgv = false;

    # Membres: 
    public $langs;
    public $db;
    protected $pdf = null;
    public static $html_purifier = null;

    # Objets liés: 
    public $object = null;
    public $fromCompany = null; // En-tête
    public $footerCompany = null; // Pied de page
    public $object_conf = null;

    # Contenu:
    public $text = '';
    public $result = array();
    public $header_vars = array();
    public $footer_vars = array();
    public $header = null;
    public $footer = null;
    public $watermark = '';
    public $concat_files = array();

    # Paramètres: 
    public $add_header = true;
    public $add_footer = true;
    public $typeObject = '';
    public $primary = '000000';
    public $maxLogoWidth = 120; // px
    public $maxLogoHeight = 60; // px
    public $prefName = "";

    # Données:
    private $isInit = false;
    public $errors = array();

    public function __construct($db, $orientation = 'P', $format = 'A4')
    {
        unset($_SERVER['DOCUMENT_ROOT']);
        if (!defined('BIMP_LIB')) {
            require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
        }

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
        if (!$this->isInit) {
            $this->init(null);
        }

        if (count($this->errors)) {
            setEventMessages('Probléme génération PDF', $this->errors, 'errors');
            return 0;
        }

        if ($this->add_header) {
            if (is_null($this->header)) {
                if (file_exists(static::$tpl_dir . '/' . static::$type . '/header.html')) {
                    $this->header = $this->renderTemplate(static::$tpl_dir . '/' . static::$type . '/header.html', $this->header_vars);
                } else {
                    $this->header = $this->renderTemplate(static::$tpl_dir . '/header.html', $this->header_vars);
                }
            }

            $this->pdf->createHeader($this->header);
        }

        if ($this->add_footer) {
            if (is_null($this->footer)) {
                if (file_exists(static::$tpl_dir . '/' . static::$type . '/footer.html')) {
                    $this->footer = $this->renderTemplate(static::$tpl_dir . '/' . static::$type . '/footer.html', $this->footer_vars);
                } else {
                    $this->footer = $this->renderTemplate(static::$tpl_dir . '/footer.html', $this->footer_vars);
                }
            }

            $this->pdf->createFooter($this->footer);
        }

        $this->pdf->newPage();
        $this->renderContent();

        if (!empty($this->concat_files)) {
            $this->pdf->extra_concat_files = $this->concat_files;
        }

        return $this->pdf->render($file_name, $display, $display_only, $this->watermark, $this->errors);
    }

    protected function renderContent()
    {
        if ($this->text) {
            $this->writeContent($this->text);
        }
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

    public function writeFullBlock($html, &$page = 0, &$yPos = 0)
    {
        $pdf = clone $this->pdf;

        $page_num = $this->pdf->getPage();

        $page = $page_num;
        $yPos = $this->pdf->getY();

        $this->writeContent($html);
        $cur_page = (int) $this->pdf->getPage();

        if ($cur_page > $page_num) {
            unset($this->pdf);
            $this->pdf = $pdf;
            $this->pdf->newPage();

            $page++;
            $yPos = $this->pdf->getY();

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
        $html = '<br/><span style="font-size: 11px; color: #' . $this->primary . ';">' . $this->fromCompany->name . '</span><br/>';
        $html .= '<span style="font-size: 7px">' . $this->fromCompany->address . '<br/>' . $this->fromCompany->zip . ' ' . $this->fromCompany->town;
        if ($this->fromCompany->phone) {
            $html .= '<br/>Tél. : ' . $this->fromCompany->phone;
        }
        $html .= '</span>';
        $html .= '<span style="color: #' . $this->primary . '; font-size: 7px;"><br/>';
        if ($this->fromCompany->url) {
            $html .= $this->fromCompany->url . ($this->fromCompany->email ? ((strlen($this->fromCompany->url) > 30)? '<br/>' : ' - ') : '');
        }
        if ($this->fromCompany->email) {
            $html .= $this->fromCompany->email;
        }
        $html .= '</span>';

        if (BimpCore::isEntity('bimp')) {
            global $mysoc;
            if ($this->fromCompany->zip != $mysoc->zip || $this->fromCompany->town != $mysoc->town) {
                $html .= '<span style="font-size: 6px; font-style: italic; color: #5A5959"><br/>';
                $html .= 'NB:  les règlements ne doivent être envoyés<br/>qu\'à notre siège social : <b>OLYS 2 rue des Erables</b><br/>';
                $html .= '<b>CS 21055 69760 LIMONEST</b>';
                $html .= '</span>';
            }
        }
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

        $html = '';
        $usedetailedbban = $account->useDetailedBBAN();

        if (!$only_number) {
            $html .= '<span style="font-style: italic">' . $this->langs->transnoentities('PaymentByTransferOnThisBankAccount') . ':</span><br/><br/>';
        }

        $date_new_account = BimpCore::getConf('new_bank_account_date', '', 'bimpcommercial');

        if ($date_new_account) {
            $html .= '<span style="color: #A00000; font-weight: bold">';
            $html .= 'Attention: NOS COORDONNEES BANCAIRES depuis le ' . date('d / m / Y', strtotime($date_new_account));
            $html .= '</span><br/><br/>';
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
                            $content = $account->code_guichet;
                            break;
                        case 'BankAccountNumber':
                            $content = $account->number;
                            break;
                        case 'BankAccountNumberKey':
                            $content = $account->cle_rib;
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
        return $this->prefName . $name . '.pdf';
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

    public function calculeWidthHeightLogo($width, $height, $maxWidth, $maxHeight)
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

    public function replaceHtmlStyles($html)
    {
        for ($i = 6; $i < 30; $i++) {
            $html = str_replace('font-size: ' . $i . 'px', 'font-size: ' . ($i - 3) . 'px', $html);
            $html = str_replace('font-size:' . $i . 'px', 'font-size:' . ($i - 3) . 'px', $html);
        }

        return $html;
    }

    public static function getHtmlPurifier()
    {
        if (is_null(self::$html_purifier)) {
            BimpCore::LoadHtmlPurifier();

            $config = HTMLPurifier_Config::createDefault();
            $allowed_tags = 'a,b,blockquote,br,dd,del,div,dl,dt,em,font,h1,h2,h3,h4,h5,h6,hr,i,img,li,ol,p,pre,small,span,strong,sub,sup,table,td,th,thead,tr,tt,u,ul';
            $config->set('HTML.AllowedElements', $allowed_tags);

            $root = '';

            if (defined('PATH_TMP') && PATH_TMP) {
                $root = PATH_TMP;
                $path = '/htmlpurifier/serialiser';
            } else {
                $root = DOL_DATA_ROOT;
                $path = '/bimpcore/htmlpurifier/serialiser';
            }

            if (!is_dir($root . $path)) {
                BimpTools::makeDirectories($path, $root);
            }

            $config->set('Cache.SerializerPath', $root . $path);

            self::$html_purifier = new HTMLPurifier($config);
        }

        return self::$html_purifier;
    }

    public static function cleanHtml($html)
    {
        if ((int) BimpCore::getConf('pdf_use_html_purifier')) {
//            echo 'AVANT: <br/>'; 
//            echo htmlentities($html);

            $purifier = self::getHtmlPurifier();
            $html = $purifier->purify($html);

//            echo '<br/><br/>APRES: <br/>';
//            echo htmlentities($html);
//            exit;
        } else {
            // Envisager d'autres méthodes... 
        }

        return $html;
    }
}
