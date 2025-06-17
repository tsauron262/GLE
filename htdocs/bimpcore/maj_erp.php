<?php

global $no_erp_updates;
$no_erp_updates = true;

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

$pull_infos_file = DOL_DOCUMENT_ROOT . '/bimpressources/pull_infos.json';
$pull_info = json_decode(file_get_contents($pull_infos_file), true);

echo 'INFOS PULL : <pre>' . print_r($pull_info, 1) . '</pre>';

if (!empty($pull_info)) {
	if (!$pull_info['end']) {
		echo '<br/>Forçage fin de pull : ';
		$pull_info['end'] = date('Y-m-d H:i:s');
		if (file_put_contents($pull_infos_file, json_encode($pull_info))) {
			echo 'OK';
		} else {
			echo 'FAIL';
		}
		echo '<br/><br/>';
	}
}

$no_erp_updates = false;
BimpCore::checkErpUpdates(true);

echo '</body></html>';

//llxFooter();
