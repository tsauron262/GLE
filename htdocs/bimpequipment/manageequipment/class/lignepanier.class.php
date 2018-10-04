<?php

class LignePanier {

    private $db;
    public $prodId = 0;
    public $serial = "";
    public $equipmentId = 0;
    public $entrepotId = 0;
    public $error = "";

    function __construct($db) {
        $this->db = $db;
    }

    function isProduct($ref) {
        $sql = 'SELECT rowid';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'product';
        $sql .= ' WHERE ref="' . $ref . '"';
        $sql .= ' OR ref="' . str_replace("/", "_", $ref) . '"';
        $sql .= ' OR ref LIKE "%' . $ref . '"';
        $sql .= ' OR ref LIKE "%' . str_replace("/", "_", $ref) . '"';
        $sql .= ' OR barcode="' . $ref . '"';

        $result = $this->db->query($sql);
        if ($result and $this->db->num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $this->prodId = $obj->rowid;
                return true;
            }
        }
        return false;
    }

    function isEquipment($serial) {
        $sql = 'SELECT id, id_product, serial';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_equipment';
        $sql .= ' WHERE serial="' . $serial . '" || concat("S", serial)="' . $serial . '"';

        $result = $this->db->query($sql);
        if ($result and $this->db->num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $this->equipmentId = $obj->id;
                $this->prodId = $obj->id_product;
                $this->serial = $obj->serial;
                return true;
            }
        }
        return false;
    }

    function fetchProd($prodId, $entrepotId) {
        $this->prodId = $prodId;
        $this->entrepotId = $entrepotId;
    }

    function check($entree, $entrepotId) {
        $this->entrepotId = $entrepotId;
        if (!$this->isProduct($entree)) {
            if (!$this->isEquipment(GETPOST('ref'))) {
                $this->error .= "Produit inconnu";
                return false;
            }
        } else if ($this->isSerialisable()) {
            $this->error .= "Veuillez scanner le numéro de série au lieu de la référence.";
        }
    }

    function isSerialisable() {
        $sql = 'SELECT serialisable';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'product_extrafields';
        $sql .= ' WHERE fk_object=' . $this->prodId;

        $result = $this->db->query($sql);
        if ($result and $this->db->num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                if ($obj->serialisable == 1)
                    return true;
                else
                    return false;
            }
        }
        return false;
    }

    function getError() {
        return array('error' => $this->error);
    }

    function getInfo() {
        if ($this->prodId > 1){
        $prod = new product($this->db);
        $prod->fetch($this->prodId);
            $label = $prod->label;
            $url = $prod->getNomUrl(1);
        }
        else{
            $label = "";
            $url = "";
        }

        return array('id' => $this->prodId, 'isEquipment' => ($this->equipmentId > 0), 'stock' => $this->checkStock(), 'label' => dol_trunc($label, 30), 'refUrl' => $url, 'serial' => $this->serial, 'error' => $this->error);
    }

    function checkStock() {
        if ($this->equipmentId > 0) {
            $stock = $this->checkStockEquipment();
            $is_reserved = $this->checkReservation();
            if ($is_reserved)
                $this->error .= "Cet équipement est déjà réservé.";
        } else {
            $stock = $this->checkStockProd();
        }
        return ($stock > 0) ? ($is_reserved ? 0 : $stock) : 0;
    }

    function checkStockEquipment() {

        $sql = 'SELECT id';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_equipment_place';
        $sql .= ' WHERE id_equipment="' . $this->equipmentId . '"';
        $sql .= ' AND position=1';
        $sql .= ' AND id_entrepot=' . $this->entrepotId;


        $result = $this->db->query($sql);
        if ($result and $this->db->num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                return 1;
            }
        }
        return 0;
    }

    /**
     * @return true if the equipment is reserved
     */
    private function checkReservation() {
        
        $sql = 'SELECT id';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'br_reservation';
        $sql .= ' WHERE id_equipment="' . $this->equipmentId . '"';
        $sql .= ' AND status < 300';

        $result = $this->db->query($sql);
        if ($result and $this->db->num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                return 1;
            }
        }
        return 0;
    }

    function checkStockProd() {

        $sql = 'SELECT reel';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'product_stock';
        $sql .= ' WHERE fk_product =' . $this->prodId;
        $sql .= ' AND   fk_entrepot=' . $this->entrepotId;

        $result = $this->db->query($sql);
        if ($result and $this->db->num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $qty = $obj->reel;
            }
            return $qty;
        } else {
            return 0;
        }
    }

}
