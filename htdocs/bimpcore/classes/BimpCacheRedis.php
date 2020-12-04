<?php

class BimpCacheRedis{
    static $REDIS_LOCALHOST_SOCKET = "/var/run/redis/redis.sock";
    static $redisObj = null;
    
    public static function init(){
        if(is_null(static:: $redisObj)){
            static::$redisObj = new Redis();
            static::$redisObj->connect(static::$REDIS_LOCALHOST_SOCKET);
        }
    }
    
    public static function getCacheServeur($key){
        static::init();
        $result = static::$redisObj->get($key);
        if($result == '')
            return null;
        if($result == 'valvide')
            return '';
        $resultO = json_decode($result, true);
        if((json_last_error() == JSON_ERROR_NONE)){
            $result = $resultO;
        }
        return $result;
    }
    
    public static function setCacheServeur($key, $value){
        if(is_null($value))
            $value = "valnull";
        if($value == '')
            $value = "valvide";
        static::init();
        if(is_array($value))
            $value = json_encode ($value);
        static::$redisObj->set($key, $value);
    }
}

