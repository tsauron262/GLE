<?php

require_once __DIR__ . '/BimpCommDocumentPDF.php';

class SavPcPDF extends BimpCommDocumentPDF
{

    public static $type = 'sav_pc';
    public $sav = null;
    public $mode = "normal";
    public $signature_bloc = true;

    public function __construct($db)
    {
        parent::__construct($db);

        $this->langs->load("bills");
        $this->langs->load("propal");
        $this->langs->load("products");

        $this->typeObject = 'sav';

        $primary = BimpCore::getParam('pdf/primary_sav', '');

        if ($primary) {
            $this->primary = $primary;
        }
    }
}
