<?php

require_once('../../main.inc.php');

$type = $_REQUEST['type'];
$idElem = $_REQUEST['idElem'];
$idFk = $_REQUEST['idFk'];
$newRang = $_REQUEST['newRang'];
$oldRang = $_REQUEST['oldRang'];

if($newRang == -1 && $oldRang == 0){
    $newRang = 0;
    $oldRang = 1;
}

if ($newRang > $oldRang) {
    $ptiRang = $oldRang;
    $grRang = $newRang;
    $changePlus = true;
} else {
    $ptiRang = $newRang;
    $grRang = $oldRang;
    $changePlus = false;
}

if ($type == "contratdet" && $newRang >= 0) {
    $champFk = "contratdet_refid IN (SELECT rowid FROM " . MAIN_DB_PREFIX . "contratdet WHERE fk_contrat = " . $idFk . ")";
    $nomTab = MAIN_DB_PREFIX . "Synopsis_contratdet_GMAO";
    $champId = "contratdet_refid";
    $header = "Location: ../../contrat/fiche.php?id=" . $idFk;
}
if ($type == "chrono_key" && $newRang >= 0) {
    $champFk = "model_refid = " . $idFk . "";
    $nomTab = MAIN_DB_PREFIX . "Synopsis_Chrono_key";
    $champId = "id";
    $header = "Location: ../../admin/Synopsis_Chrono_advMode.php?id=" . $idFk;
}


if (isset($champFk)) {
    $sql = $db->query("SELECT * FROM  " . $nomTab . " WHERE  " . $champFk . " AND rang = 0");
    if ($db->num_rows($sql) > 0) {
        $sql = $db->query("SELECT * FROM  " . $nomTab . " WHERE  " . $champFk." order by rang");
        $i = 0;
        while ($result = $db->fetch_object($sql)) {
            $i++;
            $request = "UPDATE " . $nomTab . " SET rang = " . $i . " WHERE " . $champFk . " AND " . $champId . " = " . $result->$champId;
            $db->query($request);
            echo $request;
        }
    }


    $request = "UPDATE " . $nomTab . " SET rang = 99999 WHERE " . $champFk . " AND " . $champId . " = " . $idElem;
    $db->query($request);
    echo $request . "|" . $oldRang . "|" . $newRang;
    if ($oldRang > 0) {
        $request = "UPDATE " . $nomTab . " SET rang = rang " . ($changePlus ? "-" : "+") . "1 WHERE " . $champFk . " AND rang >= " . $ptiRang . " AND rang <= " . $grRang;
        $db->query($request);
        echo $request . "|" . $oldRang . "|" . $newRang;
    }
    $request = "UPDATE " . $nomTab . " SET rang = " . $newRang . " WHERE " . $champFk . " AND " . $champId . " = " . $idElem;
    $db->query($request);
    echo $request . "|" . $oldRang . "|" . $newRang;
    header($header);
}
?>
