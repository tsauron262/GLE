<?php

/**
 *      \file       /bimpticketrestaurant/interface.php
 *      \ingroup    bimpticketrestaurant
 *      \brief      Make interface between the class and the client
 */
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/bimpticketrestaurant/class/BimpTicketRestaurant.class.php';

$btr = new BimpTicketRestaurant($db);

switch (GETPOST('action')) {
    case 'get_ticket': {
            echo json_encode(array(
                'user_ticket' => $btr->getTicket(GETPOST('id_user')),
                'errors' => $btr->errors
            ));
            break;
        }


    default: {
            echo json_encode(array(
                'errors' => "Aucune action ne correspond à : " . GETPOST('action')
            ));
            break;
        }
}


$db->close();
