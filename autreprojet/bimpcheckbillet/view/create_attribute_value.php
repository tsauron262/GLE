<?php

include_once '../param.inc.php';

include_once 'header.php';
include_once 'footer.php';


$arrayofjs = array('../js/create_attribute_value.js');

printHeader('Créer valeur attribut', $arrayofjs);

print '<body>';

print '<fieldset class="container_form">';

print '<legend><span>Créer valeur attribut<span></legend>';

print '<label for="attribute_parent">Attribut parent </label><br/>';
print '<select class="chosen-select" name="attribute_parent"><option value="">Sélectionnez un attribut parent</option></select><br/><br/>';

print '<label for="label">Libellé </label>';
print '<input class="form-control" placeholder="Libellé" name="label" maxlength=256 style="width: 300px"><br/>';

print '<button class="btn btn-primary" name="create">Créer</button>';

print '<div id="alertSubmit"></div>';
print '</fieldset>';

print '</body>';

printFooter();
