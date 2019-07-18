<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';

//llxHeader();

$i=0;
$sql = $db->query("SELECT rowid FROM `llx_facture` WHERE `date_valid` > '2019-06-30 00:00:00' AND fk_user_comm = 0 AND `facnumber` LIKE 'AVS%'");
while($ln = $db->fetch_object($sql)){
    $i++;
    echo $i." Fact : ".$ln->rowid."<br/>";
}
        
//llxFooter();
