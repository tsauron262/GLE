<?php

require_once DOL_DOCUMENT_ROOT . "/synopsistools/class/importExport/import8sens.class.php";

class importStock extends import8sens {

    public function __construct($db) {
        parent::__construct($db);
        $this->path = $this->path . "stock/";
        $this->prod = new Product($this->db);
        $this->entrepot = new Entrepot($this->db);
    }

    public function go() {
        parent::go();
    }

    function traiteLn($ln) {
        if ($ln['ArdGDepCode'] != "" && $ln['ArdGArtCode'] != "") {
            if (isset($this->tabCache['prod'][$ln['ArdGArtCode']])) {
                $this->prodId = $this->tabCache['prod'][$ln['ArdGArtCode']];
            } else {
                $this->prodId = 0;
                $sql = $this->db->query("SELECT rowid FROM `llx_product` WHERE `ref` = '" . $ln['ArdGArtCode'] . "'");
                if ($this->db->num_rows($sql) > 0) {
                    $result = $this->db->fetch_object($sql);
                    $this->prodId = $result->rowid;
                }
                $this->tabCache['prod'][$ln['ArdGArtCode']] = $this->prodId;
            }
            if (isset($this->tabCache['entrepot'][$ln['ArdGDepCode']])) {
                $this->entrepotId = $this->tabCache['entrepot'][$ln['ArdGDepCode']];
            } else {
                $this->entrepotId = 0;
                if ($this->entrepot->fetch('', $ln['ArdGDepCode']))
                    $this->entrepotId = $this->entrepot->id;
                else
                    $this->error("2entrepot introuvable " . $ln['ArdGDepCode']);

                $this->tabCache['entrepot'][$ln['ArdGDepCode']] = $this->entrepotId;
            }

            $newStock = round(str_replace(",", ".", $ln['ArdStk']));


            if ($this->prodId < 1){
                if($newStock != 0)
                    $this->error("Prod introuvable " . $ln['ArdGArtCode']);
            }
            elseif ($this->entrepotId < 1)
                $this->error("entrepot introuvable " . $ln['ArdGDepCode']);
            else {
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
