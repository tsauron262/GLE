<?php

/**
 *      \file       /htdocs/bimpequipment/addequipment/addEquipementLib
 *      \ingroup    bimpequipment
 *      \brief      Lib of addEquipment
 */
/* Return true if the serial number already exists, else return false */
function checkIfEquipmentExists($db, $serial)
{

    $sql = 'SELECT rowid';
    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_equipement';
    $sql .= ' WHERE serial=' . $serial;

    $result = $db->query($sql);
    if ($result and mysqli_num_rows($result) > 0) {
        return true;
    }
    return false;
}

function addEquipment($db, $idEntrepot, $idProd, $serialNumber)
{

//    $sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'equipement';
//    $sql .= ' (fk_entrepot, fk_product) ';
//    $sql .= ' VALUES ';
//    $sql .= ' ( ' . $idEntrepot . ', ' . $idProd . ')';
    
    // Attention. Ne pas faire d'insertion directe pour les BimpObject. 
    // 
//    $sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'be_equipement';
//    $sql .= ' (id_product, serial) ';
//    $sql .= ' VALUES ';
//    $sql .= ' ( ' . $idProd . ', ' . $serialNumber . ')';
//
//    $result = $db->query($sql);
//    if ($result and mysqli_num_rows($result) > 0) {
//        $db->commit();
//        return 1;
//    } else {
//        $db->rollback();
//        return -1;
//    }
    
    define('BIMP_NEW', 1);
    require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
    $equipment = BimpObject::getInstance('bimpequipment', 'Equipment');
    if (count($equipment->validateArray(array(
                        'id_product' => (int) $idProd,
                        'serial'     => $serialNumber
            )))) {
        return -1;
    }

    if (count($equipment->create())) {
        return -1;
    }

    $place = BimpObject::getInstance('bimpequipment', 'BE_Place');
    if (!count($place->validateArray(array(
                        'id_equipment' => $equipment->id,
                        'type'         => BE_Place::BE_PLACE_ENTREPOT,
                        'id_entrepot'  => (int) $idEntrepot
            )))) {
        $place->create();
    }
    return 1;
}

function getNote($db, $idCurrentProd)
{

    $sql = 'SELECT note';
    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'product';
    $sql .= ' WHERE rowid=' . $idCurrentProd;

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
