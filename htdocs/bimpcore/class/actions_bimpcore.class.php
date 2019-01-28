<?php

class ActionsBimpcore {

    function doActions($parameters, &$object, &$action, $hookmanager) {
    }

    
    

    function getNomUrl($parameters, &$object, &$action, $hookmanager) {
        global $langs;
        
        
        if(class_exists("BimpObject") && is_a($object, "product")   ){
            BimpObject::loadClass('bimpcore', 'Bimp_Product');
            $hookmanager->resPrint = Bimp_Product::getStockIconStatic($object->id); // $id_entrepôt facultatif, peut être null.
        }
        $hookmanager->resPrint .= 'rr';
        return 0;
    }

}
