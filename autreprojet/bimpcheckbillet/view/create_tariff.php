<?php

include_once '../param.inc.php';

include_once 'header.php';
include_once 'footer.php';

include_once '../class/user.class.php';

$arrayofjs = array('../js/create_tariff.js');

/**
 * Function
 */
function printExtra($id) {
    print '<div class="sub_container_form">';
    print '<label for="name_extra_' . $id . '">Extra ' . $id . ' </label><br/>';
    print '<table><tr>';
    print '<th>Type</th>';
    print '<th>Nom</th>';
    print '<th>Obligatoire</th>';
    print '</tr><tr>';
    print '<td><select class="chosen-select" name="type_extra_' . $id . '">';
    print '<option value="">Sélectionnez</option>';
    print '<option value=1>Entier</option>';
    print '<option value=2>Réel</option>';
    print '<option value=3>Chaîne de charactère</option>';
    print '</select></td>';
    print '<td><input class="form-control" placeholder="Nom extra ' . $id . '" name="name_extra_' . $id . '" maxlength=256 style="width: 300px"></td>';
    print '<td><div class="btn-group btn-group-toggle" data-toggle="buttons">';
    print '<label class="btn btn-primary active"><input value=0 type="radio" name="require_extra_' . $id . '" checked>Non</label>';
    print '<label class="btn btn-primary">       <input value=1 type="radio" name="require_extra_' . $id . '">Oui</label>';
    print '</div></td>';
    print '</tr></table>';
    print '</div>';
}

/**
 * View
 */
printHeader('Créer tarif', $arrayofjs);


$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME;

$db = new PDO($dsn, DB_USER, DB_PASS_WORD)
        or die("Impossible de se connecter à la base : " . mysql_error());

$user = new User($db);
$user_session = json_decode($_SESSION['user']);
$user->fetch($user_session->id);


print '<body>';

print '<fieldset class="container_form">';

print '<legend><span>Créer tarif<span></legend>';

if ($user->status != $user::STATUT_SUPER_ADMIN and $user->create_event_tariff == 0) {
    print '<p>Vous n\'avez pas les droit requis pour créer un tariff</p>';
} else {

    print '<form id="create_form" action="../interface.php" method="post" enctype="multipart/form-data" >';
    print '<input name="action" value="create_tariff" style="display: none;"/>';

    print '<label for="event">Evènement </label><br/>';
    print '<select class="chosen-select" name="id_event"><option value="">Sélectionnez un évènement</option></select><br/><br/>';

    print '<label for="label">Libellé </label>';
    print '<input class="form-control" placeholder="Libellé" name="label" maxlength=256 style="width: 300px"><br/>';

    print '<label for="email_text">Contenu email </label>';
    print '<textarea id="email_text" class="tinymce" placeholder="Description" rows="3" name="email_text" style="width: 500px"></textarea><br/>';

    print '<label for="price">Prix (taxes incluses)</label>';
    print '<input class="form-control bfh-number" name="price" step=".01" type="number" min="0" style="width: 120px"/><br/>';

    print '<label for="number_place">Nombre de place</label>';
    print '<input class="form-control bfh-number" name="number_place" step="1" type="number" min="0" style="width: 120px"/><br/>';

    print '<label>Image (.bmp, .jpg ou .png)</label><br/>';
    print '<label class="btn btn-primary" for="file">&nbsp';
    print '<input id="file" accept=".bmp,.jpg,.png" type="file" name="file" style="display:none"/>Parcourir</label>';

    print '<span class="label label-info" id="name_file_display"></span>';
    print '<img id="img_display" src="#" alt="&nbsp Aucune image sélectionnée" style="max-height: 600px ; max-width: 800px"/><br/><br/><br/>';

    // custom image
    print '<label for="event">Image sur ticket (.bmp, .jpg ou .png)</label>&nbsp';
    print '<div class="btn-group btn-group-toggle" data-toggle="buttons">';
    print '<label class="btn btn-primary active"><input value=0 type="radio" name="input_cust_img" checked>Logo Zoomdici</label>';
    print '<label class="btn btn-primary">       <input value=1 type="radio" name="input_cust_img">Image personnalisée</label>';
    print '</div><br/><br/>';


    print '<div id="div_custom_img" style="display: none;">';
    print '<label>Image sur les tickets</label><br/>';
    print '<label class="btn btn-primary" for="custom_img">';
    print '<input id="custom_img" accept=".bmp,.jpg,.png" type="file" name="custom_img" style="display:none"/>Parcourir</label><br/>';

    print '<span class="label label-info" id="name_file_display"></span>';
    print '<img id="custom_img_display" src="#" alt="&nbsp Aucune image sélectionnée" style="max-height: 600px ; max-width: 800px"/><br/><br/><br/>';
    print '</div>';

    print '<label for="event">Exiger nom et prénom </label><br/>';
    print '<div class="btn-group btn-group-toggle" data-toggle="buttons">';
    print '<label class="btn btn-primary active"><input value=0 type="radio" name="require_names" checked>Non</label>';
    print '<label class="btn btn-primary">       <input value=1 type="radio" name="require_names">Oui</label>';
    print '</div><br/><br/>';


    print '<label for="date_stop_sale">Date de fin de vente</label><br/>';
    print '<input class="form-control" placeholder="Date de fin de vente" type="text" name="date_stop_sale" style="display: inline ; width: 160px"/>';
    print '<input class="form-control" type="time" value="00:00" name="time_end_sale" style="display: inline ; width: 100px"/><br/>';

    print '<label for="date_start">Date de début (facultatif)</label><br/>';
    print '<input class="form-control" placeholder="Date de début" type="text" name="date_start" style="display: inline ; width: 160px"/>';
    print '<input class="form-control" type="time" value="00:00" name="time_start" style="display: inline ; width: 100px"/><br/>';

    print '<label for="date_end">Date de fin (facultatif)</label><br/>';
    print '<input class="form-control" placeholder="Date de fin" type="text" name="date_end" style="display: inline ; width: 160px"/>';
    print '<input class="form-control" type="time" value="00:00" name="time_end" style="display: inline ; width: 100px"/><br/><br/>';

    printExtra(1);
    printExtra(2);
    printExtra(3);
    printExtra(4);
    printExtra(5);
    printExtra(6);

    print '<button class="btn btn-primary" name="create">Créer</button>';
    print '</form>';
    print '<div id="alertSubmit"></div>';
}

print '</fieldset>';
print '</body>';


printFooter();
