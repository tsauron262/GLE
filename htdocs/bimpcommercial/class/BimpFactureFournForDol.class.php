<?php
require_once(DOL_DOCUMENT_ROOT.'/bimpcommercial/objects/Bimp_FactureFourn.class.php');

class BimpFactureFournForDol extends Bimp_FactureFourn{
    public function __construct($db) {
        
        require_once __DIR__ . '/../../bimpcore/Bimp_Lib.php';
        
        return parent::__construct('bimpcommercial', 'Bimp_FactureFourn');
    }
}
