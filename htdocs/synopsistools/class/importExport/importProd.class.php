<?php

require_once DOL_DOCUMENT_ROOT . "/synopsistools/class/importExport/importCat.class.php";

class importProd extends importCat {

    public function __construct($db) {
        parent::__construct($db);
        $this->path .= "../prod/";
        $this->sepCollone = "	";
    }

    function traiteLn($ln) {
        $this->tabResult["total"] ++;
        if ($ln['ArtCode'] != "" && $ln['ArtCode'] != "0") {
            if ($ln['ArtLib'] == "")
                $ln['ArtLib'] = "*";
            if ($ln['ArtID'] == "")
                $ln['ArtID'] = "N/C";
            $sql = $this->db->query("SELECT rowid as id, ref FROM llx_product WHERE import_key = '" . $ln['ArtID'] . "' || ref = '".$ln['ArtCode']."'");
//            if ($this->db->num_rows($sql) == 1) {
//                $result = $this->db->fetch_object($sql);
////                $sql2 = $this->db->query("SELECT rowid as id FROM llx_product WHERE ref = '" . $ln['ArtCode'] . "' AND (import_key != '" . $ln['ArtID'] . "' || import_key IS NULL)");
////                if ($this->db->num_rows($sql2) > 0) {
////                    $result2 = $this->db->fetch_object($sql2);
////                    $this->tabResult["error"] ++;
////                    $this->error("Prod avec ref identique et autre avec id8sens identique");
////                    $this->alert("IdIdentique id :" . $result->id . " ref : " . $result->ref . " |  UPDATE llx_commandedet SET fk_product = '" . $result->id . "' WHERE fk_product = '" . $result2->id . "'");
////                    $this->alert("RefIdentique id:" . $result2->id . " ref: " . $ln['ArtCode'] . "  |  UPDATE llx_commandedet SET fk_product = '" . $result2->id . "' WHERE fk_product = '" . $result->id . "'");
////                } else {
//                    $this->tabResult["connue"] ++;
//                    $this->updateProd($result->id, $ln);
////                }
//            } elseif ($this->db->num_rows($sql) > 1) {
//                $this->tabResult["double"] ++;
//                $this->error("ref " . $ln['ArtCode'] . " avec plusieur pord : " . $this->db->num_rows($sql));
//            } else {
                $sql = $this->db->query("SELECT rowid as id FROM llx_product WHERE ref = '" . $ln['ArtCode'] . "'");
                if ($this->db->num_rows($sql) == 0) {
                    if (/*$this->isProdActif($ln)*/1) {
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
//            }
//            } else {
//                $this->tabResult["error"] ++;
//                $this->alert("Prod sans label ref : ".$ln['ArtCode']);
//            }
        } else {
            $this->tabResult["error"] ++;
            $this->alert("Prod sans ref label : " . $ln['ArtLib']);
        }
    }
    
    function isProdActif($ln){
        if($ln['ArtIsSupp'] != "X" && $ln['ArtIsSleep'] != "X" && $ln['ArtNiv'] < 1)
            return 1;
        return 0;
        
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

            $this->ident = $this->object->ref;

            $this->traiteChamp("price", $ln['ArtPrixBase'], true);
            $this->traiteChamp("tva_tx", $ln['ArtGTaxTaux'], true);
            $this->updatePrice = $this->update;
            $this->update = false;


            $this->traiteChamp("options_serialisable", ($ln['ArtStkNuf'] == "N° de série" || $ln['ArtStkNuf'] == "NUFARTSTKSERIE"));
            $this->traiteChamp("options_validate", 1);
            $this->traiteChamp("options_deee", ($ln['ArtFree1'] == '' ? 0 : $ln['ArtFree1']), true);
            $this->traiteChamp("options_rpcp", ($ln['ArtFree2'] == '' ? 0 : $ln['ArtFree2']), true);



            $this->traiteChamp("status", ($this->isProdActif($ln)) ? "1" : "0");
            $this->traiteChamp("status_buy", ($this->isProdActif($ln)) ? "1" : "0");


            $this->traiteChamp("label", $ln['ArtLib']);
            
            $this->traiteChamp("pmp", $ln['ArtLastPA'], true);
            
            $desc = ($ln['ArtGCmtTxt'] != "")? $ln['ArtGCmtTxt'] : $ln['ArtLib'];
            
            $this->traiteChamp("description", $desc);
            $this->traiteChamp("ref", $ln['ArtCode']);
            $this->traiteChamp("import_key", $ln['ArtID']);
            $this->traiteChamp("barcode", $ln['ArtCodeBarre']);
            
            if(!isset($_REQUEST['light'])){
                $this->getAllCat();

                $this->traiteCat1("Gamme", $ln["ArtGammeEnu"]);
                $this->traiteCat1("Categorie", $ln["ArtCategEnu"]);
                $this->traiteCat1("Nature", $ln["ArtNatureEnu"]);
                $this->traiteCat1("Collection", $ln["ArtCollectEnu"]);
                $this->traiteCat1("Famille", $ln["ArtFamilleEnu"]);
            }


            if ($this->updatePrice) {
                $this->object->updatePrice($this->object->price, 'HT', $user, $this->object->tva_tx);
                $this->tabResult["modifier"] ++;
            }
            if ($this->update) {
                $this->object->update($this->object->id, $user);
                $this->tabResult["modifier"] ++;
            }
        } else
            $this->error("Pas d'id pour maj " . $ln['ArtCode']);
    }

}
