<?php

require_once("../../main.inc.php");


$sql = $db->query("SELECT COUNT(*) as nbSerial, SUBSTRING(`serial`, LENGTH(`serial`)-3, 4) as fin, MIN(id) as minEquipment, MIN(id_product) as minProd, COUnT(DISTINCT(id_product)) as nbProd FROM `llx_be_equipment` WHERE ( LENGTH(serial) = 13 || LENGTH(serial) = 12) AND id_product > 0 GROUP BY fin ORDER BY COUNT(*) DESC");

$erreurs = $ok = array();

while($ln = $db->fetch_object($sql)){
    if($ln->nbSerial > 50){
        if($ln->nbProd > 1){
            $erreurs[] = $ln->fin." plusieurs prod ...";
        }
        else{
            $sql2 = $db->query("SELECT COUNT(*) as nbSerial  FROM `llx_be_equipment` WHERE ( LENGTH(serial) = 13 || LENGTH(serial) = 12) AND id_product = 0 AND serial LIKE '%".$ln->fin."'");
            $ln2 = $db->fetch_object($sql2);
            
            $ok[] = "OK prod ".$ln->minProd.' code config '.$ln->fin. '   corrigera '.$ln2->nbSerial. ' equipement SAV';
        }
    }
}


echo '<pre>';

print_r($ok);

print_r($erreurs);