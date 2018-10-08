<?php

require_once __DIR__ . '/BimpDocumentPDF.php';

class SavPcPDF extends BimpDocumentPDF
{

    public static $type = 'sav_pc';
    public $sav = null;
    public $mode = "normal";

    public function __construct($db)
    {
        parent::__construct($db);

        $this->langs->load("bills");
        $this->langs->load("propal");
        $this->langs->load("products");

        $this->typeObject = 'sav';
    }
}