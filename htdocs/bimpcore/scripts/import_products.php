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

$maj_comm_fourn = false;

$dir = DOL_DATA_ROOT . '/bimpcore/imports/' . date('Y-m-d') . '/';

$actions = array(
    'import_apple_products'  => 'Import produits Apple',
    'import_apple_prices'    => 'Import prix Apple',
    'import_td_prices'       => 'Import prix TechData',
    'import_ingram_prices'   => 'Import prix Ingram',
    'validate_apple_producs' => 'Valider les produits Apple importés',
    'import_ldlc_products'   => 'Import produits LDLC'
);

global $action;
$action = BimpTools::getValue('action', '');

if (!$action) {
    $path = pathinfo(__FILE__);

    foreach ($actions as $code => $label) {
        echo '<div style="margin-bottom: 10px">';
        echo '<a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?action=' . $code . '" class="btn btn-default">';
        echo $label . BimpRender::renderIcon('fas_arrow-circle-right', 'iconRight');
        echo '</a>';
        echo '</div>';
    }

    exit;
}

switch ($action) {
    case 'import_apple_products':
        importAppleProducts($dir . 'products.txt');
        break;

    case 'import_apple_prices':
        importFournPrices($dir . 'pa_apple.txt', 261968, $maj_comm_fourn);
        break;

    case 'import_td_prices':
        importFournPrices($dir . 'pa_td.txt', 229890, $maj_comm_fourn);
        break;

    case 'import_ingram_prices':
        importFournPrices($dir . 'pa_ingram.txt', 230496, $maj_comm_fourn);
        break;

    case 'validate_apple_producs':
        validateProducts($dir . 'products.txt', 0, $bdb);
        break;

    case 'import_ldlc_products':
        importLdlcProducts();
        break;

    default:
        echo 'Action invalide';
        break;
}

function importAppleProducts($file)
{
    if (!file_exists($file)) {
        echo BimpRender::renderAlerts('Le fichier "' . $file . '" n\'existe pas');
        return;
    }

    $rows = file($file, FILE_IGNORE_NEW_LINES);

    if (!count($rows)) {
        echo BimpRender::renderAlerts('Aucune ligne à traiter');
        return;
    }

    if (!(int) BimpTools::getValue('exec', 0)) {
        global $action;
        $path = pathinfo(__FILE__);

        echo '<div style="margin-bottom: 30px">';
        echo '<a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?action=' . $action . '&exec=1" class="btn btn-default">';
        echo 'Exécuter' . BimpRender::renderIcon('fas_arrow-circle-right', 'iconRight');
        echo '</a>';
        echo '</div>';

        echo '<pre>';
        print_r($rows);
        echo '</pre>';

        return;
    }


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
        21 => 'crt'
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
                    case 'crt':
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

function importFournPrices($file, $id_fourn, $maj_comm_fourn = false)
{
    if ($maj_comm_fourn) {
        $_POST['update_comm_fourn'] = 1;
    }

    if (!file_exists($file)) {
        echo BimpRender::renderAlerts('Le fichier "' . $file . '" n\'existe pas');
        return;
    }

    $rows = file($file, FILE_IGNORE_NEW_LINES);

    if (!count($rows)) {
        echo BimpRender::renderAlerts('Aucune ligne à traiter');
        return;
    }

    if (!(int) BimpTools::getValue('exec', 0)) {
        global $action;
        $path = pathinfo(__FILE__);

        echo '<div style="margin-bottom: 30px">';
        echo '<a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?action=' . $action . '&exec=1" class="btn btn-default">';
        echo 'Exécuter' . BimpRender::renderIcon('fas_arrow-circle-right', 'iconRight');
        echo '</a>';
        echo '</div>';

        echo '<pre>';
        print_r($rows);
        echo '</pre>';

        return;
    }

    $keys = array(
        'id_fourn'    => 0,
        'ref_fourn'   => 1,
        'price'       => 5,
        'ref_product' => 7,
        'ean'         => 14
    );
    foreach ($rows as $r) {
        $data = explode("\t", $r);

//        if ($data[$keys['ref_product']] !== 'APP-MRT32FN/A') { // POURT TESTS
//            continue;
//        }
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

        BimpCache::$cache = array();
    }
}

function importLdlcProducts()
{
    $dir = '/data/importldlc/';
    $file = date('Ymd') . '_catalog_ldlc_to_bimp.csv';

    if (!file_exists($dir . $file)) {
        $file = '';
        if (file_exists($dir) && is_dir($dir)) {
            $files = scandir($dir);
            arsort($files);

            foreach ($files as $f) {
                if (preg_match('/^[0-9]{8}_catalog_ldlc_to_bimp\.csv$/', $f)) {
                    $file = $f;
                    break;
                }
            }
        } else {
            echo BimpRender::renderAlerts('Dossier "' . $dir . '" absent');
        }
    }
    if (!$file) {
        echo BimpRender::renderAlerts('Aucun fichier trouvé dans le dossier "' . $dir . '"');
        return;
    }

    echo 'Fichier: ' . $file . '<br/><br/>';


    $rows = file($dir . $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if (empty($rows)) {
        echo BimpRender::renderAlerts('Aucune ligne trouvée');
        return;
    }

    if (!(int) BimpTools::getValue('exec', 0)) {
        global $action;
        $path = pathinfo(__FILE__);

        echo '<div style="margin-bottom: 30px">';
        echo '<a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?action=' . $action . '&exec=1" class="btn btn-default">';
        echo 'Exécuter' . BimpRender::renderIcon('fas_arrow-circle-right', 'iconRight');
        echo '</a>';
        echo '</div>';

        echo '<pre>';
        print_r($rows);
        echo '</pre>';

        return;
    }

    $keys = array(
        'ref'  => 0,
        'code' => 1
    );

    $bdb = BimpCache::getBdb();

    $result = $bdb->getRows('product', '1', null, 'array', array('ref', 'rowid'));
    $refs_prods = array();

    if (!is_null($result)) {
        foreach ($result as $res) {
            $ref = $res['ref'];

            if (preg_match('/^[A-Z]{3}\-(.+)$/', $ref, $matches)) {
                $ref = $matches[1];
            }

            if ($ref) {
                $refs_prods[$ref] = (int) $res['rowid'];
            }
        }
    }

    foreach ($rows as $idx => $r) {
        if (!$idx) {
            continue;
        }

        $data = explode(';', $r);

        $ref = (isset($data[$keys['ref']]) ? $data[$keys['ref']] : '');

        if ($ref && array_key_exists($ref, $refs_prods)) {
            echo 'Ref prod trouvée: ' . $ref . ' - PROD #' . $refs_prods[$ref] . '<br/>';
            echo '<pre>';
            print_r($r);
            echo '</pre>';
        }
    }
}
echo '<br/>FIN';

echo '</body></html>';

//llxFooter();