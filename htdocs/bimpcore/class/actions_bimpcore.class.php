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

    function replaceThirdparty($parameters, &$object, &$action, $hookmanager)
    {
        ini_set('display_errors', 1);

//        global $db;
//        $db->query("UPDATE ".MAIN_DB_PREFIX."bs_sav SET id_client = ".$parameters['soc_dest']." WHERE id_client = ".$parameters['soc_origin']);
        
        if (!defined('BIMP_LIB')) {
            require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
        }

        BimpTools::changeBimpObjectId($parameters['soc_origin'], $parameters['soc_dest'], 'bimpcore', 'Bimp_Societe');
        BimpTools::changeBimpObjectId($parameters['soc_origin'], $parameters['soc_dest'], 'bimpcore', 'Bimp_Client');
        BimpTools::changeBimpObjectId($parameters['soc_origin'], $parameters['soc_dest'], 'bimpcore', 'Bimp_Fournisseur');
        BimpTools::changeDolObjectId($parameters['soc_origin'], $parameters['soc_dest'], 'societe');

        return 0;
    }
}
