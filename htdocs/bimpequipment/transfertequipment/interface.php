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
            $id = checkProductByRefOrBarcode($db, GETPOST('ref'));
            if ($id == false) {
                $idProdAndIdEquipment = getIdOfProductAndEquipment($db, GETPOST('ref')); // ref => serial
                $id = $idProdAndIdEquipment['id_product'];
                if ($id != false)
                    $serial = GETPOST('ref');
            }
            if ($id != false) {
                $prod = new product($db);
                $prod->id = $id;

                $isEquipment = equipmentExists($db, $idProdAndIdEquipment['id']);
                if ($isEquipment) {
                    $stock = checkStockEquipment($db, $idProdAndIdEquipment['id'], GETPOST('idEntrepotStart'), $id);
                    $prod->ref = GETPOST('ref');
                    $label = getLabel($db, $id);
                } else {
                    $prod->ref = GETPOST('ref');
                    $label = getLabel($db, $id);
                    $stock = checkStock($db, $id, GETPOST('idEntrepotStart'));
                }
                $data = array_merge($date, array('id' => $id, 'isEquipment' => $isEquipment, 'stock' => $stock, 'label' => $label, 'refUrl' => $prod->getNomUrl(1), 'serial' => $serial, 'error' => 'no_errors'));
            }
            $data['error'] = 'unknown_product';
            echo json_encode($data);
            break;
        }
    default: break;
}


$db->close();
