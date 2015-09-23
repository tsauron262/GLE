<?php

function addElementElement($typeS, $typeD, $idS, $idD, $ordre = 1) {
    global $db;
    if (!$ordre) {
        $typeST = $typeD;
        $idST = $idD;
        $typeD = $typeS;
        $idD = $idS;
        $typeS = $typeST;
        $idS = $idST;
    }
    $req = "INSERT INTO " . MAIN_DB_PREFIX . "element_element (sourcetype, targettype, fk_source, fk_target) VALUES ('" . $typeS . "', '" . $typeD . "', " . $idS . ", " . $idD . ")";
    return $db->query($req);
}

function delElementElement($typeS, $typeD, $idS = null, $idD = null, $ordre = true) {
    global $db;
    if (!$ordre) {
        $typeST = $typeD;
        $idST = $idD;
        $typeD = $typeS;
        $idD = $idS;
        $typeS = $typeST;
        $idS = $idST;
    }
    if (!isset($typeS) && !isset($typeD))
        die("Suppr tout probleme pas de type");
    if (!isset($typeS) || !isset($typeD))
        dol_syslog("Suppr element_elemnt un seul type ".$typeS."|".$typeD."|".$idS."|".$idD, 3);
    $req = "DELETE FROM " . MAIN_DB_PREFIX . "element_element WHERE 1";
    if (isset($typeS))
        $req .= " AND sourcetype = '" . $typeS . "'";
    if (isset($typeD))
        $req .= " AND targettype = '" . $typeD . "'";

    if (isset($idS))
        $req .= " AND fk_source = " . $idS;
    if (isset($idD))
        $req .= " AND fk_target = " . $idD;
    $db->query($req);
}

function getElementElement($typeS = null, $typeD = null, $idS = null, $idD = null, $ordre = true) {
    global $db;
    if (!$ordre) {
        $typeST = $typeD;
        $idST = $idD;
        $typeD = $typeS;
        $idD = $idS;
        $typeS = $typeST;
        $idS = $idST;
    }
    $req = "SELECT * FROM " . MAIN_DB_PREFIX . "element_element WHERE ";
    $tabWhere = array("1");
    if ($typeS)
        $tabWhere[] = "sourcetype = '" . $typeS . "'";
    if ($typeD)
        $tabWhere[] = "targettype = '" . $typeD . "'";
    $req .= implode(" AND ", $tabWhere);

    if (isset($idS)) {
        if (is_array($idS))
            $req .= " AND fk_source IN ('" . implode("','", $idS) . "')";
        else
            $req .= " AND fk_source = " . $idS;
    }
    if (isset($idD)) {
        if (is_array($idD))
            $req .= " AND fk_target IN ('" . implode("','", $idD) . "')";
        else
            $req .= " AND fk_target = " . $idD;
    }

//    echo $req;
    $sql = $db->query($req);
    $tab = array();
    while ($result = $db->fetch_object($sql)) {
        if ($ordre)
            $tab[] = array("s" => $result->fk_source, "d" => $result->fk_target, "ts" => $result->sourcetype, "td" => $result->targettype);
        else
            $tab[] = array("d" => $result->fk_source, "s" => $result->fk_target, "td" => $result->sourcetype, "ts" => $result->targettype);
    }
    return $tab;
}

function setElementElement($typeS, $typeD, $idS, $idD, $ordre = true) {
    if (!$ordre) {
        $typeST = $typeD;
        $idST = $idD;
        $typeD = $typeS;
        $idD = $idS;
        $typeS = $typeST;
        $idS = $idST;
    }
    if ($ordre)
        delElementElement($typeS, $typeD, $idS);
    else
        delElementElement($typeS, $typeD, null, $idD);
    return addElementElement($typeS, $typeD, $idS, $idD);
}
