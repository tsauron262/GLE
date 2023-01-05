<?php

require_once DOL_DOCUMENT_ROOT . "/synopsistools/class/importExport/importCat.class.php";

class importBl extends import8sens {
    public $tabCommande = array();

    public function __construct($db) {
        $this->mode = 2;
        parent::__construct($db);
        $this->path .= "bl/";
        $this->sepCollone = "	";
    }

    function traiteLn($ln) {
        $this->tabResult["total"] ++;
        
        $ref = "";
        $newLines = array();
        foreach($ln['lignes'] as $ln2){
            if($ln2['PlvPPlvCodePcv'] != "")
                $ref = $ln2['PlvPPlvCodePcv'];
            if($ln2['PlvQteUV'] > 0 || BimpTools::stringToFloat($ln2['PlvQteUV']) < 0)
                $newLines[] = $ln2;
        }
        
        if($ref != "" && count($newLines) > 0){
            if(isset($this->tabCommande[$ref]['lignes'])){
                foreach($newLines as $lnT)
                $this->tabCommande[$ref]['lignes'][] = $lnT;
            }
            else{
                $ln['lignes'] = $newLines;
                $this->tabCommande[$ref] = $ln;
            }
//            echo "<pre>";print_r($this->tabCommande[$ref]);
        }
    }
    
    function go() {
        parent::go();
        
        global $tempDataBl;
        $tempDataBl= $this->tabCommande;
    }
    
}