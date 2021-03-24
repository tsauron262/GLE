<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/classes/BimpCacheServer.php';

class BimpCacheRedis extends BimpCacheServer
{

    protected static $REDIS_LOCALHOST_SOCKET = "/var/run/redis/redis.socks";
    protected static $redisObj = null;
    protected static $isActif = true;
    protected static $isInit = false;
    public static $type = 'server';

    public function initCacheServeur()
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

        $result = self::$redisObj->get($key);

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

        self::$redisObj->set($key, $value);

        return true;
    }

    public function cache_exists($key)
    {
        // todo : trouver meilleur méthode
        return !is_null($this->getCacheServeur($key, false));
    }
}
