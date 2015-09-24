<?php

require_once('../../main.inc.php');
$return = 0;

$forceRightEdit = false;

if (isset($_POST['url']) && isset($_POST['type']) && $_POST['type'] == 'note') {
    $url = $_POST['url'];

    $nomId = "id";
    $nomChampId = 'rowid';
    $nomChampNote = "note_public";


    if (stripos($url, '/societe/') !== false
            || stripos($url, '/comm/card.php')
            || stripos($url, '/categories/categorie.php')
            || stripos($url, '/synopsistools/allDocumentSoc.php') !== false) {
        $droit1 = $user->rights->societe->lire;
        $droit2 = $user->rights->societe->creer;
        $table = MAIN_DB_PREFIX . "societe";
        $nomId = "socid";
    }
    if (stripos($url, '/commande/') !== false || stripos($url, '/Synopsis_PrepaCommande/prepacommande.php') !== false) {
        $table = MAIN_DB_PREFIX . "commande";
        $droit1 = $user->rights->commande->lire;
        $droit2 = $user->rights->commande->creer;
    }
    if (stripos($url, '/synopsischrono/card.php') !== false) {
        $table = MAIN_DB_PREFIX . "synopsischrono";
        $droit1 = true;
        $droit2 = true;
        $nomChampNote = "note";
        $nomChampId = "id";
    }
    if (stripos($url, '/fichinter/') !== false || stripos($url, '/synopsisfichinter/') !== false) {
        $table = MAIN_DB_PREFIX . "fichinter";
        $droit1 = $user->rights->synopsisficheinter->lire;
        $droit2 = $user->rights->synopsisficheinter->creer;
    }
    if (stripos($url, '/contrat/') !== false
            || stripos($url, '/Synopsis_Contrat/annexes.php')
            || stripos($url, '/Synopsis_Contrat/intervByContrat.php')) {
        $table = MAIN_DB_PREFIX . "contrat";
        $droit1 = $user->rights->contrat->lire;
        $droit2 = $user->rights->contrat->creer;
    }
    if (stripos($url, '/synopsisdemandeinterv/') !== false) {
        $table = MAIN_DB_PREFIX . "synopsisdemandeinterv";
        $droit1 = $user->rights->synopsisdemandeinterv->lire;
        $droit2 = $user->rights->synopsisdemandeinterv->creer;
    }
    if (stripos($url, '/synopsisdemandeinterv/') !== false) {
        $table = MAIN_DB_PREFIX . "synopsisdemandeinterv";
        $droit1 = $user->rights->synopsisdemandeinterv->lire;
        $droit2 = $user->rights->synopsisdemandeinterv->creer;
    }
    if (stripos($url, '/comm/propal') !== false) {
        $table = MAIN_DB_PREFIX . "propal";
        $droit1 = $user->rights->propal->lire;
        $droit2 = $user->rights->propal->creer;
    }
    if (stripos($url, '/compta/facture') !== false) {
        $table = MAIN_DB_PREFIX . "facture";
        $droit1 = $user->rights->facture->lire;
        $droit2 = $user->rights->facture->creer;
        $nomId = "facid";
    }

    $id = getIdInUrl($url, $nomId);

    if (!isset($id) && is_int($id) && $nomId != "id") {
        //On reesseille avec id standard
        $nomId = "id";
        $id = getIdInUrl($url, $nomId);
    }

    if ($forceRightEdit)
        $droit2 = $droit1;


    if (isset($table) && isset($id) && $id > 0 && $droit1) {
        if (isset($_POST['note']) && $droit2) {//Onupload
            $requete = "UPDATE " . $table . " SET " . $nomChampNote . " = '" . addslashes(trim($_POST['note'])) . "' WHERE ".$nomChampId." = " . $id;
            $db->query($requete);
        }

        $requete = "SELECT " . $nomChampNote . " as note FROM " . $table . " WHERE ".$nomChampId." = " . $id;
        $sql = $db->query($requete);
        $result = $db->fetch_object($sql);
        $return = str_replace("\n", "<br/>", $result->note);
        $return = ($droit2 ? "[1]" : "") . $return;
    }
} elseif ($_POST['type'] == 'consigne') {
require_once(DOL_DOCUMENT_ROOT."/synopsisres/extractObjTypeId.php");
    $url = $_POST['url'];
    $tabElem = getTypeAndId($url);

    $element_type = $tabElem[0];
    $element_id = getIdInUrl($url, "id");
    global $db;
    $consigne = new consigneCommande($db);
    $consigne->fetch($element_type, $element_id);
    $consigne->setNote(str_replace("\n", "<br/>", trim($_POST['note'])));
    $consigne->init();
    $return = $consigne->note;
}

echo $return;
?>
