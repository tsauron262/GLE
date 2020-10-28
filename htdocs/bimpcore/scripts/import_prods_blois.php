<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'IMPORT PRODS BLOIS', 0, 0, array(), array());

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

$dir = DOL_DOCUMENT_ROOT . '/bimpcore/scripts/docs/';
$file_name = 'import_prods_blois.csv';

if (!file_exists($dir . $file_name)) {
    echo BimpRender::renderAlerts('Le fichier "' . $dir . $file_name . '" n\'existe pas');
    ecit;
}

$rows = array();
$lines = file($dir . $file_name, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($lines as $line) {
    $rows[] = str_getcsv($line, ';');
}

//echo '<pre>';
//print_r($rows);
//exit;

if (!(int) BimPTools::getValue('exec', 0)) {
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

$keys = array(
//    'Code'         => 0,
    'label'        => 1,
//    '3LETTRES'     => 2,
    'ref'          => 3,
    'serialisable' => 4,
    'GAMME'        => 5,
    'CATEGORIE'    => 6,
    'COLLECTION'   => 7,
    'NATURE'       => 8,
    'FAMILLE'      => 9,
    'stock'        => 10,
//    'Emplacement stock' => 11,
    'pa_ht'        => 12,
//    'Total' => 13,
//    'Fabricant' => 14,
//    'Ref. fabricant' => 15,
    'fourn'        => 16,
    'ref_fourn'    => 17,
//    'Dépréciation' => 18,
    'ean'          => 19,
//    'Unité'        => 20,
//    'Poids net' => 21,
//    'Poids brut' => 22,
    'pu_ht'        => 23,
//    'Prix ttc' => 24,
//    'Code tva' => 25,
    'tva_tx'       => 26,
//    'Catégorie' => 27,
//    'Sous catégorie' => 28,
//    'Garantie' => 29,
//    'Nombre de colis' => 30
);


foreach ($rows as $r) {
    $prod = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_Product', array(
                'ref' => $r[$keys['ref']]
                    ), true);

    if (!BimpObject::objectLoaded($prod)) {
        $prod = BimpObject::getInstance('bimpcore', 'Bimp_Product');

        $prod->validateArray(array(
            'ref'   => $r[$keys['ref']],
            'label' => $r[$keys['label']],
            'price' => $r[$keys['pu_ht']],
            'ref'   => $r[$keys['ref']],
            'ref'   => $r[$keys['ref']],
            'ref'   => $r[$keys['ref']],
            'ref'   => $r[$keys['ref']],
            'ref'   => $r[$keys['ref']],
            'ref'   => $r[$keys['ref']],
            'ref'   => $r[$keys['ref']],
            'ref'   => $r[$keys['ref']],
            'ref'   => $r[$keys['ref']],
        ));
    } else {
        
    }
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();
