<?php

include_once '../param.inc.php';

include_once 'header.php';
include_once 'footer.php';

include_once '../class/user.class.php';

$arrayofjs = array('../js/create_event.js');

printHeader('Créer évènement', $arrayofjs);

$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME;

$db = new PDO($dsn, DB_USER, DB_PASS_WORD)
        or die("Impossible de se connecter à la base : " . mysql_error());

$user = new User($db);
$user_session = json_decode($_SESSION['user']);
$user->fetch($user_session->id);

print '<body>';

print '<fieldset class="container_form">';

print '<legend><span>Créer évènement<span></legend>';

if ($user->status != $user::STATUT_SUPER_ADMIN and $user->create_event_tariff == 0) {
    print '<p>Vous n\'avez pas les droit requis pour créer un évènement</p>';
} else {

    print '<form id="create_form" action="../interface.php" method="post" enctype="multipart/form-data" >';
    print '<input name="action" value="create_event" style="display: none;"/>';

    print '<label for="label">Libellé </label>';
    print '<input class="form-control" placeholder="Libellé" name="label" maxlength=256 style="width: 300px"><br/>';

    print '<label for="description">Description </label>';
    print '<textarea id="description" class="tinymce" placeholder="Description" rows="3" name="description" style="width: 500px"></textarea><br/>';

    print '<label for="place">Lieu </label> <label style="font: 10px arial, sans-serif;">(La dernière ligne doit contenir le code postale et le nom de la ville)</label>';
    print '<textarea id="place" class="tinymce" placeholder="Lieu" rows="3" name="place" style="width: 500px"></textarea><br/>';

    print '<label for="date_start">Date de début</label><br/>';
    print '<input class="form-control" placeholder="Date de début" type="text" name="date_start" style="display: inline ; width: 160px"/>';
    print '<input class="form-control" type="time" value="00:00" name="time_start" style="display: inline ; width: 100px"/><br/><br/>';

    print '<label for="date_end">Date de fin</label><br/>';
    print '<input class="form-control" placeholder="Date de fin" type="text" name="date_end" style="display: inline ; width: 160px"/>';
    print '<input class="form-control" type="time" value="00:00" name="time_end" style="display: inline ; width: 100px"/><br/><br/>';


    print '<label>Catégorie mère</label><br/>';
    print '<div class="btn-group btn-group-toggle" data-toggle="buttons">';
    print '<label class="btn btn-primary active"><input value=' . ID_CATEG_FESTIVAL . ' type="radio" name="categ_parent" checked>Festival</label>';
    print '<label class="btn btn-primary">       <input value=' . ID_CATEG_CONCERT . ' type="radio" name="categ_parent">Concert</label>';
    print '<label class="btn btn-primary">       <input value=' . ID_CATEG_SPECTACLE . ' type="radio" name="categ_parent">Spectacle</label>';
    print '<label class="btn btn-primary">       <input value=' . ID_CATEG_AUTRE . ' type="radio" name="categ_parent">Autre</label>';
    print '</div><br/><br/>';


    print '<label>Image (.bmp, .jpg ou .png)</label><br/>';

    print '<label class="btn btn-primary" for="file">';
    print '<input id="file" accept=".bmp,.jpg,.png" type="file" name="file" style="display:none"/>Parcourir</label>';

    print '<span class="label label-info" id="name_file_display"></span>';
    print '<img id="img_display" src="#" alt="&nbsp Aucune image sélectionnée"/><br/><br/><br/>';

    print '<button class="btn btn-primary" name="create">Créer évènement</button>';
    print '</form>';
    print '<div id="alertSubmit"></div>';
    print '</fieldset>';
}
print '</body>';

printFooter();
