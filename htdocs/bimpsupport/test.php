<?php

require_once("../main.inc.php");



llxHeader();



$sql = $db->query('SELECT email FROM `llx_societe` WHERE rowid IN (SELECT id_client FROM `llx_bs_sav` WHERE status = 9 AND code_centre = "P")');

$tabMail = array();
foreach($db->fetch_object($sql) as $ln){
    $mail = $ln->email;
    if(stripos($mail, "@")){
        $tabMail[] = $mail;
    }
}


print_r($tabMail);




llxFooter();
