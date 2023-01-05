<?php

include_once DOL_DOCUMENT_ROOT . '/bimpcommercial/classes/BimpCommTriggers.php';

class Interfacevalidate extends BimpCommTriggers
{

    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
        global $conf, $user;
        $errors = array();
        $success = array();

        $object_name = '';
        $action_name = '';

        $bimpObject = $this->getBimpCommObject($action, $object, $object_name, $action_name, $errors);

        if (BimpObject::objectLoaded($bimpObject)) {
            switch ($action_name) {
                case 'VALIDATE':
                    if (method_exists($bimpObject, 'isValidatable')) {
                        if ($bimpObject->isValidatable($errors)  && (int) $conf->global->MAIN_MODULE_BIMPVALIDATEORDER == 1) {
                            $validateur = BimpCache::getBimpObjectInstance('bimpvalidateorder', 'ValidComm');
                            $can_validate = (int) $validateur->tryToValidate($bimpObject, $user, $errors, $success);
                        }
                        if (count($success)) {
                            setEventMessages(BimpTools::getMsgFromArray($success), null, 'warnings');
                        }
                    }
                    break;

                case 'UNVALIDATE':
                    if (method_exists($bimpObject, 'isUnvalidatable')) {
                        $bimpObject->isUnvalidatable($errors);
                    }
                    break;

                case 'DELETE':
                    if (method_exists($bimpObject, 'isDeletable')) {
                        global $rgpd_delete;
                        if (!$bimpObject->isDeletable($rgpd_delete ? true : false)) {
                            $errors[] = BimpTools::ucfirst($bimpObject->getLabel('this')) . ' ne peut pas être supprimé' . $bimpObject->e();
                        }
                    }
                    break;
            }

            if (count($errors)) {
                $this->errors = $errors;
                // Attention toute la remontée des erreurs est basée là-dessus (pour les BimpComm): 
                setEventMessages(BimpTools::getMsgFromArray($errors), null, 'errors');
                return -1;
            }
        } elseif (count($errors)) {
            $this->errors = $errors;
            setEventMessages(BimpTools::getMsgFromArray($errors), null, 'errors');
            return -1;
        }


        return 0;
    }
}
