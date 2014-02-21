<?php
require_once('../../main.inc.php');
require_once("libAgenda.php");


$db->query("UPDATE ".MAIN_DB_PREFIX."actioncomm SET fk_user_action = ".$newTabUser2[$_REQUEST['setUser']].", datep2 = '".$db->idate(($_REQUEST['end']/1000)-60)."', datep = '".$db->idate($_REQUEST['start']/1000)."' WHERE id =".$_REQUEST['id']);