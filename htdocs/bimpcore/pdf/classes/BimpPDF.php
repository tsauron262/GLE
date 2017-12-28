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

        if ($display === true) {
            $output = 'D';
        } elseif ($display === false) {
            $output = 'S';
        } elseif ($display == 'D') {
            $output = 'D';
        } elseif ($display == 'S') {
            $output = 'S';
        } elseif ($display == 'F') {
            $output = 'F';
        } else {
            $output = 'I';
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
