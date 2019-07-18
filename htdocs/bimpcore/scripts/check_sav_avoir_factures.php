<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';

//llxHeader();

$i=0;
$sql = $db->query("SELECT rowid FROM `llx_facture` WHERE `date_valid` > '2019-06-30 00:00:00' AND fk_user_comm = 0 AND `facnumber` LIKE 'AVS%'");
while($ln = $db->fetch_object($sql)){
    
    
    $sql2 = $db->query("SELECT fk_socpeople FROM `llx_element_contact` WHERE `element_id` IN (SELECT `fk_facture` FROM `llx_societe_remise_except` WHERE `fk_facture_source` IN (".$ln->rowid.")) AND `fk_c_type_contact` = 50");
    if($db->num_rows($sql2) > 0){
        $ln2 = $db->fetch_object($sql2);
           $i++;
        echo $i." Fact : ".$ln->rowid." | ".$ln2->fk_socpeople."<br/>";
//        $db->query("INSERT INTO `llx_element_contact`(`statut`, `element_id`, `fk_c_type_contact`, `fk_socpeople`) VALUES (4,".$ln->rowid.",50,".$ln2->fk_socpeople.")");
    }
    
}
        
//llxFooter();
