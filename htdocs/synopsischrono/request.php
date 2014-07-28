<?php

require '../main.inc.php';

require_once(DOL_DOCUMENT_ROOT . "/synopsischrono/Chrono.class.php");
$chrono = new Chrono($db);
$chrono->fetch($_REQUEST['id']);
$chrono->getValues();
$idEtat = 1056;

$ok = false;

if (isset($_REQUEST['actionEtat'])) {
    $action = $_REQUEST['actionEtat'];
    
    if($chrono->description == "N/C")
        $chrono->description = "";

    if ($action == "devisOk" && $chrono->propal->id > 0) {
        $chrono->description = (($chrono->description != "")? $chrono->description."\n\n" : "");
        $chrono->description .= "Devis accepté le ".  date('d-m-y H:i');
        $chrono->update($chrono->id);
        require_once(DOL_DOCUMENT_ROOT . "/core/modules/propale/modules_propale.php");
        $chrono->propal->cloture($user, 2, "Auto via SAV");
        $chrono->setDatas($chrono->id, array($idEtat => 0));
        $ok = true;
    }
    if ($action == "devisKo" && $chrono->propal->id > 0) {
        $chrono->description = (($chrono->description != "")? $chrono->description."\n\n" : "");
        $chrono->description .= "Devis refusé le ".  date('d-m-y H:i');
        $chrono->update($chrono->id);
        require_once(DOL_DOCUMENT_ROOT . "/core/modules/propale/modules_propale.php");
        $chrono->propal->cloture($user, 3, "Auto via SAV");
        $chrono->setDatas($chrono->id, array($idEtat => 9));
        $ok = true;
    }
    if($action == "pieceOk"){
        $chrono->description = (($chrono->description != "")? $chrono->description."\n\n" : "");
        $chrono->description .= "Pièce reçue le ".  date('d-m-y H:i');
        $chrono->update($chrono->id);
        $chrono->setDatas($chrono->id, array($idEtat => 0));
        $ok = true;
    }
    if($action == "attenteClient"){
        $chrono->description = (($chrono->description != "")? $chrono->description."\n\n" : "");
        $chrono->description .= "Attente client depuis le ".  date('d-m-y H:i');
        $chrono->update($chrono->id);
        $chrono->setDatas($chrono->id, array($idEtat => 2));
        $ok = true;
    }
    
    if($action == "restituer"){
        $chrono->description = (($chrono->description != "")? $chrono->description."\n\n" : "");
        $chrono->description .= "Restitué le ".  date('d-m-y H:i');
        $chrono->update($chrono->id);
        $chrono->setDatas($chrono->id, array($idEtat => 999));
        $ok = true;
    }
}

if ($ok)
    header("Location:fiche.php?id=" . $_GET['id']);

