<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

class InventoryExpected extends BimpObject {
    
    /**
     * Ne créer pas l'expected ici, juste MAJ
     */
    public function addProductQtyScanned($qty, $scan_line) {
        $errors = array();

        // Cette ligne existe
        if(!is_null($this->id)) {
            $init_qty_scanned = (int) $this->getData('qty_scanned');
            $new_qty = $init_qty_scanned + $qty;
            $this->updateField('qty_scanned', $new_qty);
            
        // Cette ligne n'existe pas => création
        } else {
            $inventory = $scan_line->getParentInstance();
            $wt = $inventory->getMainWT();

            $errors = array_merge($errors, $this->validateArray(array(
                'id_inventory'   => (int)   $inventory->getData('id'),
                'id_wt'          => (int)   $wt->getData('id'),
                'id_package'     => (int)   0,
                'id_product'     => (int)   $scan_line->getData('fk_product'),
                'qty'            => (int)   0,
                'qty_scanned'    => (int)   1,
                'ids_equipments' => (array) array(),
                'serialisable'   => O
            )));
            $errors = array_merge($errors, $this->create());
        }
        
        return $errors;
    }
    
    /**
     * Ne créer pas l'expected ici, juste MAJ
     */
    public function setScannedEquipment($id_equipment, $scan_line) {
        $errors = array();
        $ids_equipments = $this->getData('ids_equipments');

        $new_qty = (int) $this->getData('qty_scanned') + 1;
        
//        echo 'id = '. $id_equipment;
//        print_r($ids_equipments);
//        die('dzafz');

        // Non attendu, l'expected n'existe pas non plus
        if (is_null($ids_equipments)) {
            $inventory = $scan_line->getParentInstance();
            $wt = $inventory->getMainWT();
            
            $errors = array_merge($errors, $this->validateArray(array(
                'id_inventory'   => (int)   $inventory->getData('id'),
                'id_wt'          => (int)   $wt->getData('id'),
                'id_package'     => (int)   0,
                'id_product'     => (int)   $scan_line->getData('fk_product'),
                'qty'            => (int)   0,
                'qty_scanned'    => (int)   1,
                'ids_equipments' => (array) array($id_equipment => 2),
                'serialisable'   => 1
            )));
            $errors = array_merge($errors, $this->create());
            
        // Attendu et pas encore scanné
        } elseif(isset($ids_equipments[$id_equipment]) and (int) $ids_equipments[$id_equipment] == 0) {
            $ids_equipments[$id_equipment] = 1;
            $this->updateField('ids_equipments', json_encode($ids_equipments));
            $this->updateField('qty_scanned', $new_qty);

        // Attendu mais déjà scanné
        } elseif((int) $ids_equipments[$id_equipment] == 1 or $ids_equipments[$id_equipment] == 2) {
            $errors[] = "Cet équipement à déjà été scanné";
            $scan_line->delete();
            
        // Non attendu mais un expected été déjà créer pour ce type de produits
        } elseif(is_array($ids_equipments)) {
            $ids_equipments[$id_equipment] = 2;
            $this->updateField('ids_equipments', json_encode($ids_equipments));
            $this->updateField('qty_scanned', $new_qty);
        }

        return $errors;
    }
    
    
    public function renderEquipments() {
        
        $ids_equipments = $this->getData('ids_equipments');
        
        $html = '';
        
        foreach ($ids_equipments as $id_equipment => $code_scan) {
            
            $eq = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
            
            if((int) $code_scan == 0)
                $html .= 'Non scanné ';
            elseif((int) $code_scan == 1)
                $html .= 'Scanné  ';
            elseif((int) $code_scan == 2)
                $html .= 'En trop  ';
            
            $html .= $eq->getNomUrl();
            $html .= '<br/>';
            
        }

        return $html;
        
    }
    
}