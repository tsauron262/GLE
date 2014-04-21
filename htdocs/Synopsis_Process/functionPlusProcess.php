<?php

function getSNChrono($idChrono, $source) {
    global $db;
    $key = 1011;
    $dest = "productCli";
    $ordre = 1;
    
    
    $return = array();
    $result = getElementElement($source, $dest, $idChrono, null, $ordre);
    if (count($result) > 0) {
        $chronoTab = array();
        foreach ($result as $chrono)
            $chronoTab[] = $chrono['d'];
        $req = "SELECT `value` FROM `" . MAIN_DB_PREFIX . "Synopsis_Chrono_value` WHERE `chrono_refid` IN (" . implode(",", $chronoTab) . ") AND `key_id` = ".$key;
        $sql = $db->query($req);
        while ($result = $db->fetch_object($sql))
            $return[] = $result->value;
    }

    return implode(" | ", $return);
}
