<?php
require_once(DOL_DOCUMENT_ROOT.'/bimpcommercial/objects/Bimp_Facture.class.php');

class BimpFactureForDol extends Bimp_Facture{
    public function __construct($db) {
        
        
        return parent::__construct('bimpcommercial', 'Bimp_Facture');
    }
}
