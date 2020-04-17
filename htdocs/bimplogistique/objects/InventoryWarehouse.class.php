<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimpequipment/objects/BE_Place.class.php';

class InventoryWarehouse extends BimpDolObject {
    
    public static $types;
    
    public function __construct($module, $object_name) {
        self::$types = BE_Place::$types;
        parent::__construct($module, $object_name);
    }

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

    
    /**
     * @return product[$id_product] = array('id_package' => $id_package,
     *                                      'qty'        => $qty)
     */
    public function getProductStock($filter_products = 0) {
        $products = array();
        
//        if((is_array($filter_products) and empty($filter_products))
//               OR (int) $this->getData('type') == BE_Place::BE_PLACE_VOL)
//            return $products;

        
        // Récupération dans les stocks
        if((int) $this->getData('type') == BE_Place::BE_PLACE_ENTREPOT) {
            $sql  = 'SELECT fk_product AS id_product, reel AS qty, 0 AS id_package';
            $sql .= ' FROM      ' . MAIN_DB_PREFIX . 'product_stock       AS ps';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'product_extrafields AS pe ON pe.fk_object=ps.fk_product';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'product             AS p  ON p.rowid     =ps.fk_product';
            $sql .= ' WHERE ps.fk_entrepot=' . $this->getData('fk_warehouse');
            $sql .= ' AND (serialisable=0 OR serialisable IS NULL)';
            $sql .= ' AND fk_product_type=0'; // N'est pas un service
            if(is_array($filter_products) and !isset($filter_products['all']))
                $sql .= ' AND fk_product IN(' . implode(',', array_keys($filter_products)) . ')';
            
            $result = $this->db->db->query($sql);
            if ($result and mysqli_num_rows($result) > 0) {
                while ($obj = $this->db->db->fetch_object($result)) {
                    if((int) $obj->qty == 0)
                        continue;

                    if (!isset($products[(int) $obj->id_product]))
                        $products[(int) $obj->id_product] = array();
                        
                    $products[(int) $obj->id_product][] = array('qty' => (int) $obj->qty, 'id_package' => (int) $obj->id_package);
                    
                }
            }
        }
        
        // Récupération dans les package
        $sql  = 'SELECT pp.id_product AS id_product, pp.qty AS qty, pp.id_package AS id_package';
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

        $result = $this->db->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->db->fetch_object($result)) {
                if((int) $obj->qty == 0)
                    continue;

                if (!isset($products[(int) $obj->id_product]))
                    $products[(int) $obj->id_product] = array();

                $products[(int) $obj->id_product][] = array('qty' => (int) $obj->qty, 'id_package' => (int) $obj->id_package);
                
            }
        }

        
        return $products;
    }
    
    public function getProductScanned($filter_products = 0) {
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
            while ($obj = $this->db->db->fetch_object($result))
                $products[(int) $obj->id_product] = (int) $obj->sum;
        }
        
        return $products;
    }
    
    /**
     * 
     * @param type $display 0 => id_equipment
     *                      1 => id_package
     *                      2 => id_product
     * @param array $filter_products filter by id_product in that array
     * @return array
     */
    public function getEquipmentStock($display = 0, $filter_products = 0) {
        $equipments = array();
        
        if(is_array($filter_products) and empty($filter_products))
            return $equipments;

        // autre
        $sql  = 'SELECT DISTINCT(e.id) AS id_equipment, e.id_package AS id_package, e.id_product AS id_product';
        $sql .= ' FROM      ' . MAIN_DB_PREFIX . 'be_equipment e';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'be_equipment_place AS epl ON epl.id_equipment = e.id';
        $sql .= ' WHERE  epl.position = 1 ';
        $sql .= ' AND epl.id_entrepot=' . $this->getData('fk_warehouse');
        $sql .= ' AND epl.type=' . $this->getData('type');
        $sql .= ' AND e.id_package=0';
        if(is_array($filter_products))
            $sql .= ' AND id_product IN(' . implode(',', array_keys($filter_products)) . ')';
        

        $result = $this->db->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->db->fetch_object($result))
                if($display == 0)
                    $equipments[(int) $obj->id_equipment] = (int) $obj->id_equipment;
                elseif($display == 1)
                     $equipments[(int) $obj->id_equipment] = (int) $obj->id_package;
                
                elseif($display == 2) {
                    if(isset($equipments[(int) $obj->id_package][(int) $obj->id_product]))
                        $equipments[(int) $obj->id_package][(int) $obj->id_product][(int) $obj->id_equipment] = 0;
                    else
                        $equipments[(int) $obj->id_package][(int) $obj->id_product] = array((int) $obj->id_equipment => 0);
                }
       }
       
        // Ceux qui sont dans des package sont ceux des mouvements d'inventaire
        if((int) $this->getData('type') == BE_Place::BE_PLACE_VOL)
            return $equipments;
        
        // package
        $sql  = 'SELECT DISTINCT(e.id) AS id_equipment, e.id_package AS id_package, e.id_product AS id_product';
        $sql .= ' FROM      ' . MAIN_DB_PREFIX . 'be_equipment e ';
//        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'be_equipment_place AS epl ON epl.id_equipment = e.id';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'be_package_place AS ppl ON ppl.id_package = e.id_package';
        $sql .= ' WHERE e.id_package > 0';
        $sql .= ' AND ppl.position = 1';
        $sql .= ' AND ppl.type=' . $this->getData('type');
        $sql .= ' AND ppl.id_entrepot=' . $this->getData('fk_warehouse');
        if(is_array($filter_products))
            $sql .= ' AND e.id_product IN(' . implode(',', array_keys($filter_products)) . ')';
        
        
        
//        $sql .= ' WHERE (ppl.position = 1 AND ppl.type=' . $this->getData('type') . ' AND ppl.type!=' .  BE_Place::BE_PLACE_VOL . ')';
//        $sql .= ' AND (ppl.id_entrepot=' . $this->getData('fk_warehouse') . ')';
//        $sql .= ' OR (epl.position = 1 AND epl.id_entrepot=' . $this->getData('fk_warehouse') . ')';
        
        
        $result = $this->db->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->db->fetch_object($result))
                if($display == 0)
                    $equipments[(int) $obj->id_equipment] = (int) $obj->id_equipment;
                elseif($display == 1)
                     $equipments[(int) $obj->id_equipment] = (int) $obj->id_package;
//                elseif($display == 2)
//                     $equipments[(int) $obj->id_equipment] = (int) $obj->id_product;
                elseif($display == 2) {
                    if(isset($equipments[(int) $obj->id_package][(int) $obj->id_product]))
                        $equipments[(int) $obj->id_package][(int) $obj->id_product][(int) $obj->id_equipment] = 0;
                    else
                        $equipments[(int) $obj->id_package][(int) $obj->id_product] = array((int) $obj->id_equipment => 0);
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
        $html .= ' - ' . self::$types[$this->getData('type')];
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
    
    public function getScanExpectedProduct() {
        
        $ret_expected = array();
        
        $expected = BimpCache::getBimpObjectInstance($this->module, 'InventoryExpected');
        
        $filters = array(
            'id_wt'        => (int) $this->id,
            'serialisable' => 0
        );
        
        $return = array('id_package', 'id_product', 'qty', 'qty_scanned');
        
        $l_expected = $expected->getList($filters, null, null, 'id', 'DESC', 'object', $return);
        
        foreach($l_expected as $c_e) {
            
            if(!isset($ret_expected[(int) $c_e->id_package]))
                $ret_expected[(int) $c_e->id_package] = array();
            
            if(!isset($ret_expected[(int) $c_e->id_package][(int) $c_e->id_product]))
                $ret_expected[(int) $c_e->id_package][(int) $c_e->id_product] = array();
            
            $ret_expected[(int) $c_e->id_package][(int) $c_e->id_product] = array(
                'nb_scan' => (int) $c_e->qty_scanned,
                'stock'   => (int) $c_e->qty,
                'diff'    => (int) $c_e->qty_scanned - (int) $c_e->qty
                );
        }
        
        return $ret_expected;
        
    }
    
    public function getScanExpectedEquipment() {
        
        $ret_expected = array();
        
        $expected = BimpCache::getBimpObjectInstance($this->module, 'InventoryExpected');
        
        $filters = array(
            'id_wt'        => (int) $this->id,
            'serialisable' => 1
        );
        
        $return = array('id_package', 'ids_equipments');
        
        $l_expected = $expected->getList($filters, null, null, 'id', 'DESC', 'object', $return);
        
        
        foreach($l_expected as $c_e) {

            $equip_scan = json_decode($c_e->ids_equipments);
            
            foreach ($equip_scan as $id_equipment => $code_scan) {
                $ret_expected[$id_equipment] = array(
                    'code_scan'   => (int) $code_scan, // 0 attendu, 1 scanné, 2 scan sans être attendu
                    'id_package'  => (int) $c_e->id_package,
                    'id_wt'       => (int) $this->id,
                );
            }
            
            
        }

        return $ret_expected;
        
    }
    
}