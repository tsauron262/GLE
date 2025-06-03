<?php
if (!defined('MAX_TIME_LOG'))
{
    define("MAX_TIME_LOG", 6);
}

if (!defined('CONSUL_READ_FROM_WRITE_DB_HOST_TIME'))
{
    define("CONSUL_READ_FROM_WRITE_DB_HOST_TIME", 60);
}

if (!defined('CONSUL_REDIS_CACHE_TTL'))
{
    define("CONSUL_REDIS_CACHE_TTL", 120);
}
