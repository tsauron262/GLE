<?php

include_once 'header.php';
include_once 'footer.php';

$arrayofjs = array('../js/login.js', '../js/annexes.js');

session_destroy();

printHeader('Billetterie', $arrayofjs);

print '<body>';


print '<fieldset class="container_form">';

print '<legend><span>Connection<span></legend>';

if (IS_MAIN_SERVER)
    print '<input id="url_after_login" value="home.php" style="display: none"/>';
else
    print '<input id="url_after_login" value="check_ticket.php" style="display: none"/>';

print '<label for="login">Identifiant</label>';
print '<input class="form-control" placeholder="Identifiant" name="login" maxlength=256 style="width: 300px"><br/>';

print '<label for="pass_word">Mot de passe </label>';
print '<input type="password" class="form-control" placeholder="Mot de passe" name="pass_word" maxlength=256 style="width: 300px"><br/>';

print '<button class="btn btn-primary" name="connect" style="margin-right: 40px">Se connecter</button>';
print '<input type="button" class="btn btn-primary" value="S\'inscrire" onClick="document.location.href=\'register.php\'"/>';
print '<div id="alertSubmit"></div>';
print '</fieldset>';
print '</body>';


printFooter();