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
$files = glob('dump_*/*.sql');
usort($files, function($a, $b) {
    return filectime($a) < filectime($b);
});


foreach ($files as $ind => $file) {
    if (strpos($file, 'monthly') !== false)
        $class= 'day';
    else
        $class = '';

    if ($ind == 0)
        print '<input id="' . $file . '" name="file" type="radio" value="' . $file . '" checked>';
    else
        print '<input id="' . $file . '" name="file" type="radio" value="' . $file . '">';
    print '<label class="' . $class . '" for="' . $file . '">Sauvegarde du ' . date("d/m/Y G:i:s", filectime($file)) . '</label ><br/><br/>';
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

print '<input id="delete1" name="days_to_keep" type="radio" value=2 checked>';
print '<label for="delete1">2 jours</label>';

print '<input id="delete7" name="days_to_keep" type="radio" value=7>';
print '<label for="delete7">1 semaine</label>';

print '<input id="delete14" name="days_to_keep" type="radio" value=14>';
print '<label for="delete14">2 semaines</label>';

print '<br/><br/>';

print '<button style="width:200px" type="submit">Supprimer</button>';
print '</form>';
print '</div>';


// logout
print '<form action="logout.php">';
print '<button style="width:200px" type="submit">Se déconnecter</button>';
print '</form>';

print '</div>';
