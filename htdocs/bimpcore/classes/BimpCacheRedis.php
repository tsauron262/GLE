<?php

class BimpCacheRedis
{

    static $REDIS_LOCALHOST_SOCKET = "/var/run/redis/redis.socks";
    static $redisObj = null;
    static $isActif = false;

    public static function initCacheServeur()
    {
        if (class_exists('Redis')) {
            if (is_null(self::$redisObj)) {
                self::$redisObj = new Redis();

                try {
                    self::$redisObj->connect(self::$REDIS_LOCALHOST_SOCKET);
                } catch (Exception $e) {
                    self::$isActif = false;
                }
            }
        } else {
            self::$isActif = false;
        }
    }

    public static function getCacheServeur($key)
    {
        if (!self::$isActif)
            return null;

        self::initCacheServeur();

        if (!self::$isActif)
            return null;

        $result = self::$redisObj->get($key);

        if ($result == '')
            return null;

        if ($result == 'valvide')
            return '';

        $resultO = json_decode($result, true);

        if ((json_last_error() == JSON_ERROR_NONE)) {
            $result = $resultO;
        }

        return $result;
    }

    public static function setCacheServeur($key, $value)
    {
        if (!self::$isActif)
            return false;

        if (is_null($value))
            $value = "valnull";

        if ($value == '')
            $value = "valvide";

        self::initCacheServeur();

        if (is_array($value))
            $value = json_encode($value);

        self::$redisObj->set($key, $value);
    }
}
