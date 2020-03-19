<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'IMPORT PRODUCTS', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db;
$bdb = new BimpDb($db);

$dir = DOL_DATA_ROOT . '/bimpcore/imports/' . date('Y-m-d') . '/';

//importProducts($dir . 'products.txt');
//importFournPrices($dir . 'pa_apple.txt', 261968);
//importFournPrices($dir . 'pa_td.txt', 229890);
//importFournPrices($dir . 'pa_ingram.txt', 230496);

//validateProducts($dir . 'products.txt', 0, $bdb);

function importProducts($file)
{
    $rows = file($file, FILE_IGNORE_NEW_LINES);

    $keys = array(
        0  => 'ref',
        1  => 'label',
        3  => 'barcode',
        4  => 'serialisable',
        5  => 'price',
        7  => 'tva_tx',
        8  => 'price_ttc',
        9  => 'gamme',
        10 => 'categorie',
        11 => 'collection',
        12 => 'nature',
        13 => 'famille',
        15 => 'deee',
        16 => 'cto',
        17 => 'cur_pa_ht',
        22 => 'crt'
    );

    $categories = BimpCache::getProductsTagsByTypeArray('categorie', false);
    $collections = BimpCache::getProductsTagsByTypeArray('collection', false);
    $natures = BimpCache::getProductsTagsByTypeArray('nature', false);
    $familles = BimpCache::getProductsTagsByTypeArray('famille', false);
    $gammes = BimpCache::getProductsTagsByTypeArray('gamme', false);

//    $instance = BimpObject::getInstance('bimpcore', 'Bimp_Product');
//    $categories = $instance->getValues8sens('categorie');
//    $collections = $instance->getValues8sens('collection');
//    $natures = $instance->getValues8sens('nature');
//    $familles = $instance->getValues8sens('famille');
//    $gammes = $instance->getValues8sens('gamme');

    $refs_done = array();
    $i = 0;
    foreach ($rows as $r) {
        $i++;
        $data = explode("\t", $r);

        $values = array();
        foreach ($keys as $key => $field) {
            if (isset($data[$key]) && (string) $data[$key]) {
                $value = $data[$key];

                switch ($field) {
                    case 'serialisable':
                        if ($value === 'NUFARTSTKSERIE') {
                            $value = 1;
                        } else {
                            $value = 0;
                        }
                        break;

                    case 'price':
                    case 'tav_tx':
                    case 'price_ttc':
                    case 'ecotaxe':
                    case 'cur_pa':
                    case 'remise_crt':
                        $value = (float) str_replace(',', '.', $value);
                        break;

                    case 'gamme':
                        $value = 0;
                        foreach ($gammes as $id => $label) {
                            if ((string) $data[$key] == $label) {
                                $value = $id;
                                break 2;
                            }
                        }
                        if (!$value) {
                            echo BimpRender::renderAlerts('La gamme "' . $data[$key] . '" n\'existe pas');
                        }
                        break;

                    case 'categorie':
                        $value = 0;
                        foreach ($categories as $id => $label) {
                            if ((string) $data[$key] == $label) {
                                $value = $id;
                                break 2;
                            }
                        }
                        if (!$value) {
                            echo BimpRender::renderAlerts('La catégorie "' . $data[$key] . '" n\'existe pas');
                        }
                        break;

                    case 'collection':
                        $value = 0;
                        foreach ($collections as $id => $label) {
                            if ((string) $data[$key] == $label) {
                                $value = $id;
                                break 2;
                            }
                        }
                        if (!$value) {
                            echo BimpRender::renderAlerts('La collection "' . $data[$key] . '" n\'existe pas');
                        }
                        break;

                    case 'nature':
                        $value = 0;
                        foreach ($natures as $id => $label) {
                            if ((string) $data[$key] == $label) {
                                $value = $id;
                                break 2;
                            }
                        }
                        if (!$value) {
                            echo BimpRender::renderAlerts('La nature "' . $data[$key] . '" n\'existe pas');
                        }
                        break;

                    case 'famille':
                        $value = 0;
                        foreach ($familles as $id => $label) {
                            if ((string) $data[$key] == $label) {
                                $value = $id;
                                break 2;
                            }
                        }
                        if (!$value) {
                            echo BimpRender::renderAlerts('La famille "' . $data[$key] . '" n\'existe pas');
                        }
                        break;
                }
                $values[$field] = $value;
            }
        }

        $values['fk_product_type'] = 0;

        if (in_array($values['ref'], $refs_done)) {
            echo 'Ref déjà traitée: ' . $values['ref'] . '<br/>';
            continue;
        } else {
            $product = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_Product', array(
                        'ref' => $values['ref']
            ));
            if (BimpObject::objectLoaded($product)) {
                echo 'La ref ' . $values['ref'] . ' existe déjà <br/>';
                continue;
            } else {
                $refs_done[] = $values['ref'];
            }
        }

        $product = BimpObject::getInstance('bimpcore', 'Bimp_Product');
        $errors = $product->validateArray($values);
        if (!count($errors)) {
            $errors = $product->create($warnings, true);
        }

        if (count($errors)) {
            echo BimpRender::renderAlerts(BimpTools::getMsgFromArray($errors, 'Ligne ' . $i . ' - ' . $values['ref']));
        }
    }
}

function validateProducts($file, $ref_key, BimpDb $bdb)
{
    $rows = file($file, FILE_IGNORE_NEW_LINES);
    $refs = array();

    foreach ($rows as $r) {
        $data = explode("\t", $r);
        if (isset($data[$ref_key]) && (string) $data[$ref_key]) {
            $refs[] = "'" . $data[$ref_key] . "'";
        }
    }

    $ids = array();
    $result = $bdb->getRows('product', '`ref` IN (' . implode(',', $refs) . ')');
    foreach ($result as $res) {
        $ids[] = (int) $res->rowid;
    }

    if (!empty($ids)) {
        if ($bdb->update('product_extrafields', array(
                    'validate' => 1
                        ), 'fk_object IN (' . implode(',', $ids) . ')') <= 0) {
            echo $bdb->db->lasterror() . '<br/>';
        } else {
            echo 'OK';
        }
    } else {
        echo $bdb->db->lasterror() . '<br/>';
    }
}

function importFournPrices($file, $id_fourn)
{
    $rows = file($file, FILE_IGNORE_NEW_LINES);

//    echo '<pre>';
//    print_r($rows);
//    exit;

    $keys = array(
        'id_fourn'    => 0,
        'ref_fourn'   => 1,
        'price'       => 5,
        'ref_product' => 7,
        'ean'         => 14
    );
    foreach ($rows as $r) {
        $data = explode("\t", $r);

//        echo '<pre>';
//        print_r($data);
//        echo '</pre>';
//        
//        continue;

        $product = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_Product', array(
                    'ref' => $data[$keys['ref_product']]
        ));

        if (!BimpObject::objectLoaded($product)) {
            echo BimpRender::renderAlerts('Aucun produit trouvé pour la référence "' . $data[$keys['ref_product']] . '"');
            continue;
        } else {
            echo $data[$keys['ref_product']] . ': ' . $product->id . '<br/>';
        }

        $pfp = BimpObject::getInstance('bimpcore', 'Bimp_ProductFournisseurPrice');
        $errors = $pfp->validateArray(array(
            'fk_product' => (int) $product->id,
            'fk_soc'     => $id_fourn,
            'ref_fourn'  => $data[$keys['ref_fourn']],
            'price'      => (float) str_replace(',', '.', $data[$keys['price']]),
            'tva_tx'     => (float) $product->getData('tva_tx')
        ));

        if (!count($errors)) {
            $errors = $pfp->create($warnings, true);
        }

        if (count($errors)) {
            echo BimpRender::renderAlerts(BimpTools::getMsgFromArray($errors, 'Produit "' . $data[$keys['ref_product']] . '"'));
        }
    }
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();