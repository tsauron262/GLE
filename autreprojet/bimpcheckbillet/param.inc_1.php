<?php

// Database admin server
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS_WORD', 'root');
define('DB_NAME', 'test');

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

//define('URL_PRESTA', "http://192.168.0.78/~tilito/prestashop");
//define('URL_CHECK', "http://192.168.0.78/~tilito/bimp-erp/autreprojet/bimpcheckbillet/");

define('URL_PRESTA', "localhost/prestashop");
define('URL_CHECK', "localhost/bimpcheckbillet/");




define('PRESTA_PREF', "ps_");

define('USE_ATTRIBUTE', true);

// Ids categories parent in prestashop
define('ID_CATEG_FESTIVAL', 96);
define('ID_CATEG_CONCERT', 97);
define('ID_CATEG_SPECTACLE', 98);
define('ID_CATEG_AUTRE', 99);
