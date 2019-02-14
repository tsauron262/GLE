<?php

/**
 *      \file       /bimpcontratauto/interface.php
 *      \ingroup    bimpcontratauto
 *      \brief      Make interface between the class and the client
 */
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/bimpremoveduplicate/class/BimpRemoveDuplicate.class.php';
$staticRD = new BimpRemoveDuplicateCustomer($db);
$action = GETPOST('action');
switch ($action) {

    case 'set_message': {
            $_SESSION['dol_events'][GETPOST('code')][] = GETPOST('message');
            break;
        }

    case 'get_all_duplicate': {
            echo json_encode(array(
                'duplicates' => $staticRD->getAllDuplicate(GETPOST('limit'), GETPOST('details')),
                'errors' => $staticRD->errors
            ));
            break;
        }

    case 'delete_customers': {
            echo json_encode(array(
                'nb_delete' => $staticRD->deleteCustomer(GETPOST('ids_to_delete')),
                'errors' => $staticRD->errors
            ));
            break;
        }

    default: {
            echo json_encode(array(
                'errors' => "Aucune action pour " . $action
            ));
            break;
        }
}


$db->close();
