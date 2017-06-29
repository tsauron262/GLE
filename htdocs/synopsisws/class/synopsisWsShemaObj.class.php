<?php

require_once(DOL_DOCUMENT_ROOT . "/synopsischrono/class/chrono.class.php");

class synopsisWsShemaObj {

    public function __construct($db) {
        $this->db = $db;
    }

    private function traiteTypeChamp($champ, $typeObj) {
        $tabReplace = array(
            1 => "text",
            2 => "date",
            3 => "datetime",
            4 => "boolean",
            6 => "int",
            7 => "int",
            8 => "relation1N",
            10 => "relationN1"
        );

        $tabReplace2 = array(//a suppr renommé sav en chrono105 ...
            "chrono105" => "sav",
            "chrono101" => "productCli",
            "chrono100" => "appel",
            "chrono106" => "ope"
        );

        $type = $champ['type'];

        if (isset($tabReplace[$type])) {
            $champ['type'] = $tabReplace[$type];

            if ($type == 8) {
                $champ['fils'] = array("object" => "listForm", "blockUpdate" => true);
                $champ['paramsReq']['serv'] = array("subValeur=" . $champ['type_subvaleur']);
            }
            if ($type == 10) {
                $lien = new lien($this->db);
                $lien->fetch($champ['type_subvaleur']);

                $type1 = "sourcetype";
                $type2 = "targettype";
                $val1 = "fk_source";
                $val2 = "fk_target";
                if (!$lien->ordre) {
                    $typeT = $type2;
                    $type2 = $type1;
                    $type1 = $typeT;
                    $valT = $val2;
                    $val2 = $val1;
                    $val1 = $valT;
                }
                $lien->table = str_replace(MAIN_DB_PREFIX, "", $lien->table);
                $champ['fils'] = array("object" => ($lien->table == "synopsischrono") ? "chrono" . $lien->typeChrono : $lien->table,
                    "clefLien" => $val1,"clefLienF" => $val2);

                if (!isset($tabReplace2[$typeObj]))
                    die("pas de concordance dans tabReplace2");
                
                $typeObj = $tabReplace2[$typeObj];
                
                $champ['paramsReq']['serv'] = array(
                    $type1 . "=" . $typeObj,
                    $type2 . "=" . $lien->nomElem);
//                print_r($champ);die;
            }
        }
//        echo "<pre>";print_r($champ);die;

        return $champ;
    }

    function getShema($typeObj) {
        if (stripos($typeObj, "chrono") === 0) {
            $typeChrono = str_replace("chrono", "", $typeObj);
            $chrono = new Chrono($this->db);
            $chrono->model_refid = $typeChrono;
            $chrono->getKeys();
            $champs = $chrono->keysListByModel[$typeChrono];
            $newTab = array("id" => array("type" => "id"));
            foreach ($champs as $idChamp => $champ) {
                $nomChamp = traiteCarac($champ['nom']);
                $champ['label'] = $champ['nom'] . "-" . $champ['type'];
                $champ = $this->traiteTypeChamp($champ, $typeObj);
                $newTab[$nomChamp] = $champ;
            }


            $tabResult = array(
                "tabSql" => "synopsischrono_chrono_" . $typeChrono,
                "champLabel" => array("tabLJ1ref"),
                "champDesc" => array("tabLJ1description"),
                "labelTitre" => "Chrono ",
                "champs" => array(
                    "id" => array("type" => "id"),
                    "details" => array(
                        "type" => "relation11",
                        "label" => "Details",
                        "fils" => array(
                            "table" => "synopsischrono",
                            "champ" => "id",
                            "shema" => array(
                                "champLabel" => array("ref"),
                                "labelTitre" => array("Chrono"),
                                "champs" => array(
                                    "id" => array("type" => "id"),
                                    "ref" => array("type" => "text"),
                                    "description" => array("type" => "text"),
                                    "fk_soc" => array("type" => "relation1N", "label" => "Societe", "fils" => array("object" => "societe"))
                                )
                            )
                        )
                    )
                )
            );


            $tabResult["champs"] = array_merge($tabResult["champs"], $newTab);


            return $tabResult;
        }


        if (stripos($typeObj, "listForm") === 0) {
            $tabResult = array(
                "champLabel" => array("label"),
                "labelTitre" => "Liste ",
                "champs" => array(
                    "id" => array("type" => "string"),
                    "label" => array()
                )
            );

            return $tabResult;
        }
    }

}
