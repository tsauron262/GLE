<?php

function printHeader($title, $arrayofjs = array(), $arrayofcss = array()) {
print '<!DOCTYPE html>';
print '<html>';

print '<head>';
print '<title>' . $title . '</title>';
// CSS
print '<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">';
print '<link rel="stylesheet" href="../css/menu.css">';
foreach ($arrayofcss as $cssfile)
    print '<link rel="stylesheet" type="text/css" href="' . $cssfile . '">';

// JS
print '<script src="https://code.jquery.com/jquery-3.3.1.min.js"
    integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
    crossorigin="anonymous"></script>';
print '<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>';
foreach ($arrayofjs as $jsfile)
    print '<script type="text/javascript" src="' . $jsfile . '"></script>';
print '</head>';

print '
<div class="container">
   <div class="menubar">
      <ul>
         <li class="product">
            <a href="#">Utilisateur<i class="fa fa-angle-down" aria-hidden="true"></i></a>
            <ul class="submenu1">
               <li><a href="registration_user.php">S\'inscrire</a></li>
            </ul>
         </li>
         <li class="service">
            <a href="#">Evènement<i class="fa fa-angle-down" aria-hidden="true"></i></a>
            <ul class="submenu2">
               <li><a href="create_event.php">Créer</a></li>
               <li><a href="create_tariff.php">Créer tarif</a></li>
               <li><a href="create_ticket.php">Réserver ticket</a></li>
               <li><a href="check_ticket.php">Valider ticket</a></li>
            </ul>
         </li>
      </ul>
   </div>
</div>

';
}
