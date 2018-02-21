<?php

class EquipmentManager {

    private $db;
    public $errors;

    function __construct($db) {
        $this->db = $db;
        $this->errors = array();
    }

    function getProductFromEntrepot($entrepotId, $idProd = null) {
        $prodQty = array();
        $sql = 'SELECT fk_product, reel';
        $sql.= ' FROM ' . MAIN_DB_PREFIX . 'product_stock';
        $sql.= ' WHERE fk_entrepot=' . $entrepotId;
        $sql.= (isset($idProd)) ? ' AND fk_product=' . $idProd : '';

        $result = $this->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $prodQty[$obj->fk_product] = $obj->reel;
            }
        }
        if (!$result) {
            $this->errors[] = "La requête SQL pour la recherche des produits a échouée";
        }
        return $prodQty;
    }

    function getProductSerialFromEntrepot($entrepotId, $idProd = null) {
        $prodSerial = array();
        $sql = 'SELECT e.serial as serial, e.id_product as id_product';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_equipment as e';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'be_equipment_place as e_place ON e.id = e_place.id_equipment';
        $sql .= ' WHERE e_place.id_entrepot=' . $entrepotId;
        $sql .= ' AND e_place.position=1';
        $sql.= (isset($idProd)) ? ' AND id_product=' . $idProd : '';

        $result = $this->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $prodSerial[$obj->id_product][] = $obj->serial;
            }
        }
        if (!$result) {
            $this->errors[] = "La requête SQL pour la recherche des équipements a échouée";
        }

        return $prodSerial;
    }

    function getStockAndSerial($entrepotId, $idProd) {
        $prods = array();
        if ($idProd == 0) { // not ref or bar code
            $idProd = 
        }
        
        $equipments = $this->getProductSerialFromEntrepot($entrepotId, $idProd);
        if(empty($equipments))
            $prods = $this->getProductFromEntrepot($entrepotId, $idProd);
        return array(
            'products' => $prods,
            'equipments' => $equipments,
            'errors' => $this->errors);
    }

}
