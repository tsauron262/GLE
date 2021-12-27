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

$keys = array(
    'code'         => 0,
    'label'        => 1,
    'stock'        => 2,
    'pref'         => 3,
    'ref'          => 4,
    'serialisable' => 5,
    'gamme'        => 6,
    'categorie'    => 7,
    'collection'   => 8,
    'nature'       => 9,
    'famille'      => 10,
    'serials'      => 11,
    'pu_ht'        => 12,
    'pa_ht'        => 13,
    'fourn'        => 15,
    'barcode'      => 16,
);

$fourns = array(
    'TECH DATA'                                  => 229890,
    'NEKLAN'                                     => 231801,
    'EDOX'                                       => 230082,
    'D3C'                                        => 528832,
    'INGRAM MICRO'                               => 230496,
    'DAM DISTRIBUTEUR VOGEL\'S SCHNEPEL - iTRIO' => 528835,
    'VOG IMPORT'                                 => 233094,
    'COMPUTERS UNLIMITED SAS'                    => 528838,
    'BRICO DEPOT'                                => 231399,
    'ALSO'                                       => 229917,
    'OCTANT'                                     => 528841,
    'THS FRANCE'                                 => 528844,
    'DEXXON MEDIA'                               => 230658,
    'C2M-INTELWARE'                              => 231879,
    'AASSET SECURITY'                            => 528847,
    'CONRAD'                                     => 229440,
    'ALIEXPRESS'                                 => 528850,
);


$bdb = new BimpDb($db);

$dir = DOL_DOCUMENT_ROOT . '/bimpcore/scripts/docs/';
$file_name = 'import_prods_blois_final.csv';

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

echo '<pre>';
print_r($rows);
echo '</pre>';

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
    $noPa = array();
    global $fourns, $bdb;

    $categories = BimpCache::getProductsTagsByTypeArray('categorie', false, 'label');
    $gammes = BimpCache::getProductsTagsByTypeArray('gamme', false, 'label');
    $collections = BimpCache::getProductsTagsByTypeArray('collection', false, 'label');
    $natures = BimpCache::getProductsTagsByTypeArray('nature', false, 'label');
    $familles = BimpCache::getProductsTagsByTypeArray('famille', false, 'label');

    $id_entrepot = 450;
    $code_move = 'IMPORT_CSV_BLOIS';
    $label_move = 'Imports produits centre Blois';

    BimpObject::loadClass('bimpcore', 'BimpProductCurPa');
    BimpObject::loadClass('bimpcore', 'Bimp_ProductFournisseurPrice');

    foreach ($rows as $r) {
        echo '<br/><br/>';
        if (!(string) $r['ref']) {
            if ($r['code'] && $r['pref'] && preg_match('/^([A-Z]{1,3})\-?$/', $r['pref'], $matches)) {
                $r['ref'] = $matches[1] . '-' . $r['code'];
            }
        }

        if (!$r['ref']) {
            $r['ref'] = $r['code'];

            if ($r['ref']) {
                echo '<span class="danger">Code "' . $r['ref'] . '": pas de référence complète</span>';
                $failed[] = $r['ref'];
            }
            continue;
        }

        echo '<strong>' . $r['ref'] . '</strong>: ';

        $pu_ht = cleanPrice($r['pu_ht']);
        if (!$pu_ht) {
            echo '<span class="danger">PU absent</span>';
            $failed[] = $r['ref'];
            continue;
        }

        $isNew = false;
        $tva_tx = 20;

        $serialisable = ($r['serialisable'] == 'NUFARTSTKSERIE' ? 1 : 0);

        $errors = array();
        $prod = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_Product', array(
                    'ref' => $r['ref']
                        ), true);

        if (!BimpObject::objectLoaded($prod)) {
            $isNew = true;
            // Créa du produit: 
            $prod = BimpObject::createBimpObject('bimpcore', 'Bimp_Product', array(
                        'ref'          => $r['ref'],
                        'label'        => $r['label'],
                        'price'        => $pu_ht,
                        'tva_tx'       => $tva_tx,
                        'serialisable' => $serialisable,
                        'barcode'      => $r['barcode'],
                        'collection'   => ($r['collection'] && isset($collections[$r['collection']]) ? $collections[$r['collection']] : 0),
                        'nature'       => ($r['nature'] && isset($natures[$r['nature']]) ? $natures[$r['nature']] : 0),
                        'famille'      => ($r['famille'] && isset($familles[$r['famille']]) ? $familles[$r['famille']] : 0),
                        'gamme'        => ($r['gamme'] && isset($gammes[$r['gamme']]) ? $gammes[$r['gamme']] : 0),
                        'categorie'    => ($r['categorie'] && isset($categories[$r['categorie']]) ? $categories[$r['categorie']] : 0)
                            ), true, $errors);

            if (count($errors)) {
                echo BimpRender::renderAlerts($errors);
                $failed[] = $r['ref'];
            } else {
                echo '<span class="success">Créa OK (#' . $prod->id . ')</span>';
            }
        } else {
            echo '<span class="success">Prod #' . $prod->id . '</span>';
        }

        if (!count($errors) && BimpObject::objectLoaded($prod)) {
            // Ajout PA: 
            $pa_ht = cleanPrice($r['pa_ht']);
            if ($pa_ht) {
                $pa_ok = false;

                $ref_fourn = (isset($refs[$r['ref']]['ref_fourn']) ? $refs[$r['ref']]['ref_fourn'] : '');
                $id_fourn = ($r['fourn'] ? (isset($fourns[$r['fourn']]) ? (int) $fourns[$r['fourn']] : 0) : 0);

                if ($ref_fourn && $id_fourn) {
                    $_POST['is_cur_pa'] = 1;
                    $pfp = null;

                    if (!$isNew) {
                        $pfp = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_ProductFournisseurPrice', array(
                                    'fk_product' => (int) $prod->id,
                                    'ref_fourn'  => $ref_fourn,
                                    'fk_soc'     => $id_fourn
                                        ), true);

                        $curPa = BimpProductCurPa::getProductCurPa((int) $prod->id);
                        if (BimpObject::objectLoaded($curPa)) {
                            $_POST['is_cur_pa'] = 0;
                        }
                    }

                    if (is_null($pfp)) {
                        // Créa prix fourn
                        BimpObject::createBimpObject('bimpcore', 'Bimp_ProductFournisseurPrice', array(
                            'fk_product' => $prod->id,
                            'fk_soc'     => $id_fourn,
                            'ref_fourn'  => $ref_fourn,
                            'price'      => $pa_ht,
                            'tva_tx'     => $tva_tx
                                ), true, $errors);

                        if (count($errors)) {
                            echo BimpRender::renderAlerts($errors);
                            $noPa[] = $r['ref'];
                        } else {
                            echo ' - <span class="success">Prix Fourn OK</span>';
                            $pa_ok = true;
                        }
                    }
                } else {
                    // Créa PA courant: 
                    BimpObject::createBimpObject('bimpcore', 'BimpProductCurPa', array(
                        'id_product' => $prod->id,
                        'amount'     => $pa_ht
                            ), true, $errors);

                    if (count($errors)) {
                        echo BimpRender::renderAlerts($errors);
                        $noPa[] = $r['ref'];
                    } else {
                        echo ' - <span class="success">PA courant OK</span>';
                        $pa_ok = true;
                    }
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
                echo ' - <span class="warning">PAS DE PA</span>';
                $noPa[] = $r['ref'];
            }

            // Maj des stocks / équipements. 
            if ($serialisable) {
                if ($r['serials']) {
                    $serials = explode(',', $r['serials']);
                }

                if (!empty($serials)) {
                    foreach ($serials as $serial) {
                        echo '<br/>NS "' . $serial . '": ';
                        $eq_errors = array();
                        $equipment = BimpObject::createBimpObject('bimpequipment', 'Equipment', array(
                                    'id_product' => $prod->id,
                                    'serial'     => $serial
                                        ), true, $eq_errors);

                        if (count($eq_errors)) {
                            echo BimpRender::renderAlerts($eq_errors);
                        } else {
                            $place_errors = array();
                            BimpObject::createBimpObject('bimpequipment', 'BE_Place', array(
                                'id_equipment' => $equipment->id,
                                'type'         => BE_Place::BE_PLACE_ENTREPOT,
                                'id_entrepot'  => $id_entrepot,
                                'date'         => date('Y-m-d H:i:s'),
                                'code_mvt'     => $code_move,
                                'infos'        => $label_move
                                    ), true, $place_errors);

                            if (count($place_errors)) {
                                echo BimpRender::renderAlerts($place_errors, 'danger');
                            } else {
                                echo '<span class="success">OK</span>';
                            }
                        }
                    }
                }
            } else {
                if ((float) $r['stock']) {
                    $stock_errors = $prod->correctStocks($id_entrepot, (float) $r['stock'], 0, $code_move, $label_move);

                    if (count($stock_errors)) {
                        echo BimpRender::renderAlerts($stock_errors);
                    } else {
                        echo ' - <span class="success">Stocks OK</span>';
                    }
                } else {
                    echo ' - <span class="warning">Pas de stock</span>';
                }
            }
        }
    }

    echo '<br/><br/> NON CREES: <br/><br/>';
    foreach ($failed as $ref) {
        echo $ref . '<br/>';
    }

    echo '<br/><br/> PAS DE PA: <br/><br/>';
    foreach ($noPa as $ref) {
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
