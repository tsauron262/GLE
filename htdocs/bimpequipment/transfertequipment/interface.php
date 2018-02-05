<?php

/**
 *      \file       /htdocs/bimpequipment/transfertequipment/interface.php
 *      \ingroup    transfertequipment
 *      \brief      Make interface between the server and the client
 */
require_once '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT . '/bimpequipment/transfertequipment/product.lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpequipment/transfertequipment/equipment.lib.php';


switch (GETPOST('action')) {
    case 'checkStockForProduct': {
            $qty = checkStock($db, GETPOST('idProduct'), GETPOST('idEntrepotStart'));
//            $note = getNote($db, GETPOST('idCurrentProd'));
//            (!checkIfEquipmentExists($db, GETPOST('serialNumber'))) ? $code = 1 : $code = -1;
            echo json_encode(array('nb_product' => $qty));
            break;
        }
    case 'checkStockEquipment': {
            echo json_encode(checkEquipment($db, GETPOST('serial'), GETPOST('idEntrepotStart')));
            break;
        }
    default: break;
}


$db->close();
