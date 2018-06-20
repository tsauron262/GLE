<?php

include_once '../param.inc.php';

include_once 'header.php';
include_once 'footer.php';


$arrayofjs = array('../js/create_combination.js');

printHeader('Créer déclinaison', $arrayofjs);

print '<body>';

print '<fieldset class="container_form">';

print '<legend><span>Créer déclinaison<span></legend>';

print '<label for="label">Libellé </label>';
print '<input class="form-control" placeholder="Libellé" name="label" maxlength=256 style="width: 300px"><br/>';

print '<label for="price">Prix </label>';
print '<input class="form-control bfh-number" name="price" step=".01" type="number" value=0 min="0" style="width: 120px"/><br/>';

print '<label for="number_place">Nombre de place</label>';
print '<input class="form-control bfh-number" name="number_place" step="1" type="number" value=0 min="0" style="width: 120px"/><br/>';

print '<button class="btn btn-primary" name="create">Créer</button>';

print '<div id="alertSubmit"></div>';
print '</fieldset>';

print '</body>';

printFooter();
