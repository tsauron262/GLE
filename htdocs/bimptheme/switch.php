<?php


require_once("../main.inc.php");

// $conf->global->MAIN_THEME n'est pas mis à jour lors du switch
$sql = $db->query("SELECT * FROM `llx_user_param` WHERE `fk_user` = ".$user->id." AND `param` LIKE 'MAIN_THEME' ORDER BY `fk_user` ASC");

if($db->num_rows($sql) > 0){
    while ($ln = $db->fetch_object($sql)){
        if($ln->value == "BimpTheme")
            $db->query("DELETE FROM `llx_user_param` WHERE `fk_user` = ".$user->id." AND `param` LIKE 'MAIN_THEME'");
        else
            $db->query("UPDATE `llx_user_param` SET `value`= 'BimpTheme' WHERE `fk_user` = ".$user->id." AND `param` LIKE 'MAIN_THEME'");
    }
}
else{
    $db->query("INSERT INTO `llx_user_param`(`fk_user`, `entity`, `param`, `value`) VALUES (".$user->id.", ".$conf->entity.", 'MAIN_THEME', 'BimpTheme')");
}

if($_SERVER['HTTP_REFERER'] == "")
    $_SERVER['HTTP_REFERER'] = '/';
header("location:".  $_SERVER['HTTP_REFERER']); 
