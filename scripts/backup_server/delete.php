<?php

print '<head>
    <link rel="stylesheet" type="text/css" href="styles.css">
    <title>Accueil sauvegarde</title>
</head>
    <div class="container">
';

include_once 'param.inc.php';

session_start();

if (isset($_POST['nb_to_keep'])) {

    $nb_to_keep = intVal($_POST['nb_to_keep']);

    usort($files, function($a, $b) {
        return filemtime($a) < filemtime($b);
    });

    foreach ($files as $ind => $file) {
        if ($ind < $nb_to_keep - 1)
            continue;

        $command = 'rm ' . $file;
        exec($command, $errors, $ret_val);
        if ($ret_val != 0) {
            print 'Une erreur est survenue, la suppression n\'a pas été faite entièrement voici les détails :';
            print_r($errors);
            break;
        }
    }
} else {
    'Le nombre de sauvegardes à gardé n\'est pas définit';
}

print '<form action="' . URL_ROOT . '/manage_backup.php">';
print '<button type="submit">Retour accueil</button>';
print '</form>';
print '</div>';
