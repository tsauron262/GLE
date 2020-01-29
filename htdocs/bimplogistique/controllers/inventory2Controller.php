<?php

class inventory2Controller extends BimpController {

    protected function ajaxProcessInsertInventoryLine() {
        $id_inventory_det = NULL;
        $input = BimpTools::getValue('input');
        $id_inventory = (int) BimpTools::getValue('id');
        $quantity_input = BimpTools::getValue('quantity');
        $inventory = BimpCache::getBimpObjectInstance($this->module, 'Inventory2', $id_inventory);
        $inventory_line = BimpObject::getInstance($this->module, 'InventoryLine2');
        $id_product = 0;
        $id_equipment = 0;
        $msg = '';

        $errors = $inventory_line->checkInput($input, $id_product, $id_equipment);
        
        if((int) $id_equipment > 0)
            $inventory->insertLineEquipment($id_product, $id_equipment, $errors);
        else
            $inventory->insertLineProduct($id_product, $quantity_input, $errors);
        
        $data = array(
            'id_product'       => $id_product,
            'id_equipment'     => $id_equipment,
            'id_inventory'     => $id_inventory,
            'warning' => array()
        );

        die(json_encode(array(
            'errors'     => $errors,
            'success'    => $msg,
            'data'       => $data,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }

    
    public function getPageTitle() {
        $title = 'Inv.';
        $inventory = $this->config->getObject('', 'inventory2');

        if (BimpObject::objectLoaded($inventory)) {
            $warehouse = $inventory->getEntrepotRef();
            $title .= '#' . $inventory->getData('id') . ' ' . $warehouse;
        }
        
        return $title;
    }
    

}
