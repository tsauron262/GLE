<?php

/**
 *      \file       /bimpcontratauto/interface.php
 *      \ingroup    bimpcontratauto
 *      \brief      Make interface between the class and the client
 */
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcontratauto/class/BimpContratAuto.class.php';

$staticca = new BimpContratAuto($db);

switch (GETPOST('action')) {
    case 'getAllContrats': {
            $contrats = $staticca->getAllContrats(GETPOST('socid'));
            echo json_encode($contrats);
            break;
        }
    case 'newContrat': {
            $staticca->createContrat(GETPOST('socid'), GETPOST('contrat'), GETPOST('dateDeb'));
        }
    default: break;
}


$db->close();
