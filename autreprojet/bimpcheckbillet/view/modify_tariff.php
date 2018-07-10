<?php

include_once '../param.inc.php';

include_once 'header.php';
include_once 'footer.php';

include_once '../class/user.class.php';

$arrayofjs = array('../js/modify_tariff.js');

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
    print '<td><input class="form-control" placeholder="Nom extra ' . $id . '" name="name_extra_' . $id . '" maxlength=256 style="width: 300px"/></td>';
    print '<td><div class="btn-group btn-group-toggle" data-toggle="buttons">';
    print '<label class="btn btn-primary active"><input value=0 type="radio" name="require_extra_' . $id . '" checked/>Non</label>';
    print '<label class="btn btn-primary">       <input value=1 type="radio" name="require_extra_' . $id . '"/>Oui</label>';
    print '</div></td>';
    print '</tr></table>';
    print '</div>';
}

/**
 * View
 */
printHeader('Modifier tarif', $arrayofjs);


$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME;

$db = new PDO($dsn, DB_USER, DB_PASS_WORD)
        or die("Impossible de se connecter à la base : " . mysql_error());

$user = new User($db);
$user_session = json_decode($_SESSION['user']);
$user->fetch($user_session->id);


print '<body>';

print '<fieldset class="container_form">';

print '<legend><span>Modifier tarif<span></legend>';

if ($user->status != $user::STATUT_SUPER_ADMIN and $user->create_event_tariff == 0) {
    print '<p>Vous n\'avez pas les droit requis pour créer un tariff</p>';
} else {
//    print '<form id="create_form" action="../interface.php" method="post" enctype="multipart/form-data" >';
    print '<form id="modify_form" method="post" >';
    print '<input name="action" value="create_tariff" style="display: none;"/>';

    print '<label for="event">Evènement </label><br/>';
    print '<select class="chosen-select" name="id_event"><option value="">Sélectionnez un évènement</option></select><br/><br/>';

    print '<label for="tariff">Tarif </label><br/>';
    print '<select class="chosen-select" name="tariff"><option value="">Sélectionnez un tarif</option></select><br/><br/>';

    print '<div style="'
            . 'border: 1px solid #c2c2c2;'
            . 'border-radius: 8px;'
            . 'box-shadow: 0 1px 10px rgba(0,0,0,.5);'
            . 'padding: 10px;">';
    print '<label>Prestashop</label><br/>';





    print '<div class="alert alert-info" role="alert" name="select_tariff" style="display: block"><strong>Information</strong>: Sélectionnez un tarif pour créer un produit prestashop.</div>';

    print '<div class="btn btn-primary" name="create_prestashop_product" style="display: none">Creér produit prestashop</div><br/>';

    print '<div class="alert alert-info" role="alert" name="product_already_created" style="display: none"><strong>Information</strong>: Ce tarif correspond déjà à un produit prestashop</div>';

    print '<div class="btn btn-primary" name="toggle_active" style="display: none;">Activer/désactiver produit </div><br/>';

    print '<div class="alert alert-info" role="alert" name="product_not_created" style="display: none"><strong>Information</strong>: Créez le produit prestashop pour pouvoir l\'activer.</div>';




    print '<div id="div_tax" style="'
            . 'display: none;'
            . 'border: 1px solid #c2c2c2;'
            . 'border-radius: 8px;'
            . 'box-shadow: 0 1px 10px rgba(0,0,0,.5);'
            . 'padding: 10px;">';
    print '<label >Taxe </label><br/>';
    print '<div id="container_tax" class="btn-group btn-group-toggle" data-toggle="buttons">';
    print '<label class="btn btn-primary active"><input value=1 type="radio" name="tax">20%</label>';
    print '<label class="btn btn-primary">       <input value=2 type="radio" name="tax">10%</label>';
    print '<label class="btn btn-primary">       <input value=3 type="radio" name="tax">5.5%</label>';
    print '</div><br/><br/>';
    print '<div class="btn btn-primary" id="conf_create_prestashop_category">Confirmer création catégorie prestashop</div>';
    print '</div>';
    print '</div><br/>';

    print '<label for="label">Libellé </label>';
    print '<input class="form-control" placeholder="Libellé" name="label" maxlength=256 style="width: 300px"><br/>';

    print '<label for="email_text">Contenu email </label>';
    print '<textarea id="email_text" class="tinymce" placeholder="Description" rows="3" name="email_text" style="width: 500px"></textarea><br/>';

    print '<label for="price">Prix (taxes incluses)</label>';
    print '<input class="form-control bfh-number" name="price" step=".01" type="number" min="0" style="width: 120px"/><br/>';

    print '<label for="number_place">Nombre de place </label>';
    print '<input class="form-control bfh-number" name="number_place" step="1" type="number" min="0" style="width: 120px"/><br/>';

    print '<label for="img_display">Image</label><br/>';
    print '<img id="img_display" src="#" alt="&nbsp Aucune image" style="max-height: 600px ; max-width: 800px"/><br/><br/>';

    print '<label for="img_display">Image sur les tickets</label><br/>';
    print '<img id="img_custom_display" src="#" alt="&nbsp Aucune image n\'est définie pour les tickets de ce tariff" style="max-height: 600px ; max-width: 800px"/><br/><br/>';

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
    
    print '<div class="alert alert-info" role="alert" style="display: block;"><strong>Information en cas de modification:</strong><br/> Uniquement les champs suivants seront mis à jour dans prestashop:<br/> libellé, prix et contenue mail.</div>';

    print '<button class="btn btn-primary" name="modify">Modifier</button><br/><br/>';

    print '<div class="btn btn-danger" name="delete">Supprimer tarif</div>';

    print '</form>';
    print '<div id="alertSubmit"></div>';
}

print '</fieldset>';
print '</body>';


printFooter();
