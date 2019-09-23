<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

top_htmlhead('', 'CHECK COMMANDES', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db;
$bdb = new BimpDb($db);

$commandes = BimpCache::getBimpObjectObjects('bimpcommercial', 'Bimp_Commande', array(
            'fk_statut'         => array(
                'in' => array(1, 2, 3)
            ),
            'logistique_status' => array(
                'operator' => '!=',
                'value'    => 6
            )
        ));

foreach ($commandes as $commande) {
    if (!BimpObject::objectLoaded($commande)) {
        continue;
    }

    $commande->checkStatus();
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();

