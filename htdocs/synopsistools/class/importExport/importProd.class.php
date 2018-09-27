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
                    if ($ln['ArtIsSupp'] != "X" && $ln['ArtIsSleep'] != "X") {
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

            $this->ident = $this->object->ref;

            $this->traiteChamp("price", $ln['ArtPrixBase'], true);
            $this->traiteChamp("tva_tx", $ln['ArtGTaxTaux'], true);
            $this->updatePrice = $this->update;
            $this->update = false;


            $this->traiteChamp("options_serialisable2", ($ln['ArtStkNuf'] == "N° de série"));
            $this->traiteChamp("options_serialisable", 0);
            $this->traiteChamp("options_deee", ($ln['ArtFree1'] == '' ? 0 : $ln['ArtFree1']), true);
            $this->traiteChamp("options_rpcp", ($ln['ArtFree2'] == '' ? 0 : $ln['ArtFree2']), true);



            $this->traiteChamp("status", ($ln['ArtIsSupp'] != "X" && $ln['ArtIsSleep'] != "X") ? "1" : "0");
            $this->traiteChamp("status_buy", ($ln['ArtIsSupp'] != "X" && $ln['ArtIsSleep'] != "X") ? "1" : "0");


            $this->traiteChamp("label", $ln['ArtLib']);
            $this->traiteChamp("description", $ln['ArtLib']);
            $this->traiteChamp("ref", $ln['ArtCode']);
            $this->traiteChamp("import_key", $ln['ArtID']);
            
            $this->getAllCat();

            $this->traiteCat("Gamme", $ln["ArtGammeEnu"]);
            $this->traiteCat("Famille", $ln["ArtFamilleEnu"]);
            $this->traiteCat("Categorie", $ln["ArtCategEnu"]);
            $this->traiteCat("Nature", $ln["ArtNatureEnu"]);
            $this->traiteCat("Collection", $ln["ArtCollectEnu"]);


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

    function getCatIDByNom($nom, $parent = "2", $lien = "parent") {
        if (isset($this->cache['catIdByNom'][$parent][$lien][$nom]))
            return $this->cache['catIdByNom'][$parent][$lien][$nom];
        else {
            if($lien == "parent")
                $sql = $this->db->query("SELECT rowid FROM `llx_categorie` WHERE `type` = 0 AND `label` LIKE '" . $nom . "' AND fk_parent = ".$parent);
            else
                $sql = $this->db->query("SELECT rowid  FROM `" . MAIN_DB_PREFIX . "view_categorie_all` WHERE `leaf` LIKE  '" . addslashes($nom) . "' AND id_subroot = " . $parent); //TODO rajout de type
            if ($this->db->num_rows($sql) < 1)
                return 0;
            else {
                $ln = $this->db->fetch_object($sql);
                $this->cache['catIdByNom'][$parent][$lien][$nom] = $ln->rowid;
                return $ln->rowid;
            }
        }
        
    }

    function traiteCat($grandeCat, $cat) {
        $grCatId = $this->getCatIDByNom($grandeCat);
        if ($grCatId < 1)
            die("Grande Famille " . $grandeCat . " introuvable");
        else {
            $catId = array();
            if ($cat == "" || $cat == "  " || $cat == " " || $cat == "  ")
                $cat = "A catégoriser";
            $catTmp = $this->getCatIDByNom($cat, $grCatId, "racine");
            if ($catTmp < 1) {
                $catId[] = $this->createCat($cat, $grCatId);
            } else {
                $catId[] = $catTmp;
                while ($catMere = $this->getCatMere($catTmp) AND $catMere != $grCatId) {
                    if ($catMere != $grCatId)
                        $catId[] = $catMere;
                    $catTmp = $catMere;
                }
            }
            if(!$this->testCat($catId, $grCatId))
                $this->updateProdCat($catId, $grCatId);
        }
    }
    
    function getAllCat(){
        $this->allCatProd = array();
        $sql = $this->db->query("SELECT fk_categorie FROM " . MAIN_DB_PREFIX . "categorie_product WHERE fk_product = ".$this->object->id);
        while($result = $this->db->fetch_object($sql))
                $this->allCatProd[] = $result->fk_categorie;
    }
    
    function testCat($catId, $fk_parent){
        if(!isset($this->cache['listSousCat'][$fk_parent])){
            $sql100 = $this->db->query("SELECT rowid FROM `" . MAIN_DB_PREFIX . "view_categorie` WHERE `id_subroot` = " . $fk_parent . "");
            while($result = $this->db->fetch_object($sql100))
                $this->cache['listSousCat'][$fk_parent][] = $result->rowid;
        }
        
        
        $sql = $this->db->query("SELECT * FROM " . MAIN_DB_PREFIX . "categorie_product WHERE  fk_categorie IN (".implode(",", $this->cache['listSousCat'][$fk_parent]).") AND fk_product = " . $this->object->id. " AND fk_categorie NOT IN (".implode(", ",$catId).")");
        if($this->db->num_rows($sql) > 0)//Cat a suppr
            return 0;
        foreach($catId as $cat)
            if(!in_array($cat, $this->allCatProd))
                    return 0; //Car a ajouter
        return 1;
    }

    function getCatMere($id) {
        $sql = $this->db->query("SELECT `fk_parent` FROM `llx_categorie` WHERE `rowid` = " . $id);
        if ($this->db->num_rows($sql) > 0) {
            $ln = $this->db->fetch_object($sql);
            return $ln->fk_parent;
        }
    }

    function createCat($cat, $fk_parent) {
        $sql = $this->db->query("INSERT INTO " . MAIN_DB_PREFIX . "categorie (label, type, fk_parent) VALUES ('" . addslashes($cat) . "', 0, " . $fk_parent . ") ");
        $id = $this->db->last_insert_id($sql);
        if(isset($this->cache['listSousCat'][$fk_parent]))
            $this->cache['listSousCat'][$fk_parent][] = $id;
        return $id;
    }

    function updateProdCat($catId, $fk_parent) {
        echo "update cat prod ".$this->object->id."<br/>";
        $this->db->query("DELETE FROM " . MAIN_DB_PREFIX . "categorie_product WHERE  fk_categorie IN (SELECT rowid FROM `" . MAIN_DB_PREFIX . "view_categorie` WHERE `id_subroot` = " . $fk_parent . ") AND fk_product = " . $this->object->id);
        foreach ($catId as $cat) {
            $this->db->query("INSERT INTO " . MAIN_DB_PREFIX . "categorie_product (fk_categorie, fk_product) VALUES (" . $cat . "," . $this->object->id . ")");
        }
    }

}
