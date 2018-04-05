<?php

include_once '../param.inc.php';

include_once 'header.php';
include_once 'footer.php';

$arrayofjs = array('../js/create_tariff.js', '../js/annexes.js');

printHeader('Crée tarif', $arrayofjs);

print '<body>';

print '<h4>Crée tarif</h4>';

print '<label for="label">Libellé </label>';
print '<input name="label" maxlength=256><br/>';

print '<label for="price">Prix </label>';
print '<input name="price" type="number" min="0" step="any"/><br/>';

print '<label for="price">Evènement </label>';
print '<select name="event"></select><br/>';

print '<button name="create">Créer</button>';
print '<div id="alertSubmit"></div>';

print '</body>';

printFooter();