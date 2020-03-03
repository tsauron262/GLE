<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

class InventoryExpected extends BimpObject {
    
    /**
     * Ne créer pas l'expected ici, juste MAJ
     */
    public function addProductQtyScanned($qty) {
        $init_qty_scanned = (int) $this->getData('qty_scanned');
        $new_qty = $init_qty_scanned + $qty;
        return $this->updateField('qty_scanned', $new_qty);
    }
    
    /**
     * Ne créer pas l'expected ici, juste MAJ
     */
    public function setScannedEquipment($id_equipment) {
        $errors = array();
        $ids_equipments = $this->getData('ids_equipments');

        $new_qty = (int) $this->getData('qty_scanned') + 1;

        // Attendu et pas encore scanné
        if((int) $ids_equipments[$id_equipment] == 0) {
            $ids_equipments[$id_equipment] = 1;
            $this->updateField('ids_equipments', json_encode($ids_equipments));
            $this->updateField('qty_scanned', $new_qty);

        // Attendu mais déjà scanné
        } elseif((int) $ids_equipments[$id_equipment] == 1) {
            $errors[] = "Cet équipement à déjà été scanné";
        }

        return $errors;
    }
//    
//    /**
//    * Appelé lors de l'insertion de ligne de scan
//     */
//    public function manageScanProduct($id_inventory, $id_scan_line){
//
//        $current_line = BimpCache::getBimpObjectInstance($this->module, 'InventoryLine2', (int) $id_scan_line);
//
//        $c_id_wt        = (int) $current_line->getData('fk_warehouse_type');
//        $c_id_product   = (int) $current_line->getData('fk_product');
//        $c_qty          = (int) $current_line->getData('qty');
//
//        $filters =  array(
//            'id_wt' => array(
//                'operator' => '=',
//                'value'    => $c_id_wt
//            ),
//            'id_product' => array(
//                'operator' => '=',
//                'value'    => $c_id_product
//            )
//        );
//
//        $l_expected = $this->getList($filters, null, null, 'id', 'asc', 'array', array('id'));
//       
//
//        // Pas de expected pour ce produit => création
//        if(empty($l_expected)) {
//
//            $new_expected = BimpObject::getInstance($this->module, 'InventoryExpected');
//
//            $errors = array_merge($errors, $new_expected->validateArray(array(
//                'id_inventory'   => $id_inventory,
//                'id_wt'          => $c_id_wt,
//                'id_product'     => $c_id_product,
//                'ids_equipments' => (array) array(), 
//                'qty'            => 0, // On en attendais pas sinon il serait déjà créer
//                'qty_scanned'    => $c_qty,
//                'serialisable'   => 0
//            )));
//
//            $errors = array_merge($errors, $new_expected->create());
//
//        // Il existe un expected
//        } else {
//            $errors = array_merge($errors, $this->fetch($l_expected[0]['id']));
//            $errors = array_merge($errors, $this->addProductQtyScanned($c_qty));
//        }
//        
//        return $errors;
//    }
//    
//    
//    /**
//    * Appelé lors de l'insertion de ligne de scan
//     */
//    public function manageScanEquipment($id_inventory, $id_scan_line){
//
//        $current_line = BimpCache::getBimpObjectInstance($this->module, 'InventoryLine2', (int) $id_scan_line);
//
//        $c_id_wt        = (int) $current_line->getData('fk_warehouse_type');
//        $c_id_product   = (int) $current_line->getData('fk_product');
//        $c_qty          = (int) $current_line->getData('qty');
//
//        $filters =  array(
//            'id_wt' => array(
//                'operator' => '=',
//                'value'    => $c_id_wt
//            ),
//            'id_product' => array(
//                'operator' => '=',
//                'value'    => $c_id_product
//            )
//        );
//
//        $l_expected = $this->getList($filters, null, null, 'id', 'desc', 'array', array('id'));
//
//
//        // Pas de expected pour ce produit => création
//        if(empty($l_expected)) {
//
//            $new_expected = BimpObject::getInstance($this->module, 'InventoryExpected');
//
//            $errors = array_merge($errors, $new_expected->validateArray(array(
//                'id_inventory'   => $id_inventory,
//                'id_wt'          => $c_id_wt,
//                'id_product'     => $c_id_product,
//                'ids_equipments' => (array) array(), 
//                'qty'            => 0, // On en attendais pas sinon il serait déjà créer
//                'qty_scanned'    => $c_qty,
//                'serialisable'   => 1
//            )));
//
//            $errors = array_merge($errors, $new_expected->create());
//
//        // Il existe un expected
//        } else {
//            $errors = array_merge($errors, $this->fetch($l_expected[0]['id']));
//            $errors = array_merge($errors, $this->addProductQtyScanned($c_qty));
//        }
//        
//        return $errors;
//        
//    }
    
    public function renderEquipments() {
        
        $ids_equipments = $this->getData('ids_equipments');
        
        $html = '';
        
        foreach ($ids_equipments as $id_equipment => $code_scan) {
            
            $eq = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
            
            if((int) $code_scan == 0)
                $html .= 'Attendu ';
            elseif((int) $code_scan == 1)
                $html .= 'Scanné  ';
            
            $html .= $eq->getNomUrl();
            $html .= '<br/>';
            
        }

        return $html;
        
    }
    
}