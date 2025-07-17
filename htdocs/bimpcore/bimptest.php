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

global $dolibarr_main_url_root;

echo '$dolibarr_main_url_root : ' . $dolibarr_main_url_root . '<br/>';
echo 'DOL_URL_ROOT : ' . DOL_URL_ROOT . '<br/>';
echo '$_SERVER[\'SERVER_NAME\'] . DOL_URL_ROOT : ' . $_SERVER['SERVER_NAME'] . DOL_URL_ROOT . '<br/>';

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
