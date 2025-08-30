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

$rows = $bdb->getRows('bws_user', '1', null, 'array', array('id', 'token', 'token_expire'));

foreach ($rows as $r) {
	echo 'Aj token user #' . $r['id'] . ' - ';
	if ($bdb->insert('bws_user_token', array(
			'id_ws_user'   => $r['id'],
			'token'        => $r['token'],
			'token_expire' => $r['token_expire']
		)) <= 0) {
		echo 'FAIL - ' . $bdb->err();
	}
	echo 'OK <br/>';
}

echo '<br/><br/>';

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
