<?php
die('Désactivé'); 


require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';

$extrafields = new ExtraFields($db);
$extrafields->addExtraField('prime', 'Prime', 'double', 100, '24,8', 'facture', 0, 0, 0, '', 1, '', 1);
$extrafields->addExtraField('prime', 'Prime', 'double', 100, '24,8', 'propal', 0, 0, 0, '', 1, '', 1);
echo 'Fin';