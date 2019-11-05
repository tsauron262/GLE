<?php

if(!class_exists(Bimp_Product))
    require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/Bimp_Product.class.php';


class ProductInventory extends Bimp_Product {
    
    /* Ne peut être utilisé que dans l'affichage des listes à cause de $inventory->current_wt */
    public function renderStock() {
        $id_inventory = (int) BimpTools::getValue('id');
        $inventory = BimpCache::getBimpObjectInstance('bimplogistique', 'Inventory2', $id_inventory);
        $diff = $inventory->getDiffProduct($inventory->current_wt, $this->getData('id'));
        return $diff['stock'];
    }
    
    /* Ne peut être utilisé que dans l'affichage des listes à cause de $inventory->current_wt */
    public function renderNbScanned() {
        $id_inventory = (int) BimpTools::getValue('id');
        $inventory = BimpCache::getBimpObjectInstance('bimplogistique', 'Inventory2', $id_inventory);
        $diff = $inventory->getDiffProduct($inventory->current_wt, $this->getData('id'));
        return $diff['nb_scan'];
    }
}