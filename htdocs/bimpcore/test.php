<?php

require_once("../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'TESTS', 0, 0, array(), array());

echo '<body>';

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

$id_client = 23;
$where = 'obj_module = \'bimpcore\' AND obj_name = \'Bimp_Client\' AND id_obj ' . $id_client;
$where .= ' AND content LIKE \'%' . $bdb->db->escape('L\'encours ICBA pour ce client n\'est valable que jusqu\'au') . '%\'';

$where .= ' AND date_create < \'2023-02-21 00:00:00\'';

if ($bdb->delete('bimpcore_note', $where) <= 0) {
    echo 'FAIL - ' . $bdb->err();
} else {
    echo 'ok';
}

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
