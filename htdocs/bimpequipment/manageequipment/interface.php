<?php

/**
 *      \file       /htdocs/bimpequipment/manageequipment/interface.php
 *      \ingroup    transfertequipment
 *      \brief      Make interface between the server and the client
 */
include_once '../../main.inc.php';

include_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

include_once DOL_DOCUMENT_ROOT . '/bimpequipment/manageequipment/class/lignepanier.class.php';
include_once DOL_DOCUMENT_ROOT . '/bimpequipment/manageequipment/class/transfert.class.php';
include_once DOL_DOCUMENT_ROOT . '/bimpequipment/manageequipment/lib/product.lib.php';
include_once DOL_DOCUMENT_ROOT . '/bimpequipment/manageequipment/lib/equipment.lib.php';

$lp = new LignePanier($db);
switch (GETPOST('action')) {
    case 'checkStockForProduct': {
            $lp->fetchProd(GETPOST('idProduct'), GETPOST('idEntrepotStart'));
            echo json_encode($lp->getInfo());
            break;
        }
    case 'checkProductByRef': {
            $lp->check(GETPOST('ref'), GETPOST('idEntrepotStart'));
            echo json_encode($lp->getInfo());
            break;
        }
    case 'transfertAll': {
            $transfert = new Transfert($db, GETPOST('idEntrepotStart'), GETPOST('idEntrepotEnd'), $user);
            $transfert->addLignes(GETPOST('products'));
            echo json_encode($transfert->execute());
            break;
        }
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
