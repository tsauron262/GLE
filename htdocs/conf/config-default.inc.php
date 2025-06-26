<?php


$tabConstantDefault = array(
	"MAX_TIME_LOG" => 6,
	"CONSUL_READ_FROM_WRITE_DB_HOST_TIME" => 60,
	"CONSUL_REDIS_CACHE_TTL" => 120,
	"LIST_DOMAINE_VALID" => serialize (array("bimp.fr","bimp.local","bimp-partners.fr")),
	"DOMAINE_GROUP_ID" => 3,
	"TAB_IP_INTERNE" => "78.217.11.123,88.125.45.38,79.141.2.247,82.239.30.139,79.141.2.249,88.177.112.5,193.251.68.25,78.244.4.41,79.141.2.248,82.247.120.103,79.141.2.240,79.141.2.241,88.176.75.29,85.171.69.57,91.211.167.225",
	"OLD_PRICE_FOURN" => 1,
	"PRODUCTION_APPLE" => true,
	"LDAP_MOD_AD" => 1,
	"USE_BDD_FOR_SESSION" => 1,
	"CONSUL_SET_MAIN_DB_HOST" => true,
	"CONSUL_READ_FROM_WRITE_DB_HOST" => true,
	"CONSUL_USE_REDIS_CACHE" => true,
);

foreach($tabConstantDefault as $clef => $val){
	if (!defined($clef))
	{
		define($clef, $val);
	}
}