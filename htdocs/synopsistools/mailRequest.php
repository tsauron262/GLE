<?php

require '../main.inc.php';
set_time_limit(150);


if (isset($_REQUEST['action']) && $_REQUEST['action'] == "annulObj" && isset($_SERVER["HTTP_REFERER"]) && $_SERVER["HTTP_REFERER"] != '') {
    mailSyn("tommy@drsi.fr", "Demande annulation", "Annuler Obj : " . $_SERVER["HTTP_REFERER"] . "<br/><br/><a href='" . DOL_URL_ROOT . "/synopsistools/mailRequest.php?action=deblockComm&id=" . $_REQUEST['id'] . "'>Deverouiller</a>");
    header("Location:" . $_SERVER["HTTP_REFERER"]);
}



if (isset($_REQUEST['action']) && $_REQUEST['action'] == "deblockComm" && isset($_REQUEST['id'])) {
    $id = $_REQUEST['id'];
    $db->query("UPDATE " . MAIN_DB_PREFIX . "facture SET paye = 0, fk_statut = 1 WHERe rowid = " . $id);
    header("Location:" . DOL_URL_ROOT . "/compta/facture.php?id=" . $id);
}

if (isset($_REQUEST['action']) && $_REQUEST['action'] == "majCommerciaux") {
    $result = $db->query("SELECT * FROM llx_societe WHERE idprof4 is not null && idprof4 != ''");
    $db->query("UPDATE llx_societe SET fk_pays = '1' WHERE fk_pays = '0';");
    while ($ligne = $db->fetch_object($result)) {
        $result2 = $db->query("SELECT fk_object FROM llx_user_extrafields WHERE id8sens = '" . $ligne->idprof4 . "'");
        if ($db->num_rows($result2) > 0) {
            while ($ligne2 = $db->fetch_object($result2)) {
                setIdComm($ligne->rowid, $ligne2->fk_object);
                echo "Ajout grace a la fiche" . $ligne->rowid . "<br/>";
            }
        } else {
            $result2 = $db->query("SELECT * FROM  `llx_element_element`  WHERE  `fk_source` = '" . $ligne->idprof4 . "' AND  `sourcetype` LIKE  'idUser8Sens' AND  `targettype` LIKE  'idUserGle'");
            if ($db->num_rows($result2) > 0) {
                while ($ligne2 = $db->fetch_object($result2)) {
                    setIdComm($ligne->rowid, $ligne2->fk_target);
                    echo "Ajout grace a la table element_element" . $ligne->rowid . "<br/>";
                }
            }
        }
    }
}

function setIdComm($soc, $idComm) {
    global $db;
    $db->query("DELETE FROM `llx_societe_commerciaux` WHERE fk_soc = '" . $soc . "';");
    $db->query("INSERT INTO `llx_societe_commerciaux` (`rowid`, `fk_soc`, `fk_user`) VALUES (NULL, '" . $soc . "', '" . $idComm . "');");
    $db->query("UPDATE llx_societe SET idprof4 = '' WHERE rowid = '" . $soc . "';");
}

if (isset($_REQUEST['action']) && $_REQUEST['action'] == "fusionCli" && isset($_REQUEST['id']) && $_REQUEST['id'] > 0 && isset($_REQUEST['id2'])) {
    $id = $_REQUEST['id'];
    $id2 = $_REQUEST['id2'];
    if($id2 > 0){
    $db->query("UPDATE " . MAIN_DB_PREFIX . "propal SET fk_soc = " . $id2 . " WHERe fk_soc = " . $id);
    $db->query("UPDATE " . MAIN_DB_PREFIX . "synopsischrono SET fk_soc = " . $id2 . " WHERe fk_soc = " . $id);
    $db->query("UPDATE " . MAIN_DB_PREFIX . "commande SET fk_soc = " . $id2 . " WHERe fk_soc = " . $id);
    $db->query("UPDATE " . MAIN_DB_PREFIX . "facture SET fk_soc = " . $id2 . " WHERe fk_soc = " . $id);
    $db->query("UPDATE " . MAIN_DB_PREFIX . "contrat SET fk_soc = " . $id2 . " WHERe fk_soc = " . $id);
    $db->query("UPDATE " . MAIN_DB_PREFIX . "synopsisdemandeinterv SET fk_soc = " . $id2 . " WHERe fk_soc = " . $id);
    $db->query("UPDATE " . MAIN_DB_PREFIX . "fichinter SET fk_soc = " . $id2 . " WHERe fk_soc = " . $id);
    $db->query("UPDATE " . MAIN_DB_PREFIX . "socpeople SET fk_soc = " . $id2 . " WHERe fk_soc = " . $id);
    $db->query("UPDATE " . MAIN_DB_PREFIX . "actioncomm SET fk_soc = " . $id2 . " WHERe fk_soc = " . $id);
    }

    header("Location:" . DOL_URL_ROOT . "/comm/card.php?socid=" . $id);
}

if (isset($_REQUEST['action']) && $_REQUEST['action'] == "majRevision") {
    $sql = $db->query("SELECT * FROM llx_synopsischrono WHERE orig_ref is not null AND revision > 0");
    while ($ligne = $db->fetch_object($sql)) {
        if ($ligne->revision == 1)
            $where = "ref = '" . $ligne->orig_ref . "'";
        else
            $where = "orig_ref = '" . $ligne->orig_ref . "' AND revision = " . ($ligne->revision - 1);
        $db->query("Update llx_synopsischrono SET revisionNext = " . $ligne->id . " WHERE " . $where) . "<br/>";
    }
}


echo "Rien a faire";
