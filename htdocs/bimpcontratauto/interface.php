<?php

/**
 *      \file       /bimpcontratauto/interface.php
 *      \ingroup    bimpcontratauto
 *      \brief      Make interface between the class and the client
 */
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcontratauto/class/BimpContratAuto.class.php';

$ca = new BimpContratAuto($db);

switch (GETPOST('action')) {
    case 'getAllContrats': {
            $contrats = $ca->getAllContrats(GETPOST('id_client'));
            echo json_encode($contrats);
            break;
        }
    default: break;
}


$db->close();
