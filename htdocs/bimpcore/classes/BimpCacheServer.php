<?php

class BimpCacheServer
{

    public static $cache = array();
    public static $type = 'session';

    public function getCacheServeur($key, $true_val = true)
    {
        if (isset(self::$cache[$key])) {
            if ($true_val) {
                if (self::$cache[$key] == 'valnull') {
                    return null;
                }

                if (self::$cache[$key] == 'valvide') {
                    return '';
                }
            }

            return self::$cache[$key];
        }

        return null;
    }

    public function setCacheServeur($key, $value)
    {
        if (is_null($value)) {
            self::$cache[$key] = 'valnull';
        } elseif ($value === '') {
            self::$cache[$key] = 'valvide';
        } else {
            self::$cache[$key] = $value;
        }

        return true;
    }

    public function cache_exists($key)
    {
        return (isset(self::$cache[$key]));
    }
    
    public function getType()
    {
        return static::$type;
    }
}
