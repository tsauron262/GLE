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

$products = BimpCache::getBimpObjectObjects('bimpcore', 'Bimp_Product', array(
            'rowid' => array(
                'operator' => '>',
                'value'    => 4
            )
        ));

if (count($products)) {
    echo 'SUPPR DES PRODS';
    $n = 0;
    foreach ($products as $p) {
        $err = $p->delete($w, true);

        if (count($err)) {
            echo BimpRender::renderAlerts(BimpTools::getMsgFromArray($err, '#' . $p->id));
        } else {
            $n++;
        }
    }

    echo $n . ' prod(s) suppr';
    exit;
}

die('no prods');

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
        $row[$code] = $data[$i];
    }

    $rows[] = $row;
}

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
    $entrepots = array();

    $code_move = 'IMPORT_CSV';
    $label_move = 'Import CSV';

    BimpObject::loadClass('bimpcore', 'BimpProductCurPa');

    $apple_keywords = array('apple', 'iphone', 'ipad', 'mac', 'ipod');
    $done = array();
    foreach ($rows as $r) {
        if ($r['code_stock'] == 'SAVM') {
            $r['code_stock'] = 'SAV2MV';
        }
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
        $id_entrepot = 0;
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
            if (strpos($r['com2'], $kw) !== false) {
                $is_apple = true;
                break;
            }
        }

        $ref = ($is_apple ? 'APP-' : 'INC-') . $r['ref'];

        $data = array(
            'ref'          => $r['ref'],
            'label'        => $r['label'],
            'price'        => (float) $r['pu_ht'],
            'tva_tx'       => $tva_tx,
            'barcode'      => $r['ean'],
            'id_famille'   => $id_famille,
            'deee'         => $r['eco_tax'],
            'validate'     => 1,
            'fabricant'    => ($is_apple ? 'APPLE' : ''),
            'serialisable' => ((int) $r['serialisable'] === 1 ? 1 : 0),
            'datec'        => substr($r['date_create'], 0, 19)
        );

        $prod = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_Product', array(
                    'ref' => array($ref, $r['ref'])
        ));

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
            $errors = $prod->validateArray($data);

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
            if (isset($entrepots[$r['label_stock']])) {
                $id_entrepot = (int) $entrepots[$r['label_stock']];
            } else {
                $id_entrepot = (int) $bdb->getValue('entrepot', 'rowid', 'ref = \'' . $r['label_stock'] . '\'');

                if ($id_entrepot) {
                    $entrepots[$r['label_stock']] = $id_entrepot;
                } else {
                    $id_entrepot = (int) $bdb->insert('entrepot', array(
                                'ref' => $r['label_stock']
                                    ), true);

                    if (!$id_entrepot) {
                        echo '<span class="danger">';
                        echo 'Echec de la création de l\'entrepôt ' . $r['label_stock'] . ' - ' . $bdb->err();
                        echo '</span>';
                    } else {
                        $entrepots[$r['label_stock']] = $id_entrepot;
                    }
                }
            }

            // Maj des stocks. 
            if ($id_entrepot && (float) $r['qty']) {
                $stock_errors = $prod->correctStocks($id_entrepot, (float) $r['qty'], 0, $code_move, $label_move);

                if (count($stock_errors)) {
                    echo BimpRender::renderAlerts($stock_errors);
                } else {
                    echo ' - <span class="success">Stocks OK</span>';
                }
            }
        }

        break;
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
