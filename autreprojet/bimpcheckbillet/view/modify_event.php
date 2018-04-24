<?php

include_once '../param.inc.php';

include_once 'header.php';
include_once 'footer.php';

include_once '../class/user.class.php';

$arrayofjs = array('../js/modify_event.js');

printHeader('Modifier évènement', $arrayofjs);

$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME;

$db = new PDO($dsn, DB_USER, DB_PASS_WORD)
        or die("Impossible de se connecter à la base : " . mysql_error());

$user = new User($db);
$user_session = json_decode($_SESSION['user']);
$user->fetch($user_session->id);

print '<body>';

print '<fieldset class="container_form">';

print '<legend><span>Modifier évènement<span></legend>';

if ($user->status != $user::STATUT_SUPER_ADMIN and $user->create_event_tariff == 0) {
    print '<p>Vous n\'avez pas les droit requis pour créer un évènement</p>';
} else {


//    print '<form id="create_form" action="../interface.php" method="post" enctype="multipart/form-data" >';
    print '<form id="create_form" method="post">';
    print '<input name="action" value="modify_event" style="display: none;"/>';


    print '<label for="event">Evènement </label><br/>';
    print '<select class="chosen-select" name="id_event"><option value="">Sélectionnez un évènement</option></select><br/><br/>';
    
    print '<label for="label">Libellé </label>';
    print '<input class="form-control" placeholder="Libellé" name="label" maxlength=256 style="width: 300px"><br/>';

    print '<label for="date_start">Date de début</label><br/>';
    print '<input class="form-control" placeholder="Date de début" type="text" name="date_start" style="display: inline ; width: 160px"/>';
    print '<input class="form-control" type="time" value="00:00" name="time_start" style="display: inline ; width: 100px"/><br/>';

    print '<label for="date_end">Date de fin</label><br/>';
    print '<input class="form-control" placeholder="Date de fin" type="text" name="date_end" style="display: inline ; width: 160px"/>';
    print '<input class="form-control" type="time" value="00:00" name="time_end" style="display: inline ; width: 100px"/><br/>';

    print '<label>Image</label><br/>';

    print '<label class="btn btn-primary" for="file">';
    print '<input id="file" type="file" name="file" style="display:none"/>Parcourir</label>';

    print '<span class="label label-info" id="name_file_display"></span>';
    print '<img id="img_display" src="#" alt="Aucune image sélectionnée"/><br/><br/><br/>';
//    print '<img id="img_display" src="../img/event/9.png"/><br/><br/><br/>';

    print '<button class="btn btn-primary" name="modify">Modifier évènement</button>';
    print '<div id="alertSubmit"></div>';
    print '</form>';
    print '</fieldset>';
}
print '</body>';

printFooter();
