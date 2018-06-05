<?php

// Database admin server
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS_WORD', 'root');
define('DB_NAME', 'test-billet');

// Database customer server
define('DB_HOST_2', 'localhost');
define('DB_USER_2', 'root');
define('DB_PASS_WORD_2', 'root');
define('DB_NAME_2', 'BIMP_TEST_ZOOM');

// Extern user (prestashop)
define('EXTERN_USER', 2);   // Ne pas changer cette valeur (référencé en base)

// Paths and URL
define('PATH', realpath(dirname(__FILE__)));
define('IS_MAIN_SERVER', true);

define('PRESTA_URL', "http://localhost/zoom");

define('PRESTA_PREF', "ps_");

