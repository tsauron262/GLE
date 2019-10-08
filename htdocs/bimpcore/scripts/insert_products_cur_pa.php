<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'INSERT PRODUCTS CUR PA', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db;
$bdb = new BimpDb($db);

$list = BimpCache::getBimpObjectList('bimpcore', 'Bimp_Product', array(
            'no_fixe_prices' => 0,
//            'rowid'          => 131312
        ));

foreach ($list as $id_product) {
    $product = BimpObject::getInstance('bimpcore', 'Bimp_Product', $id_product);

    if (!BimpObject::objectLoaded($product)) {
        continue;
    }

    // On suppr les cur pas actuels: 
    $bdb->delete('bimp_product_cur_pa', 'id_product = ' . (int) $id_product);

    // Reconstruction de l'historique des Cur Pa sur la base des factures fournisseurs: 

    $current_date = date('Y-m-d H:i:s');
    $dates = array();
    $where = 'l.fk_product = ' . (int) $id_product . ' AND f.fk_statut IN (1,2)';
    $from = ' FROM ' . MAIN_DB_PREFIX . 'facture_fourn f';
    $join = ' LEFT JOIN ' . MAIN_DB_PREFIX . 'facture_fourn_det l ON l.fk_facture_fourn = f.rowid';

    while (1) {
        $pa_ht = 0;
        // ON sélectionne le dernier PA enregistré inférieur à $current_date: 
        $sql = 'SELECT f.rowid as id, f.datec as date, f.fk_soc, l.pu_ht' . $from . $join;
        $sql .= ' WHERE ' . $where;
        $sql .= ' AND f.datec < \'' . $current_date . '\'';
        $sql .= ' ORDER BY f.datec DESC LIMIT 1';

//        echo $sql . '<br/>';
        $res1 = $bdb->executeS($sql, 'array');

//        echo 'Res1: <pre>';
//        print_r($res1);
//        echo '</pre>';
//        echo $bdb->db->lasterror() . '<br/>';

        if (isset($res1[0]['pu_ht'])) {
            $pa_ht = (float) $res1[0]['pu_ht'];
        }


        if ($pa_ht) {
            // On récupère la date de la première facture précédante ayant un pa différent: 
            $sql = 'SELECT MAX(f.datec) as date' . $from . $join . ' WHERE ' . $where . ' AND l.pu_ht != ' . $pa_ht . ' AND f.datec < \'' . $current_date . '\'';
//            echo $sql . '<br/>';
            $res2 = $bdb->executeS($sql, 'array');
//            echo 'Res2: <pre>';
//            print_r($res2);
//            echo '</pre>';
//            echo $bdb->db->lasterror() . '<br/>';
            if (isset($res2[0]['date'])) {
                $prev_date = $res2[0]['date'];
            } else {
                $prev_date = '0000-00-00 00:00:00';
            }

            // ON sélectionne la première facture créée avec ce pa avant $current_date et après $prev_date:
            $sql = 'SELECT f.rowid as id, f.datec as date, f.fk_soc' . $from . $join;
            $sql .= ' WHERE ' . $where;
            $sql .= ' AND f.datec < \'' . $current_date . '\'';
            $sql .= ' AND f.datec > \'' . $prev_date . '\' ORDER BY f.datec ASC LIMIT 1';

//            echo $sql . '<br/>';
            $res3 = $bdb->executeS($sql, 'array');
//            echo 'Res3: <pre>';
//            print_r($res3);
//            echo '</pre>';
//            echo $bdb->db->lasterror() . '<br/>';

            if (isset($res3[0])) {
                $current_date = $res3[0]['date'];

                $id_fp = (int) $product->findFournPriceIdForPaHt($pa_ht, (int) $res3[0]['fk_soc']);
                $dates[$current_date] = array(
                    'pa_ht'  => $pa_ht,
                    'id_fac' => $res3[0]['id'],
                    'id_fp'  => $id_fp
                );
                continue;
            }
        }
        break;
    }

//    echo '<pre>';
//    print_r($dates);
//    echo '</pre>';

    $date_to = null;
    foreach ($dates as $date_from => $data) {
        $curPa = BimpObject::getInstance('bimpcore', 'BimpProductCurPa');
        $err = $curPa->validateArray(array(
            'id_product'     => (int) $id_product,
            'amount'         => $data['pa_ht'],
            'date_from'      => $date_from,
            'origin'         => 'facture_fourn',
            'id_origin'      => (int) $data['id_fac'],
            'id_fourn_price' => (int) $data['id_fp']
        ));
        if (!is_null($date_to)) {
            $curPa->set('date_to', $date_to);
        }

        $date_to = $date_from;

        if (!count($err)) {
            $err = $curPa->create($w, true);
        }
        if (count($err)) {
            echo BimpRender::renderAlerts(BimpTools::getMsgFromArray($err, 'PROD #' . $id_prod));
            break;
        }
    }

    if (is_null($date_to) || $date_to > '2019-07-01 23:59:59') {
        while (1) {
            $sql = 'SELECT rowid, datec, price FROM ' . MAIN_DB_PREFIX . 'product_fournisseur_price WHERE fk_product = ' . (int) $id_product;
            if (!is_null($date_to)) {
                $sql .= ' AND datec < \'' . $date_to . '\'';
            }
            $sql .= ' ORDER BY datec DESC LIMIT 1';

            $res = $bdb->executeS($sql, 'array');
            if (isset($res[0])) {
                $data = $res[0];
                $curPa = BimpObject::getInstance('bimpcore', 'BimpProductCurPa');
                $err = $curPa->validateArray(array(
                    'id_product'     => (int) $id_product,
                    'amount'         => $data['price'],
                    'date_from'      => $data['datec'],
                    'origin'         => 'fourn_price',
                    'id_origin'      => (int) $data['rowid'],
                    'id_fourn_price' => (int) $data['rowid']
                ));
                if (!is_null($date_to)) {
                    $curPa->set('date_to', $date_to);
                }
                if (!count($err)) {
                    $err = $curPa->create($w, true);
                }
                if (count($err)) {
                    echo BimpRender::renderAlerts(BimpTools::getMsgFromArray($err, 'PROD #' . $id_prod));
                    break;
                }

                if ($data['datec'] < '2019-07-01 23:59:59') {
                    break;
                }
                $date_to = $data['datec'];
            }
            break;
        }
    }

    if (is_null($date_to) || $date_to > '2019-07-01 23:59:59') {
        $pmp = (float) $product->getData('pmp');
        if ($pmp) {
            $curPa = BimpObject::getInstance('bimpcore', 'BimpProductCurPa');
            $err = $curPa->validateArray(array(
                'id_product' => (int) $id_product,
                'amount'     => $pmp,
                'date_from'  => '2019-07-01 00:00:00',
                'origin'     => 'pmp'
            ));
            if (!is_null($date_to)) {
                $curPa->set('date_to', $date_to);
            }
            if (!count($err)) {
                $err = $curPa->create($w, true);
            }
            if (count($err)) {
                echo BimpRender::renderAlerts(BimpTools::getMsgFromArray($err, 'PROD #' . $id_prod));
            }
        }
    }

    unset($product);
    BimpCache::$cache = array();
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();