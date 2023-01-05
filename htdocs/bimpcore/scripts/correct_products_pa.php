<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';

top_htmlhead('', 'MAJ PA PRODUITS', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db;
$bdb = new BimpDb($db);

set_time_limit(1200);

//UPDATE ERP_TEST_BIMP_FLODEV.`llx_product` p1 SET p1.`cur_pa_ht` = (SELECT p2.pmp FROM ERP_TEST_BIMP_2019.`llx_product` p2 WHERE p1.rowid = p2.rowid) WHERE p1.fk_product_type = 0;
//UPDATE `llx_propaldet` l 
//LEFT JOIN llx_propal p ON l.`fk_propal` = p.rowid
//LEFT JOIN llx_product pr ON pr.rowid = l.fk_product
//SET l.`buy_price_ht` = pr.cur_pa_ht, l.`fk_product_fournisseur_price` = pr.id_cur_fp
//WHERE l.fk_product > 0 AND p.datec > '2019-06-30' AND pr.fk_product_type = 0;
//UPDATE `llx_commandedet` l 
//LEFT JOIN llx_commande c ON c.rowid = l.`fk_commande` 
//LEFT JOIN llx_product p ON p.rowid = l.fk_product
//SET l.`buy_price_ht` = p.cur_pa_ht, l.fk_product_fournisseur_price = p.id_cur_fp
//WHERE l.fk_product > 0 AND c.logistique_status != 6 AND p.fk_product_type = 0;
//UPDATE `llx_facturedet` l 
//LEFT JOIN llx_facture f ON l.fk_facture = f.rowid
//LEFT JOIN llx_product p ON p.rowid = l.fk_product
//SET l.`buy_price_ht` = p.cur_pa_ht, l.`fk_product_fournisseur_price` = p.id_cur_fp
//WHERE f.datec > '2019-06-30' AND p.fk_product_type = 0 AND l.fk_product > 0;


$product = BimpObject::getInstance('bimpcore', 'Bimp_Product');

$prods = $product->getList(array(
    'cur_pa_ht'       => 0,
    'fk_product_type' => 0
        ), null, null, 'id', 'asc', 'array', array('rowid', 'pmp', 'validate'));


foreach ($prods as $p) {
    // On récup le dernier PA enregistré: 
    $sql = 'SELECT rowid as id, price FROM ' . MAIN_DB_PREFIX . 'product_fournisseur_price WHERE fk_product = ' . (int) $p['rowid'];
    $sql .= ' AND tms = (SELECT MAX(tms) FROM ' . MAIN_DB_PREFIX . 'product_fournisseur_price WHERE fk_product = ' . (int) $p['rowid'] . ')';

    $result = $bdb->executeS($sql, 'array');

    $pa = 0;
    $id_fp = 0;

    if (isset($result[0]['id']) && (float) $result[0]['price']) {
        echo $p['rowid'] . ': (PA FP) ' . $result[0]['id'] . ' => ' . $result[0]['price'] . '<br/>';
        if ($bdb->update('product', array(
                    'cur_pa_ht' => (float) $result[0]['price'],
                    'id_cur_fp' => (int) $result[0]['id']
                        ), '`rowid` = ' . (int) $p['rowid']) <= 0) {
            echo $bdb->db->lasterror() . '<br/>';
        }
    } elseif ((float) $p['pmp']) {
        // Pas de prix fourn trouvé on attribue le pmp: 
        echo $p['rowid'] . ': (pmp) ' . $p['pmp'] . '<br/>';
        if ($bdb->update('product', array(
                    'cur_pa_ht' => (float) $p['pmp'],
                    'id_cur_fp' => 0
                        ), '`rowid` = ' . (int) $p['rowid']) <= 0) {
            echo $bdb->db->lasterror() . '<br/>';
        }
    } else {
        // Pas de pmp, PA prévu?

        $pa_prevu = 0;

        if (!(int) $p['validate']) {
            $pa_prevu = $bdb->getValue('product_extrafields', 'pa_prevu', '`fk_object` = ' . (int) $p['rowid']);
        }

        if ((float) $pa_prevu) {
            echo $p['rowid'] . ': (pa prévu) ' . $pa_prevu . '<br/>';
            if ($bdb->update('product', array(
                        'cur_pa_ht' => (float) $pa_prevu,
                        'id_cur_fp' => 0
                            ), '`rowid` = ' . (int) $p['rowid']) <= 0) {
                echo $bdb->db->lasterror() . '<br/>';
            }
        } else {
            echo '<span class="danger">';
            echo $p['rowid'] . ' : [AUCUN PA TROUVE]<br/>';
            echo '</span>';
        }
    }
}

echo '</body></html>';

//llxFooter();
