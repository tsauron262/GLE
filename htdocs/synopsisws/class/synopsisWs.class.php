<?php

require_once(DOL_DOCUMENT_ROOT."/synopsisws/class/synopsisWsDataSql.class.php");
require_once(DOL_DOCUMENT_ROOT."/synopsisws/class/synopsisWsDataObj.class.php");
require_once(DOL_DOCUMENT_ROOT."/synopsisws/class/synopsisWsShemaObj.class.php");
require_once(DOL_DOCUMENT_ROOT."/synopsisws/class/synopsisWsShemaFixe.class.php");

class synopsisWs {

    var $typeObj;
    var $tabShema;
    var $db;
    var $champClef = '';
    var $tabInfoObjPhp = array();
    var $params = array();
    var $idTable = 1;
    var $prefixe = "tabP";
    var $tablePrinc;
    var $wsData;

    public function __construct($db) {
        $this->db = $db;
    }

    function init($typeObj, $params) {
        $this->typeObj = $typeObj;
        $this->params = $params;
        $this->getTabShema();
        
        if(in_array($typeObj, array("propal", "contravvt", "contratdet", "listForm")))//Traitement des data par object Dolibarr
            $class = "synopsisWsDataObj";
        else        
            $class = "synopsisWsDataSql";
        $this->wsData = $this->wsData = new $class($this->db, $this->typeObj, $this->prefixe, $this->champClef, $this->params, $this->tabInfoObjPhp, $this->tabShema);
        $this->wsData->init();
    }
    
        
    function getOne($idObject){
        $this->wsData->getOne($idObject);
    }
    function getList(){
        $this->wsData->getList();
    }
    
    function setData($id, $data){
        $this->wsData->setData($id, $data);
    }

    

    function getTabShema() {
        $result = array();
        
        if(count($result) < 1){
            $objShemaFixe = new synopsisWsShemaFixe($this->db);
            $result = $objShemaFixe->getShema($this->typeObj);
        }
        
        if(count($result) < 1){
            $objShemaObj = new synopsisWsShemaObj($this->db);
            $result = $objShemaObj->getShema($this->typeObj);
        }
        $nomTable = (isset($result['tabSql']))? $result['tabSql'] : $this->typeObj; 
        
        $this->tabShema = $this->traiteShema($result, $nomTable, $this->prefixe);


        $this->champClef = $this->tabInfoObjJs[0]['champClef'][0];
    }

    function traiteShema($shemaT, $nomTable, $prefixe = "", $option = array()) {
        $champs = $shemaT['champs'];
        //Parametre par default  valdef val def
        unset($shemaT['champs']);
        if(!isset($shemaT['champClef']))
            $shemaT['champClef'][] = "id";
        if(!isset($shemaT['champDesc']))
            $shemaT['champDesc'] = array();
        $shemaT['prefixe'] = $prefixe;
        $shemaT['nomTable'] = $nomTable;

        if(!isset($shemaT['type']))
          $shemaT['type'] = "text";

        foreach(array("champClef", "champLabel", "champDesc") as $nomTemp){
            foreach ($shemaT[$nomTemp] as $id => $val)
                $shemaT[$nomTemp."Pref"][$id] = $prefixe.$shemaT[$nomTemp][$id];
        }
        $this->tabInfoObjJs[] = $shemaT;

        $this->tabInfoObjPhp['tabSql'][] = array_merge(array(
            "nom" => $nomTable,
            "infoJs" => $shemaT,
            "prefixe" => $prefixe), $option);

        foreach ($champs as $nomChamp => $champ){
            if(isset($champ['fils'])){
               if(!isset($champ['fils']['champ']))
                $champ['fils']['champ'] = $prefixe."rowid";
            }



            if (isset($champ['type']) && $champ['type'] == "relation11") {
                if (isset($champ['fils'])) {
                    $newPrefixe = $prefixe . "tabLJ" . intval(count($this->tabInfoObjPhp['tabSql']));
                    $this->idTable ++;
                    $option = array(
                      "relation" => "LEFT JOIN",
                      "prefixeParent" => $prefixe,
                      "champFiltre" => $newPrefixe.$champ['fils']['champ'],
                      "champFiltreLien" => $prefixe. $shemaT['champClef'][0],
                      "condition"=> "ON " . $newPrefixe . "." . $champ['fils']['champ'] . " = " . ($prefixe != "" ? $prefixe."." :"") . $shemaT['champClef'][0]);
                    $newNewTab = $this->traiteShema($champ['fils']['shema'], $champ['fils']['table'], $newPrefixe, $option);
                    $newTab = array_merge($newTab, $newNewTab);
                } else
                    errorSortie("Pas d'info file pour " . print_r($champ));
            }
            else {
                $champ['isClef'] = in_array($nomChamp, $shemaT['champClef']);
                $champ['prefixe'] = $prefixe;
                $newTab[$prefixe . $nomChamp] = $champ;
            }
        }

        return $newTab;
    }


    function getShema() {
        $result = array("tabInfoObj" => $this->tabInfoObjJs, "tabChamps" => $this->tabShema);
//        print_r($result   );die;
        echo json_encode($result);
    }

}
