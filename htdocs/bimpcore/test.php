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

$ac = BimpObject::getInstance('bimpcore', 'Bimp_ActionComm');
$title = 'Ajout d\\\'un événement';
$values = array(
    'fields' => array(
        'datep'          => '2023-03-31 15:00:00',
        'datep2'         => '2023-03-31 16:00:00',
        'users_assigned' => array(270)
    )
);
$onclick = $ac->getJsLoadModalForm('add', $title, $values) . '</script>';

echo $html .'<br/>';

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
