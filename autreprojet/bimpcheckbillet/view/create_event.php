<?php

include_once '../param.inc.php';

include_once 'header.php';
include_once 'footer.php';

$arrayofjs = array('../js/create_event.js', '../js/annexes.js');

printHeader('Créer évènement', $arrayofjs);

print '<body>';

print '<fieldset class="container_form">';

print '<legend><span>Créer évènement<span></legend>';

print '<label for="label">Libellé </label>';
print '<input class="form-control" placeholder="Libellé" name="label" maxlength=256 style="width: 300px"><br/>';

print '<label for="date_start">Date de début</label>';
print '<input class="form-control" placeholder="Date de début" type="text" name="date_start" style="width: 160px"><br/>';

print '<label for="date_end">Date de fin</label>';
print '<input class="form-control" placeholder="Date de fin" type="text" name="date_end" style="width: 160px"><br/>';

print '<button class="btn btn-primary" name="create">S\'inscrire</button>';
print '</fieldset>';
print '<div id="alertSubmit"></div>';
print '</body>';

printFooter();