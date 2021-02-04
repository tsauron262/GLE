<?php

include_once DOL_DOCUMENT_ROOT . '/core/triggers/dolibarrtriggers.class.php';

abstract class BimpCommTriggers extends DolibarrTriggers
{

    public static $bimpcomm_objects = array(
        'PROPAL'         => 'Bimp_Propal',
        'ORDER'          => 'Bimp_Commande',
        'BILL'           => 'Bimp_Facture',
        'ORDER_SUPPLIER' => 'Bimp_CommandeFourn',
        'BILL_SUPPLIER'  => 'Bimp_FactureFourn',
        'PAYMENT_CUSTOMER'  => 'Bimp_Paiement'
    );

    public function getBimpCommObject($action, $object, &$object_name = '', &$action_name = '', &$errors = array())
    {
        $bimpObject = null;

        if (preg_match('/^([A-Za-z_]+)_([A-Za-z]+)$/', $action, $matches)) {
            $object_name = $matches[1];
            $action_name = $matches[2];
        }
        
        if ($object_name && array_key_exists($object_name, self::$bimpcomm_objects) && BimpObject::objectLoaded($object)) {
            $bimpObject = BimpCache::getBimpObjectInstance('bimpcommercial', self::$bimpcomm_objects[$object_name], (int) $object->id);

            if (BimpObject::objectLoaded($bimpObject)) {
                $bimpObject->fetch((int) $object->id);
                if(method_exists($bimpObject, 'checkLines'))
                    $bimpObject->checkLines();
            } else {
                if(is_object($bimpObject))
                    $errors[] = BimpTools::ucfirst($bimpObject->getLabel('the')) . ' d\'ID ' . $object->id . ' n\'existe plus';
                else
                    $errors[] = BimpTools::ucfirst($object_name) . ' d\'ID ' . $object->id . ' n\'existe plus';
            }
        }

        return $bimpObject;
    }
}
