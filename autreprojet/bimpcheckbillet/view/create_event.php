<?php

include_once '../param.inc.php';

include_once 'header.php';
include_once 'footer.php';

$arrayofjs = array('../js/create_event.js', '../js/annexes.js');

printHeader('Crée évènement', $arrayofjs);

print '<body>';

print '<h4>Crée évènement</h4>';

print '<label for="label">Libellé </label>';
print '<input name="label" maxlength=256><br/>';

print '<label for="date_start">Date de début </label>';
print '<input type="text" name="date_start" style="width: 100px"><br/>';

print '<label for="date_start">Date de fin </label>';
print '<input type="text" name="date_end" style="width: 100px"><br/>';

print '<button name="create">Créer</button>';
print '<div id="alertSubmit"></div>';

print '</body>';

printFooter();