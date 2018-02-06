<?php

/**
 *      \file       /htdocs/bimpequipment/addequipment/interface
 *      \ingroup    bimpequipment
 *      \brief      Make interface between the server and the client
 */
require_once '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT . '/bimpequipment/addequipment/product.lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpequipment/addequipment/equipment.lib.php';


switch (GETPOST('action')) {
    case 'checkEquipment': {
            $note = getNote($db, GETPOST('idCurrentProd'));
            (!checkIfEquipmentExists($db, GETPOST('serialNumber'))) ? $code = 1 : $code = -1;
            echo json_encode(array('note' => $note, 'code' => $code));
            break;
        }
    case 'addEquipment': {
            echo json_encode(addEquipments(GETPOST('newEquipments')));
            break;
        }
    default: break;
}


$db->close();
