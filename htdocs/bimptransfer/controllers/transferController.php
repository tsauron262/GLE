<?php

class transferController extends BimpController {

    protected function ajaxProcessInsertTransferLine() {

        $errors = array();
        $id_affected = NULL;

        $input = BimpTools::getValue('input');
        $id_transfer = (int) BimpTools::getValue('id');
        $quantity_input = (int) BimpTools::getValue('quantity');
        $transfert_line = BimpObject::getInstance('bimptransfer', 'TransferLine');
        $id_product = 0;
        $id_equipment = 0;
        $quantity_avaible = 0;
        $previous_quantity = 0;

        $errors = $transfert_line->checkInput($input, $id_product, $id_equipment);
        $transfer = BimpObject::getInstance('bimptransfer', 'Transfer');

        $transfer->fetch((int) $id_transfer);
        $id_warehouse_source = $transfer->getData('id_warehouse_source');

        $errors = array_merge($errors, $transfert_line->checkStock($quantity_avaible, $id_product, $id_equipment, $id_warehouse_source, $id_transfer));

        // There is enough product
        $id_line = $transfert_line->lineExists($id_transfer, $id_product, $id_equipment, $previous_quantity);

        if ($quantity_avaible < ($quantity_input + $previous_quantity)) {
            $errors[] = "Il n'y a que " . $quantity_avaible . " fois ce produit dans cet entrepôt." .
                    ($previous_quantity > 0) ? " Or il y a déjà " . $previous_quantity . " réservations pour ce transfert." : '';
        }

        if (sizeof($errors) == 0) {
            if ($id_line > 0) {
                $transfert_line->fetch($id_line);
                $errors = array_merge($errors, $transfert_line->set('quantity_sent', (int) $transfert_line->getData('quantity_sent') + (int) $quantity_input));
                $errors = array_merge($errors, $transfert_line->update());
                $id_affected = $id_line;
            } else
                $errors = array_merge($errors, $transfert_line->create_2($id_transfer, $id_product, $id_equipment, $quantity_input, $id_affected, $id_warehouse_source));
        }

        $data = array(
            'id_affected' => $id_affected, // new id or modified id
            'id_transfer' => $id_transfer,
            'warning' => array()
        );

        die(json_encode(array(
            'errors' => $errors,
            'success' => sizeof($errors) == 0 ? "Transfert enregistré" : '',
            'data' => $data,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }

}
