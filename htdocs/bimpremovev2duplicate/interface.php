<?php

/**
 *      \file       /bimpcontratauto/interface.php
 *      \ingroup    bimpcontratauto
 *      \brief      Make interface between the class and the client
 */
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/bimpremovev2duplicate/class/BimpRemoveDuplicateV2.class.php';
$staticRD = new BimpRemoveDuplicateCustomerV2($db);

$action = GETPOST('action');
switch ($action) {

    case 'set_message': {
            $_SESSION['dol_events'][GETPOST('code')][] = GETPOST('message');
            break;
        }

    case 'get_all_duplicate': {
            $start = microtime(true);
            $staticRD->s_min = GETPOST('s_min');
            $staticRD->s_name = GETPOST('s_name');
            $staticRD->s_email = GETPOST('s_email');
            $staticRD->s_address = GETPOST('s_address');
            $staticRD->s_zip = GETPOST('s_zip');
            $staticRD->s_town = GETPOST('s_town');
            $staticRD->s_phone = GETPOST('s_phone');
            echo json_encode(array(
                'duplicates' => $staticRD->getAllDuplicate(GETPOST('limit'), GETPOST('details')),
                'nb_row' => $staticRD->nb_row,
                'time_exec' => microtime(true) - $start,
                'errors' => $staticRD->errors
            ));
            break;
        }

    case 'merge_duplicates': {
            echo json_encode(array(
                'success' => $staticRD->mergeDuplicate(GETPOST('src_to_dest')),
                'errors' => $staticRD->errors
            ));
            break;
        }

    case 'init_duplicate': {
            echo json_encode(array(
                'code' => $staticRD->setAsUnprocessed(),
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
