<?php

require_once DOL_DOCUMENT_ROOT . "/synopsistools/class/importExport/import8sens.class.php";

class importStock extends import8sens {

    public function __construct($db) {
        parent::__construct($db);
        $this->path = $this->path . "stock/";
        $this->prod = new Product($this->db);
        $this->entrepot = new Entrepot($this->db);
    }
    
//    CONST CHAMP_REF = 'ArdGArtCode';
//    CONST CHAMP_ENTREPOT = 'ArdGDepCode';
//    CONST CHAMP_STOCK = 'ArdStk';
    CONST CHAMP_REF = 'ArtCode';
    CONST CHAMP_ENTREPOT = 'DepCode';
    CONST CHAMP_STOCK = 'OpeStkCumul';
    CONST CHAMP_PA = 'OpePA';
    
    private $entrepotNottoye = array();

    public function go() {
        parent::go();
    }

    function traiteLn($ln) {
        if ($ln[self::CHAMP_ENTREPOT] != "" && $ln[self::CHAMP_REF] != "") {
            if (isset($this->tabCache['prod'][$ln[self::CHAMP_REF]])) {
                $this->prodId = $this->tabCache['prod'][$ln[self::CHAMP_REF]];
            } else {
                $this->prodId = 0;
                $sql = $this->db->query("SELECT rowid FROM `llx_product` WHERE `ref` = '" . $ln[self::CHAMP_REF] . "'");
                if ($this->db->num_rows($sql) > 0) {
                    $result = $this->db->fetch_object($sql);
                    $this->prodId = $result->rowid;
                }
                $this->tabCache['prod'][$ln[self::CHAMP_REF]] = $this->prodId;
            }
            if (isset($this->tabCache['entrepot'][$ln[self::CHAMP_ENTREPOT]])) {
                $this->entrepotId = $this->tabCache['entrepot'][$ln[self::CHAMP_ENTREPOT]];
            } else {
                $this->entrepotId = 0;
                if ($this->entrepot->fetch('', $ln[self::CHAMP_ENTREPOT]))
                    $this->entrepotId = $this->entrepot->id;
                else
                    $this->error("2entrepot introuvable " . $ln[self::CHAMP_ENTREPOT]);

                $this->tabCache['entrepot'][$ln[self::CHAMP_ENTREPOT]] = $this->entrepotId;
            }

            $newStock = round(str_replace(",", ".", $ln[self::CHAMP_STOCK]));


            if ($this->prodId < 1){
                if($newStock != 0)
                    $this->error("Prod introuvable " . $ln[self::CHAMP_REF]);
            }
            elseif ($this->entrepotId < 1)
                $this->error("entrepot introuvable " . $ln[self::CHAMP_ENTREPOT]);
            else {
                //gestion des pa
                if(isset($ln[self::CHAMP_PA])){
                    $this->db->query("UPDATE `llx_product` SET pmp= '".str_replace(",", ".", $ln[self::CHAMP_PA])."' WHERE rowid = ".$this->prodId);
                    //netoyage de l'entrepot si pas fait
                    if(!isset($this->entrepotNottoye[$this->entrepotId])){
                        $this->db->query("DELETE FROM `llx_product_stock` WHERE `fk_entrepot` = ".$this->entrepotId);
                        $this->entrepotNottoye[$this->entrepotId] = true;
                    }
                }
                    
                
                
                
                
                $sql = $this->db->query("SELECT reel FROM `llx_product_stock` WHERE `fk_product` = " . $this->prodId . " AND `fk_entrepot` = " . $this->entrepotId);
                $actuel = 0;
                if ($this->db->num_rows($sql) == 1) {
                    $result = $this->db->fetch_object($sql);
                    $actuel = $result->reel;
                    $this->tabResult['connue'] ++;
                    if ($actuel != $newStock) {
                        $this->db->query("UPDATE `llx_product_stock` SET `reel`= '" . $newStock . "' WHERE `fk_product` = " . $this->prodId . " AND `fk_entrepot` = " . $this->entrepotId);
                        $this->tabResult['modifier'] ++;
                    }
                } else {
                    $this->tabResult['creer'] ++;
                    $this->db->query("INSERT INTO `llx_product_stock`(`fk_product`, `fk_entrepot`, `reel`) VALUES ('" . $this->prodId . "','" . $this->entrepotId . "','" . $newStock . "')");
                }
                
                
            }
        }
    }

}
