<?php
require_once('../../main.inc.php');
require_once("libAgenda.php");
require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';


$actioncomm = new ActionComm($db);
$actioncomm->fetch($_REQUEST['id']);
$actioncomm->usertodo->id = $newTabUser2[$_REQUEST['setUser']];
$actioncomm->datep = $_REQUEST['start']/1000;
$actioncomm->datef = ($_REQUEST['end']/1000)-60;
$actioncomm->update($user);

//$db->query("UPDATE ".MAIN_DB_PREFIX."actioncomm SET fk_user_action = "..", datep2 = '".$db->idate(()."', datep = '".$db->idate($_REQUEST['start']/1000)."' WHERE id =".);