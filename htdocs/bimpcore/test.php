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

//$date = '2023-01-15 01:15:30';
//$tms = strtotime($date);
//
//echo 'Date : ' . $date . '<br/>';
//echo 'tms : ' . $tms . '<br/>';
//
//$dt = new DateTime($date);
//$dt2 = new DateTime((string) $tms);
//
//echo 'From date ' . $dt->format('Y-m-d H:i:s') . '<br/>';
//echo 'From TMS : ' . $dt2->format('Y-m-d H:i:s');

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
