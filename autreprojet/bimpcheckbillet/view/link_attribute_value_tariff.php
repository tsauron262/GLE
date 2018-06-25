<?php

include_once '../param.inc.php';

include_once 'header.php';
include_once 'footer.php';


$arrayofjs = array('../js/link_attribute_value_tariff.js');

printHeader('Lier valeur d\'attribut - tarif', $arrayofjs);

print '<body>';

print '<fieldset class="container_form">';

print '<legend><span>Lier valeur d\'attribut - tarif<span></legend>';

print '<label for="event">Evènement </label><br/>';
print '<select class="chosen-select" name="id_event"><option value="">Sélectionnez un évènement</option></select><br/><br/>';

print '<label for="tariff">Tarif </label><br/>';
print '<select class="chosen-select" name="tariff"><option value="">Sélectionnez un tarif</option></select><br/><br/>';

print '<label for="attribute">Attribut </label><br/>';
print '<select class="chosen-select" name="attribute"><option value="">Sélectionnez un attribut</option></select><br/><br/>';


print '<label for="attribute_value">Valeur d\'attribut </label><br/>';
print '<select class="chosen-select" name="attribute_value"><option value="">Sélectionnez une valeur d\'attribut</option></select><br/><br/>';


print '<button class="btn btn-primary" name="link">Lier</button>';

print '<div id="alertSubmit"></div>';
print '</fieldset>';

print '</body>';

printFooter();
