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
    exit;
}

$bdb = new BimpDb($db);

$tables = $bdb->executeS('SHOW TABLES', 'array');

echo 'Pour vérif: (pas d\'éxécution)<br/>';
echo 'Liste des tables<pre>';
print_r($tables);
echo '</pre>';
exit;

foreach ($tables as $table) {
    echo 'Table "' . $table . '": ';
    $result = $bdb->executeS('SELECT `AUTO_INCREMENT` as ai FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = \'' . $dolibarr_main_db_name . '\' AND TABLE_NAME = \'' . $table . '\'', 'array');

    if (isset($result[0]['ai'])) {
        $ai = (int) $result[0]['ai'];

        $new_ai = $ai + max(array(1000, ceil($ai / 100)));

        if ($bdb->execute('ALTER TABLE `' . $table . '` AUTO_INCREMENT=' . $new_ai) <= 0) {
            echo '<span class="danger">Echec màj AI - '.$bdb->err().'</span>';
        } else {
            echo '<span class="success">OK</span>';
        }
    } else {
        echo '<span class="danger">Echec récup AI - ' . $bdb->err() . '</span>';
    }
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();
