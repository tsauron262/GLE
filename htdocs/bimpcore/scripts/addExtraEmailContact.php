<?php

die('Désactivé'); 

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';

$extrafields = new ExtraFields($db);
$extrafields->addExtraField('contact_default', 'Contact email facturation par défaut', 'sellist', 100, '', 'societe', 0, 0, 0, 'a:1:{s:7:"options";a:1:{s:37:"socpeople:lastname:rowid::fk_soc=$ID$";N;}}', 1, '', 1);
echo 'Fin';