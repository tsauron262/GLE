<?php

require_once("../../main.inc.php");


$sql = $db->query("SELECT COUNT(*) as nbSerial, SUBSTRING(`serial`, LENGTH(`serial`)-3, 4) as fin, MIN(id) as minEquipment, MIN(id_product) as minProd, MAX(id_product) as maxProd, COUnT(DISTINCT(id_product)) as nbProd FROM `llx_be_equipment` WHERE ( LENGTH(serial) = 13 || LENGTH(serial) = 12) AND id_product > 0 AND SUBSTRING(`serial`, LENGTH(`serial`)-3, 4) NOT IN (SELECT code_config FROM llx_product_extrafields WHERE code_config IS NOT NULL) GROUP BY fin ORDER BY COUNT(*) DESC");

$erreurs = $info = $ok = array();

$go = (isset($_REQUEST['action']) && $_REQUEST['action'] == 'go')? 1 : 0;

while($ln = $db->fetch_object($sql)){
    if($ln->nbSerial > 50){
        if($ln->nbProd > 1){
            $erreurs[] = $ln->fin." plusieurs prod (".$ln->minProd.", ".$ln->maxProd.", ...)";
        }
        else{
            if(!isset($ok[$ln->fin])){
                $ok[$ln->fin]= 0; 
                $sql2 = $db->query("SELECT COUNT(*) as nbSerial  FROM `llx_be_equipment` WHERE ( LENGTH(serial) = 13 || LENGTH(serial) = 12) AND id_product = 0 AND serial LIKE '%".$ln->fin."'");
                $ln2 = $db->fetch_object($sql2);

                $info[] = "OK prod ".$ln->minProd.' code config '.$ln->fin. '   corrigera '.$ln2->nbSerial. ' equipement SAV';

                if($go)
                    $db->query("UPDATE llx_product_extrafields SET code_config = '".$ln->fin."' WHERE fk_object = '".$ln->minProd."'"); 
            }
            else
                $erreurs[] = $ln->fin.' plusieurs ('.($ok[$ln->fin]+1).') fois ATTENTION......';
            $ok[$ln->fin]++;
            
            
        }
    }
}





$sql3 = $db->query("SELECT COUNT(*) as nbIdentique, serial FROM `llx_be_equipment` WHERE `id_product` > 0 AND ( LENGTH(serial) = 13 || LENGTH(serial) = 12) GROUP BY `serial`, id_product HAVING nbIdentique > 1  
ORDER BY COUNT(*)  DESC");
while($ln3 = $db->fetch_object($sql3)){
    $erreurs[] = $ln3->serial." plusieurs foix ... grave";
}





if($go){
    $sql4 = $db->query("SELECT code_config, fk_object FROM llx_product_extrafields WHERE code_config IS NOT NULL");
    while($ln4 = $db->fetch_object($sql4)){
        $db->query("UPDATE llx_be_equipment SET id_product = ".$ln4->fk_object." WHERE serial LIKE '%".$ln4->code_config."' and ( LENGTH(serial) = 13 || LENGTH(serial) = 12)");
    }
}


echo '<pre>';

print_r($info);

print_r($erreurs);