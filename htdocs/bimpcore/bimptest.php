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

$pull_infos_file = DOL_DOCUMENT_ROOT . '/bimpressources/pull_infos.json';

if (!file_exists(DOL_DOCUMENT_ROOT.'/bimpressources')) {
	echo 'BIMPRESSOURCES ABSENT<br/>';
} else {
	echo 'BIMPRESSOURCES PRESENT<br/>';
}

//if (file_exists($pull_infos_file)) {
//	echo 'DEL PULL INFOS FILE<br/>';
//	unlink($pull_infos_file);
//}

//echo '<br/>SIMULATION D\'UN PULL<br/><br/>';
//$pull_idx = 0;
//
//$pull_info = json_decode(file_get_contents($pull_infos_file), true);
//if (isset($pull_info['idx'])) {
//	$pull_idx = (int) $pull_info['idx'];
//} else {
//	echo 'PAS DE PULL INFOS<br/>';
//}
//
//$pull_idx++;
//echo '<br/>Pull idx : ' . $pull_idx . '<br/>';
//
//$pull_info = array(
//	'idx'   => $pull_idx,
//	'start' => date('Y-m-d H:i:s'),
//	'end'   => date('Y-m-d H:i:s')
//);
//
//echo 'NEW PULL INFOS<pre>' . print_r($pull_info, 1) . '</pre>';
//if (file_put_contents($pull_infos_file, json_encode($pull_info))) {
//	echo 'OK';
//} else {
//	echo 'FAIL';
//}

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
