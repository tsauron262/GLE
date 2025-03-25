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

//echo 'Envoi : <br/>';

///** @var Bimp_Commande $obj */
////$obj = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', 104540);
//$errors = BimpUserMsg::envoiMsg('code_erp', 'TEST MSG', 'Test test');
//
//echo '<pre>' . print_r($errors, 1) . '</pre>';

$html = '<b>AC+for For Headphones AirPods Pro</b><br/>Ref BR: 8197083734<br/>Ref CF : CFB2301-28889<br/>Montant initial HT : 39,00 EUR';

if (preg_match('/^.+Ref CF : (CF[^<]+).*$/', $html, $matches)) {
	echo 'OK';
	echo '<pre>' . print_r($matches, 1) . '</pre>';
} else {
	echo 'KO';
}

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
