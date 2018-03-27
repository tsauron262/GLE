<?php
session_start();

print '<head>
    <link rel="stylesheet" type="text/css" href="styles.css">
    <title>Accueil sauvegarde</title>
</head>
    <div class="container">
        <div class="greyBorder">';

include_once 'param.inc.php';

$command = 'mysql --user=\'' . DB_USER . '\' --password=\'' . DB_PASSWORD . '\' --host=\'' . DB_HOST . '\' \'' . DB_NAME . '\'< \'' . PATH . '/' . $_POST['file'] . '\'';

exec($command, $errors, $ret_val);

if ($ret_val != 0) {
    print 'Une erreur est survenue, voici les détails :';
    print_r($errors);
} else {
    print '<strong>Sauvegarde restaurée</strong>';
}

print '<form action="' . URL_ROOT . '/index.php">';
print '<button type="submit">Retour accueil</button>';
print '</form>';
print '</div>';
print '</div>';
