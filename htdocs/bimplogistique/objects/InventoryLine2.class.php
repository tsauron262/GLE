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
            $errors[] = "Produit inconnu";
        else if (!$is_equipment and $this->isSerialisable($id_product))
            $errors[] = "Veuillez scanner le numéro de série au lieu de la référence.";
        return $errors;
    }

    public function canCreate(){
        return 1;
    }
    
    public function canDelete(){
        return 1;
    }
    
//    public function isDeletable(){
//        return 1;
//    }
        
        
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
        $sql .= ' WHERE serial="' . $input . '" || concat("S", serial)="' . $input . '" || concat("S", "' . $input . '")=serial';
        
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
        $inventory = $this->getParentInstance();
        if((int) $this->getData('fk_equipment') > 0) {
            if ((int) $inventory->getData('status') <= Inventory2::STATUS_PARTIALLY_CLOSED)
                return 1;
        } else {
            if ((int) $inventory->getData('status') < Inventory2::STATUS_PARTIALLY_CLOSED)
                return 1;
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
            $errors[] = "Ce produit n'est pas attendu dans cet inventaire."
                . "Merci de le spécifié dans la configuration si vous"
                . "souhaitez ajouter ce produit (droit recquis).";
        
        // Vérification du statut de l'inventaire
        if(Inventory2::STATUS_OPEN != (int) $inventory->getData('status')
            and (int) $this->getData('fk_equipment') == 0)
            $errors[] = "Le statut de l'inventaire ne permet pas d'ajouter des lignes"
                . "de produits non sérialisé";
        
        
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
        );

        $l_expected = $expected->getList($filters, null, null, 'id', 'asc', 'array', array('id'));
        
        // Equipment
        if(0 < (int) $this->getData('fk_equipment')) {
            
            $errors = array_merge($errors, $expected->fetch((int) $l_expected[0]['id']));
            $errors = array_merge($errors, $expected->setScannedEquipment((int) $this->getData('fk_equipment')));
            
        // Produit non sérialisé
        } else {
        
            $errors = array_merge($errors, $expected->fetch((int) $l_expected[0]['id']));
            $errors = array_merge($errors, $expected->addProductQtyScanned((int) $this->getData('qty')));
            
        }
        
        return $errors;
        
    }

}

