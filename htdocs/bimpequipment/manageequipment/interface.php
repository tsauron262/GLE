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
            echo json_encode($bl->addInStock(GETPOST('products'), GETPOST('orderId', 'int'), GETPOST('entrepotId', 'int'), $user, GETPOST('isTotal')));
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
//            echo json_encode(array('success' => $inventory->updateStatut($inventory::STATUT_CLOSED), 'errors' => $inventory->errors));
            break;
        }


    /* Old Inventories */
    /** @deprecated */
    case 'getStockAndSerial': {
            $lp->check(GETPOST('ref'), GETPOST('idEntrepot'));
            if (!$lp->prodId) {
                echo json_encode(array('errors' => array($lp->error)));
                break;
            }
            echo json_encode($em->getStockAndSerial(GETPOST('idEntrepot'), $lp->prodId, $lp->serial));
            break;
        }
    /** @deprecated */
    case 'getStock': {
            echo json_encode($em->getStockAndSerial(GETPOST('idEntrepot'), GETPOST('prodId'), ''));
            break;
        }
    /** @deprecated */
    case 'correctStock': {
//            echo json_encode($em->correctStock(GETPOST('idEntrepot'), GETPOST('products')), $user);
            break;
        }
    default: {
            echo json_encode(array('errors' => array('Aucune action ne match avec : ' . GETPOST('action'))));
            break;
        }
        
}


$db->close();
