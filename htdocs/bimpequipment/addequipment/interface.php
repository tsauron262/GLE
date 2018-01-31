<?php

/**
 *      \file       /htdocs/bimpequipment/addequipment/interface
 *      \ingroup    bimpequipment
 *      \brief      Make interface between the server and the client
 */
require_once '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT . '/bimpequipment/addequipment/addEquipementLib.php';


switch (GETPOST('action')) {
    case 'checkEquipment': {
            $note = getNote($db, GETPOST('idCurrentProd'));
            if (!checkIfEquipmentExists($db, GETPOST('serialNumber'))) {
                $code = 1;
            } else {
                $code = -1;
            }
            $out = array('note' => $note, 'code' => $code);
            echo json_encode($out);
            break;
        }
    default: break;
}


$db->close();
