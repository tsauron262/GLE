<?php

class BimpDebug
{

    public static $config = null;

    protected static function getConfig()
    {
        if (is_null(self::$config)) {
            self::$config = new BimpConfig(DOL_DOCUMENT_ROOT . '/bimpcore/', 'debug.yml', new BimpObject('', ''));
        }

        return self::$config;
    }

    public static function checkUser()
    {
        global $user;
        if (!BimpObject::objectLoaded($user)) {
            return 0;
        }

        if (!$user->admin) {
            return 0;
        }

        return 1;
    }

    public static function isActive($full_path)
    {
        if (!self::checkUser()) {
            return 0;
        }

        if (self::getConfig()->get('debug', 0, false, 'bool')) {
            return (int) self::getConfig()->get($full_path, 0, false, 'bool');
        }
    }

    public static function getParam($full_path, $default_value = '', $type = 'string')
    {
        return (int) self::getConfig()->get($full_path, $default_value, false, $type);
    }
}
