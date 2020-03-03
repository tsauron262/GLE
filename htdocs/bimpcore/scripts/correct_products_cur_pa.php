<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(60);

ignore_user_abort(0);

top_htmlhead('', 'CORRECT PRODUCTS CUR PA', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db;
$bdb = new BimpDb($db);

$list = BimpCache::getBimpObjectList('bimpcore', 'Bimp_Product', array(
            'cur_pa_ht' => 0
//            'rowid' => 131312
        ));

$h_file = fopen(DOL_DATA_ROOT . '/bimpcore/cur_pa_correction_test10.txt', 'w');

if (!$h_file) {
    echo BimpRender::renderAlerts('Echec de la création du fichier "cur_pa_corrections.txt"');
    exit;
}

foreach ($list as $id_p) {
    $prod = BimpObject::getInstance('bimpcore', 'Bimp_Product', (int) $id_p);
    if (BimpObject::objectLoaded($prod)) {
        $cur_pa = (float) $prod->getCurrentPaHt(null, true);

        if (!$cur_pa) {
            continue;
        }
        
        $dates = array();
        $id_pfp = $prod->findFournPriceIdForPaHt($cur_pa);

        if ($id_pfp) {
            $date = $bdb->getValue('product_fournisseur_price', 'datec', '`rowid` = ' . (int) $id_pfp);

            if (is_null($date) || !(string) $date) {
                $date = $bdb->getValue('product_fournisseur_price', 'tms', '`rowid` = ' . (int) $id_pfp);
            }

            if (!is_null($date) && (string) $date) {
                $dates[] = array(
                    'pa'  => $cur_pa,
                    'min' => $date
                );

                while ($date > '2019-07-01 00:00:00') {
                    $where1 = '`fk_product` = ' . (int) $id_p . ' AND `datec` < \'' . $date . '\'';
                    $sql = 'SELECT `datec`, `price` FROM ' . MAIN_DB_PREFIX . 'product_fournisseur_price';
                    $sql .= ' WHERE ' . $where1 . ' AND `tms` = (SELECT MAX(tms) FROM ' . MAIN_DB_PREFIX . 'product_fournisseur_price WHERE ' . $where1 . ')';
                    $sql .= ' ORDER BY `rowid` DESC LIMIT 1';

                    $result = $bdb->executeS($sql, 'array');

                    if (isset($result[0]['price']) && (float) $result[0]['price']) {
                        $date = $result[0]['datec'];
                        $dates[] = array(
                            'pa'  => (float) $result[0]['price'],
                            'min' => $result[0]['datec']
                        );
                        continue;
                    }
                    break;
                }
            }
        }
//        else {
//            $txt .= ' Aucun PA fournisseur trouvé - pas de correction de pièces' . "\n";
//        }
//        $err = $prod->updateField('cur_pa_ht', $cur_pa);
//        if (count($err)) {
//            echo BimpRender::renderAlerts(BimpTools::getMsgFromArray($err, 'Echec màj cur pa pour prod #' . $id_p));
//            continue;
//        }

        $date_max = date('Y-m-d H:i:s');

        $rows = '';
        foreach ($dates as $date_data) {
            $new_pa = $date_data['pa'];
            $date_min = $date_data['min'];

            if ($date_min >= $date_max) {
                continue;
            }

            $dt_min = new DateTime($date_min);
            $dt_max = new DateTime($date_max);

            $counts = '';

            $sql = 'SELECT COUNT(DISTINCT a.rowid) as nb FROM '.MAIN_DB_PREFIX.'propal a';
            $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'propaldet l ON a.rowid = l.fk_propal';
            $sql .= ' WHERE l.fk_product = ' . (int) $id_p;
            $sql .= ' AND a.datec >= \'' . $date_min . '\' AND a.datec < \'' . $date_max . '\'';
            $sql .= ' AND l.buy_price_ht != ' . (float) $new_pa;

            $res = $bdb->executeS($sql, 'array');

            if (isset($res[0]['nb']) && (int) $res[0]['nb']) {
                $counts .= $res[0]['nb'] . ' prop - ';
            }

            $sql = 'SELECT COUNT(DISTINCT a.rowid) as nb FROM '.MAIN_DB_PREFIX.'commande a';
            $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'commandedet l ON a.rowid = l.fk_commande';
            $sql .= ' WHERE l.fk_product = ' . (int) $id_p;
            $sql .= ' AND a.date_creation >= \'' . $date_min . '\' AND a.date_creation < \'' . $date_max . '\'';
            $sql .= ' AND l.buy_price_ht != ' . (float) $new_pa;

            $res = $bdb->executeS($sql, 'array');

            if (isset($res[0]['nb']) && (int) $res[0]['nb']) {
                $counts .= $res[0]['nb'] . ' cmdes - ';
            }

            $sql = 'SELECT COUNT(DISTINCT a.rowid) as nb FROM '.MAIN_DB_PREFIX.'facture a';
            $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'facturedet l ON a.rowid = l.fk_facture';
            $sql .= ' WHERE l.fk_product = ' . (int) $id_p;
            $sql .= ' AND a.datec >= \'' . $date_min . '\' AND a.datec < \'' . $date_max . '\'';
            $sql .= ' AND l.buy_price_ht != ' . (float) $new_pa;
            $sql .= ' AND (a.id_user_commission = 0 AND a.id_entrepot_commission = 0)';

            $res = $bdb->executeS($sql, 'array');

            if (isset($res[0]['nb']) && (int) $res[0]['nb']) {
                $counts .= $res[0]['nb'] . ' fac non comm - ';
            }

            $sql = 'SELECT COUNT(DISTINCT a.rowid) as nb FROM '.MAIN_DB_PREFIX.'facture a';
            $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'facturedet l ON a.rowid = l.fk_facture';
            $sql .= ' WHERE l.fk_product = ' . (int) $id_p;
            $sql .= ' AND a.datec >= \'' . $date_min . '\' AND a.datec < \'' . $date_max . '\'';
            $sql .= ' AND l.buy_price_ht != ' . (float) $new_pa;
            $sql .= ' AND (a.id_user_commission > 0 OR a.id_entrepot_commission > 0)';

            $res = $bdb->executeS($sql, 'array');

            if (isset($res[0]['nb']) && (int) $res[0]['nb']) {
                $counts .= $res[0]['nb'] . ' fac commissionnées';
            }

            if ($counts) {
                $rows .= 'PA du ' . $dt_min->format('d / m / Y') . ' au ' . $dt_max->format('d / m / Y') . ' (' . BimpTools::displayMoneyValue($new_pa, '') . '): ' . $counts . "\n";
            }
            $date_max = $date_min;
        }

        if ($rows) {
            $txt = 'PRODUIT #' . $id_p . ' "' . $prod->getRef() . '" (Nouveau PA courant: ' . BimpTools::displayMoneyValue($cur_pa, '') . ')' . "\n";
            $txt .= $rows . "\n";
            fwrite($h_file, $txt);
        }

//        // Màj des propales: 
//        $sql = BimpTools::getSqlSelect(array('rowid', 'buy_price_ht', 'fk_propal'));
//        $sql .= BimpTools::getSqlFrom('propaldet', array(
//                    array(
//                        'table' => 'propal',
//                        'on'    => 'p.rowid = a.fk_propal',
//                        'alias' => 'p'
//                    )
//        ));
//        $sql .= BimpTools::getSqlWhere(array(
//                    'a.fk_product'   => (int) $id_p,
//                    'a.buy_price_ht' => '!= ' . $cur_pa,
//                    'p.datec'        => '> 2019-06-30 23:59:59',
//        ));
//
//        $rows = $bdb->executeS($sql, 'array');
//
//        if (is_array($rows) && !empty($rows)) {
//            $lines = array();
//            foreach ($rows as $r) {
//                if (!in_array((int) $r['rowid'], $lines)) {
//                    $lines[] = (int) $r['rowid'];
//                }
//            }
//            if (!empty($lines)) {
//                if ($bdb->update('propaldet', array(
//                            'buy_price_ht' => $cur_pa
//                                ), '`rowid` IN (' . implode(',', $lines) . ')') <= 0) {
//                    $msg = 'Eche màj lignes de propal pour prod #' . $id_p . ' - ' . $bdb->db->lasterror();
//                    echo BimpRender::renderAlerts($msg);
//                }
//            }
//        }
//
//        // Màj des commandes: 
//        $sql = BimpTools::getSqlSelect(array('rowid', 'buy_price_ht', 'fk_commande'));
//        $sql .= BimpTools::getSqlFrom('commandedet', array(
//                    array(
//                        'table' => 'commande',
//                        'on'    => 'c.rowid = a.fk_commande',
//                        'alias' => 'c'
//                    )
//        ));
//        $sql .= BimpTools::getSqlWhere(array(
//                    'a.fk_product'    => (int) $id_p,
//                    'a.buy_price_ht'  => '!= ' . $cur_pa,
//                    'c.date_creation' => array(
//                        'min' => '2019-06-30 23:59:59',
//                        'max' => $max_date
//                    )
//        ));
//
//        $rows = $bdb->executeS($sql, 'array');
//
//        if (is_array($rows) && !empty($rows)) {
//            $lines = array();
//            foreach ($rows as $r) {
//                if (!in_array((int) $r['rowid'], $lines)) {
//                    $lines[] = (int) $r['rowid'];
//                }
//            }
//            if (!empty($lines)) {
//                if ($bdb->update('commandedet', array(
//                            'buy_price_ht' => $cur_pa
//                                ), '`rowid` IN (' . implode(',', $lines) . ')') <= 0) {
//                    $msg = 'Eche màj lignes de commande pour prod #' . $id_p . ' - ' . $bdb->db->lasterror();
//                    echo BimpRender::renderAlerts($msg);
//                }
//            }
//        }
//
//        // Màj des factures non commissionnées: 
//        $sql = BimpTools::getSqlSelect(array('rowid', 'buy_price_ht', 'fk_facture'));
//        $sql .= BimpTools::getSqlFrom('facturedet', array(
//                    array(
//                        'table' => 'facture',
//                        'on'    => 'f.rowid = a.fk_facture',
//                        'alias' => 'f'
//                    )
//        ));
//        $sql .= BimpTools::getSqlWhere(array(
//                    'a.fk_product'             => (int) $id_p,
//                    'a.buy_price_ht'           => '!= ' . $cur_pa,
//                    'f.datec'                  => '> 2019-06-30 23:59:59',
//                    'f.id_entrepot_commission' => 0,
//                    'f.id_user_commission'     => 0,
//        ));
//
//        $rows = $bdb->executeS($sql, 'array');
//
//        if (is_array($rows) && !empty($rows)) {
//            $lines = array();
//            foreach ($rows as $r) {
//                if (!in_array((int) $r['rowid'], $lines)) {
//                    $lines[] = (int) $r['rowid'];
//                }
//            }
//            if (!empty($lines)) {
//                if ($bdb->update('facturedet', array(
//                            'buy_price_ht' => $cur_pa
//                                ), '`rowid` IN (' . implode(',', $lines) . ')') <= 0) {
//                    $msg = 'Echec màj lignes de facture pour prod #' . $id_p . ' - ' . $bdb->db->lasterror();
//                    echo BimpRender::renderAlerts($msg);
//                }
//            }
//        }
//
//        // Créa des reval pour les factures déjà commissionnées. 
//        $sql = BimpTools::getSqlSelect(array('rowid', 'buy_price_ht', 'fk_facture'));
//        $sql .= BimpTools::getSqlFrom('facturedet', array(
//                    array(
//                        'table' => 'facture',
//                        'on'    => 'f.rowid = a.fk_facture',
//                        'alias' => 'f'
//                    )
//        ));
//        $sql .= BimpTools::getSqlWhere(array(
//                    'a.fk_product'   => (int) $id_p,
//                    'a.buy_price_ht' => '!= ' . $cur_pa,
//                    'f.datec'        => '> 2019-06-30 23:59:59',
//                    'commissions'    => array(
//                        'custom' => '(f.id_entrepot_commission > 0 OR f.id_user_commission > 0)'
//                    )
//        ));
//
//        $rows = $bdb->executeS($sql, 'array');
//
//        if (is_array($rows) && !empty($rows)) {
//            $lines = array();
//            foreach ($rows as $r) {
//                if (!in_array((int) $r['rowid'], $lines)) {
//                    $lines[] = (int) $r['rowid'];
//                }
//            }
//        }
////            
//        foreach ($lines as $id_line) {
//            $line = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_FactureLine', array(
//                        'id_line' => (int) $id_line
//            ));
//
//            if (!BimpObject::objectLoaded($line)) {
////                    echo 'pas de ligne <br/>';
//                continue;
//            }
//
//            if ((float) $line->qty <= 0) {
////                    echo 'Qty 0 <br/>';
//                continue;
//            }
//
//            $total_reval = ((float) $line->pa_ht - $cur_pa) * (float) $line->qty;
//
//            if (!$amount) {
////                    echo 'pas de amount <br/>';
//                continue;
//            }
//
//            // On vérifie qu'une reval n'existe pas déjà: 
//            $id_facture = (int) $line->getData('id_obj');
//
//            if (!$id_facture) {
////                    echo 'pas de fac <br/>';
//                continue;
//            }
//
//            // Check des revals existantes: 
//            $revals = BimpCache::getBimpObjectObjects('bimpfinanc', 'BimpRevalorisation', array(
//                        'id_facture'      => (int) $facture->id,
//                        'id_facture_line' => (int) $this->id,
//                        'type'            => 'correction_pa'
//            ));
//
//            foreach ($revals as $reval) {
//                // Déduction du montant des revals validées / suppr. des autres. 
//                if ((int) $reval->getData('status') === 1) {
//                    $total_reval -= (float) $reval->getTotal();
//                } else {
//                    $w = array();
//                    $del_errors = $reval->delete($w, true);
//                    if (count($del_errors)) {
//                        $total_reval -= (float) $reval->getTotal();
//                    }
//                }
//            }
//
//            $reval = BimpObject::getInstance('bimpfinanc', 'BimpRevalorisation');
//
//            $reval_errors = $reval->validateArray(array(
//                'id_facture'      => (int) $id_facture,
//                'id_facture_line' => (int) $line->id,
//                'type'            => 'correction_pa',
//                'date'            => date('Y-m-d'),
//                'amount'          => $amount,
//                'qty'             => (float) $line->qty,
//                'note'            => "Correction automatique du prix d'achat. Prix d'achat enregistré: " . $line->pa_ht . ". Prix d'achat réel: " . $cur_pa
//            ));
//
//            if (!count($reval_errors)) {
//                $reval_warnings = array();
//                $reval_errors = $reval->create($reval_warnings, true);
//            }
//
//            if (count($reval_errors)) {
//                echo BimpRender::renderAlerts(BimpTools::getMsgFromArray($reval_errors, 'Prod #' . $id_p . ': Echec de la créa de la reval pour ligne #' . $line->getData('id_line') . ' - fac #' . $id_facture));
//            } else {
//                echo 'REVAL OK ' . $reval->id . '<br/>';
//            }
//        }
    }
    unset($prod);
    BimpCache::$cache = array();
}

fclose($h_file);

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();