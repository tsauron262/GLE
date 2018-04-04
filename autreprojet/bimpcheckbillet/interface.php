<?php

include_once 'param.inc.php';

include_once 'class/event.class.php';

$dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME;

$db = new PDO($dsn, DB_USER, DB_PASSWORD)
        or die("Impossible de se connecter à la base : " . mysql_error());


$event = new Event($db);

switch ($_POST['action']) {
    case 'create_event': {
            echo "OKKKKKKKKKK\n";
            break;
        }
    default: {
            echo "Pas d'action \n";
            break;
        }
}


//mysql_close($db);