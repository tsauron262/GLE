<?php

include_once 'param.inc.php';

include_once 'class/event.class.php';
include_once 'class/client.class.php';

$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME;

$db = new PDO($dsn, DB_USER, DB_PASSWORD)
        or die("Impossible de se connecter à la base : " . mysql_error());


$event = new Event($db);
$client = new Client($db);

switch ($_POST['action']) {
    /**
     * Event
     */
    case 'create_event': {
            echo json_encode(array(
                'code_return' => $event->create($_POST['label'], $_POST['date_start'], $_POST['date_end']),
                'errors' => $event->errors));
            break;
        }
    /**
     * Client
     */
    case 'registration_user': {
            echo json_encode(array(
                'code_return' => $client->create($_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['date_born']),
                'errors' => $client->errors));
            break;
        }
    /**
     * Default
     */
    default: {
            echo "Pas d'action \n";
            break;
        }
}


//mysql_close($db);