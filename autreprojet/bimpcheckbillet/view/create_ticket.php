<?php

include_once '../param.inc.php';

include_once 'header.php';
include_once 'footer.php';

$arrayofjs = array('../js/create_ticket.js');

printHeader('Réserver ticket', $arrayofjs);

print '<body>';

print '<fieldset class="container_form">';

print '<legend><span>Réserver ticket<span></legend>';

print '<label for="event">Evènement </label><br/>';
print '<select class="chosen-select" name="event"><option value="">Sélectionnez un évènement</option></select><br/><br/>';

print '<label for="tariff">Tarif </label><br/>';
print '<select class="chosen-select" name="tariff"><option value="">Sélectionnez un tariff</option></select><br/><br/>';

print '<label for="price">Prix (facultatif)</label>';
print '<input class="form-control bfh-number" name="price" step=".01" type="number" min="0" style="width: 120px"/><br/>';

print '<label for="extra_int1">Extra nombre 1 (facultatif)</label>';
print '<input class="form-control bfh-number" name="extra_int1" step="1" type="number" style="width: 120px"/><br/>';

print '<label for="extra_int2">Extra nombre 2 (facultatif)</label>';
print '<input class="form-control bfh-number" name="extra_int2" step="1" type="number" style="width: 120px"/><br/>';

print '<label for="extra_string1">Extra chaîne 1 (facultatif)</label>';
print '<input class="form-control" placeholder="Extra chaîne 1" name="extra_string1" maxlength=256 style="width: 300px"><br/>';

print '<label for="extra_string2">Extra chaîne 2 (facultatif) </label>';
print '<input class="form-control" placeholder="Extra chaîne 2" name="extra_string2" maxlength=256 style="width: 300px"><br/>';

print '<button class="btn btn-primary" name="create">Réserver</button>';
print '<div id="alertSubmit"></div>';

print '</fieldset>';

print '</body>';

printFooter();
