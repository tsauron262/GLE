<?php

/**
 *      \file       /htdocs/bimpequipment/manageequipment/equipment.lib.php
 *      \ingroup    bimpequipment
 *      \brief      Lib of equipment
 */
include_once '../../../main.inc.php';

include_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
include_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

function addEquipments($db, $newEquipments, $user) {

    $cntEquipment = 0;
    foreach ($newEquipments as $newEquipment) {
        $newErrors = array();
        $equipement = BimpObject::getInstance('bimpequipment', 'Equipment');
        
        $equipement->validateArray(array(
            'id_product' => $newEquipment['id_product'], // ID du produit. 
            'type' => 2, // cf $types
            'serial' => $newEquipment['serial'], // num série
            'reserved' => 0, // réservé ou non
//            'date_purchase' => '2010-10-10', // date d'achat TODO remove
//            'date_warranty_end' => '2010-10-10', // TODO remove
            'warranty_type' => 0, // type de garantie (liste non définie actuellement)
            'admin_login' => '',
            'admin_pword' => '',
//            'date_vente' => '2999-01-01 00:00:00',
//            'date_update' => '2999-01-01 00:00:00',
            'note' => ''
        ));

        $newErrors = BimpTools::merge_array($newErrors, $equipement->create());

        $emplacement = BimpObject::getInstance('bimpequipment', 'BE_Place');

        $emplacement->validateArray(array(
            'id_equipment' => $equipement->id,
            'type' => 2, // cf $types
            'id_entrepot' => $newEquipment['id_entrepot'], // si type = 2
            'infos' => '...',
//            'date_update' => '2999-01-01 00:00:00',
            'date' => dol_print_date(dol_now(), '%Y-%m-%d %H:%M:%S') // date et heure d'arrivée
        ));
        $newErrors = BimpTools::merge_array($newErrors, $emplacement->create());

        if (sizeof($newErrors) == 0)
            $cntEquipment++;
    }
    return array('nbNewEquipment' => $cntEquipment, 'errors' => $newErrors);
}

function equipmentExists($db, $id) {

    $sql = 'SELECT id';
    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_equipment';
    $sql .= ' WHERE id=' . $id;

    $result = $db->query($sql);
    if ($result and mysqli_num_rows($result) > 0) {
        return true;
    }
    return false;
}
