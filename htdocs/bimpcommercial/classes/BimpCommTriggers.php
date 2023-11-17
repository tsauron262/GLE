<?php

include_once DOL_DOCUMENT_ROOT . '/core/triggers/dolibarrtriggers.class.php';

abstract class BimpCommTriggers extends DolibarrTriggers
{

    public static $bimpcomm_objects = array(
        'PROPAL'           => 'Bimp_Propal',
        'propaldet'           => 'Bimp_PropalLine',
        'ORDER'            => 'Bimp_Commande',
        'BILL'             => 'Bimp_Facture',
        'ORDER_SUPPLIER'   => 'Bimp_CommandeFourn',
        'BILL_SUPPLIER'    => 'Bimp_FactureFourn',
        'PAYMENT_CUSTOMER' => 'Bimp_Paiement'
    );

    public function getBimpCommObject($action, $object, &$object_name = '', &$action_name = '', &$errors = array())
    {
        $bimpObject = null;

        if (preg_match('/^([A-Za-z_]+)_([A-Za-z]+)$/', $action, $matches)) {
            $object_name = $matches[1];
            $action_name = $matches[2];
        }

        $forceDontFetch = false;
        if ($object_name && array_key_exists($object_name, self::$bimpcomm_objects) && BimpObject::objectLoaded($object)) {
            if(stripos($object_name, 'det') !== false){
                $forceDontFetch = true;
                $filtre = array('id_line' => (int) $object->id);
                $bimpObject = BimpCache::findBimpObjectInstance('bimpcommercial', self::$bimpcomm_objects[$object_name], $filtre, true);
            }
            else{
                $bimpObject = BimpCache::getBimpObjectInstance('bimpcommercial', self::$bimpcomm_objects[$object_name], (int) $object->id);
            }

            if (BimpObject::objectLoaded($bimpObject)) {
                if (!$bimpObject->noFetchOnTrigger && !$forceDontFetch) { // noFetchOnTrigger : true lorsqu'on fait un create / update depuis le bimpObject => dans ce cas ne pas re-fetcher l'objet sinon écrasement des données.
                    $bimpObject->fetch((int) $object->id);
                    
                    // On alimente $bimpObject avec les données de $object: 
                    $bimpObject->dol_object = $object;
                    $bimpObject->hydrateFromDolObject();
                    
                    if (method_exists($bimpObject, 'checkLines')) {
                        $bimpObject->checkLines();
                    }
                }
            } else {
                if (is_object($bimpObject)) {
                    $errors[] = BimpTools::ucfirst($bimpObject->getLabel('the')) . ' d\'ID ' . $object->id . ' n\'existe plus (triggers '.$action.')';
                } else {
                    $errors[] = 'L\'objet de type "' . BimpTools::ucfirst($object_name) . '" d\'ID ' . $object->id . ' n\'existe plus (triggers '.$action.')';
                }
            }
        }
        return $bimpObject;
    }
}
