<?php

class inventory2Controller extends BimpController {

    protected function ajaxProcessInsertInventoryLine() {
        
        $warnings = array();
        $input = trim(BimpTools::getValue('input'));
        $id_inventory = (int) BimpTools::getValue('id');
        $quantity_input = BimpTools::getValue('quantity');
        $inventory = BimpCache::getBimpObjectInstance($this->module, 'Inventory2', $id_inventory);
        $inventory_line = BimpObject::getInstance($this->module, 'InventoryLine2');
        $id_product = 0;
        $id_equipment = 0;
        $msg = '';
        
        $errors = $inventory_line->checkInput($input, $id_product, $id_equipment);
        
        if(!empty($errors)) {
            die(json_encode(array(
                'errors'     => $errors,
                'data'       => array(),
                'request_id' => BimpTools::getValue('request_id', 0)
            )));
        }
            
        if((int) $id_equipment > 0)
            $inventory_line_ids = $inventory->insertLineEquipment($id_product, $id_equipment, $errors);
        elseif($quantity_input != 0)
            $inventory_line_ids = $inventory->insertLineProduct($id_product, $quantity_input, $errors);
        else
            $errors[] = "Quantité égale à zéro.";
        
        if($quantity_input < 0 and empty($errors))
            $warnings[] = "Vous venez d'insérer une quantité négative.";
        
        $data = array(
            'id_product'       => $id_product,
            'id_equipment'     => $id_equipment,
            'id_inventory'     => $id_inventory,
        );

        die(json_encode(array(
            'errors'     => $errors,
            'warnings'   => $warnings,
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
        $input_name = BimpTools::getPostFieldValue('input_name');
        
        $label = '<strong style="margin-right: 5px;" >Produit n°' . $number . '</strong>';
        $input = BimpInput::renderInput('search_product', 'prod_' . $input_name . '_' . $number);
        
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
    
    protected function ajaxProcessDeleteEquipment() {
        
        $id_wt = (int) BimpTools::getPostFieldValue('id_wt');
        $id_equip = (int) BimpTools::getPostFieldValue('id_equip');
        $wt = BimpCache::getBimpObjectInstance('bimplogistique', 'InventoryExpected', $id_wt);
        $errors = $wt->ignoreEquipment($id_equip);
        
        die(json_encode(array(
            'errors'     => $errors,
            'success'    => 'Équipement ingnoré',
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
        
    }
}