<?php

session_start();

print '<head>
    <link rel="stylesheet" type="text/css" href="styles.css">
    <title>Accueil sauvegarde</title>
</head>
    <div class="container">
        <div class="greyBorder">';

include_once 'param.inc.php';

/**
 * Function
 */
function executeCommand($command, $error_message) {
    exec($command, $errors, $ret);
    if ($ret != 0) {
        print $error_message . ", voici les détails :";
        print_r($errors);
        return -1;
    }
    return 1;
}

function restoreDatabase($prod_database, $backup_database, $file_to_restore) {

    // Delete back-up database
    $command = 'mysql ' . DB_OPT . ' -e "DROP DATABASE IF EXISTS ' . $backup_database . '"';
    $error_message = 'Une erreur est survenue lors de la suppression de la base de donnée "' . $backup_database . '"';
    if (executeCommand($command, $error_message) < 0)
        return -1;

    // Create back-up database
    $command = 'mysqladmin --user=\'' . DB_USER . '\' --password=\'' . DB_PASSWORD . '\' create ' . $backup_database;
    $error_message = 'Une erreur est survenue lors de la suppression de la base de donnée "' . $backup_database . '"';
    if (executeCommand($command, $error_message) < 0)
        return -2;

    // Transfer prod database to back-up database
    $command = 'mysqldump ' . DB_OPT . ' ' . $prod_database . ' | mysql ' . DB_OPT . ' ' . $backup_database;
    $error_message = 'Une erreur est survenue lors de la copie de la base de donnée "' . $prod_database . '" vers la base de donnée "' . $backup_database . '"';
    if (executeCommand($command, $error_message) < 0)
        return -3;

    // Delete prod database
    $command = 'mysql ' . DB_OPT . ' -e "DROP DATABASE IF EXISTS ' . $prod_database . '"';
    $error_message = 'Une erreur est survenue lors de la suppression de la base de donnée "' . $prod_database . '"';
    if (executeCommand($command, $error_message) < 0)
        return -4;

    // Create prod database if
    $command = 'mysql ' . DB_OPT . ' -e "CREATE DATABASE ' . $prod_database . '"';
    $error_message = 'Une erreur est survenue lors de la création de la base de donnée "' . $prod_database . '"';
    if (executeCommand($command, $error_message) < 0)
        return -5;

    // Retrieve data from the selected file
    $command = 'mysql ' . DB_OPT . ' --database=\'' . $prod_database . '\' < \'' . $file_to_restore . '\'';
    $error_message = 'Une erreur est survenue lors de la récupération du dump de la base de donnée  "' . $prod_database . '" depuis le fichier  "' . $file_to_restore . '"';
    if (executeCommand($command, $error_message) < 0) {
        // Retrieve data from the backup database to prod database
        $command = 'mysqldump ' . DB_OPT . ' ' . $backup_database . ' | mysql ' . DB_OPT . ' ' . $prod_database;
        $error_message = 'Une erreur est survenue lors de la copie de la base de donnée "' . $backup_database . '" vers la base de donnée "' . $prod_database . '"';
        if (executeCommand($command, $error_message) < 0)
            return -6;
        else
            print 'La base de donnée n\'a pas pu être restaurée, mais la base de donné de production n\'a pas été endommagé';
        return -7;
    }

    print '<strong>Sauvegarde restaurée</strong>';
    return 1;
}

restoreDatabase(DB_NAME, DB_NAME . '_backup', $_POST['file']);

/**
 * Main
 */
print '<form action="' . URL_ROOT . '/index.php">';
print '<button type="submit">Retour accueil</button>';
print '</form>';
print '</div>';
print '</div>';
