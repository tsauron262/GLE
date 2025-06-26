<?php
global $dolibarr_main_url_root;
$dolibarr_main_db_prefix= "llx_";
$dolibarr_main_db_character_set='utf8';
$dolibarr_main_db_collation='utf8_unicode_ci';
$dolibarr_main_prod="0";
$dolibarr_nocsrfcheck="1";
$dolibarr_main_force_https="0";
$dolibarr_mailing_limit_sendbyweb="0";

$dolibarr_main_url_root="https://erp.bimp.fr/".$instance;
$dolibarr_main_document_root="/usr/local/www/webapp/".$instance."/";
$dolibarr_main_data_root="/usr/local/data1/".$instance."/data-nfs";
define("PATH_TMP", "/usr/local/data1/".$instance."/tmp/");

$dolibarr_main_db_type="mysqlic";

define("WEBHOOK_PATH_GIT_PULL", "/hooks/".$instance);
define("WEBHOOK_PATH_GIT_LOG", "/hooks/log-".$instance);
