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

    print '<div id="alertSubmit"></div><br/>';

    print '<br/><div style="'
            . 'border: 1px solid #c2c2c2;'
            . 'border-radius: 8px;'
            . 'box-shadow: 0 1px 10px rgba(0,0,0,.5);'
            . 'padding: 10px;">';
    print '<label>Prestashop</label><br/>';

    print '<div class="alert alert-info" role="alert" name="select_event" style = "display: none"><strong>Information</strong>: Sélectionnez un évènement pour créer une catégorie prestashop ou activer une catégorie</div>';

    print '<div class="btn btn-primary" name="create_prestashop_category">Créer catégorie prestashop</div><br/>';

    print '<div class="alert alert-info" role="alert" name="categ_already_created" style = "display: none"><strong>Information</strong>: Cet évènement est déjà catégorisé dans prestashop</div>';

    print '<div class="btn btn-primary" name="toggle_active" style="display: none;">Activer/désactiver catégorie</div><br/>';

    print '<div class="alert alert-info" role="alert" name="categ_not_created" style = "display: none"><strong>Information</strong>: Créer la catégorie pour pouvoir l\'activer</div>';

    print '</div><br/>';

    print '<label for="label">Libellé </label>';
    print '<input class="form-control" placeholder="Libellé" name="label" maxlength=256 style="width: 300px"><br/>';

    print '<label for="description">Description </label>';
    print '<textarea id="description" class="tinymce" placeholder="Description" name="description"></textarea><br/>';

    print '<label for="place">Lieu </label> <label style="font: 10px arial, sans-serif;">(La dernière ligne doit contenir le code postale et le nom de la ville)</label>';
    print '<textarea id="place" class="tinymce" placeholder="Lieu" rows="3" name="place" style="width: 500px"></textarea><br/>';

    print '<label for="date_start">Date de début</label><br/>';
    print '<input class="form-control" placeholder="Date de début" type="text" name="date_start" style="display: inline ; width: 160px"/>';
    print '<input class="form-control" type="time" value="00:00" name="time_start" style="display: inline ; width: 100px"/><br/><br/>';

    print '<label for="date_end">Date de fin</label><br/>';
    print '<input class="form-control" placeholder="Date de fin" type="text" name="date_end" style="display: inline ; width: 160px"/>';
    print '<input class="form-control" type="time" value="00:00" name="time_end" style="display: inline ; width: 100px"/><br/><br/>';

//    print '<label>Image</label><br/>';
//
//    print '<label class="btn btn-primary" for="file">';
//    print '<input id="file" type="file" name="file" style="display:none"/>Parcourir</label>';
//
    print '<label for="img_display">Image</label><br/>';
    print '<img id="img_display" src="#" alt="&nbsp Aucune image" style="max-height: 600px ; max-width: 800px"/><br/><br/>';

    print '<div class="alert alert-info" role="alert" style="display: block;"><strong>Information en cas de modification:</strong><br/> Uniquement les champs suivants seront mis à jour dans prestashop:<br/> libellé, description et lieu.</div>';
    
    print '<button class="btn btn-primary" name="modify">Modifier évènement</button>';

//    print '<button class="btn btn-danger" name="delete">Supprimer évènement</button>';

    print '<div id="alertBottom"></div><br/><br/>';


    print '</form><br/>';

    print '<label>Statut</label><br/>';
    print '<button class="btn btn-primary" name="draft">Définir comme brouillon</button>';
    print '<button class="btn btn-success" name="validate" style="margin-left: 20px;">Valider évènement</button>';
    print '<button class="btn btn-danger" name="close" style="margin-left: 20px;">Fermer évènement</button><br/><br/>';

    print '<button class="btn btn-danger" name="delete">Supprimer</button>';

    print '</fieldset>';
}
print '</body>';

printFooter();
