<?php

include_once '../param.inc.php';

include_once 'header.php';
include_once 'footer.php';


$arrayofjs = array('../js/link_attribute_tariff.js');

printHeader('Lier attribut', $arrayofjs);

print '<body>';

print '<fieldset class="container_form">';

print '<legend><span>Lier attribut<span></legend>';

print '<label for="event">Evènement </label><br/>';
print '<select class="chosen-select" name="id_event"><option value="">Sélectionnez un évènement</option></select><br/><br/>';

print '<label for="tariff">Tarif </label><br/>';
print '<select class="chosen-select" name="tariff"><option value="">Sélectionnez un tarif</option></select><br/><br/>';

print '<label for="attribute">Attribut </label><br/>';
print '<select class="chosen-select" name="attribute"><option value="">Sélectionnez une attribut</option></select><br/><br/>';

print '<button class="btn btn-primary" name="link">Lier</button>';

print '<div id="alertSubmit"></div>';
print '</fieldset>';

print '</body>';

printFooter();
