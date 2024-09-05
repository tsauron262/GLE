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

foreach (array() as $old => $new) {
    echo '<br/>Up : ' . $old . ' => ' . $new . ' : ';
    if ($bdb->update('be_equipment', array(
                'serial' => $new
                    ), 'serial = \'' . $old . '\'') <= 0) {
        echo 'FAIL - ' . $bdb->err();
    } else {
        echo 'OK';
    }
}

$rib = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_SocBankAccount', 289);

$rib->printData();

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
