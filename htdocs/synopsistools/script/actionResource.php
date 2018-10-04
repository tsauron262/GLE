<?php


require("../../main.inc.php");


llxHeader();


$sql = $db->query("SELECT * FROM `llx_actioncomm` WHERE `id` not in (SELECT `fk_actioncomm` FROM `llx_actioncomm_resources` WHERE 1) ORDER BY `llx_actioncomm`.`datec` DESC ");

while ($ln = $db->fetch_object($sql)){
    $db->query("INSERT INTO `llx_actioncomm_resources`"
            . "(`fk_actioncomm`, `element_type`, `fk_element`, `answer_status`, `mandatory`, `transparency`) VALUES "
            . "(".$ln->id.",'user',".$ln->fk_user_action.",0,0,1)");
}