<?php

require_once('../../main.inc.php');
$return = 0;

if (isset($_POST['url'])) {
    $url = $_POST['url'];
    $tabUrl = explode("?", $url);
    $tabUrl = explode("&", $tabUrl[1]);
    
        $nomId = "id";
        $nomChampNote = "note_public";
    
    
    if (stripos($url, '/societe/') !== false
            || stripos($url, '/comm/fiche.php')
            || stripos($url, '/categories/categorie.php')) {
        $droit1 = $user->rights->societe->lire;
        $droit2 = $user->rights->societe->creer;
        $table = MAIN_DB_PREFIX."societe";
        $nomId = "socid";
        $nomChampNote = "note";
    }
    if (stripos($url, '/commande/') !== false) {
        $table = MAIN_DB_PREFIX."commande";
        $droit1 = $user->rights->commande->lire;
        $droit2 = $user->rights->commande->creer;
    }
    if (stripos($url, '/fichinter/') !== false) {
        $table = MAIN_DB_PREFIX."Synopsis_fichinter";
        $droit1 = $user->rights->synopsisficheinter->lire;
        $droit2 = $user->rights->synopsisficheinter->creer;
    }
    if (stripos($url, '/contrat/') !== false
            || stripos($url, '/Babel_GMAO/annexes.php')
            || stripos($url, '/Babel_GMAO/intervByContrat.php')) {
        $table = MAIN_DB_PREFIX."contrat";
        $droit1 = $user->rights->contrat->lire;
        $droit2 = $user->rights->contrat->creer;
    }
    if (stripos($url, '/Synopsis_DemandeInterv/') !== false) {
        $table = MAIN_DB_PREFIX."Synopsis_demandeInterv";
        $droit1 = $user->rights->synopsisdemandeinterv->lire;
        $droit2 = $user->rights->synopsisdemandeinterv->creer;
    }

    foreach ($tabUrl as $val) {
        if (stripos($val, $nomId) !== false)
            $id = str_replace($nomId . "=", "", $val);
    }


    if (isset($table) && isset($id) && $droit1) {
        if (isset($_POST['note']) && $droit2) {//Onupload
            $requete = "UPDATE " . $table . " SET ".$nomChampNote." = '" . trim($_POST['note']) . "' WHERE rowid = " . $id;
            $db->query($requete);
        }

        $requete = "SELECT ".$nomChampNote." as note FROM " . $table . " WHERE rowid = " . $id;
        $sql = $db->query($requete);
        $result = $db->fetch_object($sql);
        $return = str_replace("\n", "<br/>", $result->note);
        $return = ($droit2? "[1]" : "").$return;
    }
}


echo $return;
?>
