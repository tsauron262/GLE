<?php

include_once 'header.php';
include_once 'footer.php';

$arrayofjs = array('../js/stats_event.js', '../lib/js/loader.js');

printHeader('Statistique évènement', $arrayofjs);


print '<body></div>';
print '<fieldset class="container_form">';
print '<legend><span>Statistique évènement<span></legend>';

print '<label for="event">Evènement </label><br/>';

print '<select class="chosen-select" name="event"><option value="">Sélectionnez évènement</option></select><br/><br/>';

print '<div id="container_event"></div>';

print '<div id="alertSubmit"></div><br/><br/>';

print '</fieldset>';
print '</body>';
