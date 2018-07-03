<?php

include_once '../param.inc.php';

include_once 'header.php';
include_once 'footer.php';

$arrayofjs = array('../js/print_ticket.js');

/**
 * View
 */
printHeader('Imprimer ticket', $arrayofjs);


print '<body>';

print '<fieldset class="container_form">';

print '<legend><span>Imprimer ticket<span></legend>';

print '<label for="event">Evènement </label><br/>';
print '<select class="chosen-select" name="id_event"><option value="">Sélectionnez un évènement</option></select><br/><br/>';

print '<label for="tariff">Tarif </label><br/>';
print '<select class="chosen-select" name="tariff"><option value="">Sélectionnez un tarif</option></select><br/><br/>';

print '<label>Numéroté</label><br/>';
print '<div class="btn-group btn-group-toggle" data-toggle="buttons">';
print '<label class="btn btn-primary active"><input value=0 type="radio" name="with_num" checked>Non</label>';
print '<label class="btn btn-primary">       <input value=1 type="radio" name="with_num">Oui</label>';
print '</div><br/><br/>';

print '<div id="div_num_start" style="display: none;">';
print '<label for="num_start">Numéro de début</label><br/>';
print '<input class="form-control bfh-number" name="num_start" value=1 step="1" type="number" min="1" style="width: 120px"/><br/>';
print '</div>';

print '<label for="number">Nombre de ticket</label><br/>';
print '<input class="form-control bfh-number" name="number" value=1 step="1" type="number" min="1" style="width: 120px"/><br/>';

print '<label>Format</label><br/>';
print '<div class="btn-group btn-group-toggle" data-toggle="buttons">';
print '<label class="btn btn-primary active"><input value="A4" type="radio" name="format" checked>A4</label>';
print '<label class="btn btn-primary">       <input value="A3" type="radio" name="format">A3</label>';
//print '<label class="btn btn-primary">       <input value="A2" type="radio" name="format">A2</label>';
print '</div><br/><br/>';


print '<div class="btn btn-primary" name="create">Créer</div><br/><br/>';

print '<a style="display: none; cursor: pointer;" class="btn btn-primary" name="download" href="' . URL_CHECK . '/img/multiple_print.pdf" download>Télécharger</a>';

print '<div id="alertSubmit"></div>';

print '</fieldset>';
print '</body>';


printFooter();
