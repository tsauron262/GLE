<?php

die('Désactivé'); 

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

top_htmlhead('', 'CHECK Dol lines', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

echo 'DEBUT <br/><br/>';

global $db;

$nb = GETPOST('nb');
if($nb < 1)
    $nb = 500;

$html = '';
$bimpComm = BimpCache::getBimpObjectInstance('bimpcommercial', 'BimpComm');
$bimpComm->checkAllObjectLine(0, $html, $nb);

echo $html."<br/>";
print_r($errors);
