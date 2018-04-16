<?php

include_once '../param.inc.php';

include_once 'header.php';
include_once 'footer.php';

$arrayofjs = array('../js/create_event.js');

printHeader('Créer évènement', $arrayofjs);

print '<body>';

print '<fieldset class="container_form">';

print '<legend><span>Créer évènement<span></legend>';

print '<form id="create_form" action="../interface.php" method="post" enctype="multipart/form-data" >';

print '<input name="action" value="create_event" style="display: none;"/>';

print '<label for="label">Libellé </label>';
print '<input class="form-control" placeholder="Libellé" name="label" maxlength=256 style="width: 300px"><br/>';

print '<label for="date_start">Date de début</label>';
print '<input class="form-control" placeholder="Date de début" type="text" name="date_start" style="width: 160px"><br/>';

print '<label for="date_end">Date de fin</label>';
print '<input class="form-control" placeholder="Date de fin" type="text" name="date_end" style="width: 160px"><br/>';

print '<label>Image</label><br/>';

print '<input id="file" type="file" name="file"><br><br>';

//print '<label class="btn btn-primary" for="img_event">';
//print '<input type="file" id="img_event" style="display:none"/>Parcourir</label>';
//print '<span class="label label-info" id="name_file_display"></span>';
//print '<img id="img_display" src="#" alt=" Aucune image sélectionnée"/><br/><br/><br/>';

print '<button class="btn btn-primary" name="create">Créer évènement</button>';
print '</form>';
print '</fieldset>';
print '<div id="alertSubmit"></div>';
print '</body>';

printFooter();