<?php

include_once DOL_DOCUMENT_ROOT . '/bimpcommercial/classes/BimpCommTriggers.php';

class InterfacePostProcess extends BimpCommTriggers
{

    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
        global $conf;
        $errors = array();
        $warnings = array();

        $object_name = '';
        $action_name = '';
        
        $bimpObject = $this->getBimpCommObject($action, $object, $object_name, $action_name, $warnings);

        if (BimpObject::objectLoaded($bimpObject)) {
            $obj_errors = BimpTools::getDolEventsMsgs(array('errors'), false);
            if (empty($obj_errors)) {
                BimpObject::loadClass('bimpalert', 'AlertProduit');
                if(class_exists('AlertProduit'))
                    AlertProduit::traiteAlertes($bimpObject, $action_name, $errors, $warnings);
                switch ($action_name) {
                    case 'CREATE':
                        if (method_exists($bimpObject, 'onCreate')) {
                            $errors = $bimpObject->onCreate($warnings);
                        }
                        break;

                    case 'VALIDATE':
                        if (method_exists($bimpObject, 'onValidate')) {
                            $errors = $bimpObject->onValidate($warnings);
                        }
                        break;

                    case 'UNVALIDATE':
                        if (method_exists($bimpObject, 'onUnvalidate')) {
                            $errors = $bimpObject->onUnvalidate($warnings);
                        }
                        break;

                    case 'DELETE':
                        if (method_exists($bimpObject, 'onDelete')) {
                            $errors = $bimpObject->onDelete($warnings);
                        }
                        break;
                }
                
                if(isset($bimpObject->dol_object))
                    $bimpObject->hydrateDolObject();

                if (count($warnings)) {
                    setEventMessages(BimpTools::getMsgFromArray($warnings), null, 'warnings');
                }

                if (count($errors)) {
                    setEventMessages(BimpTools::getMsgFromArray($errors), null, 'errors');
                    return -1;
                }
            }
        }

        if ($action == 'PAYMENT_CUSTOMER_DELETE') {
            $bimp_object = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Paiement', $object->id);
            if (BimpObject::objectLoaded($bimp_object)) {
                $errors = $bimp_object->onDelete();
                if (count($errors)) {
                    $this->errors = $errors;
//                    setEventMessages(BimpTools::getMsgFromArray($errors), null, 'errors');
                    return -1;
                }
            }
        } elseif ($action == 'BILL_PAYED') {
            $bimpFacture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $object->id);
            if (BimpObject::objectLoaded($bimpFacture)) {
                $bimpFacture->updateField('paiement_status', 2);
                $bimpFacture->checkRemainToPay();
            }
        } elseif ($action == 'BILL_UNPAYED') {
            $bimpFacture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $object->id);
            if (BimpObject::objectLoaded($bimpFacture)) {
                $bimpFacture->checkIsPaid(true, 0, 0);
            }
        } elseif ($action == 'CONTACT_MODIFY') {
            $clients = BimpCache::getBimpObjectObjects('bimpcore', 'Bimp_Client', array(
                        'id_contact_relances' => (int) $object->id
            ));

            foreach ($clients as $client) {
                $client->checkRelancesLinesContact($warnings);
            }
        }

        if (count($warnings)) {
            setEventMessages(BimpTools::getMsgFromArray($warnings), null, 'warnings');
        }

        return 0;
    }
}
