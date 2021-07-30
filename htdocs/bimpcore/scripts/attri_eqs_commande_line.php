<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'Attributions equipements', 0, 0, array(), array());

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

echo 'Script désactivé';
exit;

$id_line_fourn = 0;
$id_line_cli = 0;
$id_reception = 0;

$bdb = new BimpDb($db);
$line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFournLine', $id_line_fourn);

if (BimpObject::objectLoaded($line)) {
    $receptions = $line->getData('receptions');
    
    $eqs = array();
    
    foreach ($receptions[$id_reception]['equipments'] as $id_eq => $eq_data) {
        $eqs[] = $id_eq;
    }
    
    $commLine = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', $id_line_cli);
    $err = $commLine->addEquipments($eqs);
    
    echo 'Erreurs: <pre>';
    print_r($err);
    echo '</pre>';
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();
