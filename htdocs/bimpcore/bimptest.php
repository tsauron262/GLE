<?php

global $no_erp_updates;
$no_erp_updates = true;

require_once("../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'TESTS RAPIDES', 0, 0, array(), array());

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

if (file_exists(DOL_DATA_ROOT . '/bimpcore/fatal_errors_logs.json')) {
	echo 'LOGS<pre>' . print_r(json_decode(file_get_contents(DOL_DATA_ROOT . '/bimpcore/fatal_errors_logs.json'), 1), 1) . '</pre>';
} else {
	echo 'FICHIER KO';
}

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
