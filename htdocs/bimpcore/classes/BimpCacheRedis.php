<?php

class BimpCacheRedis
{

    static $REDIS_LOCALHOST_SOCKET = "/var/run/redis/redis.socks";
    static $redisObj = null;
    static $isActif = true;

    public static function initCacheServeur()
    {
        if (is_null(static:: $redisObj)) {
            static::$redisObj = new Redis();

            try {
                static::$redisObj->connect(static::$REDIS_LOCALHOST_SOCKET);
            } catch (Exception $e) {
                static::$isActif = false;
            }
        }
    }

    public static function getCacheServeur($key)
    {
        if (!static::$isActif)
            return null;
        
        self::initCacheServeur();
        
        if (!static::$isActif)
            return null;
        
        $result = static::$redisObj->get($key);
        
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
        if (!static::$isActif)
            return false;
        
        if (is_null($value))
            $value = "valnull";
        
        if ($value == '')
            $value = "valvide";
        
        self::initCacheServeur();
        
        if (is_array($value))
            $value = json_encode($value);
        
        static::$redisObj->set($key, $value);
    }
}
