<?php

include_once DOL_DOCUMENT_ROOT . '/bimpcommercial/classes/BimpCommTriggers.php';

class InterfacePostProcess extends BimpCommTriggers
{

    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
        global $conf;
        $errors = array();

        $object_name = '';
        $action_name = '';

        $bimpObject = $this->getBimpCommObject($action, $object, $object_name, $action_name);

        if (BimpObject::objectLoaded($bimpObject)) {
            $warnings = array();
            
            switch ($action_name) {
                case 'CREATE':
                    $errors = $bimpObject->onCreate($warnings);
                    break;

                case 'VALIDATE':
                    $errors = $bimpObject->onValidate($warnings);
                    break;

                case 'UNVALIDATE':
                    $errors = $bimpObject->onUnvalidate($warnings);
                    break;

                case 'DELETE':
                    $errors = $bimpObject->onDelete($warnings);
                    break;
            }

            if (count($warnings)) {
                setEventMessages(BimpTools::getMsgFromArray($warnings), null, 'warnings');
            }
            
            if (count($errors)) {
                setEventMessages(BimpTools::getMsgFromArray($errors), null, 'errors');
                return -1;
            }
        }

        if ($action == 'PAYMENT_CUSTOMER_DELETE') {
            $bimp_object = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Paiement', $object->id);
            if (BimpObject::objectLoaded($bimp_object)) {
                $errors = $bimp_object->onDelete();
                if (count($errors)) {
                    $this->errors= $errors;
//                    setEventMessages(BimpTools::getMsgFromArray($errors), null, 'errors');
                    return -1;
                }
            }
        }

        return 0;
    }
}
