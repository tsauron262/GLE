<?php

include_once '../param.inc.php';

include_once 'header.php';
include_once 'footer.php';

$arrayofjs = array('../js/create_ticket.js');

printHeader('Réserver ticket', $arrayofjs);

print '<body>';

print '<h4>Réserver ticket</h4>';

print '<label for="event">Evènement </label>';
print '<select name="event"></select><br/>';

print '<label for="tariff">Tarif </label>';
print '<select name="tariff"></select><br/>';

print '<button name="create">Créer</button>';
print '<div id="alertSubmit"></div>';

print '</body>';

printFooter();