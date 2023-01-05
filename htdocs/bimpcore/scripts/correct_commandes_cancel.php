<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

top_htmlhead('', 'MAJ PA AVOIRS', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db;
$bdb = new BimpDb($db);

$commandes = BimpCache::getBimpObjectList('bimpcommercial', 'Bimp_Commande', array(
            'fk_statut' => -1
        ));

foreach ($commandes as $id_commande) {
    $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $id_commande);

    $warnings = array();
    $errors = $commande->cancel($warnings);

    if (count($errors)) {
        echo BimpRender::renderAlerts($errors);
    } else {
        echo '<span class="success">';
        echo 'Commande ' . $id_commande . ' OK';
        echo '</span><br/>';
    }

    if (count($warnings)) {
        echo BimpRender::renderAlerts($warnings, 'warning');
    }
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();

