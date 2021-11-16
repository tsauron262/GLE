<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/classes/BimpCacheServer.php';

class BimpCacheRedis extends BimpCacheServer
{

    protected static $REDIS_LOCALHOST_SOCKET = "";
    protected static $redisObj = null;
    protected static $isActif = true;
    protected static $isInit = false;
    public static $type = 'server';

    public function initCacheServeur()
    {
        if (class_exists('Redis')) {
            if(!defined('REDIS_LOCALHOST_SOCKET'))
            {
                dol_syslog("Constante REDIS_LOCALHOST_SOCKET non definie", LOG_WARNING);
                self::$REDIS_LOCALHOST_SOCKET = "/var/run/redis/redis.sock";
            }
            else        
                self::$REDIS_LOCALHOST_SOCKET = REDIS_LOCALHOST_SOCKET;
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

        self::$isInit = true;
    }

    public function getCacheServeur($key, $true_val = true)
    {
        if (!self::$isInit) {
            self::initCacheServeur();
        }

        if (!self::$isActif) {
            return parent::getCacheServeur($key);
        }

        try{
            $result = self::$redisObj->get($key);
        }
        catch (Exception $e) {
            BimpCore::addlog('Redis ingoignable '.$e->getMessage(), Bimp_Log::BIMP_LOG_ALERTE);
            return null;
        }
        
        if ($true_val) {
            if ($result == 'valnull')
                return null;

            if ($result == 'valvide')
                return '';

            $resultO = json_decode($result, true);

            if ((json_last_error() == JSON_ERROR_NONE)) {
                $result = $resultO;
            }
        }

        return $result;
    }

    public function setCacheServeur($key, $value)
    {
        if (!self::$isInit) {
            self::initCacheServeur();
        }

        if (!self::$isActif) {
            return parent::setCacheServeur($key, $value);
        }

        if (is_null($value))
            $value = "valnull";

        if ($value == '')
            $value = "valvide";

        if (is_array($value))
            $value = json_encode($value);

        
        
        try{
            self::$redisObj->set($key, $value);
        }
        catch (Exception $e) {
            BimpCore::addlog('Redis ingoignable '.$e->getMessage(), Bimp_Log::BIMP_LOG_ALERTE);
            return null;
        }

        return true;
    }

    public function cache_exists($key)
    {
        if (!self::$isInit) {
            self::initCacheServeur();
        }

        if (!self::$isActif) {
            return parent::getCacheServeur($key);
        }
        
        try{
            $ret = self::$redisObj->exists($key);
        }
        catch (Exception $e) {
            BimpCore::addlog('Redis ingoignable '.$e->getMessage(), Bimp_Log::BIMP_LOG_ALERTE);
            return null;
        }
        return $ret;
    }
}
