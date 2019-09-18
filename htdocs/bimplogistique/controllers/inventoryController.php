<?php

class inventoryController extends BimpController {

    protected function ajaxProcessInsertInventoryLine() {
        global $user;
        $id_inventory_det = NULL;
        $input = BimpTools::getValue('input');
        $id_inventory = (int) BimpTools::getValue('id');
        $quantity_input = (int) BimpTools::getValue('quantity');
        $inventory = BimpCache::getBimpObjectInstance($this->module, 'Inventory', $id_inventory);
        $inventory_line = BimpObject::getInstance($this->module, 'InventoryLine');
        $id_product = 0;
        $id_equipment = 0;

        $errors = $inventory_line->checkInput($input, $id_product, $id_equipment);
        
        $tab = $inventory->createLines($id_product, $id_equipment, $quantity_input);
        $id_inventory_det = $tab['id_inventory_det'];
        $errors = array_merge($errors, $tab['errors']);
        $msg = $tab['msg'];

        $data = array(
            'id_inventory_det' => $id_inventory_det,
            'id_product'       => $id_product,
            'id_equipment'     => $id_equipment,
            'id_inventory'     => $id_inventory,
            'warning' => array()
        );

        die(json_encode(array(
            'errors' => $errors,
            'success' => $msg,
            'data' => $data,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }

    public function getPageTitle() {
        $title = 'Inv.';
        $inventory = $this->config->getObject('', 'inventory');
        $warehouse = '';

        if (BimpObject::objectLoaded($inventory)) {
            $warehouse = $inventory->getEntrepotRef();
            $title .= '#' . $inventory->getData('id') . ' ' . $warehouse;
        }
        return $title;
    }
    

}
