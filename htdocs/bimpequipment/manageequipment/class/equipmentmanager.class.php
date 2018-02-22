<?php

include_once DOL_DOCUMENT_ROOT . '/bimpequipment/manageequipment/class/lignepanier.class.php';
include_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

class EquipmentManager {

    private $db;
    public $errors;

    function __construct($db) {
        $this->db = $db;
        $this->errors = array();
    }

    function getProductFromEntrepot($entrepotId, $idProd = null) {
        $sql = 'SELECT reel';
        $sql.= ' FROM ' . MAIN_DB_PREFIX . 'product_stock';
        $sql.= ' WHERE fk_entrepot=' . $entrepotId;
        $sql.= (isset($idProd)) ? ' AND fk_product=' . $idProd : '';

        $result = $this->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $prodQty = $obj->reel;
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
                $prodSerial[] = $obj->serial;
            }
        }
        if (!$result) {
            $this->errors[] = "La requête SQL pour la recherche des équipements a échouée";
        }

        return $prodSerial;
    }

    function equipmentIsInEntrepot($serial, $entrepotId) {
        $sql = 'SELECT e_place.position as position';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_equipment as e';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'be_equipment_place as e_place ON e.id = e_place.id_equipment';
        $sql .= ' WHERE e_place.id_entrepot=' . $entrepotId;
        $sql .= ' AND e.serial="' . $serial . '"';

        $result = $this->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                return $obj->position;
            }
        }
        return false; // no row found
    }

    /* Called by the interface */

    function getStockAndSerial($entrepotId, $idProd, $serial) {
        $doliProd = new Product($this->db);
        $doliProd->fetch($idProd);
        $this->errors = array_merge($this->errors, $doliProd->errors);
        $position = $this->equipmentIsInEntrepot($serial, $entrepotId);
        if ($position == false && $serial != '') { // équipement connu mais jamais dans cet entrepôt
            $this->errors[] = "Cet équipement n'a jamais été dans cet entrepôt.";
        }
        if ($position == 1 or $position == false) { // equipement existe ou est un produit
            $equipments = $this->getProductSerialFromEntrepot($entrepotId, $idProd);
            $stocks = $this->getProductFromEntrepot($entrepotId, $idProd);
        } else {
            $this->errors[] = "Cet équipement n'est plus dans cet entrepôt.";
        }
        return array(
            'id' => $idProd,
            'stocks' => $stocks,
            'equipments' => $equipments,
            'serial' => $serial,
            'ref' => $doliProd->getNomUrl(1),
            'label' => dol_trunc($doliProd->label, 25),
            'errors' => $this->errors);
    }

    /* Called by the interface */

    function correctrrStock($entrepotId, $products, $user) {
        var_dump($products);
        $now = dol_now();
        $codemove = dol_print_date($now, '%y%m%d%H%M%S');
        $label = 'Inventaire Bimp ' . dol_print_date($now, '%Y-%m-%d %H:%M');
        foreach ($products as $id => $prod) {
            echo $prod['qtyMissing'];
            if ($prod['qtyMissing'] == 0) {
                
            } else {
                $doliProd = new Product($this->db);
                $doliProd->fetch($id);
                if (0 < $prod['qtyMissing']) {
                    $result = $doliProd->correct_stock($user, $entrepotId, $prod['qtyMissing'], 0, $label, 0, $codemove, 'entrepot', $entrepotId);
//                    if ($result == 1)
//                        $change
                } elseif ($prod['qtyMissing'] < 0) {
                    $result = $doliProd->correct_stock($user, $entrepotId, -$prod['qtyMissing'], 1, $label, 0, $codemove, 'entrepot', $entrepotId);
                }
                if ($result == -1)
                    $this->errors = array_merge($this->errors, $doliProd->errors);
            }
        }
        return array('OK' => 'OK', 'errors' => $this->errors);
    }

}
