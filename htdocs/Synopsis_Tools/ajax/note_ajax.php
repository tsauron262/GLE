<?php

require_once('../../main.inc.php');
$return = 0;

if (isset($_POST['url'])) {
    $url = $_POST['url'];
    $tabUrl = explode("?", $url);
    $tabUrl = explode("&", $tabUrl[1]);
    
        $nomId = "id";
        $nomChampNote = "note";
    
    
    if (stripos($url, '/societe/') !== false
            || stripos($url, '/comm/fiche.php')
            || stripos($url, '/categories/categorie.php')) {
        $table = "llx_societe";
        $nomId = "socid";
    }
    if (stripos($url, '/commande/') !== false) {
        $table = "llx_commande";
        $nomChampNote = "note_public";
    }
    if (stripos($url, '/fichinter/') !== false) {
        $table = "llx_Synopsis_fichinter";
        $nomChampNote = "note_public";
    }

    foreach ($tabUrl as $val) {
        if (stripos($val, $nomId) !== false)
            $id = str_replace($nomId . "=", "", $val);
    }


    if (isset($table) && isset($id)) {
        if (isset($_POST['note'])) {//Onupload
            $requete = "UPDATE " . $table . " SET ".$nomChampNote." = '" . trim($_POST['note']) . "' WHERE rowid = " . $id;
            $db->query($requete);
        }

        $requete = "SELECT ".$nomChampNote." as note FROM " . $table . " WHERE rowid = " . $id;
        $sql = $db->query($requete);
        $result = $db->fetch_object($sql);
        $return = str_replace("\n", "<br/>", $result->note);
    }
}


echo $return;
?>
