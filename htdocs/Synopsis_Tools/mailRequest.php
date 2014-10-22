<?php

require '../main.inc.php';


if(isset($_REQUEST['action']) && $_REQUEST['action'] == "annulObj" && isset($_SERVER["HTTP_REFERER"]) && $_SERVER["HTTP_REFERER"] != ''){
    mailSyn("tommy@drsi.fr", "Demande annulation", "Annuler Obj : ".$_SERVER["HTTP_REFERER"]."<br/><br/><a href='".DOL_URL_ROOT."/Synopsis_Tools/mailRequest.php?action=deblockComm&id=".$_REQUEST['id']."'>Deverouiller</a>");
    header("Location:" . $_SERVER["HTTP_REFERER"]);
}



if(isset($_REQUEST['action']) && $_REQUEST['action'] == "deblockComm" && isset($_REQUEST['id'])){
    $id = $_REQUEST['id'];
    $db->query("UPDATE ".MAIN_DB_PREFIX."facture SET paye = 0, fk_statut = 1 WHERe rowid = ".$id);
    header("Location:" .DOL_URL_ROOT."/compta/facture.php?id=".$id);
}

echo "Rien a faire";