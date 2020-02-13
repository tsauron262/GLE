<?php
require_once(DOL_DOCUMENT_ROOT.'/bimpcommercial/objects/Bimp_Facture.class.php');

class BimpToolsForDol extends BimpTools{
    public function __construct($db) {
        
        require_once __DIR__ . '/../../bimpcore/Bimp_Lib.php';
        
//        return parent::__construct('bimpcore', 'BimpTools');
    }
}
