<?php


require("../../main.inc.php");


llxHeader();

//$db->query("DELETE FROM llx_synopsisfichinter_User_PrixTypeInterv WHERE user_refid != 49");


$sql = $db->query("SELECT * FROM `llx_synopsisfichinter_User_PrixTypeInterv` WHERE `user_refid` = 49");
while($ln = $db->fetch_object($sql))
        $tabP[$ln->typeInterv_refid] = $ln->prix_ht;

$sql2 = $db->query("SELECT * FROM `llx_user` WHERE rowid NOT IN (SELECT fk_user FROM `llx_synopsisfichinter_User_PrixTypeInterv`)");

while($ln2 = $db->fetch_object($sql2))
        foreach($tabP as $idP => $prix)
            $db->query("INSERT INTO `llx_synopsisfichinter_User_PrixTypeInterv`(`user_refid`, `typeInterv_refid`, `prix_ht`) VALUES ('".$ln2->rowid."','".$idP."','".$prix."')");



echo "ok";