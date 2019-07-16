<?php

/**
 *      \file       /Bimpstatsfacture/interface.php
 *      \ingroup    Bimpstatsfacture
 *      \brief      Make interface between the class and the client
 */
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/bimpstatsfacture/class/BimpStatsFacture.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimpstatsfacture/class/BimpStatsFactureFournisseur.class.php';


global $conf;
$conf->global->MAIN_MAX_DECIMALS_SHOWN = str_replace("...","", $conf->global->MAIN_MAX_DECIMALS_SHOWN);

$staticSF = new BimpStatsFacture($db);
$staticSFF = new BimpStatsFactureFournisseur($db);

switch (GETPOST('action')) {
    case 'getFactures': {
            if (GETPOST('is_customer') == 'true') {
                $factures = $staticSF->getFactures($user, GETPOST('dateStart'), GETPOST('dateEnd'), GETPOST('types'), GETPOST('centres'), GETPOST('statut'), GETPOST('sortBy'), GETPOST('taxes'), GETPOST('etats'), GETPOST('type'), GETPOST('format'), GETPOST('nomFichier'), GETPOST('typePlace'));
                echo json_encode($factures);
            } else {    // facture fournisseur
                echo json_encode($staticSFF->getFactures(GETPOST('dateStart'), GETPOST('dateEnd'), GETPOST('centres'), GETPOST('statut'), GETPOST('sortBy'), GETPOST('taxes'), GETPOST('etats'), GETPOST('format'), GETPOST('nomFichier')));
            }
            break;
        }
    default: break;
}


$db->close();
