<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

top_htmlhead('', 'CORRECT PRODUCTS CUR PA', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db;
$bdb = new BimpDb($db);

echo 'Désactivé';
exit;

$list = BimpCache::getBimpObjectList('bimpcore', 'Bimp_Product', array(
            'cur_pa_ht' => 0,
//            'rowid' => 131312
        ));


foreach ($list as $id_p) {
    $prod = BimpObject::getInstance('bimpcore', 'Bimp_Product', (int) $id_p);
    if (BimpObject::objectLoaded($prod)) {
        $cur_pa = (float) $prod->getCurrentPaHt();

        if ($cur_pa) {
            $err = $prod->updateField('cur_pa_ht', $cur_pa);
            if (count($err)) {
                echo BimpRender::renderAlerts(BimpTools::getMsgFromArray($err, 'Echec màj cur pa pour prod #' . $id_p));
                continue;
            }
            
            // Màj des propales: 
            $sql = BimpTools::getSqlSelect(array('rowid', 'buy_price_ht', 'fk_propal'));
            $sql .= BimpTools::getSqlFrom('propaldet', array(
                        array(
                            'table' => 'propal',
                            'on'    => 'p.rowid = a.fk_propal',
                            'alias' => 'p'
                        )
            ));
            $sql .= BimpTools::getSqlWhere(array(
                        'a.fk_product'   => (int) $id_p,
                        'a.buy_price_ht' => '!= ' . $cur_pa,
                        'p.datec'        => '> 2019-06-30 23:59:59',
            ));

            $rows = $bdb->executeS($sql, 'array');

            if (is_array($rows) && !empty($rows)) {
                $lines = array();
                foreach ($rows as $r) {
                    if (!in_array((int) $r['rowid'], $lines)) {
                        $lines[] = (int) $r['rowid'];
                    }
                }
                if (!empty($lines)) {
                    if ($bdb->update('propaldet', array(
                                'buy_price_ht' => $cur_pa
                                    ), '`rowid` IN (' . implode(',', $lines) . ')') <= 0) {
                        $msg = 'Eche màj lignes de propal pour prod #' . $id_p . ' - ' . $bdb->db->lasterror();
                        echo BimpRender::renderAlerts($msg);
                    }
                }
            }
            
            // Màj des commandes: 
            $sql = BimpTools::getSqlSelect(array('rowid', 'buy_price_ht', 'fk_commande'));
            $sql .= BimpTools::getSqlFrom('commandedet', array(
                        array(
                            'table' => 'commande',
                            'on'    => 'c.rowid = a.fk_commande',
                            'alias' => 'c'
                        )
            ));
            $sql .= BimpTools::getSqlWhere(array(
                        'a.fk_product'    => (int) $id_p,
                        'a.buy_price_ht'  => '!= ' . $cur_pa,
                        'c.date_creation' => '> 2019-06-30 23:59:59',
            ));

            $rows = $bdb->executeS($sql, 'array');

            if (is_array($rows) && !empty($rows)) {
                $lines = array();
                foreach ($rows as $r) {
                    if (!in_array((int) $r['rowid'], $lines)) {
                        $lines[] = (int) $r['rowid'];
                    }
                }
                if (!empty($lines)) {
                    if ($bdb->update('commandedet', array(
                                'buy_price_ht' => $cur_pa
                                    ), '`rowid` IN (' . implode(',', $lines) . ')') <= 0) {
                        $msg = 'Eche màj lignes de commande pour prod #' . $id_p . ' - ' . $bdb->db->lasterror();
                        echo BimpRender::renderAlerts($msg);
                    }
                }
            }
            
            // Màj des factures non commissionnées: 
            $sql = BimpTools::getSqlSelect(array('rowid', 'buy_price_ht', 'fk_facture'));
            $sql .= BimpTools::getSqlFrom('facturedet', array(
                        array(
                            'table' => 'facture',
                            'on'    => 'f.rowid = a.fk_facture',
                            'alias' => 'f'
                        )
            ));
            $sql .= BimpTools::getSqlWhere(array(
                        'a.fk_product'             => (int) $id_p,
                        'a.buy_price_ht'           => '!= ' . $cur_pa,
                        'f.datec'                  => '> 2019-06-30 23:59:59',
                        'f.id_entrepot_commission' => 0,
                        'f.id_user_commission'     => 0,
            ));

            $rows = $bdb->executeS($sql, 'array');
            
            if (is_array($rows) && !empty($rows)) {
                $lines = array();
                foreach ($rows as $r) {
                    if (!in_array((int) $r['rowid'], $lines)) {
                        $lines[] = (int) $r['rowid'];
                    }
                }
                if (!empty($lines)) {
                    if ($bdb->update('facturedet', array(
                                'buy_price_ht' => $cur_pa
                                    ), '`rowid` IN (' . implode(',', $lines) . ')') <= 0) {
                        $msg = 'Echec màj lignes de facture pour prod #' . $id_p . ' - ' . $bdb->db->lasterror();
                        echo BimpRender::renderAlerts($msg);
                    }
                }
            }
            
            // Créa des reval pour les factures déjà commissionnées. 
            $sql = BimpTools::getSqlSelect(array('rowid', 'buy_price_ht', 'fk_facture'));
            $sql .= BimpTools::getSqlFrom('facturedet', array(
                        array(
                            'table' => 'facture',
                            'on'    => 'f.rowid = a.fk_facture',
                            'alias' => 'f'
                        )
            ));
            $sql .= BimpTools::getSqlWhere(array(
                        'a.fk_product'   => (int) $id_p,
                        'a.buy_price_ht' => '!= ' . $cur_pa,
                        'f.datec'        => '> 2019-06-30 23:59:59',
                        'commissions'    => array(
                            'custom' => '(f.id_entrepot_commission > 0 OR f.id_user_commission > 0)'
                        )
            ));

            $rows = $bdb->executeS($sql, 'array');

            if (is_array($rows) && !empty($rows)) {
                $lines = array();
                foreach ($rows as $r) {
                    if (!in_array((int) $r['rowid'], $lines)) {
                        $lines[] = (int) $r['rowid'];
                    }
                }
            }
//            
            foreach ($lines as $id_line) {
                $line = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_FactureLine', array(
                            'id_line' => (int) $id_line
                ));

                if (!BimpObject::objectLoaded($line)) {
                    echo 'pas de ligne <br/>';
                    continue;
                }

                $amount = (float) $line->pa_ht - $cur_pa;

                if (!$amount) {
                    echo 'pas de amount <br/>';
                    continue;
                }

                // On vérifie qu'une reval n'existe pas déjà: 
                $id_facture = (int) $line->getData('id_obj');

                if (!$id_facture) {
                    echo 'pas de fac <br/>';
                    continue;
                }

                $reval = BimpCache::findBimpObjectInstance('bimpfinanc', 'BimpRevalorisation', array(
                            'id_facture'      => (int) $id_facture,
                            'id_facture_line' => (int) $line->id,
                            'type'            => 'correction_pa'
                ));

                if (BimpObject::objectLoaded($reval)) {
                    echo 'reval exist<br/>';
                    continue;
                }

                $reval = BimpObject::getInstance('bimpfinanc', 'BimpRevalorisation');

                $reval_errors = $reval->validateArray(array(
                    'id_facture'      => (int) $id_facture,
                    'id_facture_line' => (int) $line->id,
                    'type'            => 'correction_pa',
                    'date'            => date('Y-m-d'),
                    'amount'          => $amount,
                    'qty'             => (float) $line->qty,
                    'note'            => "Correction automatique du prix d'achat. Prix d'achat enregistré: " . $line->pa_ht . ". Prix d'achat réel: " . $cur_pa
                ));

                if (!count($reval_errors)) {
                    $reval_warnings = array();
                    $reval_errors = $reval->create($reval_warnings, true);
                }

                if (count($reval_errors)) {
                    echo BimpRender::renderAlerts(BimpTools::getMsgFromArray($reval_errors, 'Prod #' . $id_p . ': Echec de la créa de la reval pour ligne #' . $line->getData('id_line') . ' - fac #' . $id_facture));
                } else {
                    echo 'REVAL OK ' . $reval->id . '<br/>';
                }
            }
        }
    }
    unset($prod);
    BimpCache::$cache = array();
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();



    