<?php

define('NOLOGIN', '1');

require_once("../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/Bimp_Lib.php';

//llxHeader();

echo '<!DOCTYPE html>';
echo '<html lang="fr">';

echo '<head>';
//echo '<link rel="stylesheet" type="text/css" href="' . DOL_URL_ROOT . '/bimpcore/views/css/ticket.css' . '"/>';
echo '<script src="/test2/includes/jquery/js/jquery.min.js?version=6.0.4" type="text/javascript"></script>';
echo '</head>';

echo '<body>';

global $db;
$bdb = new BimpDb($db);


echo '</body></html>';

//llxFooter();
