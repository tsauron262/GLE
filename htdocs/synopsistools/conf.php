<?php



define("NOLOGIN", 1);

require_once('../main.inc.php');


$constAGarder = array("ID_ERP", "CONSUL_SERVICES_PRIORITY_READ", "CONSUL_SERVERS", "CONSUL_SERVICES_PRIORITY_WRITE", "CONSUL_SERVICES_USE_FOR_WRITE", "CONSUL_SERVICES_USE_FOR_READ", "WEBHOOK_PATH_GIT_PULL", "WEBHOOK_PATH_REDIS_RESTART", "WEBHOOK_SERVER", "WEBHOOK_SECRET_REDIS_RESTART", "WEBHOOK_SECRET_GIT_PULL", "WEBHOOK_SECRET_REDIS_RESTART");
$varAGarder = array("dolibarr_main_auth_ldap_host");
//non trouvée webhook_server_name   consul_cloud_name

$conf =  '
// Rendered by pongo 4.0.2
global $dolibarr_main_url_root;
$dolibarr_main_url_root="https://erp.bimp.fr/lease";
$dolibarr_main_document_root="/usr/local/www/webapp/lease/";
$dolibarr_main_data_root="/usr/local/data1/lease/data-nfs";
define("PATH_TMP", "/usr/local/data1/lease/tmp/");
define("BIMP_EXTENDS_ENTITY", "prolease");
$dolibarr_main_db_port= "3306";
$dolibarr_main_db_host= "10.192.20.221";
$dolibarr_main_db_pass= "Iev4eeshayahw3nu2ik3kach";
$dolibarr_main_db_name= "ERP_PROD_LEASE";
$dolibarr_main_db_user= "lease";
$dolibarr_main_db_prefix= "llx_";
$dolibarr_main_db_type="mysqlic";
$dolibarr_main_db_character_set="";
$dolibarr_main_db_collation="";




$dolibarr_main_authentication="ldap";
$dolibarr_main_auth_ldap_port="636";
$dolibarr_main_auth_ldap_version="3";
$dolibarr_main_auth_ldap_servertype="activedirectory";  // openldap, activedirectory or egroupware
$dolibarr_main_auth_ldap_login_attribute="userPrincipalName";   // Ex: uid or samaccountname for active directory
$dolibarr_main_auth_ldap_dn="OU=Olys,OU=Filiales,OU=Groupe LDLC.COM,DC=siege,DC=ldlc,DC=com"; // Ex: ou=users,dc=my-domain,dc=com
$dolibarr_main_auth_ldap_filter = "(&(|(userPrincipalName=%1%)(sAMAccountName=%1%))(!(userAccountControl:1.2.840.113556.1.4.803:=2)))";
$dolibarr_main_auth_ldap_admin_login="CN=LDAP ERP GLE,CN=Users,DC=siege,DC=ldlc,DC=com";
$dolibarr_main_auth_ldap_admin_pass="w2L2!U:Qk7dhQd-8_4";
$dolibarr_main_auth_ldap_debug="false";
define("LDAP_MOD_AD", 1);

// Use database to save PHP session data
define("USE_BDD_FOR_SESSION", 1);

define("LIST_DOMAINE_VALID", serialize (array("bimp.local","bimp-partners.fr","bimp.fr")));
define("TAB_IP_INTERNE", "78.217.11.123,88.125.45.38,79.141.2.247,82.239.30.139,79.141.2.249,88.177.112.5,193.251.68.25,78.244.4.41,79.141.2.248,82.247.120.103,79.141.2.240,79.141.2.241,88.176.75.29,85.171.69.57");

define("CONSUL_SERVICE_DATABASE", "bderp");
define("CONSUL_SET_MAIN_DB_HOST", true);
define("CONSUL_READ_FROM_WRITE_DB_HOST", true);
define("CONSUL_READ_FROM_WRITE_DB_HOST_TIME", 60);
define("CONSUL_USE_REDIS_CACHE", true);
define("CONSUL_REDIS_CACHE_TTL", 120);
define("REDIS_USE_LOCALHOST", true);
define("REDIS_LOCALHOST_SOCKET", "/var/run/redis/redis.sock");
define("REDIS_USE_CONSUL_SEARCH", false);
define("CONSUL_SERVICE_REDIS", "rediserpdev");
define("REDIS_USE_HOST", false);
define("REDIS_HOST", "10.192.20.92:6379");

//define("IP_ADMIN", "78.217.11.123");
//define("MYSQL_SLOW_LOG", "/var/lib/mysql/hbg-pa41-slow.log");

define("DIR_SYNCH_COMPTA", "/usr/local/data1/lease/tmp/");
define("MAX_TIME_LOG", 6);
define("OLD_PRICE_FOURN", 1);
define("PRODUCTION_APPLE", true);
define("MOD_DEV_SYN", false);
define("DOMAINE_GROUP_ID", 3);
define("CHAINE_CALDAV", "fgfjfhjfytcrt");

$dolibarr_main_prod="0";
$dolibarr_nocsrfcheck="1";
$dolibarr_main_force_https="0";
// $dolibarr_main_force_https="https://erp2.bimp.fr";
// define("REDIRECT_DOMAINE", $dolibarr_main_force_https);
$dolibarr_main_cookie_cryptkey="eighei1oowoh9hi9Leighudae2aeGhu0";
$dolibarr_mailing_limit_sendbyweb="0";
';


foreach($constAGarder as $const){
    if(defined($const)){
        $val = '';
        eval('$val = '.$const.";");
        if(!is_int($val))
            $val ='\''.$val.'\'';
        $conf .= "\n".'define("'.$const.'", '. $val .');';
    }
    else {
        die($const ."non définit");
    }
}

foreach($varAGarder as $var){
    if(isset($var)){
        $val = '';
        eval('$val = $'.$var.";");
        if(!is_int($val))
            $val ='"'.$val.'"';
        $conf .= "\n".'$'.$var.' = '.$val.';';
    }
    else {
        die($const ."non définit");
    }
}

//echo nl2br($conf);


$conf = '<?php
'.$conf.'
?>';


file_put_contents(DOL_DOCUMENT_ROOT.'/conf/conf.php', $conf);