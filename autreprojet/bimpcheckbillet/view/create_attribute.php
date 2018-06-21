<?php

include_once '../param.inc.php';
include_once '../class/attribute.class.php';

include_once 'header.php';
include_once 'footer.php';


$arrayofjs = array('../js/create_attribute.js');

printHeader('Créer attribut', $arrayofjs);

print '<body>';

print '<fieldset class="container_form">';

print '<legend><span>Créer attribut<span></legend>';

print '<label for="label">Libellé </label>';
print '<input class="form-control" placeholder="Libellé" name="label" maxlength=256 style="width: 300px"><br/>';

print '<label for="type">Type </label><br/>';
print '<select class="chosen-select" name="type">';
print '<option value="">Sélectionnez un type</option>';
print '<option value=' . Attribute::TYPE_LIST . '>Liste de sélection</option>';
print '<option value=' . Attribute::TYPE_RADIO . '>Bouton radio</option>';
print '<option value=' . Attribute::TYPE_COLOR . '>Couleur</option>';
print '</select><br/><br/>';

print '<button class="btn btn-primary" name="create">Créer</button>';

print '<div id="alertSubmit"></div>';
print '</fieldset>';

print '</body>';

printFooter();
