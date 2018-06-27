<?php

require_once DOL_DOCUMENT_ROOT . "/synopsistools/class/importExport/import8sens.class.php";

class importProd extends import8sens {

    public function __construct($db) {
        parent::__construct($db);
        $this->path .= "prod/";
    }

    function traiteLn($ln) {
        $this->tabResult["total"] ++;
        if ($ln['ArtCode'] != "" && $ln['ArtCode'] != "0") {
            if ($ln['ArtLib'] == "") //{
                $ln['ArtLib'] = "*";
            $sql = $this->db->query("SELECT rowid as id, ref FROM llx_product WHERE import_key = '" . $ln['ArtID'] . "'");
            if ($this->db->num_rows($sql) == 1) {
                $result = $this->db->fetch_object($sql);
                $sql2 = $this->db->query("SELECT rowid as id FROM llx_product WHERE ref = '" . $ln['ArtCode'] . "' AND (import_key != '" . $ln['ArtID'] . "' || import_key IS NULL)");
                if ($this->db->num_rows($sql2) > 0) {
                    $result2 = $this->db->fetch_object($sql2);
                    $this->tabResult["error"] ++;
                    $this->alert("Prod avec ref identique et autre avec id8sens identique");
                    $this->alert("IdIdentique id :" . $result->id . " ref : " . $result->ref . " |  UPDATE llx_commandedet SET fk_product = '" . $result->id . "' WHERE fk_product = '" . $result2->id . "'");
                    $this->alert("RefIdentique id:" . $result2->id . " ref: " . $ln['ArtCode'] . "  |  UPDATE llx_commandedet SET fk_product = '" . $result2->id . "' WHERE fk_product = '" . $result->id . "'");
                } else {
                    $this->tabResult["connue"] ++;
                    $this->updateProd($result->id, $ln);
                }
            } elseif ($this->db->num_rows($sql) > 1) {
                $this->tabResult["double"] ++;
                $this->error("ID " . $ln['ArtID'] . " avec plusieur pord : " . $result->nb);
            } else {
                $sql = $this->db->query("SELECT rowid as id FROM llx_product WHERE ref = '" . $ln['ArtCode'] . "'");
                if ($this->db->num_rows($sql) == 0) {
                    if($ln['ArtIsSupp'] != "X" && $ln['ArtIsSleep'] != "X"){
                        $this->tabResult["inc"] ++;
                        $this->updateProd($this->addProd($ln), $ln);
                    }
                } elseif ($this->db->num_rows($sql) == 1) {
                    $result = $this->db->fetch_object($sql);
                    $this->tabResult["connue"] ++;
                    $this->updateProd($result->id, $ln);
                } else {
                    $this->tabResult["double"] ++;
                    $this->error("Ref " . $ln['ArtCode'] . " avec plusieur pord : " . $result->nb);
                }
            }
//            } else {
//                $this->tabResult["error"] ++;
//                $this->alert("Prod sans label ref : ".$ln['ArtCode']);
//            }
        } else {
            $this->tabResult["error"] ++;
            $this->alert("Prod sans ref label : " . $ln['ArtLib']);
        }
    }

    function addProd($ln) {
        global $user;
        $prod = new Product($this->db);
        $prod->ref = $ln['ArtCode'];
        $prod->label = $ln['ArtLib'];
        $id = $prod->create($user);

        if ($prod->error != "") {
            $this->error($prod->error);
            return -1;
        }
        if (count($prod->errors) > 0) {
            $this->error($prod->errors);
            return -1;
        }
        $this->tabResult["creer"] ++;
        return $id;
    }

    function updateProd($idGle, $ln) {
        global $user;
        if ($idGle > 0) {
            $this->update = false;
            $this->object = new Product($this->db);
            $this->object->fetch($idGle);
            $this->object->fetch_optionals();

            $this->traiteChamp("price", $ln['ArtPrixBase'], true);
            $this->traiteChamp("tva_tx", $ln['ArtGTaxTaux'], true);
            $this->updatePrice = $this->update;
            $this->update = false;


            $this->traiteChamp("options_serialisable", ($ln['ArtStkNuf'] == "N° de série"));




            $this->traiteChamp("status", $ln['ArtIsSupp'] != "X" && $ln['ArtIsSleep'] != "X");
            $this->traiteChamp("status_buy", $ln['ArtIsSupp'] != "X" && $ln['ArtIsSleep'] != "X");


            $this->traiteChamp("label", $ln['ArtLib']);
            $this->traiteChamp("ref", $ln['ArtCode']);
            $this->traiteChamp("import_key", $ln['ArtID']);


            if ($this->updatePrice) {
                $this->object->updatePrice($this->object->price, 'HT', $user, $this->object->tva_tx);
                $this->alert($this->object->id . " updatePrice");
//                $this->alert (print_r($ln,1));
                $this->tabResult["modifier"] ++;
            }
            if ($this->update) {
                $this->object->update($this->object->id, $user);
                $this->alert($this->object->id . " update");
//                $this->alert (print_r($ln,1));
                $this->tabResult["modifier"] ++;
            }
        } else
            $this->error("Pas d'id pour maj " . $ln['ArtCode']);
    }

}
