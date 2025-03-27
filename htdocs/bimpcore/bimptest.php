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

define('MOD_DEV_SYN_MAIL', 'f.martinez@bimp.fr');

echo 'Envoi : <br/>';
//$obj = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', 104540);

$centres = BimpCache::getCentresData();
$centres['AB']['mail'] = '';
$errors = BimpUserMsg::envoiMsg('sav_online_by_client', 'TEST MSG', 'Test test', $centres['AB']);
echo 'Err<pre>' . print_r($errors, 1) . '</pre>';

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
