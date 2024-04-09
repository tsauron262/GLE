<?php

class inventory_srController extends BimpController {

    protected function ajaxProcessInsertInventoryLine() {
        global $user;
        $id_inventory_det = NULL;
        $input = BimpTools::getValue('input', '', 'alphanohtml');
        $id_inventory = (int) BimpTools::getValue('id', 0, 'int');
        $quantity_input = BimpTools::getValue('quantity', 0, 'float');
        $inventory = BimpCache::getBimpObjectInstance($this->module, 'InventorySR', $id_inventory);
        $inventory_line = BimpObject::getInstance($this->module, 'InventoryLineSR');
        $id_product = 0;
        $id_equipment = 0;

        $errors = $inventory_line->checkInput($input, $id_product, $id_equipment);
        
        if(!count($errors)){
            if((int) $id_equipment > 0)
                $tab = $inventory->createLinesEquipment($id_product, $id_equipment);
            else
                $tab = $inventory->createLinesProduct($id_product, $quantity_input);
            $id_inventory_det = $tab['id_inventory_det'];
            $errors = BimpTools::merge_array($errors, $tab['errors']);
            $msg = $tab['msg'];
        }

        $data = array(
            'id_inventory_det' => $id_inventory_det,
            'id_product'       => $id_product,
            'id_equipment'     => $id_equipment,
            'id_inventory'     => $id_inventory,
            'warning' => array()
        );

        die(json_encode(array(
            'errors'     => $errors,
            'success'    => $msg,
            'data'       => $data,
            'request_id' => BimpTools::getValue('request_id', 0, 'int')
        )));
    }

    
    public function getPageTitle() {
        $title = 'Inv.SR';
        $inventory = $this->config->getObject('', 'inventory_sr');
        $warehouse = '';

        if (BimpObject::objectLoaded($inventory)) {
            $warehouse = $inventory->getEntrepotRef();
            $title .= '#' . $inventory->getData('id') . ' ' . $warehouse;
        }
        return $title;
    }
    

}
