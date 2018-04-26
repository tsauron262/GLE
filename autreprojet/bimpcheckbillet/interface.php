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
                'code_return' => $event->create($_POST['label'], $_POST['description'], $_POST['date_start'], $_POST['time_start'], $_POST['date_end'], $_POST['time_end'], $user->id, $_FILES['file']),
                'errors' => $event->errors));
            break;
        }

    /**
     * create_tariff.php
     */
    case 'create_tariff': {
            echo json_encode(array(
                'code_return' => $tariff->create($_POST['label'], $_POST['price'], $_POST['id_event'], $_FILES['file'], $_POST['require_names'], '', $_POST['date_start'], $_POST['time_start'], $_POST['date_end'], $_POST['time_end'], $_POST['type_extra_1'], $_POST['name_extra_1'], $_POST['type_extra_2'], $_POST['name_extra_2'], $_POST['type_extra_3'], $_POST['name_extra_3'], $_POST['type_extra_4'], $_POST['name_extra_4'], $_POST['type_extra_5'], $_POST['name_extra_5'], $_POST['type_extra_6'], $_POST['name_extra_6']),
                'errors' => $tariff->errors));
            break;
        }

    /**
     * create_ticket.php
     */
    case 'create_ticket': {
            $user_session = json_decode($_SESSION['user']);
            $user->fetch($user_session->id);
            echo json_encode(array(
                'code_return' => $ticket->create($_POST['id_tariff'], $user->id, $_POST['id_event'], $_POST['price'], $_POST['first_name'], $_POST['last_name'], $_POST['extra_1'], $_POST['extra_2'], $_POST['extra_3'], $_POST['extra_4'], $_POST['extra_5'], $_POST['extra_6']),
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


    /**
     * modify_event.php
     */
    case 'modify_event': {
            $user = json_decode($_SESSION['user']);
            echo json_encode(array(
                'code_return' => $event->update($_POST['id_event'], $_POST['label'], $_POST['description'], $_POST['date_start'], $_POST['time_start'], $_POST['date_end'], $_POST['time_end'], $user->id),
                'errors' => $event->errors));
            break;
        }

    case 'draft_event': {
            $user_session = json_decode($_SESSION['user']);
            $user->fetch($user_session->id);
            if ($user->validate_event != 1) {
                echo json_encode(array('errors' => "Vous n'avez pas le droit de définir comme bbrouillon un évènement."));
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
            if ($user->validate_event != 1) {
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
            if ($user->validate_event != 1) {
                echo json_encode(array('errors' => "Vous n'avez pas le droit de fermer un évènement."));
                break;
            } else {
                echo json_encode(array(
                    'code_return' => $event->updateStatus($_POST['id_event'], $event::STATUS_CLOSED),
                    'errors' => $event->errors));
                break;
            }
        }

    /**
     * modify_tariff.php
     */
    case 'modify_tariff': {
            echo json_encode(array(
                'code_return' => $tariff->update($_POST['id_tariff'], $_POST['label'], $_POST['price'], $_POST['require_names'], /* $_FILES['file'], */ $_POST['date_start'], $_POST['time_start'], $_POST['date_end'], $_POST['time_end'], $_POST['type_extra_1'], $_POST['name_extra_1'], $_POST['type_extra_2'], $_POST['name_extra_2'], $_POST['type_extra_3'], $_POST['name_extra_3'], $_POST['type_extra_4'], $_POST['name_extra_4'], $_POST['type_extra_5'], $_POST['name_extra_5'], $_POST['type_extra_6'], $_POST['name_extra_6']),
                'errors' => $tariff->errors));
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
    case 'get_tariffs_for_event': {
            echo json_encode(array(
                'tariffs' => $tariff->getTariffsForEvent($_POST['id_event']),
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


//    case 'get_image': {
//            $file = $_POST['folder'] . $_POST['name'] . '.png';
    // A few settings
// Read image path, convert to base64 encoding
//            $imageData = base64_encode(file_get_contents($file));
//
//// Format the image SRC:  data:{mime};base64,{data};
//            $src = 'data: ' . mime_content_type($image) . ';base64,' . $imageData;
//            $img_data = file_get_contents($file);
//            imagejpeg($img_data, $file);
//            echo '<img src=' . $file . '>';
//            $image = base64_encode($file);
//            echo $img_data;
//
//            $img_binary = fread(fopen($file, "r"), filesize($file));
//            $img_string = base64_encode($img_binary);
//
//            echo json_encode(array('src' => $img_string,
//                'errors' => array()));
//
//            break;
//        }

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