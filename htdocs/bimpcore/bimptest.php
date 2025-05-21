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

if (!BimpObject::objectLoaded($user)) {
	echo BimpRender::renderAlerts('Aucun utilisateur connecté');
	exit;
}

if (!$user->admin) {
	echo BimpRender::renderAlerts('Seuls les admin peuvent exécuter ce script');
	exit;
}

$bdb = BimpCache::getBdb(true);

$rows = $bdb->getRows('ticket', 'rowid', 'message');

foreach ($rows as $r) {
	/* @var Bimp_Ticket $ticket */

	$ticket->useNoTransactionsDb();

	$new_txt = BimpTools::cleanHtml($r['message']);

	if ($new_txt != $r['message']) {
		echo 'UP ' . $r['rowid'] . ' : ';

		if ($bdb->update('ticket', array(
			'message' => $new_txt
		), 'rowid = ' . $ticket->id) <= 0) {
			echo 'FAIL - ' . $bdb->err();
		} else {
			echo 'OK';
		}
		echo '<br/>';
	}
}

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
