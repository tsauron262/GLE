<?php

include_once 'param.inc.php';

include_once 'class/event.class.php';
include_once 'class/client.class.php';
include_once 'class/tariff.class.php';
include_once 'class/ticket.class.php';

$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME;

$db = new PDO($dsn, DB_USER, DB_PASSWORD)
        or die("Impossible de se connecter à la base : " . mysql_error());


$event = new Event($db);
$client = new Client($db);
$tariff = new Tariff($db);
$ticket = new Ticket($db);

switch ($_POST['action']) {
    /**
     * create_event.php
     */
    case 'create_event': {
            echo json_encode(array(
                'code_return' => $event->create($_POST['label'], $_POST['date_start'], $_POST['date_end']),
                'errors' => $event->errors));
            break;
        }

    /**
     * registration_user.php
     */
    case 'registration_user': {
            echo json_encode(array(
                'code_return' => $client->create($_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['date_born']),
                'errors' => $client->errors));
            break;
        }

    /**
     * create_tariff.php
     */
    case 'create_tariff': {
            echo json_encode(array(
                'code_return' => $tariff->create($_POST['label'], $_POST['price'], $_POST['id_event']),
                'errors' => $tariff->errors));
            break;
        }

    /**
     * create_ticket.php
     */
    case 'create_ticket': {
            echo json_encode(array(
                'code_return' => $ticket->create($_POST['id_tariff'], $_POST['id_client'], $_POST['id_event']),
                'errors' => $ticket->errors));
            break;
        }

    /**
     * General
     */
    case 'get_events': {
            echo json_encode(array(
                'events' => $event->getEvents(),
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