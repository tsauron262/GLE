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
    'gamme'        => 5,
    'categorie'    => 6,
    'collection'   => 7,
    'nature'       => 8,
    'famille'      => 9,
    'stock'        => 10,
//    'Emplacement stock' => 11,
    'pa_ht'        => 12,
//    'Total' => 13,
//    'Fabricant' => 14,
//    'Ref. fabricant' => 15,
    'fourn'        => 16,
    'ref_fourn'    => 17,
//    'Dépréciation' => 18,
    'barcode'      => 19,
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

$_POST['is_cur_pa'] = 1;

$categories = BimpCache::getProductsTagsByTypeArray('categorie', false, 'label');
$gammes = BimpCache::getProductsTagsByTypeArray('gamme', false, 'label');
$collections = BimpCache::getProductsTagsByTypeArray('collection', false, 'label');
$natures = BimpCache::getProductsTagsByTypeArray('nature', false, 'label');
$familles = BimpCache::getProductsTagsByTypeArray('famille', false, 'label');

foreach ($rows as $row) {
    $r = array();
    foreach ($keys as $key => $idx) {
        $r[$key] = $row[$idx];
    }

    echo $r['ref'] . ': ';

    $errors = array();
    $prod = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_Product', array(
                'ref' => $r['ref']
                    ), true);

    if (!BimpObject::objectLoaded($prod)) {
        // Créa du produit: 
        $prod = BimpObject::createBimpObject('bimpcore', 'Bimp_Product', array(
                    'ref'          => $r['ref'],
                    'label'        => $r['label'],
                    'price'        => (float) str_replace(',', '.', $r['pu_ht']),
                    'tva_tx'       => (float) str_replace(',', '.', $r['tva_tx']),
                    'serialisable' => ($r['serialisable'] == 'NUFARTSTKSERIE' ? 1 : 0),
                    'barcode'      => $r['barcode'],
                    'collection'   => (isset($collections[$r['collection']]) ? $collections[$r['collection']] : 0),
                    'nature'       => (isset($natures[$r['nature']]) ? $natures[$r['nature']] : 0),
                    'famille'      => (isset($familles[$r['famille']]) ? $familles[$r['famille']] : 0),
                    'gamme'        => (isset($gammes[$r['gamme']]) ? $gammes[$r['gamme']] : 0),
                    'categorie'    => (isset($categories[$r['categorie']]) ? $categories[$r['categorie']] : 0)
                        ), true, $errors, $warnings);

        if (!count($errors)) {
            echo '<span class="success">Créa OK</span>';

            // Créa prix fourn
            $id_fourn = 0;

            $pfp = BimpObject::createBimpObject('bimpcore', 'Bimp_ProductFournisseurPrice', array(
                        'fk_product' => (int) $prod->id,
                        'fk_soc'     => $id_fourn,
                        'ref_fourn'  => $r['ref_fourn'],
                        'price'      => str_replace(',', '.', $r['pa_ht']),
                        'tva_tx'     => str_replace(',', '.', $r['tva_tx'])
                            ), true, $errors, $warnings);

            if (!count($errors)) {
                echo ' - <span class="success">Prix Fourn OK</span>';
            }
        }
    } else {
        // Vérif prix fourn:    
    }

    // Maj des stocks / équipements. 
    if (BimpObject::objectLoaded($prod)) {
        
    }

    if (count($errors)) {
        echo BimpRender::renderAlerts($errors);
    }

    if (count($warnings)) {
        echo BimpRender::renderAlerts($warnings, 'warning');
    }

    echo '<br/>';
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();
