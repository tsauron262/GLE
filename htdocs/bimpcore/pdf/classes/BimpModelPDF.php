<?php

require_once __DIR__ . '/BimpPDF.php';

Abstract class BimpModelPDF {

    protected $pdf = null;
    public $tpl_dir = DOL_DOCUMENT_ROOT . '/bimpcore/pdf/templates/invoice/';
    public $header_vars = array();
    public $footer_vars = array(
        'footer_line_1' => 'Société anonyme à conseil d administration - Capital de 1085372€ - N° de déclaration d’activité: 320 387 483 00060',
        'footer_line_2' => 'NAF: 4741Z - RCS: Lyon B 320 387 483 - TVA intracommunautaire: FR 34 320387483'
    );
    public $prefName = "";
    public $text = "";
    public $object;
    private $isInit = false;

    public function __construct() {
        global $conf, $mysoc;
        $this->header_vars = array(
            'logo_img' => $conf->mycompany->dir_output . '/logos/' . $mysoc->logo,
            'logo_width' => '120'
        );



        $this->pdf = new BimpPDF();
    }

    abstract function initData();

    function init($object) {
        $this->object = $object;

        if (is_file($this->tpl_dir . '/style.css')) {
            $css = $this->pdf->renderTemplate($this->tpl_dir . '/style.css', array());
            $this->text .= "<style>" . $css . "</style>";
        }

        $this->initData();
        $this->isInit = true;
    }

    public function render($file_name, $display) {
        if (!$this->isInit)
            $this->init(null);

        $header = $this->pdf->renderTemplate($this->tpl_dir . '/header.html', $this->header_vars);
        $this->pdf->createHeader($header);

        $footer = $this->pdf->renderTemplate($this->tpl_dir . '/footer.html', $this->footer_vars);
        $this->pdf->createFooter($footer);



        $this->printContent();

        return $this->pdf->render($file_name, $display);
    }

    public function printContent() {
        $this->pdf->writePage($this->text);
    }

    function getPath() {
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

    function getName() {
        $name = "doc_" . $this->typeObject;
        if (isset($this->object)) {
            if (isset($this->object->ref))
                $name = dol_sanitizeFileName($this->object->ref);
            elseif (isset($this->object->id))
                $name .= "_" . dol_sanitizeFileName($this->object->id);
        }
        return $this->prefName . $name;
    }

    /**
     *  Function to build pdf onto disk
     *
     *  @param		Object		$object				Object to generate
     *  @param		Translate	$outputlangs		Lang output object
     *  @param		string		$srctemplatepath	Full path of source filename for generator using a template file
     *  @param		int			$hidedetails		Do not show line details
     *  @param		int			$hidedesc			Do not show desc
     *  @param		int			$hideref			Do not show ref
     *  @return     int             				1=OK, 0=KO
     */
    function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0) {
        $this->init($object);
        $file = $this->getPath() . $this->getName();

        $this->render($file, false);

        return 1;
    }

}
