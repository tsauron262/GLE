<?php

/**
 *      \file       /htdocs/bimpequipment/addequipment/equipment.lib.php
 *      \ingroup    bimpequipment
 *      \brief      Lib of equipment
 */
require_once '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpequipment/transfertequipment/product.lib.php';

function addEquipments($newEquipments) {

    $cntEquipment = 0;
    $errors = array();
    foreach ($newEquipments as $newEquipment) {
        $newErrors = array();
        $equipement = BimpObject::getInstance('bimpequipment', 'Equipment');

        $equipement->validateArray(array(
            'id_product' => $newEquipment['id_product'], // ID du produit. 
            'type' => 2, // cf $types
            'serial' => $newEquipment['serial'], // num série
            'reserved' => 0, // réservé ou non
            'date_purchase' => '2010-10-10', // date d'achat TODO remove
            'date_warranty_end' => '2010-10-10', // TODO remove
            'warranty_type' => 0, // type de garantie (liste non définie actuellement)
            'admin_login' => '',
            'admin_pword' => '',
            'note' => ''
        ));

        $newErrors = array_merge($newErrors, $equipement->create());

        $emplacement = BimpObject::getInstance('bimpequipment', 'BE_Place');

        $emplacement->validateArray(array(
            'id_equipment' => $equipement->id,
            'type' => 2, // cf $types
//            'id_client' => 123, // si type = 1
//            'id_contact' => 123, // id contact associé au client
            'id_entrepot' => $newEquipment['id_entrepot'], // si type = 2
//            'id_user' => 123, // si type = 3
//            'place_name' => '', // Nom de l'emplacement si type = 4
            'infos' => '...',
            'date' => '2018-01-01 00:00:00' // date et heure d'arrivée
        ));
        $newErrors = array_merge($newErrors, $emplacement->create());

        if (sizeof($newErrors) == 0)
            $cntEquipment++;

        $errors = array_merge($errors, $newErrors);
    }
    return array('nbNewEquipment' => $cntEquipment, 'errors' => $errors);
}

//function getSerialById($db, $id) {
//    $sql = 'SELECT serial';
//    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_equipment';
//    $sql .= ' WHERE id_product="' . $id . '"';
//
//    $result = $db->query($sql);
//    if ($result and mysqli_num_rows($result) > 0) {
//        while ($obj = $db->fetch_object($result)) {
//            return $obj->serial;
//        }
//    }
//    return 'serial_unknown';
//}

function getIdOfProductAndEquipment($db, $serial) {

    $sql = 'SELECT id, id_product';
    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_equipment';
    $sql .= ' WHERE serial="' . $serial . '"';

    $result = $db->query($sql);
    if ($result and mysqli_num_rows($result) > 0) {
        while ($obj = $db->fetch_object($result)) {
            return array('id' => $obj->id, 'id_product' => $obj->id_product);
        }
    }

    return false;
}

function checkEquipment($db, $serial, $idEntrepotStart) {

    $sql = 'SELECT id, id_product';
    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_equipment';
    $sql .= ' WHERE serial="' . $serial . '"';

    $result = $db->query($sql);
    if ($result and mysqli_num_rows($result) > 0) {
        while ($obj = $db->fetch_object($result)) {
            return checkStockEquipment($db, $idEntrepotStart, $obj->id, $obj->id_product);
        }
    }
    return 'serial_unknown';
}

function checkStockEquipment($db, $idEntrepotStart, $idEquipment, $idProduct) {

    $sql = 'SELECT id';
    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_equipment_place';
    $sql .= ' WHERE id_equipment="' . $idEquipment . '"';
    $sql .= ' AND position=1';
    $sql .= ' AND id_entrepot=' . $idEntrepotStart;

    $result = $db->query($sql);
    if ($result and mysqli_num_rows($result) > 0) {
        while ($obj = $db->fetch_object($result)) {
            return 1;
        }
    }
    return 'no_entrepot_for_equipment';
}

function equipmentExists($db, $id) {
    
    if($id < 1)
        return false;

    $sql = 'SELECT id';
    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_equipment';
    $sql .= ' WHERE id=' . $id;

    $result = $db->query($sql);
    if ($result and mysqli_num_rows($result) > 0) {
        return true;
    }
    return false;
}

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
