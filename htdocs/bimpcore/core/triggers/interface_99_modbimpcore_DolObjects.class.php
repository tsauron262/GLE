<?php

require_once DOL_DOCUMENT_ROOT . '/core/triggers/dolibarrtriggers.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

/**
 *  Class of triggers for demo module
 */
class InterfaceDolObjects extends DolibarrTriggers
{

    public $picto = 'technic';
    public $description = "Rechargement des BimpObjects en cache lors d'une mise à jour sur l'objet Dolibarr associé";
    public $version = self::VERSION_DOLIBARR;
    public static $dolObjectsUpdates = array(
        // Objets BimpComm: 
        'PROPAL' => array('bimpcommercial', 'Bimp_Propal'),
        'LINEPROPAL' => array('bimpcommercial', 'Bimp_PropalLine'),
        'ORDER' => array('bimpcommercial', 'Bimp_Commande'),
        'LINEORDER' => array('bimpcommercial', 'Bimp_CommandeLine'),
        'BILL' => array('bimpcommercial', 'Bimp_Facture'),
        'LINEBILL' => array('bimpcommercial', 'Bimp_FactureLine'),
        'ORDER_SUPPLIER' => array('bimpcommercial'),
        'LINEORDER_SUPPLIER' => array('bimpcommercial'),
        'BILL_SUPPLIER' => array('bimpcommercial'),
        'LINEBILL_SUPPLIER' => array('bimpcommercial'),
        // objets BimpCore: 
        'COMPANY' => array('bimpcore', 'Bimp_Societe'),
        'CONTACT' => array('bimpcore', 'Bimp_Contact'),
        'PRODUCT' => array('bimpcore', 'Bimp_Product'),
        'USER' => array('bimpcore', 'Bimp_User'),
    );

    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
        // Edit signature
        if($action == 'USER_CREATE' or $action == 'USER_MODIFY') {
            global $db;
            // tab card and signature unset
                require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
                if ((int) $object->id > 0)
                    define('ID_SELECTED_FOR_SIGNATURE', (int) $object->id); // used in next script
                if(ID_SELECTED_FOR_SIGNATURE > 0) {
                    require_once DOL_DOCUMENT_ROOT . '/bimpcore/scripts/edit_signature.php';
                }
        }
        
        // On re-fetch() le BimpObject lié au DolObject afin d'être sûr que l'objet soit à jour dans le cache. 
        // (ou on le supprime du cache si l'action est de type DELETE. 
        if ((BimpObject::objectLoaded($object))) {
            if (preg_match('/^([A-Z_]+)_([A-Z]+)$/', $action, $matches)) {
                $objKey = $matches[1];
                $actionCode = $matches[2];

                if (array_key_exists($objKey, self::$dolObjectsUpdates)) {
                    $data = self::$dolObjectsUpdates[$objKey];
                    $cache_key = 'bimp_object_' . $data[0] . '_' . $data[1] . '_' . $object->id;
                    if (BimpCache::cacheExists($cache_key)) {
                        if ($actionCode === 'DELETE') {
                            BimpCache::unsetBimpObjectInstance($data[0], $data[1], $object->id);
                        } elseif ($actionCode !== 'CREATE') {
                            $bimp_object = BimpCache::getBimpObjectInstance($data[0], $data[1], $object->id);
                            // noFetchOnTrigger: On est dans le cas d'un create() ou update() via le BimpObject, donc pas de fetch(). 
                            if (BimpObject::objectLoaded($bimp_object) && !$bimp_object->noFetchOnTrigger) {
                                $bimp_object->fetch((int) $object->id);
                            }
                        }
                    }
                }
            }
        }
        
        return 0;
    }
}
