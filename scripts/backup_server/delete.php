<?php

session_start();

print '<head>
    <link rel="stylesheet" type="text/css" href="styles.css">
    <title>Supprimmer sauvegarde</title>
</head>
    <div class="container">
    <div class="greyBorder">';

include_once 'param.inc.php';

$cnt_supression = 0;
if (isset($_POST['days_to_keep'])) {

    $days_to_keep = intVal($_POST['days_to_keep']);
    $files = glob('dump_daily/*.sql');
    $now = time();
//    $limit_date = 60 * 60 * 24 * $days_to_keep; // TODO
    $limit_date = $days_to_keep;

    
    foreach ($files as $file) {
        $val = $now - filectime($file);
        if (is_file($file)) {
            if ($now - filectime($file) >= $limit_date) {
                if (unlink(PATH . '/' . $file))
                    $cnt_supression++;
                else
                    print 'La suppression n\' pas pû avoir lieu<br/>';
            }
        }
    }
    if ($cnt_supression == 1)
        print "$cnt_supression sauvegarde a été supprimée.<br/>";
    else if ($cnt_supression > 1)
        print "$cnt_supression sauvegardes ont été été supprimées.<br/>";
    else
        print "Aucune sauvegardes n'a été supprimé";    
} else {
    print 'Le nombre de jours/semaines de sauvegarde à garder n\'est pas définit.';
}

print '<form action="' . URL_ROOT . '/index.php">';
print '<button type="submit">Retour accueil</button>';
print '</form>';
print '</div>';
print '</div>';
