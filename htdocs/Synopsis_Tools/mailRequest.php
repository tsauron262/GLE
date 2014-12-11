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



if(isset($_REQUEST['action']) && $_REQUEST['action'] == "fusionCli" && isset($_REQUEST['id']) && isset($_REQUEST['id2'])){
    $id = $_REQUEST['id'];
    $id2 = $_REQUEST['id2'];
    $db->query("UPDATE ".MAIN_DB_PREFIX."propal SET fk_soc = ".$id2." WHERe fk_soc = ".$id);
    $db->query("UPDATE ".MAIN_DB_PREFIX."synopsischrono SET fk_societe = ".$id2." WHERe fk_societe = ".$id);
    $db->query("UPDATE ".MAIN_DB_PREFIX."commande SET fk_soc = ".$id2." WHERe fk_soc = ".$id);
    $db->query("UPDATE ".MAIN_DB_PREFIX."facture SET fk_soc = ".$id2." WHERe fk_soc = ".$id);
    $db->query("UPDATE ".MAIN_DB_PREFIX."contrat SET fk_soc = ".$id2." WHERe fk_soc = ".$id);
    $db->query("UPDATE ".MAIN_DB_PREFIX."synopsisdemandeinterv SET fk_soc = ".$id2." WHERe fk_soc = ".$id);
    $db->query("UPDATE ".MAIN_DB_PREFIX."fichinter SET fk_soc = ".$id2." WHERe fk_soc = ".$id);
    $db->query("UPDATE ".MAIN_DB_PREFIX."socpeople SET fk_soc = ".$id2." WHERe fk_soc = ".$id);

    header("Location:" .DOL_URL_ROOT."/comm/fiche.php?socid=".$id);
}

if(isset($_REQUEST['action']) && $_REQUEST['action'] == "majRevision"){
    $sql = $db->query("SELECT * FROM llx_synopsischrono WHERE orig_ref is not null AND revision > 0");
    while($ligne = $db->fetch_object($sql)){
        if($ligne->revision == 1)
            $where = "ref = '".$ligne->orig_ref."'";
        else
            $where = "orig_ref = '".$ligne->orig_ref."' AND revision = ".($ligne->revision-1);
        $db->query("Update llx_synopsischrono SET revisionNext = ".$ligne->id." WHERE ".$where)."<br/>";
    }
}


echo "Rien a faire";