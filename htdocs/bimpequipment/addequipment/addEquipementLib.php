<?php

/**
 *      \file       /htdocs/bimpequipment/addequipment/addEquipementLib
 *      \ingroup    bimpequipment
 *      \brief      Lib of addEquipment
 */

require '../../main.inc.php';

function addEquipment($db, $idEntrepot, $idProd, $serialNumber) {

    $sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'equipement';
    $sql .= ' (fk_entrepot, fk_product) ';
    $sql .= ' VALUES ';
    $sql .= ' ( ' . $idEntrepot . ', ' . $idProd . ')';

//    $sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'be_equipement';
//    $sql .= ' (id_product, serial) ';
//    $sql .= ' VALUES ';
//    $sql .= ' ( ' . $idProd . ', ' . $serialNumber . ')';

    $result = $db->query($sql);
    if ($result and mysqli_num_rows($result) > 0) {
        $db->commit();
        return 1;
    } else {
        $db->rollback();
        return -1;
    }
}

function getNote($db, $idCurentProd) {

    $sql = 'SELECT note';
    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'product';
    $sql .= ' WHERE rowid=' . $idCurentProd;

    $result = $db->query($sql);
    if ($result and mysqli_num_rows($result) > 0) {
        while ($obj = $db->fetch_object($result)) {
            $note = $obj->note;
        }
    }
    $note = "OK";
    return $note;
}

$db->close();
