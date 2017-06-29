<?php

require("../main.inc.php");

global $db;



header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
header('Access-Control-Allow-Credentials: true');
header('Content-Type:application/json; charset=utf-8');



$params = (isset($_REQUEST['params'])) ? str_replace("!eg!", "=", str_replace("!et!", " AND ", $_REQUEST['params'])) : null;

$idObject = (isset($_REQUEST['idObject'])) ? $_REQUEST['idObject'] : -100;

if (isset($_REQUEST['object']) && $_REQUEST['object'] != "") {
    $typeObjet = $_REQUEST['object'];

    $angular = new angularWS($db);
    $angular->init($typeObjet, $params);


    //die($req);


    if (isset($_REQUEST['action']) && $_REQUEST['action'] == "data") {
        if ($idObject >= 0) {
            $angular->getOne($idObject);
        } else {
            $angular->getList();
        }
    }
    if (isset($_REQUEST['action']) && $_REQUEST['action'] == "shema") {
        $angular->getShema();
    }
}

class angularWS {

    var $typeObj;
    var $tabShema;
    var $db;
    var $req;
    var $tabChamp = array();
    var $champClef = 'rowid';
    var $tabInfoObj = array();
    var $params = array();
    var $idTable = 1;
    var $prefixe = "tabP";

    public function __construct($db) {
        $this->db = $db;
    }

    function init($typeObj, $params) {
        $this->typeObj = $typeObj;
        $this->params = $params;
        $this->getTabShema();
        $this->toTabChamp();
        $this->getReq();
    }

    function getReq() {
        $tablePrinc = MAIN_DB_PREFIX . $this->typeObj . " " . $this->prefixe;


        $req = "SELECT " . implode(",", $this->tabChamp) . " FROM " . $tablePrinc . " ";

        foreach($this->tabInfoObj['tabSql'] as $val){
            $req .= " ".$val['relation']." "." ".MAIN_DB_PREFIX.$val['nom']." ".$val['prefixe']. " ".$val['condition'];
        }



        $req .= " WHERE 1";
        if ($idObject)
            $req .= " AND " . $this->champClef . " = " . $idObject;
        if ($this->params) {
            if (stripos($this->params, "undefined") === false)
                $req .= " AND " . $this->params;
            else
                $req .= " AND 0 ";
        }
        $this->req = $req;
    }

    function getTabShema() {

        $tabShema = array();

        $tabShema['user_extrafields'] = array(
            "champLabel" => array("nom"),
            "labelTitre" => "Utilisateur extra",
            "champs" => array(
                "apple_id" => array("type" => "id", 'label' => 'Id Apple', 'required' => true),
                "apple_service" => array("type" => "text"),
                "fk_object" => array("type" => "id", "label" => "Lien user", 'disabled' => true)
            )
        );


        $tabShema['user'] = array(
            'champClef' => array("rowid"),
            "champLabel" => array("lastname", "firstname"),
            "labelTitre" => "Utilisateur",
            "champs" => array(
                "rowid" => array("type" => "hidden", 'disabled' => true, 'label' => 'Id Utilisateur', 'required' => true, 'disabled' => true),
                "lastname" => array("type" => "text"),
                "firstname" => array("type" => "text", 'required' => true),
                "login" => array("type" => "text"),
                "email" => array("type" => "email"),
                "admin" => array("type" => "checkbox"),
                "office_phone" => array("type" => "text", "label" => "Tel Pro"),
                "user_mobile" => array("type" => "text", "label" => "Tel Mobile"),
                "onsansfous" => array("order" => 3, "type" => "relationN1", "label" => "Societe créer par l'utilisateur", "fils" => array("object" => "societe", "clefLien" => "fk_user_creat")),
                "fk_soc" => array("type" => "relation1N", "label" => "Societe de l'utilisateur", "fils" => array("object" => "societe")),
                "onsansfous2" => array("type" => "relation11", "label" => "Extra", "fils" => array("table" => "user_extrafields", "champ" => "fk_object", "shema" => $tabShema['user_extrafields'])),
                "onsansfous3" => array("type" => "relation11", "label" => "Extra", "fils" => array("table" => "user_extrafields", "champ" => "rowid", "shema" => $tabShema['user_extrafields']))
            )
        );

        $tabShema['societe'] = array(
            "champLabel" => array("nom"),
            "labelTitre" => "Societe",
            "champs" => array(
                "rowid" => array("type" => "id", 'label' => 'Id Client', 'required' => true, 'disablead' => true),
                "nom" => array("type" => "text", "label" => "Nom", "type" => "text", 'disablead' => true),
                'tms' => array('label' => "Dernière activité", 'type' => "date"),
                "fk_user_creat" => array("type" => "relation1N", "label" => "Créateur", "fils" => array("object" => "user"))
            )
        );





        $this->tabShema = $this->traiteShema($tabShema[$this->typeObj], $this->prefixe);


        $this->champClef = $this->tabShemaInfo[0]['champClef'][0];
    }

    function traiteShema($shemaT, $prefixe = "", $newTab = array()) {
        $champs = $shemaT['champs'];

        //Parametre par default
        unset($shemaT['champs']);
        if(!isset($shemaT['champClef']))
            $shemaT['champClef'][] = "rowid";
        $shemaT['prefixe'] = $prefixe;
        foreach(array("champClef", "champLabel") as $nomTemp){
            foreach ($shemaT[$nomTemp] as $id => $val)
                $shemaT[$nomTemp."Pref"][$id] = $prefixe.$shemaT[$nomTemp][$id];
        }
        $this->tabShemaInfo[] = $shemaT;

        foreach ($champs as $nomChamp => $champ) {
            if (isset($champ['type']) && $champ['type'] == "relation11") {
                if (isset($champ['fils'])) {
                    $newPrefixe = $prefixe . "tabLJ" . intval(count($this->tabInfoObj['tabSql'])+1);
                    $this->idTable ++;
                    $this->tabInfoObj['tabSql'][] = array(
                        "nom" => $champ['fils']['table'],
                        "relation" => "LEFT JOIN",
                        "prefixe" => $newPrefixe,
                        "condition"=> "ON " . $newPrefixe . "." . $champ['fils']['champ'] . " = " . ($prefixe != "" ? $prefixe."." :"") . $shemaT['champClef'][0]);
                    $newTab = $this->traiteShema($champ['fils']['shema'], $newPrefixe, $newTab);
                } else
                    errorSortie("Pas d'info file pour " . print_r($champ));
            }
            else {
                $champ['prefixe'] = $prefixe;
                $newTab[$prefixe . $nomChamp] = $champ;
            }
        }

        return $shemaT = $newTab;
    }

    function toTabChamp($tabShema = null) {
        if (!$tabShema)
            $tabShema = $this->tabShema;

        foreach ($tabShema as $champ => $infos) {
            $prefixe = $infos['prefixe'];

            $champ = str_replace($prefixe, "", $champ);

            if (isset($infos['type']) && $infos['type'] == "relationN1") {

            }//on ajoute pas le champ
            else {
                if ($prefixe != "")
                    $this->tabChamp[] = $prefixe . "." . $champ . " as " . $prefixe . $champ;
                else
                    $this->tabChamp[] = $champ;
            }
        }
    }

    function getList() {
        $req .= $this->req . " LIMIT 0,100";
        $sql = $this->db->query($req);
        $data = array();
        while ($result = $this->db->fetch_object($sql)) {
            $champClef = $this->prefixe . $this->champClef;
            if (!isset($result->$champClef))
                errorSortie("Pas de  pour champ : " . $champClef . " dans " . print_r($result, 3));

            $data[$result->$champClef] = $result;
        }

        echo json_encode($data);
    }


    function getOne($idObject) {
        $champClef = $this->prefixe . $this->champClef;
        $sql = $this->db->query($this->req . " AND " . $this->prefixe . "." . $this->champClef . " = " . $idObject);
        if ($this->db->num_rows($sql) > 0) {
            while ($result = $this->db->fetch_object($sql)) {
                if (!isset($result->$champClef))
                    errorSortie("La clef " . $champClef . " est introuvable dans result : " . print_r($result, 1));
                $data[$this->typeObj][$result->$champClef] = $result;
            }
        }
        else {
            dol_syslog("pas de result", 3);
            $obj = new Object();
            $obj->$champClef = $idObject;
            $data[$this->typeObj][$idObject] = $obj;
        }


        echo json_encode(array($data[$this->typeObj][$idObject]));
    }

    function getShema() {
        $result = array("tabInfoObj" => $this->tabShemaInfo, "tabChamps" => $this->tabShema);
//        print_r($result   );die;
        echo json_encode($result);
    }

}

function errorSortie($str) {
    echo "Erreur : " . $str;
    die;
    dol_syslog($str, 3);
}
