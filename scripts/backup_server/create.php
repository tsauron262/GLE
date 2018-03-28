<?php

print '<head>
    <link rel="stylesheet" type="text/css" href="styles.css">
    <title>Créer sauvegarde</title>
</head>
    <div class="container">
    <div class="greyBorder">';

include_once 'param.inc.php';

/**
 * Function
 */

/**
 * @param type $file_name with path and extension
 */
function createBackup($file_name) {

    $command = 'mysqldump --user=\'' . DB_USER . '\' --password=\'' . DB_PASSWORD . '\' ' . '--host=\'' . DB_HOST . '\' \'' . DB_NAME . '\'> \'' . $file_name . '\'';

    exec($command, $errors, $ret_val);

    if ($ret_val != 0) {
        print 'Une erreur est survenue, voici les détails :';
        print_r($errors);
        return -1;
    } else {
        if (strpos($file_name, 'monthly') != false)
            print '<strong>Sauvegarde bimensuelle créee.</strong>';
        else if (strpos($file_name, 'daily') != false)
            print '<strong>Sauvegarde temporaire créee</strong>';
        else
            print '<strong>Sauvegarde créee, mais erreur de chemin</strong>';
        return 1;
    }
}

function createDaily($now) {
    if (createBackup(PATH . '/dump_daily/backup-' . $now . '.sql') == 1)
        return 1;
    else
        return -2;
}

/**
 * @param type $now date today
 * @return  1 if a backup as been created
 *         -1 if not created
 *         -2 if error
 */
function createMonthly($now) {

    $currrent_day = date('d', $now);
    $currrent_month = date('m', $now);

    $files_m = glob('dump_monthly/*.sql');
    usort($files_m, function($a, $b) {
        return filectime($a) < filectime($b);
    });

    $time_gap = $now - filectime($files_m[0]);
    $time_15_day = 30; //60 * 60 * 24 * 15;

    if ($time_gap > $time_15_day) {
        if (createBackup(PATH . '/dump_monthly/backup-' . $now . '.sql') == 1)
            return 1;
        else
            return -2;
    } else
        return -1;
}

/**
 * Main
 */
$now = time();
$date = new DateTime();

if (createMonthly($now) == -1) // not created but no error
    createDaily($now);


print '<form action="' . URL_ROOT . '/index.php">';
print '<button type="submit">Retour accueil</button>';
print '</form>';
print '</div>';
print '</div>';
