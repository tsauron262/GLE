<?php

/**
 *      \file       /htdocs/bimpequipment/transfertequipment/interface.php
 *      \ingroup    transfertequipment
 *      \brief      Make interface between the server and the client
 */
require_once '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT . '/bimpequipment/transfertequipment/lineTransfert.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';


$ln = new lineTransfert($db);
switch (GETPOST('action')) {
    case 'checkStockForProduct': {
            $ln->fetchProd(GETPOST('idProduct'), GETPOST('idEntrepotStart'));

            echo json_encode($ln->getInfo());
            break;
        }
    case 'checkProductByRef': {
            $ln->check(GETPOST('ref'), GETPOST('idEntrepotStart'));
            echo json_encode($ln->getInfo());
            break;
        }
    default: break;
}


$db->close();
