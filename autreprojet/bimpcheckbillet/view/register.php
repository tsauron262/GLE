<?php

include_once 'header.php';
include_once 'footer.php';

$arrayofjs = array('../js/register.js', '../js/annexes.js');

printHeader('Billetterie', $arrayofjs);

print '<body>';

print '<fieldset class="container_form">';

print '<legend><span>S\'inscrire<span></legend>';

print '<label for="first_name">Prénom</label>';
print '<input class="form-control" placeholder="Prénom" name="first_name" maxlength=256 style="width: 300px"><br/>';

print '<label for="last_name">Nom</label>';
print '<input class="form-control" placeholder="Nom" name="last_name" maxlength=256 style="width: 300px"><br/>';

print '<label for="email">Email</label>';
print '<input class="form-control" placeholder="Email" name="email" maxlength=256 style="width: 300px"><br/>';

print '<label for="login">Login</label>';
print '<input class="form-control" placeholder="Login" name="login" maxlength=256 style="width: 300px"><br/>';

print '<label for="pass_word">Mot de passe </label>';
print '<input type="password" class="form-control" placeholder="Mot de passe" name="pass_word" maxlength=256 style="width: 300px"><br/>';

print '<label for="conf_pass_word">Confirmation mot de passe </label>';
print '<input type="password" class="form-control" placeholder="Confirmation mot de passe" name="conf_pass_word" maxlength=256 style="width: 300px"><br/>';

print '<button class="btn btn-primary" name="register"  style="margin-right: 40px">S\'inscrire</button>';
print '<input type="button" class="btn btn-primary" value="Se connecter" onClick="document.location.href=\'index.php\'"/>';
print '</fieldset>';
print '<div id="alertSubmit"></div>';
print '</body>';

printFooter();