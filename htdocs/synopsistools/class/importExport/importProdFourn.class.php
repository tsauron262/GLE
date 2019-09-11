<?php

require_once DOL_DOCUMENT_ROOT . "/synopsistools/class/importExport/import8sens.class.php";

class importProdFourn extends import8sens {

    public function __construct($db) {
        parent::__construct($db);
        $this->path .= "prodFourn/";
    }
    
    private $cache = array("prod"=>array(), "fourn"=> array());
    
    function getProd($nom){
        if($nom == "")
            return 0;
        if(isset( $this->cache['prod'][$nom]))
            return $this->cache['prod'][$nom];
        else{
            $sql1 = $this->db->query("SELECT rowid FROM ".MAIN_DB_PREFIX."product WHERE ref LIKE '".$nom."'");
            if($this->db->num_rows($sql1) > 0){
                $ln1 = $this->db->fetch_object($sql1);
                 $this->cache['prod'][$nom] = $ln1->rowid;
                 return  $this->cache['prod'][$nom];
            }
        }
    }
    
    function getFourn($nom){
        if(isset( $this->cache['fourn'][$nom]))
            return $this->cache['fourn'][$nom];
        else{
            $sql1 = $this->db->query("SELECT rowid FROM `llx_societe` WHERE `code_fournisseur` LIKE '".$nom."'");
            if($this->db->num_rows($sql1) > 0){
                $ln1 = $this->db->fetch_object($sql1);
                 $this->cache['fourn'][$nom] = $ln1->rowid;
                 return  $this->cache['fourn'][$nom];
            }
        }
    }

    function traiteLn($ln) {
        $ln['ProPrixBase'] = str_replace(",",".", $ln['ProPrixBase']);
        
        $this->tabResult["total"] ++;
        $prodId = $this->getProd($ln['ProGArtCode']);
        if($prodId > 0){
            $fournId = $this->getFourn($ln['ProGFouCode']);
            if($fournId > 0){
                $sql3 = $this->db->query("SELECT rowid, ref_fourn, price, unitprice, quantity, tva_tx, fk_product FROM `llx_product_fournisseur_price` WHERE  `fk_soc` = ".$fournId." AND ref_fourn LIKE '".$ln['ProCode']."'");
                $ok = false;
                $existeDeja = "non";
                while ($ln3 = $this->db->fetch_object($sql3)){
                    if($ln3->fk_product == $prodId){
                        if($ln['Pro1TaxTaux'] == "")
                            $ln['Pro1TaxTaux'] = 0;
                        $ok = true;
                        $achanger = "";
                        if($ln3->ref_fourn != $ln['ProCode'])
                            $achanger .= " ref";
                        if($this->traiteNumber($ln3->unitprice) != $this->traiteNumber($ln['ProPrixBase'])) 
                            $achanger .= " prix";
                        if($this->traiteNumber($ln3->price) != $this->traiteNumber($ln['ProPrixBase']) ) 
                            $achanger .= " prix2";
                        if($ln3->quantity != 1 ) 
                            $achanger .= " qty";
                        if($this->traiteNumber($ln3->tva_tx) != $this->traiteNumber($ln['Pro1TaxTaux'])) 
                            $achanger .= " tva";
                            
                        if($achanger != ""){
                            echo "updatePrice ".$achanger."<br/>";
                            $this->updatePrice($ln3->rowid, $ln);
                        }
                    }
                    else{
                        $existeDeja = $ln3->fk_product;
                    }
                }
                if(!$ok){
                    if($existeDeja != "non"){
                        mailSyn2("Ref fourn en double", "debugerp@bimp.fr,f.poirier@bimp.fr", "gle@bimp.fr", "Bonjour la ref : ".$ln['ProCode']." existe deja dans prodId ".$existeDeja." ne peut donc pas être ajouter a ".$prodId." fournisseurid ".$fournId);
                    }
                    else{
                        $this->addPrice($prodId, $fournId, $ln);
                    }
                }
            }
            else{
                $this->error("Fourn ".$ln['ProdFouCode']." introuvable");
            }
        }
        else{
            if($ln['ProGArtCode'] != "")
                $this->alert("Prod ".$ln['ProGArtCode']." introuvable");
        }
        
    }

    function addPrice($idP, $idF, $ln) {
        echo "addPrice<br/>";
        $this->db->query("INSERT INTO `llx_product_fournisseur_price`"
                . "(`fk_product`, `fk_soc`, `ref_fourn`,`price`, `quantity`, `tva_tx`, unitprice) VALUES "
                . "(".$idP.",".$idF.",'".$ln['ProCode']."','".$ln['ProPrixBase']."',1,'".$ln['Pro1TaxTaux']."', '".$ln['ProPrixBase']."')");
        return $id;
    }

    function updatePrice($idGle, $ln) {
        global $user;
        $this->db->query("UPDATE `llx_product_fournisseur_price` SET "
                . "ref_fourn = '".$ln['ProCode']."', price = '".$ln['ProPrixBase']."', quantity = 1, tva_tx = '".$ln['Pro1TaxTaux']."', unitprice = '".$ln['ProPrixBase']."' "
                . " WHERE rowid = ".$idGle);
        
    }


}
