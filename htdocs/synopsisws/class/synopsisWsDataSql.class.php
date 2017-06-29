<?php

require_once(DOL_DOCUMENT_ROOT . "/synopsisws/class/synopsisWsData.class.php");

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
class synopsisWsDataSql extends synopsisWsData {

    var $req = "";
    var $lastInsert = array();
    var $lastUpdate = array();
    var $tabChamp = array();

    function init() {
        $this->toTabChamp();
        $this->getReq();
    }

    private function getReq() {
        //$this->tablePrinc = MAIN_DB_PREFIX . $this->typeObj . " " . $this->prefixe;

        /*
         * Ajout de element_element pour relation NN
         */
        $elemElemCond = "1";
        $chVide = "";
        $tabChEl = array("sourcetype", "targettype", "fk_source", "fk_target");
        foreach ($this->tabParams as $idParam => $param) {
            if (in_array($param['champ'], $tabChEl)) {
                $elemElemCond .= " AND " . $param['tot'];
                $this->tabParams[$idParam]['tot'] = "1";
                if ($param['champ'] == $tabChEl[3])
                    $chVide = $tabChEl[2];
                if ($param['champ'] == $tabChEl[2])
                    $chVide = $tabChEl[3];
            }
        }
        if ($elemElemCond != "1") {
            $prefixe = "elemElem";
            $this->tabInfoObjPhp['tabSql'][] = array(
                "relation" => "INNER JOIN",
                "nom" => "element_element",
                "condition" => "ON " . $elemElemCond,
                "champFiltre" => $elemElemCond . " AND " . $chVide,
                "prefixe" => $prefixe,
                "infoJs" => array(
                    "champClef" => $tabChEl
                )
            );
            foreach($tabChEl as $tt)
                $this->tabShema[$prefixe.$tt] = array("prefixe" => $prefixe);
            $idObject = $chVide;
        }

        $req = "SELECT " . implode(",", $this->tabChamp) . " FROM ";

        foreach ($this->tabInfoObjPhp['tabSql'] as $val) {
            $req .= " " . $val['relation'] . " " . " " . MAIN_DB_PREFIX . $val['nom'] . " " . $val['prefixe'] . " " . $val['condition'];
        }

        //print_r($this->tabInfoObjPhp['tabSql']);die($req);


        $req .= " WHERE 1";
        if ($idObject)
            $req .= " AND " . $this->prefixe . "." . $this->champClef . " = " . $idObject;
        foreach ($this->tabParams as $param) {
            if (stripos($param['tot'], "undefined") === false)
                $req .= " AND " . $param['tot'];
            else
                $req .= " AND 0 ";
        }
        $this->req = $req;
    }

    function toTabChamp($tabShema = null) {
        if (!$tabShema)
            $tabShema = $this->tabShema;

        foreach ($tabShema as $champ => $infos) {
            $prefixe = $infos['prefixe'];

            $champ = str_replace($prefixe, "", $champ);

            $champ1 = $this->traiteChamp($champ, true);
            $newTab = array();

            if (isset($infos['type']) && $infos['type'] == "relationN1") {
                
            }//on ajoute pas le champ
            else {
                if ($prefixe != "")
                    $this->tabChamp[] = $prefixe . "." . $champ1 . " as " . $prefixe . $champ;
                else
                    $this->tabChamp[] = $champ;
            }
        }
    }

    function getList() {
        $req = $this->req . " ORDER BY " . $this->prefixe . "." . $this->champClef . " DESC LIMIT 0," . $this->limitList;

        $sql = $this->db->query($req);
        $data = array();
        while ($result = $this->db->fetch_object($sql)) {
            $champClef = $this->prefixe . $this->champClef;
            if (!isset($result->$champClef))
                errorSortie("Impossible de trouvé : " . $champClef . " dans " . print_r($result, 3));

            $data[$result->$champClef] = $result;
        }

        echo json_encode($data);
    }

    function getOne($idObject) {
        $champClef = $this->prefixe . $this->champClef;
        if ($idObject > 0)
            $sql = $this->db->query($this->req . " AND " . $this->prefixe . "." . $this->champClef . " = " . $idObject);
        else
            $sql = $this->db->query($this->req . " LIMIT 0,1");
        if ($this->db->num_rows($sql) > 0) {
            while ($result = $this->db->fetch_object($sql)) {
                if (!isset($result->$champClef))
                    errorSortie("La clef " . $champClef . " est introuvable dans result : " . print_r($result, 1));
                $data[$this->typeObj][$result->$champClef] = $result;
            }
            if ($idObject == 0) {
                foreach ($data[$this->typeObj] as $obj)
                    $data[$this->typeObj][0] = $obj;
                foreach ($data[$this->typeObj][0] as $nomCh => $inut)
                    $data[$this->typeObj][0]->$nomCh = "";
            }
        } else {
            if ($this->typeObj != "login")
                dol_syslog("pas de result req: " . $this->req, 3);
            $obj = new Object();
            $obj->$champClef = $idObject;
            $data[$this->typeObj][$idObject] = $obj;
        }

        foreach ($this->tabInfoObjPhp['tabSql'] as $champ) {
            if (isset($champ['relation']) && $champ['relation'] == "LEFT JOIN") {//pour chaque relations
                foreach ($data[$this->typeObj] as $id => $vals) {//Pour chaque ligne (normallement que une)
                    $champVal = $champ['champFiltreLien'];
                    $champFiltre = $champ['champFiltre'];
                    if (!property_exists($vals, $champFiltre))
                        die("Impossible de trouvé le champ " . $champFiltre);
                    if ($vals->$champFiltre == "")
                        $data[$this->typeObj][$id]->$champFiltre = "forCreate" . $vals->$champVal;
                }
            }
        }

        echo json_encode(array($data[$this->typeObj][$idObject]));
    }

    function setData($id, $data) {
        $tabData = json_decode($data);
        $tabModif = $tabUp = $tabIn1 = $tabIn2 = array();
        foreach ($this->tabShema as $champ => $data) {
            $valeur = -1000;
            $champ = $this->traiteChamp($champ, true);
            $champSqlSanPref = str_replace($data['prefixe'], "", $champ);
            $champSql = str_replace($data['prefixe'], $data['prefixe'] . ".", $champ);
            if (isset($tabData->$champ) && !in_array($data['type'], $this->tabTypeNoSql)) {
                $valeur = addslashes($tabData->$champ);

            }
            
            
            //on regarde enssuite dans tabParams
            foreach ($this->tabParams as $param)
                if ($param["operateur"] == "=" && $data['prefixe'] . $param["champ"] == $champ){
                    $valeur = $param["valeur"];
                    $tabData->$champ = $valeur;
                }

            //Si valeur on ajoute
            if ($valeur != -1000) {
                $valeur = $this->traiteValeur($data, $valeur, true);
                $tabModif[$champSqlSanPref] = $valeur;
                $tabUp[$data['prefixe']][] = $champSql . "=" . $valeur . "";

                $tabIn1[$data['prefixe']][] = $champSqlSanPref;

                $tabIn2[$data['prefixe']][] = str_replace("forCreate", "", $valeur);
            }
        }
//        print_r($this->tabShema);
//die("ok");
        if ($this->typeObj == "login") {
            echo gleLogin($tabModif['login'], $tabModif['password']);
        } else {
            $ok = true;
            foreach ($this->tabInfoObjPhp['tabSql'] as $tabSql) {//Pour chaque table sql
                if ($ok) {
                    $where = array();
                    $mode = "up";
                    $prefixe = $tabSql['prefixe'];
                    $tableSql = MAIN_DB_PREFIX . $tabSql['nom'];


//print_r($this->tabInfoObjJs);
                    if (isset($tabSql['champFiltre'])) {//pour chaque champ lien
                        $lastIdIns = $this->lastInsert[$tabSql['prefixeParent']];
                        $lastIdUpd = $this->lastUpdate[$tabSql['prefixeParent']];

                        if ($lastIdIns > 0) {
                            foreach ($tabIn1[$prefixe] as $id => $ch)
                                if ($tabSql['champFiltre'] == $prefixe . $ch)
                                    $idCh = $id;
                            $tabIn2[$prefixe][$idCh] = $lastIdIns;
                        }
                        else if ($lastIdUpd > 0) {
                            foreach ($tabIn1[$prefixe] as $id => $ch)
                                if ($tabSql['champFiltre'] == $prefixe . $ch)
                                    $idCh = $id;
                            $tabIn2[$prefixe][$idCh] = $lastIdUpd;
                        }
                    }



                    foreach ($tabSql['infoJs']['champClef'] as $chId) {//Pour chaque id de la table
                        $champIdP = $prefixe . "" . $chId;
                        $chId = $this->traiteChamp($chId, true);
                        if (!isset($tabData->$champIdP))
                            die("pas de valeur pour id " . $champIdP . " table " . $tableSql." data :".print_r($tabData,1));
                        elseif ($tabData->$champIdP == "" || $tabData->$champIdP == 0)
                            $mode = "in";
//die("valeur null ".$chId." pour remplir  ".$champIdP." table ".$tableSql);
                        elseif (stripos($tabData->$champIdP, "forCreate") !== false) {
                            $mode = "in";
                            $where[] = $chId . "=" . $tabData->$champIdP;
                        } elseif (!is_numeric($tabData->$champIdP))
                            die("l'id n'est pas un numérique " . $chId . " val : " . $tabData->$champIdP);
                        else {//on update
                            $where[] = $chId . "=" . $tabData->$champIdP;
                            $this->lastUpdate[$prefixe] = $tabData->$champIdP;
                        }
                    }
                    if ($mode == "up") {
                        $sql = "UPDATE " . $tableSql . " " . $prefixe . " SET " . implode(", ", $tabUp[$prefixe]) . " WHERE " . implode(" AND ", $where);
                        $result = $this->db->query($sql);
                    } else {
                        if($tabSql['nom'] == "element_element"){
                            
                        }
                        else{                        
                        $sql = "INSERT INTO " . $tableSql . " (" . implode(",", $tabIn1[$prefixe]) . ") VALUES (" . implode(",", $tabIn2[$prefixe]) . ")";
                        $result = $this->db->query($sql);

                        $this->lastInsert[$prefixe] = $this->db->last_insert_id($tableSql);
                        }
                    }
echo $sql."\n\n";
                    $ok = ($ok && $result);
                }
            }
            if ($ok)
                echo json_encode(array("OK" => "OK", "newIdObj" => $this->lastInsert[$this->prefixe]));
            else {
                echo json_encode(array("ereurCode" => "NoSave", "erreurMess" => $this->db->error() . " | " . $sql));
            }
        }
    }

}
