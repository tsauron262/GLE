<?php

require_once("../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'TESTS', 0, 0, array(), array());

echo '<body style="padding: 30px">';

BimpCore::displayHeaderFiles();

global $db, $user;
$bdb = new BimpDb($db);

if (!BimpObject::objectLoaded($user)) {
    echo BimpRender::renderAlerts('Aucun utilisateur connecté');
    exit;
}

if (!$user->admin) {
    echo BimpRender::renderAlerts('Seuls les admin peuvent exécuter ce script');
    exit;
}

$dir = DOL_DATA_ROOT . '/bimpcore/apple_csv/2024/';

foreach (scandir($dir) as $f) {
    if (in_array($f, array('.', '..'))) {
        continue;
    }

    echo '<br/>';
    echo '<a href="' . DOL_URL_ROOT . 'document.php?modulepart=' . urlencode($dir) . '&file=' . urlencode($f) . '" target="_blank">' . $f . '</a>';
}

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
