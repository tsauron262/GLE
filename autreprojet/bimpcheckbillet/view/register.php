<?php

include_once '../param.inc.php';

include_once 'header.php';
include_once 'footer.php';

$arrayofjs = array('../js/register.js', '../js/annexes.js');

printHeader('Billetterie', $arrayofjs);

print '<body>';

print '<fieldset class="container_form">';

print '<legend><span>S\'inscrire<span></legend>';

print '<label for="login">Login</label>';
print '<input class="form-control" placeholder="Login" name="login" maxlength=256 style="width: 300px"><br/>';

print '<label for="password">Mot de passe </label>';
print '<input type="password" class="form-control" placeholder="Mot de passe" name="password" maxlength=256 style="width: 300px"><br/>';

print '<label for="conf_password">Confirmation mot de passe </label>';
print '<input type="password" class="form-control" placeholder="Confirmation mot de passe" name="conf_password" maxlength=256 style="width: 300px"><br/>';

print '<button class="btn btn-primary" name="register">S\'inscrire</button>';
print '</fieldset>';
print '<div id="alertSubmit"></div>';
print '</body>';


printFooter();