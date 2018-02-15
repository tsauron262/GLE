<?php

function getAllEntrepots($db) {

    $entrepots = array();

    $sql = 'SELECT rowid, label';
    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'entrepot';

    $result = $db->query($sql);
    if ($result and mysqli_num_rows($result) > 0) {
        while ($obj = $db->fetch_object($result)) {
            $entrepots[$obj->rowid] = $obj->label;
        }
    }
    return $entrepots;
}