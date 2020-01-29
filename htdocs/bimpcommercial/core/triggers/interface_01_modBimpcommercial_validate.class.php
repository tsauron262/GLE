<?php

include_once DOL_DOCUMENT_ROOT . '/bimpcommercial/classes/BimpCommTriggers.php';

class Interfacevalidate extends BimpCommTriggers
{

    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
        global $conf;
        $errors = array();

        $object_name = '';
        $action_name = '';

        $bimpObject = $this->getBimpCommObject($action, $object, $object_name, $action_name);

        if (BimpObject::objectLoaded($bimpObject)) {
            switch ($action_name) {
                case 'VALIDATE':
                    $bimpObject->isValidatable($errors);
                    break;

                case 'UNVALIDATE':
                    $bimpObject->isUnvalidatable($errors);
                    break;

                case 'DELETE':
                    if (!$bimpObject->isDeletable()) {
                        $errors[] = BimpTools::ucfirst($bimpObject->getLabel('this')) . ' ne peut pas être supprimé' . $bimpObject->e();
                    }
                    break;
            }

            if (count($errors)) {
                $this->errors= $errors;
//                setEventMessages(BimpTools::getMsgFromArray($errors), null, 'errors');
                return -1;
            }
        }

        return 0;
    }
}
