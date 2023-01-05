<?php
require_once(DOL_DOCUMENT_ROOT.'/bimpcommercial/objects/Bimp_Facture.class.php');

class BimpFactureForDol extends Bimp_Facture{
    public function __construct($db) {
        
        require_once __DIR__ . '/../../bimpcore/Bimp_Lib.php';
        
        return parent::__construct('bimpcommercial', 'Bimp_Facture');
    }
    
    public function createTaskChorus(){
        $list = BimpObject::getBimpObjectObjects('bimpcommercial', 'Bimp_Facture', array('chorus_status' => array(0,1)));
        foreach($list as $fact){
            if(in_array($fact->getData('chorus_status'), array(0,1))){
                BimpCore::addAutoTask('facturation', 'Facture en attente d\'export Chorus '.$fact->getRef(), 'La facture '.$fact->getLink(array('modal_view'=>'', 'card'=> 0)).' est en attente d\'export chorus', "facture_extrafields:fk_object=" . $fact->id . ' AND chorus_status > 1');
            }
        }
        return 0;
    }
}
