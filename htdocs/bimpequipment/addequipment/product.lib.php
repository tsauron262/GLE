<?php

/**
 *      \file       /htdocs/bimpequipment/addequipment/product.lib.php
 *      \ingroup    bimpequipment
 *      \brief      Lib of products
 */

require_once '../../main.inc.php';


/* Return true if the serial number already exists, else return false */
function checkIfEquipmentExists($db, $serial) {

    $sql = 'SELECT rowid';
    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_equipment';
    $sql .= ' WHERE serial="' . $serial . '"';

    $result = $db->query($sql);
    if ($result and mysqli_num_rows($result) > 0) {
        return true;
    }
    return false;
}

function getNote($db, $idCurrentProd) {
    
    $sql = 'SELECT note_public';
    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'product';
    $sql .= ' WHERE rowid=' . $idCurrentProd;

    $result = $db->query($sql);
    if ($result and mysqli_num_rows($result) > 0) {
        while ($obj = $db->fetch_object($result)) {
            $note = $obj->note_public;
        }
    }
    return $note;
}
