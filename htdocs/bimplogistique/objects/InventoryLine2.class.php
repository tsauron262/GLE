<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

class InventoryLine2 extends BimpObject {

    public function checkInput($input, &$id_product, &$id_equipment/*, &$err_serializable*/) {
        $errors = array();
        if($input == '') {
            $errors[] = "Entrée vide";
            return $errors;
        }
        $is_product = $this->isProduct($input, $id_product);
        $is_equipment = $this->isEquipment($input, $id_equipment, $id_product);
        if (!$is_equipment and ! $is_product)
            $errors[] = "Produit inconnu (c'est peut-être un service)";
        else if (!$is_equipment and $this->isSerialisable($id_product))
            $errors[] = "Veuillez scanner le numéro de série au lieu de la référence.";
        return $errors;
    }

    public function canCreate(){
        return 1;
    }
    
    public function canDelete(){
        global $user;
        
        return (int) $user->rights->bimpequipment->inventory->create;
    }
        
        
    public function isProduct($search, &$id_product) {
        $sql = 'SELECT rowid';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'product';
        $sql .= ' WHERE (';
        $sql .= ' ref="' . $search . '"';
        $sql .= ' OR ref="' . str_replace("/", "_", $search) . '"';
        $sql .= ' OR ref LIKE "%' . $search . '"';
        $sql .= ' OR ref LIKE "%' . str_replace("/", "_", $search) . '"';
        $sql .= ' OR barcode="' . $search . '"';
        $sql .= ')';
        $sql .= ' AND fk_product_type=0';

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
        $sql .= ' WHERE serial="' . $input . '" || concat("S", serial)="' . $input . '" || concat("S", "' . $input . '")=serial ORDER BY id_product DESC';
        
        $result = $this->db->db->query($sql);
        if ($result and $this->db->db->num_rows($result) > 0) {
            while ($obj = $this->db->db->fetch_object($result)) {
                if((int) $obj->id > 0){
                    $id_product = (int) $obj->id_product;
                    $id_equipment = (int) $obj->id;
                    return true;
                }
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
    
    public function isDeletable($force_delete = false, &$errors = array()) {
        global $user;
        $inventory = $this->getParentInstance();
        
        if ((int) $inventory->getData('status') == Inventory2::STATUS_OPEN
            and (int) $user->rights->bimpequipment->inventory->create) {
            
                if(0 < (int) $this->getData('fk_equipment'))
                    return 1;
            
                return $inventory->lineIsDeletable((int) $this->id);
            }
        
        return 0;
    }
        
    public function renderWarehouseTypeName() {
        $wt = BimpCache::getBimpObjectInstance($this->module, 'InventoryWarehouse', $this->getData('fk_warehouse_type'));
        return $wt->renderName();
    }
    
    public function create(&$warnings = array(), $force_create = false) {
        
        $errors = array();
        
        $errors = array_merge($errors, $this->beforeCreate());
        
        if(empty($errors)) {
            $errors = array_merge($errors, parent::create($warnings, $force_create));
            $errors = array_merge($errors, $this->onCreate());
        }
        
        return $errors;
    }
    
    public function beforeCreate() {
        
        $errors = array();

        $inventory = $this->getParentInstance();
        $is_allowed = $inventory->isAllowedProduct($this->getData('fk_product'));
        
        // Vérification que ce produit soit inventorisé
        if(!$is_allowed)
            $errors[] = "Ce produit n'est pas attendu dans cet inventaire. "
                . "Merci de le spécifié dans la configuration si vous "
                . "souhaitez ajouter ce produit (droit requis).";
        
        // Vérification du statut de l'inventaire
        if(Inventory2::STATUS_OPEN != (int) $inventory->getData('status')
            and (int) $this->getData('fk_equipment') == 0)
            $errors[] = "Le statut de l'inventaire ne permet pas d'ajouter des lignes"
                . "de produits non sérialisé";
        
        // Vérification que cet équipement n'ai pas déjà été scanné
        if((int) $this->getData('fk_equipment') > 0) {
            $filters = array(
                'fk_equipment' => $this->getData('fk_equipment'),
                'fk_inventory' => $this->getData('fk_inventory')
            );
            
            $lines = $this->getList($filters);
            if(!empty($lines))
                $errors[] = "Cet équipement à déjà été scanné";
        }
        
        return $errors;
        
    }


    public function onCreate() {
        
        $errors = array();
       
        
        // MAJ de l'expected concerné par cette ligne de scan
        $expected = BimpCache::getBimpObjectInstance($this->module, 'InventoryExpected');
        
                        
        $filters =  array(
            'id_wt' => array(
                'operator' => '=',
                'value'    => $this->getData('fk_warehouse_type')
            ),
            'id_product' => array(
                'operator' => '=',
                'value'    => $this->getData('fk_product')
            )
//                ,
//                'qty' => array(
//                    'operator' => '>',
//                    'value'    => 0
//                )

        );
        
        // Echange SN
        if(0 < (int) $this->getData('fk_package')) {
            
            $filters['id_package'] = array(
                'operator' => '=',
                'value'    => $this->getData('fk_package')
            );
        }
            

        $l_expected = $expected->getList($filters, null, null, 'id', 'asc', 'array', array('id'));
        
        // Equipment
        if(0 < (int) $this->getData('fk_equipment')) {
            
            $expected->fetch((int) $l_expected[0]['id']);
            $errors = array_merge($errors, $expected->setScannedEquipment((int) $this->getData('fk_equipment'), $this));
            
        // Produit non sérialisé
        } else {
        
            $expected->fetch((int) $l_expected[0]['id']);
            $errors = array_merge($errors, $expected->addProductQtyScanned((int) $this->getData('qty'), $this));
            
        }
        
        if(!empty($errors))
            $errors = BimpTools::merge_array ($errors, $this->delete());
                
        return $errors;
        
    }
    
    public function isAdmin() {
        global $user;

        return (int) $user->rights->bimpequipment->inventory->create;
    }
    
    public function delete(&$warnings = array(), $force_delete = false) {
        $errors = array();

        $inventory = $this->getParentInstance();
        
        if(isset($inventory->line_to_delete) and 0 < $inventory->line_to_delete and
                !isset($inventory->warning_delete)) {
            $warnings[] = "Votre demande a bien été prise en compte, la quantité scanné "
                . " a bien été modifié sur une ou plusieurs autre ligne de scan.";
            $inventory->warning_delete = true;
        }
        
        $inventory->line_to_delete = $this->id;        
        // 2
        
        $errors = BimpTools::merge_array($errors, $this->beforeDelete());
        if(empty($errors))
            $errors = BimpTools::merge_array($errors, parent::delete($warnings, $force_delete));
        
        return $errors;
    }
    
    public function beforeDelete() {
        $errors = array();
                
        $filters = array(
            'id_wt'      => (int) $this->getData('fk_warehouse_type'),
            'id_product' => (int) $this->getData('fk_product'),
            'id_package' => (int) $this->getData('fk_package')
        );

        $expected = BimpCache::getBimpObjectInstance($this->module, 'InventoryExpected');
        $list_e = $expected->getListObjects($filters, null, null, 'id_wt', 'DESC');
            
        // Prod sérialisé
        if((int) $this->getData('fk_equipment') > 0) {
            $trouve = false;
            foreach ($list_e as $e) {
                if($e->containEquipment($this->getData('fk_equipment'))) {
                    $errors = BimpTools::merge_array($errors, $e->unsetScannedEquipment($this->getData('fk_equipment')));
                    $trouve = true;
                    break;
                }
                
            }
            
            if(!$trouve)
                $errors[] = "Ligne attendu non trouvée";
            
        // Prod non sérialisé
        } else {
            
            // Vérification qu'il n'y ai pas une ligne en excès pour ce produit
            $qty_scan = $this->getData('qty');
            
            if($qty_scan > 0) {
                
                // Expected n'existe pas
                if(empty($list_e))
                    $errors = BimpTools::merge_array($errors, $expected->addProductQtyScanned(-$qty_scan, $this, 1));
                
                else {
                    foreach ($list_e as $e) {

                        // Il y en a plus (ou autant) dans l'expected que dans la ligne de scan
                        if($qty_scan <= $e->getData('qty_scanned')) {
                            $errors = BimpTools::merge_array($errors, $e->addProductQtyScanned(-$qty_scan, null));
                            break;
                        } else {
                            $errors = BimpTools::merge_array($errors, $e->addProductQtyScanned(-$e->getData('qty_scanned'), null));
                            $qty_scan -= $e->getData('qty_scanned');
                        }

                        if((int) $e->getData('qty') == 0 and (int) $e->getData('qty_scanned') == 0) {
//                            $errors = BimpTools::merge_array($errors, $e->delete()); // déjà fait dans la fonction addProductQtyScanned()
                            break;
                        }

                    }
                }
                
            // Qty scan négative
            } else {
                
                // Expected n'existe pas
                if(empty($list_e))
                    $errors = BimpTools::merge_array($errors, $expected->addProductQtyScanned(-$qty_scan, $this, 1));
                
                else {
                
                    foreach ($list_e as $e) {

                        $errors = BimpTools::merge_array($errors, $e->addProductQtyScanned(-$qty_scan, null));

                        if((int) $e->getData('qty') == 0 and (int) $e->getData('qty_scanned') == 0) {
//                            $errors = BimpTools::merge_array($errors, $e->delete()); // déjà fait dans la fonction addProductQtyScanned()
                            break;
                        }

                    }

                }
            }
        }
        

        return $errors;
    }
    

    
    public function canEditField($field_name) {
        global $user;
        if($field_name == 'info' and ((int) $this->getData('user_create') == (int) $user->id or $this->isAdmin()))
            return 1;
        
        return parent::canEditField($field_name);
    }
}

