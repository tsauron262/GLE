<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

class InventoryExpected extends BimpObject {
    
    CONST EXPECTED = 0;
    CONST SCANNED = 1;
    CONST EXCESS = 2;

    
    /**
     * Ne créer pas l'expected ici, juste MAJ
     */
    public function addProductQtyScanned($qty, $scan_line) {
        $errors = array();

        // Cette ligne existe
        if(!is_null($this->id)) {
            $new_qty = (int) $this->getData('qty_scanned') + $qty;
            $this->updateField('qty_scanned', $new_qty);
            
            // Si la qty scanned passe à 0 alors que cette ligne était 
            // en excès => on supprime la ligne
            if((int) $this->getData('qty') == 0 and (int) $this->getData('qty_scanned') == 0)
                $errors = BimpTools::merge_array($errors, $this->delete());
            
        // Cette ligne n'existe pas => création
        } else {
            $inventory = $scan_line->getParentInstance();
            $wt = $inventory->getMainWT();

            $errors = array_merge($errors, $this->validateArray(array(
                'id_inventory'   => (int)   $inventory->getData('id'),
                'id_wt'          => (int)   $wt->getData('id'),
                'id_package'     => (int)   $scan_line->getData('fk_package'),
                'id_product'     => (int)   $scan_line->getData('fk_product'),
                'qty'            => (int)   0,
                'qty_scanned'    => (int)   $scan_line->getData('qty'),
                'ids_equipments' => (array) array(),
                'serialisable'   => 0
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
        

        // Non attendu, l'expected n'existe pas non plus
        if (is_null($ids_equipments)) {
            $inventory = $scan_line->getParentInstance();
            $wt = $inventory->getMainWT();
            
            $errors = array_merge($errors, $this->validateArray(array(
                'id_inventory'   => (int)   $inventory->getData('id'),
                'id_wt'          => (int)   $wt->getData('id'),
                'id_package'     => (int)   $inventory->getPackageNouveau(),
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
    
    
    public function renderEquipments($view_code_scan = -1, $display_html = true) {
        
        $ids_equipments = $this->getData('ids_equipments');
        
        $html = '';
        
        $qteScan = 0;
        foreach ($ids_equipments as $id_equipment => $code_scan) {
            
            $eq = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
            
            global $modeCsv;
            
            if(!$modeCsv)
                $label = $eq->getNomUrl() .  '<br/>';
            else
                $label = $eq->getData('serial') .  '<br/>';
            

            if((int) $code_scan == 0 and $view_code_scan < 1) {
                if($display_html)
                    $html .= '<span class="error">Non scanné </span> ' . $label;
                else
                    $html .= $label;

            }elseif((int) $code_scan == 1){
                if($view_code_scan == -1 or $view_code_scan ==1){
                    if($display_html)
                        $html .= 'Scanné  ' . $label;
                    else
                        $html .= $label;
                }
                $qteScan++;
                
            }elseif((int) $code_scan == 2) {
                if($view_code_scan == -1 or $view_code_scan ==2){
                    if($display_html)
                        $html .= '<span class="error">En trop </span> ' . $label;
                    else
                        $html .= $label;
                }
                $qteScan++;
            }
        }
        if(count($ids_equipments) > 0 && $this->getData('qty_scanned') != $qteScan){
            $html = '<span class="error">ATTENTION INCOHERENCE DES DONNEE</span>'.$html;
            mailSyn2 ('Incohérence inventaire', 'dev@bimp.fr', null, "Bonjour il y a une onchérence dans la ligne d'exected ".$this->id.' '.$qteScan." num de serie scanné pour ".$this->getData('qty_scanned'));
        }
        return $html ;
        
    }
    
    public function renderValorisation() {
        
        $diff = $this->getData('qty') - $this->getData('qty_scanned');
        $id_prod = $this->getData('id_product');
        $prod = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $id_prod);
        $pa = $prod->getCurrentPaHt();
            
        if(!$this->getData('serialisable')) {
            $valorisation = $pa * $diff;
        } else {
            $valorisation = 0;
            foreach($this->getData('ids_equipments') as $id_equip => $code_scan) {
                
                $equip = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $id_equip);
                $pa_e = (float) $equip->getData('prix_achat');
                if($pa_e < 0.10)
                    $pa_e = $pa;
                
                if($code_scan == 0) {
                    $valorisation += $pa_e;
                } elseif ($code_scan == 2) {
                    $valorisation -= $pa_e;
                }
                
            }
            
        }
        
        return price($valorisation);
    }
    
    public function containEquipment($id_equipment) {
        $errors = array();
        
        $ids_equipments = $this->getData('ids_equipments');
        if(is_null($ids_equipments) or ! is_array($ids_equipments)) {
            $errors[] = "Aucun équipement n'est attendu dans cette ligne";
            return $errors;
        }
        
        foreach($ids_equipments as $c_id_equipment => $code_scan) {
            if((int) $c_id_equipment == (int) $id_equipment)
                return true;
        }
            
        return $errors;
    }
    
    public function unsetScannedEquipment($id_equipment) {
        $errors = array();
        
        $ids_equipments = $this->getData('ids_equipments');
        if(is_null($ids_equipments) or ! is_array($ids_equipments)) {
            $errors[] = "Aucun équipement n'est attendu dans cette ligne";
            return $errors;
        }
        
        
        foreach($ids_equipments as $c_id_equipment => $code_scan) {
            if((int) $c_id_equipment == (int) $id_equipment) {
                $has_change = false;
                if((int) $code_scan == self::SCANNED) {
                    $ids_equipments[$c_id_equipment] = self::EXPECTED;
                    $has_change = true;

                } elseif((int) $code_scan == self::EXCESS) {
                    unset($ids_equipments[$c_id_equipment]);
                    $has_change = true;

                } else
                    $errors = "Cet équipement n'a pas été scanné";
                
                if($has_change) {
                    $this->updateField('ids_equipments', $ids_equipments);
                    $this->updateField('qty_scanned', $this->getData('qty_scanned') - 1);
                    
                    
                    // Si la qty scanned passe à 0 alors que cette ligne était 
                    // en excès => on supprime la ligne
                    if((int) $this->getData('qty') == 0 and (int) $this->getData('qty_scanned') == 0
                            and empty($ids_equipments))
                        $errors = BimpTools::merge_array($errors, $this->delete());
                    
                }
                    
                return $errors;
            }
        }
        
        return $errors;
    }
    
    
}
