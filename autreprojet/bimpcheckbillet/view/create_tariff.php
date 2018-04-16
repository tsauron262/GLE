<?php

include_once '../param.inc.php';

include_once 'header.php';
include_once 'footer.php';

$arrayofjs = array('../js/create_tariff.js');

printHeader('Créer tarif', $arrayofjs);


print '<body>';

print '<fieldset class="container_form">';

print '<legend><span>Créer tarif<span></legend>';

print '<label for="label">Libellé </label>';
print '<input class="form-control" placeholder="Libellé" name="label" maxlength=256 style="width: 300px"><br/>';

print '<label for="price">Prix </label>';
print '<input class="form-control bfh-number" name="price" step=".01" type="number" min="0" style="width: 120px"/><br/>';

if (!isset($_SESSION['id_event'])) {
    print '<label for="price">Evènement </label><br/>';
    print '<select class="chosen-select" name="event"><option></option></select><br/><br/>';
} else {
    print '<select class="chosen-select" name="event" style="display:none">';
    print '<option value=' . $_SESSION['id_event'] . ' selected>Good event</option>';
    print '</select><br/><br/>';
}

print '<button class="btn btn-primary" name="create">Créer</button>';
print '</fieldset>';
print '<div id="alertSubmit"></div>';
print '</body>';


printFooter();
