<?php

require_once DOL_DOCUMENT_ROOT . '/bimpvalidation/classes/BimpValidationType.php';

class BimpValidationComm extends BimpValidationType
{

    

    public static function tryToValidate($rules, $object, $object_type, $val, $extra_data = array(), &$errors = array())
    {
        global $user;

        if (!BimpObject::objectLoaded($user)) {
            $errors[] = 'Aucun utilisateur connecté';
            return 0;
        }

        foreach ($rules as $rule) {
            if (!in_array($object_type, $rule->getData('objects'))) {
                continue;
            }

            $val_min = (float) $rule->getData('val_min');
            $val_max = (float) $rule->getData('val_max');

            if ($val < $val_min || $val > $val_max) {
                continue;
            }

            if ($rule->isUserAllowed($user->id)) {
                return 1;
            }
        }

        return 0;
    }

    public static function getValidationUsers($rules, $object, $object_type, $val, $extra_data = array(), &$infos = '', &$errors = array())
    {
        
    }
}
