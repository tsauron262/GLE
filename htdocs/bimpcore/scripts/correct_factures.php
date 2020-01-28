<?php

define('NOLOGIN', '1');

require_once("../../main.inc.php");
require_once __DIR__ . '/../Bimp_Lib.php';

ini_set('display_errors', 1);
set_time_limit(0);

//llxHeader();

echo '<!DOCTYPE html>';
echo '<html lang="fr">';

echo '<head>';
//echo '<link rel="stylesheet" type="text/css" href="' . DOL_URL_ROOT . '/bimpcore/views/css/ticket.css' . '"/>';
echo '<script src="/test2/includes/jquery/js/jquery.min.js?version=6.0.4" type="text/javascript"></script>';
echo '</head>';

echo '<body>';

global $db;
$bdb = new BimpDb($db);

$sql = BimpTools::getSqlSelect(array(
            'a.id_propal',
            'a.id_facture',
        ));

$sql .= BimpTools::getSqlFrom('bs_sav', array(array(
                'table' => 'facture',
                'alias' => 'f',
                'on'    => 'f.rowid = a.id_facture'
        )));

$sql .= BimpTools::getSqlWhere(array(
            'a.id_facture' => array(
                'operator' => '>',
                'value'    => 0
            ),
            'f.datec'      => array(
                'operator' => '>',
                'value'    => '2019-05-22'
            )
        ));

$rows = $bdb->executeS($sql, 'array');

if (!is_null($rows)) {
    $n1 = 0;
    $n2 = 0;

    foreach ($rows as $r) {
        $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $r['id_facture']);
        if (!BimpObject::objectLoaded($facture)) {
            echo BimpRender::renderAlerts('FACTURE NON TROUVEE ' . $r['id_facture']);
        } else {
            $lines = $facture->getLines('not_text');

            if (count($lines)) {
//                echo 'FAC ' . $r['id_facture'] . '<br/>';

                $propal_lines_done = array();
                foreach ($lines as $line) {
                    if (!(float) $line->pa_ht) {
                        $where = '';

                        if ((int) $line->id_product) {
                            $where = '`fk_product` = ' . (int) $line->id_product;
                        } else {
                            $where = '`description` LIKE \'' . $line->desc . '\'';
                        }

                        $where .= ' AND `qty` = ' . (int) $line->qty . ' AND `subprice` = ' . (float) $line->pu_ht . ' AND `fk_propal` = ' . (int) $r['id_propal'];

                        $propal_rows = $bdb->getRows('propaldet', $where, null, 'array', array(
                            'rowid',
                            'buy_price_ht',
                            'fk_product_fournisseur_price'
                                ), 'rang', 'asc');

                        $pr = null;
                        if (!is_null($propal_rows)) {
                            foreach ($propal_rows as $prow) {
                                if (in_array($pr['rowid'], $propal_lines_done)) {
                                    continue;
                                }

                                $pr = $prow;
                                break;
                            }

                            echo 'MAJ LINE ' . $line->id . ': ' . $pr['rowid'] . ': ' . $pr['buy_price_ht'] . ', ' . $pr['fk_product_fournisseur_price'];

                            if (!is_null($pr) && ((float) $pr['buy_price_ht'] || (int) $pr['fk_product_fournisseur_price'])) {
//                                if ((float) $pr['buy_price_ht']) {
//                                    echo $facture->getData('facnumber') . ': ligne ' . $line->id . ' - ' . $line->desc . '. PA HT: ' . $pr['buy_price_ht'];
//                                    echo '<br/>';
//                                    $n1++;
//                                } elseif ((int) $pr['fk_product_fournisseur_price']) {
//                                    $pfp = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ProductFournPrice', (int) $pr['fk_product_fournisseur_price']);
//                                    if (BimpObject::objectLoaded($pfp)) {
//                                        $price = (float) $pfp->getData('price');
//                                        echo '<strong>' . $facture->getData('facnumber') . ': ligne ' . $line->id . ' - ' . $line->desc . '. PA HT: ' . $price . '</strong>';
//                                        echo '<br/>';
//                                        $n2++;
//                                    }
//                                }

                                $propal_lines_done[] = (int) $pr['rowid'];

                                $line->pa_ht = (float) $pr['buy_price_ht'];
                                $line->id_fourn_price = (int) $r['fk_product_fournisseur_price'];

                                if ($bdb->update('facturedet', array(
                                    'buy_price_ht'                 => (float) $pr['buy_price_ht'],
                                    'fk_product_fournisseur_price' => (int) $r['fk_product_fournisseur_price']
                                        ), '`rowid` = ' . (int) $line->getData('id_line')) <= 0) {
                                    echo '[ECHEC] '.$bdb->db->lasterror();
                                } else {
                                    echo '[OK]';
                                }
                                echo '<br/>';
                            } else {
                                echo 'AUCUNE LIGNE DE PROPAL CORRESPONDANTE TROUVEE <br/>';
                            }
                        } else {
                            echo 'AUCUN LIGNE DE PROPAL TROUVEE <br/>';
                        }

                        echo '<br/>';
                    }
                }
            }
        }
    }

    echo '<br/><br/>';
    echo 'N1: ' . $n1 . '<br/>';
    echo 'N2: ' . $n2 . '<br/>';
} else {
    echo 'AUCUNE FACTURE TROUVEE';
}

echo '</body></html>';

//llxFooter();
