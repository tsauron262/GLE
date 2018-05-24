<?php

include_once '../param.inc.php';

include_once 'header.php';
include_once 'footer.php';

$arrayofjs = array('../js/list_ticket.js');
$arrayofcss = array('../css/list_ticket.css');

/**
 * View
 */
printHeader('Liste ticket', $arrayofjs, $arrayofcss);


print '<body>';

print '<fieldset class="container_form">';

print '<legend><span>Liste ticket<span></legend>';

print '<label for="event">Evènement </label><br/>';

print '<select class="chosen-select" name="event"><option value="">Sélectionnez évènement</option></select><br/><br/>';

print '<div id="displayTable"></div>';

print '</fieldset>';
print '<div id="alertSubmit"></div>';
print '</body>';


printFooter();
