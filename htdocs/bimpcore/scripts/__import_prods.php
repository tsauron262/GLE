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

//ID pièce
//5 Statut Cde
//Imprimée
//4 Attentions particulières
//Appro Spécifique pour
//16 - Validation DT
//21 Date d'appro Max
//7 Date d'instal souhaitée
//9 Tech. Instal
//Etat
//N° pièce vente
//Date pièce
//Code représentant
//Code client
//Libellé client
//Code établissement
//Code dépôt départ
//Dernier utilisateur
//0 Ref Cde Client
//SELECT F_ARTICLE.AR_Ref ActiReference, 
//        F_ARTICLE.AR_Design Designation, 
//        IIF(F_ARTICLE.AR_CodeBarre = '', NULL, F_ARTICLE.AR_CodeBarre) EanCode,
//              F_ARTICLE.FA_CodeFamille, 
//        F_FAMILLE.FA_Intitule,
//              F_ARTSTOCK.AS_QteSto Quantity, 
//        F_ARTICLE.AR_PrixVen PriceVatOff, 
//        F_ARTICLE.AR_PrixVen * 1.2 PriceVatOn, 
//        F_ARTICLE.AR_PrixAch BuyingPriceVatOff,ISNULL(D3E.TA_Taux, 0) EcoTaxVatOff,
//                      ISNULL(F_ARTSTOCK.cbCreation, GETDATE()) CreatedAt, 
//        ISNULL(F_ARTSTOCK.cbModification, GETDATE()) UpdatedAt,
//                      F_DEPOT.DE_No, 
//        F_DEPOT.DE_Intitule


$keys = array(
    'ref'           => 0,
    'label'         => 1,
    'ean'           => 2,
    'code_famille'  => 3,
    'label_famille' => 4,
    'qty'           => 5,
    'pu_ht'         => 6,
    'pu_ttc'        => 7,
    'pa_ht'         => 8,
    'eco_tax'       => 9,
    'date_create'   => 10,
    'date_update'   => 11,
    'code_stock'    => 12,
    'label_stock'   => 13
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

//echo '<pre>';
//print_r($rows);
//echo '</pre>';

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

//    $categories = BimpCache::getProductsTagsByTypeArray('categorie', false, 'label');
//    $gammes = BimpCache::getProductsTagsByTypeArray('gamme', false, 'label');
//    $collections = BimpCache::getProductsTagsByTypeArray('collection', false, 'label');
//    $natures = BimpCache::getProductsTagsByTypeArray('nature', false, 'label');
//    $familles = BimpCache::getProductsTagsByTypeArray('famille', false, 'label');

    $familles = array();
    $entrepots = array();

    $code_move = 'IMPORT_CSV';
    $label_move = 'Import CSV';

    BimpObject::loadClass('bimpcore', 'BimpProductCurPa');

    foreach ($rows as $r) {
        echo '<br/><br/>';
        if (!(string) $r['ref']) {
            if ($r['code'] && $r['pref'] && preg_match('/^([A-Z]{1,3})\-?$/', $r['pref'], $matches)) {
                $r['ref'] = $matches[1] . '-' . $r['code'];
            }
        }
        $id_product = (int) $bdb->getValue('product', 'rowid', 'ref = \'' . $r['ref'] . '\'');
        if (!$id_product) {
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

            // Créa du produit: 
            $prod = BimpObject::createBimpObject('bimpcore', 'Bimp_Product', array(
                        'ref'        => $r['ref'],
                        'label'      => $r['label'],
                        'price'      => (float) $r['pu_ht'],
                        'tva_tx'     => $tva_tx,
                        'barcode'    => $r['ean'],
                        'id_famille' => $id_famille,
                        'deee'       => $r['eco_tax'],
                        'validate'   => 1
                            ), true, $errors);

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

                if (count($errors)) {
                    echo BimpRender::renderAlerts($errors);
                } else {
                    echo ' - <span class="success">PA courant OK</span>';
                }
            }
        } else {
            $prod = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $id_product);
        }

        if (BimpObject::objectLoaded($prod)) {
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
