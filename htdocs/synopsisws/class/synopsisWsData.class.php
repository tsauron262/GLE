<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of synopsisWsData
 *
 * @author tijean
 */
abstract class synopsisWsData {

    var $limitList = 200;
    var $db;
    var $typeObj;
    var $prefixe;
    var $champClef;
    var $params;
    var $paramsArray;
    var $tabParams;
    var $tabInfoObjPhp;
    var $tabShema;
    var $user;
    var $tabTypeNoSql = array("relationN1");
    var $tabTypeInt = array("relation1N", "int", "id", "boolean");

    function __construct($db, $typeObj, $prefixe, $champClef, $params, $tabInfoObjPhp, $tabShema) {
        $this->db = $db;
        $this->typeObj = $typeObj;
        $this->prefixe = $prefixe;
        $this->champClef = $champClef;
        $this->params = $params;
        $this->paramsArray = explode(" AND ", $this->params);
        $this->tabInfoObjPhp = $tabInfoObjPhp;
        $this->tabShema = $tabShema;
        
        $this->traiteParams();

        global $user;
        $this->user = $user;
    }
    
    
    private function traiteParams(){
        $this->params = str_replace(" AND ", "&",$this->params);
        foreach(explode("&", $this->params) as $param){
            $operateur = false;
            foreach(array("=", "LIKE") as $op){
                if(stripos($param, $op) > -1){
                    $operateur = $op;
                    $tParam = explode($operateur, $param);
                }
            }
            if($operateur){
                    $this->tabParams[] = array("champ" => $tParam[0], 
                        "valeur" => $tParam[1], 
                        "operateur" => $operateur, 
                        "tot" => $tParam[0].$operateur.(is_int($tParam[1])? $tParam[1] : "'".$tParam[1]."'"));
            }
        }
    }

    protected function traiteValeur($data, $valeur, $forSql = false) {
        if ((in_array($data['type'], $this->tabTypeInt) || $data['isClef']) && (!is_string($valeur) || $valeur == "")) {
            $valeur = ($valeur != "" && ($valeur > 0 )) ? $valeur : '0';
        } else if ($data['type'] == "date" && $forSql) {
            $valeur = ($valeur != "") ? "'" . ($valeur) . "'" : 'null';
        } else if(!is_numeric($valeur) && $forSql)
            $valeur = "'" . $valeur . "'";
        
        
        return $valeur;
    }
    
    
    
    protected function traiteChamp($champ, $forSql = false){
        if(!$forSql){
            $replace = array(array("rowid", "id"), 
                array("socid", "fk_soc"), 
                array("commercial_signature_id", "fk_commercial_signature"),
                array("commercial_suivi_id", "fk_commercial_suivi"));
            foreach($replace as $elem){
            if($champ == $elem[0])
                $champ = $elem[1];
            if($champ == $this->prefixe.$elem[0])
                $champ = $this->prefixe.$elem[1];
            }
        }
        return $champ;
    }

    abstract function getOne($idObject);

    abstract function getList();

    abstract function setData($id, $data);
}
