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

echo 'HOLA!';
//$i = (int) BimpCache::getCacheServeur('test_cache_server_count');
//
//echo 'COUNT : ' . $i;
//
//if ((int) BimpTools::getValue('delete', 0, 'int')) {
//	BimpCache::unsetCacheServeur('test_cache_server_count');
//	echo '<br/>Cache serveur supprimé';
//} else {
//	BimpCache::setCacheServeur('test_cache_server_count', $i + 1);
//	echo '<br/>Cache serveur incrémenté';
//}

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
