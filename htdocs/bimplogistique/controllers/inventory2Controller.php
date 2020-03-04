<?php

class inventory2Controller extends BimpController {

    protected function ajaxProcessInsertInventoryLine() {
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
            $inventory_line_ids = $inventory->insertLineEquipment($id_product, $id_equipment, $errors);
        else
            $inventory_line_ids = $inventory->insertLineProduct($id_product, $quantity_input, $errors);
//        print_r($errors);
//        die();
//        $expected = BimpCache::getBimpObjectInstance($this->module, 'InventoryExpected');

//        // Produit
//        if(is_array($inventory_line_ids)) {
//            foreach ($inventory_line_ids as $id_line)
//                $errors = array_merge($errors, $expected->manageScanProduct($id_inventory, $id_line));
//            
//        // C'est un équipement
//        } else
//            $errors = array_merge($errors, $expected->manageScanEquipment($id_inventory, $inventory_line_ids));
        
        
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
    
    protected function ajaxProcessAddProductInput() {
        
        $number = BimpTools::getPostFieldValue('number');
        
        $label = '<strong style="margin-right: 5px;" >Produit n°' . $number . '</strong>';
        $input = BimpInput::renderInput('search_product', 'prod' . $number);
        
        $delete_btn = '<button type="button" class="addValueBtn btn btn-danger" '
                . 'onclick="deleteUnitProduct($(this))" style="margin-left: 5px;">'
                . '<i class="fas fa5-trash-alt"></button>';
        
        $div_url = '<div url_prod></div>';
        
        
        $html  = '<div name="cnt_prod' . $number . '" style="margin: 12px;" is_product>';
        $html .= $label . $input . $delete_btn . $div_url;
        $html .= '</div>';
                
        die(json_encode(array(
            'data'       => $html,
            'success'    => 'Produit ajouté',
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }
    
    protected function ajaxProcessGetProductUrl() {
        
        $id_prod = (int) BimpTools::getPostFieldValue('id_prod');
        $prod = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $id_prod);
        
        die(json_encode(array(
            'url'        => $prod->getNomUrl(),
            'success'    => 'Produit ajouté',
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
        
    }
    
    protected function ajaxProcessSaveObject() {
        $errors = array();
        $warnings = '';
        $success = '';
        
        $id_inventory = (int) BimpTools::getValue('id');
        $inventory = BimpCache::getBimpObjectInstance($this->module, 'Inventory2', $id_inventory);
                
        $errors = array_merge($errors, $inventory->addProductToConfig($success, $warnings));
        
        die(json_encode(array(
            'errors'     => $errors,
            'success'    => $success,
            'warnings'   => $warnings,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }

    

}