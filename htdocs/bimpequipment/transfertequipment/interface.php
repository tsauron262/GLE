<?php

/**
 *      \file       /htdocs/bimpequipment/transfertequipment/interface.php
 *      \ingroup    transfertequipment
 *      \brief      Make interface between the server and the client
 */
require_once '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT . '/bimpequipment/transfertequipment/lignepanier.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';


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
    default: break;
}


$db->close();
