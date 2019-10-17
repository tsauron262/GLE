<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimpequipment/objects/BE_Place.class.php';

class InventoryWarehouse extends BimpDolObject {
    
    public static $types = array(
        1 => 'Client',
        2 => 'En Stock',
        3 => 'Utilisateur',
        4 => 'Champ libre',
        5 => 'En Présentation',
        6 => 'Vol',
        7 => 'Matériel de prêt',
        8 => 'SAV',
        9 => 'Utilisation interne'
    );

    public static function getAllWarehouseAndType() {
        global $db;
        $warehouse_type = array();
        
        $sql = 'SELECT rowid, ref';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'entrepot';

        $result = $db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $db->fetch_object($result)) {
                foreach(BE_Place::$entrepot_types as $type) {
                    $key = $obj->rowid . '_' . $type;
                    $text = $obj->ref . ' / ' . BE_Place::$types[$type];
                    $warehouse_type[$key] = $text;
                }
            }
        }
        
        return $warehouse_type;
    }
    
    public function getProductStock($filter_products = false) {
        $products = array();
        
        if((int) $this->getData('type') == BE_Place::BE_PLACE_ENTREPOT) {
            $sql  = 'SELECT fk_product AS id_product, reel AS qty';
            $sql .= ' FROM      ' . MAIN_DB_PREFIX . 'product_stock AS ps';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'product_extrafields AS pe ON pe.fk_object=ps.fk_product';
            $sql .= ' WHERE ps.fk_entrepot=' . $this->getData('fk_warehouse');
            $sql .= ' AND (serialisable=0 OR serialisable IS NULL)';
            if(is_array($filter_products))
                $sql .= ' AND fk_product IN(' . implode(',', array_keys($filter_products)) . ')';
        } else {
            $sql  = 'SELECT pp.id_product AS id_product, pp.qty AS qty';
            $sql .= ' FROM      ' . MAIN_DB_PREFIX . 'product p';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'product_extrafields ef  ON p.rowid        = ef.fk_object';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'be_package_product  pp  ON pp.id_product  = p.rowid';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'be_package_place    ppl ON ppl.id_package = pp.id_package';
            $sql .= ' WHERE p.fk_product_type = 0';
            $sql .= ' AND (';
            $sql .= '     (ef.serialisable != 1 OR ef.serialisable is NULL)';
            $sql .= '     AND p.rowid IN (SELECT DISTINCT pp.id_product FROM ' . MAIN_DB_PREFIX . 'be_package_product pp WHERE pp.id_product = p.rowid)';
            $sql .= ' ) AND (';
            $sql .= '    (ef.serialisable = 0 OR ef.serialisable IS NULL)';
            $sql .= '    AND ppl.position = 1 AND ppl.type IN (' . $this->getData('type') . ')';
            $sql .= ' ) AND (';
            $sql .= ' (ef.serialisable = 0 OR ef.serialisable IS NULL)';
            $sql .= '     AND ppl.position = 1 AND ppl.id_entrepot IN (' . $this->getData('fk_warehouse') . ')';
            $sql .= ' )';
            if(is_array($filter_products))
                $sql .= ' AND p.rowid    IN(' . implode(',', array_keys($filter_products)) . ')';
        }

        $result = $this->db->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->db->fetch_object($result)) {
                $products[(int) $obj->id_product] = (int) $obj->qty;
            }
        }
        
        return $products;
    }
    
    public function getProductScanned($filter_products = false) {
        $products = array();
        
        $sql  = 'SELECT fk_product AS id_product, SUM(qty) AS sum';
        $sql .= ' FROM      ' . MAIN_DB_PREFIX . 'bl_inventory_det_2 AS ps';
        $sql .= ' WHERE fk_inventory=' . $this->getData('fk_inventory');
        $sql .= ' AND fk_warehouse_type=' . $this->getData('id');
        $sql .= ' AND (fk_equipment=0 OR fk_equipment IS NULL)';
        $sql .= ' GROUP BY fk_product';
        if(is_array($filter_products))
            $sql .= ' AND fk_product IN(' . implode(',', array_keys($filter_products)) . ')';

        $result = $this->db->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->db->fetch_object($result)) {
                $products[(int) $obj->id_product] = (int) $obj->sum;
            }
        }
        
        return $products;
    }
    
    public function getEquipmentStock() {
        $equipments = array();

        // autre
        $sql  = 'SELECT DISTINCT(e.id) AS id_equipment';
        $sql .= ' FROM      ' . MAIN_DB_PREFIX . 'be_equipment e';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'be_equipment_place AS epl ON epl.id_equipment = e.id';
        $sql .= ' WHERE  epl.position = 1 ';
        $sql .= ' AND epl.id_entrepot=' . $this->getData('fk_warehouse');
        $sql .= ' AND epl.type=' . $this->getData('type');
        $sql .= ' AND e.id_package=0';
//        if($this->getData('date_opening'))
//            $sql .= ' AND p.date < "'.$this->getData('date_opening').'"';
        $result = $this->db->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->db->fetch_object($result)) {
                $equipments[(int) $obj->id_equipment] =  (int) $obj->id_equipment;
            }
        }
        
        $sql  = 'SELECT DISTINCT(e.id) AS id_equipment';
        $sql .= ' FROM      ' . MAIN_DB_PREFIX . 'be_equipment e ';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'be_equipment_place AS epl ON epl.id_equipment = e.id';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'be_package_place AS ppl ON ppl.id_package = e.id_package';
        $sql .= ' WHERE (ppl.position = 1 AND ppl.type=' . $this->getData('type') . ')';
        $sql .= ' AND (ppl.id_entrepot=' . $this->getData('fk_warehouse') . '';
        $sql .= ' OR (epl.position = 1 AND epl.id_entrepot=' . $this->getData('fk_warehouse') . '))';
        
//        die($sql);
        $result = $this->db->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->db->fetch_object($result)) {
                $equipments[(int) $obj->id_equipment] =  (int) $obj->id_equipment;
            }
        }
        
        
        
        return $equipments;
    }

    public function getEquipmentScanned() {
        $equipments = array();
        
        $sql  = 'SELECT ps.fk_equipment AS id_equipment';
        $sql .= ' FROM      ' . MAIN_DB_PREFIX . 'bl_inventory_det_2 AS ps';
        $sql .= ' WHERE fk_inventory=' . $this->getData('fk_inventory');
        $sql .= ' AND fk_warehouse_type=' . $this->getData('id');
        $sql .= ' AND fk_equipment > 0';

        $result = $this->db->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->db->fetch_object($result)) {
                $equipments[(int) $obj->id_equipment] = (int) $obj->id_equipment;
            }
        }
        
        return $equipments;        
    }
    
    public function renderName() {
        $html = '';
        $html .= self::getEntrepotRef($this->getData('fk_warehouse'));
        $html .= ' - ';
        $html .= self::$types[$this->getData('type')];
        return $html;
    }
    
    public static function getEntrepotRef($fk_warehouse) {
        global $db;
        
        $sql = 'SELECT ref';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'entrepot';
        $sql .= ' WHERE rowid=' . $fk_warehouse;

        $result = $db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            $obj = $db->fetch_object($result);
            return $obj->ref;
        }
        return "Entrepôt " . $fk_warehouse . " non définit";
    }
    
}