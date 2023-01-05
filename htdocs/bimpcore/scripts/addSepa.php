<?php

die('Désactivé');

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';


$extrafields = new ExtraFields($db);
$extrafields->addExtraField('num_sepa', 'Numéro de SEPA', 'varchar', 1, 25, 'societe');

echo 'ok fin';
