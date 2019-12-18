<?php

require_once("../../main.inc.php");


$sql = $db->query("SELECT COUNT(*) as nbSerial, SUBSTRING(`serial`, LENGTH(`serial`)-3, 4) as fin, MIN(id) as minEquipment, MIN(id_product) as minProd, COUnT(DISTINCT(id_product)) as nbProd FROM `llx_be_equipment` WHERE ( LENGTH(serial) = 13 || LENGTH(serial) = 12) AND id_product > 0 GROUP BY fin ORDER BY COUNT(*) DESC");

$erreurs = $info = $ok = array();

while($ln = $db->fetch_object($sql)){
    if($ln->nbSerial > 50){
        if($ln->nbProd > 1){
            $erreurs[] = $ln->fin." plusieurs prod ...";
        }
        else{
            if(!isset($ln->fin))
                $ok[$ln->fin]= 0;  
            else
                $erreurs[] = $ln->fin.' plusieurs fois ATTENTION......';
            $ok[$ln->fin]++;
            
            $sql2 = $db->query("SELECT COUNT(*) as nbSerial  FROM `llx_be_equipment` WHERE ( LENGTH(serial) = 13 || LENGTH(serial) = 12) AND id_product = 0 AND serial LIKE '%".$ln->fin."'");
            $ln2 = $db->fetch_object($sql2);
            
            $info[] = "OK prod ".$ln->minProd.' code config '.$ln->fin. '   corrigera '.$ln2->nbSerial. ' equipement SAV';
        }
    }
}


$sql3 = $db->query("SELECT COUNT(*) as nbIdentique, serial FROM `llx_be_equipment` WHERE `id_product` > 0 AND ( LENGTH(serial) = 13 || LENGTH(serial) = 12) GROUP BY `serial`, id_product HAVING nbIdentique > 1  
ORDER BY COUNT(*)  DESC");
while($ln3 = $db->fetch_object($sql3)){
    $erreurs[] = $ln3->serial." plusieurs foix ... grave";
}



echo '<pre>';

print_r($info);

print_r($erreurs);