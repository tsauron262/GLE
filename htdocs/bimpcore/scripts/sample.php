<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'TITRE', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db, $user;

if (!BimpObject::objectLoaded($user)) {
    echo BimpRender::renderAlerts('Aucun utilisateur connecté');
    exit;
}

if (!$user->admin) {
    echo BimpRender::renderAlerts('Seuls les admin peuvent exécuter ce script');
}

$bdb = new BimpDb($db);
$where = '';
$rows = $bdb->getRows('table', $where, null, 'array', null, 'rowid', 'desc');

if (!(int) BimPTools::getValue('exec', 0)) {
    echo 'Desc <br/>';

    if (is_array($rows) && count($rows)) {
        echo count($rows) . ' élément(s) à traiter <br/><br/>';

        $path = pathinfo(__FILE__);
        echo ' <a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?exec=1&test=1" class="btn btn-default">';
        echo 'Test';
        echo '</a>';
        echo ' <a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?exec=1&test_one=1" class="btn btn-default">';
        echo 'Executer une entrée';
        echo '</a>';
        echo '<a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?exec=1" class="btn btn-default">';
        echo 'Tout éxécuter';
        echo '</a>';
    }

    echo BimpRender::renderAlerts('Aucun élément à traiter', 'info');
    exit;
}

$test = (int) BimpTools::getValue('test', 0);
$test_one = (int) BimpTools::getValue('test_one', 0);

foreach ($rowd as $r) {
    if ($test_one) {
        break;
    }
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();