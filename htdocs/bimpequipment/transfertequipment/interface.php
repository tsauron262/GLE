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
            $id = checkProductByRefOrBarcode($db, GETPOST('ref'));
            if ($id != false) {
                $prod = new product($db);
                $prod->id = $id;
                $prod->ref = GETPOST('ref');
                $label = getLabel($db, $id);
                $isEquipment = equipmentExists($db, $id);
                if ($isEquipment)
                    $stock = checkStockEquipment($db, $id, GETPOST('idEntrepotStart'));
                else
                    $stock = checkStock($db, $id, GETPOST('idEntrepotStart'));
            }
            $data = array('id' => $id, 'isEquipment' => $isEquipment, 'stock' => $stock, 'label' => $label, 'refUrl' => $prod->getNomUrl(1));
            echo json_encode($data);
            break;
        }
    default: break;
}


$db->close();
