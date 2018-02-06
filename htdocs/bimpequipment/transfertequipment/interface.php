<?php

/**
 *      \file       /htdocs/bimpequipment/transfertequipment/interface.php
 *      \ingroup    transfertequipment
 *      \brief      Make interface between the server and the client
 */
require_once '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT . '/bimpequipment/transfertequipment/product.lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpequipment/transfertequipment/equipment.lib.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';


switch (GETPOST('action')) {
    case 'checkStockForProduct': {
            $qty = checkStock($db, GETPOST('idProduct'), GETPOST('idEntrepotStart'));
//            $note = getNote($db, GETPOST('idCurrentProd'));
//            (!checkIfEquipmentExists($db, GETPOST('serialNumber'))) ? $code = 1 : $code = -1;
//            $label = getLabel($db, GETPOST('idProduct'));
            $labelAndref = getLabelAndref($db, GETPOST('idProduct'));
            $prod = new product($db);
            $prod->id = $id;
            $prod->ref = $labelAndref['ref'];
            echo json_encode(array('nb_product' => $qty, 'label' => dol_trunc($labelAndref['label'], 20), 'refUrl' => $prod->getNomUrl(1)));
            break;
        }
    case 'checkStockEquipment': {
            echo json_encode(checkEquipment($db, GETPOST('serial'), GETPOST('idEntrepotStart')));
            break;
        }
    case 'checkProductByRef': {
            $data = array();
            $id_product = checkProductByRefOrBarcode($db, GETPOST('ref'));
            if ($id_product == false) {
                $idProdAndIdEquipment = getIdOfProductAndEquipment($db, GETPOST('ref')); // ref => serial
                $id_product = $idProdAndIdEquipment['id_product'];
                $id_equipment = $idProdAndIdEquipment['id'];
                if ($id_product != false)
                    $serial = GETPOST('ref');
            }
            if ($id_product != false) {
                $prod = new product($db);
                $prod->fetch($id_product);
//                $isEquipment = equipmentExists($db, $idProdAndIdEquipment['id']);
                if (isset($id_equipment)) {
                    $stock = checkStockEquipment($db, GETPOST('idEntrepotStart'), $id_equipment, $id_product);
                } else {
                    $stock = checkStock($db, $id_product, GETPOST('idEntrepotStart'));
                }
                $data = array_merge($data, array('id' => $id_product, 'isEquipment' => $isEquipment, 'stock' => $stock, 'label' => $label, 'refUrl' => $prod->getNomUrl(1), 'serial' => $serial, 'error' => 'no_errors'));
            } else {
                $data['error'] = 'unknown_product';
            }
            echo json_encode($data);
            break;
        }
    default: break;
}


$db->close();
