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
include_once DOL_DOCUMENT_ROOT . '/bimpequipment/manageequipment/class/bimptransfer.class.php';
include_once DOL_DOCUMENT_ROOT . '/bimpequipment/manageequipment/class/bimplivraison.class.php';
include_once DOL_DOCUMENT_ROOT . '/bimpequipment/manageequipment/class/bimpinventory.class.php';
include_once DOL_DOCUMENT_ROOT . '/bimpequipment/manageequipment/class/equipmentmanager.class.php';
include_once DOL_DOCUMENT_ROOT . '/bimpequipment/manageequipment/lib/product.lib.php';
include_once DOL_DOCUMENT_ROOT . '/bimpequipment/manageequipment/lib/equipment.lib.php';

$lp = new LignePanier($db);
$bl = new BimpLivraison($db);
$em = new EquipmentManager($db);
$inventory = new BimpInventory($db);

switch (GETPOST('action')) {
    case 'checkStockForProduct': {
            $lp->fetchProd(GETPOST('idProduct'), GETPOST('idEntrepotStart'));
            if ($lp->isSerialisable() == true) {
                $lp->error .= "Veuillez indiquer les numéros de série des produits";
                echo json_encode($lp->geterror());
                break;
            }
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
            echo json_encode(array('errors' => $transfert->execute()));
            break;
        }
    case 'checkEquipment': {
            $note = getNote($db, GETPOST('idCurrentProd'));
            (!checkIfEquipmentExists($db, GETPOST('serialNumber'))) ? $code = 1 : $code = -1;
            echo json_encode(array('note' => $note, 'code' => $code));
            break;
        }
    case 'addEquipment': {
            echo json_encode(addEquipments($db, GETPOST('newEquipments'), $user));
            break;
        }
    case 'modifyOrder': {
            $bl->fetch(GETPOST('orderId'));
            echo json_encode($bl->addInStock(GETPOST('products'), GETPOST('entrepotId', 'int'), $user, GETPOST('isTotal')));
            break;
        }
    case 'getRemainingLignes': {
            $bl->fetch(GETPOST('orderId'));
            echo json_encode($bl->getRemainingLignes());
            break;
        }


    /* Inventories - viewInventoryMain */
    case 'getInventoriesForEntrepot': {
            echo json_encode(array('inventories' => $em->getInventories(GETPOST('idEntrepot'), true), 'errors' => $em->errors));
            break;
        }
    case 'createInventory': {
            echo json_encode(array('id_inserted' => $inventory->create(GETPOST('idEntrepotCreate'), $user->id), 'errors' => $inventory->errors));
            break;
        }


    /* Inventories - viewInventory */
    case 'getAllProducts': {
            $inventory->fetch(GETPOST('inventory_id'));
            echo json_encode($inventory->retrieveScannedLignes());
            break;
        }
    case 'addLine': {
            $inventory->fetch(GETPOST('inventory_id'));
            echo json_encode($inventory->addLine(GETPOST('ref'), GETPOST('last_inserted_fk_product'), $user->id));
            break;
        }
    case 'closeInventory': {
            $inventory->fetch(GETPOST('inventory_id'));
            echo json_encode(array('success' => $inventory->updateStock($user), 'errors' => $inventory->errors));
            break;
        }


    /* Transfer - viewTransfer */

    case 'createTransfer': {
            $transfert = new BimpTransfer($db);
            $id_transfer = $transfert->create(GETPOST('idEntrepotStart'), GETPOST('idEntrepotEnd'), $user->id, $transfert::STATUS_SENT);
            $transfert->fetch($id_transfer);

            echo json_encode(array('lines_added' => $transfert->addLines(GETPOST('products')), 'errors' => $transfert->errors));
            break;
        }

    /* transfer - viewReception */

    case 'retrieveSentLines': {
            $transfert = new BimpTransfer($db);
            $transfert->fetch(GETPOST('fk_transfert'));
            echo json_encode(array('prods' => $transfert->getLines(true), 'errors' => $transfert->errors));
            break;
        }

    case 'receiveTransfert': {
            $transfert = new BimpTransfer($db);
            $transfert->fetch(GETPOST('fk_transfert'));
            echo json_encode(array('nb_update' => $transfert->receiveTransfert($user, GETPOST('products'), GETPOST('equipments')), 'errors' => $transfert->errors));
            break;
        }

    /* Default (catch bad action parameter) */
    default: {
            echo json_encode(array('errors' => array('Aucune action ne match avec : ' . GETPOST('action'))));
            break;
        }
}


$db->close();
