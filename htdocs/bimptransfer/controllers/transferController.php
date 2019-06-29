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

        $errors = $transfert_line->checkInput($input, $id_product, $id_equipment);
        $transfer = BimpObject::getInstance('bimptransfer', 'Transfer');

        $transfer->fetch((int) $id_transfer);
        $id_warehouse_source = $transfer->getData('id_warehouse_source');

        if($transfer->getData('status') == Transfer::CONTRAT_STATUS_SENDING)
            $errors = array_merge($errors, $transfert_line->checkStock($quantity_avaible, $id_product, $id_equipment, $id_warehouse_source, $id_transfer));
        else
            $quantity_avaible = 10000000;

        // There is enough product
        $id_line = $transfert_line->lineExists($id_transfer, $id_product, $id_equipment);

        
        if (sizeof($errors) == 0) {
            if($transfer->getData('status') == Transfer::CONTRAT_STATUS_SENDING){//mode envoie
                if ($id_line > 0) {
                    $transfert_lineObj = BimpCache::getBimpObjectInstance('bimptransfer', 'TransferLine', $id_line);
                    
                    $new_qty_send = $transfert_lineObj->getData("quantity_sent") + $quantity_input;
                    $qteAResa =  $new_qty_send - $transfert_lineObj->getData("quantity_received") ;

                    if ($quantity_avaible < $qteAResa) {
                        $errors[] = "Il n'y a que " . $quantity_avaible . " ce produit disponible dans cet entrepôt. "
                                . "Or vous essayez d'en réserver " . $qteAResa . " pour ce transfert.";
                    }
                    else{
                        $errors = array_merge($errors, $transfert_lineObj->set('quantity_sent', (int) $new_qty_send));
                        $errors = array_merge($errors, $transfert_lineObj->update());
                    }
                }
                else{
                    $errors = array_merge($errors, $transfert_line->create_2($id_transfer, $id_product, $id_equipment, $quantity_input, $id_affected, $id_warehouse_source));
                }

            }
            else{//reception
                if ($id_line){
                    $transfert_lineObj = BimpCache::getBimpObjectInstance('bimptransfer', 'TransferLine', $id_line);
                    $errors = array_merge($errors, $transfert_lineObj->set('quantity_received', (int) $transfert_lineObj->getData('quantity_received') + (int) $quantity_input));
                    $errors = array_merge($errors, $transfert_lineObj->update());
                }
                else
                    $errors[] = "Produit non trouvé dans les envoie.";
                
                    
            }
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
