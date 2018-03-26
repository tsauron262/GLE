<?php

include_once 'param.inc.php';
session_start();

print '<head>
    <link rel="stylesheet" type="text/css" href="styles.css">
    <title>Accueil sauvegarde</title>
</head>';

if (($_POST['login'] != LOGIN OR $_POST['pw'] != PASSWORD) and ( $_SESSION['login'] != LOGIN OR $_SESSION['pw'] != PASSWORD)) {
    print '<div class="container" style="text-align: center">';
    print "<strong>Login ou mot de passe incorrect.</strong><br/><br/>";
    print '<form action="index.html">';
    print '<button style="width:200px" type="submit">Revenir à la page d\'authentification</button>';
    print '</form>';
    print '</div>';
    return;
}


$_SESSION['login'] = $_POST['login'];
$_SESSION['pw'] = $_POST['pw'];

print $_SESSION['login'];
print $_SESSION['pw'];

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

// logout
print '<div class="greyBorder">';
print "<h3>Supprimer les sauvegardes superflues</h3>";
print '<form action="delete.php" method="post">';
print '<label for="nb_to_keep"><b>Nombre de sauvegarde à garder</b></label> : ';
print '<input style="width:80px" type="number" name="nb_to_keep" value=3 min=3 required><br/>';
print '<button style="width:200px" type="submit">Supprimer</button>';
print '</form>';
print '</div>';



// logout
print '<form action="index.html">';
print '<button style="width:200px" type="submit">Se déconnecter</button>';
print '</form>';

print '</div>';
