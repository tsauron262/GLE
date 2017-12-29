<?php

require_once DOL_DOCUMENT_ROOT . '/includes/tecnickcom/tcpdf/tcpdf.php';

class BimpPDF extends TCPDF
{

    protected $header = '';
    protected $footer = '';
    protected $pagination = '';

    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4')
    {
        $this->pagination = '<p style="text-align: right; vertical-align: text-top;"> {:pnp:} / {:ptp:} </p>';
        parent::__construct($orientation, $unit, $format);
    }

    public function createHeader($header)
    {
        $this->header = $header;
    }

    public function createFooter($footer)
    {
        $this->footer = $footer;
    }

    public function createPagination($pagination)
    {
        $this->pagination = $pagination;
    }

    public function Header()
    {
        $this->writeHTML($this->header);
    }

    public function Footer()
    {
        $this->writeHTML($this->footer);
        $this->writeHTML($this->pagination);
    }

    public function writePage($content)
    {
        $this->SetHeaderMargin(5);
        $this->SetFooterMargin(21);
        $this->setMargins(10, 40, 10);
        $this->AddPage();
        $this->writeHTML($content, true, false, true, false, '');
    }

    public function render($filename, $display = true)
    {
        $this->lastPage();
        
        
        if(stripos($filename, ".pdf") === false)
                $filename .= ".pdf";
        
        $tabT = explode("/", $filename);
        $nomPure = $tabT[count($tabT)-1];
        

        if ($display === true) {
            $display = 'I';
        } elseif ($display === false) {
            $display = 'F';
        } 
        if ($display == 'F') {// on enregistre sur server
            $output = 'F';
            
            $folder = str_replace($nomPure, "", $filename);
            if(!is_dir($folder))
                if(!mkdir($folder))
                    die("Le dossier ".$folder." n'existe pas est ne pe etre créer");
            
        } else{
            if ($display == 'DS') {//On enregistre et on download
                $this->Output($filename, 'F');
                $display = 'D';
            }elseif ($display == 'IS') {//On enregistre et on affiche
                $this->Output($filename, 'F');
                $display = 'I';
            }
            
            
            $filename = $nomPure;
            if ($display == 'D') {//On download
                $output = 'D';
            } elseif ($display == 'S') {//je sait pas
                $output = 'S';
            } else {               //On affiche
                $output = 'I';
                $filename = $nomPure;
            }
        }

        return $this->Output($filename, $output);
    }

    public function renderTemplate($file, $vars = array())
    {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            foreach ($vars as $name => $value) {
                $content = str_replace('{' . $name . '}', $value, $content);
            }
            return $content;
        } else {
            $this->Error('Template file "' . $file . '" is missing');
        }
        return '';
    }
}
