<?php

/**
 *      \file       /bimpcontratauto/interface.php
 *      \ingroup    bimpcontratauto
 *      \brief      Make interface between the class and the client
 */
if ($_POST['action'] == 'get_progress') {
    require_once("../conf/conf.php");
    die(file_get_contents($dolibarr_main_data_root . '/progress.txt'));
}

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/bimpremovev2duplicate/class/BimpRemoveDuplicateV2.class.php';
$rd = new BimpRemoveDuplicateCustomerV2($db);
session_start();

$action = GETPOST('action');
switch ($action) {

    case 'get_all_duplicate': {
            $start = microtime(true);
            $rd->s_min = GETPOST('s_min');
            $rd->s_name = GETPOST('s_name');
            $rd->s_email = GETPOST('s_email');
            $rd->s_address = GETPOST('s_address');
            $rd->s_zip = GETPOST('s_zip');
            $rd->s_town = GETPOST('s_town');
            $rd->s_phone = GETPOST('s_phone');
            $rd->s_siret = GETPOST('s_siret');
            echo json_encode(array(
                'duplicates' => $rd->getAllDuplicate(GETPOST('limit'), GETPOST('commercial'), GETPOST('details')),
                'nb_row' => $rd->nb_row,
                'time_exec' => microtime(true) - $start,
                'errors' => $rd->errors
            ));
            break;
        }

    case 'merge_duplicates': {
            echo json_encode(array(
                'success' => $rd->mergeDuplicate(GETPOST('src_to_dest')),
                'code' => $rd->setAsProcessed(GETPOST('ids_processed')),
                'errors' => $rd->errors
            ));
            break;
        }

    case 'init_duplicate': {
            echo json_encode(array(
                'code' => $rd->setAsUnprocessed(),
                'errors' => $rd->errors
            ));
            break;
        }

    case 'set_as_processed': {
            echo json_encode(array(
                'code' => $rd->setAsProcessed(array(GETPOST('id'))),
                'errors' => $rd->errors
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
