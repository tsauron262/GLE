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

$rows = $bdb->getRows('product', '', null, 'array', array('rowid', 'ref'));

if (is_array($rows)) {
    foreach ($rows as $r) {
        if (preg_match('/^(.+)_A$/', $r['ref'], $matches)) {
            $ref = $matches[1] . '/A';
            if ($bdb->update('product', array(
                        'ref' => $ref
                            ), 'rowid = ' . $r['rowid']) <= 0) {
                echo 'FAIL : ' . $ref . '<br/>';
            } else {
                echo 'OK ' . $ref . '<br/>';
            }
        }
    }
}


echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
