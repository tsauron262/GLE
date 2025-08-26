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

if (is_dir(DOL_DOCUMENT_ROOT . '/bimpressources')) {
	$hfile = fopen(DOL_DOCUMENT_ROOT . '/bimpressources/injections_log.txt', 'a');
	if ($hfile) {
		fwrite($hfile, '------------------------------------' . "\n" . date('d / m / Y H:i') . "TEST\n\n");
		fclose($hfile);

		echo 'AJ OK';
	} else {
		echo 'FAIL';
	}
} else {
	echo 'NO DIR';
}

echo '<br/><br/>';

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
