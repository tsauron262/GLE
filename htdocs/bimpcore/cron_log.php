<?php

require_once("../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'CRON LOG', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db, $user;

if (!BimpObject::objectLoaded($user)) {
    echo BimpRender::renderAlerts('Aucun utilisateur connecté');
    exit;
}

if (!$user->admin) {
    echo BimpRender::renderAlerts('Seuls les admin peuvent exécuter ce script');
    exit;
}

$file = DOL_DATA_ROOT . '/bimpcore/cron_logs.txt';
if (file_exists($file)) {
    if ((int) BimpTools::getValue('delete_file', 0)) {
        unlink($file);
        if (file_exists($file)) {
            echo '<span class="danger">Echec suppr</span>';
        } else {
            echo '<span class="success">Suppr ok</span>';
        }
    } else {
        echo '<a class="btn btn-default" href="' . $_SERVER['PHP_SELF'] . '?delete_file=1">';
        echo 'Suppimer fichier';
        echo '</a>&nbsp;&nbsp;';

        $url = DOL_URL_ROOT . '/document.php?modulepart=bimpcore&file=cron_logs.txt';
        echo '<span class="btn btn-default" onclick="window.open(\'' . $url . '\')">';
        echo 'Télécharger';
        echo '</span><br/><br/>';

        echo file_get_contents($file);
    }
} else {
    echo 'Fichier absent';
}

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
