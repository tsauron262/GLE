<?php

/**
 *      \file       /bimpcontratauto/interface.php
 *      \ingroup    bimpcontratauto
 *      \brief      Make interface between the class and the client
 */
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcontratauto/class/BimpContratAuto.class.php';

$staticCA = new BimpContratAuto($db);

switch (GETPOST('action')) {
    case 'getAllContrats': {
            $contrats = $staticCA->getAllContrats(GETPOST('socid'));
            echo json_encode($contrats);
            break;
        }
    case 'newContrat': {
            echo json_encode($staticCA->createContrat(GETPOST('socid'), GETPOST('services'), GETPOST('dateDeb'), $user));
        }
    default: break;
}


$db->close();
