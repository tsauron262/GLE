<?php

// Database admin server
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS_WORD', '$mokinU2');
define('DB_NAME', 'test_billet');

// Database customer server
define('DB_HOST_2', 'localhost');
define('DB_USER_2', 'root');
define('DB_PASS_WORD_2', '$mokinU2');
define('DB_NAME_2', 'prestashop');

// Extern user (prestashop)
define('EXTERN_USER', 2);   // Ne pas changer cette valeur (référencé en base)

// Paths and URL
define('PATH', realpath(dirname(__FILE__)));
define('IS_MAIN_SERVER', true);

define('URL_PRESTA', "http://192.168.0.78/~tilito/prestashop");
define('URL_CHECK', "http://192.168.0.78/~tilito/bimp-erp/autreprojet/bimpcheckbillet/");

define('PRESTA_PREF', "ps_");

