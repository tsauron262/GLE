<?php

/**
 *      \file       /htdocs/bimpequipment/addequipment/interface
 *      \ingroup    bimpequipment
 *      \brief      Make interface between the server and the client
 */
require_once '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT . '/bimpequipment/addequipment/addEquipementLib.php';


switch (GETPOST('action')) {
    case 'addEquipment': {
            $note = getNote($db, GETPOST('idCurrentProd'));
            if (!checkIfEquipmentExists($db, GETPOST('serialNumber'))) {
                $code = addEquipment($db, GETPOST('idCurrentEntrepot'), GETPOST('idCurrentProd'), GETPOST('serialNumber')); //return -1 (error) or 1
            } else {
                $code = -2;
            }
            $out = array('note' => $note, 'code' => $code);
            echo json_encode($out);
            break;
        }
    default: break;
}


$db->close();
