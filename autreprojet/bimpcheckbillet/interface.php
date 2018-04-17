<?php

session_start();

include_once 'param.inc.php';

include_once 'class/user.class.php';
include_once 'class/event.class.php';
include_once 'class/tariff.class.php';
include_once 'class/ticket.class.php';

$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME;

$db = new PDO($dsn, DB_USER, DB_PASS_WORD)
        or die("Impossible de se connecter à la base : " . mysql_error());

$user_session = json_decode($_SESSION['user']);

$user = new User($db);
$event = new Event($db);
$tariff = new Tariff($db);
$ticket = new Ticket($db);

//var_dump($_POST);
//return;

switch ($_POST['action']) {
    /**
     * create_event.php
     */
    case 'create_event': {
            $user = json_decode($_SESSION['user']);
            echo json_encode(array(
                'code_return' => $event->create($_POST['label'], $_POST['date_start'], $_POST['date_end'], $user->id, $_FILES['file']),
                'errors' => $event->errors));
            break;
        }

    /**
     * create_tariff.php
     */
    case 'create_tariff': {
            echo json_encode(array(
                'code_return' => $tariff->create($_POST['label'], $_POST['price'], $_POST['id_event'], $_FILES['file'], ($_POST['date_start'] == '') ? null : $_POST['date_start'], ($_POST['date_end'] == '') ? null : $_POST['date_end']),
                'errors' => $tariff->errors));
            break;
        }

    /**
     * create_ticket.php
     */
    case 'create_ticket': {
            echo json_encode(array(
                'code_return' => $ticket->create($_POST['id_tariff'], 1, $_POST['id_event']),
                'errors' => $ticket->errors));
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

    /**
     * check_ticket.php
     */
    case 'check_ticket': {
            $ticket->check($_POST['barcode'], $_POST['id_event']);
            echo json_encode(array('errors' => $ticket->errors));
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
     * home.php
     */
    case 'get_events_user': {
            $user_session = json_decode($_SESSION['user']);
            $user->fetch($user_session->id);
            echo json_encode(array(
                'events' => $event->getEvents($user->id, true, $user->status == $user::STATUT_SUPER_ADMIN),
                'errors' => $event->errors));
            break;
        }
    /**
     * General
     */
    case 'get_events': {
            $user_session = json_decode($_SESSION['user']);
            $user->fetch($user_session->id);
            echo json_encode(array(
                'events' => $event->getEvents($user->id, false, $user->status == $user::STATUT_SUPER_ADMIN),
                'errors' => $event->errors));
            break;
        }
    case 'get_tariffs_for_event' : {
            echo json_encode(array(
                'tariffs' => $tariff->getTariffsForEvent($_POST['id_event']),
                'errors' => $tariff->errors));
            break;
        }

    /**
     * Default
     */
    default: {
            echo json_encode(array(
                'errors' => "Pas d'action pour : " . $_POST['action']));
            break;
        }
}

//mysql_close($db);