<?php

require_once('../../main.inc.php');
require_once("libAgenda.php");
require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';


if ($_REQUEST['id'] > 0) {
    $actioncomm = new ActionComm($db);
    $actioncomm->fetch($_REQUEST['id']);
    $actioncomm->fetch_userassigned();
    
    if(!$user->rights->agenda->myactions->create || (!$user->rights->agenda->allactions->create && $user->id != $actioncomm->userownerid)){
        //pas le droit
    }else{
//    $actioncomm->userownerid = $newTabUser2[$_REQUEST['setUser']];
    $actioncomm->datep = $_REQUEST['start'] / 1000;
    $actioncomm->datef = ($_REQUEST['end'] / 1000) - 60;
    if($_REQUEST['clone'] == "true"){
        $actioncomm->fetch_optionals($actioncomm->id);
        $actioncomm->array_options['options_uri'] = "";
        $actioncomm->add($user);
    }
    else
        $actioncomm->update($user);
    }
}

//$db->query("UPDATE ".MAIN_DB_PREFIX."actioncomm SET fk_user_action = "..", datep2 = '".$db->idate(()."', datep = '".$db->idate($_REQUEST['start']/1000)."' WHERE id =".);