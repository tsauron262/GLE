<?php

include_once 'param.inc.php';

print '<head>
    <link rel="stylesheet" type="text/css" href="styles.css">
    <title>Accueil sauvegarde</title>
</head>';

print '<div class="container" style="text-align: center">';
print '<form action="restore.php"  method="post">';
print '<div class="greyBorder">';

print "<h3>Restaurer une sauvegarde</h3>";
$files = glob('backups/*.sql');
usort($files, function($a, $b) {
    return filemtime($a) < filemtime($b);
});


foreach ($files as $ind => $file) {
    if ($ind == 0)
        print '<input id="' . $file . '" name="file" type="radio" value="' . $file . '" checked>';
    else
        print '<input id="' . $file . '" name="file" type="radio" value="' . $file . '">';
    print '<label for="' . $file . '">Sauvegarde du ' . date("d/m/Y G:i:s", filemtime($file)) . '</label ><br/><br/>';
}

print '<button style="width:200px" type="submit">Valider</button>';
print '</form>';
print '</div>';


// Create
print '<div class="greyBorder">';
print "<h3>Créer une sauvegarde</h3>";
print '<form action="create.php">';
print '<button style="width:200px" type="submit">Créer</button>';
print '</form>';
print '</div><br/>';


// Delete
print '<div class="greyBorder">';
print "<h3>Supprimer des sauvegardes</h3>";
print '<form action="delete.php" method="post">';
print '<label for="days_to_keep"><b>Garder les sauvegardes de moins de </b></label> ';
print '<input style="width:80px" type="number" name="days_to_keep" value=2 min=2 required>';
print '<label for="days_to_keep"><b> jour(s)</b></label><br/>';
print '<button style="width:200px" type="submit">Supprimer</button>';
print '</form>';
print '</div>';



// logout
print '<form action="logout.php">';
print '<button style="width:200px" type="submit">Se déconnecter</button>';
print '</form>';

print '</div>';
