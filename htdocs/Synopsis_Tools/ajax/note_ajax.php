<?php

require_once('../../main.inc.php');
$return = 0;

$forceRightEdit = false;

if (isset($_POST['url']) && isset($_POST['type']) && $_POST['type'] == 'note') {
    $url = $_POST['url'];
    $tabUrl = explode("?", $url);
    $tabUrl = explode("&", $tabUrl[1]);

    $nomId = "id";
    $nomChampNote = "note_public";

    
    if (stripos($url, '/societe/') !== false
            || stripos($url, '/comm/fiche.php')
            || stripos($url, '/categories/categorie.php')
            || stripos($url, '/Synopsis_Tools/allDocumentSoc.php') !== false) {
        $droit1 = $user->rights->societe->lire;
        $droit2 = $user->rights->societe->creer;
        $table = MAIN_DB_PREFIX . "societe";
        $nomId = "socid";
        $nomChampNote = "note";
    }
    if (stripos($url, '/commande/') !== false || stripos($url, '/Synopsis_PrepaCommande/prepacommande.php') !== false) {
        $table = MAIN_DB_PREFIX . "commande";
        $droit1 = $user->rights->commande->lire;
        $droit2 = $user->rights->commande->creer;
    }
    if (stripos($url, '/fichinter/') !== false) {
        $table = MAIN_DB_PREFIX . "Synopsis_fichinter";
        $droit1 = $user->rights->synopsisficheinter->lire;
        $droit2 = $user->rights->synopsisficheinter->creer;
    }
    if (stripos($url, '/contrat/') !== false
            || stripos($url, '/Babel_GMAO/annexes.php')
            || stripos($url, '/Babel_GMAO/intervByContrat.php')) {
        $table = MAIN_DB_PREFIX . "contrat";
        $droit1 = $user->rights->contrat->lire;
        $droit2 = $user->rights->contrat->creer;
    }
    if (stripos($url, '/Synopsis_DemandeInterv/') !== false) {
        $table = MAIN_DB_PREFIX . "Synopsis_demandeInterv";
        $droit1 = $user->rights->synopsisdemandeinterv->lire;
        $droit2 = $user->rights->synopsisdemandeinterv->creer;
    }
    if (stripos($url, '/Synopsis_DemandeInterv/') !== false) {
        $table = MAIN_DB_PREFIX . "Synopsis_demandeInterv";
        $droit1 = $user->rights->synopsisdemandeinterv->lire;
        $droit2 = $user->rights->synopsisdemandeinterv->creer;
    }
    if (stripos($url, '/comm/propal') !== false) {
        $table = MAIN_DB_PREFIX . "propal";
        $droit1 = $user->rights->propal->lire;
        $droit2 = $user->rights->propal->creer;
    }

    foreach ($tabUrl as $val) {
        if (stripos($val, $nomId) !== false)
            $id = str_replace($nomId . "=", "", $val);
    }

    if (!isset($id) && $nomId != "id") {
        //On reesseille avec id standard
        $nomId = "id";
        foreach ($tabUrl as $val) {
            if (stripos($val, $nomId) !== false)
                $id = str_replace($nomId . "=", "", $val);
        }
    }

    if ($forceRightEdit)
        $droit2 = $droit1;


    if (isset($table) && isset($id) && $droit1) {
        if (isset($_POST['note']) && $droit2) {//Onupload
            $requete = "UPDATE " . $table . " SET " . $nomChampNote . " = '" . trim($_POST['note']) . "' WHERE rowid = " . $id;
            $db->query($requete);
        }

        $requete = "SELECT " . $nomChampNote . " as note FROM " . $table . " WHERE rowid = " . $id;
        $sql = $db->query($requete);
        $result = $db->fetch_object($sql);
        $return = str_replace("\n", "<br/>", $result->note);
        $return = ($droit2 ? "[1]" : "") . $return;
    }
}
elseif($_POST['type'] == 'consigne'){
        $url = $_POST['url'];
        $tabElem = getTypeAndId($url);
    $tabUrl = explode("?", $url);
    $tabUrl = explode("&", $tabUrl[1]);
    foreach ($tabUrl as $val)
        if (stripos($val, "id") !== false)
            $id = str_replace("id" . "=", "", $val);
        $element_type = $tabElem[0];
        $element_id = $id;
            global $db;
            $consigne = new consigneCommande($db);
            $consigne->fetch($element_type, $element_id);
//            die($element_id);
            $consigne->setNote(str_replace("\n", "<br/>", trim($_POST['note'])), $element_type, $element_id);
            $consigne->fetch($element_type, $element_id);
            $return = $consigne->note;
}

echo $return;
?>
