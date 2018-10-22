<?php

error_reporting(E_ERROR);
ini_set('display_errors', 1);

ini_set('max_execution_time', 600);

session_start();

include_once 'param.inc.php';

include_once 'class/user.class.php';
include_once 'class/event.class.php';
include_once 'class/tariff.class.php';
include_once 'class/ticket.class.php';
include_once 'class/order.class.php';
include_once 'class/attribute.class.php';
include_once 'class/attribute_value.class.php';

if (isset($_POST['description']))
    $_POST['description'] = addslashes($_POST['description']);


$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME;

$db = new PDO($dsn, DB_USER, DB_PASS_WORD)
        or die("Impossible de se connecter à la base interne : " . mysql_error());

$action = $_POST['action'];


if (!isset($_POST['sender']) and $action != 'login' and $action != 'register')
    @$user_session = json_decode($_SESSION['user']);

$user = new User($db);
$event = new Event($db);
$tariff = new Tariff($db);
$ticket = new Ticket($db);
$attribute = new Attribute($db);
$attribute_value = new AttributeValue($db);


if (!IS_MAIN_SERVER) {
    if ($action != 'check_ticket' and $action != 'login' and $action != 'get_events') {
        echo json_encode(array(
            'errors' => "Ce serveur n'autorise que les connexions et les validation de ticket."));
        exit();
    }
}

switch ($action) {
    /*            HOME               */

    /**
     * home.php
     */
    case 'get_events_user': {
            if (isset($_SESSION['user'])) {
                $user_session = json_decode($_SESSION['user']);
                $user->fetch($user_session->id);
                echo json_encode(array(
                    'events' => $event->getEvents($user->id, true, $user->status == $user::STATUT_SUPER_ADMIN),
                    'errors' => $event->errors));
            }
            break;
        }






    /*           MANAGEMENT          */

    /**
     * manage_user.php
     */
    case 'get_users': {
            $user_session = json_decode($_SESSION['user']);
            $user->fetch($user_session->id);
            if ($user->status != $user::STATUT_SUPER_ADMIN) {
                echo json_encode(array('errors' => "Vous n'avez pas le droit d'accéder à ces données"));
                break;
            } else {
                $static_user = new User($db);
                echo json_encode(array(
                    'users' => $static_user->getUser(),
                    'errors' => $static_user->errors));
                break;
            }
        }
    case 'change_login_and_pass_word': {
            $user_session = json_decode($_SESSION['user']);
            $user->fetch($user_session->id);
            if ($user->status != $user::STATUT_SUPER_ADMIN) {
                echo json_encode(array('errors' => "Vous n'avez pas le droit d'accéder à ces données"));
                break;
            } else {
                $static_user = new User($db);
                echo json_encode(array(
                    'code_return' => $static_user->changeLoginAndPassWord($_POST['id_user'], $_POST['login'], $_POST['pass_word']),
                    'errors' => $static_user->errors));
                break;
            }
        }

    case 'change_right_user': {
            $user_session = json_decode($_SESSION['user']);
            $user->fetch($user_session->id);
            if ($user->status != $user::STATUT_SUPER_ADMIN) {
                echo json_encode(array('errors' => "Vous n'avez pas le droit d'accéder à ces données"));
                break;
            } else {
                $static_user = new User($db);
                echo json_encode(array(
                    'code_return' => $static_user->updateUser($_POST['id_user'], $_POST['right'], $_POST['new_status']),
                    'errors' => $static_user->errors));
                break;
            }
        }




    /*           EVENT               */

    /**
     * create_event.php
     */
    case 'create_event': {
            $user = json_decode($_SESSION['user']);
            echo json_encode(array(
                'code_return' => $event->create($_POST['label'], $_POST['description'], $_POST['place'], $_POST['date_start'], $_POST['time_start'], $_POST['date_end'], $_POST['time_end'], $user->id, $_FILES['file'], $_POST['categ_parent'], $_POST['id_categ ']),
                'errors' => $event->errors));
            break;
        }


    /**
     * modify_event.php
     */
    case 'modify_event': {
            $user = json_decode($_SESSION['user']);
            echo json_encode(array(
                'code_return' => $event->update($_POST['id_event'], $_POST['label'], $_POST['description'], $_POST['place'], $_POST['date_start'], $_POST['time_start'], $_POST['date_end'], $_POST['time_end'], $user->id),
                'errors' => $event->errors));
            break;
        }

    case 'draft_event': {
            $user_session = json_decode($_SESSION['user']);
            $user->fetch($user_session->id);
            if ($user->validate_event != 1 and $user->status != 2) {
                echo json_encode(array('errors' => "Vous n'avez pas le droit de définir comme brouillon un évènement."));
                break;
            } else {
                echo json_encode(array(
                    'code_return' => $event->updateStatus($_POST['id_event'], $event::STATUS_DRAFT),
                    'errors' => $event->errors));
                break;
            }
        }

    case 'validate_event': {
            $user_session = json_decode($_SESSION['user']);
            $user->fetch($user_session->id);
            if ($user->validate_event != 1 and $user->status != 2) {
                echo json_encode(array('errors' => "Vous n'avez pas le droit de valider un évènement."));
                break;
            } else {
                echo json_encode(array(
                    'code_return' => $event->updateStatus($_POST['id_event'], $event::STATUS_VALIDATE),
                    'errors' => $event->errors));
                break;
            }
        }

    case 'close_event': {
            $user_session = json_decode($_SESSION['user']);
            $user->fetch($user_session->id);
            if ($user->validate_event != 1 and $user->status != 2) {
                echo json_encode(array('errors' => "Vous n'avez pas le droit de fermer un évènement."));
                break;
            } else {
                echo json_encode(array(
                    'code_return' => $event->updateStatus($_POST['id_event'], $event::STATUS_CLOSED),
                    'errors' => $event->errors));
                break;
            }
        }
    case 'set_id_categ': {
            echo json_encode(array(
                'code_return' => $event->setIdCateg($_POST['id_event'], $_POST['id_categ']),
                'errors' => $event->errors));
            break;
        }

    case 'delete_event': {
            echo json_encode(array(
                'code_return' => $event->delete($_POST['id_event']),
                'errors' => $event->errors));
            break;
        }

    /**
     * stats_event.php
     */
    case 'get_stats': {
            echo json_encode(array(
                'tab' => $event->getStats($_POST['id_event']),
                'errors' => $event->errors));
            break;
        }






    /*           TARIFF              */

    /**
     * create_tariff.php
     */
    case 'create_tariff': {
            echo json_encode(array(
                'code_return' => $tariff->create($_POST['label'], $_POST['price'], $_POST['number_place'], $_POST['id_event'], $_FILES['file'], $_FILES['custom_img'], $_POST['input_cust_img'], $_POST['require_names'], '', $_POST['date_stop_sale'], $_POST['time_end_sale'], $_POST['date_start'], $_POST['time_start'], $_POST['date_end'], $_POST['time_end'], $_POST['email_text'], $_POST['type_extra_1'], $_POST['name_extra_1'], $_POST['require_extra_1'], $_POST['type_extra_2'], $_POST['name_extra_2'], $_POST['require_extra_2'], $_POST['type_extra_3'], $_POST['name_extra_3'], $_POST['require_extra_3'], $_POST['type_extra_4'], $_POST['name_extra_4'], $_POST['require_extra_4'], $_POST['type_extra_5'], $_POST['name_extra_5'], $_POST['require_extra_5'], $_POST['type_extra_6'], $_POST['name_extra_6'], $_POST['require_extra_6']),
                'errors' => $tariff->errors));
            break;
        }

    /**
     * modify_tariff.php
     */
    case 'modify_tariff': {
            echo json_encode(array(
                'code_return' => $tariff->update($_POST['id_tariff'], $_POST['label'], $_POST['price'], $_POST['number_place'], $_POST['require_names'], /* $_FILES['file'], */ $_POST['date_stop_sale'], $_POST['time_stop_sale'], $_POST['date_start'], $_POST['time_start'], $_POST['date_end'], $_POST['time_end'], $_POST['email_text'], $_POST['type_extra_1'], $_POST['name_extra_1'], $_POST['require_extra_1'], $_POST['type_extra_2'], $_POST['name_extra_2'], $_POST['require_extra_2'], $_POST['type_extra_3'], $_POST['name_extra_3'], $_POST['require_extra_3'], $_POST['type_extra_4'], $_POST['name_extra_4'], $_POST['require_extra_4'], $_POST['type_extra_5'], $_POST['name_extra_5'], $_POST['require_extra_5'], $_POST['type_extra_6'], $_POST['name_extra_6'], $_POST['require_extra_6']),
                'errors' => $tariff->errors));
            break;
        }
    case 'set_id_prod_extern': {
            echo json_encode(array(
                'code_return' => $tariff->setIdProdExtern($_POST['id_tariff'], $_POST['id_prod_extern']),
                'errors' => $tariff->errors));
            break;
        }

    case 'delete_tariff': {
            $tariff->fetch($_POST['id_tariff']);
            echo json_encode(array(
                'code_return' => $tariff->delete(),
                'errors' => $tariff->errors));
            break;
        }








    /*         ATTRIBUTE             */

    /**
     * create_attribute.php
     */
    case 'create_attribute': {
            echo json_encode(array(
                'id_inserted' => $attribute->create($_POST['label'], $_POST['type'], $_POST['id_attribute_extern']),
                'errors' => $attribute->errors));
            break;
        }

    /**
     * link_attribute_tariff.php
     */
    case 'link_attribute_tariff': {
            echo json_encode(array(
                'id_inserted' => $attribute->createTariffAttribute($_POST['id_tariff'], $_POST['id_attribute']),
                'errors' => $attribute->errors));
            break;
        }

    /**
     * create_attribute_value.php
     */
    case 'create_attribute_value': {
            echo json_encode(array(
                'id_inserted' => $attribute_value->create($_POST['label'], $_POST['id_attribute_parent'], $_POST['id_attribute_value_extern']),
                'errors' => $attribute_value->errors));
            break;
        }

    /**
     * link_attribute_value_tariff.php
     */
    case 'link_attribute_value_tariff': {
            echo json_encode(array(
                'id_inserted' => $attribute_value->createTariffAttributeValue($_POST['id_tariff'], $_POST['id_attribute_value'], $_POST['price'], $_POST['qty']),
                'errors' => $attribute_value->errors));
            break;
        }






    /*           TICKET              */

    /**
     * create_ticket.php
     */
    case 'create_ticket': {
            $user_session = json_decode($_SESSION['user']);
            $user->fetch($user_session->id);
            if ($user->id == EXTERN_USER) {
                echo json_encode(array(
                    'code_return' => $ticket->create($_POST['id_tariff'], $user->id, $_POST['id_event'], $_POST['price'], $_POST['first_name'], $_POST['last_name'], $_POST['extra_1'], $_POST['extra_2'], $_POST['extra_3'], $_POST['extra_4'], $_POST['extra_5'], $_POST['extra_6'], $_POST['id_order'], $_POST['id_prod_extern']),
                    'errors' => $ticket->errors));
            } else {
                $id_order = -$user->id;
                $id_ticket = $ticket->create($_POST['id_tariff'], $user->id, $_POST['id_event'], $_POST['price'], $_POST['first_name'], $_POST['last_name'], $_POST['extra_1'], $_POST['extra_2'], $_POST['extra_3'], $_POST['extra_4'], $_POST['extra_5'], $_POST['extra_6'], $id_order, $_POST['id_prod_extern']);
                $ticket->createPdf($id_ticket, 5, 5, true, true, true, $id_order);
                echo json_encode(array(
                    'code_return' => $id_ticket,
                    'url' => URL_CHECK . 'img/tickets/ticket' . base64_encode($id_order) . '.pdf',
                    'errors' => $ticket->errors));
            }
            break;
        }

    /**
     * check_ticket.php
     */
    case 'check_ticket': {
            $ticket->check($_POST['barcode'], $_POST['id_event']);
            echo json_encode(array('errors' => $ticket->errors));
            break;
        }

    /**
     * list_ticket.php
     */
    case 'get_ticket_list': {
            echo json_encode(array(
                'tariffs' => $event->getTicketList($_POST['id_event']),
                'errors' => $event->errors));
            break;
        }

    /**
     * print_ticket.php
     */
    case 'create_tickets_from_check': {
            $user_session = json_decode($_SESSION['user']);
            $user->fetch($user_session->id);
            $id_user = (int) $user->id;
            $id_event = (int) $_POST['id_event'];
            $id_tariff = (int) $_POST['id_tariff'];
            $with_num = (int) $_POST['with_num'];
            $num_start = (int) $_POST['num_start'];
            $number = (int) $_POST['number'];
            $format = $_POST['format'];
            $souche = (int) $_POST['souche'];

            $num_in_db = $num_start;

            $ids_inserted = array();

            for ($i = 0; $i < $number; $i++, $num_in_db++) {
                $new_id = $ticket->create($id_tariff, $id_user, $id_event, '', $num_in_db, '', '', '', '', '', '', '', '');
                // $ticket->create($id_tariff, $id_user, $id_event, $price, $first_name, $last_name, $extra_1, $extra_2, $extra_3, $extra_4, $extra_5, $extra_6, $id_order = '');
                if ($new_id > 0)
                    $ids_inserted[] = $new_id;
                else {
                    echo json_encode(array(
                        'errors' => $ticket->errors));
                    die();
                }
            }

            echo json_encode(array(
                'code_return' => $ticket->createPdfFromCheck($ids_inserted, $id_event, $id_tariff, $with_num, $num_start, $format, $souche),
                'errors' => $ticket->errors,
            ));
            break;
        }








    /*          GENERAL              */

    case 'change_event_admin': {
            $user_session = json_decode($_SESSION['user']);
            $user->fetch($user_session->id);
            if ($user->status != $user::STATUT_SUPER_ADMIN) {
                echo json_encode(array('errors' => "Vous n'avez pas le droit de modifier ces données"));
                break;
            } else {
                if ($_POST['new_status'] == 'true') {
                    echo json_encode(array(
                        'code_return' => $event->createEventAdmin($_POST['id_event'], $_POST['id_user']),
                        'errors' => $event->errors));
                } elseif ($_POST['new_status'] == 'false') {
                    echo json_encode(array(
                        'code_return' => $event->deleteEventAdmin($_POST['id_event'], $_POST['id_user']),
                        'errors' => $event->errors));
                } else {
                    echo json_encode(array('errors' => "Mauvais statut: " . $_POST['new_status']));
                }
            }
            break;
        }

    case 'get_events': {
            if (isset($_SESSION['user'])) {
                $user_session = json_decode($_SESSION['user']);
                $user->fetch($user_session->id);
                echo json_encode(array(
                    'events' => $event->getEvents($user->id, false, $user->status == $user::STATUT_SUPER_ADMIN),
                    'errors' => $event->errors));
            }
            break;
        }

    case 'get_tariffs_for_event': {
            echo json_encode(array(
                'tariffs' => $tariff->getTariffsForEvent($_POST['id_event']),
                'errors' => $tariff->errors));
            break;
        }

    case 'get_tariffs_for_event_with_attribute': {
            echo json_encode(array(
                'tariffs' => $tariff->getTariffsForEvent($_POST['id_event'], true),
                'errors' => $tariff->errors));
            break;
        }

    case 'change_event_session': {
            $_SESSION['id_event'] = intVal($_POST['id_event']);
            if ($_SESSION['id_event'] > 0)
                echo json_encode(array('code_return' => 1));
            else
                echo json_encode(array('code_return' => -1));
            break;
        }

    case 'get_remaining_place': {
            echo json_encode(array(
                'tariffs' => $tariff->getRemainingPlace($_POST['id_tariff']),
                'errors' => $tariff->errors));
            break;
        }

    case 'get_event_by_tariff_id': {
            $tariff->fetch($_POST['id_tariff']);
            $event->fetch($tariff->fk_event);
            echo json_encode(array(
                'event' => $event,
                'image_name' => $tariff->getImageName($_POST['id_tariff']),
                'errors' => array_merge($tariff->errors, $event->errors)));
            break;
        }

    case 'get_all_attributes': {
            echo json_encode(array(
                'attributes' => $attribute->getAllAttribute(),
                'errors' => $attribute->errors));
            break;
        }

    case 'get_attributes_value_by_parent_id': {
            echo json_encode(array(
                'attribute_values' => $attribute_value->getAllByParentId($_POST['id_attribute_parent']),
                'errors' => $attribute_value->errors));
            break;
        }

    case 'get_attributes_by_tariff_id': {
            echo json_encode(array(
                'attributes' => $attribute->getAttributeByTariff($_POST['id_tariff']),
                'errors' => $attribute->errors));
            break;
        }

    /**
     * register.php
     */
    case 'register': {
            $id_user = $user->create($_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['login'], $_POST['pass_word']);
            if ($id_user > 0) {
                $user->fetch($id_user);
                unset($user->db);
                $_SESSION['user'] = json_encode($user);
            }
            echo json_encode(array(
                'id_inserted' => $id_user,
                'errors' => $user->errors));
            break;
        }
    /**
     * index.php
     */
    case 'login': {
            $id_user = $user->connect($_POST['login'], $_POST['pass_word']);
            if ($id_user > 0) {
                $user->fetch($id_user);
                unset($user->db);
                $_SESSION['user'] = json_encode($user);
            }
            echo json_encode(array('errors' => $user->errors));
            break;
        }





    /*         PRESTASHOP            */

    case 'get_tariff_from_prestashop': {
            echo json_encode(array(
                'tariffs' => $tariff->getTariffByProdsExtern($_POST['ids_prods_extern']),
                'errors' => $tariff->errors));
            break;
        }

    case 'get_ids_events_by_ids_tariffs': {
            echo json_encode(array(
                'ids_events' => $tariff->getIdsEventsByIdsTariffs($_POST['ids_tariff']),
                'errors' => $tariff->errors));
            break;
        }

    case 'check_order': {
            $dsn2 = 'mysql:host=' . DB_HOST_2 . ';dbname=' . DB_NAME_2;
            $db2 = new PDO($dsn2, DB_USER_2, DB_PASS_WORD_2)
                    or die("Impossible de se connecter à la base externe : " . mysql_error());
            $order = new Order($db2);
            $code_return = $order->check($_POST['id_order'], $_POST['tickets'], $ticket);
            if ($code_return == 1) {
                $ids_inserted = array();
                $i = 0;
                foreach ($_POST['tickets'] as $t) {
                    $id_inserted = $ticket->create($t['id_tariff'], EXTERN_USER, $t['id_event'], $t['price'], $t['first_name'], $t['last_name'], $t['extra_1'], $t['extra_2'], $t['extra_3'], $t['extra_4'], $t['extra_5'], $t['extra_6'], $_POST['id_order']);
                    $ids_inserted[] = $id_inserted;
                    if ($id_inserted < 0) {
                        array_pop($ids_inserted);
                        foreach ($ids_inserted as $id)
                            $ticket->delete($id);
                        echo json_encode(array(
                            'code_return' => $ids_inserted,
                            'errors' => $ticket->errors
                        ));
                        break;
                    }
                    $i++;
                }
                echo json_encode(array(
                    'ids_inserted' => $ids_inserted,
                    'errors' => array_merge($order->errors, $ticket->errors)
                ));
            } else {
                echo json_encode(array(
                    'code_return' => $code_return,
                    'errors' => $order->errors
                ));
            }
            break;
        }

    case 'create_tickets': {
            $tickets = $ticket->getTicketsByOrder($_POST['id_order']);
            $position = array('x' => 5, 'y' => 5);
            $i = 0;
            if (is_array($tickets)) {
                foreach ($tickets as $t) {
                    $is_first = $i == 0;
                    $is_last = ($i + 1 == sizeof($tickets));
                    $set_to_left = ($i % 2 == 0);
                    $position = $ticket->createPdf($t->id, $position['x'], $position['y'], $is_first, $is_last, $set_to_left, $_POST['id_order']);
                    $i++;
                }
                echo json_encode(array(
                    'code_return' => 1,
                    'errors' => $ticket->errors
                ));
            } else {
                echo json_encode(array(
                    'code_return' => 0,
                    'errors' => "Veuillez remplir au moins un ticket"
                ));
            }
            break;
        }

    case 'check_order_status': {
            $dsn2 = 'mysql:host=' . DB_HOST_2 . ';dbname=' . DB_NAME_2;
            $db2 = new PDO($dsn2, DB_USER_2, DB_PASS_WORD_2)
                    or die("Impossible de se connecter à la base externe : " . mysql_error());
            $order = new Order($db2);
            $status = $order->checkOrderStatus($_POST['id_order'], $ticket);
            if ($status == 1) {
                echo json_encode(array(
                    'status' => $status,
                    'generated_tickets' => $order->generateTicket($_POST['id_order'], $ticket, $tariff),
                    'errors' => $order->errors
                ));
            } else {
                echo json_encode(array(
                    'status' => $status,
                    'errors' => $order->errors
                ));
            }
            break;
        }
    case 'get_filled_tickets': {
            echo json_encode(array(
                'tickets' => $ticket->getTicketsByOrder($_POST['id_order']),
                'errors' => $ticket->errors
            ));
            break;
        }




    /*          DEFAULT              */

    default: {
            echo json_encode(array(
                'errors' => "Pas d'action pour : " . $_POST['action']));
            break;
        }
}
