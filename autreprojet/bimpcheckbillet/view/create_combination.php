<?php

include_once '../param.inc.php';

include_once 'header.php';
include_once 'footer.php';


$arrayofjs = array('../js/create_combination.js');

printHeader('Créer combinaison', $arrayofjs);

print '<body>';

print '<fieldset class="container_form">';

print '<legend><span>Créer combinaison<span></legend>';

print '<label for="label">Libellé </label>';
print '<input class="form-control" placeholder="Libellé" name="label" maxlength=256 style="width: 300px"><br/>';

print '<button class="btn btn-primary" name="create">Créer combinaison</button>';

print '<div id="alertSubmit"></div>';
print '</fieldset>';

print '</body>';

printFooter();
