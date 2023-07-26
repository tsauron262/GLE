<?php

require_once DOL_DOCUMENT_ROOT . '/bimpvalidation/classes/BimpValidation.php';

abstract class BimpValidationType
{

    public static function checkRule($rule, $object_type, $secteur, $val, $extra_data)
    {
        
    }

    abstract public static function tryToValidate($rules, $object, $object_type, $val, $extra_data = array(), &$errors = array());

    abstract public static function getValidationUsers($rules, $object, $object_type, $val, $extra_data = array(), &$infos = '', &$errors = array());
}
