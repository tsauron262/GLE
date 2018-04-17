<?php

include_once '../param.inc.php';

include_once 'header.php';
include_once 'footer.php';

$arrayofjs = array('../js/create_tariff.js');

printHeader('Créer tarif', $arrayofjs);


print '<body>';

print '<fieldset class="container_form">';

print '<legend><span>Créer tarif<span></legend>';

print '<form id="create_form" action="../interface.php" method="post" enctype="multipart/form-data" >';
print '<input name="action" value="create_tariff" style="display: none;"/>';

if (!isset($_SESSION['id_event'])) {
    print '<label for="price">Evènement </label><br/>';
    print '<select class="chosen-select" name="id_event"><option value="">Sélectionnez un évènement</option></select><br/><br/>';
} else {
    print '<select class="chosen-select" name="id_event" style="display:none">';
    print '<option value=' . $_SESSION['id_event'] . ' selected>Celui-là</option>';
    print '</select><br/><br/>';
}


print '<label for="label">Libellé </label>';
print '<input class="form-control" placeholder="Libellé" name="label" maxlength=256 style="width: 300px"><br/>';

print '<label for="price">Prix </label>';
print '<input class="form-control bfh-number" name="price" step=".01" type="number" min="0" style="width: 120px"/><br/>';

print '<label class="btn btn-primary" for="file">';
print '<input id="file" type="file" name="file" style="display:none"/>Parcourir</label>';

print '<span class="label label-info" id="name_file_display"></span>';
print '<img id="img_display" src="#" alt=" Aucune image sélectionnée"/><br/><br/><br/>';

print '<label for="date_start">Date de début (facultatif)</label>';
print '<input class="form-control" placeholder="Date de début" type="text" name="date_start" style="width: 160px"><br/>';

print '<label for="date_end">Date de fin (facultatif)</label>';
print '<input class="form-control" placeholder="Date de fin" type="text" name="date_end" style="width: 160px"><br/>';

print '<button class="btn btn-primary" name="create">Créer</button>';
print '</form>';
print '<div id="alertSubmit"></div>';

print '</fieldset>';
print '</body>';


printFooter();
