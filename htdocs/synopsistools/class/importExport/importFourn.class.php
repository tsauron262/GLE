<?php

require_once DOL_DOCUMENT_ROOT . "/synopsistools/class/importExport/import8sens.class.php";

class importFourn extends import8sens {

    public function __construct($db) {
        parent::__construct($db);
        $this->path .= "fournisseur/";
    }

    function traiteLn($ln) {
        $this->tabResult["total"] ++;
        $sql = $this->db->query("SELECT rowid FROM `llx_societe` WHERE `code_fournisseur` LIKE '".$ln['FouCode']."'");
        if($this->db->num_rows($sql) < 1){
            $sql = $this->db->query("SELECT rowid FROM `llx_societe` WHERE `code_fournisseur` LIKE '"."IM".$ln['FouCode']."'");
        }
        
        if($this->db->num_rows($sql) > 0){
            $lnS = $this->db->fetch_object($sql);
            $this->updateFourn($lnS->rowid, $ln);
        }
        else{
            if(($ln['FouIsSupp'] != "X" && $ln['FouIsSleep'] != "X") ? "1" : "0")
                $this->updateFourn($this->addFourn($ln), $ln);
        }
    }

    function addFourn($ln) {
        echo "<br/>addFourn";
        global $user;
        $fourn = new Societe($this->db);
        $fourn->code_fournisseur = $ln['FouCode'];
        $fourn->name = $ln['FouLib'];
        $fourn->fournisseur = 1;
        $id = $fourn->create($user);

        if ($fourn->error != "") {
            $this->error($fourn->error);
            return -1;
        }
        if ($id < 1) {
            $this->error("Impossible de créer le fourn ".print_r($ln,1));
            return -1;
        }
        if (count($fourn->errors) > 0) {
            $this->error($fourn->errors);
            return -1;
        }
        $this->tabResult["creer"] ++;
        return $id;
    }

    function updateFourn($idGle, $ln) {
        global $user;
        if ($idGle > 0) {
            $this->update = false;
            $this->object = new Societe($this->db);
            $this->object->fetch($idGle);
//            $this->object->fetch_optionals();
//
            $this->ident = $this->object->ref;
//
            $this->traiteChamp("fournisseur", 1);
            $this->traiteChamp("idprof2", str_replace(array(" ","."), "", $ln['FouSIRET']));
            $this->traiteChamp("status", ($ln['FouIsSupp'] != "X" && $ln['FouIsSleep'] != "X") ? "1" : "0");
            $adress = "";
            if($ln["FouFAdrRue1"] != "")
                $adress = $ln["FouFAdrRue1"]."\n";
            if($ln["FouFAdrRue2"] != "")
                $adress = $ln["FouFAdrRue2"]."\n";
            if($ln["FouFAdrRue3"] != "")
                $adress = $ln["FouFAdrRue3"]."\n";
            $this->traiteChamp("address", $adress);
            $this->traiteChamp("zip", $ln['FouFAdrZip']);
            $this->traiteChamp("town", $ln['FouFAdrCity']);
            $this->traiteChamp("phone", str_replace(array(" ","."), "", $ln['FouGMocTel']));
            $this->traiteChamp("email", $ln['FouGMocMail']);
            
            
            
//            $this->traiteChamp("tva_tx", $ln['ArtGTaxTaux'], true);
//            $this->updatePrice = $this->update;
//            $this->update = false;
//
//
////            $this->traiteChamp("options_serialisable", ($ln['ArtStkNuf'] == "N° de série"));
//            $this->traiteChamp("options_serialisable", 0);
//
//
//
//            $this->traiteChamp("status_buy", ($ln['ArtIsSupp'] != "X" && $ln['ArtIsSleep'] != "X") ? "1" : "0");
//
//
//            $this->traiteChamp("label", $ln['ArtLib']);
//            $this->traiteChamp("description", $ln['ArtLib']);
//            $this->traiteChamp("ref", $ln['ArtCode']);
//            $this->traiteChamp("import_key", $ln['ArtID']);
//
//            $this->traiteCat("Gamme", $ln["ArtGammeEnu"]);
//            $this->traiteCat("Famille", $ln["ArtFamilleEnu"]);
//            $this->traiteCat("Categorie", $ln["ArtCategEnu"]);
//            $this->traiteCat("Nature", $ln["ArtNatureEnu"]);
//            $this->traiteCat("Collection", $ln["ArtCollectEnu"]);
//
//
//            if ($this->updatePrice) {
//                $this->object->updatePrice($this->object->price, 'HT', $user, $this->object->tva_tx);
//                $this->tabResult["modifier"] ++;
//            }
            if ($this->update) {
            echo "<br/>update ".$idGle;
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
            $this->updateFournCat($catId, $grCatId);
        }
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
        return $this->db->last_insert_id($sql);
    }

    function updateFournCat($catId, $fk_parent) {
        $this->db->query("DELETE FROM " . MAIN_DB_PREFIX . "categorie_product WHERE  fk_categorie IN (SELECT rowid FROM `" . MAIN_DB_PREFIX . "view_categorie` WHERE `id_subroot` = " . $fk_parent . ") AND fk_product = " . $this->object->id);
        foreach ($catId as $cat) {
            $this->db->query("INSERT INTO " . MAIN_DB_PREFIX . "categorie_product (fk_categorie, fk_product) VALUES (" . $cat . "," . $this->object->id . ")");
        }
    }

}
