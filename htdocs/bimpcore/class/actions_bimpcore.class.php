<?php

class ActionsBimpcore
{

    function doActions($parameters, &$object, &$action, $hookmanager)
    {
        
    }

    function getNomUrl($parameters, &$object, &$action, $hookmanager)
    {
        global $langs;

        if (is_a($object, "product") || is_a($object, 'Bimp_Product')) {
            BimpObject::loadClass('bimpcore', 'Bimp_Product');
            $hookmanager->resPrint = Bimp_Product::getStockIconStatic($object->id); // $id_entrepôt facultatif, peut être null.
        }

        return 0;
    }
}
