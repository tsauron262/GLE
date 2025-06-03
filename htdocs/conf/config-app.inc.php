<?php
global $dolibarr_main_url_root;
$dolibarr_main_db_prefix= "llx_";
$dolibarr_main_db_character_set='utf8';
$dolibarr_main_db_collation='utf8_unicode_ci';
define("CONSUL_SET_MAIN_DB_HOST", true);
define("CONSUL_READ_FROM_WRITE_DB_HOST", true);
define("CONSUL_USE_REDIS_CACHE", true);
$dolibarr_main_prod="0";
$dolibarr_nocsrfcheck="1";
$dolibarr_main_force_https="0";
$dolibarr_mailing_limit_sendbyweb="0";
