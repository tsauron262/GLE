<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'MAJ CSV', 0, 0, array(), array());

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

echo 'Désactivé';
exit;

global $bdb, $keys;

$keys = array(
    'ref'      => 0,
    'old_code' => 1,
    'new_code' => 2
);

$bdb = new BimpDb($db);

$dir = DOL_DOCUMENT_ROOT . '/bimpcore/scripts/docs/';
$file_name = 'maj_codes_clients.csv';

if (!file_exists($dir . $file_name)) {
    echo BimpRender::renderAlerts('Le fichier "' . $dir . $file_name . '" n\'existe pas');
    exit;
}

$rows = array();
$lines = file($dir . $file_name, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($lines as $idx => $line) {
    $data = str_getcsv($line, ';');
    $row = array();

    foreach ($keys as $code => $i) {
        if ($data[$i] == 'NULL') {
            $row[$code] = '';
            continue;
        }
        $row[$code] = $data[$i];
    }

    $rows[] = $row;
}

//echo '<pre>';
//print_r($rows);
//echo '</pre>';

if (!(int) BimpTools::getValue('exec', 0)) {
    if (is_array($rows) && count($rows)) {
        echo count($rows) . ' élément(s) à traiter <br/><br/>';

        $path = pathinfo(__FILE__);
        echo ' <a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?exec=1" class="btn btn-default">';
        echo 'Exécuter';
        echo '</a>';
        exit;
    }

    echo BimpRender::renderAlerts('Aucun élément à traiter', 'info');
    exit;
}

$nOk = 0;
$nFails = 0;

foreach ($rows as $r) {
    if ($bdb->update('societe', array(
                'code_compta' => $r['new_code']
                    ), 'code_client = \'' . $r['ref'] . '\' AND code_compta = \'' . $r['old_code'] . '\'') <= 0) {
        echo 'ECHEC insertion - ' . $r['ref'] . ' - ' . $bdb->err() . '<br/>';
        $nFails++;
    } else {
        $nOk++;
    }
}

echo '<br/>';
echo $nOk . 'OK<br/>';
echo $nFails . ' échecs <br/>';
echo '<br/>FIN';

echo '</body></html>';

//llxFooter();
