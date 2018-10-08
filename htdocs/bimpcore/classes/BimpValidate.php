<?php

class BimpValidate
{

    public static function isEmail($string)
    {
        if (empty($string)) {
            return false;
        }

        if (is_string($string)) {
            if (preg_match('/^[a-z\p{L}0-9!#$%&\'*+\/=?^`{}|~_-]+[.a-z\p{L}0-9!#$%&\'*+\/=?^`{}|~_-]*@[a-z\p{L}0-9]+(?:[.]?[_a-z\p{L}0-9-])*\.[a-z\p{L}0-9]+$/ui', $string)) {
                return true;
            }
        }

        return false;
    }
}
