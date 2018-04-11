<?php

include_once '../param.inc.php';

include_once 'header.php';
include_once 'footer.php';

$arrayofjs = array('../js/registration_user.js', '../js/annexes.js');

printHeader('Inscrire utilisateur', $arrayofjs);

print '<body>';

print '<fieldset class="container_form">';

print '<legend><span>Inscrire utilisateur<span></legend>';

print '<label for="first_name">Prénom </label>';
print '<input class="form-control" placeholder="Prénom" name="first_name" maxlength=256 style="width: 300px"><br/>';

print '<label for="last_name">Nom </label>';
print '<input class="form-control" placeholder="Nom" name="last_name" maxlength=256 style="width: 300px"><br/>';

print '<label for="email">Email </label>';
print '<input class="form-control" placeholder="Email" name="email" maxlength=256 style="width: 400px" type="email"><br/>';

print '<label for="date_born">Date de naissance </label>';
print '<input class="form-control" placeholder="Date de naissance" type="text" name="date_born" style="width: 160px"><br/>';

print '<button class="btn btn-primary" name="create">S\'inscrire</button>';
print '</fieldset>';
print '<div id="alertSubmit"></div>';
print '</body>';

printFooter();