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
include_once DOL_DOCUMENT_ROOT . '/bimpequipment/manageequipment/class/bimporderclient.class.php';
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
            $transfer = new Transfert($db, GETPOST('idEntrepotStart'), GETPOST('idEntrepotEnd'), $user);
            $transfer->addLignes(GETPOST('products'));
            echo json_encode(array('errors' => $transfer->execute()));
            break;
        }
    case 'checkEquipment': {
            $note = getNote($db, GETPOST('idCurrentProd'));
            (!checkIfEquipmentExists($db, GETPOST('serialNumber'))) ? $code = 1 : $code = -1;
            echo json_encode(array('note' => $note, 'code' => $code));
            break;
        }
    case 'checkIsSerializable': {
            $lp->prodId = GETPOST('id_product');
            echo json_encode(array('is_serialisable' => $lp->isSerialisable()));
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
            ini_set('max_execution_time', 30000);
            $inventory->fetch(GETPOST('inventory_id'));
            echo json_encode(array('success' => $inventory->updateStock($user), 'errors' => $inventory->errors));
            break;
        }


    /* Transfer - viewTransfer */

    case 'createTransfer': {
            $transfer = new BimpTransfer($db);
            $id_transfer = $transfer->create(GETPOST('idEntrepotStart'), GETPOST('idEntrepotEnd'), $user->id, $transfer::STATUS_SENT);
            $transfer->fetch($id_transfer);

            echo json_encode(array('lines_added' => $transfer->addLines(GETPOST('products')), 'errors' => $transfer->errors));
            break;
        }

    /* transfer - viewReception */

    case 'retrieveSentLines': {
            $transfer = new BimpTransfer($db);
            $transfer->fetch(GETPOST('fk_transfer'));
            echo json_encode(array('prods' => $transfer->getLines(true), 'errors' => $transfer->errors));
            break;
        }

    case 'receiveTransfer': {
            $transfer = new BimpTransfer($db);
            $transfer->fetch(GETPOST('fk_transfer'));
            echo json_encode(array('nb_update' => $transfer->receiveTransfert($user, GETPOST('products'), GETPOST('equipments')),
                'is_now_closed' => $transfer->checkClose(),
                'errors' => $transfer->errors));
            break;
        }

    case 'closeTransfer': {
            $transfer = new BimpTransfer($db);
            $transfer->fetch(GETPOST('fk_transfer'));
            echo json_encode(array('status_changed' => $transfer->closeTransfer(), 'errors' => $transfer->errors));
            break;
        }

    /* OrderClient - viewOrderClient */

    case 'retrieveOrderClient': {
            $boc = new BimpOrderClient($db);
            $boc->fetch(GETPOST('fk_order'), GETPOST('ref_order'));
            echo json_encode(array('order' => $boc->retrieveOrderClient(), 'errors' => $boc->errors));
            break;
        }

    /* Manageequipment - Index - accueil boutique */
    case 'getLineTransferAndOrder': {
            $transferstatic = new BimpTransfer($db);
            $blstatic = new BimpLivraison($db);
            echo json_encode(array(
                'transfers' => $transferstatic->getTransfers(GETPOST('fk_warehouse'), array($transferstatic::STATUS_DRAFT,
                    $transferstatic::STATUS_SENT,
                    $transferstatic::STATUS_RECEIVED_PARTIALLY), true),
                'orders' => $blstatic->getOrders(GETPOST('fk_warehouse'), 3, 4),
                'right_caisse_admin' => $user->rights->bimpequipment->caisse_admin->read,
                'right_caisse' => $user->rights->bimpequipment->caisse->read,
                'errors' => BimpTools::merge_array($transferstatic->errors, $blstatic->errors)));
            break;
        }


    /* Default (catch bad action parameter) */
    default: {
            echo json_encode(array('errors' => array('Aucune action ne match avec : ' . GETPOST('action'))));
            break;
        }
}


$db->close();
