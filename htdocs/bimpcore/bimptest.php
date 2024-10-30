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

foreach (BimpCache::getBimpObjectObjects('bimpcore', 'Bimp_Product', array(
    'ref' => array(
        'part_type' => 'end',
        'part'      => '_A'
    )
)) as $p) {
    $ref = $p->getRef();

    if (preg_match('/^(.+)_A$/', $ref, $matches)) {
        $ref = $matches[1] . '/A';
        if ($bdb->update('product', array(), 'rowid = ' . $p->id) <= 0) {
            echo 'FAIL : ' . $ref . '<br/>';
        } else {
            echo 'OK ' . $ref . '<br/>';
        }
    }
}

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
