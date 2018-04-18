<?php

include_once 'header.php';
include_once 'footer.php';

$arrayofjs = array('../js/manage_user.js');
$arrayofcss = array('../css/checkboxes.css');

printHeader('Gestion utilisateur', $arrayofjs, $arrayofcss);


print '<body>';
print '<fieldset class="container_form">';
print '<legend><span>Gestion utilisateur<span></legend>';

print '<label for="event">Utilisateur </label><br/>';

print '<select class="chosen-select" name="user"><option value="">Sélectionnez utilisateur</option></select><br/><br/>';

print '<div id="alertSubmit"></div><br/><br/>';


// Identification
print '<fieldset class="sub_container_form">';
print '<legend><span>Identification<span></legend>';

print '<label for="login">Login </label>';
print '<input class="form-control" placeholder="Login" name="login" maxlength=256 style="width: 300px" disabled><br/>';

print '<label for="pass_word">Mot de passe </label>';
print '<input type="password" class="form-control" placeholder="Mot de passe" name="pass_word" maxlength=256 style="width: 300px" disabled><br/>';

print '<button class="btn btn-primary" name="modify">Modifier </button>';

print '</fieldset>';

// Events
print '<fieldset id="container_event" class="sub_container_form">';
print '<legend><span>Evènement<span></legend>';
print '<label>Accès évènements admin</label>';

print '</fieldset>';

print '</fieldset>';
print '</body>';
