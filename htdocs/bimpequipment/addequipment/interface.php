<?php

/**
 *      \file       /htdocs/bimpequipment/addequipment/interface
 *      \ingroup    bimpequipment
 *      \brief      Make interface between the server and the client
 */

require_once '../../main.inc.php';

//require_once DOL_DOCUMENT_ROOT . '/bimpequipment/addequipment/addEquipementLib.php';


switch (GETPOST('action')) {
    case 'addEquipment': {
//            addEquipment($db, GETPOST('idCurentEntrepot'), GETPOST('idCurentProd'), GETPOST('serialNumber'));
//            $note = getNote($db, GETPOST('idCurentProd'));
            $note = 'OK';
            echo json_encode($note);
            break;
        }
    default: break;
}


$db->close();