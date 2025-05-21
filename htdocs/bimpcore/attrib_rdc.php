<?php

require_once("../main.inc.php");

require_once __DIR__ . '/Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'INSTALL RDC', 0, 0, array(), array());

echo '<body style="padding: 30px">';

BimpCore::displayHeaderFiles();

global $db, $user;
$bdd = new BimpDb($db);

if (!BimpObject::objectLoaded($user)) {
	echo BimpRender::renderAlerts('Aucun utilisateur connecté');
	exit;
}

if (!$user->admin) {
	echo BimpRender::renderAlerts('Seuls les admin peuvent exécuter ce script');
	exit;
}

ini_set('display_errors', 1);
$errors = array();

$listId = "23861, 24656, 23918, 24140, 26438, 19484, 24182, 23996, 18995, 23870, 19214, 19646, 19868, 23726, 24038, 24173, 18920, 23798, 19277, 23654, 19274, 23642, 24191, 23564, 23927, 23849, 23846, 19271, 21422, 23873, 19505, 24587, 23630, 23594, 23525, 18917, 23600, 24167, 19070, 23696, 19466, 24419, 23777, 23756, 19193, 23876, 24101, 24071, 19388, 19739, 26003, 23933, 24653, 19235, 24314, 24146, 19568, 23774, 24170, 23561, 23936, 23765, 23639, 26624, 24854, 19055, 24155, 23948, 19049, 23930, 24176, 24143, 24569, 23912, 19220, 24221, 24002, 24152, 23717, 24026, 19658, 24179, 19343, 19778, 22052, 23954, 24074, 23906, 24188, 23588, 24134, 23567, 19478, 19481, 19898, 23636, 24053, 24203, 18983, 24461, 24185";
$errors = $bdd->update('societe', array('fk_user_attr_rdc' => 8), 'rowid IN (' . $listId . ')');

echo 'Erreurs : <pre>' . print_r($errors, 1) . '</pre>';

echo '<br/>FIN';
echo '</body></html>';
