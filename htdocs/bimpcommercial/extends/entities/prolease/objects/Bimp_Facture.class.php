<?php
require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/Bimp_Facture.class.php';

class Bimp_Facture_ExtEntity extends Bimp_Facture
{
    public static $types_vente = array(
        null      => null,
        1      => 'Contrat de location',
        2      => 'Indemnité dépassement contrat ',
        3      => 'Cession final du produit',
        10     => 'Autre'
    );
    
    public function isFieldEditable($field, $force_edit = false) {
        if($field == 'type_vente'/* && !$this->getData('exported')*/)
            return 1;
        return parent::isFieldEditable($field, $force_edit);
    }
    
    
    public function getProdWithFactureType($line = null){
//        print_r($line);
        if(stripos($line->desc, 'AppleCare') != false && $line->tva_tx == 0){
            return 15;
        }
        switch($this->getData('type_vente')){
            case 1:
                return 6;
                break;
            case 2:
                return 9;
                break;
            case 3:
                return 12;
                break;
        }
    }
}