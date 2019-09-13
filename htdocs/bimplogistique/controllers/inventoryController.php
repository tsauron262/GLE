<?php

class inventoryController extends BimpController {

    protected function ajaxProcessInsertInventoryLine() {
        global $user;
        $id_inventory_det = NULL;
        $input = BimpTools::getValue('input');
        $id_inventory = (int) BimpTools::getValue('id');
        $quantity_input = (int) BimpTools::getValue('quantity');
        $inventory_line = BimpObject::getInstance($this->module, 'InventoryLine');
        $id_product = 0;
        $id_equipment = 0;

        $errors = $inventory_line->checkInput($input, $id_product, $id_equipment);
        

        $errors = array_merge($errors, $inventory_line->validateArray(array(
            'fk_inventory' => (int) $id_inventory,
            'fk_product' => (int) $id_product,
            'fk_equipment' => (int) $id_equipment,
            'qty' => (int) $quantity_input
        )));

        if (!count($errors)) {
            $warning = array();
            $errors = array_merge($errors, $inventory_line->create($warning));
        } else {
            $errors[] = "Erreur lors de la validation des données renseignées";
        }

        $id_inventory_det = $inventory_line->db->db->last_insert_id();

        $data = array(
            'id_inventory_det' => $id_inventory_det,
            'id_product'       => $id_product,
            'id_equipment'     => $id_equipment,
            'id_inventory'     => $id_inventory,
            'warning' => array()
        );

        die(json_encode(array(
            'errors' => $errors,
            'success' => sizeof($errors) == 0 ? "Ligne enregistrée" : '',
            'data' => $data,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }

    public function getPageTitle() {
        $title = 'Inventaire ';
        $inventory = $this->config->getObject('', 'inventory');

        if (BimpObject::objectLoaded($inventory)) {
            $title .= '#' . $inventory->getData('id');
        }
        return $title;
    }
    

}
