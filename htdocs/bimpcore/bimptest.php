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

$client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', 142370);
foreach (array(
	'f.martinez@bimp.fr'
//			 'test-n0sf2zo7p@srv1.mail-tester.com',
//			 'grunchy99@gmail.com'
		 ) as $to) {
	echo '<br/>TEST ' . $to . ' : ';
	$mail = new BimpMail($client, 'TEST', $to, '', 'TEST');

	$errors = array();
	$mail->send($errors);

	if (count($errors)) {
		echo '<pre>' . print_r($errors) . '</pre>';
	} else {
		echo 'OK';
	}
}

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
