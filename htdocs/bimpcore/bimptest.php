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

$num = '06 37 00 08 40';
$infos = '';
echo 'TEST dur : ';
if (!BimpTools::isValidNumMobile($num)) {
	echo 'KO - ' . $infos;
} else {
	echo 'OK';
}
echo '<br/>';

/** @var Bimp_Contact $contact */
$contact = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', 202486);
if (BimpObject::objectLoaded($contact)) {

	echo 'TEST contact ' . $contact->getLink() .'<br/>';

	$infos = '';
	$num = $contact->dol_object->phone_mobile;
	echo 'TEST mobile (' . $num . ') : ';
	if (!BimpTools::isValidNumMobile($num)) {
		echo 'KO - ' . $infos;
	} else {
		echo 'OK';
	}
	echo '<br/>';

	$infos = '';
	$num = $contact->dol_object->phone_pro;
	echo 'TEST pro (' . $num . ') : ';
	if (!BimpTools::isValidNumMobile($num)) {
		echo 'KO - ' . $infos;
	} else {
		echo 'OK';
	}
	echo '<br/>';

	$infos = '';
	$num = $contact->dol_object->phone_perso;
	echo 'TEST perso (' . $num . ') : ';
	if (!BimpTools::isValidNumMobile($num)) {
		echo 'KO - ' . $infos;
	} else {
		echo 'OK';
	}
	echo '<br/>';
} else {
	echo 'KO - Contact non chargé';
}

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
