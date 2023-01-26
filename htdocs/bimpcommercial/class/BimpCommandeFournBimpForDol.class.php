<?php

require_once(DOL_DOCUMENT_ROOT.'/bimpcommercial/extends/entities/bimp/objects/Bimp_CommandeFourn.class.php');

class BimpCommandeFournBimpForDol extends Bimp_CommandeFourn_ExtEntity{
    public function __construct($db) {
        
        require_once __DIR__ . '/../../bimpcore/Bimp_Lib.php';
        
        return parent::__construct('bimpcommercial', 'Bimp_Commande');
    }
    
    public function cronVerifMajLdlc(){
        $msg = '';
        $errors = $this->verifMajLdlc($msg);
        $this->output = $msg;
        if(count($errors))
            $msg .= '<br/>Erreurs : <br/>'.implode('<br/>', $errors);
        return 0;
    }
}
