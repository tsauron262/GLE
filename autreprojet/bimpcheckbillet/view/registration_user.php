<?php

include_once '../param.inc.php';

include_once 'header.php';
include_once 'footer.php';

$arrayofjs = array('../js/registration_user.js', '../js/annexes.js');

printHeader('Inscrire utilisateur', $arrayofjs);

print '<body>';

print '<h4>Inscrire utilisateur</h4>';

print '<label for="first_name">Prénom </label>';
print '<input name="first_name" maxlength=256><br/>';

print '<label for="last_name">Nom </label>';
print '<input name="last_name" maxlength=256><br/>';

print '<label for="email">Email </label>';
print '<input name="email" maxlength=256><br/>';

print '<label for="date_born">Date de naissance </label>';
print '<input type="text" name="date_born" style="width: 100px"><br/>';

print '<button name="create">S\'inscrire</button>';
print '<div id="alertSubmit"></div>';

print '</body>';

printFooter();