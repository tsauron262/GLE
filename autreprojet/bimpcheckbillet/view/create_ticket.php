<?php

include_once '../param.inc.php';

include_once 'header.php';
include_once 'footer.php';

include_once '../class/user.class.php';


$arrayofjs = array('../js/create_ticket.js');

printHeader('Réserver ticket', $arrayofjs);


$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME;

$db = new PDO($dsn, DB_USER, DB_PASS_WORD)
        or die("Impossible de se connecter à la base : " . mysql_error());

$user = new User($db);
$user_session = json_decode($_SESSION['user']);
$user->fetch($user_session->id);

print '<body>';

print '<fieldset class="container_form">';

print '<legend><span>Réserver ticket<span></legend>';

if ($user->status != $user::STATUT_SUPER_ADMIN and $user->reserve_ticket == 0) {
    print '<p>Vous n\'avez pas les droit requis pour réserver un ticket</p>';
} else {

    print '<label for="event">Evènement </label><br/>';
    print '<select class="chosen-select" name="event"><option value="">Sélectionnez un évènement</option></select><br/><br/>';

    print '<label for="tariff">Tarif </label><br/>';
    print '<select class="chosen-select" name="tariff"><option value="">Sélectionnez un tarif</option></select><br/><br/>';

    print '<label for="price">Prix (facultatif)</label>';
    print '<input class="form-control bfh-number" name="price" step=".01" type="number" min="0" style="width: 120px"/><br/>';


    print '<label id="label_first_name" for="first_name">Prénom (facultatif)</label>';
    print '<input class="form-control" placeholder="Prénom" name="first_name" maxlength=256 style="width: 300px"><br/>';

    print '<label id="label_last_name" for="last_name">Nom (facultatif)</label>';
    print '<input class="form-control" placeholder="Nom" name="last_name" maxlength=256 style="width: 300px"><br/>';

    
    print '<div id="extra"></div>';

    print '<button class="btn btn-primary" name="create">Réserver</button>';
    print '<div id="alertSubmit"></div>';
}

print '</fieldset>';

print '</body>';

printFooter();
