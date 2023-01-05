<?php
global $db;



require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';

llxHeader();


global $db;

$sql = $db->query("SELECT * FROM `llx_societe_extrafields` WHERE `num_sepa` != '' AND `num_sepa` is not null ORDER BY `num_sepa`  DESC ");
while ($ln = $db->fetch_object($sql)){
    $rib = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_SocBankAccount');
    $rib->set('fk_soc', $ln->fk_object);
    $rib->set('label', 'Default');
    $rib->set('rum', $ln->num_sepa);
    $rib->create();
}


echo 'fin';