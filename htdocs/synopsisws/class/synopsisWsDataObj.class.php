<?php

require_once(DOL_DOCUMENT_ROOT . "/synopsisws/class/synopsisWsData.class.php");

require_once(DOL_DOCUMENT_ROOT . "/comm/propal/class/propal.class.php");
require_once(DOL_DOCUMENT_ROOT . "/contrat/class/contrat.class.php");

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of synopsisWsDataSql
 *
 * @author tijean
 */
class synopsisWsDataObj extends synopsisWsData {

    var $req = "";
    var $lastInsert = array();
    var $lastUpdate = array();
    var $object;
    var $subVal = 0;

    function init() {
        $class = ucfirst($this->typeObj);
        $class = str_replace("det", "Ligne", $class);
        $this->object = new $class($this->db);
        
        
        foreach($this->paramsArray as $param)
            if(stripos($param, "subvaleur=") !== false)
                    $this->subVal = str_replace("subValeur=", "", $param);
    }

    public function getList() {
        // $tab = $this->object->liste_array(0,0,0,0,$this->limitList);
        $tabRet = array();
        // foreach($tab as $ligne)
        //$tabRet = $this->traiteObjOrArray($ligne, $tabRet);
        
         if($this->typeObj == "listForm" && $this->subVal > 0){
             $this->object->fetch($this->subVal);
             $tabRet = $this->traiteIdLabel($this->object->getValues());
         }
        
        $this->traiteRetour($tabRet);
    }

    public function getOne($idObject) {
        $this->object->fetch($idObject);
        
        if($this->typeObj == "listForm"){
            
            $this->object->fetch($this->subVal);
            $this->object = $this->traiteIdLabel($this->object->getValue($idObject));
        }
        
        $this->traiteRetour($this->object);
    }
    
    private function traiteIdLabel($object){
        $newArr = array();
        foreach($object as $id => $val)
            $newArr = array_merge($newArr, $this->traiteObjOrArray(array('id' => $id, 'label'=>$val)));
        return $newArr;
    }

    public function setData($idObject, $data) {
        $data = json_decode($data);
        $this->object->fetch($idObject);

        foreach ($this->tabShema as $nChamp => $champ) {
            $nomChamp = $this->traiteChamp(str_replace($champ['prefixe'], "", $nChamp));
            
            $valeur = $this->traiteValeur($champ, $data->$nChamp);
            
            if (isset($data->$nChamp))
                $this->object->$nomChamp = $valeur;
            else
                echo "pas de " . $nChamp;
        }
        if($idObject > 0)
            $ok = $this->object->update($this->user);
        else{
            $ok = $this->object->create($this->user);
            $this->object->id = $ok;
        }


        if ($ok > 0)
            echo json_encode(array("OK" => "OK", "newIdObj" => $this->object->id));
        else {
            echo json_encode(array("ereurCode" => "NoSave", "erreurMess" => "Erreur enregistrement ".$this->object->error. "   : ".$this->db->error() . " | "));
        }
    }

    private function traiteObjOrArray($objOrArray, $tabRet = array()) {
        $tabT = array();
        $valClef = "";
        foreach ($this->tabShema as $nChamp => $champ) {
            $nomChamp = str_replace($champ['prefixe'], "", $nChamp);


            if (is_array($objOrArray) && isset($objOrArray[$nomChamp]))
                $val = $objOrArray[$nomChamp];
            else if (isset($objOrArray->$nomChamp))
                $val = $objOrArray->$nomChamp;
            else
                $val = "Données inconnue";

            if ($nomChamp == $this->champClef)
                $valClef = $val;

            $tabT[$nChamp] = $val;
        }

        if ($valClef < 1 && $valClef != "0" && !is_string($valClef))
            die("Pas de valleur ID: ".$this->champClef . "|" . print_r($objOrArray, 1));

        $tabRet[$valClef] = $tabT;
        return $tabRet;
    }

    private function traiteRetour($ret) {
        echo json_encode($ret);
//        print_r($ret);
    }

}
