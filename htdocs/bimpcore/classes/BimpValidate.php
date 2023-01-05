<?php

class BimpValidate
{

    public static function isEmail($string)
    {
        if (empty($string)) {
            return 0;
        }

        if (is_string($string)) {
            $string = BimpTools::cleanEmailsStr($string);

            foreach (explode(',', $string) as $email) {
                if (!preg_match('/^[a-z\p{L}0-9!#$%&\'*+\/=?^`{}|~_-]+[.a-z\p{L}0-9!#$%&\'*+\/=?^`{}|~_-]*@[a-z\p{L}0-9]+(?:[.]?[_a-z\p{L}0-9-])*\.[a-z\p{L}0-9][a-z\p{L}0-9]+$/ui', $email)) {
                    return 0;
                }
            }

            return 1;
        }

        return 0;
    }
}
