<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'IMPORT PRODS', 0, 0, array(), array());

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

global $bdb, $keys, $fourns;

// ActiReference, Designation, EanCode, CodeFamille, LibFamille, Serialisable, Quantity, PriceVatOff, PriceVatOn, BuyingPriceVatOff, EcoTaxVatOff, CreatedAt, UpdatedAt, CodeDépot, LibDepot, Com1, Com2, Com3, Com4
//$products = BimpCache::getBimpObjectObjects('bimpcore', 'Bimp_Product', array(
//            'rowid' => array(
//                'operator' => '>',
//                'value'    => 4
//            )
//        ));
//
//if (count($products)) {
//    echo 'SUPPR DES PRODS';
//    $n = 0;
//    foreach ($products as $p) {
//        $err = $p->delete($w, true);
//
//        if (count($err)) {
//            echo BimpRender::renderAlerts(BimpTools::getMsgFromArray($err, '#' . $p->id));
//        } else {
//            $n++;
//        }
//    }
//
//    echo $n . ' prod(s) suppr';
//    exit;
//}
//
//die('no prods');

$keys = array(
    'ref'           => 0,
    'label'         => 1,
    'ean'           => 2,
    'code_famille'  => 3,
    'label_famille' => 4,
    'serialisable'  => 5,
    'qty'           => 6,
    'pu_ht'         => 7,
    'pu_ttc'        => 8,
    'pa_ht'         => 9,
    'eco_tax'       => 10,
    'date_create'   => 11,
    'date_update'   => 12,
    'code_stock'    => 13,
    'label_stock'   => 14,
    'com1'          => 15,
    'com2'          => 16,
    'com3'          => 17,
    'com4'          => 18
);

$fourns = array();

$bdb = new BimpDb($db);

$dir = DOL_DOCUMENT_ROOT . '/bimpcore/scripts/docs/';
$file_name = 'import_prods.csv';

if (!file_exists($dir . $file_name)) {
    echo BimpRender::renderAlerts('Le fichier "' . $dir . $file_name . '" n\'existe pas');
    exit;
}

$rows = array();
$lines = file($dir . $file_name, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($lines as $idx => $line) {
    if (!$idx) {
        continue;
    }

    $data = str_getcsv($line, ';');
    $row = array();

    foreach ($keys as $code => $i) {
        if ($data[$i] === 'NULL') {
            $data[$i] = null;
        }
        $row[$code] = $data[$i];
    }

    $rows[] = $row;
}

//echo '<pre>';
//print_r($rows);
//exit;

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

import($rows, $refs, BimpTools::getValue('test', 0));

function cleanPrice($price)
{
    $price = str_replace(' ', '', $price);
    $price = str_replace('€', '', $price);
    $price = str_replace(',', '.', $price);

    return (float) $price;
}

function import($rows, $refs)
{
    $failed = array();
    global $bdb;

    $familles = array();

    $code_move = 'IMPORT_CSV';
    $label_move = 'Import CSV';

    BimpObject::loadClass('bimpcore', 'BimpProductCurPa');

    $apple_keywords = array('apple', 'iphone', 'ipad', 'mac', 'ipod');
    $done = array();
    foreach ($rows as $r) {
        $is_apple = false;
        foreach ($apple_keywords as $kw) {
            if (strpos(strtolower($r['com2']), $kw) !== false) {
                $is_apple = true;
                break;
            }
        }
        continue;
        if ($r['code_famille'] == 'PIECESAV') {
            continue;
        }

        if (isset($done[$r['code_stock']][$r['ref']])) {
            continue;
        }

        if (!isset($done[$r['code_stock']])) {
            $done[$r['code_stock']] = array();
        }

        $done[$r['code_stock']][$r['ref']] = 1;

        echo '<br/><br/>';
        echo '<strong>' . $r['ref'] . '</strong>: ';
        $tva_tx = 20;

        $errors = array();
        $id_famille = 0;
        if (!isset($familles[$r['code_famille']])) {
            $id_famille = (int) $bdb->getValue('c_famille_produit', 'id', 'code = \'' . $r['code_famille'] . '\'');

            if ($id_famille) {
                $familles[$r['code_famille']] = $id_famille;
            } else {
                $id_famille = (int) $bdb->insert('c_famille_produit', array(
                            'code'  => $r['code_famille'],
                            'label' => $r['label_famille']
                                ), true);

                if (!$id_famille) {
                    echo '<span class="danger">';
                    echo '<br/>Echec de la création de la famille "' . $r['code_famille'] . '" - ' . $bdb->err() . '<br/>';
                    echo '</span>';
                } else {
                    $familles[$r['code_famille']] = $id_famille;
                }
            }
        } else {
            $id_famille = (int) $familles[$r['code_famille']];
        }

        $is_apple = false;
        foreach ($apple_keywords as $kw) {
            if (strpos(strtolower($r['com2']), $kw) !== false) {
                $is_apple = true;
                break;
            }
        }

        $ref = ($is_apple ? 'APP-' : 'INC-') . $r['ref'];

        $data = array(
            'ref'             => $ref,
            'label'           => $r['label'],
            'price'           => (float) $r['pu_ht'],
            'tva_tx'          => $tva_tx,
            'barcode'         => $r['ean'],
            'fk_barcode_type' => ($r['ean'] ? 2 : null),
            'id_famille'      => $id_famille,
            'deee'            => $r['eco_tax'],
            'validate'        => 1,
            'fabricant'       => ($is_apple ? 'APPLE' : ''),
            'serialisable'    => ((int) $r['serialisable'] === 1 ? 1 : 0),
            'datec'           => substr($r['date_create'], 0, 19),
            'import_actimac'  => 1
        );

        $filters = array();

        if ((string) $r['ean']) {
            $filters = array(
                'or_search' => array(
                    'or' => array(
                        'ref'     => array($ref, $r['ref']),
                        'barcode' => $r['ean']
                    )
            ));
        } else {
            $filters['ref'] = array($ref, $r['ref']);
        }

        $prod = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_Product', $filters, true);

        if (!BimpObject::objectLoaded($prod)) {
            // Créa du produit: 
            $prod = BimpObject::createBimpObject('bimpcore', 'Bimp_Product', $data, true, $errors);

            if (count($errors)) {
                echo BimpRender::renderAlerts($errors);
                $failed[] = $r['ref'];
            } else {
                echo '<span class="success">Créa OK (' . $prod->getLink() . ')</span>';
            }

            if (!count($errors) && BimpObject::objectLoaded($prod)) {
                // Créa PA courant: 
                BimpObject::createBimpObject('bimpcore', 'BimpProductCurPa', array(
                    'id_product' => $prod->id,
                    'amount'     => (float) $r['pa_ht']
                        ), true, $errors);

                if (!count($errors)) {
                    echo ' - <span class="success">PA courant OK</span>';
                }
            }
        } else {
            if ((int) $prod->getData('import_actimac')) {
                echo 'Déjà traité ' . $prod->getLink();
                continue;
            }
            $w = array();
            if ($r['code_famille'] == 'PIECESAV') {
                // suppr: 
                $errors = $prod->delete($w, true);
                if (!count($errors)) {
                    echo ' - <span class="success">Suppr prod part OK</span>';
                }
                continue;
            }

            // Maj du produit: 
            $errors = $prod->validateArray(array(
                'barcode'         => $r['ean'],
                'fk_barcode_type' => ($r['ean'] ? 2 : null),
                'id_famille'      => $id_famille,
                'fabricant'       => ($is_apple ? 'APPLE' : '')
            ));

            if (!count($errors)) {
                $errors = $prod->update($w, true);

                if (!count($errors)) {
                    echo ' - <span class="success">MAJ Prod OK</span>';
                }
            }
        }

        if (count($errors)) {
            echo BimpRender::renderAlerts($errors);
        } elseif (BimpObject::objectLoaded($prod)) {
            $id_entrepot = 0;
            $id_package = 0;

            switch ((int) $r['code_stock']) {
                case 29: //'CAMBRIDGE':
                    $id_entrepot = 10;
                    break;

                case 22: //'SAVM':
                    $id_entrepot = 1;
                    break;

                case 23: //'IDF':
                    $id_entrepot = 34;
                    break;

                case 32: //'Prêts Cambridge':
                    $id_package = 4;
                    break;

                case 24: //'Prêts SAVM':
                    $id_package = 1;
                    break;

                case 8: // Utilisation interne: 
                    $id_package = 7;
                    break;
            }

            // Maj des stocks. 
            if ((float) $r['qty']) {
                $stock_errors = array();

                if ((int) $prod->getData('serialisable')) {
                    for ($i = 1; $i <= (int) $r['qty']; $i++) {
                        $eq_errors = array();
                        $serial = 'NS_INC_' . $r['ref'] . '_' . $i;
                        $eq = BimpObject::createBimpObject('bimpequipment', 'Equipment', array(
                                    'serial'     => $serial,
                                    'id_product' => $prod->id
                                        ), true, $eq_errors);

                        if (BimpObject::objectLoaded($eq)) {
                            if ($id_entrepot) {
                                echo 'AJ nb ' . $serial . ' Entr. #' . $id_entrepot;
                                BimpObject::createBimpObject('bimpequipment', 'BE_Place', array(
                                    'id_equipment' => $eq->id,
                                    'type'         => 2,
                                    'id_entrepot'  => $id_entrepot,
                                    'code_mvt'     => 'IMPORT_CSV',
                                    'infos'        => 'Import CSV',
                                    'date'         => date('Y-m-d H:i:s')
                                        ), true, $eq_errors);
                            } elseif ($id_package) {
                                echo 'AJ nb ' . $serial . ' Pack. #' . $id_package;
                                $package = BimpCache::getBimpObjectInstance('bimpequipment', 'BE_Package', $id_package);
                                if (BimpObject::objectLoaded($package)) {
                                    $eq_errors = $package->addEquipment($eq->id, 'IMPORT_CSV', 'Import CSV');
                                } else {
                                    $stock_errors[] = 'PACK #' . $id_package . ' absent';
                                }
                            }
                        }

                        if (count($eq_errors)) {
                            $stock_errors[] = BimpTools::getMsgFromArray($eq_errors, 'Eq ' . $i);
                        }
                    }
                } else {
                    if ($id_entrepot) {
                        echo ' - AJ Entr. #' . $id_entrepot;
                        $stock_errors = $prod->correctStocks($id_entrepot, (float) $r['qty'], 0, $code_move, $label_move);
                    } elseif ($id_package) {
                        echo ' - AJ Pack. #' . $id_package;
                        $package = BimpCache::getBimpObjectInstance('bimpequipment', 'BE_Package', $id_package);
                        if (BimpObject::objectLoaded($package)) {
                            $warnings = array();
                            $stock_errors = $package->addProduct($prod->id, (float) $r['qty'], -1, $warnings, 'IMPORT_CSV', 'Import CSV');
                        } else {
                            $stock_errors[] = 'PACK #' . $id_package . ' absent';
                        }
                    }
                }

                if (count($stock_errors)) {
                    echo BimpRender::renderAlerts($stock_errors);
                } else {
                    echo ' - <span class="success">Stocks OK</span>';
                }
            }
        }

//        break;
    }

    echo '<br/><br/> NON CREES: <br/><br/>';
    foreach ($failed as $ref) {
        echo $ref . '<br/>';
    }
}

function validateNoPa($refs)
{
    global $bdb;
    BimpObject::loadClass('bimpcore', 'BimpProductCurPa');

    foreach ($refs as $ref) {
        $errors = array();

        echo '<br/>' . $ref . ': ';
        $prod = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_Product', array(
                    'ref' => $ref
                        ), true);

        if (BimpObject::objectLoaded($prod)) {
            $pa_ok = false;
            $curPa = BimpProductCurPa::getProductCurPa($prod->id);

            if (!BimpObject::objectLoaded($curPa)) {
                BimpObject::createBimpObject('bimpcore', 'BimpProductCurPa', array(
                    'id_product' => $prod->id,
                    'amount'     => 0
                        ), true, $errors);

                if (count($errors)) {
                    echo BimpRender::renderAlerts($errors);
                } else {
                    echo '<span class="success">PA courant OK</span>';
                    $pa_ok = true;
                }
            } else {
                echo '<span class="info">PA courant existe</span>';
                $pa_ok = true;
            }

            if ($pa_ok) {
                if ($bdb->update('product_extrafields', array(
                            'validate' => 1
                                ), 'fk_object = ' . $prod->id) <= 0) {
                    echo BimpRender::renderAlerts('Echec validation - ' . $bdb->err(), 'warning');
                } else {
                    echo ' - <span class="success">Validation OK</span>';
                }
            }
        } else {
            echo '<span class="danger">Pas de prod</span>';
        }
    }
}
echo '<br/>FIN';

echo '</body></html>';

//llxFooter();
