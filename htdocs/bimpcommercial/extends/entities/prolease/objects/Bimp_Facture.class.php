<?php
require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/Bimp_Facture.class.php';

class Bimp_Facture_ExtEntity extends Bimp_Facture
{
    public static $types_vente = array(
        1      => 'Cession',
        2      => 'Union Européenne',
        3      => 'Hors UE'
    );
    
    public function isFieldEditable($field, $force_edit = false) {
        if($field == 'type_vente')
            return 1;
        return parent::isFieldEditable($field, $force_edit);
    }
    
    
    public function getProdWithFactureType(){
        switch($this->getData('type_vente')){
            case 1:
//                return 
                break;
            case 2:
                
                break;
        }
    }
}