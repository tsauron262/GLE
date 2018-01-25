<?php

/**
 *      \file       /Bimpstatsfacture/interface.php
 *      \ingroup    Bimpstatsfacture
 *      \brief      Make interface between the class and the client
 */
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/bimpstatsfacture/class/BimpStatsFacture.class.php';

$staticSF = new BimpStatsFacture($db);

switch (GETPOST('action')) {
    case 'getFactures': {
            $factures = $staticSF->getFactures(GETPOST('dateStart'), GETPOST('dateEnd'), GETPOST('types'),
                    GETPOST('centres'), GETPOST('statut'), GETPOST('sortBy'), GETPOST('taxes'), GETPOST('etats'), GETPOST('format'));
            echo json_encode($factures);
            break;
        }
    default: break;
}


$db->close();