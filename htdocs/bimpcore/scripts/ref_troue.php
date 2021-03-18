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

//$tables = $bdb->executeS('SHOW TABLES', 'array');

//echo 'Pour vérif: (pas d\'éxécution)<br/>';
//echo 'Liste des tables<pre>';
//print_r($tables);
//echo '</pre>';
//exit;

$tables = array(
    array("facture", 'FAS', 'fac_number'),
     array("commande", "CO2102-"),
     array("propal", "CO2102-")
    
    );

foreach ($tables as $table) {
    echo 'Table "' . $table . '": ';
    
    if(!isset($table[2]))
        $table[2] = 'ref';
    
    $result = $bdb->executeS('SELECT * FROM '.MAIN_DB_PREFIX.$table[0].' WHERE '.$table[2].' = '.$table[1]);
    
    if($result){
        echo '<span class="success">OK '.$table[0].'</span>';
    } else {
        echo '<span class="danger">Echec récup AI - ' . $bdb->err() . '</span>';
    }
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();
