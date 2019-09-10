<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

class InventoryLine extends BimpObject {

    public function checkInput($input, &$id_product, &$id_equipment) {
        $errors = array();
        $is_product = $this->isProduct($input, $id_product);
        $is_equipment = $this->isEquipment($input, $id_equipment, $id_product);
        if (!$is_equipment and ! $is_product)
            $errors[] = "Produit inconnu";
        else if (!$is_equipment and $this->isSerialisable($id_product))
            $errors[] = "Veuillez scanner le numéro de série au lieu de la référence.";
        return $errors;
    }

    public function isProduct($search, &$id_product) {
        $sql = 'SELECT rowid';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'product';
        $sql .= ' WHERE ref="' . $search . '"';
        $sql .= ' OR ref="' . str_replace("/", "_", $search) . '"';
        $sql .= ' OR ref LIKE "%' . $search . '"';
        $sql .= ' OR ref LIKE "%' . str_replace("/", "_", $search) . '"';
        $sql .= ' OR barcode="' . $search . '"';

        $rows = $this->db->executeS($sql, 'array');
        if (!is_null($rows)) {
            foreach ($rows as $row) {
                $id_product = $row['rowid'];
                return true;
            }
        }
        return false;
    }

    public function isEquipment($input, &$id_equipment, &$id_product) {
        $sql = 'SELECT id, id_product';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_equipment';
        $sql .= ' WHERE serial="' . $input . '" || concat("S", serial)="' . $input . '"';

        $result = $this->db->db->query($sql);
        if ($result and $this->db->db->num_rows($result) > 0) {
            while ($obj = $this->db->db->fetch_object($result)) {
                $id_product = $obj->id_product;
                $id_equipment = $obj->id;
//                die($id_product.' aaa '.$sql);
                return true;
            }
        }
        return false;
    }

    public function isSerialisable($id_product) {
        $sql = 'SELECT serialisable';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'product_extrafields';
        $sql .= ' WHERE fk_object=' . $id_product;

        $result = $this->db->db->query($sql);
        if ($result and $this->db->db->num_rows($result) > 0) {
            while ($obj = $this->db->db->fetch_object($result)) {
                if ($obj->serialisable == 1)
                    return true;
                else
                    return false;
            }
        }
        return false;
    }

}
