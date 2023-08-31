<?php

require_once("../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'TESTS', 0, 0, array(), array());

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

if (BimpCore::isModuleActive('bimpvalidateorder')) {
    echo 'bimpvalidateorder actif.<br/>';
} else {
    echo 'bimpvalidateorder inactf.<br/>';
}

echo '<br/>';

if (BimpCore::isModuleActive('bimpvalidation')) {
    echo 'bimpvalidation actif.<br/>';
} else {
    echo 'bimpvalidation inactf.<br/>';
}

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
